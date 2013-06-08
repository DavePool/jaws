<?php
/**
 * Blocks Installer
 *
 * @category    GadgetModel
 * @package     Blocks
 * @author      Ali Fazelzadeh <afz@php.net>
 * @copyright   2012-2013 Jaws Development Group
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class Blocks_Installer extends Jaws_Gadget_Installer
{
    /**
     * Gadget ACLs
     *
     * @var     array
     * @access  private
     */
    var $_ACLs = array(
        'AddBlock',
        'EditBlock',
        'DeleteBlock',
    );

    /**
     * Install the gadget
     *
     * @access  public
     * @return  mixed    Returns True if installation success or Jaws_Error on any error found
     */
    function Install()
    {
        $result = $this->installSchema('schema.xml');
        if (Jaws_Error::IsError($result)) {
            return $result;
        }

        return true;
    }

    /**
     * Uninstall the gadget
     *
     * @access  public
     * @return  mixed   True on if successful or Jaws_Error otherwise
     */
    function Uninstall()
    {
        $result = $GLOBALS['db']->dropTable('blocks');
        if (Jaws_Error::IsError($result)) {
            $gName  = _t('BLOCKS_NAME');
            $errMsg = _t('GLOBAL_ERROR_GADGET_NOT_UNINSTALLED', $gName);
            $GLOBALS['app']->Session->PushLastResponse($errMsg, RESPONSE_ERROR);
            return new Jaws_Error($errMsg, $gName);
        }

        return true;
    }

    /**
     * Upgrades the gadget
     *
     * @access  public
     * @param   string  $old    Current version (in registry)
     * @param   string  $new    New version (in the $gadgetInfo file)
     * @return  bool    True on Success or Jaws_Error on Failure
     */
    function Upgrade($old, $new)
    {
        return true;
    }

}