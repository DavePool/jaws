<?php
/**
 * Jaws Gadget Session
 *
 * @category   Gadget
 * @package    Core
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2016 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class Jaws_Gadget_Session
{
    /**
     * Jaws app object
     *
     * @var     object
     * @access  public
     */
    public $app = null;

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
        $this->app = Jaws::getInstance();
    }

    /**
     * Delete a session attribute
     *
     * @access  public
     * @param   string  $name       Session key name
     * @param   bool    $trashed    Trashed attribute(eliminated end of current request)
     * @return  bool    True
     */
    function delete($name, $trashed = false)
    {
        $gadget = $this->gadget->name;
        return $this->app->session->deleteAttribute($name, $trashed, $gadget);
    }

    /**
     * Push response data
     *
     * @access  public
     * @param   string  $text       Response text
     * @param   string  $type       Response type
     * @param   string  $resource   Response name
     * @param   mixed   $data       Response data
     * @param   int     $code       Response code
     * @return  void
     */
    function push($text, $type = RESPONSE_NOTICE, $resource = 'Resource', $data = null, $code = 0)
    {
        $gadget = $this->gadget->name;
        return $this->app->session->pushResponse($text, "Response.$resource", $type, $data, $code, $gadget);
    }

    /**
     * Returns the response data
     *
     * @access  public
     * @param   string  $resource   Resource's name
     * @param   bool    $remove     Optional remove popped response
     * @return  mixed   Response data, or Null if resource not found
     */
    function pop($resource = 'Resource', $remove = true)
    {
        $gadget = $this->gadget->name;
        return $this->app->session->popResponse("Response.$resource", $remove, $gadget);
    }

    /**
     * Get formated data by response structure
     *
     * @access  public
     * @param   string  $text   Response text
     * @param   string  $type   Response type
     * @param   mixed   $data   Response data
     * @param   int     $code   Response code
     * @return  array   Returns array include text, type, data and code class
     */
    function response($text, $type = RESPONSE_NOTICE, $data = null, $code = 0)
    {
        return $this->app->session->getResponse($text, $type, $data, $code);
    }

    /**
     * Overloading __get magic method
     *
     * @access  private
     * @param   string  $property   Property name
     * @return  mixed   Requested property otherwise Jaws_Error
     */
    function __get($property)
    {
        return $this->app->session->getAttribute($property, $this->gadget->name);
    }

    /**
     * Overloading __set magic method
     *
     * @access  private
     * @param   string  $property   Property name
     * @param   mixed   $value      Property value
     * @return  void
     */
    function __set($property, $value)
    {
        return $this->app->session->setAttribute($property, $value, false, $this->gadget->name);
    }

}