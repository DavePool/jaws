<?php
/**
 * ServerTime Gadget
 *
 * @category   Gadget
 * @package    ServerTime
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2004-2013 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class ServerTime_AdminHTML extends Jaws_Gadget_HTML
{
    /**
     * Displays the administration page
     *
     * @access  public
     * @return  string  XHTML template content
     */
    function Admin()
    {
        $this->AjaxMe('script.js');

        $model = $GLOBALS['app']->LoadGadget('ServerTime', 'AdminModel');

        $tpl = new Jaws_Template('gadgets/ServerTime/templates/');
        $tpl->Load('AdminServerTime.html');
        $tpl->SetBlock('servertime');

        $form =& Piwi::CreateWidget('Form', BASE_SCRIPT, 'post');
        $form->Add(Piwi::CreateWidget('HiddenEntry', 'gadget', 'ServerTime'));
        $form->Add(Piwi::CreateWidget('HiddenEntry', 'action', 'UpdateProperties'));

        include_once JAWS_PATH . 'include/Jaws/Widgets/FieldSet.php';
        $fieldset = new Jaws_Widgets_FieldSet(_t('SERVERTIME_NAME'));
        $fieldset->SetDirection('vertical');
        $fieldset->SetStyle('white-space: nowrap;');

        $now = time();
        $objDate = $GLOBALS['app']->loadDate();
        $dFormat =& Piwi::CreateWidget('Combo', 'date_format');
        $dFormat->SetID('date_format');
        $dFormat->SetTitle(_t('SERVERTIME_FORMAT_TEXT'));
        $dFormat->SetStyle('width: 300px;');
        $dFormat->AddOption($objDate->Format($now, 'MN j, g:i a'),     'MN j, g:i a');
        $dFormat->AddOption($objDate->Format($now, 'j.m.y'),           'j.m.y');
        $dFormat->AddOption($objDate->Format($now, 'j MN, g:i a'),     'j MN, g:i a');
        $dFormat->AddOption($objDate->Format($now, 'y.m.d, g:i a'),    'y.m.d, g:i a');
        $dFormat->AddOption($objDate->Format($now, 'd MN Y'),          'd MN Y');
        $dFormat->AddOption($objDate->Format($now, 'DN d MN Y'),       'DN d MN Y');
        $dFormat->AddOption($objDate->Format($now, 'DN d MN Y g:i a'), 'DN d MN Y g:i a');
        $dFormat->AddOption($objDate->Format($now, 'j MN y'),          'j MN y');
        $dFormat->SetDefault($this->gadget->registry->fetch('date_format'));
        $fieldset->Add($dFormat);

        $form->Add($fieldset);
        $submit =& Piwi::CreateWidget('Button', 'save', _t('GLOBAL_UPDATE', _t('GLOBAL_SETTINGS')), STOCK_SAVE);
        $submit->SetStyle(_t('GLOBAL_LANG_DIRECTION')=='rtl'?'float: left;' : 'float: right;');
        $submit->AddEvent(ON_CLICK, 'javascript: updateProperties(this.form);');
        $form->Add($submit);

        $tpl->SetVariable('form', $form->Get());

        $tpl->ParseBlock('servertime');
        return $tpl->Get();
    }

}