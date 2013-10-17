<?php
/**
 * Banner Gadget Info
 *
 * @category   GadgetInfo
 * @package    Banner
 */
class Banner_Info extends Jaws_Gadget
{
    /**
     * Gadget version
     *
     * @var     string
     * @access  private
     */
    var $version = '0.9.0';

    /**
     * Banners data directory
     *
     * @var     string
     * @access  protected
     */
    var $DataDirectory = 'banners/';

    /**
     * Default front-end action name
     *
     * @var     string
     * @access  protected
     */
    var $default_action = 'Banners';

}