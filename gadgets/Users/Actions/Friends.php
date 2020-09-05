<?php
/**
 * Users Core Gadget
 *
 * @category    Gadget
 * @package     Users
 */
class Users_Actions_Friends extends Users_Actions_Default
{
    /**
     * Prepares a form for manage user's friends groups
     *
     * @access  public
     * @return  string  XHTML template of a form
     */
    function FriendsGroups()
    {
        if (!$this->app->session->user->logged) {
            return Jaws_Header::Location(
                $this->gadget->urlMap(
                    'Login',
                    array('referrer'  => bin2hex(Jaws_Utils::getRequestURL(true)))
                )
            );
        }

        $this->gadget->CheckPermission('ManageFriends');
        $this->AjaxMe('index.js');
        $this->gadget->define('lbl_title', Jaws::t('TITLE'));
        $this->gadget->define('lbl_name', Jaws::t('NAME'));
        $this->gadget->define('confirmDelete', Jaws::t('CONFIRM_DELETE'));
        $this->gadget->define('lbl_addFriend', _t('USERS_FRIENDS_ADD'));
        $this->gadget->define('lbl_editFriend', _t('USERS_FRIENDS_EDIT'));
        $this->gadget->define('lbl_edit', Jaws::t('EDIT'));
        $this->gadget->define('lbl_delete', Jaws::t('DELETE'));
        $this->gadget->define('lbl_manageFriends', _t('USERS_FRIENDS_MANAGE'));

        $response = $this->gadget->session->pop('Groups');
        // Load the template
        $tpl = $this->gadget->template->load('Friends.html');
        $tpl->SetBlock('groups');
        if (!empty($response)) {
            $tpl->SetVariable('response_type', $response['type']);
            $tpl->SetVariable('response_text', $response['text']);
        }

        // Menu navigation
        $this->gadget->action->load('MenuNavigation')->navigation($tpl);

        $tpl->SetVariable('title', _t('USERS_FRIENDS'));

        // Users
        $uModel = new Jaws_User();
        $superadmin = $this->app->session->user->superadmin ? null : false;
        $users = $uModel->GetUsers(false, false, $superadmin);
        if (!Jaws_Error::IsError($users)) {
            foreach ($users as $user) {
                $tpl->SetBlock('groups/user');
                $tpl->SetVariable('id', $user['id']);
                $tpl->SetVariable('title', $user['nickname']. ' ('. $user['username']. ')');
                $tpl->ParseBlock('groups/user');
            }
        }

        $tpl->SetVariable('lbl_addFriend', _t('USERS_FRIENDS_ADD'));
        $tpl->SetVariable('lbl_manageFriends', _t('USERS_FRIENDS_MANAGE'));
        $tpl->SetVariable('lbl_name', Jaws::t('NAME'));
        $tpl->SetVariable('lbl_description', Jaws::t('DESCRIPTION'));
        $tpl->SetVariable('lbl_title', Jaws::t('TITLE'));
        $tpl->SetVariable('lbl_add', Jaws::t('ADD'));
        $tpl->SetVariable('lbl_actions', Jaws::t('ACTIONS'));
        $tpl->SetVariable('lbl_no_action', Jaws::t('NO_ACTION'));
        $tpl->SetVariable('lbl_cancel', Jaws::t('CANCEL'));
        $tpl->SetVariable('lbl_save', Jaws::t('SAVE'));

        $tpl->SetVariable('lbl_delete', Jaws::t('DELETE'));
        $tpl->SetVariable('lbl_add_group', _t('USERS_ADD_GROUP'));
        $tpl->SetVariable('url_add_group', $this->gadget->urlMap('FriendsGroupUI'));

        $tpl->SetVariable('lbl_of', Jaws::t('OF'));
        $tpl->SetVariable('lbl_to', Jaws::t('TO'));
        $tpl->SetVariable('lbl_items', Jaws::t('ITEMS'));
        $tpl->SetVariable('lbl_per_page', Jaws::t('PERPAGE'));

        $tpl->ParseBlock('groups');
        return $tpl->Get();
    }

    /**
     * Get friends groups list
     *
     * @access  public
     * @return  JSON
     */
    function GetFriendGroups()
    {
        $this->gadget->CheckPermission('ManageFriends');
        $post = $this->gadget->request->fetch(
            array('filters:array', 'limit', 'offset', 'searchLogic', 'search:array', 'sort:array'),
            'post'
        );

        $user = $this->app->session->user->id;
        $jUser = new Jaws_User;
        $groups = $jUser->GetGroups($user, $post['limit'], $post['offset']);

        foreach($groups as $key=>$group) {
            $group['recid'] = $group['id'];
            $groups[$key] = $group;
        }
        $groupsCount = $jUser->GetUserContactsCount($user);

        return array(
            'status' => 'success',
            'total' => $groupsCount,
            'records' => $groups
        );
    }

    /**
     * Get a friend group info
     *
     * @access  public
     * @return  JSON
     */
    function GetFriendGroup()
    {
        $this->gadget->CheckPermission('ManageFriends');
        $id = $this->gadget->request->fetch('id', 'post');

        $user = $this->app->session->user->id;
        $jUser = new Jaws_User;
        return $jUser->GetGroup($id, $user);
    }

    /**
     * Add or Update a friend group
     *
     * @access  public
     * @return  void
     */
    function SaveFriendGroup()
    {
        $this->gadget->CheckPermission('ManageFriends');

        $post = $this->gadget->request->fetch(array('id', 'data:array'), 'post');
        $user = $this->app->session->user->id;
        $jUser = new Jaws_User;

        // Update group
        if(!empty($post['id'])) {
            $res = $jUser->UpdateGroup($post['id'], $post['data'], $user);
            // Add new group
        } else {
            unset($post['id']);
            $res = $jUser->AddGroup($post['data'], $user);
        }

        if (Jaws_Error::isError($res)) {
            return $this->gadget->session->response($res->GetMessage(), RESPONSE_ERROR);
        } else {
            return $this->gadget->session->response(_t('USERS_GROUPS_CREATED', $post['data']['title']), RESPONSE_NOTICE);
        }
    }

    /**
     * Delete user's friend group(s)
     *
     * @access  public
     * @return  void
     */
    function DeleteFriendGroups()
    {
        $this->gadget->CheckPermission('ManageFriends');

        $ids = $this->gadget->request->fetch('ids:array', 'post');
        $user = $this->app->session->user->id;

        if (!empty($ids)) {
            $jUser = new Jaws_User;
            foreach($ids as $id) {
                // TODO: improve performance
                $res= $jUser->DeleteGroup($id, $user);
                if (Jaws_Error::IsError($res)) {
                    $this->gadget->session->push(
                        $res->getMessage(),
                        RESPONSE_ERROR,
                        'Groups'
                    );
                    break;
                }
            }

            if (Jaws_Error::isError($res)) {
                return $this->gadget->session->response($res->GetMessage(), RESPONSE_ERROR);
            } else {
                return $this->gadget->session->response(_t('USERS_GROUP_DELETED'), RESPONSE_NOTICE);
            }
        }
    }

    /**
     * Adds a group of users(by their IDs) to a certain group
     *
     * @access  public
     * @return  array   Response array (notice or error)
     */
    function AddUsersToFriendGroup()
    {
        $this->gadget->CheckPermission('ManageGroups');
        $post = $this->gadget->request->fetch(array('gid', 'users:array'), 'post');

        $uModel = $this->gadget->model->load('Friends');
        $user = $this->app->session->user->id;
        $res = $uModel->AddUsersToGroup((int)$post['gid'], $post['users'], $user);

        if (Jaws_Error::IsError($res)) {
            return $this->gadget->session->response(_t('USERS_GROUP_CANNOT_ADD_USER'), RESPONSE_ERROR);
        } else {
            return $this->gadget->session->response(_t('USERS_GROUP_ADDED_USER'), RESPONSE_NOTICE);
        }
    }
}