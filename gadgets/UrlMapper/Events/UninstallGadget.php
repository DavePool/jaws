<?php
/**
 * UrlMapper UninstallGadget event
 *
 * @category   Gadget
 * @package    UrlMapper
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2013 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class UrlMapper_Events_UninstallGadget extends Jaws_Gadget_Event
{
    /**
     * Event execute method
     *
     */
    function Execute($gadget)
    {
        $uModel = $this->gadget->loadAdminModel('Maps');
        $res = $uModel->DeleteGadgetMaps($gadget);
        return $res;
    }

}