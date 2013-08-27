<?php
/**
 * Phoo Gadget
 *
 * @category   GadgetAdmin
 * @package    Phoo
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Raul Murciano <raul@murciano.net>
 * @copyright  2004-2013 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class Phoo_Actions_Admin_Thumb extends Phoo_AdminHTML
{
    /**
     * Resize an image on the fly
     * 
     * FIXME: I don't know if is better to get it as a standalone function...
     * 
     * @returns binary Image resized
     */
    function Thumb()
    {
        $request =& Jaws_Request::getInstance();
        $image   = $request->get('image', 'get');

        include_once JAWS_PATH . 'include/Jaws/Image.php';
        $objImage = Jaws_Image::factory();
        if (!Jaws_Error::IsError($objImage)) {
            $result = $objImage->load(JAWS_DATA . 'phoo/import/' . $image);
            if (!Jaws_Error::IsError($result)) {
                $thumbSize = explode('x', $this->gadget->registry->fetch('thumbsize'));
                $objImage->resize($thumbSize[0], $thumbSize[1]);
                $result = $objImage->display();
                if (!Jaws_Error::IsError($result)) {
                    return $result;
                }
            }
        }

        header('Content-type: image/png');
        return file_get_contents('gadgets/Phoo/images/logo.png');
    }

}