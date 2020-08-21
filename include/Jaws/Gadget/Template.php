<?php
/**
 * Jaws Gadget Template
 *
 * @category   Gadget
 * @package    Core
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2008-2020 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class Jaws_Gadget_Template
{
    /**
     * Jaws_Gadget object
     *
     * @var     object
     * @access  protected
     */
    var $gadget = null;

    /**
     * constructor
     *
     * @access  public
     * @param   object  $gadget Jaws_Gadget object
     * @return  void
     */
    function __construct($gadget)
    {
        $this->gadget = $gadget;
    }

    /**
     * Loads the gadget template file in question
     *
     * @access  public
     * @param   string  $filename   Template file name
     * @param   string  $options    Load template options(e.g. loadFromTheme, loadRTLDirection)
     * @param   bool    $backend    Admin template file?
     * @return  object  Jaws_Template object
     */
    function &exLoad($filename, $options = array(), $backend = false)
    {
        $filepath = dirname($filename);
        $filename = basename($filename);
        $loadFromTheme = isset($options['loadFromTheme'])? $options['loadFromTheme'] : (JAWS_SCRIPT == 'index');
        // if dirname returned dot ('.'), indicating no slashes in path(current directory)
        if ($filepath == '.') {
            $filepath = 'gadgets/'. $this->gadget->name. '/Templates'. ($backend? '/Admin': '');
        }

        $tpl = new Jaws_ExTemplate($filepath. DIRECTORY_SEPARATOR);
        $tpl->parseFile($filename);
        return $tpl;
    }

    /**
     * Loads the gadget template file in question
     *
     * @access  public
     * @param   string  $filename   Template file name
     * @param   string  $options    Load template options(e.g. loadFromTheme, loadRTLDirection)
     * @param   bool    $backend    Admin template file?
     * @return  object  Jaws_Template object
     */
    function &load($filename, $options = array(), $backend = false)
    {
        $filepath = dirname($filename);
        $filename = basename($filename);
        $loadFromTheme = isset($options['loadFromTheme'])? $options['loadFromTheme'] : (JAWS_SCRIPT == 'index');
        // if dirname returned dot ('.'), indicating no slashes in path(current directory)
        if ($filepath == '.') {
            $filepath = 'gadgets/'. $this->gadget->name. '/Templates/'. ($backend? 'Admin': '');
        }

        unset($options['loadFromTheme']);
        $tpl = new Jaws_Template($loadFromTheme);
        foreach ($options as $option => $value) {
            $tpl->$option = $value;
        }

        $tpl->Load($filename, $filepath);
        return $tpl;
    }

    /**
     * Loads the gadget template file in question
     *
     * @access  public
     * @param   string  $filename   Template file name
     * @param   string  $options    Load template options(e.g. loadFromTheme, loadRTLDirection)
     * @return  object  Jaws_Template object
     */
    function &loadAdmin($filename, $options = array())
    {
        return $this->load($filename, $options, true);
    }

}