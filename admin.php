<?php
/**
 * Admin page for jaws
 *
 * @category   Application
 * @package    Core
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Helgi �ormar <dufuz@php.net>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2005-2013 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
define('JAWS_SCRIPT', 'admin');
define('BASE_SCRIPT', basename(__FILE__));

// Redirect to the installer if JawsConfig can't be found.
$root = dirname(__FILE__);
if (!file_exists($root . '/config/JawsConfig.php')) {
    require_once 'include/Jaws/Utils.php';
    header('Location: '. Jaws_Utils::getBaseURL('/'). 'install/index.php');
    exit;
} else {
    require $root . '/config/JawsConfig.php';
}

require_once JAWS_PATH . 'include/Jaws/InitApplication.php';
$GLOBALS['app']->loadClass('Jaws_ACL', 'ACL');

$request =& Jaws_Request::getInstance();
$ReqGadget = Jaws_Gadget::filter($request->get('gadget', array('post', 'get')));
$ReqAction = Jaws_Gadget_HTML::filter($request->get('action', array('post', 'get')));
if (empty($ReqGadget)) {
    $ReqGadget = 'ControlPanel';
    $ReqAction = '';
}

$httpAuthEnabled = $GLOBALS['app']->Registry->fetch('http_auth', 'Settings') == 'true';
if ($httpAuthEnabled) {
    require_once JAWS_PATH . 'include/Jaws/HTTPAuth.php';
    $httpAuth = new Jaws_HTTPAuth();
}

// Check for login action is requested
if (!$GLOBALS['app']->Session->Logged())
{
    $loginMsg = '';
    if (($ReqGadget == 'ControlPanel' && $ReqAction == 'Login') ||
        ($httpAuthEnabled && isset($_SERVER['PHP_AUTH_USER'])))
    {
        if ($httpAuthEnabled) {
            $httpAuth->AssignData();
            $user   = $httpAuth->getUsername();
            $passwd = $httpAuth->getPassword();
        } else {
            $user    = $request->get('username', 'post');
            $passwd  = $request->get('password', 'post');
            $crypted = $request->get('usecrypt', 'post');

            if (isset($crypted)) {
                require_once JAWS_PATH . 'include/Jaws/Crypt.php';
                $JCrypt = new Jaws_Crypt();
                $JCrypt->Init();
                $passwd = $JCrypt->decrypt($passwd);
                if (Jaws_Error::IsError($passwd)) {
                    $passwd = '';
                }
            }
        }

        // check captcha
        $mPolicy = $GLOBALS['app']->LoadGadget('Policy', 'HTML');
        $resCheck = $mPolicy->checkCaptcha('login');
        if (!Jaws_Error::IsError($resCheck)) {
            $param = $request->get(array('redirect_to', 'remember', 'authtype'), 'post');
            $resCheck = $GLOBALS['app']->Session->Login(
                $user,
                $passwd, 
                isset($param['remember']),
                $param['authtype']
            );
        }
        if (!Jaws_Error::IsError($resCheck)) {
            // Can enter to Control Panel?
            if ($GLOBALS['app']->Session->GetPermission('ControlPanel', 'default_admin')) {
                $redirectTo = isset($param['redirect_to'])? $param['redirect_to'] : '';
                Jaws_Header::Location(hex2bin($redirectTo));
            } else {
                $GLOBALS['app']->Session->Logout();
                $loginMsg = _t('GLOBAL_ERROR_LOGIN_NOTCP');
            }
        } else {
            $loginMsg = $resCheck->GetMessage();
        }
    }

    if ($httpAuthEnabled) {
        $httpAuth->showLoginBox();
    } else {
        // Init layout
        $GLOBALS['app']->InstanceLayout();
        $cpl = $GLOBALS['app']->LoadGadget('ControlPanel', 'AdminHTML', 'Login');
        echo $cpl->LoginBox($loginMsg);
    }

    // Sync session
    $GLOBALS['app']->Session->Synchronize();
    $GLOBALS['log']->End();
    exit;
}

// remove checksess(check session) parameter from requested url
if (!is_null($request->get('checksess', 'get'))) {
    Jaws_Header::Location(substr(Jaws_Utils::getRequestURL(false), 0, -10));
}

// Can use Control Panel?
$GLOBALS['app']->Session->CheckPermission('ControlPanel', 'default_admin');

if (Jaws_Gadget::IsGadgetEnabled($ReqGadget)) {
    $GLOBALS['app']->Session->CheckPermission($ReqGadget, 'default_admin');
    $goGadget = $GLOBALS['app']->LoadGadget($ReqGadget, 'AdminHTML');
    if (Jaws_Error::IsError($goGadget)) {
        Jaws_Error::Fatal("Error loading gadget: $ReqGadget");
    }

    if (empty($ReqAction)) {
        $ReqAction = $goGadget->gadget->default_admin_action;
    }
    $GLOBALS['app']->SetMainRequest(false, $ReqGadget, $ReqAction);
    $IsReqActionStandAlone = $goGadget->IsStandAloneAdmin($ReqAction);
    if (!$IsReqActionStandAlone) {
        // Init layout
        $GLOBALS['app']->InstanceLayout();
    }

    $ReqResult = $goGadget->Execute($ReqAction);
    if (Jaws_Error::IsError($ReqResult)) {
        Jaws_Error::Fatal($ReqResult->getMessage());
    }

    if (!$IsReqActionStandAlone) {
        // Load ControlPanel header
        $GLOBALS['app']->Layout->LoadControlPanelHead();
        $GLOBALS['app']->Layout->Populate($ReqResult);
        $GLOBALS['app']->Layout->AddHeadLink(
            'gadgets/'.$ReqGadget.'/resources/style.css',
            'stylesheet',
            'text/css'
        );
        $GLOBALS['app']->Layout->LoadControlPanel($ReqGadget);
        $ReqResult = $GLOBALS['app']->Layout->Get();
    }
} else {
    Jaws_Error::Fatal('Invalid requested gadget');
}

// Send content to client
$resType = $request->get('restype');
switch ($resType) {
    case 'json':
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        echo Jaws_UTF8::json_encode($ReqResult);
        break;

    case 'gzip':
    case 'x-gzip':
        $ReqResult = gzencode($ReqResult, COMPRESS_LEVEL, FORCE_GZIP);
        header('Content-Length: '.strlen($ReqResult));
        header('Content-Encoding: '. $resType);
        echo $ReqResult;
        break;

    default:
        echo $ReqResult;
}

// Sync session
$GLOBALS['app']->Session->Synchronize();
$GLOBALS['log']->End();
exit;
