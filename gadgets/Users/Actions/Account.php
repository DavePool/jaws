<?php
/**
 * Users Core Gadget
 *
 * @category    Gadget
 * @package     Users
 */
class Users_Actions_Account extends Users_Actions_Default
{
    /**
     * Builds a simple form to update user account info(nickname, email, password)
     *
     * @access  public
     * @return  string  XHTML form
     */
    function Account()
    {
        if (!$this->app->session->user->logged) {
            return Jaws_Header::Location(
                $this->gadget->urlMap(
                    'Login',
                    array('referrer'  => bin2hex(Jaws_Utils::getRequestURL(true)))
                )
            );
        }

        $this->gadget->CheckPermission('EditUserName,EditUserNickname,EditUserEmail,EditUserPassword', '', false);
        $response = $this->gadget->session->pop('Account');
        if (!isset($response['data'])) {
            $jUser = new Jaws_User;
            $account = $jUser->GetUser($this->app->session->user->id, true, true);
        } else {
            $account = $response['data'];
        }

        // Load the template
        $tpl = $this->gadget->template->load('Account.html');
        $tpl->SetBlock('account');
        $tpl->SetVariable('title', _t('USERS_PERSONAL_INFO'));
        $tpl->SetVariable('base_script', BASE_SCRIPT);
        $tpl->SetVariable('update', _t('USERS_USERS_ACCOUNT_UPDATE'));

        // Menu navigation
        $this->gadget->action->load('MenuNavigation')->navigation($tpl);

        $tpl->SetVariable('title', _t('USERS_ACCOUNT_INFO'));
        $tpl->SetVariable('update', _t('USERS_USERS_ACCOUNT_UPDATE'));
        $tpl->SetVariable('lbl_username', _t('USERS_USERS_USERNAME'));
        $tpl->SetVariable('lbl_nickname', _t('USERS_USERS_NICKNAME'));
        $tpl->SetVariable('lbl_email', _t('GLOBAL_EMAIL'));
        $tpl->SetVariable('lbl_mobile', _t('USERS_CONTACTS_MOBILE_NUMBER'));
        $tpl->SetVariable('lbl_password', _t('USERS_USERS_PASSWORD'));
        $tpl->SetVariable('emptypassword', _t('USERS_NOCHANGE_PASSWORD'));
        $tpl->SetVariable('lbl_chkpassword', _t('USERS_USERS_PASSWORD_VERIFY'));

        if (!$this->gadget->GetPermission('EditUserName')) {
            $tpl->SetVariable('username_disabled', 'disabled="disabled"');
        }
        if (!$this->gadget->GetPermission('EditUserNickname')) {
            $tpl->SetVariable('nickname_disabled', 'disabled="disabled"');
        }
        if (!$this->gadget->GetPermission('EditUserEmail')) {
            $tpl->SetVariable('email_disabled', 'disabled="disabled"');
        }
        if (!$this->gadget->GetPermission('EditUserMobile')) {
            $tpl->SetVariable('mobile_disabled', 'disabled="disabled"');
        }
        if (!$this->gadget->GetPermission('EditUserPassword')) {
            $tpl->SetVariable('password_disabled', 'disabled="disabled"');
        }

        if (empty($account['avatar'])) {
            $user_current_avatar = $this->app->getSiteURL('/gadgets/Users/Resources/images/photo128px.png');
        } else {
            $user_current_avatar = $this->app->getDataURL() . "avatar/" . $account['avatar'];
            $user_current_avatar .= !empty($account['last_update']) ? "?" . $account['last_update'] . "" : '';
        }
        $avatar =& Piwi::CreateWidget('Image', $user_current_avatar);
        $avatar->SetID('avatar');
        $tpl->SetVariable('avatar', $avatar->Get());

        $tpl->SetVariablesArray($account);

        if (!empty($response)) {
            $tpl->SetVariable('response_type', $response['type']);
            $tpl->SetVariable('response_text', $response['text']);
        }

        $tpl->ParseBlock('account');
        return $tpl->Get();
    }

    /**
     * Updates user account information
     *
     * @access  public
     * @return  void
     */
    function UpdateAccount()
    {
        if (!$this->app->session->user->logged) {
            return Jaws_Header::Location(
                $this->gadget->urlMap(
                    'Login',
                    array('referrer'  => bin2hex(Jaws_Utils::getRequestURL(true)))
                )
            );
        }

        $this->gadget->CheckPermission(
            'EditUserName,EditUserNickname,EditUserEmail,EditUserMobile,EditUserPassword',
            '',
            false
        );
        $post = $this->gadget->request->fetch(
            array('username', 'nickname', 'email', 'mobile', 'password', 'chkpassword'),
            'post'
        );
        if ($post['password'] === $post['chkpassword']) {
            // check edit username permission
            if (empty($post['username']) ||
                !$this->gadget->GetPermission('EditUserName'))
            {
                $post['username'] = $this->app->session->user->username;
            }
            // check edit nickname permission
            if (empty($post['nickname']) ||
                !$this->gadget->GetPermission('EditUserNickname'))
            {
                $post['nickname'] = $this->app->session->user->nickname;
            }
            // check edit email permission
            if (empty($post['email']) ||
                !$this->gadget->GetPermission('EditUserEmail'))
            {
                $post['email'] = $this->app->session->user->email;
            }

            // set new email
            $post['new_email'] = '';
            if ($post['email'] != $this->app->session->user->email) {
                $post['new_email'] = $post['email'];
                $post['email'] = $this->app->session->user->email;
            }

            // check edit mobile permission
            if (empty($post['mobile']) ||
                !$this->gadget->GetPermission('EditUserMobile'))
            {
                $post['mobile'] = $this->app->session->user->mobile;
            }

            // check edit password permission
            if (empty($post['password']) ||
                !$this->gadget->GetPermission('EditUserPassword'))
            {
                $post['password'] = null;
            }

            $model  = $this->gadget->model->load('Account');
            $result = $model->UpdateAccount(
                $this->app->session->user->id,
                $post['username'],
                $post['nickname'],
                $post['email'],
                $post['new_email'],
                $post['mobile'],
                $post['password']
            );
            // unset unnecessary account data
            unset($post['password'], $post['chkpassword']);
            if (!Jaws_Error::IsError($result)) {
                $message = _t('USERS_MYACCOUNT_UPDATED');
                if (!empty($post['new_email'])) {
                    $mResult = $this->ReplaceEmailNotification(
                        $this->app->session->user->id,
                        $post['username'],
                        $post['nickname'],
                        $post['new_email'],
                        $post['email'],
                        $post['mobile']
                    );
                    if (Jaws_Error::IsError($mResult)) {
                        $message = $message. "\n" . $mResult->getMessage();
                    } else {
                        $message = $message. "\n" . _t('USERS_EMAIL_REPLACEMENT_SENT');
                    }
                }
                $this->gadget->session->push(
                    $message,
                    RESPONSE_NOTICE,
                    'Account'
                );
            } else {
                $this->gadget->session->push(
                    $result->GetMessage(),
                    RESPONSE_ERROR,
                    'Account',
                    $post
                );
            }
        } else {
            // unset unnecessary account data
            unset($post['password'], $post['chkpassword']);
            $this->gadget->session->push(
                _t('USERS_USERS_PASSWORDS_DONT_MATCH'),
                RESPONSE_ERROR,
                'Account',
                $post
            );
        }

        return Jaws_Header::Location($this->gadget->urlMap('Account'));
    }

    /**
     * Sends replace email notification to user
     *
     * @access  public
     * @param   int     $user_id    User's ID
     * @param   string  $nickname   User's nickname
     * @param   string  $new_email  User's new email
     * @param   string  $old_email  User's old email
     * @param   string  $mobile     User's mobile number
     * @return  mixed   True on success otherwise Jaws_Error on failure
     */
    function ReplaceEmailNotification($user_id, $username, $nickname, $new_email, $old_email, $mobile)
    {
        $tpl = $this->gadget->template->load('NewEmail.txt');
        $tpl->SetBlock('Notification');
        $tpl->SetVariable('nickname', $nickname);
        $tpl->SetVariable('say_hello', _t('USERS_EMAIL_REPLACEMENT_HELLO', $nickname));
        $tpl->SetVariable('message', _t('USERS_EMAIL_REPLACEMENT_MSG'));

        $tpl->SetBlock('Notification/IP');
        $tpl->SetVariable('lbl_ip', _t('GLOBAL_IP'));
        $tpl->SetVariable('ip', $_SERVER['REMOTE_ADDR']);
        $tpl->ParseBlock('Notification/IP');

        $tpl->SetVariable('lbl_username', _t('USERS_USERS_USERNAME'));
        $tpl->SetVariable('username', $username);

        $tpl->SetVariable('lbl_email', _t('GLOBAL_EMAIL'));
        $tpl->SetVariable('email', $old_email);

        $jUser = new Jaws_User;
        $verifyKey = $jUser->UpdateEmailVerifyKey($user_id);
        if (Jaws_Error::IsError($verifyKey)) {
            return $verifyKey;
        } else {
            $tpl->SetBlock('Notification/Activation');
            $tpl->SetVariable('lbl_activation_link', _t('USERS_ACTIVATE_ACTIVATION_LINK'));
            $tpl->SetVariable(
                'activation_link',
                $this->gadget->urlMap(
                    'ReplaceUserEmail',
                    array('key' => $verifyKey),
                    array('absolute' => true)
                )
            );
            $tpl->ParseBlock('Notification/Activation');
        }

        $site_url  = $this->app->getSiteURL('/');
        $site_name = $this->gadget->registry->fetch('site_name', 'Settings');
        $tpl->SetVariable('site-name', $site_name);
        $tpl->SetVariable('site-url',  $site_url);
        $tpl->SetVariable('thanks',    _t('GLOBAL_THANKS'));

        $tpl->ParseBlock('Notification');
        $body = $tpl->Get();

        $subject = _t('USERS_EMAIL_REPLACEMENT_SUBJECT', $site_name);
        $mail = Jaws_Mail::getInstance();
        $mail->SetFrom();
        $mail->AddRecipient($new_email);
        $mail->SetSubject($subject);
        $mail->SetBody($this->gadget->plugin->parseAdmin($body));
        $mresult = $mail->send();
        if (Jaws_Error::IsError($mresult)) {
            return $mresult;
        }

        return true;
    }

}