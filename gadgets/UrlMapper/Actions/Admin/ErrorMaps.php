<?php
/**
 * UrlMapper Core Gadget Admin
 *
 * @category   Gadget
 * @package    UrlMapper
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @author     Mojtaba Ebrahimi <ebrahimi@zehneziba.ir>
 * @copyright  2006-2013 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class UrlMapper_Actions_Admin_ErrorMaps extends UrlMapper_AdminHTML
{
    /**
     * Builds error maps datagrid
     *
     * @access  public
     * @return  string  XHTML datagrid
     */
    function ErrorMapsDatagrid()
    {
        $datagrid =& Piwi::CreateWidget('DataGrid', array());
        $datagrid->setID('errormaps_datagrid');

        $column1 = Piwi::CreateWidget('Column', _t('URLMAPPER_ERRORMAPS_URL'), null, false);
        $column1->SetStyle('width:200px;');
        $datagrid->AddColumn($column1);

        $column2 = Piwi::CreateWidget('Column', _t('URLMAPPER_ERRORMAPS_CODE'), null, false);
        $column2->SetStyle('width:100px;');
        $datagrid->AddColumn($column2);

        $column3 = Piwi::CreateWidget('Column', _t('URLMAPPER_ERRORMAPS_NEW_URL'), null, false);
        $column3->SetStyle('width:200px;');
        $datagrid->AddColumn($column3);

        $column4 = Piwi::CreateWidget('Column', _t('URLMAPPER_ERRORMAPS_NEW_CODE'), null, false);
        $column4->SetStyle('width:100px;');
        $datagrid->AddColumn($column4);

        $column5 = Piwi::CreateWidget('Column', _t('URLMAPPER_ERRORMAPS_HITS'), null, false);
        $column5->SetStyle('width:100px;');
        $datagrid->AddColumn($column5);


        $colActions = Piwi::CreateWidget('Column', _t('GLOBAL_ACTIONS'), null, false);
        $colActions->SetStyle('width: 60px; white-space:nowrap;');
        $datagrid->addColumn($colActions);

        $datagrid->SetStyle('margin-top: 0px; width: 100%;');
        return $datagrid->Get();
    }

    /**
     * Prepares list of error maps for datagrid
     *
     * @access  public
     * @param   int     $limit
     * @param   int     $offset
     * @return  array   Grid data
     */
    function GetErrorMaps($limit, $offset)
    {
        require_once JAWS_PATH . 'include/Jaws/User.php';
        $model = $GLOBALS['app']->LoadGadget('UrlMapper', 'AdminModel', 'ErrorMaps');
        $errorMaps = $model->GetErrorMaps($limit, $offset);
        if (Jaws_Error::IsError($errorMaps)) {
            return array();
        }

        $retData = array();
        foreach ($errorMaps as $errorMap) {
            $usrData = array();
            $usrData['url'] = $errorMap['url'];
            $usrData['code'] = $errorMap['code'];
            $usrData['new_url'] = $errorMap['new_url'];
            $usrData['new_code'] = $errorMap['new_code'];
            $usrData['hits'] = $errorMap['hits'];

            $actions = '';
            if ($this->gadget->GetPermission('ManageErrorMaps')) {
                $link =& Piwi::CreateWidget('Link',
                    _t('GLOBAL_EDIT'),
                    "javascript: editErrorMap(this, '".$errorMap['id']."');",
                    STOCK_EDIT);
                $actions.= $link->Get().'&nbsp;';
                $link =& Piwi::CreateWidget('Link',
                    _t('GLOBAL_DELETE'),
                    "javascript: deleteErrorMap(this, '".$errorMap['id']."');",
                    STOCK_DELETE);
                $actions.= $link->Get().'&nbsp;';
            }


            $usrData['actions'] = $actions;
            $retData[] = $usrData;
        }

        return $retData;
    }

    /**
     * Builds error maps UI
     *
     * @access  public
     * @return  string  XHTML template content
     */
    function ErrorMaps()
    {
        $this->AjaxMe('script.js');
        $tpl = $this->gadget->loadTemplate('ErrorMaps.html');
        $tpl->SetBlock('ErrorMaps');

        // Menubar
        $tpl->SetVariable('menubar', $this->MenuBar('ErrorMaps'));

        $tpl->SetVariable('lbl_maps',    _t('URLMAPPER_MAPS'));
        $tpl->SetVariable('datagrid', $this->ErrorMapsDatagrid());

        // url
        $code =& Piwi::CreateWidget('Entry', 'url', '');
        $code->SetID('url');
        $code->SetStyle('direction: ltr; width: 250px;');
        $tpl->SetVariable('lbl_url', _t('URLMAPPER_ERRORMAPS_URL'));
        $tpl->SetVariable('url', $code->Get());

        // Combo for code
        $codeCombo =& Piwi::CreateWidget('Combo', 'code');
        $codeCombo->SetID('code');
        $codeCombo->SetStyle('width: 200px;');
        $codeCombo->AddOption(_t('GLOBAL_HTTP_ERROR_TITLE_404'), 404);
        $tpl->SetVariable('lbl_code', _t('URLMAPPER_ERRORMAPS_CODE'));
        $tpl->SetVariable('code', $codeCombo->Get());

        // new url
        $newUrl =& Piwi::CreateWidget('Entry', 'new_url', '');
        $newUrl->SetID('new_url');
        $newUrl->SetStyle('direction: ltr; width: 250px;');
        $tpl->SetVariable('lbl_new_url', _t('URLMAPPER_ERRORMAPS_NEW_URL'));
        $tpl->SetVariable('new_url', $newUrl->Get());

        // Combo for new code
        $codeCombo =& Piwi::CreateWidget('Combo', 'new_code');
        $codeCombo->SetID('new_code');
        $codeCombo->SetStyle('width: 200px;');
        $codeCombo->AddOption(_t('GLOBAL_HTTP_ERROR_TITLE_301'), 301);
        $codeCombo->AddOption(_t('GLOBAL_HTTP_ERROR_TITLE_302'), 302);
        $codeCombo->AddOption(_t('GLOBAL_HTTP_ERROR_TITLE_404'), 404);
        $codeCombo->AddOption(_t('GLOBAL_HTTP_ERROR_TITLE_410'), 410);
        $codeCombo->SetDefault(404);
        $codeCombo->AddEvent(ON_CHANGE, "javascript: changeCode();");
        $tpl->SetVariable('lbl_new_code', _t('URLMAPPER_ERRORMAPS_NEW_CODE'));
        $tpl->SetVariable('new_code', $codeCombo->Get());

        $btnCancel =& Piwi::CreateWidget('Button', 'btn_cancel', _t('GLOBAL_CANCEL'), STOCK_CANCEL);
        $btnCancel->SetID('btn_cancel');
        $btnCancel->SetStyle('visibility: hidden;');
        $btnCancel->AddEvent(ON_CLICK, "javascript: stopErrorMapAction();");
        $tpl->SetVariable('btn_cancel', $btnCancel->Get());

        $btnSave =& Piwi::CreateWidget('Button', 'btn_save', _t('GLOBAL_SAVE'), STOCK_SAVE);
        $btnSave->AddEvent(ON_CLICK, "javascript: saveErrorMap();");
        $tpl->SetVariable('btn_save', $btnSave->Get());

        $tpl->SetVariable('addErrorMap_title',     _t('URLMAPPER_ERRORMAPS_ADD_TITLE'));
        $tpl->SetVariable('editErrorMap_title',    _t('URLMAPPER_ERRORMAPS_EDIT_TITLE'));
        $tpl->SetVariable('confirmErrorMapDelete', _t('URLMAPPER_ERRORMAPS_CONFIRM_DELETE'));
        $tpl->SetVariable('incompleteFieldsMsg',   _t('URLMAPPER_ERRORMAPS_INCOMPLETE_FIELDS'));

        $tpl->ParseBlock('ErrorMaps');
        return $tpl->Get();
    }
}