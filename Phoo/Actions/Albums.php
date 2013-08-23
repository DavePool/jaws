<?php
/**
 * Phoo Gadget
 *
 * @category   Gadget
 * @package    Phoo
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Raul Murciano <raul@murciano.net>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2004-2013 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class Phoo_Actions_Albums extends Jaws_Gadget_HTML
{
    /**
     * Displays an index of galleries.
     *
     * @access  public
     * @return  string XHTML template content
     */
    function AlbumList()
    {
        $tpl = $this->gadget->loadTemplate('Albums.html');
        $tpl->SetBlock('albums');
        $tpl->SetVariable('title', _t('PHOO_ALBUMS'));
        $model = $GLOBALS['app']->LoadGadget('Phoo', 'Model');
        $albums = $model->GetAlbumList();
        if (!Jaws_Error::IsError($albums)) {
            $date = $GLOBALS['app']->loadDate();
            require_once JAWS_PATH . 'include/Jaws/Image.php';
            foreach ($albums as $album) {
                if (!isset($album['qty'])) {
                    continue;
                }

                $tpl->SetBlock('albums/item');
                $imgData = Jaws_Image::get_image_details(JAWS_DATA . 'phoo/' . $album['thumb']);
                if (!Jaws_Error::IsError($imgData)) {
                    $tpl->SetVariable('width',    $imgData[0]);
                    $tpl->SetVariable('height',   $imgData[1]);
                }
                $url = $GLOBALS['app']->Map->GetURLFor('Phoo','ViewAlbum', array('id' => $album['id']));
                $tpl->SetVariable('url',      $url);
                $tpl->SetVariable('name',     $album['name']);
                $tpl->SetVariable('filename', $album['filename']);
                $tpl->SetVariable('thumb',    $GLOBALS['app']->getDataURL('phoo/' . $album['thumb']));
                $tpl->SetVariable('howmany',  _t('PHOO_NUM_PHOTOS_ALBUM', $album['qty']));
                $tpl->SetVariable('description', $this->gadget->ParseText($album['description']));
                $tpl->SetVariable('createtime', $date->Format($album['createtime']));
                $tpl->ParseBlock('albums/item');
            }
        }
        $tpl->ParseBlock('albums');
        return $tpl->Get();
    }

}