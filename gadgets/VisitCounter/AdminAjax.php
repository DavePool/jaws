<?php
/**
 * VisitCounter AJAX API
 *
 * @category   Ajax
 * @package    VisitCounter
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2005-2013 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class VisitCounter_AdminAjax extends Jaws_Gadget_HTML
{
    /**
     * Cleans all the entries (records)
     *
     * @access  public
     * @return  array   Response array (notice or error)
     */
    function CleanEntries()
    {
        $this->gadget->CheckPermission('ResetCounter');
        $model = $GLOBALS['app']->LoadGadget('VisitCounter', 'AdminModel', 'Visitors');
        $model->ClearVisitors();
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Resets the counter
     *
     * @access  public
     * @return  array   Response array (notice or error)
     */
    function ResetCounter()
    {
        $this->gadget->CheckPermission('ResetCounter');
        $model = $GLOBALS['app']->LoadGadget('VisitCounter', 'AdminModel', 'Visitors');
        $model->SetStartDate(date('Y-m-d H:i:s'));
        $model->ResetCounter();
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Gets the start date
     *
     * @access  public
     * @return  string  Start date(taken from registry)
     */
    function GetStartDate()
    {
        $date  = $GLOBALS['app']->loadDate();
        $model = $GLOBALS['app']->LoadGadget('VisitCounter', 'Model', 'Visitors');
        $start = $model->GetStartDate();
        return $date->Format($start);
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
        @list($counters, $numdays, $type, $mode, $custom_text) = jaws()->request->fetchAll('post');
        $custom_text = jaws()->request->fetch(4, 'post', false);
        $model = $GLOBALS['app']->LoadGadget('VisitCounter', 'AdminModel', 'Properties');
        $model->UpdateProperties($counters, $numdays, $type, $mode, $custom_text);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Gets all entries/records for datagrid
     *
     * @access  public
     * @return  array   List of visits
     */
    function GetData()
    {
        @list($offset) = jaws()->request->fetchAll('post');
        if (!is_numeric($offset)) {
            $offset = 0;
        }
        $gadget = $GLOBALS['app']->LoadGadget('VisitCounter', 'AdminHTML', 'VisitCounter');
        return $gadget->GetVisits($offset);
    }

}