<?php
/**
 * Phoo AJAX API
 *
 * @category   Ajax
 * @package    Phoo
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @copyright  2005-2013 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class Phoo_AdminAjax extends Jaws_Gadget_Action
{
    /**
     * Import an image located in 'import' folder
     *
     * @access  public
     * @return  array   Response array (notice or error)
     */
    function ImportImage()
    {
        $this->gadget->CheckPermission('Import');
        @list($image, $name, $album) = jaws()->request->fetchAll('post');
        $file = array();
        $file['tmp_name'] = JAWS_DATA . 'phoo/import/' . $image;
        $file['name'] = $image;
        $file['size'] = @filesize($file['tmp_name']);

        $aModel = $this->gadget->loadModel('Albums');
        $pModel = $this->gadget->loadAdminModel('Photos');
        $album_data = $aModel->getAlbumInfo($album);
        $id = $pModel->NewEntry($GLOBALS['app']->Session->GetAttribute('user'),
                                $file,
                                $name,
                                '',
                                false,
                                $album_data);
        $res = $pModel->AddEntryToAlbum($id, $album);
    }

    /**
     * Update album photo information
     *
     * @access  public
     * @return  array   Response array (notice or error)
     */
    function UpdatePhoto()
    {
        @list($id, $title, $desc, $allow_comments, $published, $albums) = jaws()->request->fetchAll('post');
        $albums = jaws()->request->fetch('5:array', 'post');
        $desc = jaws()->request->fetch(2, 'post', false);
        if (!$this->gadget->GetPermission('ManageAlbums')) {
            $albums    = null;
            $published = null;
        }

        $model = $this->gadget->loadAdminModel('Photos');
        $model->UpdateEntry($id, $title, $desc, $allow_comments, $published, $albums);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Add new group
     *
     * @access  public
     * @return  array   Response array (notice or error)
     */
    function AddGroup()
    {
        $rqst = jaws()->request->fetch(array('name', 'description'));
        $rqst['[description]'] = $rqst['description'];
        unset($rqst['description']);
        $model = $this->gadget->loadAdminModel('Groups');
        $res = $model->AddGroup($rqst);

        if (Jaws_Error::isError($res)) {
            $GLOBALS['app']->Session->PushLastResponse($res->getMessage(), RESPONSE_ERROR);
        } else {
            $response =  array();
            $response['id']      = $res;
            $response['message'] = _t('PHOO_GROUPS_GROUP_CREATED');

            $GLOBALS['app']->Session->PushLastResponse($response, RESPONSE_NOTICE);
        }

        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Edit a group
     *
     * @access  public
     * @return  array   Response array (notice or error)
     */
    function EditGroup()
    {
        $rqst = jaws()->request->fetch(array('name', 'description'));
        $gid  = (int) jaws()->request->fetch('id');
        $rqst['[description]'] = $rqst['description'];
        unset($rqst['description']);
        $model = $this->gadget->loadAdminModel('Groups');
        $res = $model->EditGroup($gid, $rqst);

        if (Jaws_Error::isError($res)) {
            $GLOBALS['app']->Session->PushLastResponse($res->getMessage(), RESPONSE_ERROR);
        } else {
            $GLOBALS['app']->Session->PushLastResponse(_t('PHOO_GROUPS_GROUP_UPDATED'), RESPONSE_NOTICE);
        }

        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Delete a group
     *
     * @access  public
     * @return  array   Response array (notice or error)
     */
    function DeleteGroup()
    {
        $gid  = (int) jaws()->request->fetch('id');
        $model = $this->gadget->loadAdminModel('Groups');
        $res = $model->DeleteGroup($gid);

        if (Jaws_Error::isError($res)) {
            $GLOBALS['app']->Session->PushLastResponse($res->getMessage(), RESPONSE_ERROR);
        } else {
            $GLOBALS['app']->Session->PushLastResponse(_t('PHOO_GROUPS_GROUP_DELETED'), RESPONSE_NOTICE);
        }

        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Gets data of a group
     *
     * @access  public
     * @return  mixed   Group data array or False on error
     */
    function GetGroup()
    {
        $gid = jaws()->request->fetch('gid');
        $model = $this->gadget->loadModel('Groups');
        $group = $model->GetGroup($gid);
        if (Jaws_Error::IsError($group)) {
            return false; //we need to handle errors on ajax
        }

        return $group;
    }

    function GetAlbums()
    {
        $gid = jaws()->request->fetch('gid');
        $aModel = $this->gadget->loadModel('Albums');
        $albums = $aModel->GetAlbums('createtime', 'ASC', $gid);
        $free_photos[] = array('id'         => 0,
            'name'       => _t('PHOO_WITHOUT_ALBUM'),
            'createtime' => date('Y-m-d H:i:s'),
            'howmany'    => 0);
        if (Jaws_Error::IsError($albums) || !is_array($albums)) {
            $albums = $free_photos;
        } else {
            $albums = array_merge($free_photos, $albums);
        }
        return $albums;
    }
}