<?php
/**
 * Jaws gadget installer class
 *
 * @category   Gadget
 * @package    Core
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2012-2013 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class Jaws_Gadget_Installer
{
    /**
     * Default ACL value of frontend gadget access
     *
     * @var     bool
     * @access  protected
     */
    var $default_acl = true;

    /**
     * Gadget Registry keys
     *
     * @var     array
     * @access  private
     */
    var $_RegKeys = array();

    /**
     * Gadget ACL keys
     *
     * @var     array
     * @access  private
     */
    var $_ACLKeys = array();

    /**
     * Jaws_Gadget object
     *
     * @var     object
     * @access  protected
     */
    var $gadget = null;

    /**
     * constructor
     *
     * @access  public
     * @param   object $gadget Jaws_Gadget object
     * @return  void
     */
    function Jaws_Gadget_Installer($gadget)
    {
        $this->gadget = $gadget;
    }

    /**
     * Loads the gadget model file in question, makes a instance and
     * stores it globally for later use so we do not have duplicates
     * of the same instance around in our code.
     *
     * @access  public
     * @param   string  $type   Model type
     * @return  mixed   Model class object on successful, Jaws_Error otherwise
     */
    function &loadInstaller()
    {
        if (!isset($this->gadget->installer)) {
            $installer_class_name = $this->gadget->name. '_Installer';
            $file = JAWS_PATH. 'gadgets/'. $this->gadget->name. '/Installer.php';

            if (!file_exists($file)) {
                return Jaws_Error::raiseError("File [$file] not exists!", __FUNCTION__);
            }

            include_once($file);
            if (!Jaws::classExists($installer_class_name)) {
                return Jaws_Error::raiseError("Class [$installer_class_name] not exists!", __FUNCTION__);
            }

            $this->gadget->installer = new $installer_class_name($this->gadget);
            $GLOBALS['log']->Log(JAWS_LOG_DEBUG, "Loaded gadget installer: [$installer_class_name]");
        }

        return $this->gadget->installer;
    }

    /**
     * Get all ACLs for the gadet
     *
     * @access  public
     * @return  array   ACLs of the gadget
     */
    function GetACLs()
    {
        $result = array();
        foreach ($this->_ACLKeys as $acl) {
            if (is_array($acl)) {
                $result[] = $acl;
            } else {
                $result[] = array($acl, '', false);
            }
        }

        // Adding common ACL keys
        $result[] = array('default', '', $this->default_acl);
        $result[] = array('default_admin', '', false);
        $result[] = array('default_registry', '', false);
        return $result;
    }

    /**
     * Gets gadgets that depend on a given gadget
     *
     * @access  public
     * @return  mixed   Array of gadgets otherwise Jaws_Error
     */
    function dependOnGadgets()
    {
        $params = array();
        $params['name']  = 'requires';
        $params['value'] = '%,'. $this->gadget->name. ',%';

        $sql = '
            SELECT
                [component]
            FROM [[registry]]
            WHERE
                [key_name] = {name}
              AND
                [key_value] LIKE {value}';

        $requires = $GLOBALS['db']->queryCol($sql, $params);
        return $requires;
    }

    /**
     * Install a gadget
     *
     * @access  public
     * @return  mixed   True if success or Jaws_Error on error
     */
    function InstallGadget($insert = '', $variables = array())
    {
        if (Jaws_Gadget::IsGadgetInstalled($this->gadget->name)) {
            return true;
        }

        $installer = $this->loadInstaller();
        if (Jaws_Error::IsError($installer)) {
            return $installer;
        }

        // all required gadgets, must be installed
        foreach ($this->gadget->_Requires as $req) {
            if (!Jaws_Gadget::IsGadgetInstalled($req)) {
                return Jaws_Error::raiseError(
                    _t('GLOBAL_GI_GADGET_REQUIRES', $req, $this->gadget->name),
                    __FUNCTION__
                );
            }
        }

        // Registry keys
        $requires = ','. implode($this->gadget->_Requires, ','). ',';
        $installer->_RegKeys = array_merge(
            array('version' => $this->gadget->version, 'requires' => $requires),
            $installer->_RegKeys
        );
        $this->gadget->registry->insert($installer->_RegKeys, $this->gadget->name);

        // load gadget install method
        $result = $installer->Install($insert, $variables);
        if (Jaws_Error::IsError($result)) {
            // removeing gadget registry keys
            $GLOBALS['app']->Registry->delete($this->gadget->name);
            return $result;
        }

        // ACL keys
        $this->gadget->acl->insert($installer->GetACLs(), $this->gadget->name);

        // adding gadget to installed gadgets list
        $installed_gadgets = $GLOBALS['app']->Registry->fetch('gadgets_installed_items');
        $installed_gadgets.= $this->gadget->name. ',';
        $GLOBALS['app']->Registry->update('gadgets_installed_items', $installed_gadgets);

        // adding gadget to autoload gadgets list
        if (file_exists(JAWS_PATH . "gadgets/{$this->gadget->name}/Autoload.php")) {
            $autoload_gadgets = $GLOBALS['app']->Registry->fetch('gadgets_autoload_items');
            $autoload_gadgets.= $this->gadget->name. ',';
            $GLOBALS['app']->Registry->update('gadgets_autoload_items', $autoload_gadgets);
        }

        // end install gadget event
        $res = $GLOBALS['app']->Listener->Shout('InstallGadget', $this->gadget->name);
        if (Jaws_Error::IsError($res)) {
            return $res;
        }

        return true;
    }

    /**
     * Uninstall a gadget
     * Does a complete uninstall to the gadget, removing acl keys, registry keys, tables, data, etc..
     *
     * @access  public
     * @return  mixed    True if success or Jaws_Error on error
     */
    function UninstallGadget()
    {
        if (!Jaws_Gadget::IsGadgetInstalled($this->gadget->name)) {
            return Jaws_Error::raiseError(
                "gadget [{$this->gadget->name}] not installed",
                __FUNCTION__
            );
        }

        if ($this->gadget->registry->fetch('main_gadget', 'Settings') == $this->gadget->name) {
            return Jaws_Error::raiseError(
                "you can't uninstall main gadget",
                __FUNCTION__
            );
        }

        if ($this->gadget->_IsCore) {
            return Jaws_Error::raiseError(
                "you can't uninstall core gadgets",
                __FUNCTION__
            );
        }

        $dependent_gadgets = $this->dependOnGadgets();
        if (!empty($dependent_gadgets)) {
            if (Jaws_Error::IsError($dependent_gadgets)) {
                return $dependent_gadgets;
            }

            $dependent_gadgets = implode(', ', $dependent_gadgets);
            return Jaws_Error::raiseError(
                "you can't uninstall this gadget, because $dependent_gadgets gadget(s) is dependent on it",
                __FUNCTION__
            );
        }

        $installer = $this->loadInstaller();
        if (Jaws_Error::IsError($installer)) {
            return $installer;
        }

        $result = $installer->Uninstall();
        if (Jaws_Error::IsError($result)) {
            return $result;
        }

        // removeing gadget from installed gadgets list
        $installed_gadgets = $GLOBALS['app']->Registry->fetch('gadgets_installed_items');
        $installed_gadgets = str_replace(",{$this->gadget->name},", ',', $installed_gadgets);
        $GLOBALS['app']->Registry->update('gadgets_installed_items', $installed_gadgets);

        // removeing gadget from autoload gadgets list
        $autoload_gadgets = $GLOBALS['app']->Registry->fetch('gadgets_autoload_items');
        $autoload_gadgets = str_replace(",{$this->gadget->name},", ',', $autoload_gadgets);
        $GLOBALS['app']->Registry->update('gadgets_autoload_items', $autoload_gadgets);

        // removeing gadget listeners
        $GLOBALS['app']->Listener->DeleteListener($this->gadget->name);
        // removeing gadget ACL keys
        $GLOBALS['app']->ACL->delete($this->gadget->name);
        // removeing gadget registry keys
        $GLOBALS['app']->Registry->delete($this->gadget->name);

        // end uninstall gadget event
        $result = $GLOBALS['app']->Listener->Shout('UninstallGadget', $this->gadget->name);
        if (Jaws_Error::IsError($result)) {
            return $result;
        }

        return true;
    }

    /**
     * Upgrade a gadget
     * Does an update to the gadget, if the update of the gadget is ok then the version
     * key (in registry) will be updated
     *
     * @access  public
     * @return  mixed    True if success or Jaws_Error on error
     */
    function UpgradeGadget()
    {
        $oldVersion = $this->gadget->registry->fetch('version', $this->gadget->name);
        $newVersion = $this->gadget->version;
        if (version_compare($oldVersion, $newVersion, ">=")) {
            return true;
        }

        $installer = $this->loadInstaller();
        if (Jaws_Error::IsError($installer)) {
            return $installer;
        }

        $result = $installer->Upgrade($oldVersion, $newVersion);
        if (Jaws_Error::IsError($result)) {
            return $result;
        }

        if (is_string($result)) {
            // set return the new version number
            $this->gadget->registry->update('version', $result);
        } else {
            // set the latest version number
            $this->gadget->registry->update('version', $newVersion);
        }

        // autoload feature
        $autoload_gadgets = explode(',', $GLOBALS['app']->Registry->fetch('gadgets_autoload_items'));
        $autoload_gadgets = array_filter(array_map('trim', $autoload_gadgets));
        if (file_exists(JAWS_PATH. 'gadgets/'. $this->gadget->name. '/Autoload.php')) {
            if (!in_array($this->gadget->name, $autoload_gadgets)) {
                array_push($autoload_gadgets, $this->gadget->name);
                $GLOBALS['app']->Registry->update('gadgets_autoload_items', implode(',', $autoload_gadgets));
            }
        } else {
            if (false !== $indx = array_search($this->gadget->name, $autoload_gadgets)) {
                unset($autoload_gadgets[$indx]);
                $GLOBALS['app']->Registry->update('gadgets_autoload_items', implode(',', $autoload_gadgets));
            }
        }

        // end upgrade gadget event
        $result = $GLOBALS['app']->Listener->Shout('UpgradeGadget', $this->gadget->name);
        if (Jaws_Error::IsError($result)) {
            return $result;
        }

        return true;
    }

    /**
     * Enable a gadget
     *
     * @access  public
     * @return  mixed    True if success or Jaws_Error on error
     */
    function EnableGadget()
    {
        if (!Jaws_Gadget::IsGadgetInstalled($this->gadget->name)) {
            return Jaws_Error::raiseError(
                "gadget [{$this->gadget->name}] not installed",
                __FUNCTION__
            );
        }

        // all required gadgets, must be enabled
        foreach ($this->gadget->_Requires as $req) {
            if (!Jaws_Gadget::IsGadgetEnabled($req)) {
                return Jaws_Error::raiseError(
                    _t('GLOBAL_GI_GADGET_REQUIRES', $req, $this->gadget->name),
                    __FUNCTION__
                );
            }
        }

        // removing gadget from disabled gadgets list
        $disabled_gadgets = $GLOBALS['app']->Registry->fetch('gadgets_disabled_items');
        $disabled_gadgets = str_replace(",{$this->gadget->name},", ',', $disabled_gadgets);
        $GLOBALS['app']->Registry->update('gadgets_disabled_items', $disabled_gadgets);

        // end disable gadget event
        $res = $GLOBALS['app']->Listener->Shout('EnableGadget', $this->gadget->name);
        if (Jaws_Error::IsError($res)) {
            return $res;
        }

        return true;
    }

    /**
     * Disable a gadget
     *
     * @access  public
     * @return  mixed    True if success or Jaws_Error on error
     */
    function DisableGadget()
    {
        if (!Jaws_Gadget::IsGadgetInstalled($this->gadget->name)) {
            return Jaws_Error::raiseError(
                "gadget [{$this->gadget->name}] not installed",
                __FUNCTION__
            );
        }

        if ($this->gadget->registry->fetch('main_gadget', 'Settings') == $this->gadget->name) {
            return Jaws_Error::raiseError(
                "you can't disable main gadget",
                __FUNCTION__
            );
        }

        if ($this->gadget->_IsCore) {
            return Jaws_Error::raiseError(
                "you can't disable core gadgets",
                __FUNCTION__
            );
        }

        // check depend on gadgets status
        $dependent_gadgets = $this->dependOnGadgets();
        if (Jaws_Error::IsError($dependent_gadgets)) {
            return $dependent_gadgets;
        }
        foreach ($dependent_gadgets as $idx => $gadget) {
            if (!Jaws_Gadget::IsGadgetEnabled($gadget)) {
                $dependent_gadgets[$idx] = null;
            }
        }
        $dependent_gadgets = implode(', ', array_filter($dependent_gadgets));
        if (!empty($dependent_gadgets)) {
            return Jaws_Error::raiseError(
                "you can't disable this gadget, because $dependent_gadgets gadget(s) is dependent on it",
                __FUNCTION__
            );
        }

        // adding gadget to disabled gadgets list
        $disabled_gadgets = $GLOBALS['app']->Registry->fetch('gadgets_disabled_items');
        $disabled_gadgets.= $this->gadget->name. ',';
        $GLOBALS['app']->Registry->update('gadgets_disabled_items', $disabled_gadgets);

        // end disable gadget event
        $res = $GLOBALS['app']->Listener->Shout('DisableGadget', $this->gadget->name);
        if (Jaws_Error::IsError($res)) {
            return $res;
        }

        return true;
    }

    /**
     * Returns true or false if the gadget if the gadget is running in the required Jaws.
     *
     * If gadget doesn't have any required Jaws version to run it will return true
     *
     * @access  public
     * @return  bool    True or false, depends of the jaws version
     */
    function CanRunInCoreVersion()
    {
        if (self::IsGadgetInstalled($this->gadget->name)) {
            $coreVersion     = $GLOBALS['app']->Registry->fetch('version');
            $requiredVersion = $this->gadget->GetRequiredJawsVersion();

            if ($requiredVersion == $coreVersion) {
                return true;
            }

            if (version_compare($coreVersion, $requiredVersion, '>')) {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * @access  public
     * @return  bool    True on success and Jaws_Error on failure
     */
    function InstallSchema($main_schema, $variables = array(), $base_schema = false, $data = false, $create = true, $debug = false)
    {
        $main_file = $main_schema;
        if (!preg_match('@\\\\|/@', $main_schema)) {
            $main_file = JAWS_PATH. "gadgets/{$this->gadget->name}/schema/$main_schema";
        }
        if (!file_exists($main_file)) {
            return Jaws_Error::raiseError(
                _t('GLOBAL_ERROR_SQLFILE_NOT_EXISTS', $main_schema),
                __FUNCTION__,
                JAWS_ERROR_ERROR,
                1
            );
        }

        $base_file = false;
        if (!empty($base_schema)) {
                $base_file = $base_schema;
            if (!preg_match('@\\\\|/@', $base_schema)) {
                $base_file = JAWS_PATH. "gadgets/{$this->gadget->name}/schema/$base_schema";
            }
            if (!file_exists($base_file)) {
                return Jaws_Error::raiseError(
                    _t('GLOBAL_ERROR_SQLFILE_NOT_EXISTS', $base_schema),
                    __FUNCTION__,
                    JAWS_ERROR_ERROR,
                    1
                );
            }
        }

        $result = $GLOBALS['db']->installSchema($main_file, $variables, $base_file, $data, $create, $debug);
        if (Jaws_Error::IsError($result)) {
            return Jaws_Error::raiseError(
                _t('GLOBAL_ERROR_FAILED_QUERY_FILE',$main_schema . (empty($base_schema)? '': "/$base_schema")),
                __FUNCTION__,
                JAWS_ERROR_ERROR,
                1
            );
        }

        return true;
    }

    /**
     * Install the gadget
     * Gadgets should override this method only if they need to perform actions to install
     *
     * @access  public
     * @return  bool    True on successfull install and Jaws_Error on failure
     */
    function Install()
    {
        return true;
    }

    /**
     * Uninstall the gadget
     * Gadgets should override this method only if they need to perform actions to uninstall
     *
     * @access  public
     * @return  mixed    True on a successful uninstall and Jaws_Error otherwise
     */
    function Uninstall()
    {
        return true;
    }

    /**
     * Upgrade the gadget
     * Gadgets should override this method only if they need to perform actions to upgrade
     *
     * @access  public
     * @return  bool    True on success and Jaws_Error on failure
     */
    function Upgrade()
    {
        return true;
    }

}