<?php
/**
 * Class that deals like a wrapper between Jaws and pear/Mail
 *
 * @category   Mail
 * @package    Core
 * @author     David Coallier <davidc@agoraproduction.com>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2005-2012 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class Jaws_Mail
{
    /**
     * The mailer type
     * @param   string $mailer The mailer type
     */
    var $mailer = '';

    /**
     * Send email via this email
     * @param   string $gate_email The default site from email address
     */
    var $gate_email = '';

    /**
     * From name
     * @param   string $gate_title The default site from email name
     */
    var $gate_title = '';

    /**
     * default site email address
     * @param   string $site_email The default site email address
     */
    var $site_email = '';

    /**
     * site email name
     * @param   string $site_name The default site email name
     */
    var $site_name = '';

    /**
     * SMTP email verification?
     * @param   bool    $smtp_vrfy SMTP email verification?
     */
    var $smtp_vrfy = false;

    // {{{ Variables
    /**
     * The server infos (host,login,pass)
     * @param   array $server The server infos
     */
    var $params = array();

    /**
     * The email recipients.
     * @param   array $recipients The recipients.
     */
    var $recipient = array();

    /**
     * The email headers
     *
     * @param   array string $headers The headers of the mail.
     */
    var $headers = array();

    /**
     * The crlf character(s)
     *
     * @param   string $crlf
     */
    var $crlf = "\n";

    /**
     * A object of Mail_Mime
     *
     * @param object $mail_mime
     */
    var $mail_mime;

    /**
     * This creates the mail object that will
     * add recipient, send emails to destinated
     * email addresses calling functions.
     *
     * @access constructor
     */
    function Jaws_Mail($init = true)
    {
        require_once PEAR_PATH. 'Mail.php';
        require_once PEAR_PATH. 'Mail/mime.php';
        $this->mail_mime = new Mail_Mime($this->crlf);
        $this->headers['Subject'] = '';
        if ($init) {
            $this->Init();
        }
    }

    /**
     * This function loads the mail settings from
     * the registry.
     *
     * @access  public
     */
    function Init()
    {
        if (!isset($GLOBALS['app'])) {
            return new Jaws_Error('$GLOBALS[\'app\'] not available',
                                  __FUNCTION__);
        }

        // Get the Mail settings data from Registry
        $this->mailer     = $GLOBALS['app']->Registry->Get('/gadgets/Settings/mailer');
        $this->gate_email = $GLOBALS['app']->Registry->Get('/gadgets/Settings/gate_email');
        $this->gate_title = $GLOBALS['app']->Registry->Get('/gadgets/Settings/gate_title');
        $this->smtp_vrfy  = $GLOBALS['app']->Registry->Get('/gadgets/Settings/smtp_vrfy') == 'true';

        $this->site_email = $GLOBALS['app']->Registry->Get('/config/site_email');
        $this->site_name  = $GLOBALS['app']->Registry->Get('/config/site_name');

        $params = array();
        $params['sendmail_path'] = $GLOBALS['app']->Registry->Get('/gadgets/Settings/sendmail_path');
        $params['sendmail_args'] = $GLOBALS['app']->Registry->Get('/gadgets/Settings/sendmail_args');
        $params['host']          = $GLOBALS['app']->Registry->Get('/gadgets/Settings/smtp_host');
        $params['port']          = $GLOBALS['app']->Registry->Get('/gadgets/Settings/smtp_port');
        $params['auth']          = $GLOBALS['app']->Registry->Get('/gadgets/Settings/smtp_auth')  == 'true';
        $params['pipelining']    = $GLOBALS['app']->Registry->Get('/gadgets/Settings/pipelining') == 'true';
        $params['username']      = $GLOBALS['app']->Registry->Get('/gadgets/Settings/smtp_user');
        $params['password']      = $GLOBALS['app']->Registry->Get('/gadgets/Settings/smtp_pass');

        $this->params = $params;
        return $this->params;
    }

    /**
     * This adds a recipient to the mail to send.
     *
     * @param   string $recipient     The recipient to add.
     * @param   string $inform_type   Inform type(To, Bcc, Cc)
     * @access  public
     * @return  string recipients
     */
    function AddRecipient($recipient = '', $inform_type = 'To')
    {
        if (empty($recipient)) {
            $recipient = $this->site_email;
            if (!empty($this->site_name)) {
               $recipient = $this->site_name . ' <'. $recipient. '>';
            }
        }

        switch (strtolower($inform_type)) {
            case 'to':
                $this->headers['To'] = (array_key_exists('To', $this->headers)? ($this->headers['To']. ', ') : ''). $recipient;
                break;
            case 'cc':
                $this->headers['Cc'] = (array_key_exists('Cc', $this->headers)? ($this->headers['Cc']. ', ') : ''). $recipient;
                break;
        }

        if (substr($recipient, -1) == '>' && substr($recipient, 0, 1) != '<') {
            $parts = array_filter(explode('<', $recipient));
            $parts[0] = Jaws_UTF8::encode_mimeheader(Jaws_UTF8::trim($parts[0]));
            $recipient = implode('<', $parts);
        }

        $this->recipient[] = $recipient;
        return true;
    }

    /**
     * This function sets the subject of the email to send.
     *
     * @param   string $subject       Subject of the email.
     * @access  public
     * @return  void
     */
    function SetSubject($subject = '')
    {
        $this->headers['Subject'] = $subject;
    }

    /**
     * This function sets the from of the email to send.
     *
     * @param   string $from_email    Who the email is from(E-mail address).
     * @param   string $from_name     Who the email is from(name).
     * @access  public
     * @return  voild
     */
    function SetFrom($from_email = '', $from_name = '')
    {
        if ($this->smtp_vrfy) {
            $replyTo    = $from_name . ' <'.$from_email.'>';
            $from_name  = $this->gate_title;
            $from_email = $this->gate_email;
        } else {
            $from_name  = empty($from_email)? $this->gate_title : $from_name;
            $from_email = empty($from_email)? $this->gate_email : $from_email;
        }

        $this->headers['From'] = $from_name . ' <'.$from_email.'>';
        $this->headers['Reply-To'] = isset($replyTo)? $replyTo : $this->headers['From'];
    }

    /**
     * This function sets the body, the structure
     * of the email, what's in it..
     *
     * @param   string $body   The body of the email
     * @param   string $format The format to use.
     * @access  protected
     * @return  string $body
     */
    function SetBody($body, $format = 'html')
    {
        if (!isset($body) && empty($body)) {
            return false;
        }

        switch ($format) {
            case 'file':
                $res = $this->mail_mime->addAttachment($body);
                break;
            case 'image':
                $res = $this->mail_mime->addHTMLImage($body);
                break;
            case 'html':
                $res = $this->mail_mime->setHTMLBody($body);
                break;
            case 'text':
                $res = $this->mail_mime->setTXTBody($body);
                break;
            default:
                $res = false;
        }

        return $res;
    }

    /**
     * This function sends the email
     *
     * @access  public
     * @return  mixed
     */
    function send()
    {
        $mail = null;
        switch ($this->mailer) {
            case 'phpmail':
                $mail =& Mail::factory('mail');
                break;
            case 'sendmail':
                $mail =& Mail::factory('sendmail', $this->params);
                break;
            case 'smtp':
                $mail =& Mail::factory('smtp', $this->params);
                break;
            default:
                return false;
        }

        $realbody = $this->mail_mime->get(array('html_encoding' => '8bit',
                                                'text_encoding' => '8bit',
                                                'head_encoding' => 'base64',
                                                'html_charset'  => 'utf-8',
                                                'text_charset'  => 'utf-8',
                                                'head_charset'  => 'utf-8',
                                                ));

        if (empty($this->recipient)) {
            $this->AddRecipient();
        }

        $headers  = $this->mail_mime->headers($this->headers);
        $res = $mail->send($this->recipient, $headers, $realbody);
        if (PEAR::isError($res)) {
            return new Jaws_Error($res->getMessage(),
                                  __FUNCTION__);
        }

        return true;
    }

    /**
     * Resets the values and updates
     *
     * @access  public
     */
    function ResetValues()
    {
        $this->headers = array();
        $this->headers['Subject'] = '';

        $this->recipient = array();
        unset($this->mail_mime);
        $this->mail_mime = new Mail_Mime($this->crlf);
    }

}