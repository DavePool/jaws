<?php
/**
 * Directory Gadget
 *
 * @category    Gadget
 * @package     Directory
 * @author      Mohsen Khahani <mkhahani@gmail.com>
 * @copyright   2013 Jaws Development Group
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class Directory_Actions_Share extends Jaws_Gadget_HTML
{
    /**
     * Fetches list of system user groups
     *
     * @access  public
     * @return  array   Array of groups or an empty array
     */
    function ShareForm()
    {
        $tpl = $this->gadget->loadTemplate('Share.html');
        $tpl->SetBlock('share');
        $tpl->SetVariable('lbl_shared_for', _t('DIRECTORY_SHARED_FOR'));
        $tpl->SetVariable('lbl_public', _t('DIRECTORY_FILE_PUBLIC_URL'));
        $tpl->SetVariable('lbl_make_public', _t('DIRECTORY_FILE_MAKE_PUBLIC'));

        // Edit UI
        if ($this->gadget->GetPermission('ShareFile')) {
            $tpl->SetBlock('share/edit');
            $tpl->SetVariable('lbl_groups', _t('DIRECTORY_GROUPS'));
            $tpl->SetVariable('lbl_users', _t('DIRECTORY_USERS'));
            require_once JAWS_PATH . 'include/Jaws/User.php';
            $uModel = new Jaws_User();
            $groups = $uModel->GetGroups(true, 'title');
            if (!Jaws_Error::IsError($groups)) {
                $combo =& Piwi::CreateWidget('Combo', 'groups');
                $combo->AddEvent(ON_CHANGE, 'toggleUsers(this.value)');
                $combo->AddOption('All Users', 0);
                foreach ($groups as $group) {
                    $combo->AddOption($group['title'], $group['id']);
                }
                $tpl->SetVariable('groups', $combo->Get());
            }
            $tpl->ParseBlock('share/edit');
            $tpl->SetBlock('share/actions');
            $tpl->SetVariable('lbl_ok', _t('GLOBAL_OK'));
            $tpl->SetVariable('lbl_cancel', _t('GLOBAL_CANCEL'));
            $tpl->ParseBlock('share/actions');
        }

        $tpl->ParseBlock('share');
        return $tpl->Get();
    }

    /**
     * Fetches list of system users
     *
     * @access  public
     * @return  array   Array of users or an empty array
     */
    function GetUsers()
    {
        $gid = (int)jaws()->request->fetch('gid');
        if ($gid === 0) {
            $gid = false;
        }
        require_once JAWS_PATH . 'include/Jaws/User.php';
        $uModel = new Jaws_User();
        $users = $uModel->GetUsers($gid, null, 1);
        if (Jaws_Error::IsError($users)) {
            return array();
        }
        return $users;
    }

    /**
     * Fetches ID's of users whitch the file is shared for
     *
     * @access  public
     * @return  array   Array of users or an empty array
     */
    function GetFileUsers()
    {
        $id = (int)jaws()->request->fetch('id');

        // Validate file
        $model = $GLOBALS['app']->LoadGadget('Directory', 'Model', 'Files');
        $file = $model->GetFile($id);
        if (Jaws_Error::IsError($file)) {
            return array();
        }
        $user = (int)$GLOBALS['app']->Session->GetAttribute('user');
        if ($file['user'] != $user) {
            return array();
        }

        $model = $GLOBALS['app']->LoadGadget('Directory', 'Model', 'Share');
        $users = $model->GetFileUsers($id);
        if (Jaws_Error::IsError($users)) {
            return array();
        }
        return $users;
    }

    /**
     * Shares file for passed users
     *
     * @access  public
     * @return  array   Array of users or an empty array
     */
    function UpdateFileUsers()
    {
        try {
            $id = (int)jaws()->request->fetch('id');

            // Validate file
            $model = $GLOBALS['app']->LoadGadget('Directory', 'Model', 'Files');
            $file = $model->GetFile($id);
            if (Jaws_Error::IsError($file)) {
                throw new Exception($file->getMessage());
            }

            // Validate user
            $user = (int)$GLOBALS['app']->Session->GetAttribute('user');
            if ($file['user'] != $user) {
                throw new Exception(_t('DIRECTORY_ERROR_UPDATING_SHARE'));
            }

            $users = jaws()->request->fetch('users');
            $users = empty($users)? array() : explode(',', $users);
            $model = $GLOBALS['app']->LoadGadget('Directory', 'Model', 'Share');
            $res = $model->UpdateFileUsers($id, $users);
            if (Jaws_Error::IsError($res)) {
                throw new Exception($res->getMessage());
            }
        } catch (Exception $e) {
            return $GLOBALS['app']->Session->GetResponse(
                $e->getMessage(),
                RESPONSE_ERROR
            );
        }

        return $GLOBALS['app']->Session->GetResponse(
            _t('DIRECTORY_NOTICE_SHARE_UPDATED'),
            RESPONSE_NOTICE
        );
    }
}