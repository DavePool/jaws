<?php
require_once JAWS_PATH . 'gadgets/Phoo/Model.php';
/**
 * Phoo Gadget
 *
 * @category   GadgetModel
 * @package    Phoo
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Raul Murciano <raul@murciano.net>
 * @copyright  2004-2013 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class Phoo_Model_Admin_Photos extends Phoo_Model
{
    /**
     * Update the information of an image
     *
     * @access  public
     * @param   int     $id                 ID of the image
     * @param   string  $title              Title of the image
     * @param   string  $description        Description of the image
     * @param   bool    $allow_comments     True is comments allowed, False is not allowed
     * @param   bool    $published          true for Published, false for Hidden
     * @param   array   $albums
     * @return  mixed   True if entry was updated successfully and Jaws_Error if not
     */
    function UpdateEntry($id, $title, $description, $allow_comments, $published, $albums = null)
    {
        $data = array();
        $data['title'] = $title;
        $data['description'] = $description;
        $data['allow_comments'] = $allow_comments;
        $data['updatetime'] = $GLOBALS['db']->Date();

        $table = Jaws_ORM::getInstance()->table('phoo_image');
        if (is_null($published)) {
            $data['published'] = (bool)$published;
        }
        $result = $table->update($data)->where('id', (int)$id)->exec();
        if (Jaws_Error::IsError($result)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('PHOO_ERROR_CANT_UPDATE_PHOTO'), RESPONSE_ERROR);
            return new Jaws_Error(_t('PHOO_ERROR_CANT_UPDATE_PHOTO'), _t('PHOO_NAME'));
        }

        if ($albums !== null) {
            $this->SetEntryAlbums($id, $albums);
        }
        $GLOBALS['app']->Session->PushLastResponse(_t('PHOO_PHOTO_UPDATED'), RESPONSE_NOTICE);
        return true;
    }


    /**
     * Delete an image
     *
     * @access  public
     * @param   int     $id     ID of the image
     * @return  mixed   True if entry was deleted successfully and Jaws_Error if not
     */
    function DeletePhoto($id)
    {
        $table = Jaws_ORM::getInstance()->table('phoo_image');
        $image = $table->select('filename')->where('id', $id)->fetchRow();
        if (Jaws_Error::IsError($image)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('PHOO_IMPOSSIBLE_DELETE_IMAGE'), RESPONSE_ERROR);
            return new Jaws_Error(_t('PHOO_IMPOSSIBLE_DELETE_IMAGE'), _t('PHOO_NAME'));
        }

        $table->reset();
        $result = $table->delete()->where('id', $id)->exec();
        if (Jaws_Error::IsError($result)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('PHOO_IMPOSSIBLE_DELETE_IMAGE'), RESPONSE_ERROR);
            return new Jaws_Error(_t('PHOO_IMPOSSIBLE_DELETE_IMAGE'), _t('PHOO_NAME'));
        }

        $table = Jaws_ORM::getInstance()->table('phoo_image_album');
        $result = $table->delete()->where('phoo_image_id', $id)->exec();
        if (Jaws_Error::IsError($result)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('PHOO_IMPOSSIBLE_DELETE_IMAGE'), RESPONSE_ERROR);
            return new Jaws_Error(_t('PHOO_IMPOSSIBLE_DELETE_IMAGE'), _t('PHOO_NAME'));
        }

        @unlink(JAWS_DATA . 'phoo/' . $image['filename']);
        @unlink(JAWS_DATA . 'phoo/' . $this->GetMediumPath($image['filename']));
        @unlink(JAWS_DATA . 'phoo/' . $this->GetThumbPath($image['filename']));

        $GLOBALS['app']->Session->PushLastResponse(_t('PHOO_PHOTO_DELETED'), RESPONSE_NOTICE);
        return true;
    }

    /**
     * Add a new entry
     *
     * @access  public
     * @param   string  $user             User who is adding the photo
     * @param   array   $files            info like original name, tmp name and size
     * @param   string  $title            Title of the image
     * @param   string  $description      Description of the image
     * @param   bool    $fromControlPanel Is it called from ControlPanel?
     * @param   array   $album            Array containing the required info about the album
     * @return  mixed   Returns the ID of the new entry and Jaws_Error on error
     */
    function NewEntry($user, $files, $title, $description, $fromControlPanel = true, $album)
    {
        // check if it's really a uploaded file.
        /*if (is_uploaded_file($files['tmp_name'])) {
            $GLOBALS['app']->Session->PushLastResponse(_t('PHOO_ERROR_CANT_UPLOAD_PHOTO'), RESPONSE_ERROR);
            return new Jaws_Error(_t('PHOO_ERROR_CANT_UPLOAD_PHOTO'), _t('PHOO_NAME'));
        }*/

        if (!preg_match("/\.png$|\.jpg$|\.jpeg$|\.gif$/i", $files['name'])) {
            $GLOBALS['app']->Session->PushLastResponse(_t('PHOO_ERROR_CANT_UPLOAD_PHOTO_EXT'), RESPONSE_ERROR);
            return new Jaws_Error(_t('PHOO_ERROR_CANT_UPLOAD_PHOTO_EXT'), _t('PHOO_NAME'));
        }

        // Create directories
        $uploaddir = JAWS_DATA . 'phoo/' . date('Y_m_d') . '/';
        if (!is_dir($uploaddir)) {
            if (!Jaws_Utils::is_writable(JAWS_DATA . 'phoo/')) {
                $GLOBALS['app']->Session->PushLastResponse(_t('PHOO_ERROR_CANT_UPLOAD_PHOTO'), RESPONSE_ERROR);
                return new Jaws_Error(_t('PHOO_ERROR_CANT_UPLOAD_PHOTO'), _t('PHOO_NAME'));
            }

            $new_dirs = array();
            $new_dirs[] = $uploaddir;
            $new_dirs[] = $uploaddir . 'thumb';
            $new_dirs[] = $uploaddir . 'medium';
            foreach ($new_dirs as $new_dir) {
                if (!Jaws_Utils::mkdir($new_dir)) {
                    $GLOBALS['app']->Session->PushLastResponse(_t('PHOO_ERROR_CANT_UPLOAD_PHOTO'), RESPONSE_ERROR);
                    return new Jaws_Error(_t('PHOO_ERROR_CANT_UPLOAD_PHOTO'), _t('PHOO_NAME'));
                }
            }
        }

        $filename = $files['name'];
        if (file_exists($uploaddir.$files['name'])) {
            $filename = time() . '_' . $files['name'];
        }

        $res = Jaws_Utils::UploadFiles($files, $uploaddir, 'jpg,gif,png,jpeg', '', false, !$fromControlPanel);
        if (Jaws_Error::IsError($res)) {
            $GLOBALS['app']->Session->PushLastResponse($res->getMessage(), RESPONSE_ERROR);
            return new Jaws_Error($res->getMessage(), _t('PHOO_NAME'));
        }
        $filename = $res[0][0]['host_filename'];
        $uploadfile = $uploaddir . $filename;

        // Resize Image
        include_once JAWS_PATH . 'include/Jaws/Image.php';
        $objImage = Jaws_Image::factory();
        if (Jaws_Error::IsError($objImage)) {
            return Jaws_Error::raiseError($objImage->getMessage(), _t('PHOO_NAME'));
        }

        $thumbSize  = explode('x', $this->gadget->registry->fetch('thumbsize'));
        $mediumSize = explode('x', $this->gadget->registry->fetch('mediumsize'));

        $objImage->load($uploadfile);
        $objImage->resize($thumbSize[0], $thumbSize[1]);
        $res = $objImage->save($this->GetThumbPath($uploadfile));
        $objImage->free();
        if (Jaws_Error::IsError($res)) {
            // Return an error if image can't be resized
            $GLOBALS['app']->Session->PushLastResponse(_t('PHOO_ERROR_CANT_RESIZE_TO_THUMB'), RESPONSE_ERROR);
            return new Jaws_Error($res->getMessage(), _t('PHOO_NAME'));
        }

        $objImage->load($uploadfile);
        $objImage->resize($mediumSize[0], $mediumSize[1]);
        $res = $objImage->save($this->GetMediumPath($uploadfile));
        $objImage->free();
        if (Jaws_Error::IsError($res)) {
            // Return an error if image can't be resized
            $GLOBALS['app']->Session->PushLastResponse($res->getMessage(), RESPONSE_ERROR);
            return new Jaws_Error(_t('PHOO_ERROR_CANT_RESIZE_TO_MEDIUM'), _t('PHOO_NAME'));
        }

        $data = array();
        $data['user_id']        = $user;
        $data['filename']       = date('Y_m_d').'/'.$filename;
        $data['title']          = $title;
        $data['description']    = $description;

        if ($this->gadget->registry->fetch('allow_comments') === 'true' &&
            $album['allow_comments'])
        {
            $data['allow_comments'] = true;
        } else {
            $data['allow_comments'] = false;
        }

        if ($this->gadget->registry->fetch('published') === 'true' &&
            $this->gadget->GetPermission('ManageAlbums'))
        {
            $data['published'] = true;
        } else {
            $data['published'] = false;
        }

        $jDate = $GLOBALS['app']->loadDate();
        $createtime = $GLOBALS['db']->Date();
        if (function_exists('exif_read_data') &&
            (preg_match("/\.jpg$|\.jpeg$/i", $files['name'])) &&
            ($exifData = @exif_read_data($uploadfile, 1, true))
            && !empty($exifData['IFD0']['DateTime']) && $jDate->ValidDBDate($exifData['IFD0']['DateTime']))
        {
            $aux        = explode(' ', $exifData['IFD0']['DateTime']);
            $auxdate    = str_replace(':', '-', $aux[0]);
            $auxtime    = $aux[1];
            $createtime = $auxdate . ' ' . $auxtime;
        }
        $data['createtime'] = $createtime;

        $table = Jaws_ORM::getInstance()->table('phoo_image');
        $result = $table->insert($data)->exec();
        if (Jaws_Error::IsError($result)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('PHOO_ERROR_CANT_UPLOAD_PHOTO'), RESPONSE_ERROR);
            return new Jaws_Error(_t('PHOO_ERROR_CANT_UPLOAD_PHOTO'), _t('PHOO_NAME'));
        }

        // Lets remove the original if keep_original = false
        if ($this->gadget->registry->fetch('keep_original') == 'false') {
            @unlink(JAWS_DATA . 'phoo/' . $data['filename']);
        }

        // Get last id...
        $GLOBALS['app']->Session->PushLastResponse(_t('PHOO_PHOTO_ADDED'), RESPONSE_NOTICE);
        return $GLOBALS['db']->lastInsertID('phoo_image', 'id');
    }

    /**
     * Add entry to an existing album
     *
     * @access  public
     * @param   int     $id    Entry Id
     * @param   int     $album Album Id
     * @return  mixed   Returns true if entry was added without problems, Jaws_Error if not.
     */
    function AddEntryToAlbum($id, $album)
    {
        $data = array();
        $data['phoo_image_id'] = $id;
        $data['phoo_album_id'] = $album;

        $table = Jaws_ORM::getInstance()->table('phoo_image_album', '', '');
        $result = $table->insert($data)->exec();
        if (Jaws_Error::IsError($result)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('PHOO_ERROR_CANT_ADD_ENTRY_TO_ALBUM'), RESPONSE_ERROR);
            return new Jaws_Error(_t('PHOO_ERROR_CANT_ADD_ENTRY_TO_ALBUM'), _t('PHOO_NAME'));
        }

        return true;
    }

    /**
     * Set entry albums
     *
     * @access  public
     * @param   int     $id     Entry Id
     * @param   array   $albums Array with albums id's
     * @return  mixed   Returns true, or Jaws_Error on error
     */
    function SetEntryAlbums($id, $albums)
    {
        // Remove albums
        $table = Jaws_ORM::getInstance()->table('phoo_image_album');
        // FIXME: Check for error but maybe not since it always returns true, foobar stuff
        $table->delete()->where('phoo_image_id', (int)$id)->exec();

        if (is_array($albums) && !empty($albums)) {
            foreach ($albums as $album) {
                $rs = $this->AddEntryToAlbum($id, $album);
                if (Jaws_Error::IsError($rs)) {
                    return $rs;
                }
            }
        }

        $GLOBALS['app']->Session->PushLastResponse(_t('PHOO_ALBUMS_UPDATED'), RESPONSE_NOTICE);
        return true;
    }
}