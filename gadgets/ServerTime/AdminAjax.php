<?php
/**
 * ServerTime AJAX API
 *
 * @category   Ajax
 * @package    ServerTime
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2005-2013 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class ServerTime_AdminAjax extends Jaws_Gadget_HTML
{
    /**
     * Updates properties
     *
     * @access  public
     * @return  array   Response array (notice or error)
     */
    function UpdateProperties()
    {
        @list($format) = jaws()->request->fetchAll('post');
        $modelServerTime = $this->gadget->loadAdminModel('Properties');
        $modelServerTime->UpdateProperties($format);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

}