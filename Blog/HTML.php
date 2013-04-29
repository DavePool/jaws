<?php
/**
 * Blog Gadget
 *
 * @category   Gadget
 * @package    Blog
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2004-2013 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class Blog_HTML extends Jaws_Gadget_HTML
{
    /**
     * Calls default action(view)
     *
     * @access  public
     * @return  string XHTML template content
     */
    function DefaultAction()
    {
        $default_view = $this->gadget->registry->get('default_view');
        switch ($default_view) {
            case 'default_category':
                $cat = $this->gadget->registry->get('default_category');
                $postsHTML = $GLOBALS['app']->LoadGadget('Blog', 'HTML', 'Posts');
                return $postsHTML->ViewPage($cat);
                break;

            case 'monthly':
                $model = $GLOBALS['app']->LoadGadget('Blog', 'Model');
                $dates = $model->GetPostsDateLimitation(true);
                $date = $GLOBALS['app']->loadDate();
                $mDate = $date->Format($dates['max_date'], 'Y-m');
                $mDate = explode('-', $mDate);
                $dateHTML = $GLOBALS['app']->LoadGadget('Blog', 'HTML', 'DatePosts');
                return $dateHTML->ViewDatePage($mDate[0], $mDate[1]);
                break;

            case 'latest_entry':
                $postHTML = $GLOBALS['app']->LoadGadget('Blog', 'HTML', 'Post');
                return $postHTML->LastPost();
                break;

            default:
                $postsHTML = $GLOBALS['app']->LoadGadget('Blog', 'HTML', 'Posts');
                return $postsHTML->ViewPage();
        }
    }

    /**
     * Get page navigation links
     *
     * @access  public
     * @param   int     $page       page number
     * @param   int     $page_size
     * @param   int     $total
     * @param   string  $action     action
     * @param   array   $params     params array
     * @return  string  XHTML template content
     */
    function GetNumberedPageNavigation($page, $page_size, $total, $action, $params = array())
    {
        $tpl = new Jaws_Template('gadgets/Blog/templates/');
        $tpl->Load('PageNavigation.html');
        $tpl->SetBlock('pager');

        $model = $GLOBALS['app']->LoadGadget('Blog', 'Model');
        $pager = $model->GetEntryPagerNumbered($page, $page_size, $total);
        if (count($pager) > 0) {
            $tpl->SetBlock('pager/numbered-navigation');
            $tpl->SetVariable('total', _t('BLOG_ENTRIES_COUNT', $pager['total']));

            $pager_view = '';
            foreach ($pager as $k => $v) {
                $tpl->SetBlock('pager/numbered-navigation/item');
                $params['page'] = $v;
                if ($k == 'next') {
                    if ($v) {
                        $tpl->SetBlock('pager/numbered-navigation/item/next');
                        $tpl->SetVariable('lbl_next', _t('BLOG_PAGENAVIGATION_NEXTPAGE'));
                        $url = $this->gadget->GetURLFor($action, $params);
                        $tpl->SetVariable('url_next', $url);
                        $tpl->ParseBlock('pager/numbered-navigation/item/next');
                    } else {
                        $tpl->SetBlock('pager/numbered-navigation/item/no_next');
                        $tpl->SetVariable('lbl_next', _t('BLOG_PAGENAVIGATION_NEXTPAGE'));
                        $tpl->ParseBlock('pager/numbered-navigation/item/no_next');
                    }
                } elseif ($k == 'previous') {
                    if ($v) {
                        $tpl->SetBlock('pager/numbered-navigation/item/previous');
                        $tpl->SetVariable('lbl_previous', _t('BLOG_PAGENAVIGATION_PREVIOUSPAGE'));
                        $url = $this->gadget->GetURLFor($action, $params);
                        $tpl->SetVariable('url_previous', $url);
                        $tpl->ParseBlock('pager/numbered-navigation/item/previous');
                    } else {
                        $tpl->SetBlock('pager/numbered-navigation/item/no_previous');
                        $tpl->SetVariable('lbl_previous', _t('BLOG_PAGENAVIGATION_PREVIOUSPAGE'));
                        $tpl->ParseBlock('pager/numbered-navigation/item/no_previous');
                    }
                } elseif ($k == 'separator1' || $k == 'separator2') {
                    $tpl->SetBlock('pager/numbered-navigation/item/page_separator');
                    $tpl->ParseBlock('pager/numbered-navigation/item/page_separator');
                } elseif ($k == 'current') {
                    $tpl->SetBlock('pager/numbered-navigation/item/page_current');
                    $url = $this->gadget->GetURLFor($action, $params);
                    $tpl->SetVariable('lbl_page', $v);
                    $tpl->SetVariable('url_page', $url);
                    $tpl->ParseBlock('pager/numbered-navigation/item/page_current');
                } elseif ($k != 'total' && $k != 'next' && $k != 'previous') {
                    $tpl->SetBlock('pager/numbered-navigation/item/page_number');
                    $url = $this->gadget->GetURLFor($action, $params);
                    $tpl->SetVariable('lbl_page', $v);
                    $tpl->SetVariable('url_page', $url);
                    $tpl->ParseBlock('pager/numbered-navigation/item/page_number');
                }
                $tpl->ParseBlock('pager/numbered-navigation/item');
            }

            $tpl->ParseBlock('pager/numbered-navigation');
        }

        $tpl->ParseBlock('pager');

        return $tpl->Get();
    }

    /**
     * Get navigation links
     * 
     * @access  public
     * @param   string  $purl
     * @param   string  $ptitle     title
     * @param   string  $nurl       url
     * @param   string  $ntitle     title
     * @return  string  XHTML template content
     */
    function GetNavigation($purl, $ptitle, $nurl, $ntitle)
    {
        $tpl = new Jaws_Template('gadgets/Blog/templates/');
        $tpl->Load('PageNavigation.html');
        $tpl->SetBlock('pager');
        $tpl->SetBlock('pager/simple-navigation');

        if (!is_null($purl)) {
                $tpl->SetBlock('pager/simple-navigation/previous');
                $tpl->SetVariable('url', $purl);
                $tpl->SetVariable('title', $ptitle);
                $tpl->ParseBlock('pager/simple-navigation/previous');
        }

        if (!is_null($nurl)) {
                $tpl->SetBlock('pager/simple-navigation/next');
                $tpl->SetVariable('url', $nurl);
                $tpl->SetVariable('title',$ntitle);
                $tpl->ParseBlock('pager/simple-navigation/next');
        }

        $tpl->ParseBlock('pager/simple-navigation');
        $tpl->ParseBlock('pager');

        return $tpl->Get();
    }

    /**
     * Displays a given blog entry according to given parameters
     *
     * @access  public
     * @param   object  $tpl            Jaws_Template object
     * @param   string  $tpl_base_block Template block name
     * @param   int     $entry          entry id
     * @param   bool    $summary        Show post summary
     * @return  string XHTML template content
     */
    function ShowEntry(&$tpl, $tpl_base_block, $entry, $summary = true)
    {
        $tpl->SetBlock("$tpl_base_block/entry");
        $tpl->SetVariablesArray($entry);

        $tpl->SetVariable('posted_by', _t('BLOG_POSTED_BY'));
        $tpl->SetVariable('author-url',   $this->gadget->GetURLFor('ViewAuthorPage', array('id' => $entry['username'])));
        $date = $GLOBALS['app']->loadDate();
        $tpl->SetVariable('createtime-iso',       $date->ToISO($entry['publishtime']));
        $tpl->SetVariable('createtime',           $date->Format($entry['publishtime']));
        $tpl->SetVariable('createtime-monthname', $date->Format($entry['publishtime'], 'MN'));
        $tpl->SetVariable('createtime-monthabbr', $date->Format($entry['publishtime'], 'M'));
        $tpl->SetVariable('createtime-month',     $date->Format($entry['publishtime'], 'm'));
        $tpl->SetVariable('createtime-dayname',   $date->Format($entry['publishtime'], 'DN'));
        $tpl->SetVariable('createtime-dayabbr',   $date->Format($entry['publishtime'], 'D'));
        $tpl->SetVariable('createtime-day',       $date->Format($entry['publishtime'], 'd'));
        $tpl->SetVariable('createtime-year',      $date->Format($entry['publishtime'], 'Y'));
        $tpl->SetVariable('createtime-time',      $date->Format($entry['publishtime'], 'g:ia'));
        $tpl->SetVariable('entry-visits',         _t('BLOG_ENTRY_VISITS', $entry['clicks']));

        $id = empty($entry['fast_url']) ? $entry['id'] : $entry['fast_url'];
        $perm_url = $this->gadget->GetURLFor('SingleView', array('id' => $id));

        $summary = $entry['summary'];
        $text    = $entry['text'];

        // for compatibility with old versions
        $more_pos = Jaws_UTF8::strpos($text, '[more]');
        if ($more_pos !== false) {
            $summary = Jaws_UTF8::substr($text, 0, $more_pos);
            $text    = Jaws_UTF8::str_replace('[more]', '', $text);

            // Update this entry to split summary and body of post
            $model = $GLOBALS['app']->LoadGadget('Blog', 'Model');
            $model->SplitEntry($entry['id'], $summary, $text);
        }

        $summary = empty($summary)? $text : $summary;
        $summary = $this->gadget->ParseText($summary);
        $text    = $this->gadget->ParseText($text);

        if ($summary){
            if (Jaws_UTF8::trim($text) != '') {
                $tpl->SetBlock("$tpl_base_block/entry/read-more");
                $tpl->SetVariable('url', $perm_url);
                $tpl->SetVariable('read_more', _t('BLOG_READ_MORE'));
                $tpl->ParseBlock("$tpl_base_block/entry/read-more");
            }
            $tpl->SetVariable('text', $summary);
        } else {
            $GLOBALS['app']->Layout->AddHeadLink($GLOBALS['app']->Map->GetURLFor('Blog', 'Atom'),
                                                 'alternate',
                                                 'application/atom+xml',
                                                 'Atom - All');
            $GLOBALS['app']->Layout->AddHeadLink($GLOBALS['app']->Map->GetURLFor('Blog', 'RSS'),
                                                 'alternate',
                                                 'application/rss+xml',
                                                 'RSS 2.0 - All');
            $tpl->SetVariable('text', empty($text)? $summary : $text);
        }

        $tpl->SetVariable('permanent-link', $perm_url);

        $pos = 1;
        $tpl->SetVariable('posted_in', _t('BLOG_POSTED_IN'));
        foreach ($entry['categories'] as $cat) {
            $tpl->SetBlock("$tpl_base_block/entry/category");
            $tpl->SetVariable('id',   $cat['id']);
            $tpl->SetVariable('name', $cat['name']);
            $cid = empty($cat['fast_url']) ? $cat['id'] : $cat['fast_url'];
            $tpl->SetVariable('url',  $this->gadget->GetURLFor('ShowCategory', array('id' => $cid)));
            if ($pos == count($entry['categories'])) {
                $tpl->SetVariable('separator', '');
            } else {
                $tpl->SetVariable('separator', ',');
            }
            $pos++;
            $tpl->ParseBlock("$tpl_base_block/entry/category");
        }

        if ($entry['comments'] != 0 ||
            ($entry['allow_comments'] === true &&
             $this->gadget->registry->get('allow_comments') == 'true' &&
             $this->gadget->registry->get('allow_comments', 'Comments') != 'false'))
        {
            $tpl_block = $summary? 'comment-link' : 'comments-statistic';
            $tpl->SetBlock("$tpl_base_block/entry/$tpl_block");
            $tpl->SetVariable('url', $perm_url);
            if ($summary && empty($entry['comments'])) {
                $tpl->SetVariable('text_comments', _t('BLOG_NO_COMMENT'));
            } else {
                $tpl->SetVariable('text_comments', _t('BLOG_HAS_N_COMMENTS', $entry['comments']));
            }
            $tpl->SetVariable('num_comments', $entry['comments']);
            $tpl->ParseBlock("$tpl_base_block/entry/$tpl_block");
        }

        $tpl->ParseBlock("$tpl_base_block/entry");
        return $tpl->Get();
    }

    /**
     * Displays a list of popular posts
     *
     * @access  public
     * @return  string  XHTML template content
     */
    function PopularPosts()
    {
        $this->SetTitle(_t('BLOG_POPULAR_POSTS'));
        $layoutGadget = $GLOBALS['app']->LoadGadget('Blog', 'LayoutHTML');
        return $layoutGadget->PopularPosts();
    }

    /**
     * Displays a list of posts authors
     *
     * @access  public
     * @return  string  XHTML template content
     */
    function PostsAuthors()
    {
        $this->SetTitle(_t('BLOG_POSTS_AUTHORS'));
        $layoutGadget = $GLOBALS['app']->LoadGadget('Blog', 'LayoutHTML');
        return $layoutGadget->PostsAuthors();
    }

}