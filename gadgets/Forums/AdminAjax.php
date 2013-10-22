<?php
/**
 * Forums AJAX API
 *
 * @category   Ajax
 * @package    Forums
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2012-2013 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class Forums_AdminAjax extends Jaws_Gadget_HTML
{
    /**
     * Get information of a group
     *
     * @access   public
     * @internal param  int     $gid    Group ID
     * @return   mixed  Group information or False on error
     */
    function GetGroup()
    {
        $this->gadget->CheckPermission('default');
        @list($gid) = jaws()->request->fetchAll('post');
        $gModel = $this->gadget->loadModel('Groups');
        $group = $gModel->GetGroup($gid);
        if (Jaws_Error::IsError($group)) {
            return false; //we need to handle errors on ajax
        }

        return $group;
    }

    /**
     * Get information of a forum
     *
     * @access   public
     * @internal param  int     $fid    Forum ID
     * @return   mixed  Forum information or False on error
     */
    function GetForum()
    {
        $this->gadget->CheckPermission('default');
        @list($fid) = jaws()->request->fetchAll('post');
        $fModel = $this->gadget->loadModel('Forums');
        $forum = $fModel->GetForum($fid);
        if (Jaws_Error::IsError($forum)) {
            return false; //we need to handle errors on ajax
        }

        return $forum;
    }

    /**
     * Returns the group form
     *
     * @access  public
     * @return  string  XHTML template content of groupForm
     */
    function GetGroupUI()
    {
        $this->gadget->CheckPermission('default');
        $gHTML = $GLOBALS['app']->LoadGadget('Forums', 'AdminHTML', 'Group');
        return $gHTML->GetGroupUI();
    }

    /**
     * Returns the forum form
     *
     * @access  public
     * @return  string  XHTML template content of groupForm
     */
    function GetForumUI()
    {
        $this->gadget->CheckPermission('default');
        $fHTML = $GLOBALS['app']->LoadGadget('Forums', 'AdminHTML', 'Forum');
        return $fHTML->GetForumUI();
    }

    /**
     * Insert forum
     *
     * @access   public
     * @internal param  int     $gid            group ID
     * @internal param  string  $title          forum title
     * @internal param  string  $description    forum description
     * @internal param  string  $fast_url
     * @internal param  string  $order
     * @internal param  bool    $locked         is locked
     * @internal param  bool    $published      is published
     * @return   array  Response array (notice or error)
     */
    function InsertForum()
    {
        $this->gadget->CheckPermission('ManageForums');
        @list($gid, $title, $description, $fast_url, $order, $locked, $published) = jaws()->request->fetchAll('post');
        $fModel = $this->gadget->loadAdminModel('Forums');
        $res = $fModel->InsertForum($gid, $title, $description, $fast_url, $order, $locked, $published);
        if (Jaws_Error::IsError($res)) {
            return $GLOBALS['app']->Session->GetResponse($res->getMessage(), RESPONSE_ERROR);
        }

        return $GLOBALS['app']->Session->GetResponse(
            _t('FORUMS_NOTICE_FORUM_CREATED'),
            RESPONSE_NOTICE,
            $res
        );
    }

    /**
     * Update forum
     *
     * @access   public
     * @internal param  int     $fid            forum ID
     * @internal param  int     $gid            group ID
     * @internal param  string  $title forum    title
     * @internal param  string  $description    forum description
     * @internal param  string  $fast_url
     * @internal param  string  $order
     * @internal param  bool    $locked
     * @internal param  bool    $published
     * @return   array  Response array (notice or error)
     */
    function UpdateForum()
    {
        $this->gadget->CheckPermission('ManageForums');
        @list($fid, $gid, $title, $description,
            $fast_url, $order, $locked, $published
        ) = jaws()->request->fetchAll('post');
        $fModel = $this->gadget->loadAdminModel('Forums');
        $res = $fModel->UpdateForum($fid, $gid, $title, $description, $fast_url, $order, $locked, $published);
        if (Jaws_Error::IsError($res)) {
            return $GLOBALS['app']->Session->GetResponse($res->getMessage(), RESPONSE_ERROR);
        }

        return $GLOBALS['app']->Session->GetResponse(
            _t('FORUMS_NOTICE_FORUM_UPDATED'),
            RESPONSE_NOTICE
        );
    }

    /**
     * Delete a forum
     *
     * @access   public
     * @internal param  int     $fid    Forum ID
     * @return   array  Response array (notice or error)
     */
    function DeleteForum()
    {
        $this->gadget->CheckPermission('ManageForums');
        @list($fid) = jaws()->request->fetchAll('post');
        $fModel = $this->gadget->loadAdminModel('Forums');
        $res = $fModel->DeleteForum($fid);
        if (Jaws_Error::IsError($res)) {
            return $GLOBALS['app']->Session->GetResponse($res->getMessage(), RESPONSE_ERROR);
        } elseif ($res) {
            return $GLOBALS['app']->Session->GetResponse(
                _t('FORUMS_NOTICE_FORUM_DELETED'),
                RESPONSE_NOTICE
            );
        } else {
            return $GLOBALS['app']->Session->GetResponse(
                _t('FORUMS_ERROR_FORUM_NOT_EMPTY'),
                RESPONSE_ERROR
            );
        }
    }

    /**
     * Insert group
     *
     * @access   public
     * @internal param  string  $title          group title
     * @internal param  string  $description    group description
     * @internal param  string  $fast_url
     * @internal param  string  $order
     * @internal param  bool    $locked
     * @internal param  bool    $published
     * @return   array  Response array (notice or error)
     */
    function InsertGroup()
    {
        $this->gadget->CheckPermission('ManageForums');
        @list($title, $description, $fast_url, $order, $locked, $published) = jaws()->request->fetchAll('post');
        $gModel = $this->gadget->loadAdminModel('Groups');
        $gid = $gModel->InsertGroup($title, $description, $fast_url, $order, $locked, $published);
        if (Jaws_Error::IsError($gid)) {
            return $GLOBALS['app']->Session->GetResponse($gid->getMessage(), RESPONSE_ERROR);
        }

        return $GLOBALS['app']->Session->GetResponse(
            _t('FORUMS_NOTICE_GROUP_CREATED'),
            RESPONSE_NOTICE,
            $gid
        );
    }

    /**
     * Update group
     *
     * @access   public
     * @internal param  int     $gid            group ID
     * @internal param  string  $title          group title
     * @internal param  string  $description    group description
     * @internal param  string  $fast_url
     * @internal param  string  $order
     * @internal param  bool    $locked
     * @internal param  bool    $published
     * @return   array  Response array (notice or error)
     */
    function UpdateGroup()
    {
        $this->gadget->CheckPermission('ManageForums');
        @list($gid, $title, $description, $fast_url, $order, $locked, $published) = jaws()->request->fetchAll('post');
        $gModel = $this->gadget->loadAdminModel('Groups');
        $res = $gModel->UpdateGroup($gid, $title, $description, $fast_url, $order, $locked, $published);
        if (Jaws_Error::IsError($res)) {
            return $GLOBALS['app']->Session->GetResponse($res->getMessage(), RESPONSE_ERROR);
        }

        return $GLOBALS['app']->Session->GetResponse(
            _t('FORUMS_NOTICE_GROUP_UPDATED'),
            RESPONSE_NOTICE
        );
    }

    /**
     * Delete a group
     *
     * @access   public
     * @internal param  int     $gid    Group ID
     * @return   array  Response array (notice or error)
     */
    function DeleteGroup()
    {
        $this->gadget->CheckPermission('ManageForums');
        @list($gid) = jaws()->request->fetchAll('post');
        $gModel = $this->gadget->loadAdminModel('Groups');
        $res = $gModel->DeleteGroup($gid);
        if (Jaws_Error::IsError($res)) {
            return $GLOBALS['app']->Session->GetResponse($res->getMessage(), RESPONSE_ERROR);
        } elseif ($res) {
            return $GLOBALS['app']->Session->GetResponse(
                _t('FORUMS_NOTICE_GROUP_DELETED'),
                RESPONSE_NOTICE
            );
        } else {
            return $GLOBALS['app']->Session->GetResponse(
                _t('FORUMS_ERROR_GROUP_NOT_EMPTY'),
                RESPONSE_ERROR
            );
        }
    }

}