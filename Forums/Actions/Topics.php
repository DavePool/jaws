<?php
/**
 * Forums Gadget
 *
 * @category    Gadget
 * @package     Forums
 * @author      Ali Fazelzadeh <afz@php.net>
 * @author      Hamid Reza Aboutalebi <abt_am@yahoo.com>
 * @copyright   2012 Jaws Development Group
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class Forums_Actions_Topics extends ForumsHTML
{
    /**
     * Display forum's topics
     *
     * @access  public
     * @return  string  XHTML template content
     */
    function Topics()
    {
        $request =& Jaws_Request::getInstance();
        $rqst = $request->get(array('fid', 'page'), 'get');

        $fModel = $GLOBALS['app']->LoadGadget('Forums', 'Model', 'Forums');
        $forum  = $fModel->GetForum($rqst['fid']);
        if (Jaws_Error::IsError($forum) || empty($forum)) {
            return false;
        }

        $model = $GLOBALS['app']->LoadGadget('Forums', 'Model', 'Topics');
        $topics = $model->GetTopics($forum['id']);
        if (Jaws_Error::IsError($topics)) {
            return false;
        }

        $objDate = $GLOBALS['app']->loadDate();
        $tpl = new Jaws_Template('gadgets/Forums/templates/');
        $tpl->Load('Topics.html');
        $tpl->SetBlock('topics');

        $tpl->SetVariable('title', $forum['title']);
        $tpl->SetVariable('url', $this->GetURLFor('Topics', array('fid' => $forum['id'])));
        $tpl->SetVariable('lbl_topics', _t('FORUMS_TIPICS'));
        $tpl->SetVariable('lbl_replies', _t('FORUMS_REPLIES'));
        $tpl->SetVariable('lbl_views', _t('FORUMS_VIEWS'));
        $tpl->SetVariable('lbl_lastpost', _t('FORUMS_LASTPOST'));

        foreach ($topics as $topic) {
            $tpl->SetBlock('topics/topic');
            $tpl->SetVariable('icon', '');
            if ($topic['locked']) {
                $tpl->SetVariable('status', _t('FORUMS_LOCKED'));
            }
            $tpl->SetVariable('title', $topic['subject']);
            $tpl->SetVariable(
                'url',
                $this->GetURLFor('Posts', array('fid' => $forum['id'], 'tid' => $topic['id']))
            );
            $tpl->SetVariable('replies', $topic['replies']);
            $tpl->SetVariable('views', $topic['views']);

            // last post
            if (!empty($topic['last_post_id'])) {
                $tpl->SetBlock('topics/topic/lastpost');
                $tpl->SetVariable('postedby_lbl',_t('FORUMS_POSTEDBY'));

                $tpl->SetVariable('username', $topic['username']);
                $tpl->SetVariable('nickname', $topic['nickname']);
                $tpl->SetVariable('user_url',
                                  $GLOBALS['app']->Map->GetURLFor('Users', 'Profile', array('user' => $topic['username']))
                );
                $tpl->SetVariable('lastpost_lbl',_t('FORUMS_LASTPOSTED'));
                $tpl->SetVariable('lastpost_date', $objDate->Format($topic['last_post_time']));
                $tpl->SetVariable('lastpost_url',
                                  $this->GetURLFor('Topic', array('id' => $topic['id']))
                );
                $tpl->ParseBlock('topics/topic/lastpost');
            }

            $tpl->ParseBlock('topics/topic');
        }

        $tpl->SetBlock('topics/actions');
        $tpl->SetVariable('newtopic_lbl', _t('FORUMS_NEWTOPIC'));
        $tpl->SetVariable('newtopic_url', $this->GetURLFor('NewTopic', array('fid' => $forum['id'])));
        $tpl->ParseBlock('topics/actions');

        $tpl->ParseBlock('topics');
        return $tpl->Get();
    }

    /**
     * Show new topic form
     *
     * @access  public
     * @return  string  XHTML template content
     */
    function NewTopic()
    {
        return $this->EditTopic();
    }

    /**
     * Show edit topic form
     *
     * @access  public
     * @return  string  XHTML template content
     */
    function EditTopic()
    {
        $request =& Jaws_Request::getInstance();
        $rqst = $request->get(array('fid', 'tid'));
        if (empty($rqst['fid'])) {
            return false;
        }

        $fModel = $GLOBALS['app']->LoadGadget('Forums', 'Model', 'Forums');
        $forum  = $fModel->GetForum($rqst['fid']);
        if (Jaws_Error::IsError($forum) || empty($forum)) {
            return false;
        }

        if (!empty($rqst['tid'])) {
            $tModel = $GLOBALS['app']->LoadGadget('Forums', 'Model', 'Topics');
            $topic = $tModel->GetTopic($rqst['tid']);
            if (Jaws_Error::IsError($topic) || empty($topic)) {
                return false;
            }

            $title = _t('FORUMS_TOPIC_EDIT_TITLE');
            $btn_title = _t('FORUMS_TOPIC_EDIT_BUTTON');
        } else {
            $topic = array();
            $topic['id'] = 0;
            $topic['subject'] = '';
            $topic['message'] = '';
            $topic['last_update_reason'] = '';
            $title = _t('FORUMS_TOPIC_ADD_TITLE');
            $btn_title = _t('FORUMS_TOPIC_ADD_BUTTON');
        }

        $tpl = new Jaws_Template('gadgets/Forums/templates/');
        $tpl->Load('EditTopic.html');
        $tpl->SetBlock('topic');

        $tpl->SetVariable('forum_title', $forum['title']);
        $tpl->SetVariable('forum_url', $this->GetURLFor('Topics', array('fid' => $forum['id'])));
        $tpl->SetVariable('title', $title);
        $tpl->SetVariable('fid', $rqst['fid']);
        $tpl->SetVariable('tid', $topic['id']);

        if ($response = $GLOBALS['app']->Session->PopSimpleResponse('Forums')) {
            $tpl->SetBlock('topic/response');
            $tpl->SetVariable('msg', $response);
            $tpl->ParseBlock('topic/response');
        }

        // subject
        $tpl->SetBlock('topic/subject');
        $tpl->SetVariable('subject', $topic['subject']);
        $tpl->SetVariable('lbl_subject', _t('FORUMS_TOPIC_SUBJECT'));
        $tpl->ParseBlock('topic/subject');

        // message
        $tpl->SetVariable('message', $topic['message']);
        $tpl->SetVariable('lbl_message', _t('FORUMS_POST_MESSAGE'));

        // update reason
        if (!empty($topic['id'])) {
            $tpl->SetBlock('topic/update_reason');
            $tpl->SetVariable('lbl_update_reason', _t('FORUMS_POST_UPDATE_REASON'));
            $tpl->SetVariable('update_reason', $topic['last_update_reason']);
            $tpl->ParseBlock('topic/update_reason');
        }

        // button
        $tpl->SetVariable('btn_title', $btn_title);

        $tpl->ParseBlock('topic');
        return $tpl->Get();
    }

    /**
     * Add/Edit a topic
     *
     * @access  public
     */
    function UpdateTopic()
    {
        $request =& Jaws_Request::getInstance();
        $topic = $request->get(
            array('fid', 'tid', 'subject', 'message', 'update_reason', 'published'),
            'post'
        );

        $tModel = $GLOBALS['app']->LoadGadget('Forums', 'Model', 'Topics');
        if (empty($topic['tid'])) {
            $result = $tModel->InsertTopic(
                $GLOBALS['app']->Session->GetAttribute('user'),
                $topic['fid'],
                $topic['subject'],
                $topic['message'],
                $topic['published']
            );
        } else {
            $result = $tModel->GetTopic($topic['tid']);
            if (!Jaws_Error::IsError($result)) {
                $result = $tModel->UpdateTopic(
                    $topic['fid'],
                    $topic['tid'],
                    $result['first_post_id'],
                    $topic['subject'],
                    $topic['message'],
                    $topic['published'],
                    $topic['update_reason']
                );
            }
        }

        if (Jaws_Error::IsError($result)) {
            $GLOBALS['app']->Session->PushSimpleResponse($result->getMessage(),
                                                         'Topic');
        } else {
            $topic['tid'] = $result;
            $GLOBALS['app']->Session->PushSimpleResponse(_t('FORUMS_TOPIC_UPDATED'),
                                                         'Topic');
        }

        // Redirect
        Jaws_Header::Location(
            $this->GetURLFor('EditTopic', array('fid' => $topic['fid'], 'tid' => $topic['tid'])),
            true
        );
    }

    /**
     * Locked a topic
     *
     * @access  public
     */
    function LockTopic()
    {
        $request =& Jaws_Request::getInstance();
        $rqst = $request->get(array('tid'));

        $tModel = $GLOBALS['app']->LoadGadget('Forums', 'Model', 'Topics');
        $topic = $tModel->GetTopic(($rqst['tid']));
        if (Jaws_Error::IsError($topic) || empty($topic)) {
            $GLOBALS['app']->Session->PushSimpleResponse(_t('FORUMS_TOPIC_NOT_FOUND'), 'Topic');
            Jaws_Header::Location($this->GetURLFor('Topic', array('tid' => $rqst['tid'])), true);
        }

        $result = $tModel->LockTopic($topic['id'], !$topic['locked']);
        if (Jaws_Error::IsError($result)) {
            $GLOBALS['app']->Session->PushSimpleResponse($apid->getMessage(), 'Topic');
        } else {
            $GLOBALS['app']->Session->PushSimpleResponse(_t('FORUMS_TOPIC_LOCKED'), 'Topic');
        }

        // Redirect
        Jaws_Header::Location(
            $this->GetURLFor('Posts', array('fid' => $topic['fid'], 'tid' => $topic['id'])),
            true
        );
    }

}