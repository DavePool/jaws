<?php
/**
 * Quotes Gadget Action
 *
 * @category   GadgetAdmin
 * @package    Quotes
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2007-2013 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class Quotes_AdminAction extends Jaws_Gadget_Action
{
    /**
     * Calls default admin action
     *
     * @access       public
     * @return       string  Template content
     */
    function Admin()
    {
        if ($this->gadget->GetPermission('ManageQuotes')) {
            return $this->Quotes();
        } elseif ($this->gadget->GetPermission('ManageQuoteGroups')) {
            return $this->QuoteGroups();
        }

        $this->gadget->CheckPermission('Properties');
    }

    /**
     * Prepares the quotes menubar
     *
     * @access  public
     * @param   string  $action   Selected action
     * @return  string  XHTML of menubar
     */
    function MenuBar($action)
    {
        $actions = array('Quotes', 'QuoteGroups');
        if (!in_array($action, $actions)) {
            $action = 'Quotes';
        }

        require_once JAWS_PATH . 'include/Jaws/Widgets/Menubar.php';
        $menubar = new Jaws_Widgets_Menubar();
        if ($this->gadget->GetPermission('ManageQuotes')) {
            $menubar->AddOption('Quotes', _t('QUOTES_NAME'),
                                BASE_SCRIPT . '?gadget=Quotes&amp;action=Admin', 'gadgets/Quotes/Resources/images/quotes_mini.png');
        }
        if ($this->gadget->GetPermission('ManageQuoteGroups')) {
            $menubar->AddOption('QuoteGroups', _t('QUOTES_GROUPS'),
                                BASE_SCRIPT . '?gadget=Quotes&amp;action=QuoteGroups', 'gadgets/Quotes/Resources/images/groups_mini.png');
        }
        $menubar->Activate($action);
        return $menubar->Get();
    }
}