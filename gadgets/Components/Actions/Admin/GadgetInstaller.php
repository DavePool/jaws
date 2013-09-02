<?php
/**
 * Components Gadget Admin
 *
 * @category    GadgetAdmin
 * @package     Components
 * @author      Ali Fazelzadeh <afz@php.net>
 * @author      Mohsen Khahani <mkhahani@gmail.com>
 * @copyright   2013 Jaws Development Group
 * @license     http://www.gnu.org/copyleft/lesser.html
 */
class Components_Actions_Admin_GadgetInstaller extends Jaws_Gadget_HTML
{
    /**
     * Installs requested gadget
     *
     * @access  public
     * @param   string  $gadget  Gadget name
     * @return  void
     */
    function InstallGadget($gadget = '')
    {
        $redirect = false;
        $this->gadget->CheckPermission('ManageGadgets');
        if (empty($gadget)) {
            $redirect = true;
            $gadget = jaws()->request->get('comp', 'get');
        }

        $objGadget = $GLOBALS['app']->LoadGadget($gadget, 'Info');
        if (Jaws_Error::IsError($objGadget)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('COMPONENTS_GADGETS_ENABLE_FAILURE', $gadget), RESPONSE_ERROR);
        } else {
            $installer = $objGadget->load('Installer');
            $return = $installer->InstallGadget();
            if (Jaws_Error::IsError($return)) {
                $GLOBALS['app']->Session->PushLastResponse($return->GetMessage(), RESPONSE_ERROR);
            } else {
                $GLOBALS['app']->Session->PushLastResponse(_t('COMPONENTS_GADGETS_ENABLE_OK', $objGadget->title), RESPONSE_NOTICE);
            }
        }

        if ($redirect) {
            Jaws_Header::Location(BASE_SCRIPT);
        }
    }

    /**
     * Upgrades requested gadget
     *
     * @access  public
     * @param   string  $gadget  Gadget name
     * @return  void
     */
    function UpgradeGadget($gadget = '')
    {
        $redirect = false;
        $this->gadget->CheckPermission('ManageGadgets');
        if (empty($gadget)) {
            $redirect = true;
            $gadget = jaws()->request->get('comp', 'get');
        }

        if (!Jaws_Gadget::IsGadgetUpdated($gadget)) {
            $objGadget = $GLOBALS['app']->LoadGadget($gadget, 'Info');
            $installer = $objGadget->load('Installer');
            $return = $installer->UpgradeGadget();
            if (Jaws_Error::IsError($return)) {
                $GLOBALS['app']->Session->PushLastResponse($return->GetMessage(), RESPONSE_ERROR);
            } else {
                $GLOBALS['app']->Session->PushLastResponse(_t('COMPONENTS_GADGETS_UPDATE_OK', $gadget), RESPONSE_NOTICE);
            }
        } else {
            $GLOBALS['app']->Session->PushLastResponse(_t('COMPONENTS_GADGETS_UPDATE_NO_NEED', $gadget), RESPONSE_ERROR);
        }

        if ($redirect) {
            Jaws_Header::Location(BASE_SCRIPT);
        }
    }

    /**
     * Uninstalls requested gadget
     *
     * @access  public
     * @param   string  $gadget  Gadget name
     * @return  void
     */
    function UninstallGadget($gadget = '')
    {
        $redirect = false;
        $this->gadget->CheckPermission('ManageGadgets');
        if (empty($gadget)) {
            $redirect = true;
            $gadget = jaws()->request->get('comp', 'get');
        }

        $objGadget = $GLOBALS['app']->LoadGadget($gadget, 'Info');
        if (Jaws_Error::IsError($objGadget)) {
            $GLOBALS['app']->Session->PushLastResponse($objGadget->GetMessage(), RESPONSE_ERROR);
        } else {
            $installer = $objGadget->load('Installer');
            $return = $installer->UninstallGadget();
            if (Jaws_Error::IsError($return)) {
                $GLOBALS['app']->Session->PushLastResponse($return->GetMessage(), RESPONSE_ERROR);
            } else {
                $GLOBALS['app']->Session->PushLastResponse(_t('COMPONENTS_GADGETS_DISABLE_OK', $objGadget->title), RESPONSE_NOTICE);
            }
        }

        if ($redirect) {
            Jaws_Header::Location(BASE_SCRIPT);
        }
    }

    /**
     * Enables requested gadget
     *
     * @access  public
     * @param   string  $gadget  Gadget name
     * @return  void
     */
    function EnableGadget($gadget = '')
    {
        $redirect = false;
        $this->gadget->CheckPermission('ManageGadgets');
        if (empty($gadget)) {
            $redirect = true;
            $gadget = jaws()->request->get('comp', 'get');
        }

        $objGadget = $GLOBALS['app']->LoadGadget($gadget, 'Info');
        if (Jaws_Error::IsError($objGadget)) {
            $GLOBALS['app']->Session->PushLastResponse($objGadget->GetMessage(), RESPONSE_ERROR);
        } else {
            $installer = $objGadget->load('Installer');
            $return = $installer->EnableGadget();
            if (Jaws_Error::IsError($return)) {
                $GLOBALS['app']->Session->PushLastResponse($return->GetMessage(), RESPONSE_ERROR);
            } else {
                $GLOBALS['app']->Session->PushLastResponse(_t('COMPONENTS_GADGETS_ENABLE_OK', $objGadget->title), RESPONSE_NOTICE);
            }
        }

        if ($redirect) {
            Jaws_Header::Location(BASE_SCRIPT);
        }
    }

    /**
     * Disables requested gadget
     *
     * @access  public
     * @param   string  $gadget  Gadget name
     * @return  void
     */
    function DisableGadget($gadget = '')
    {
        $redirect = false;
        $this->gadget->CheckPermission('ManageGadgets');
        if (empty($gadget)) {
            $redirect = true;
            $gadget = jaws()->request->get('comp', 'get');
        }

        $objGadget = $GLOBALS['app']->LoadGadget($gadget, 'Info');
        if (Jaws_Error::IsError($objGadget)) {
            $GLOBALS['app']->Session->PushLastResponse($objGadget->GetMessage(), RESPONSE_ERROR);
        } else {
            $installer = $objGadget->load('Installer');
            $return = $installer->DisableGadget();
            if (Jaws_Error::IsError($return)) {
                $GLOBALS['app']->Session->PushLastResponse($return->GetMessage(), RESPONSE_ERROR);
            } else {
                $GLOBALS['app']->Session->PushLastResponse(_t('COMPONENTS_GADGETS_DISABLE_OK', $objGadget->title), RESPONSE_NOTICE);
            }
        }

        if ($redirect) {
            Jaws_Header::Location(BASE_SCRIPT);
        }
    }

}