<?php
/**
 * Quotes - Search gadget hook
 *
 * @category   GadgetHook
 * @package    Quotes
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @copyright  2007-2013 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class QuotesSearchHook
{
    /**
     * Gets the gadget search fields
     *
     * @access  public
     * @return  array   List of search fields
     */
    function GetSearchFields() {
        return array(
                    array('[title]', '[quotation]'),
                    );
    }

    /**
     * Returns an array of the search results
     *
     * @access  public
     * @param   string  $pSql  Prepared search(WHERE) SQL
     * @return  array   Array of entries match a certain pattern
     */
    function Hook($pSql = '')
    {
        $sql = '
            SELECT
                [id], [title], [quotation], [updatetime]
            FROM [[quotes]]
            WHERE [published] = {published}
            ';

        $sql .= ' AND ' . $pSql;
        $sql .= ' ORDER BY [id] DESC';

        $params = array();
        $params['published'] = true;

        $result = $GLOBALS['db']->queryAll($sql, $params);
        if (Jaws_Error::IsError($result)) {
            return array();
        }

        $date = $GLOBALS['app']->loadDate();
        $quotations = array();
        foreach ($result as $r) {
            $quotation = array();
            $quotation['title']   = $r['title'];
            $quotation['url']     = $GLOBALS['app']->Map->GetURLFor('Quotes', 'ViewQuote', array('id' => $r['id']));
            $quotation['image']   = 'gadgets/Quotes/images/logo.png';
            $quotation['snippet'] = $r['quotation'];
            $quotation['date']    = $date->ToISO($r['updatetime']);
            $quotations[] = $quotation;
        }

        return $quotations;
    }
}
