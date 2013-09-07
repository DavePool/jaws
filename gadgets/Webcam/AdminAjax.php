<?php
/**
 * Webcam AJAX API
 *
 * @category   Ajax
 * @package    Webcam
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @copyright  2005-2013 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class Webcam_AdminAjax extends Jaws_Gadget_HTML
{
    /**
     * Gets information of a webcam
     *
     * @access  public
     * @return  mixed   Array of webcam information or false on failure
     */
    function GetWebcam()
    {
        @list($id) = jaws()->request->fetchAll('post');
        $model = $GLOBALS['app']->LoadGadget('Webcam', 'Model', 'Webcam');
        $webcamInfo = $model->GetWebcam($id);
        if (Jaws_Error::IsError($webcamInfo)) {
            return false; //we need to handle errors on ajax
        }

        return $webcamInfo;
    }

    /**
     * Adds a new webcam
     *
     * @access  public
     * @return  array   Response array (notice or error)
     */
    function NewWebcam()
    {
        $this->gadget->CheckPermission('AddWebcam');
        @list($title, $url, $refresh) = jaws()->request->fetchAll('post');
        $model = $GLOBALS['app']->LoadGadget('Webcam', 'AdminModel', 'Webcam');
        $model->NewWebcam($title, $url, $refresh);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Updates webcam information
     *
     * @access  public
     * @return  array   Response array (notice or error)
     */
    function UpdateWebcam()
    {
        $this->gadget->CheckPermission('EditWebcam');
        @list($id, $title, $url, $refresh) = jaws()->request->fetchAll('post');
        $model = $GLOBALS['app']->LoadGadget('Webcam', 'AdminModel', 'Webcam');
        $model->UpdateWebcam($id, $title, $url, $refresh);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Deletes the webcam
     *
     * @access  public
     * @return  array   Response array (notice or error)
     */
    function DeleteWebcam()
    {
        $this->gadget->CheckPermission('DeleteWebcam');
        @list($id) = jaws()->request->fetchAll('post');
        $model = $GLOBALS['app']->LoadGadget('Webcam', 'AdminModel', 'Webcam');
        $model->DeleteWebcam($id);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Updates properties
     *
     * @access  public
     * @return  array   Response array (notice or error)
     */
    function UpdateProperties()
    {
        $this->gadget->CheckPermission('UpdateProperties');
        @list($limit) = jaws()->request->fetchAll('post');
        $model = $GLOBALS['app']->LoadGadget('Webcam', 'AdminModel', 'Properties');
        $model->UpdateProperties($limit);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Callback to display short URLs
     *
     * @access  private
     * @return  string  Short URL
     */
    function ShowShortURL()
    {
        @list($url) = jaws()->request->fetchAll('post');
        if (strlen($url) > 40) {
            return "<a title=\"{$url}\" href=\"{$url}\">" . substr($url, 0, 40) . "...</a>";
        }

        return "<a title=\"{$url}\" href=\"{$url}\">".$url."</a>";
    }

    /**
     * Gets webcams
     *
     * @access  public
     * @return  array   List of webcams
     */
    function GetData()
    {
        @list($limit) = jaws()->request->fetchAll('post');
        $gadget = $GLOBALS['app']->LoadGadget('Webcam', 'AdminHTML', 'Webcam');
        return $gadget->GetWebcams($limit);
    }

}
