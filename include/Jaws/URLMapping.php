<?php
/**
 * Jaws URL Mapping
 *
 * @category   Application
 * @package    Core
 * @author     Jonathan Hernandez  <ion@suavizado.com>
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2005-2013 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class Jaws_URLMapping
{
    /**
     * Model that will be used to get data
     *
     * @var    UrlMapperModel
     * @access  private
     */
    var $_Model;

    var $_map = array();
    var $_delimiter = '@';
    var $_enabled;
    var $_request_uri = '';
    var $_use_rewrite;
    var $_custom_precedence;
    var $_restrict_multimap;
    var $_use_aliases;
    var $_extension;

    /**
     * Constructor
     * Initializes the map, just pass null to a param if you want
     * to use the default values
     *
     * @param   string  $request_uri    Request URL
     * @access  public
     */
    function Jaws_URLMapping($request_uri = '')
    {
        $urlMapper = $GLOBALS['app']->LoadGadget('UrlMapper', 'Info');
        if (Jaws_Error::isError($urlMapper)) {
            Jaws_Error::Fatal($urlMapper->getMessage());
        }

        $this->_Model = $urlMapper->load('Model')->load('Model');
        if (Jaws_Error::isError($this->_Model)) {
            Jaws_Error::Fatal($this->_Model->getMessage());
        }

        $enabled   = $urlMapper->registry->fetch('map_enabled') == 'true';
        $extension = $urlMapper->registry->fetch('map_extensions');
        $this->_enabled = $enabled && strpos(strtolower($_SERVER['SERVER_SOFTWARE']), 'iis') === false;
        $this->_use_rewrite       = $urlMapper->registry->fetch('map_use_rewrite') == 'true';
        $this->_use_aliases       = $urlMapper->registry->fetch('map_use_aliases') == 'true';
        $this->_custom_precedence = $urlMapper->registry->fetch('map_custom_precedence') == 'true';
        $this->_restrict_multimap = $urlMapper->registry->fetch('map_restrict_multimap') == 'true';
        if (!empty($extension) && $extension{0} != '.') {
            $extension = '.'.$extension;
        }
        $this->_extension = $extension;

        if (empty($request_uri)) {
            $this->_request_uri = $this->getPathInfo();
        } elseif (strpos($request_uri, 'http') !== false) {
            //prepare it manually
            if (false !== $strPos = stripos($request_uri, BASE_SCRIPT)) {
                $strPos = $strPos + strlen(BASE_SCRIPT);
                $this->_request_uri = substr($request_uri, $strPos);
            }
        } else {
            $this->_request_uri = $request_uri;
        }

        //Moment.. first check if we are running on aliases_mode
        if ($this->_use_aliases && $realURI = $this->_Model->GetAliasPath($this->_request_uri)) {
            $this->_request_uri = str_ireplace(BASE_SCRIPT, '', $realURI);
        }

        $params = explode('/', $this->_request_uri);
        if (false !== $apptype_key = array_search('apptype', $params)) {
            $request =& Jaws_Request::getInstance();
            $request->set('get', 'apptype', $params[$apptype_key + 1]);
            unset($params[$apptype_key], $params[$apptype_key+1]);
        }

        $this->_request_uri = implode('/', array_map('rawurldecode', $params));
    }

    /**
     * Resets the map
     *
     * @access  public
     */
    function Reset()
    {
        $this->_map = array();
    }

    /**
     * Loads the maps
     *
     * @access  public
     */
    function Load()
    {
        if ($this->_enabled) {
            $maps = $this->_Model->GetMaps();
            if (Jaws_Error::IsError($maps)) {
                return false;
            }

            foreach ($maps as $map) {
                $this->_map[$map['gadget']][$map['action']][] = array(
                                    'map'         => $map['map'],
                                    'params'      => null,
                                    'regexp'      => $map['regexp'],
                                    'extension'   => $map['extension'],
                                    'regexp_vars' => array_keys(unserialize($map['vars_regexps'])),
                                    'custom_map'       => $map['custom_map'],
                                    'custom_regexp'    => $map['custom_regexp'],
                );
            }
        }
    }

    /**
     * Parses a QUERY URI and if its valid it extracts the values from
     * it and creates $_GET variables for each value.
     *
     * @param   string  $path   Query URI
     */
    function Parse()
    {
        if (!$this->_enabled && !is_array($this->_map)) {
            return false;
        }

        $request =& Jaws_Request::getInstance();
        //Lets check HTTP headers to see if user is trying to login
        if ($request->get('gadget', 'post') == 'ControlPanel' && $request->get('action', 'post') == 'Login') {
            $request->set('get', 'gadget', 'ControlPanel');
            $request->set('get', 'action', 'Login');
            return true;
        }

        //If no path info is given but count($_POST) > 0?
        if (empty($this->_request_uri) && count($_POST) > 0) {
            return true;
        }

        if (strpos($this->_request_uri, '=') !== false) {
            return true;
        }

        $params = explode('/', $this->_request_uri);
        $matched_but_ignored = false;
        foreach ($this->_map as $gadget => $actions) {
            foreach ($actions as $action => $maps) {
                foreach ($maps as $map) {
                    $use_custom = !$this->_custom_precedence;
                    $has_custom = !empty($map['custom_map']);
                    for ($i = 1; $i <= 2; $i++) {
                        $use_custom = !$use_custom;
                        if ($use_custom) {
                            if (!$has_custom) {
                                continue;
                            }

                            $route  = $map['custom_map'];
                            $regexp = $map['custom_regexp'];
                            $custom = true;
                        } else {
                            $route  = $map['map'];
                            $regexp = $map['regexp'];
                            $custom = false;
                        }

                        $url = $this->_request_uri;
                        $ext = $map['extension'];
                        $ext = ($ext == '.')? $this->_extension : $ext;
                        if (substr($url, - strlen($ext)) == $ext) {
                            $url = substr($url, 0, - strlen($ext));
                        }

                        if (preg_match($regexp, $url, $matches) == 1) {
                            if ($this->_restrict_multimap) {
                                if ($this->_custom_precedence && $has_custom && !$custom) {
                                    $matched_but_ignored = true;
                                    continue;
                                }
                                if (!$this->_custom_precedence && $custom) {
                                    $matched_but_ignored = true;
                                    continue;
                                }
                            }

                            // Gadget/Action
                            $request->set('get', 'gadget', $gadget);
                            $request->set('get', 'action', $action);

                            // Params
                            if (isset($map['params']) && is_array($map['params'])) {
                                foreach ($map['params'] as $key => $value) {
                                    $request->set('get', $key, $value);
                                }
                            }

                            // Variables
                            preg_match_all('#{(\w+)}#si', $route, $matches_vars);
                            if (is_array($matches_vars)) {
                                array_shift($matches);
                                foreach ($matches as $key => $value) {
                                    $request->set('get', $matches_vars[1][$key], rawurldecode($value));
                                }
                            }

                            return true;
                        }
                    } // for
                } //foreach maps
            } // foreach actions
        }

        if ($matched_but_ignored) {
            return false;
        }

        /**
         * Ok, no alias and map found, so lets parse the path directly.
         * The first rule: it should have at least one value (the gadget name)
         */
        $params_count = count($params);
        if ($params_count >= 1) {
            if (!$this->_restrict_multimap ||
                !$this->_enabled || !isset($params[1]) ||
                !isset($this->_map[$params[0]][$params[1]]))
            {
                $request->set('get', 'gadget', $params[0]);
                if (isset($params[1])) {
                    $request->set('get', 'action', $params[1]);
                }

                /**
                 * If we have a request via POST we should take those values, not the GET ones
                 * However, I'm not pretty sure if we should allow gadget and action being passed
                 * with /, cause officially (HTTP) you can't do that (params are passed via & not /)
                 *
                 * Next params following gadget/action should be parsed only if they come from a
                 * GET request
                 */
                //Ok, next values should be formed in pairs
                $params = array_slice($params, 2);
                $params_count = count($params);
                if ($params_count % 2 == 0) {
                    for ($i = 0; $i < $params_count; $i += 2) {
                        $request->set('get', $params[$i], $params[$i+1]);
                    }
                }

                return true;
            }
        }

        return false;
    }

    /**
     * Does the reverse stuff for an URL map. It gets all the params i
     * as an array and converts all the stuff to an URL map
     *
     * @access  public
     * @param   string  $gadget     Gadget name
     * @param   string  $action     Action name
     * @param   array   $params     Parameters of action
     * @param   bool    $abs_url    Absolute or relative URL
     * @return  string  The real URL map (aka jaws permalink)
     */
    function GetURLFor($gadget, $action='', $params = array(), $abs_url = false)
    {
        $params_vars = array_keys($params);
        if ($this->_enabled && isset($this->_map[$gadget][$action])) {
            foreach ($this->_map[$gadget][$action] as $map) {
                if ($this->_custom_precedence && !empty($map['custom_map'])) {
                    $url = $map['custom_map'];
                } else {
                    $url = $map['map'];
                }

                // all params variables must exist in regexp variables
                $not_exist_vars = array_diff($params_vars, $map['regexp_vars']);
                if (!empty($not_exist_vars)) {
                    continue;
                }

                // set map variables by params values 
                foreach ($params as $key => $value) {
                    $value = implode('/', array_map('rawurlencode', explode('/', $value)));
                    $url = str_replace('{' . $key . '}', $value, $url);
                }

                // remove not fill optional part of map
                do {
                    $rpl_url = $url;
                    $url = preg_replace('$\[[[:word:]/]*{\w+}[[:word:]/]*\]$u', '', $url);
                } while ($rpl_url != $url);
                $url = str_replace(array('[', ']'), '', $url);

                if (!preg_match('#{\w+}#si', $url)) {
                    if (!$this->_use_rewrite) {
                        $url = 'index.php/' . $url;
                    }

                    $ext = $map['extension'];
                    $url.= ($ext == '.')? $this->_extension : $ext;
                    break;
                }
                $url = '';
            }

            if (!empty($url)) {
                return ($abs_url? $GLOBALS['app']->getSiteURL('/') : '') . $url;
            }
        }

        if ($this->_use_rewrite) {
            $url = $gadget . '/'. $action;
        } elseif (!$this->_enabled) {
            $url = 'index.php?' .$gadget . '/'. $action;
        } else {
            $url = 'index.php/' .$gadget . '/'. $action;
        }
        if (is_array($params)) {
            //Params should be in pairs
            foreach ($params as $key => $value) {
                $value = implode('/', array_map('rawurlencode', explode('/', $value)));
                $url.= '/' . $key . '/' . $value;
            }
        }

        return ($abs_url? $GLOBALS['app']->getSiteURL('/') : '') . $url;
    }

    /**
     * Returns the PATH_INFO or simulates it
     *
     * @access  private
     * @return  string   PATH_INFO (empty or with a trailing dash)
     */
    function getPathInfo()
    {
        if (isset($_SERVER['REQUEST_URI']) && !empty($_SERVER['REQUEST_URI'])) {
            $uri = $_SERVER['REQUEST_URI'];
        } elseif (isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING'])) {
            $uri = $_SERVER['PHP_SELF'] . '?' .$_SERVER['QUERY_STRING'];
        } else {
            $uri = '';
        }

        if (!empty($uri)) {
            if (!$this->_use_rewrite) {
                if (false !== $dotPosition = stripos($uri, BASE_SCRIPT)) {
                    $pathInfo = substr($uri, $dotPosition + strlen(BASE_SCRIPT));
                } else {
                    if (false !== $qsnPosition = stripos($uri, '?')) {
                        $pathInfo = substr($uri, $qsnPosition);
                    }
                }
            }

            if (!isset($pathInfo)) {
                $base_uri = $GLOBALS['app']->GetSiteURL('', true);
                if ($base_uri == substr($uri, 0, strlen($base_uri))) {
                    $pathInfo = substr($uri, strlen($base_uri));
                }
            }
        }

        $pathInfo = isset($pathInfo)? ltrim((string)$pathInfo, '/?') : '';
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && $pathInfo == BASE_SCRIPT) {
            $pathInfo = '';
        }

        if (false !== $qsnPosition = strripos($pathInfo, '?')) {
            $pathInfo = substr($pathInfo, 0, $qsnPosition);
        }

        return $pathInfo;
    }

}