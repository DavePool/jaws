<?php
/**
 * Session ID name
 */
define('JAWS_SESSION_NAME', 'JAWSSESSID');

/**
 * Class to manage the session when user is running a web application
 *
 * @category   Session
 * @package    Core
 * @author     Ivan -sk8- Chavero <imcsk8@gluch.org.mx>
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2005-2015 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class Jaws_Session_Web extends Jaws_Session
{
    /**
     * Initializes the Session
     *
     * @access  public
     * @return  void
     */
    function init()
    {
        parent::Init();
        $session = $this->GetCookie(JAWS_SESSION_NAME);
        if (empty($session) || !$this->Load($session)) {
            $this->Create();
        }
    }

    /**
     * Create a new session for a given data
     *
     * @access  public
     * @param   array   $info       User attributes
     * @param   bool    $remember   Remember me
     * @return  void
     * @see     Jaws_Session::Create
     */
    function Create($info = array(), $remember = false)
    {
        parent::Create($info, $remember);
        // Create cookie
        $this->SetCookie(
            JAWS_SESSION_NAME,
            $this->ssid.'-'.$this->salt,
            $remember? 60*(int)$GLOBALS['app']->Registry->fetch('session_remember_timeout', 'Policy') : 0
        );
    }

    /**
     * Logout from session
     *
     * @access  public
     * @param   bool    $prepare_new_session Preparing new session for incoming request
     * @return  void
     * @see Jaws_Session::Logout
     */
    function Logout($prepare_new_session = false)
    {
        parent::Logout($prepare_new_session);
        if ($prepare_new_session) {
            $this->DestroyCookie(JAWS_SESSION_NAME);
        } else {
            $this->SetCookie(JAWS_SESSION_NAME, $this->ssid.'-'.$this->salt, 0, true);
        }
    }

    /**
     * Create a new cookie on client
     *
     * @access  public
     * @param   string  $name       Cookie name
     * @param   string  $value      Cookie value
     * @param   int     $minutes    The time the cookie expires
     * @param   bool    $httponly   If TRUE the cookie will be made accessible only through the HTTP protocol
     * @return  void
     */
    function SetCookie($name, $value, $minutes = 0, $httponly = null)
    {
        if (defined('SESSION_INVALID')) {
            return false;
        }

        $version = $GLOBALS['app']->Registry->fetch('cookie_version', 'Settings');
        $expires = ($minutes == 0)? 0 : (time() + $minutes*60);
        $path    = $GLOBALS['app']->getSiteURL('/', true);
        $domain  = '';//$GLOBALS['app']->Registry->fetch('cookie_domain', 'Settings');
        // secure
        $secure = $GLOBALS['app']->Registry->fetch('cookie_secure', 'Settings') == 'true';
        $secure = $secure && (strtolower($_SERVER['HTTPS']) == 'on');
        // http only
        if (is_null($httponly)) {
            $httponly = $GLOBALS['app']->Registry->fetch('cookie_httponly', 'Settings') == 'true';
        }
        setcookie($name, $value, $expires, $path, $domain, $secure, $httponly);
    }

    /**
     * Get a cookie
     *
     * @access  public
     * @param   string  $name   Cookie name
     * @return  string
     */
    function GetCookie($name)
    {
        $version = $GLOBALS['app']->Registry->fetch('cookie_version', 'Settings');
        return jaws()->request->fetch($name, 'cookie');
    }

    /**
     * Destroy a cookie
     *
     * @access  public
     * @param   string  $name   Cookie name
     * @return  void
     */
    function DestroyCookie($name)
    {
        if (defined('SESSION_INVALID')) {
            return false;
        }

        $this->SetCookie($name, false);
    }

    /**
     * Check permission on a given gadget/task
     *
     * @access  public
     * @param   string  $gadget         Gadget name
     * @param   string  $key            ACL key(s) name
     * @param   string  $subkey         ACL subkey name
     * @param   bool    $together       And/Or tasks permission result, default true
     * @param   string  $errorMessage   Error message to return
     * @return  mixed   True if granted, else throws an Exception(Jaws_Error::Fatal)
     */
    function CheckPermission($gadget, $key, $subkey = '', $together = true, $errorMessage = '')
    {
        if ($this->GetPermission($gadget, $key, $subkey, $together)) {
            return true;
        }

        $user = Jaws_Gadget::getInstance('Users')->action->load('Default');
        $result = $user->ShowNoPermission($this->GetAttribute('username'), $gadget, $key);
        $result = Jaws_HTTPError::Get(403, '', $result);

        terminate($result, 403);
    }

}