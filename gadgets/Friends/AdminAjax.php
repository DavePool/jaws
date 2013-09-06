<?php
/**
 * Friends AJAX API
 *
 * @category   Ajax
 * @package    Friend
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @copyright  2005-2013 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class Friends_AdminAjax extends Jaws_Gadget_HTML
{
    /**
     * Get information of a friend
     *
     * @access   public
     * @internal param  string  $friend     Friend's name
     * @return   mixed  Friend information or False on error
     */
    function GetFriend()
    {
        @list($friend) = jaws()->request->getAll('post');
        $model = $GLOBALS['app']->loadGadget('Friends', 'Model', 'Friends');
        $friendInfo = $model->GetFriend($friend);
        if (Jaws_Error::IsError($friendInfo)) {
            return false; //we need to handle errors on ajax
        } else {
            return $friendInfo;
        }
    }

    /**
     * Add a friend
     *
     * @access   public
     * @internal param  string  $friend     Friend's name
     * @internal param  string  $url        Friend's URL
     * @return   array  Response array (notice or error)
     */
    function NewFriend()
    {
        $this->gadget->CheckPermission('AddFriend');
        @list($friend, $url) = jaws()->request->getAll('post');
        $model = $GLOBALS['app']->loadGadget('Friends', 'AdminModel', 'Friends');
        $model->NewFriend($friend, $url);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Update friend's information
     *
     * @access   public
     * @internal param  string  $old        Friend's OLD name
     * @internal param  string  $friend     Friend's name
     * @internal param  string  $url        Friend's URL
     * @return   array  Response array (notice or error)
     */
    function UpdateFriend()
    {
        $this->gadget->CheckPermission('EditFriend');
        @list($old, $friend, $url) = jaws()->request->getAll('post');
        $model = $GLOBALS['app']->loadGadget('Friends', 'AdminModel', 'Friends');
        $model->UpdateFriend($old, $friend, $url);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Delete a friend
     *
     * @access   public
     * @internal param  string  $friend     Friend's name
     * @return   array  Response array (notice or error)
     */
    function DeleteFriend()
    {
        $this->gadget->CheckPermission('DeleteFriend');
        @list($friend) = jaws()->request->getAll('post');
        $model = $GLOBALS['app']->loadGadget('Friends', 'AdminModel', 'Friends');
        $model->DeleteFriend($friend);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Update the properties
     *
     * @access   public
     * @internal param  int $limit Limit random
     * @return   array  Response array
     */
    function UpdateProperties()
    {
        $this->gadget->CheckPermission('UpdateProperties');
        @list($limit) = jaws()->request->getAll('post');
        $model = $GLOBALS['app']->loadGadget('Friends', 'AdminModel', 'Friends');
        $model->UpdateProperties($limit);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Get data from DB
     *
     * @access   public
     * @internal param  int     $limit  limit data
     * @return   array  data array
     */
    function GetData()
    {
        @list($limit) = jaws()->request->getAll('post');
        if(empty($limit)) {
            $limit = 0;
        }
        $gadget = $GLOBALS['app']->LoadGadget('Friends', 'AdminHTML', 'Friends');
        if (!is_numeric($limit)) {
            $limit = 0;
        }
        return $gadget->GetFriends($limit);
    }

}
