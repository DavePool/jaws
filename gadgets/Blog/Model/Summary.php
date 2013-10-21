<?php
/**
 * Blog Gadget
 *
 * @category   GadgetModel
 * @package    Blog
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2004-2013 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class Blog_Model_Summary extends Jaws_Gadget_Model
{
    /**
     * Get summary of the blog
     *
     * @access  public
     * @return  array   An array that has the summary of blog entries
     */
    function GetSummary()
    {
        $model   = $this->gadget->loadModel('DatePosts');
        $summary = $model->GetPostsDateLimitation();

        // Avg. entries per week
        if (isset($summary['min_date'])) {
            $dfirst    = strtotime($summary['min_date']);
            $dlast     = strtotime($summary['max_date']);
            $weekfirst = date('W', $dfirst);
            $yearfirst = date('Y', $dfirst);
            $weeklast  = date('W', $dlast);
            $yearlast  = date('Y', $dlast);
            if ($yearlast > $yearfirst) {
                // Ok ok, we assume 53 weeks per year...
                $nweeks =(54 - $weekfirst) +(53 *(($yearlast - 1) - $yearfirst)) + $weeklast;
            } else {
                $nweeks = $weeklast - $weekfirst;
            }

            if ($nweeks != 0) {
                $avg = round($summary['qty_posts'] / $nweeks);
            } else {
                $avg = $summary['qty_posts'];
            }

            $summary['AvgEntriesPerWeek'] = $avg;
        } else {
            $summary['min_date'] = null;
            $summary['max_date'] = null;
            $summary['AvgEntriesPerWeek'] = null;
        }

        // Recent entries
        $blogTable = Jaws_ORM::getInstance()->table('blog');
        $blogTable->select('id:integer', 'title', 'fast_url', 'published:boolean', 'publishtime');
        $result = $blogTable->orderBy('publishtime desc')->limit(10)->fetchAll();
        if (!Jaws_Error::IsError($result) && $result) {
            foreach ($result as $r) {
                $summary['Entries'][] = $r;
            }
        }

        if (Jaws_Gadget::IsGadgetInstalled('Comments')) {
            $cModel = $GLOBALS['app']->LoadGadget('Comments', 'Model', 'Comments');
            // total comments
            $summary['CommentsQty'] = $cModel->GetCommentsCount($this->gadget->name);
            // recent comments
            $comments = $cModel->GetComments($this->gadget->name, 10);
            if (Jaws_Error::IsError($comments)) {
                return $comments;
            }

            foreach ($comments as $r) {
                $summary['Comments'][] = array(
                    'id'         => $r['id'],
                    'name'       => $r['name'],
                    'createtime' => $r['createtime']
                );
            }
        }

        return $summary;
    }

}