<?php
/**
 * Emblems AJAX API
 *
 * @category   Ajax
 * @package    Emblems
 * @author     Amir Mohammad Saied <amirsaied@gmail.com>
 * @author     Mohsen Khahani <mkhahani@gmail.com>
 * @copyright  2005-2013 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class Emblems_AdminAjax extends Jaws_Gadget_HTML
{
    /**
     * Updates the emblem
     *
     * @access  public
     * @return  array   Response array (notice or error)
     */
    function UpdateEmblem()
    {
        $this->gadget->CheckPermission('ManageEmblems');
        @list($id, $data) = jaws()->request->fetch(array('0', '1:array'), 'post');
        $model = $GLOBALS['app']->LoadGadget('Emblems', 'AdminModel', 'Emblems');
        $res = $model->UpdateEmblem($id, $data);
        if (Jaws_Error::IsError($res)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
            return new Jaws_Error($res->getMessage(), 'SQL');
        }

        $GLOBALS['app']->Session->PushLastResponse(_t('EMBLEMS_UPDATED'), RESPONSE_NOTICE);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Deletes the emblem
     *
     * @access  public
     * @return  array   Response array (notice or error)
     */
    function DeleteEmblem()
    {
        $this->gadget->CheckPermission('ManageEmblems');
        @list($id) = jaws()->request->fetchAll('post');
        $model = $GLOBALS['app']->LoadGadget('Emblems', 'Model', 'Emblems');
        $emblem = $model->GetEmblem($id);

        $model = $GLOBALS['app']->LoadGadget('Emblems', 'AdminModel', 'Emblems');
        $res = $model->DeleteEmblem($id);
        if (Jaws_Error::IsError($res)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
            return new Jaws_Error($res->getMessage(), 'SQL');
        }

        // delete the file
        if (!empty($emblem['image'])) {
            Jaws_Utils::Delete(JAWS_DATA . 'emblems/' . $emblem['image']);
        }

        $GLOBALS['app']->Session->PushLastResponse(_t('EMBLEMS_DELETED'), RESPONSE_NOTICE);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Fetches a limited array of emblems
     *
     * @access  public
     * @return  array   An array of emblems
     */
    function GetData()
    {
        @list($limit) = jaws()->request->fetchAll('post');
        $gadget = $GLOBALS['app']->LoadGadget('Emblems', 'AdminHTML', 'Emblems');
        return $gadget->GetEmblems($limit);
    }
}
