<?php
/**
 * PrivateMessage Gadget
 *
 * @category    Gadget
 * @package     PrivateMessage
 * @author      Mojtaba Ebrahimi <ebrahimi@zehneziba.ir>
 * @copyright   2013 Jaws Development Group
 * @license     http://www.gnu.org/copyleft/lesser.html
 */
class PrivateMessage_Actions_Attachment extends Jaws_Gadget_HTML
{
    /**
     * Download message attachment
     *
     * @access  public
     * @return  string   Requested file content or HTML error page
     */
    function Attachment()
    {
        if (!$GLOBALS['app']->Session->Logged()) {
            require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
            return Jaws_HTTPError::Get(403);
        }

        require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
        $rqst = jaws()->request->fetch(array('uid', 'mid', 'aid'), 'get');
        $user = $GLOBALS['app']->Session->GetAttribute('user');

        $mModel = $GLOBALS['app']->LoadGadget('PrivateMessage', 'Model', 'Message');
        $aModel = $GLOBALS['app']->LoadGadget('PrivateMessage', 'Model', 'Attachment');
        $message = $mModel->GetMessage($rqst['mid'], false, false);
        if (Jaws_Error::IsError($message)) {
            return Jaws_HTTPError::Get(500);
        }

        // Check permissions
        $messageRecipients = $mModel->GetMessageRecipients($rqst['mid']);
        if ($message['user'] != $rqst['uid'] || ($message['user'] != $user && !in_array($user, $messageRecipients)
                && !in_array($rqst['uid'], $messageRecipients))
        ) {
            require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
            return Jaws_HTTPError::Get(403);
        }

        $attachment = $aModel->GetMessageAttachment($rqst['aid']);
        if (!empty($attachment) && ($attachment['message'] == $rqst['mid'])) {
            $filepath = JAWS_DATA . 'pm' . DIRECTORY_SEPARATOR . $rqst['uid'] . DIRECTORY_SEPARATOR .
                $attachment['filename'];
            if (file_exists($filepath)) {
                if (Jaws_Utils::Download($filepath, $attachment['title'], $attachment['filetype'])) {
                    return;
                }

                return Jaws_HTTPError::Get(500);
            }
        }

        $this->SetActionMode('Attachment', 'normal', 'standalone');
        return Jaws_HTTPError::Get(404);
    }

    /**
     * Uploads attachment file
     *
     * @access  public
     * @return  string  javascript script segment
     */
    function UploadFile()
    {
        $file_num = jaws()->request->fetch('attachment_number', 'post');

        $file = Jaws_Utils::UploadFiles(
            $_FILES,
            Jaws_Utils::upload_tmp_dir(),
            '',
            'php,php3,php4,php5,phtml,phps,pl,py,cgi,pcgi,pcgi5,pcgi4,htaccess',
            null
        );
        if (Jaws_Error::IsError($file)) {
            $response = array('type'    => 'error',
                'message' => $file->getMessage());
        } else {
            $response = array('type' => 'notice', 'file_info' => array(
                'title' => $file['attachment' . $file_num][0]['user_filename'],
                'filename' => $file['attachment' . $file_num][0]['host_filename'],
                'filesize_format' =>  Jaws_Utils::FormatSize($file['attachment' . $file_num][0]['host_filesize']),
                'filesize' => $file['attachment' . $file_num][0]['host_filesize'],
                'filetype' => $file['attachment' . $file_num][0]['host_filetype']));
        }

        $response = $GLOBALS['app']->UTF8->json_encode($response);
        return "<script type='text/javascript'>parent.onUpload($response);</script>";
    }

}