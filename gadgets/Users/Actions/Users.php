<?php
/**
 * Users  Gadget
 *
 * @category   Gadget
 * @package    Users
 */
class Users_Actions_Users extends Users_Actions_Default
{
    /**
     * Show users management interface
     *
     * @access  public
     * @return  string  XHTML template content
     */
    function Users()
    {
        $this->gadget->CheckPermission('ManageUsers');
        $this->AjaxMe('index.js');
        $this->gadget->define('lbl_nickname', _t('USERS_USERS_NICKNAME'));
        $this->gadget->define('lbl_username', _t('USERS_USERS_USERNAME'));
        $this->gadget->define('addUser_title', _t('USERS_USERS_ADD'));
        $this->gadget->define('editUser_title', _t('USERS_USERS_EDIT'));
        $this->gadget->define('deleteUser_title', _t('USERS_USERS_DELETE'));
        $this->gadget->define('editUserGroups_title', _t('USERS_USERS_GROUPS'));
        $this->gadget->define('incompleteUserFields', _t('USERS_MYACCOUNT_INCOMPLETE_FIELDS'));
        $this->gadget->define('wrongPassword', _t('USERS_MYACCOUNT_PASSWORDS_DONT_MATCH'));
        $this->gadget->define('confirmDelete', _t('GLOBAL_CONFIRM_DELETE'));

        $tpl = $this->gadget->template->load('Users.html');
        $tpl->SetBlock('Users');

        $this->SetTitle(_t('USERS_USERS'));
        $tpl->SetVariable('title', _t('USERS_USERS'));

        // Menu navigation
        $this->gadget->action->load('MenuNavigation')->navigation($tpl);

        $JCrypt = Jaws_Crypt::getInstance();
        if (!Jaws_Error::IsError($JCrypt)) {
            $tpl->SetBlock('Users/encryption');
            $tpl->SetVariable('pubkey', $JCrypt->getPublic());
            $tpl->ParseBlock('Users/encryption');
        }

        $tpl->SetVariable('lbl_nickname', _t('USERS_USERS_NICKNAME'));
        $tpl->SetVariable('lbl_username', _t('USERS_USERS_USERNAME'));
        $tpl->SetVariable('lbl_email', _t('GLOBAL_EMAIL'));
        $tpl->SetVariable('lbl_mobile', _t('USERS_CONTACTS_MOBILE_NUMBER'));
        $tpl->SetVariable('lbl_superadmin', _t('USERS_USERS_TYPE_SUPERADMIN'));
        $tpl->SetVariable('lbl_pass1', _t('USERS_USERS_PASSWORD'));
        $tpl->SetVariable('lbl_pass2', _t('USERS_USERS_PASSWORD_VERIFY'));
        $tpl->SetVariable('lbl_concurrents', _t('USERS_USERS_CONCURRENTS'));
        $tpl->SetVariable('lbl_expiry_date', _t('USERS_USERS_EXPIRY_DATE'));

        $tpl->SetVariable('lbl_status', _t('GLOBAL_STATUS'));
        $statusItems = array(
            0 => _t('USERS_USERS_STATUS_0'),
            1 => _t('USERS_USERS_STATUS_1'),
            2 => _t('USERS_USERS_STATUS_2')
        );
        foreach ($statusItems as $val => $title) {
            $tpl->SetBlock('Users/status');
            $tpl->SetVariable('value', $val);
            $tpl->SetVariable('title', $title);
            $tpl->ParseBlock('Users/status');
        }

        $tpl->SetVariable('lbl_save', _t('GLOBAL_SAVE'));
        $tpl->SetVariable('lbl_cancel', _t('GLOBAL_CANCEL'));
        $tpl->SetVariable('lbl_ok', _t('GLOBAL_OK'));
        $tpl->SetVariable('lbl_yes', _t('GLOBAL_YES'));
        $tpl->SetVariable('lbl_no', _t('GLOBAL_NO'));
        $tpl->SetVariable('lbl_add', _t('GLOBAL_ADD'));
        $tpl->SetVariable('lbl_of', _t('GLOBAL_OF'));
        $tpl->SetVariable('lbl_to', _t('GLOBAL_TO'));
        $tpl->SetVariable('lbl_items', _t('GLOBAL_ITEMS'));

        $tpl->SetVariable('addUser_title', _t('USERS_USERS_ADD'));
        $tpl->SetVariable('lbl_userGroups', _t('USERS_USERS_GROUPS'));

        // Groups
        $uModel = new Jaws_User();
        $groups = $uModel->GetGroups(0, true, 'title');
        if (!Jaws_Error::IsError($groups)) {
            foreach ($groups as $group) {
                $tpl->SetBlock('Users/group');
                $tpl->SetVariable('id', $group['id']);
                $tpl->SetVariable('title', $group['title']);
                $tpl->ParseBlock('Users/group');
            }
        }

        // datagrid  filters
        $tpl->SetVariable('lbl_filter_group', _t('USERS_GROUPS_GROUP'));
        $tpl->SetVariable('lbl_filter_type', _t('USERS_USERS_TYPE'));
        $tpl->SetVariable('lbl_filter_status', _t('GLOBAL_STATUS'));
        $tpl->SetVariable('lbl_filter_term', _t('USERS_USERS_SEARCH_TERM'));
        if (!Jaws_Error::IsError($groups)) {
            array_unshift($groups, array('id' => 0, 'title' => _t('GLOBAL_ALL')));
            foreach ($groups as $group) {
                $tpl->SetBlock('Users/filterGroup');
                $tpl->SetVariable('value', $group['id']);
                $tpl->SetVariable('title', $group['title']);
                $tpl->ParseBlock('Users/filterGroup');
            }
        }

        $filterTypes = array(
            0 => _t('GLOBAL_ALL'),
            1 => _t('USERS_USERS_TYPE_SUPERADMIN'),
            2 => _t('USERS_USERS_TYPE_NORMAL'),
        );
        foreach ($filterTypes as $key => $type) {
            $tpl->SetBlock('Users/filterType');
            $tpl->SetVariable('value', $key);
            $tpl->SetVariable('title', $type);
            $tpl->ParseBlock('Users/filterType');
        }

        $this->gadget->action->load('DatePicker')->calendar(
            $tpl,
            array('name' => 'expiry_date')
        );

        $filterTypes = array(
            -1 => _t('GLOBAL_ALL'),
            0 => _t('USERS_USERS_STATUS_0'),
            1 => _t('USERS_USERS_STATUS_1'),
            2 => _t('USERS_USERS_STATUS_2'),
        );
        foreach ($filterTypes as $key => $type) {
            $tpl->SetBlock('Users/filterStatus');
            $tpl->SetVariable('value', $key);
            $tpl->SetVariable('title', $type);
            $tpl->ParseBlock('Users/filterStatus');
        }

        $tpl->ParseBlock('Users');
        return $tpl->Get();
    }

    /**
     * Return user's regions list
     *
     * @access  public
     * @return  string  XHTML content
     */
    function GetUsers()
    {
        if (!$this->app->session->user->logged) {
            return Jaws_HTTPError::Get(401);
        }
        $this->gadget->CheckPermission('ManageUsers');

        $post = $this->gadget->request->fetch(
            array('offset', 'limit', 'sortDirection', 'sortBy', 'filters:array'),
            'post'
        );

        $orderBy = 'id asc';
        if (isset($post['sortBy'])) {
            $orderBy = trim($post['sortBy'] . ' ' . $post['sortDirection']);
        }

        $group = !empty($post['filters']['group']) ? $post['filters']['group'] : false;
        $domain = !empty($post['filters']['domain']) ? $post['filters']['domain'] : false;
        $status = isset($post['filters']['status']) && $post['filters']['status'] >= 0 ? $post['filters']['status'] : null;
        $term = !empty($post['filters']['term']) ? $post['filters']['term'] : null;
        $superadmin = null;
        if (!empty($post['filters']['type'])) {
            if ($post['filters']['type'] == 1) {
                $superadmin = true;
            } else {
                $superadmin = false;
            }
        }

        $uModel = new Jaws_User();
        $users = $uModel->GetUsers($group, $domain, $superadmin, $status, $term, $orderBy, $post['limit'], $post['offset']);
        if (Jaws_Error::IsError($users)) {
            return $this->gadget->session->response(
                $users->getMessage(),
                RESPONSE_ERROR
            );
        }
        $total = $uModel->GetUsersCount($group, $domain, $superadmin, $status, $term);
        if (Jaws_Error::IsError($total)) {
            return $this->gadget->session->response(
                $total->getMessage(),
                RESPONSE_ERROR
            );
        }

        return $this->gadget->session->response(
            '',
            RESPONSE_NOTICE,
            array(
                'total'   => $total,
                'records' => $users
            )
        );
    }

    /**
     * Get a new info
     *
     * @access  public
     * @return  array   Response array (notice or error)
     */
    function GetUser()
    {
        $this->gadget->CheckPermission('ManageUsers');
        $post = $this->gadget->request->fetch(array('id', 'account', 'personal', 'contacts') , 'post');

        $uModel = new Jaws_User();
        $profile = $uModel->GetUser((int)$post['id'], $post['account'], $post['personal'], $post['contacts']);
        if (Jaws_Error::IsError($profile)) {
            return array();
        }

        $objDate = Jaws_Date::getInstance();
        if ($post['account']) {
            if (!empty($profile['expiry_date'])) {
                $profile['expiry_date'] = $objDate->Format($profile['expiry_date'], 'Y/m/d');
            } else {
                $profile['expiry_date'] = '';
            }
        }

        if ($post['personal']) {
            if (empty($profile['avatar'])) {
                $profile['avatar'] = $this->app->getSiteURL('/gadgets/Users/Resources/images/photo128px.png');
            } else {
                $profile['avatar'] = $this->app->getDataURL(). 'avatar/'. $profile['avatar'];
            }

            if (!empty($profile['dob'])) {
                $profile['dob'] = $objDate->Format($profile['dob'], 'Y-m-d');
            } else {
                $profile['dob'] = '';
            }
        }

        return $profile;
    }

    /**
     * Adds a new user
     *
     * @access  public
     * @return  array   Response array (notice or error)
     */
    function AddUser()
    {
        $this->gadget->CheckPermission('ManageUsers');
        $uData = $this->gadget->request->fetch('data:array', 'post');
        $uData['concurrents'] = (int)$uData['concurrents'];
        $uData['superadmin'] = ($uData['superadmin'] == 1) ? true : false;
        $JCrypt = Jaws_Crypt::getInstance();
        if (!Jaws_Error::IsError($JCrypt)) {
            $uData['password'] = $JCrypt->decrypt($uData['password']);
        }

        $uData['status'] = (int)$uData['status'];
        $uData['superadmin'] = $this->app->session->user->superadmin? (bool)$uData['superadmin'] : false;
        $uModel = new Jaws_User();
        $res = $uModel->AddUser($uData);
        if (Jaws_Error::isError($res)) {
            return $this->gadget->session->response($res->GetMessage(), RESPONSE_ERROR);
        } else {
            $guid = $this->gadget->registry->fetch('anon_group');
            if (!empty($guid)) {
                $uModel->AddUserToGroup($res, (int)$guid);
            }
            return $this->gadget->session->response(_t('USERS_USERS_CREATED', $uData['username']), RESPONSE_NOTICE);
        }
    }

    /**
     * Updates user information
     *
     * @access  public
     * @return  array   Response array (notice or error)
     */
    function UpdateUser()
    {
        $this->gadget->CheckPermission('ManageUsers');
        $post = $this->gadget->request->fetch(array('data:array', 'uid'), 'post');
        $uData = $post['data'];
        $uData['concurrents'] = (int)$uData['concurrents'];
        $uData['superadmin'] = ($uData['superadmin'] == 1) ? true : false;

        $JCrypt = Jaws_Crypt::getInstance();
        if (!Jaws_Error::IsError($JCrypt)) {
            $uData['password'] = $JCrypt->decrypt($uData['password']);
        }

        if ($post['uid'] == $this->app->session->user->id) {
            unset($uData['status'], $uData['superadmin'], $uData['expiry_date']);
        } else {
            $uData['status'] = (int)$uData['status'];
            if (!$this->app->session->user->superadmin) {
                unset($uData['status'], $uData['superadmin'], $uData['expiry_date']);
            }
        }

        $uModel = new Jaws_User();
        $res = $uModel->UpdateUser($post['uid'], $uData);
        if (Jaws_Error::isError($res)) {
            return $this->gadget->session->response($res->GetMessage(), RESPONSE_ERROR);
        } else {
            // send activate notification
            if ($uData['prev_status'] == 2 && $uData['status'] == 1) {
                $uRegistration = $this->gadget->action->load('Registration');
                $uRegistration->ActivateNotification($uData, $this->gadget->registry->fetch('anon_activation'));
            }
            return $this->gadget->session->response(_t('USERS_USERS_UPDATED', $uData['username']), RESPONSE_NOTICE);
        }
    }

    /**
     * Delete a user
     *
     * @access  public
     * @return  string  XHTML content
     */
    function DeleteUser()
    {
        $this->gadget->CheckPermission('ManageUsers');
        $uid = $this->gadget->request->fetch('id', 'post');
        if ($uid == $this->app->session->user->id) {
            return $this->gadget->session->response(
                _t('USERS_USERS_CANT_DELETE_SELF'),
                RESPONSE_ERROR
            );
        }

        $uModel = new Jaws_User();
        $profile = $uModel->GetUser((int)$uid);
        if (!$this->app->session->user->superadmin && $profile['superadmin']) {
            return $this->gadget->session->response(
                _t('USERS_USERS_CANT_DELETE', $profile['username']),
                RESPONSE_ERROR
            );
        }

        if (!$uModel->DeleteUser($uid)) {
            return $this->gadget->session->response(
                _t('USERS_USERS_CANT_DELETE', $profile['username']),
                RESPONSE_ERROR
            );
        } else {
            return $this->gadget->session->response(
                _t('USERS_USER_DELETED', $profile['username']),
                RESPONSE_NOTICE
            );
        }
    }

    /**
     * Gets the user-groups data
     *
     * @access  public
     * @return  array   Groups data
     */
    function GetUserGroups()
    {
        $this->gadget->CheckPermission('ManageGroups');

        $uid = $this->gadget->request->fetch('uid', 'post');
        $uModel = new Jaws_User();
        $groups = $uModel->GetGroupsOfUser((int)$uid);
        if (Jaws_Error::IsError($groups)) {
            return array();
        }

        return array_keys($groups);
    }

    /**
     * Adds a user to groups
     *
     * @access  public
     * @return  array   Response array (notice or error)
     */
    function AddUserToGroups()
    {
        $this->gadget->CheckPermission('ManageGroups');
        $post = $this->gadget->request->fetch(array('uid', 'groups:array'), 'post');
        $uModel = new Jaws_User();
        $oldGroups = $uModel->GetGroupsOfUser((int)$post['uid']);
        if (!Jaws_Error::IsError($oldGroups)) {
            $oldGroups = array_keys($oldGroups);
            foreach ($post['groups'] as $group) {
                if (false === $gIndex = array_search($group, $oldGroups)) {
                    $uModel->AddUserToGroup($post['uid'], $group);
                } else {
                    unset($oldGroups[$gIndex]);
                }
            }

            // delete remainder groups
            foreach ($oldGroups as $group) {
                $uModel->DeleteUserFromGroup($post['uid'], $group);
            }

            return $this->gadget->session->response(_t('USERS_GROUPS_UPDATED_USERS'),
                RESPONSE_NOTICE);
        } else {
            return $this->gadget->session->response($oldGroups->GetMessage(),
                RESPONSE_ERROR);
        }
    }

}