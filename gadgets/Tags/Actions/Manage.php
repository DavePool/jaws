<?php
/**
 * Tags Gadget
 *
 * @category   Gadget
 * @package    Tags
 */
class Tags_Actions_Manage extends Jaws_Gadget_Action
{

    /**
     * Manage User's Tags
     *
     * @access  public
     * @return  string  XHTML template content
     */
    function ManageTags()
    {
        if (!$this->app->session->user->logged) {
            return Jaws_HTTPError::Get(403);
        }

        $this->AjaxMe();
        $post = $this->gadget->request->fetch(array('gadgets_filter', 'term', 'page', 'page_item'));
        $page = $post['page'];

        $filters = array();
        $selected_gadget = "";
        if(!empty($post['gadgets_filter'])) {
            $filters['gadget'] = $post['gadgets_filter'];
            $selected_gadget = $post['gadgets_filter'];
        }

        if(!empty($post['term'])) {
            $filters['name'] = $post['term'];
        }

        $tpl = $this->gadget->template->load('ManageTags.html');
        $tpl->SetBlock('tags');
        if ($response = $this->gadget->session->pop('ManageTags')) {
            $tpl->SetVariable('response_type', $response['type']);
            $tpl->SetVariable('response_text', $response['text']);
        }

        $tpl->SetVariable('title', _t('TAGS_MANAGE_TAGS'));
        if ($this->app->session->user->logged) {
            // Menu navigation
            $this->gadget->action->load('MenuNavigation')->navigation($tpl);
        }

        $page = empty($page) ? 1 : (int)$page;
        if (empty($post['page_item'])) {
            $limit = 10;
        } else {
            $limit = $post['page_item'];
        }
        $tpl->SetVariable('opt_page_item_' . $limit, 'selected="selected"');

        $user = (int)$this->app->session->user->id;
        $model = $this->gadget->model->loadAdmin('Tags');
        $tags = $model->GetTags($filters, $limit, ($page - 1) * $limit, $user);
        $tagsTotal = $model->GetTagsCount($filters, $user);

        $tpl->SetVariable('txt_term', $post['term']);
        $tpl->SetVariable('lbl_gadgets', _t('GLOBAL_GADGETS'));
        $tpl->SetVariable('lbl_all', _t('GLOBAL_ALL'));
        $tpl->SetVariable('icon_filter', STOCK_SEARCH);
        $tpl->SetVariable('icon_ok', STOCK_OK);
        $tpl->SetVariable('lbl_tag_name', _t('TAGS_TAG_NAME'));
        $tpl->SetVariable('lbl_tag_title', _t('TAGS_TAG_TITLE'));
        $tpl->SetVariable('lbl_tag_usage_count', _t('TAGS_TAG_USAGE_COUNT'));
        $tpl->SetVariable('filter', _t('GLOBAL_SEARCH'));
        $tpl->SetVariable('lbl_page_item', _t('TAGS_ITEMS_PER_PAGE'));
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
        $model = $this->gadget->model->load('Tags');
        $gadgets = $model->GetTagableGadgets();
        $tagGadgets = array();
        $tagGadgets[''] = _t('GLOBAL_ALL');
        $objTranslate = Jaws_Translate::getInstance();
        foreach ($gadgets as $gadget => $title) {
            $tpl->SetBlock('tags/gadget');
            $tpl->SetVariable('selected', '');
            if ($gadget == $selected_gadget) {
                $tpl->SetVariable('selected', 'selected="selected"');
            }
            $tpl->SetVariable('name', $gadget);
            $tpl->SetVariable('title', $title);
            $tpl->ParseBlock('tags/gadget');
        }

        foreach($tags as $tag) {
            $tpl->SetBlock('tags/tag');
            $tpl->SetVariable('id', $tag['id']);
            $tpl->SetVariable('name', $tag['name']);
            $tpl->SetVariable('title', $tag['title']);
            $tpl->SetVariable('usage_count', $tag['usage_count']);
            $tpl->SetVariable('tag_url', $this->gadget->urlMap('EditTagUI', array('tag'=>$tag['id'])));
            $tpl->ParseBlock('tags/tag');
        }

        $params = array();
        if(!empty($post['gadgets_filter'])) {
            $params['gadgets_filter'] = $post['gadgets_filter'];
        }
        if(!empty($post['term'])) {
            $params['term'] = $post['term'];
        }
        // pagination
        $this->gadget->action->load('PageNavigation')->pagination(
            $tpl,
            $page,
            $limit,
            $tagsTotal,
            'ManageTags',
            $params,
            _t('TAGS_TAG_COUNT', $tagsTotal)
        );

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
        if (!$this->app->session->user->logged) {
            return Jaws_HTTPError::Get(403);
        }

        $this->AjaxMe('index.js');

        $tag_id = $this->gadget->request->fetch('tag', 'get');
        $user = (int)$this->app->session->user->id;
        $model = $this->gadget->model->loadAdmin('Tags');
        $tag = $model->GetTag($tag_id);
        if ($tag['user'] != $user) {
            return Jaws_HTTPError::Get(403);
        }

        // Load the template
        $tpl = $this->gadget->template->load('ManageTags.html');
        $tpl->SetBlock('edit_tag');

        $tpl->SetVariable('base_script', BASE_SCRIPT);
        $tpl->SetVariable('tid', $tag_id);
        $tpl->SetVariable('name', $tag['name']);
        $tpl->SetVariable('tag_title', $tag['title']);
        $tpl->SetVariable('description', $tag['description']);

        $tpl->SetVariable('title', _t('TAGS_EDIT_TAG'));
        // Menu navigation
        $this->gadget->action->load('MenuNavigation')->navigation($tpl);

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
        if (!$this->app->session->user->logged) {
            return Jaws_HTTPError::Get(403);
        }

        $post = $this->gadget->request->fetch(array('tid', 'name', 'title', 'description'), 'post');
        $id = $post['tid'];
        unset($post['tid']);
        $user = (int)$this->app->session->user->id;
        $model = $this->gadget->model->loadAdmin('Tags');
        $tag = $model->GetTag($id);
        if ($tag['user'] != $user) {
            return Jaws_HTTPError::Get(403);
        }
        $res = $model->UpdateTag($id, $post, $user);
        if (Jaws_Error::IsError($res)) {
            $this->gadget->session->push(
                _t('TAGS_ERROR_CANT_UPDATE_TAG'),
                RESPONSE_ERROR,
                'ManageTags'
            );
        } else {
            $this->gadget->session->push(
                _t('TAGS_TAG_UPDATED'),
                RESPONSE_NOTICE,
                'ManageTags'
            );
        }

        return Jaws_Header::Location($this->gadget->urlMap('ManageTags'));
    }

    /**
     * Delete Tags
     *
     * @access  public
     * @return  void
     */
    function DeleteTags()
    {
        if (!$this->app->session->user->logged) {
            return Jaws_HTTPError::Get(403);
        }

        $ids = $this->gadget->request->fetch('tags_checkbox:array', 'post');
        $user = (int)$this->app->session->user->id;
        $model = $this->gadget->model->loadAdmin('Tags');
        $res = $model->DeleteTags($ids, $user);
        if (Jaws_Error::IsError($res)) {
            $this->gadget->session->push(
                _t('TAGS_ERROR_CANT_DELETE_TAG'),
                RESPONSE_ERROR,
                'ManageTags'
            );
        } else {
            $this->gadget->session->push(
                _t('TAGS_TAG_DELETED'),
                RESPONSE_NOTICE,
                'ManageTags'
            );
        }

        return Jaws_Header::Location($this->gadget->urlMap('ManageTags'));
    }

    /**
     * Merge Tags
     *
     * @access  public
     * @return  void
     */
    function MergeTags()
    {
        if (!$this->app->session->user->logged) {
            return Jaws_HTTPError::Get(403);
        }

        $post = $this->gadget->request->fetch(array('tags_checkbox:array', 'new_tag_name'), 'post');
        $ids = $post['tags_checkbox'];
        if (count($ids) < 3) {
            $this->gadget->session->push(
                _t('TAGS_SELECT_MORE_THAN_ONE_TAG_FOR_MERGE'),
                RESPONSE_ERROR,
                'ManageTags'
            );
        }
        if (empty($post['new_tag_name'])) {
            $this->gadget->session->push(
                _t('TAGS_ERROR_ENTER_NEW_TAG_NAME'),
                RESPONSE_ERROR,
                'ManageTags'
            );
        }
        $user = (int)$this->app->session->user->id;
        $model = $this->gadget->model->loadAdmin('Tags');
        $res = $model->MergeTags($ids, $post['new_tag_name'], $user);
        if (Jaws_Error::IsError($res)) {
            $this->gadget->session->push(
                $res->getMessage(),
                RESPONSE_ERROR,
                'ManageTags'
            );
        } else {
            $this->gadget->session->push(
                _t('TAGS_TAGS_MERGED'),
                RESPONSE_NOTICE,
                'ManageTags'
            );
        }

        return Jaws_Header::Location($this->gadget->urlMap('ManageTags'));
    }

}