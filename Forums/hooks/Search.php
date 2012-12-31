<?php
/**
 * Forums - Search gadget hook
 *
 * @category   GadgetHook
 * @package    Forums
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2012-2013 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class ForumsSearchHook
{
    /**
     * Gets the gadget's search fields
     *
     * @access  public
     * @return  array   array of search fields
     */
    function GetSearchFields() {
        return array(
            array('fp.[message]'),
        );
    }

    /**
     * Returns an array with the results of a search
     *
     * @access  public
     * @param   string  $pSql  Prepared search (WHERE) SQL
     * @return  array   An array of entries that matches a certain pattern
     */
    function Hook($pSql = '')
    {
        $sql = '
            SELECT
                fp.[id], fp.[tid], ft.[fid],
                ft.[subject], fp.[message], fp.[insert_time],
                (
                    SELECT
                        COUNT(fpc.[id])
                    FROM
                        [[forums_posts]] as fpc
                    WHERE
                        fpc.[tid] = fp.[tid] AND fpc.[id] <= fp.[id]
                ) as post_number
            FROM
                [[forums_posts]] as fp
            LEFT JOIN
                [[forums_topics]] as ft ON fp.[tid] = ft.[id]
            ';

        $sql .= ' WHERE ' . $pSql;
        $sql .= '
            ORDER BY fp.[insert_time] DESC';

        $result = $GLOBALS['db']->queryAll($sql);
        if (Jaws_Error::IsError($result)) {
            return array();
        }

        $objDate = $GLOBALS['app']->loadDate();
        $posts = array();
        foreach ($result as $r) {
            $post = array();
            $post['title'] = $r['subject'];
            $post['url']   = $GLOBALS['app']->Map->GetURLFor(
                'Forums',
                'Posts',
                array(
                    'fid' => $r['fid'],
                    'tid' => $r['tid'],
                    'pid' => $r['id'],
                    'page' => ceil($r['post_number']/10)
                )
            );
            $post['image']   = 'gadgets/Forums/images/logo.png';
            $post['snippet'] = $r['message'];
            $post['date']    = $objDate->ToISO($r['insert_time']);
            $posts[] = $post;
        }

        return $posts;
    }

}