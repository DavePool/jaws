<?php
/**
 * Users Core Gadget
 *
 * @category    Gadget
 * @package     Users
 * @author      Jonathan Hernandez <ion@suavizado.com>
 * @author      Ali Fazelzadeh <afz@php.net>
 * @copyright   2004-2013 Jaws Development Group
 * @license     http://www.gnu.org/copyleft/lesser.html
 */
class Users_Actions_Personal extends Users_HTML
{
    /**
     * Builds a simple form to update user personal (fname, lname, gender, ...)
     *
     * @access  public
     * @return  string  XHTML form
     */
    function Personal()
    {
        if (!$GLOBALS['app']->Session->Logged()) {
            Jaws_Header::Location(
                $this->gadget->urlMap(
                    'LoginBox',
                    array('referrer'  => bin2hex(Jaws_Utils::getRequestURL(true)))
                )
            );
        }

        $this->gadget->CheckPermission('EditUserPersonal');
        $response = $GLOBALS['app']->Session->PopResponse('Users.Personal.Response');
        if (!isset($response['data'])) {
            require_once JAWS_PATH . 'include/Jaws/User.php';
            $jUser = new Jaws_User;
            $personal  = $jUser->GetUser($GLOBALS['app']->Session->GetAttribute('user'), true, true);
        } else {
            $personal = $response['data'];
        }

        // Load the template
        $tpl = $this->gadget->loadTemplate('Personal.html');
        $tpl->SetBlock('personal');
        $tpl->SetVariable('title', _t('USERS_PERSONAL_INFO'));
        $tpl->SetVariable('base_script', BASE_SCRIPT);
        $tpl->SetVariable('update', _t('USERS_USERS_ACCOUNT_UPDATE'));

        if (empty($personal['avatar'])) {
            $user_current_avatar = $GLOBALS['app']->getSiteURL('/gadgets/Users/images/photo128px.png');
        } else {
            $user_current_avatar = $GLOBALS['app']->getDataURL() . "avatar/" . $personal['avatar'];
            $user_current_avatar .= !empty($personal['last_update']) ? "?" . $personal['last_update'] . "" : '';
        }

        $avatar =& Piwi::CreateWidget('Image', $user_current_avatar);
        $avatar->SetID('avatar');
        $avatar->SetStyle('max-width: 128px;max-height: 128px;');
        $tpl->SetVariable('avatar', $avatar->Get());

        $tpl->SetVariable('lbl_fname',  _t('USERS_USERS_FIRSTNAME'));
        $tpl->SetVariable('fname',      $personal['fname']);
        $tpl->SetVariable('lbl_lname',  _t('USERS_USERS_LASTNAME'));
        $tpl->SetVariable('lname',      $personal['lname']);
        $tpl->SetVariable('lbl_gender', _t('USERS_USERS_GENDER'));
        $tpl->SetVariable('gender_0',   _t('USERS_USERS_GENDER_0'));
        $tpl->SetVariable('gender_1',   _t('USERS_USERS_GENDER_1'));
        $tpl->SetVariable('gender_2',   _t('USERS_USERS_GENDER_2'));
        $tpl->SetVariable('selected_gender_'.(int)$personal['gender'], 'selected="selected"');
        $tpl->SetVariable('lbl_ssn',    _t('USERS_USERS_SSN'));
        $tpl->SetVariable('ssn',        $personal['ssn']);

        if (empty($personal['dob'])) {
            $dob = array('', '', '');
        } else {
            $date = $GLOBALS['app']->loadDate();
            $dob = $date->Format($personal['dob'], 'Y-m-d');
            $dob = explode('-', $dob);
        }

        $tpl->SetVariable('lbl_dob',    _t('USERS_USERS_BIRTHDAY'));
        $tpl->SetVariable('dob_year',   $dob[0]);
        $tpl->SetVariable('dob_month',  $dob[1]);
        $tpl->SetVariable('dob_day',    $dob[2]);
        $tpl->SetVariable('dob_sample', _t('USERS_USERS_BIRTHDAY_SAMPLE'));

        // website
        $tpl->SetVariable('lbl_url', _t('GLOBAL_URL'));
        $tpl->SetVariable('url',     empty($personal['url'])? 'http://' : $personal['url']);

        // upload/delete avatar
        $tpl->SetVariable('lbl_upload_avatar', _t('USERS_USERS_AVATAR_UPLOAD'));
        $tpl->SetVariable('lbl_delete_avatar', _t('USERS_USERS_AVATAR_DELETE'));

        // signature
        $tpl->SetVariable('lbl_signature', _t('USERS_USERS_SIGNATURE'));
        $tpl->SetVariable('signature',     $personal['signature']);

        // about
        $tpl->SetVariable('lbl_about', _t('USERS_USERS_ABOUT'));
        $tpl->SetVariable('about',     $personal['about']);

        // experiences
        $tpl->SetVariable('lbl_experiences', _t('USERS_USERS_EXPERIENCES'));
        $tpl->SetVariable('experiences',     $personal['experiences']);

        // occupations
        $tpl->SetVariable('lbl_occupations', _t('USERS_USERS_OCCUPATIONS'));
        $tpl->SetVariable('occupations',     $personal['occupations']);

        // interests
        $tpl->SetVariable('lbl_interests', _t('USERS_USERS_INTERESTS'));
        $tpl->SetVariable('interests',     $personal['interests']);

        if (!empty($response)) {
            $tpl->SetBlock('personal/response');
            $tpl->SetVariable('type', $response['type']);
            $tpl->SetVariable('text', $response['text']);
            $tpl->ParseBlock('personal/response');
        }

        $tpl->ParseBlock('personal');
        return $tpl->Get();
    }

    /**
     * Updates user personal
     *
     * @access  public
     * @return  void
     */
    function UpdatePersonal()
    {
        if (!$GLOBALS['app']->Session->Logged()) {
            Jaws_Header::Location(
                $this->gadget->urlMap(
                    'LoginBox',
                    array('referrer'  => bin2hex(Jaws_Utils::getRequestURL(true)))
                )
            );
        }

        $this->gadget->CheckPermission('EditUserPersonal');
        $request =& Jaws_Request::getInstance();
        $post = $request->get(
            array('fname', 'lname', 'gender', 'ssn', 'dob_year', 'dob_month', 'dob_day', 'url', 'signature',
                  'about', 'avatar', 'delete_avatar', 'experiences', 'occupations', 'interests'),
            'post'
        );

        $post['dob'] = null;
        if (!empty($post['dob_year']) && !empty($post['dob_year']) && !empty($post['dob_year'])) {
            $date = $GLOBALS['app']->loadDate();
            $dob  = $date->ToBaseDate($post['dob_year'], $post['dob_month'], $post['dob_day']);
            $post['dob'] = date('Y-m-d H:i:s', $dob['timestamp']);
        }
        // unset unnecessary personal data
        unset($post['dob_day'], $post['dob_month'], $post['dob_year']);

        // validate url
        if (!preg_match('|^\S+://\S+\.\S+.+$|i', $post['url'])) {
            $post['url'] = '';
        }

        $avatar = '';
        if (empty($post['delete_avatar'])) {
            $res = Jaws_Utils::UploadFiles(
                $_FILES,
                Jaws_Utils::upload_tmp_dir(),
                'gif,jpg,jpeg,png'
            );
            if (Jaws_Error::IsError($res)) {
                $GLOBALS['app']->Session->PushResponse(
                    $res->GetMessage(),
                    'Users.Personal.Response',
                    RESPONSE_ERROR,
                    $post
                );

                Jaws_Header::Location($this->gadget->urlMap('Personal'));
            } elseif (!empty($res)) {
                $avatar = $res['avatar'][0]['host_filename'];
            }
        }

        $model  = $GLOBALS['app']->LoadGadget('Users', 'Model', 'Personal');
        $result = $model->UpdatePersonal(
            $GLOBALS['app']->Session->GetAttribute('user'),
            $post['fname'],
            $post['lname'],
            $post['gender'],
            $post['ssn'],
            $post['dob'],
            $post['url'],
            $avatar,
            $post['signature'],
            $post['about'],
            $post['experiences'],
            $post['occupations'],
            $post['interests']
        );
        if (Jaws_Error::IsError($result)) {
            $GLOBALS['app']->Session->PushResponse(
                $result->GetMessage(),
                'Users.Personal.Response',
                RESPONSE_ERROR,
                $post
            );
        } else {
            $GLOBALS['app']->Session->PushResponse(
                _t('USERS_USERS_PERSONALINFO_UPDATED'),
                'Users.Personal.Response'
            );
        }

        Jaws_Header::Location($this->gadget->urlMap('Personal'));
    }

}