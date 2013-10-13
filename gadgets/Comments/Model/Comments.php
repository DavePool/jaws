<?php
/**
 * Comments Model
 *
 * @category    GadgetModel
 * @package     Comments
 * @author      Ali Fazelzadeh <afz@php.net>
 * @author      Mojtaba Ebrahimi <ebrahimi@zehneziba.ir>
 * @copyright   2013 Jaws Development Group
 * @license     http://www.gnu.org/copyleft/lesser.html
 */
class Comments_Model_Comments extends Jaws_Gadget_Model
{
    /**
     * Gets a comment
     *
     * @access  public
     * @param   int     $id Comment ID
     * @return  array   Returns an array with comment data or Jaws_Error on error
     */
    function GetComment($id)
    {
        $commentsTable = Jaws_ORM::getInstance()->table('comments');
        $commentsTable->select(
            'id:integer', 'reference:integer', 'action', 'gadget', 'reply', 'replier',
            'name', 'email', 'url', 'ip', 'msg_txt', 'status', 'createtime'
        );

        return $commentsTable->where('id', $id)->fetchRow();
    }

    /**
     * Gets a list of comments that match a certain filter options
     *
     * @access  public
     * @param   string  $gadget     Gadget name
     * @param   string  $action     Which mode should be used to filter
     * @param   int     $reference  Gadget reference id
     * @param   string  $term       Data that will be used in the filter
     * @param   int     $status     Comment status (approved=1, waiting=2, spam=3)
     * @param   int     $limit      How many comments
     * @param   mixed   $offset     Offset of data
     * @param   int     $orderBy    The column index which the result must be sorted by
     * @param   boolean $is_private Show private comments or not
     * @return  array   Returns an array with of filtered comments or Jaws_Error on error
     */
    function GetComments($gadget = '', $action = '', $reference = '', $term = '', $status = array(),
        $limit = 15, $offset = 0, $orderBy = 0, $show_private = false)
    {
        $commentsTable = Jaws_ORM::getInstance()->table('comments');
        $commentsTable->select(
            'comments.id:integer', 'gadget', 'action', 'reference:integer', 'user', 'reply', 'replier',
            'comments.name', 'comments.email', 'comments.url', 'ip', 'msg_txt', 'comments.status:integer',
            'createtime', 'users.username', 'users.nickname', 'users.email as user_email', 'users.avatar',
            'users.registered_date as user_registered_date', 'users.url as user_url',
            'replier.nickname as replier_nickname','replier.username as replier_username'
        );

        $commentsTable->join('users', 'users.id', 'comments.user', 'left');
        $commentsTable->join('users as replier', 'replier.id', 'comments.replier', 'left');

        if (!empty($gadget)) {
            $commentsTable->where('gadget', $gadget);
        }
        if(!empty($action)) {
            $commentsTable->and()->where('action', $action);
        }
        if(!empty($reference)) {
            $commentsTable->and()->where('reference', (int)$reference);
        }

        if (!empty($status)) {
            if (is_array($status)) {
                $commentsTable->and()->where('comments.status', $status, 'in');
            } else {
                $commentsTable->and()->where('comments.status', $status);
            }
        }

        if (!empty($term)) {
            $commentsTable->and()->openWhere('reference', $term);
            $commentsTable->or()->where('comments.name', '%'.$term.'%', 'like');
            $commentsTable->or()->where('comments.email', '%'.$term.'%', 'like');
            $commentsTable->or()->where('comments.url', '%'.$term.'%', 'like');
            $commentsTable->or()->closeWhere('msg_txt', '%'.$term.'%', 'like');
        }

        if (!$show_private) {
            $commentsTable->and()->where('comments.is_private', 0);
        }

        $commentsTable->limit($limit, $offset);
        $orders = array(
            'createtime asc',
            'createtime desc',
        );
        $orderBy = (int)$orderBy;
        $orderBy = $orders[($orderBy > 1)? 1 : $orderBy];
        $commentsTable->orderBy($orderBy);

        return $commentsTable->fetchAll();
    }

    /**
     * Gets count of comments that match a certain filter options
     *
     * @access  public
     * @param   string  $gadget     Gadget name
     * @param   string  $action     Which mode should be used to filter
     * @param   int     $reference  Gadget reference id
     * @param   string  $term       Data that will be used in the filter
     * @param   int     $status     Comment status (approved=1, waiting=2, spam=3)
     * @return  array   Returns count of filtered comments or Jaws_Error on error
     */
    function GetCommentsCount($gadget = '', $action = '', $reference = '', $term = '', $status = array(), $show_private = false)
    {
        $commentsTable = Jaws_ORM::getInstance()->table('comments');
        $commentsTable->select('count(comments.id)');

        if (!empty($gadget)) {
            $commentsTable->where('gadget', $gadget);
        }
        if(!empty($action)) {
            $commentsTable->and()->where('action', $action);
        }
        if(!empty($reference)) {
            $commentsTable->and()->where('reference', (int)$reference);
        }

        if (!empty($status)) {
            if (is_array($status)) {
                $commentsTable->and()->where('comments.status', $status, 'in');
            } else {
                $commentsTable->and()->where('comments.status', $status);
            }
        }

        if (!empty($term)) {
            $commentsTable->and()->openWhere('reference', $term);
            $commentsTable->or()->where('comments.name', '%'.$term.'%', 'like');
            $commentsTable->or()->where('comments.email', '%'.$term.'%', 'like');
            $commentsTable->or()->where('comments.url', '%'.$term.'%', 'like');
            $commentsTable->or()->closeWhere('msg_txt', '%'.$term.'%', 'like');
        }

        if (!$show_private) {
            $commentsTable->and()->where('comments.is_private', 0);
        }

        return $commentsTable->fetchOne();
    }

}