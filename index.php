<?php
/**
 * Index page for jaws
 *
 * @category   Application
 * @package    Core
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Helgi Þormar <dufuz@php.net>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2005-2015 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
define('JAWS_SCRIPT', 'index');
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

$IsIndex   = false;
$objAction = null;
$IsReqActionStandAlone = false;
// Only registered user can access not global website
$AccessToWebsiteDenied = !$GLOBALS['app']->Session->Logged() &&
                         $GLOBALS['app']->Registry->fetch('global_website', 'Settings') == 'false';

// Get forwarded error from webserver
$ReqError = jaws()->request->fetch('http_error', 'get');
if (empty($ReqError) && $GLOBALS['app']->Map->Parse()) {
    $ReqGadget = Jaws_Gadget::filter(jaws()->request->fetch('gadget'));
    $ReqAction = Jaws_Gadget_Action::filter(jaws()->request->fetch('action'));

    if (empty($ReqGadget)) {
        $IsIndex = true;
        $ReqGadget = $GLOBALS['app']->Registry->fetchByUser(
            $GLOBALS['app']->Session->GetAttribute('layout'),
            'main_gadget',
            'Settings'
        );
    }

    if (!empty($ReqGadget) && $ReqGadget != '-') {
        if (Jaws_Gadget::IsGadgetEnabled($ReqGadget)) {
            $objAction = Jaws_Gadget::getInstance($ReqGadget)->action->load();
            if (Jaws_Error::IsError($objAction)) {
                Jaws_Error::Fatal("Error loading gadget: $ReqGadget");
            }

            // check referrer host for internal action
            if ($objAction->getAttribute($ReqAction, 'internal') &&
                (!$GLOBALS['app']->Session->extraCheck() || Jaws_Utils::getReferrerHost() != $_SERVER['HTTP_HOST'])
            ) {
                $ReqError = '403';
            }

            $ReqAction = empty($ReqAction)? $objAction->gadget->default_action : $ReqAction;
            // set requested gadget/action
            $GLOBALS['app']->mainGadget = $ReqGadget;
            $GLOBALS['app']->mainAction = $ReqAction;
            $GLOBALS['app']->define('', 'mainGadget', $ReqGadget);
            $GLOBALS['app']->define('', 'mainAction', $ReqAction);
        } else {
            $ReqError = '404';
            $ReqGadget = null;
            $ReqAction = null;
        }
    }

    // if action not a global action and site is protected, so request redirected to login page
    if ($AccessToWebsiteDenied && (empty($objAction) || !$objAction->getAttribute($ReqAction, 'global'))) {
        $IsIndex = false;
        $ReqGadget = 'Users';
        $ReqAction = 'Login';
        $objAction = Jaws_Gadget::getInstance($ReqGadget)->action->load();
        if (Jaws_Error::IsError($objAction)) {
            Jaws_Error::Fatal("Error loading gadget: $ReqGadget");
        }

        $ReqError = '';
        // set requested gadget
        $GLOBALS['app']->mainGadget = $ReqGadget;
        $GLOBALS['app']->mainAction = $ReqAction;
        $GLOBALS['app']->define('', 'mainGadget', $ReqGadget);
        $GLOBALS['app']->define('', 'mainAction', $ReqAction);
    }
} else {
    $ReqError = empty($ReqError)? '404' : $ReqError;
    $ReqGadget = null;
    $ReqAction = null;
}

// set requested in front-end first/home page
$GLOBALS['app']->mainIndex = $IsIndex;
// Init layout...
$GLOBALS['app']->InstanceLayout();
$GLOBALS['app']->Layout->Load();

// Run auto-load methods before standalone actions too
$GLOBALS['app']->RunAutoload();

if (empty($ReqError)) {
    $ReqResult = '';
    if (!empty($objAction)) {
        // set in main request
        $GLOBALS['app']->inMainRequest = true;
        $ReqResult = $objAction->Execute($ReqAction);
        if (Jaws_Error::isError($ReqResult)) {
            $ReqResult = $ReqResult->GetMessage();
        }
        $GLOBALS['app']->inMainRequest = false;

        // we must check type of action after execute, because gadget can change it at runtime
        $ReqMode = Jaws_Gadget::filter(jaws()->request->fetch('mode'));
        $IsReqActionStandAlone = ($ReqMode == 'standalone') || $objAction->IsStandAlone($ReqAction);
    }
} else {
    $ReqResult = Jaws_HTTPError::Get($ReqError);
}

if (!$IsReqActionStandAlone) {
    $GLOBALS['app']->Layout->Populate($ReqResult, $AccessToWebsiteDenied);
    $ReqResult = $GLOBALS['app']->Layout->Get();
}

terminate($ReqResult);
