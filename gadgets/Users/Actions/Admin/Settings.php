<?php
/**
 * Users Core Gadget Admin
 *
 * @category   GadgetAdmin
 * @package    Users
 */
class Users_Actions_Admin_Settings extends Users_Actions_Admin_Default
{
    /**
     * Builds admin settings UI
     *
     * @access  public
     * @return  string  XHTML form
     */
    function Settings()
    {
        $this->gadget->CheckPermission('ManageSettings');
        $this->AjaxMe('script.js');

        $tpl = $this->gadget->template->loadAdmin('Settings.html');
        $tpl->SetBlock('settings');
        $tpl->SetVariable('menubar', $this->MenuBar('Settings'));

        // authentication method
        $authtype =& Piwi::CreateWidget('Combo', 'authtype');
        foreach ($this->gadget->model->loadAdmin('Settings')->GetAuthTypes() as $method) {
            $authtype->AddOption($method, $method);
        }
        $authtype->SetDefault($this->gadget->registry->fetch('authtype'));
        $authtype->SetEnabled($this->gadget->GetPermission('ManageAuthenticationMethod'));
        $tpl->SetVariable('lbl_authtype', Jaws::t('AUTHTYPE'));
        $tpl->SetVariable('authtype', $authtype->Get());

        $anonRegister =& Piwi::CreateWidget('Combo', 'anon_register');
        $anonRegister->AddOption(Jaws::t('YES'), 'true');
        $anonRegister->AddOption(Jaws::t('NO'), 'false');
        $anonRegister->SetDefault($this->gadget->registry->fetch('anon_register'));
        $tpl->SetVariable('lbl_anon_register', $this::t('PROPERTIES_ANON_REGISTER'));
        $tpl->SetVariable('anon_register', $anonRegister->Get());

        $anonactivate =& Piwi::CreateWidget('Combo', 'anon_activation');
        $anonactivate->AddOption($this::t('PROPERTIES_ACTIVATION_AUTO'), 'auto');
        $anonactivate->AddOption($this::t('PROPERTIES_ACTIVATION_BY_USER'), 'user');
        $anonactivate->AddOption($this::t('PROPERTIES_ACTIVATION_BY_ADMIN'), 'admin');
        $anonactivate->SetDefault($this->gadget->registry->fetch('anon_activation'));
        $tpl->SetVariable('lbl_anon_activation', $this::t('PROPERTIES_ANON_ACTIVATION'));
        $tpl->SetVariable('anon_activation', $anonactivate->Get());

        $anonGroup =& Piwi::CreateWidget('Combo', 'anon_group');
        $anonGroup->SetID('anon_group');
        $anonGroup->AddOption($this::t('GROUPS_NOGROUP'), 0);
        $groups = $this->app->users->GetGroups(null, 'title');
        if (!Jaws_Error::IsError($groups)) {
            foreach ($groups as $group) {
                $anonGroup->AddOption($group['title'], $group['id']);
            }
        }
        $anonGroup->SetDefault($this->gadget->registry->fetch('anon_group'));
        $tpl->SetVariable('lbl_anon_group', $this::t('PROPERTIES_ANON_GROUP'));
        $tpl->SetVariable('anon_group', $anonGroup->Get());

        $passRecovery =& Piwi::CreateWidget('Combo', 'password_recovery');
        $passRecovery->AddOption(Jaws::t('YES'), 'true');
        $passRecovery->AddOption(Jaws::t('NO'), 'false');
        $passRecovery->SetDefault($this->gadget->registry->fetch('password_recovery'));
        $tpl->SetVariable('lbl_password_recovery', $this::t('PROPERTIES_PASS_RECOVERY'));
        $tpl->SetVariable('password_recovery', $passRecovery->Get());

        // reserved users
        $reservedUsers =& Piwi::CreateWidget(
            'TextArea',
            'reserved_users',
            trim($this->gadget->registry->fetch('reserved_users'))
        );
        $reservedUsers->SetRows(8);
        $reservedUsers->setID('reserved_users');
        $tpl->SetVariable('lbl_reserved_users', $this::t('PROPERTIES_RESERVED_USERS'));
        $tpl->SetVariable('reserved_users', $reservedUsers->Get());

        $btnSave =& Piwi::CreateWidget('Button', 'btn_save', Jaws::t('SAVE'), STOCK_SAVE);
        $btnSave->AddEvent(ON_CLICK, "Jaws_Gadget.getInstance('Users').updateSettings();");
        $tpl->SetVariable('btn_save', $btnSave->Get());

        $tpl->ParseBlock('settings');
        return $tpl->Get();
    }

    /**
     * Updates settings
     *
     * @access  public
     * @return  array   Response array (notice or error)
     */
    function UpdateSettings()
    {
        $this->gadget->CheckPermission('ManageSettings');
        $settings = Jaws::getInstance()->request->fetchAll('post');
        $settings['reserved_users'] = implode(
            "\n",
            array_filter(preg_split("/\n|\r|\n\r/", strtolower($settings['reserved_users'])))
        );

        if ($this->gadget->model->loadAdmin('Settings')->UpdateSettings($settings)) {
            return $this->gadget->session->response(
                $this::t('PROPERTIES_UPDATED'),
                RESPONSE_NOTICE
            );
        }

        return $this->gadget->session->response(
            $this::t('PROPERTIES_CANT_UPDATE'),
            RESPONSE_ERROR
        );
    }

}