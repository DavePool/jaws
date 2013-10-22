<?php
/**
 * Menu DisableGadget event
 *
 * @category    Gadget
 * @package     Menu
 * @author      Ali Fazelzadeh <afz@php.net>
 * @copyright   2013 Jaws Development Group
 * @license     http://www.gnu.org/copyleft/lesser.html
 */
class Menu_Events_DisableGadget extends Jaws_Gadget_Event
{
    /**
     * Event execute method
     *
     */
    function Execute($gadget)
    {
        $model = $this->gadget->loadAdminModel('Menu');
        $res = $model->PublishGadgetMenus($gadget, false);
        return $res;
    }

}