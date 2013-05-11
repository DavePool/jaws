<?php
/**
 * StaticPage Gadget (layout actions in client side)
 *
 * @category   GadgetLayout
 * @package    StaticPage
 * @author     Jon Wood <jon@jellybob.co.uk>
 * @copyright  2004-2013 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class StaticPage_LayoutHTML extends Jaws_Gadget_HTML
{
    /**
     * Get GroupPages action params
     *
     * @access  public
     * @return  array list of GroupPages action params
     */
    function GroupPagesLayoutParams()
    {
        $result = array();
        $sModel = $GLOBALS['app']->LoadGadget('StaticPage', 'Model');
        $groups = $sModel->GetGroups(true);
        if (!Jaws_Error::isError($groups)) {
            $pgroups = array();
            foreach ($groups as $group) {
                $pgroups[$group['id']] = $group['title'];
            }

            $result[] = array(
                'title' => _t('GLOBAL_GROUP'),
                'value' => $pgroups
            );

            $result[] = array(
                'title' => _t('GLOBAL_ORDERBY'),
                'value' => array(
                    0 => _t('GLOBAL_CREATETIME'). ' &uarr;',
                    1 => _t('GLOBAL_CREATETIME'). ' &darr;',
                    2 => _t('GLOBAL_TITLE'). ' &uarr;',
                    3 => _t('GLOBAL_TITLE'). ' &darr;',
                    4 => _t('GLOBAL_UPDATETIME'). ' &uarr;',
                    5 => _t('GLOBAL_UPDATETIME'). ' &darr;',
                )
            );
        }

        return $result;
    }

    /**
     * Displays a block of static pages
     *
     * @access  public
     * @return  string  XHTML content
     */
    function PagesList()
    {
        $model = $GLOBALS['app']->LoadGadget('StaticPage', 'Model');
        $pages = $model->GetPages();
        if (Jaws_Error::IsError($pages)) {
            return false;
        }

        $tpl = $this->gadget->loadTemplate('StaticPage.html');
        $tpl->SetBlock('index');
        $tpl->SetVariable('title', _t('STATICPAGE_PAGES_LIST'));
        foreach ($pages as $page) {
            if ($page['published'] === true) {
                $param = array('pid' => empty($page['fast_url']) ? $page['base_id'] : $page['fast_url']);
                $link = $GLOBALS['app']->Map->GetURLFor('StaticPage', 'Page', $param);
                $tpl->SetBlock('index/item');
                $tpl->SetVariable('title', $page['title']);
                $tpl->SetVariable('link',  $link);
                $tpl->ParseBlock('index/item');
            }
        }
        $tpl->ParseBlock('index');

        return $tpl->Get();
    }

    /**
     * Displays a block of groups
     *
     * @access  public
     * @return  string  XHTML content
     */
    function GroupsList()
    {
        $model = $GLOBALS['app']->LoadGadget('StaticPage', 'Model');
        $groups = $model->GetGroups(true);
        if (Jaws_Error::IsError($groups)) {
            return false;
        }

        $tpl = $this->gadget->loadTemplate('StaticPage.html');
        $tpl->SetBlock('group_index');
        $tpl->SetVariable('title', _t('STATICPAGE_GROUPS_LIST'));
        foreach ($groups as $group) {
            $gid = empty($group['fast_url'])? $group['id'] : $group['fast_url'];
            $link = $GLOBALS['app']->Map->GetURLFor('StaticPage', 'GroupPages', array('gid' => $gid));
            $tpl->SetBlock('group_index/item');
            $tpl->SetVariable('group', $group['title']);
            $tpl->SetVariable('link',  $link);
            $tpl->ParseBlock('group_index/item');
        }
        $tpl->ParseBlock('group_index');

        return $tpl->Get();
    }

    /**
     * Displays a block of pages belongs to the specified group
     *
     * @access  public
     * @param   mixed   $gid    ID or fast_url of the group (int/string)
     * @return  string  XHTML content
     */
    function GroupPages($gid = 0, $orderBy = 1)
    {
        $model = $GLOBALS['app']->LoadGadget('StaticPage', 'Model');
        $group = $model->GetGroup($gid);
        if (Jaws_Error::IsError($group) || $group == null) {
            return false;
        }

        $GLOBALS['app']->Layout->AddToMetaKeywords($group['meta_keywords']);
        $GLOBALS['app']->Layout->SetDescription($group['meta_description']);

        if (!is_numeric($gid)) {
            $gid = $group['id'];
        }

        $pages = $model->GetPages($gid, null, $orderBy);
        if (Jaws_Error::IsError($pages)) {
            return false;
        }

        $tpl = $this->gadget->loadTemplate('StaticPage.html');
        $tpl->SetBlock('group_pages');
        $tpl->SetVariable('title', $group['title']);
        foreach ($pages as $page) {
            if ($page['published']) {
                $param = array('gid' => empty($group['fast_url'])? $group['id'] : $group['fast_url'],
                               'pid' => empty($page['fast_url']) ? $page['base_id'] : $page['fast_url']);
                $link = $GLOBALS['app']->Map->GetURLFor('StaticPage', 'Pages', $param);
                $tpl->SetBlock('group_pages/item');
                $tpl->SetVariable('page', $page['title']);
                $tpl->SetVariable('link',  $link);
                $tpl->ParseBlock('group_pages/item');
            }
        }
        $tpl->ParseBlock('group_pages');

        return $tpl->Get();
    }

}