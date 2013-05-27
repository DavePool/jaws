<?php
/**
 * Comments Model
 *
 * @category    GadgetModel
 * @package     Comments
 * @author      Pablo Fischer <pablo@pablo.com.mx>
 * @author      Jonathan Hernandez <ion@suavizado.com>
 * @author      Ali Fazelzadeh <afz@php.net>
 * @copyright   2005-2013 Jaws Development Group
 * @license     http://www.gnu.org/copyleft/lesser.html
 */
class Comments_Model_EditComments extends Jaws_Gadget_Model
{
    /**
     * Message is unique? Is it not duplicated?
     *
     * @access  public
     * @param   string   $md5     Message key in MD5
     * @return  bool    Exists (true) or Not Exists (false)
     */
    function IsMessageDuplicated($md5)
    {
        $commentsTable = Jaws_ORM::getInstance()->table('comments');
        $howmany = $commentsTable->select('count([id]):integer')->where('msg_key', $md5)->getOne();

        ///FIXME check for errors
        return ($howmany == '0') ? false : true;
    }

    /**
     * Inserts a new comment
     *
     * @param   string  $gadget   Gadget's name
     * @param   int     $gadgetId  Gadget's reference id.
     *                             It can be the ID of a blog entry, the ID of a
     *                             photo in Phoo, etc. This needs to be a reference
     *                             to find the comments related to a specific record
     *                             in a gadget.
     * @param   string  $action
     * @param   string  $name      Author's name
     * @param   string  $email     Author's email
     * @param   string  $url       Author's url
     * @param   string  $message   Author's message
     * @param   string  $ip        Author's IP
     * @param   string  $permalink Permanent link to resource
     * @param   int     $status
     * @return  int     Comment status or Jaws_Error on any error
     * @access  public
     */
    function insertComment($gadget, $gadgetId, $action, $name, $email, $url, $message,
                        $ip, $permalink, $status = COMMENT_STATUS_APPROVED)
    {
        if (!in_array($status, array(1, 2, 3))) {
            $status = Comments_Info::COMMENT_STATUS_SPAM;
        }

        $message_key = md5($message);
        if ($this->gadget->registry->fetch('allow_duplicate') == 'no') {
            if ($this->IsMessageDuplicated($message_key)) {
                return new Jaws_Error(_t('COMMENTS_SPAM_POSSIBLE_DUPLICATE_MESSAGE'), _t('COMMENTS_NAME'));
            }
        }

        // Validate website url
        if (!preg_match('$^(http|https|ftp)://([A-Z0-9][A-Z0-9_-]*(?:.[A-Z0-9][A-Z0-9_-]*)+):?(d+)?/?$i', $url)) {
            $url = '';
        }

        // Comment Status...
        $mPolicy = $GLOBALS['app']->LoadGadget('Policy', 'Model');
        if ($mPolicy->IsSpam($permalink, $gadget, $name, $email, $url, $message)) {
            $status = COMMENT_STATUS_SPAM;
        }

        $cData = array();
        $cData['reference']     = $gadgetId;
        $cData['action']        = $action;
        $cData['gadget']        = $gadget;
        $cData['name']          = $name;
        $cData['email']         = $email;
        $cData['url']           = $url;
        $cData['msg_txt']       = $message;
        $cData['status']        = (int)$status;
        $cData['msg_key']       = $message_key;
        $cData['ip']            = $ip;
        $cData['user']          = $GLOBALS['app']->Session->GetAttribute('user');
        $cData['createtime']    = $GLOBALS['db']->Date();

        $commentsTable = Jaws_ORM::getInstance()->table('comments');
        $res = $commentsTable->insert($cData)->exec();

        if (!Jaws_Error::IsError($res)) {
            $GLOBALS['app']->Listener->Shout('UpdateComment', array($gadget, $action, $gadgetId));
        }

        return $res;
    }

    /**
     * Updates a comment
     *
     * @param   string  $gadget     Gadget's name
     * @param   int     $id         Comment's ID
     * @param   string  $name       Author's name
     * @param   string  $email      Author's email
     * @param   string  $url        Author's url
     * @param   string  $message    Author's message
     * @param   string  $reply      Comment's reply
     * @param   string  $permalink  Permanent link to resource
     * @param   string  $status     Comment status
     * @return  bool    True if success or Jaws_Error on any error
     * @access  public
     */
    function updateComment($gadget, $id, $name, $email, $url, $message, $reply, $permalink, $status)
    {
        $cData = array();
        $cData['name']    = $name;
        $cData['email']   = $email;
        $cData['url']     = $url;
        $cData['msg_txt'] = $message;
        $cData['msg_key'] = md5($message);
        $cData['reply']   = $reply;
        $cData['replier'] = $GLOBALS['app']->Session->GetAttribute('user');
        $cData['status']  = $status;

        $commentsTable = Jaws_ORM::getInstance()->table('comments');
        $result = $commentsTable->update($cData)->where('id', $id)->and()->where('gadget', $gadget)->exec();
        if (Jaws_Error::IsError($result)) {
            return $result;
        }

        $cModel = $GLOBALS['app']->LoadGadget('Comments', 'Model', 'Comments');
        $origComment = $cModel->GetComment($id);
        if (($status == Comments_Info::COMMENT_STATUS_SPAM || $origComment['status'] == Comments_Info::COMMENT_STATUS_SPAM) &&
            $origComment['status'] != $status)
        {
            $mPolicy = $GLOBALS['app']->LoadGadget('Policy', 'AdminModel');
            if ($status == Comments_Info::COMMENT_STATUS_SPAM) {
                $mPolicy->SubmitSpam($permalink, $gadget, $name, $email, $url, $message);
            } else {
                $mPolicy->SubmitHam($permalink, $gadget, $name, $email, $url, $message);
            }
        }

        if (!Jaws_Error::IsError($res)) {
            $commentsTable = Jaws_ORM::getInstance()->table('comments');
            $commentsTable->select('gadget', 'reference:integer', 'action');
            $comment = $commentsTable->where('id', $id)->getRow();
            $GLOBALS['app']->Listener->Shout('UpdateComment', array($comment['gadget'], $comment['action'],
                $comment['reference']));
        }

        return true;
    }

    /**
     * Mark as a different status several comments
     *
     * @access  public
     * @param   string $gadget  Gadget's name
     * @param   array   $ids     Id's of the comments to mark as spam
     * @param   string  $status  New status (spam by default)
     * @return  bool
     */
    function MarkAs($gadget, $ids, $status = 'spam')
    {
        if (count($ids) == 0) {
            return true;
        }

        if (!in_array($status, array(1, 2, 3))) {
            $status = Comments_Info::COMMENT_STATUS_SPAM;
        }

        // Update status...
        $commentsTable = Jaws_ORM::getInstance()->table('comments');
        $commentsTable->update(array('status'=>$status))->where('id', $ids, 'in')->exec();


        $commentsTable = Jaws_ORM::getInstance()->table('comments');
        $commentsTable->select('gadget', 'reference:integer', 'action');
        $comments = $commentsTable->where('id', $ids, 'in')->getAll();
        if (Jaws_Error::IsError($comments)) {
            return $comments;
        }
        foreach($comments as $comment) {
            $GLOBALS['app']->Listener->Shout('UpdateComment', array($comment['gadget'], $comment['action'],
                $comment['reference']));
        }

        if ($status == Comments_Info::COMMENT_STATUS_SPAM) {
            $mPolicy = $GLOBALS['app']->LoadGadget('Policy', 'AdminModel');
            // Submit spam...
            $commentsTable = Jaws_ORM::getInstance()->table('comments');
            $commentsTable->select('id:integer', 'name', 'email', 'url', 'msg_txt', 'msg_txt', 'status:integer');
            $items = $commentsTable->where('id', $ids, 'in')->getAll();
            if (Jaws_Error::IsError($items)) {
                return $items;
            }

            foreach ($items as $i) {
                if ($i['status'] != Comments_Info::COMMENT_STATUS_SPAM) {
                    // FIXME Get $permalink
                    $permalink = '';
                    $mPolicy->SubmitSpam($permalink, $gadget, $i['name'], $i['email'], $i['url'], $i['message']);
                }
            }
        }

        return true;
    }

}