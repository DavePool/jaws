<?php
/**
 * Policy Admin Gadget
 *
 * @category   Gadget
 * @package    Policy
 * @author     Amir Mohammad Saied <amir@gluegadget.com>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2007-2020 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class Policy_Actions_Admin_IP extends Policy_Actions_Admin_Default
{

    /**
     * Returns an array with all the blocked IP ranges available
     *
     * @access  public
     * @param   int     $offset  offset of data needed
     * @return  array   Array of blocked IPs
     */
    function GetBlockedIPRanges($offset = null)
    {
        $model  = $this->gadget->model->loadAdmin('IP');
        $ipRanges = $model->GetBlockedIPs(12, $offset);
        if (Jaws_Error::IsError($ipRanges)) {
            return array();
        }

        $newData = array();
        foreach ($ipRanges as $ipRange) {
            $ipData = array();
            $ipData['from_ip'] = long2ip($ipRange['from_ip']);
            $ipData['to_ip']   = long2ip($ipRange['to_ip']);

            $actions = '';
            if ($this->gadget->GetPermission('ManageIPs')) {
                $ipWidget =& Piwi::CreateWidget('Link', Jaws::t('EDIT'),
                    "javascript:editIPRange(this, '".$ipRange['id']."');",
                    STOCK_EDIT);
                $actions.= $ipWidget->Get().'&nbsp;';

                $ipWidget =& Piwi::CreateWidget('Link',
                    Jaws::t('DELETE', _t('POLICY_IP_RANGE')),
                    "javascript:deleteIPRange(this, '".$ipRange['id']."');",
                    STOCK_DELETE);
                $actions.= $ipWidget->Get();
            }
            $ipData['actions'] = $actions;
            $newData[] = $ipData;
        }
        return $newData;
    }

    /**
     * Returns the Blocked IPs Datagrid
     *
     * @access  public
     * @return  XHTML content
     */
    function IPsDatagrid()
    {
        $model = $this->gadget->model->loadAdmin('IP');
        $totalIPs = $model->GetTotalOfBlockedIPs();

        $grid =& Piwi::CreateWidget('DataGrid', array(), null);
        $grid->SetID('blocked_ips_datagrid');
        $grid->TotalRows($totalIPs);
        $grid->pageBy(12);
        $grid->AddColumn(Piwi::CreateWidget('Column', Jaws::t('FROM')));
        $column2 = Piwi::CreateWidget('Column', Jaws::t('TO'), null, false);
        $column2->SetStyle('width: 120px;');
        $grid->AddColumn($column2);
        $column3 = Piwi::CreateWidget('Column', Jaws::t('ACTIONS'), null, false);
        $column3->SetStyle('width: 60px;');
        $grid->AddColumn($column3);

        return $grid->Get();
    }

    /**
     * IPBlokcing action for the Policy gadget
     *
     * @access  public
     * @return  XHTML content
     */
    function IPBlocking()
    {
        $this->gadget->CheckPermission('IPBlocking');
        $this->AjaxMe('script.js');
        $this->gadget->define('incompleteFields',     Jaws::t('ERROR_INCOMPLETE_FIELDS'));
        $this->gadget->define('confirmIPRangeDelete', _t('POLICY_RESPONSE_CONFIRM_DELETE_IP'));

        $tpl = $this->gadget->template->loadAdmin('IPBlocking.html');
        $tpl->SetBlock('ipblocking');

        // Sidebar
        $tpl->SetVariable('sidebar', $this->SideBar('IPBlocking'));
        $tpl->SetVariable('blocked_ips_datagrid', $this->IPsDatagrid());

        $default = $this->gadget->registry->fetch('block_undefined_ip') == 'true';
        $blockUndefined =& Piwi::CreateWidget('CheckButtons', 'ipblocking');
        $blockUndefined->AddOption(_t('POLICY_IP_BLOCK_UNDEFINED'),
            'true',
            'block_undefined_ip',
            $default);
        $blockUndefined->AddEvent(ON_CLICK, 'javascript:setBlockUndefinedIP();');
        $tpl->SetVariable('enabled_option', $blockUndefined->Get());

        $tpl->SetVariable('legend_title', _t('POLICY_IP_RANGE'));
        $fromIPAddress =& Piwi::CreateWidget('Entry', 'from_ipaddress', '');
        $fromIPAddress->setSize(24);
        $tpl->SetVariable('lbl_from_ipaddress', Jaws::t('FROM'));
        $tpl->SetVariable('from_ipaddress', $fromIPAddress->Get());

        $toIPAddress =& Piwi::CreateWidget('Entry', 'to_ipaddress', '');
        $toIPAddress->setSize(24);
        $tpl->SetVariable('lbl_to_ipaddress', Jaws::t('TO'));
        $tpl->SetVariable('to_ipaddress', $toIPAddress->Get());

        $script =& Piwi::CreateWidget('Combo', 'script');
        $script->SetID('script');
        $script->setStyle('width: 14em;');
        $script->AddOption(_t('POLICY_SCRIPT_INDEX'), 'index');
        $script->AddOption(_t('POLICY_SCRIPT_ADMIN'), 'admin');
        $script->AddOption(_t('POLICY_SCRIPT_BOTH'),  '');
        $script->SetDefault('index');
        $tpl->SetVariable('lbl_script', _t('POLICY_SCRIPT'));
        $tpl->SetVariable('script', $script->Get());

        $order =& Piwi::CreateWidget('Entry', 'order', '');
        $order->setSize(10);
        $tpl->SetVariable('lbl_order', _t('POLICY_ORDER'));
        $tpl->SetVariable('order', $order->Get());

        $blocked =& Piwi::CreateWidget('Combo', 'blocked');
        $blocked->SetID('blocked');
        $blocked->setStyle('width: 120px;');
        $blocked->AddOption(Jaws::t('NO'),  0);
        $blocked->AddOption(Jaws::t('YES'), 1);
        $blocked->SetDefault('1');
        $tpl->SetVariable('lbl_blocked', _t('POLICY_BLOCKED'));
        $tpl->SetVariable('blocked', $blocked->Get());

        if ($this->gadget->GetPermission('ManageIPs')) {
            $btnSave =& Piwi::CreateWidget('Button', 'btn_save', Jaws::t('SAVE'), STOCK_SAVE);
            $btnSave->AddEvent(ON_CLICK, 'javascript:saveIPRange();');
            $tpl->SetVariable('btn_save', $btnSave->Get());

            $btnCancel =& Piwi::CreateWidget('Button', 'btn_cancel', Jaws::t('CANCEL'), STOCK_CANCEL);
            $btnCancel->AddEvent(ON_CLICK, 'javascript:stopAction();');
            $tpl->SetVariable('btn_cancel', $btnCancel->Get());
        }

        $tpl->ParseBlock('ipblocking');

        return $tpl->Get();
    }
}