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
class Blog_Actions_Tags extends Blog_HTML
{
    /**
     * Display a tag cloud
     *
     * @access  public
     * @return  string  XHTML template content
     */
    function ShowTagCloud()
    {
        $model = $GLOBALS['app']->LoadGadget('Blog', 'Model', 'Tags');
        $res = $model->CreateTagCloud();
        $sortedTags = $res;
        sort($sortedTags);
        $minTagCount = log((isset($sortedTags[0]) ? $sortedTags[0]['howmany'] : 0));
        $maxTagCount = log(((count($res) != 0)? $sortedTags[count($res) - 1]['howmany'] : 0));
        unset($sortedTags);
        if ($minTagCount == $maxTagCount) {
            $tagCountRange = 1;
        } else {
            $tagCountRange = $maxTagCount - $minTagCount;
        }
        $minFontSize = 1;
        $maxFontSize = 10;
        $fontSizeRange = $maxFontSize - $minFontSize;

        $tpl = $this->gadget->loadTemplate('CategoryCloud.html');
        $tpl->SetBlock('tagcloud');
        $tpl->SetVariable('title', _t('BLOG_TAGCLOUD'));

        foreach ($res as $key => $value) {
            if (!$this->gadget->GetPermission('CategoryAccess', $value['category_id'])) {
                break;
            }
            $count  = $value['howmany'];
            $fsize = $minFontSize + $fontSizeRange * (log($count) - $minTagCount)/$tagCountRange;
            $tpl->SetBlock('tagcloud/tag');
            $tpl->SetVariable('size', (int)$fsize);
            $tpl->SetVariable('tagname',  Jaws_UTF8::strtolower($value['name']));
            $tpl->SetVariable('frequency', $value['howmany']);
            $cid = empty($value['fast_url']) ? $value['category_id'] : $value['fast_url'];
            $tpl->SetVariable('url', $GLOBALS['app']->Map->GetURLFor('Blog', 'ShowCategory', array('id' => $cid)));
            $tpl->SetVariable('category', $value['category_id']);
            $tpl->ParseBlock('tagcloud/tag');
        }
        $tpl->ParseBlock('tagcloud');

        return $tpl->Get();
    }

}