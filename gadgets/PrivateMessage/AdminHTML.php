<?php
/**
 * PrivateMessage Gadget Admin
 *
 * @category   GadgetAdmin
 * @package    PrivateMessage
 * @author     Mojtaba Ebrahimi <ebrahimi@zehneziba.ir>
 * @copyright  2013 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class PrivateMessage_AdminHTML extends Jaws_Gadget_HTML
{
    /**
     * Builds the users menubar
     *
     * @access  public
     * @param   string  $action   Selected action
     * @return  string  XHTML menubar
     */
    function MenuBar($action)
    {
        $actions = array('Properties');
        if (!in_array($action, $actions)) {
            $action = 'Properties';
        }

        require_once JAWS_PATH . 'include/Jaws/Widgets/Menubar.php';
        $menubar = new Jaws_Widgets_Menubar();
        if ($this->gadget->GetPermission('ManageProperties')) {
            $menubar->AddOption('Properties',
                                _t('GLOBAL_PROPERTIES'),
                                BASE_SCRIPT . '?gadget=PrivateMessage&amp;action=Properties',
                                STOCK_PREFERENCES);
        }
        $menubar->Activate($action);
        return $menubar->Get();
    }

}