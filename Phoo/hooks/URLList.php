<?php
/**
 * Phoo - URL List gadget hook
 *
 * @category   GadgetHook
 * @package    Phoo
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @copyright  2007-2012 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class PhooURLListHook
{
    /**
     * Returns an array with all available items the Menu gadget 
     * can use
     *
     * @access  public
     * @return  array   urls array
     */
    function Hook()
    {
        $urls   = array();
        $urls[] = array('url'   => $GLOBALS['app']->Map->GetURLFor('Phoo', 'DefaultAction'),
                        'title' => _t('PHOO_NAME'));

        //Load model
        $model  = $GLOBALS['app']->loadGadget('Phoo', 'Model');        
        $albums = $model->GetAlbums();
        if (!Jaws_Error::IsError($albums) && !empty($albums)) {
            $max_size = 20;
            foreach($albums as $a) {
                $url = $GLOBALS['app']->Map->GetURLFor('Phoo', 'ViewAlbum', array('id' => $a['id']));
                $urls[] = array('url'   => $url,
                                'title' => ($GLOBALS['app']->UTF8->strlen($a['name']) > $max_size)?
                                            $GLOBALS['app']->UTF8->substr($a['name'], 0, $max_size).'...' :
                                            $a['name']);
            }
        }
        return $urls;
    }

}