<?php
require_once JAWS_PATH. 'gadgets/Tags/AdminAction.php';
/**
 * Tags Gadget Admin
 *
 * @category   GadgetAdmin
 * @package    Tags
 * @author     Mojtaba Ebrahimi <ebrahimi@zehneziba.ir>
 * @copyright  2013 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class Tags_Actions_Admin_Tags extends Tags_AdminAction
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

        $tpl = $this->gadget->loadAdminTemplate('Tags.html');
        $tpl->SetBlock('tags');

        //Menu bar
        if (!empty($url)) {
            $tpl->SetVariable('menubar', $url);
        } else {
            $tpl->SetVariable('menubar', $this->MenuBar('Tags'));
        }

        //load other gadget translations
        $site_language = $this->gadget->registry->fetch('site_language', 'Settings');

        if (empty($gadget)) {
            $tpl->SetBlock('tags/gadgets_filter');
            //Gadgets filter
            $gadgetsCombo =& Piwi::CreateWidget('Combo', 'gadgets_filter');
            $gadgetsCombo->SetID('gadgets_filter');
            $gadgetsCombo->setStyle('width: 150px;');
            $gadgetsCombo->AddEvent(ON_CHANGE, "searchTags()");
            $gadgetsCombo->AddOption('', '');

            $model = $this->gadget->loadModel('Tags');
            $gadgets = $model->GetTagRelativeGadgets();
            $tagGadgets = array();
            $tagGadgets[''] = _t('GLOBAL_ALL');
            foreach($gadgets as $gadget) {
                $GLOBALS['app']->Translate->LoadTranslation($gadget, JAWS_COMPONENT_GADGET, $site_language);
                $gadgetsCombo->AddOption(_t(strtoupper($gadget) . '_NAME'), $gadget);
            }

            $gadgetsCombo->SetDefault('');
            $tpl->SetVariable('lbl_gadgets_filter', _t('TAGS_GADGET'));
            $tpl->SetVariable('gadgets_filter', $gadgetsCombo->Get());
            $tpl->ParseBlock('tags/gadgets_filter');
        } else {
            $gadgets_filter =& Piwi::CreateWidget('HiddenEntry', 'gadgets_filter', $gadget);
            $gadgets_filter->SetID('gadgets_filter');
            $tpl->SetVariable('gadgets_filter', $gadgets_filter->Get());
        }

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
            $tpl->SetVariable('btn_save', $btnSave->Get());
        }

        $tpl->SetVariable('incompleteTagFields',    _t('TAGS_INCOMPLETE_FIELDS'));
        $tpl->SetVariable('confirmTagDelete',       _t('TAGS_CONFIRM_DELETE'));
        $tpl->SetVariable('selectMoreThanOneTags',  _t('TAGS_SELECT_MORE_THAN_ONE_TAG_FOR_MERGE'));
        $tpl->SetVariable('legend_title',           _t('TAGS_ADD_TAG'));
        $tpl->SetVariable('editTagTitle',           _t('TAGS_EDIT_TAG'));
        $tpl->SetVariable('tagDetail_title',        _t('TAGS_EDIT_TAG'));

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
        $tpl = $this->gadget->loadAdminTemplate('Tags.html');
        $tpl->SetBlock('tagUI');

        // name
        $nameEntry =& Piwi::CreateWidget('Entry', 'name', '');
        $tpl->SetVariable('lbl_name', _t('GLOBAL_NAME'));
        $tpl->SetVariable('name', $nameEntry->Get());

        // title
        $titleEntry =& Piwi::CreateWidget('Entry', 'title', '');
        $tpl->SetVariable('lbl_title', _t('GLOBAL_TITLE'));
        $tpl->SetVariable('title', $titleEntry->Get());

        // description
        $entry =& Piwi::CreateWidget('TextArea', 'description', '');
        $entry->SetId('description');
        $entry->SetRows(4);
        $entry->SetColumns(30);
        $entry->SetStyle('width: 99%; direction: ltr; white-space: nowrap;');
        $tpl->SetVariable('lbl_description', _t('GLOBAL_DESCRIPTION'));
        $tpl->SetVariable('description', $entry->Get());


        // meta_keywords
        $entry =& Piwi::CreateWidget('Entry', 'meta_keywords', '');
        $tpl->SetVariable('lbl_meta_keywords', _t('GLOBAL_META_KEYWORDS'));
        $tpl->SetVariable('meta_keywords', $entry->Get());

        // meta_description
        $entry =& Piwi::CreateWidget('Entry', 'meta_description', '');
        $tpl->SetVariable('lbl_meta_description', _t('GLOBAL_META_DESCRIPTION'));
        $tpl->SetVariable('meta_description', $entry->Get());

        $tpl->ParseBlock('tagUI');
        return $tpl->Get();
    }

    /**
     * Build a new array with filtered data
     *
     * @access  public
     * @param   string  $editAction Edit action
     * @param   array   $filters    Search terms
     * @param   mixed   $offset     Data offset (numeric/boolean)
     * @return  array   Filtered Comments
     */
    function GetDataAsArray($editAction, $filters, $offset)
    {
        $cModel = $this->gadget->loadAdminModel('Tags');
        $tags = $cModel->GetTags($filters, 10, $offset, 0, true);
        if (Jaws_Error::IsError($tags)) {
            return array();
        }

        $data = array();
        foreach ($tags as $row) {
            $newRow = array();
            $newRow['__KEY__']      = $row['id'];

            $newRow['name']         = $row['name'];
            $newRow['title']         = $row['title'];
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
        $tModel = $this->gadget->loadModel();
        $total = $tModel->TotalOfData('tags');

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
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('TAGS_TAG_TITLE')));
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
        $execute->AddEvent(ON_CLICK, "javascript: tagsDGAction(document.getElementById('tags_actions_combo'));");

        $gridFormBox->Add($actions);
        $gridFormBox->Add($execute);
        $gridForm->Add($gridFormBox);

        //Pack everything
        $gridBox->Add($grid);
        $gridBox->Add($gridForm);

        return $gridBox->Get();
    }

}