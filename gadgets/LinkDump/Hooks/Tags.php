<?php
/**
 * LinkDump - Tags gadget hook
 *
 * @category    GadgetHook
 * @package     LinkDump
 * @author      Mojtaba Ebrahimi <ebrahimi@zehneziba.ir>
 * @copyright   2013 Jaws Development Group
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class LinkDump_Hooks_Tags extends Jaws_Gadget_Hook
{
    /**
     * Returns an array with the results of a tag content
     *
     * @access  public
     * @param   array  $tag_items  Tag items
     * @return  array  An array of entries that matches a certain pattern
     */
    function Execute($tag_items)
    {
        if(!is_array($tag_items) || empty($tag_items)) {
            return;
        }

        foreach($tag_items['link'] as $item) {

        }
        $table = Jaws_ORM::getInstance()->table('linkdump_links');
        $table->select('id:integer', 'title', 'description', 'updatetime');
        $result = $table->where('id', $tag_items['link'], 'in')->fetchAll();
        if (Jaws_Error::IsError($result)) {
            return array();
        }

        $date = $GLOBALS['app']->loadDate();
        $links = array();
        foreach ($result as $r) {
            $link = array();
            $link['title']   = $r['title'];
            $link['url']     = $GLOBALS['app']->Map->GetURLFor('LinkDump', 'Link', array('id' => $r['id']));
            $link['outer']   = true;
            $link['image']   = 'gadgets/LinkDump/images/logo.png';
            $link['snippet'] = $r['description'];
            $link['date']    = $date->ToISO($r['updatetime']);
            $links[] = $link;
        }

        return $links;
    }

}