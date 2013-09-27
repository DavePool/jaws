<?php
/**
 * Tags Gadget Admin
 *
 * @category   GadgetAdmin
 * @package    Tags
 * @author     Mojtaba Ebrahimi <ebrahimi@zehneziba.ir>
 * @copyright  2013 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class Tags_Actions_Admin_Tags extends Tags_AdminHTML
{
    /**
     * Show tags list
     *
     * @access  public
     * @param   string $gadget     Gadget name
     * @param   string $url        Gadget manage tags URL
     * @return  string XHTML template content
     */
    function Tags($gadget='', $url='')
    {
        $this->AjaxMe('script.js');

        $tpl = $this->gadget->loadTemplate('Tags.html');
        $tpl->SetBlock('tags');

        //Menu bar
        if (!empty($url)) {
            $tpl->SetVariable('menubar', $url);
        } else {
            $tpl->SetVariable('menubar', $this->MenuBar('Tags'));
        }

        //load other gadget translations
        $site_language = $this->gadget->registry->fetch('site_language', 'Settings');
        $GLOBALS['app']->Translate->LoadTranslation('Blog', JAWS_COMPONENT_GADGET, $site_language);
        $GLOBALS['app']->Translate->LoadTranslation('LinkDump', JAWS_COMPONENT_GADGET, $site_language);

        if (empty($gadget)) {
            $tpl->SetBlock('tags/gadgets_filter');
            //Gadgets filter
            $gadgetsCombo =& Piwi::CreateWidget('Combo', 'gadgets_filter');
            $gadgetsCombo->SetID('gadgets_filter');
            $gadgetsCombo->setStyle('width: 100px;');
            $gadgetsCombo->AddEvent(ON_CHANGE, "changeGadget()");
            $gadgetsCombo->AddOption('', '');
            // TODO: Get List Of Gadget Which Use Tags
            $gadgetsCombo->AddOption(_t('BLOG_NAME'), 'Blog');
            $gadgetsCombo->AddOption(_t('LINKDUMP_NAME'), 'LinkDump');
            $gadgetsCombo->SetDefault('');
            $tpl->SetVariable('lbl_gadgets_filter', _t('TAGS_GADGETS'));
            $tpl->SetVariable('gadgets_filter', $gadgetsCombo->Get());
            $tpl->ParseBlock('tags/gadgets_filter');
        } else {
            $gadgets_filter =& Piwi::CreateWidget('HiddenEntry', 'gadgets_filter', $gadget);
            $gadgets_filter->SetID('gadgets_filter');
            $tpl->SetVariable('gadgets_filter', $gadgets_filter->Get());
        }

        //Actions filter
        $actions =& Piwi::CreateWidget('Combo', 'actions');
        $actions->AddOption('&nbsp;',0);
        $actions->SetDefault(0);
        $actions->AddEvent(ON_CHANGE, 'searchTags();');
        $tpl->SetVariable('lbl_actions', _t('TAGS_ACTIONS'));
        $tpl->SetVariable('actions', $actions->Get());

        // filter
        $filterData = jaws()->request->fetch('filter', 'get');
        $filterEntry =& Piwi::CreateWidget('Entry', 'filter', is_null($filterData)? '' : $filterData);
        $filterEntry->setSize(20);
        $tpl->SetVariable('filter', $filterEntry->Get());
        $filterButton =& Piwi::CreateWidget('Button', 'filter_button',
            _t('GLOBAL_SEARCH'), STOCK_SEARCH);
        $filterButton->AddEvent(ON_CLICK, 'javascript: searchTags();');

        $tpl->SetVariable('filter_button', $filterButton->Get());

        //DataGrid
        $tpl->SetVariable('grid', $this->GetDataGrid());

        //TagUI
        $tpl->SetVariable('tag_ui', $this->TagUI());

        if ($this->gadget->GetPermission('ManageTags')) {
            $btnCancel =& Piwi::CreateWidget('Button', 'btn_cancel', _t('GLOBAL_CANCEL'), STOCK_CANCEL);
            $btnCancel->AddEvent(ON_CLICK, 'stopTagAction();');
            $btnCancel->SetStyle('display: none;');
            $tpl->SetVariable('btn_cancel', $btnCancel->Get());

            $btnSave =& Piwi::CreateWidget('Button', 'btn_save', _t('GLOBAL_SAVE'), STOCK_SAVE);
            $btnSave->AddEvent(ON_CLICK, "updateTag();");
            $btnSave->SetStyle('display: none;');
            $tpl->SetVariable('btn_save', $btnSave->Get());
        }

        $tpl->SetVariable('incompleteTagFields', _t('TAGS_INCOMPLETE_FIELDS'));
        $tpl->SetVariable('confirmTagDelete',    _t('TAGS_CONFIRM_DELETE'));
        $tpl->SetVariable('legend_title',        _t('TAGS_EDIT_TAG'));
        $tpl->SetVariable('tagDetail_title',     _t('TAGS_EDIT_TAG'));

        $tpl->ParseBlock('tags');
        return $tpl->Get();
    }

    /**
     * Show a form to show/edit a tag
     *
     * @access  public
     * @return  string  XHTML template content
     */
    function TagUI()
    {
        $tpl = $this->gadget->loadTemplate('Tags.html');
        $tpl->SetBlock('tagUI');

        //name
        $nameEntry =& Piwi::CreateWidget('Entry', 'name', '');
        $nameEntry->setStyle('width: 160px;');
        $tpl->SetVariable('lbl_name', _t('GLOBAL_NAME'));
        $tpl->SetVariable('name', $nameEntry->Get());

        $tpl->ParseBlock('tagUI');
        return $tpl->Get();
    }

    /**
     * Build a new array with filtered data
     *
     * @access  public
     * @param   string  $gadget     Gadget name
     * @param   string  $editAction Edit action
     * @param   string  $filters       Search term
     * @param   mixed   $offset     Data offset (numeric/boolean)
     * @return  array   Filtered Comments
     */
    function GetDataAsArray($gadget, $editAction, $filters, $offset)
    {
        $cModel = $GLOBALS['app']->LoadGadget('Tags', 'AdminModel', 'Tags');
        $tags = $cModel->GetTags($filters, 15, $offset);
        if (Jaws_Error::IsError($tags)) {
            return array();
        }

        $data = array();
        foreach ($tags as $row) {
            $newRow = array();
            $newRow['__KEY__']      = $row['id'];

            $newRow['name']         = $row['name'];
            $newRow['usage_count']  = $row['usage_count'];

            if (!empty($editAction)) {
                $edit_url = str_replace('{id}', $row['id'], $editAction);
            }

            $link =& Piwi::CreateWidget('Link', _t('GLOBAL_EDIT'), $edit_url, STOCK_EDIT);
            $actions= $link->Get().'&nbsp;';

            $link =& Piwi::CreateWidget('Link', _t('GLOBAL_DELETE'),
                "javascript: deleteTag('".$row['id']."');",
                STOCK_DELETE);
            $actions.= $link->Get().'&nbsp;';
            $newRow['actions'] = $actions;

            $data[] = $newRow;
        }
        return $data;
    }

    /**
     * Builds and returns the GetDataGrid UI
     *
     * @access  public
     * @return  string  UI XHTML
     */
    function GetDataGrid()
    {
        $tModel = $GLOBALS['app']->LoadGadget('Tags', 'AdminModel');
        $total = $tModel->TotalOfData('banners');

        $gridBox =& Piwi::CreateWidget('VBox');
        $gridBox->SetID('tags_box');
        $gridBox->SetStyle('width: 100%;');

        //Datagrid
        $grid =& Piwi::CreateWidget('DataGrid', array());
        $grid->SetID('tags_datagrid');
        $grid->SetStyle('width: 100%;');
        $grid->TotalRows($total);
        $grid->useMultipleSelection();
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('TAGS_TAG_NAME')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('TAGS_TAG_USAGE_COUNT')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_ACTIONS')));

        //Tools
        $gridForm =& Piwi::CreateWidget('Form');
        $gridForm->SetID('tags_form');
        $gridForm->SetStyle('float: right');

        $gridFormBox =& Piwi::CreateWidget('HBox');

        $actions =& Piwi::CreateWidget('Combo', 'tags_actions');
        $actions->SetID('tags_actions_combo');
        $actions->SetTitle(_t('GLOBAL_ACTIONS'));
        $actions->AddOption('&nbsp;', '');
        $actions->AddOption(_t('GLOBAL_DELETE'), 'delete');
        $actions->AddOption(_t('TAGS_MERGE'), 'merge');

        $execute =& Piwi::CreateWidget('Button', 'executeTagAction', '',
            STOCK_YES);
        $execute->AddEvent(ON_CLICK, "javascript: tagDGAction(document.getElementById('tags_actions_combo'));");

        $gridFormBox->Add($actions);
        $gridFormBox->Add($execute);
        $gridForm->Add($gridFormBox);

        //Pack everything
        $gridBox->Add($grid);
        $gridBox->Add($gridForm);

        return $gridBox->Get();
    }

}