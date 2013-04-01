<?php
/**
 * UrlMapper InstallGadget event
 *
 * @category   Gadget
 * @package    UrlMapper
 * @author     Mojtaba Ebrahimi <ebrahimi@zehneziba.ir>
 * @copyright  2013 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class UrlMapper_Events_HTTPError extends Jaws_Gadget
{
    /**
     * Event execute method
     *
     */
    function Execute($code)
    {
        $reqURL = Jaws_Utils::getRequestURL(true);
        $uModel = $GLOBALS['app']->loadGadget('UrlMapper', 'AdminModel');
        $res = $uModel->GetHTTPError($reqURL, $code);
        if (!Jaws_Error::IsError($res) && !empty($res) && ($res['code'] == 301 || $res['code'] == 302)) {
            Jaws_Header::Location($res['url'], true, $res['code']);
        }

        return $res;
    }

}