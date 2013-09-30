<?php
/**
 * Forums Gadget
 *
 * @category    Gadget
 * @package     Forums
 * @author      Ali Fazelzadeh <afz@php.net>
 * @copyright   2012-2013 Jaws Development Group
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class Forums_Actions_Attachment extends Forums_HTML
{
    /**
     * Download post attachment
     *
     * @access  public
     * @return  string   Requested file content or HTML error page
     */
    function Attachment()
    {
        require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
        $rqst = jaws()->request->fetch(array('fid', 'tid', 'pid', 'attach'), 'get');

        $pModel = $GLOBALS['app']->LoadGadget('Forums', 'Model', 'Posts');
        $post = $pModel->GetPost($rqst['pid'], $rqst['tid'], $rqst['fid']);
        if (Jaws_Error::IsError($post)) {
            $this->SetActionMode('Attachment', 'normal', 'standalone');
            return Jaws_HTTPError::Get(500);
        }
        $aModel = $this->gadget->load('Model')->load('Model', 'Attachments');
        $attachment = $aModel->GetAttachmentInfo($rqst['attach']);
        if (Jaws_Error::IsError($attachment)) {
            $this->SetActionMode('Attachment', 'normal', 'standalone');
            return Jaws_HTTPError::Get(500);
        }

        if (!empty($attachment)) {
            $filepath = JAWS_DATA. 'forums/'. $attachment['filename'];
            if (file_exists($filepath)) {
                // increase download hits
                $result = $aModel->HitAttachmentDownload($rqst['attach']);
                if (Jaws_Error::IsError($result)) {
                    // do nothing
                }

                if (Jaws_Utils::Download($filepath, $attachment['title'])) {
                    return;
                }

                $this->SetActionMode('Attachment', 'normal', 'standalone');
                return Jaws_HTTPError::Get(500);
            }
        }

        $this->SetActionMode('Attachment', 'normal', 'standalone');
        return Jaws_HTTPError::Get(404);
    }

}