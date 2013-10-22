<?php
/**
 * SysInfo Core Gadget
 *
 * @category   Gadget
 * @package    SysInfo
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2008-2013 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class SysInfo_Actions_DirInfo extends Jaws_Gadget_HTML
{
    /**
     * Displays information about Jaws's main directories like permissions, ...
     *
     * @access  public
     * @return  string XHTML template content
     */
    function DirInfo()
    {
        if (!$GLOBALS['app']->Session->GetPermission('SysInfo', 'DirInfo')) {
            return false;
        }

        $model = $this->gadget->loadModel('DirInfo');
        $tpl = $this->gadget->loadTemplate('SysInfo.html');
        $tpl->SetBlock('SysInfo');
        $tpl->SetVariable('title',  _t('SYSINFO_DIRINFO'));

        //Directory Permissions
        $tpl->SetBlock('SysInfo/InfoSection');
        $items = $model->GetDirsPermissions();
        foreach ($items as $item) {
            $tpl->SetBlock('SysInfo/InfoSection/InfoItem');
            $tpl->SetVariable('item_title', $item['title']);
            $tpl->SetVariable('item_value', $item['value']);
            $tpl->ParseBlock('SysInfo/InfoSection/InfoItem');
        }
        $tpl->ParseBlock('SysInfo/InfoSection');

        $tpl->ParseBlock('SysInfo');
        return $tpl->Get();
    }
}