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
class Blog_Actions_Comments extends Blog_HTML
{

    /**
     * Displays a preview of the given blog comment
     *
     * @access  public
     * @return  string  XHTML template content
     */
    function Preview()
    {
        $names = array(
            'name', 'email', 'url', 'title', 'message', 'createtime',
            'ip_address', 'reference'
        );
        $post = jaws()->request->fetch($names, 'post');
        $id   = (int)$post['reference'];

        $model = $GLOBALS['app']->LoadGadget('Blog', 'Model', 'Posts');
        $entry = $model->GetEntry($id, true);
        if (Jaws_Error::isError($entry)) {
            $GLOBALS['app']->Session->PushSimpleResponse($entry->getMessage(), 'Blog');
            Jaws_Header::Location($this->gadget->urlMap('DefaultAction'));
        }

        $postHTML = $GLOBALS['app']->LoadGadget('Blog', 'HTML', 'Post');
        $id = !empty($entry['fast_url']) ? $entry['fast_url'] : $entry['id'];
        return $postHTML->SingleView($id, true);
    }

}