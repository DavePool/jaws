<?php
/**
 * Tags Gadget
 *
 * @category   Gadget
 * @package    Tags
 * @author     Mojtaba Ebrahimi <ebrahimi@zehneziba.ir>
 * @copyright  2012-2013 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class Tags_Actions_ManageTags extends Tags_HTML
{

    /**
     * Manage User's Tags
     *
     * @access  public
     * @return  string  XHTML template content
     */
    function ManageTags()
    {
        if (!$GLOBALS['app']->Session->Logged()) {
            require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
            return Jaws_HTTPError::Get(403);
        }

        $this->AjaxMe();
        $user = $GLOBALS['app']->Session->GetAttribute('user');
        $post = jaws()->request->fetch(array('gadgets_filter', 'term'));
        $filters = array();
        $selected_gadget = "";
        if(!empty($post['gadgets_filter'])) {
            $filters['gadget'] = $post['gadgets_filter'];
            $selected_gadget = $post['gadgets_filter'];
        }

        if(!empty($post['term'])) {
            $filters['name'] = $post['term'];
        }

        $model = $GLOBALS['app']->LoadGadget('Tags', 'AdminModel', 'Tags');
        $tags = $model->GetTags($filters, null, 0, 0, false);

        $tpl = $this->gadget->loadTemplate('ManageTags.html');
        $tpl->SetBlock('tags');
        if ($response = $GLOBALS['app']->Session->PopResponse('Tags.ManageTags')) {
            $tpl->SetBlock('tags/response');
            $tpl->SetVariable('type', $response['type']);
            $tpl->SetVariable('text', $response['text']);
            $tpl->ParseBlock('tags/response');
        }

        // Menubar
        $tpl->SetVariable('menubar', $this->MenuBar('ManageTags', array('ManageTags')));

        $tpl->SetVariable('txt_term', $post['term']);
        $tpl->SetVariable('lbl_gadgets', _t('GLOBAL_GADGETS'));
        $tpl->SetVariable('lbl_all', _t('GLOBAL_ALL'));
        $tpl->SetVariable('icon_filter', STOCK_SEARCH);
        $tpl->SetVariable('icon_ok', STOCK_OK);
        $tpl->SetVariable('lbl_tag_title', _t('TAGS_TAG_TITLE'));
        $tpl->SetVariable('lbl_tag_usage_count', _t('TAGS_TAG_USAGE_COUNT'));
        $tpl->SetVariable('lbl_actions', _t('GLOBAL_ACTIONS'));
        $tpl->SetVariable('lbl_no_action', _t('GLOBAL_NO_ACTION'));
        $tpl->SetVariable('lbl_delete', _t('GLOBAL_DELETE'));
        $tpl->SetVariable('lbl_merge', _t('TAGS_MERGE'));
        $tpl->SetVariable('selectMoreThanOneTags',  _t('TAGS_SELECT_MORE_THAN_ONE_TAG_FOR_MERGE'));
        $tpl->SetVariable('enterNewTagName',  _t('TAGS_ENTER_NEW_TAG_NAME'));

        //load other gadget translations
        $site_language = $this->gadget->registry->fetch('site_language', 'Settings');

        $tpl->SetBlock('tags/gadgets_filter');
        //Gadgets filter
        $model = $GLOBALS['app']->LoadGadget('Tags', 'Model', 'Tags');
        $gadgets = $model->GetTagRelativeGadgets();
        $tagGadgets = array();
        $tagGadgets[''] = _t('GLOBAL_ALL');
        foreach ($gadgets as $gadget) {
            $tpl->SetBlock('tags/gadget');
            $GLOBALS['app']->Translate->LoadTranslation($gadget, JAWS_COMPONENT_GADGET, $site_language);
            $tpl->SetVariable('selected', '');
            if ($gadget == $selected_gadget) {
                $tpl->SetVariable('selected', 'selected="selected"');
            }
            $tpl->SetVariable('name', $gadget);
            $tpl->SetVariable('title', _t(strtoupper($gadget) . '_NAME'));
            $tpl->ParseBlock('tags/gadget');
        }

        foreach($tags as $tag) {
            $tpl->SetBlock('tags/tag');
            $tpl->SetVariable('id', $tag['id']);
            $tpl->SetVariable('title', $tag['title']);
            $tpl->SetVariable('usage_count', $tag['usage_count']);
//            $tpl->SetVariable('tag_url', $this->gadget->urlMap('ViewTag', array('tag'=>$tag['name'], 'user'=>$user)));
            $tpl->SetVariable('tag_url', $this->gadget->urlMap('EditTagUI', array('tag'=>$tag['id'])));
            $tpl->ParseBlock('tags/tag');
        }

        $tpl->ParseBlock('tags');
        return $tpl->Get();
    }

    /**
     * Edit Tag UI
     *
     * @access  public
     * @return  string  XHTML template of a form
     */
    function EditTagUI()
    {
        if (!$GLOBALS['app']->Session->Logged()) {
            require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
            return Jaws_HTTPError::Get(403);
        }

        $this->AjaxMe('index.js');

        $tag_id = jaws()->request->fetch('tag', 'get');
        $user = $GLOBALS['app']->Session->GetAttribute('user');
        $model = $GLOBALS['app']->LoadGadget('Tags', 'AdminModel', 'Tags');
        $tag = $model->GetTag($tag_id);
        if ($tag['user'] != $user) {
            require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
            return Jaws_HTTPError::Get(403);
        }

        // Load the template
        $tpl = $this->gadget->loadTemplate('ManageTags.html');
        $tpl->SetBlock('edit_tag');

        $tpl->SetVariable('base_script', BASE_SCRIPT);
        $tpl->SetVariable('tid', $tag_id);
        $tpl->SetVariable('name', $tag['name']);
        $tpl->SetVariable('tag_title', $tag['title']);
        $tpl->SetVariable('description', $tag['description']);

        $tpl->SetVariable('title', _t('TAGS_EDIT_TAG'));
        $tpl->SetVariable('menubar', $this->MenuBar('ViewTags',
                                     array('ManageTags', 'ViewTag'),
                                     array('tag' => $tag['name'], 'user' => $user)));

        $tpl->SetVariable('lbl_name', _t('GLOBAL_NAME'));
        $tpl->SetVariable('lbl_title', _t('GLOBAL_TITLE'));
        $tpl->SetVariable('lbl_description', _t('GLOBAL_DESCRIPTION'));
        $tpl->SetVariable('save', _t('GLOBAL_SAVE'));

        $tpl->ParseBlock('edit_tag');
        return $tpl->Get();
    }

    /**
     * Update a Tag
     *
     * @access  public
     * @return  void
     */
    function UpdateTag()
    {
        if (!$GLOBALS['app']->Session->Logged()) {
            require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
            return Jaws_HTTPError::Get(403);
        }

        $post = jaws()->request->fetch(array('tid', 'name', 'title', 'description'), 'post');
        $id = $post['tid'];
        unset($post['tid']);
        $model = $GLOBALS['app']->LoadGadget('Tags', 'AdminModel', 'Tags');
        $tag = $model->GetTag($id);
        if ($tag['user'] != $GLOBALS['app']->Session->GetAttribute('user')) {
            require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
            return Jaws_HTTPError::Get(403);
        }
        $res = $model->UpdateTag($id, $post);
        if (Jaws_Error::IsError($res)) {
            $GLOBALS['app']->Session->PushResponse(
                _t('TAGS_ERROR_CANT_UPDATE_TAG'),
                'Tags.ManageTags',
                RESPONSE_ERROR
            );
        } else {
            $GLOBALS['app']->Session->PushResponse(
                _t('TAGS_TAG_UPDATED'),
                'Tags.ManageTags',
                RESPONSE_NOTICE
            );
        }

        Jaws_Header::Location($this->gadget->urlMap('ManageTags'));
    }

    /**
     * Delete Tags
     *
     * @access  public
     * @return  void
     */
    function DeleteTags()
    {
        if (!$GLOBALS['app']->Session->Logged()) {
            require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
            return Jaws_HTTPError::Get(403);
        }

        $ids = jaws()->request->fetch('tags_checkbox:array', 'post');
        $model = $GLOBALS['app']->LoadGadget('Tags', 'AdminModel', 'Tags');
        $res = $model->DeleteTags($ids);
        if (Jaws_Error::IsError($res)) {
            $GLOBALS['app']->Session->PushResponse(
                _t('TAGS_ERROR_CANT_DELETE_TAG'),
                'Tags.ManageTags',
                RESPONSE_ERROR
            );
        } else {
            $GLOBALS['app']->Session->PushResponse(
                _t('TAGS_TAG_DELETED'),
                'Tags.ManageTags',
                RESPONSE_NOTICE
            );
        }

        Jaws_Header::Location($this->gadget->urlMap('ManageTags'));
    }

    /**
     * Merge Tags
     *
     * @access  public
     * @return  void
     */
    function MergeTags()
    {
        if (!$GLOBALS['app']->Session->Logged()) {
            require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
            return Jaws_HTTPError::Get(403);
        }

        $post = jaws()->request->fetch(array('tags_checkbox:array', 'new_tag_name'), 'post');
        $ids = $post['tags_checkbox'];
        if (count($ids) < 3) {
            $GLOBALS['app']->Session->PushResponse(
                _t('TAGS_SELECT_MORE_THAN_ONE_TAG_FOR_MERGE'),
                'Tags.ManageTags',
                RESPONSE_ERROR
            );
        }
        if (empty($post['new_tag_name'])) {
            $GLOBALS['app']->Session->PushResponse(
                _t('TAGS_ERROR_ENTER_NEW_TAG_NAME'),
                'Tags.ManageTags',
                RESPONSE_ERROR
            );
        }
        $model = $GLOBALS['app']->LoadGadget('Tags', 'AdminModel', 'Tags');
        $res = $model->MergeTags($ids, $post['new_tag_name'], false);
        if (Jaws_Error::IsError($res)) {
            $GLOBALS['app']->Session->PushResponse(
                _t('TAGS_ERROR_CANT_DELETE_TAG'),
                'Tags.ManageTags',
                RESPONSE_ERROR
            );
        } else {
            $GLOBALS['app']->Session->PushResponse(
                _t('TAGS_TAGS_MERGED'),
                'Tags.ManageTags',
                RESPONSE_NOTICE
            );
        }

        Jaws_Header::Location($this->gadget->urlMap('ManageTags'));
    }

}