<?php
/**
 * FeedReader Installer
 *
 * @category    GadgetModel
 * @package     FeedReader
 * @author      Ali Fazelzadeh <afz@php.net>
 * @copyright   2012-2013 Jaws Development Group
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class FeedReader_Installer extends Jaws_Gadget_Installer
{
    /**
     * Installs the gadget
     *
     * @access  public
     * @return  mixed   true on successful installation, Jaws_Error otherwise
     */
    function Install()
    {
        if (!Jaws_Utils::is_writable(JAWS_DATA)) {
            return new Jaws_Error(_t('GLOBAL_ERROR_FAILED_DIRECTORY_UNWRITABLE', JAWS_DATA));
        }

        $new_dir = JAWS_DATA . 'feedcache' . DIRECTORY_SEPARATOR;
        if (!Jaws_Utils::mkdir($new_dir)) {
            return new Jaws_Error(_t('GLOBAL_ERROR_FAILED_CREATING_DIR', $new_dir), _t('FEEDREADER_NAME'));
        }

        $result = $this->installSchema('schema.xml');
        if (Jaws_Error::IsError($result)) {
            return $result;
        }

        // Registry keys
        $this->gadget->AddRegistry('default_feed', '0');

        return true;
    }

    /**
     * Uninstalls the gadget
     *
     * @access  public
     * @return  mixed   true on success, Jaws_Error otherwise
     */
    function Uninstall()
    {
        $result = $GLOBALS['db']->dropTable('feeds');
        if (Jaws_Error::IsError($result)) {
            $gName  = _t('FEEDREADER_NAME');
            $errMsg = _t('GLOBAL_ERROR_GADGET_NOT_UNINSTALLED', $gName);
            $GLOBALS['app']->Session->PushLastResponse($errMsg, RESPONSE_ERROR);
            return new Jaws_Error($errMsg, $gName);
        }

        // registry keys
        $this->gadget->DelRegistry('default_feed');

        return true;
    }

    /**
     * Upgrades the gadget
     *
     * @access  public
     * @param   string  $old    Current version (in registry)
     * @param   string  $new    New version (in the $gadgetInfo file)
     * @return  mixed   true on success, Jaws_Error otherwise
     */
    function Upgrade($old, $new)
    {
        $result = $this->installSchema('schema.xml', '', "0.8.0.xml");
        if (Jaws_Error::IsError($result)) {
            return $result;
        }

        $new_feed_dir = JAWS_DATA. 'feedcache'. DIRECTORY_SEPARATOR;
        $old_feed_dir = JAWS_DATA. 'rsscache'.  DIRECTORY_SEPARATOR;
        if (!Jaws_Utils::mkdir($new_feed_dir)) {
            return new Jaws_Error(_t('GLOBAL_ERROR_FAILED_CREATING_DIR', $new_feed_dir), _t('FEEDREADER_NAME'));
        }

        Jaws_Utils::delete($old_feed_dir);

        // ACL keys
        $GLOBALS['app']->ACL->DeleteKey('/ACL/gadgets/FeedReader/ManageRSSSite');

        return true;
    }

}