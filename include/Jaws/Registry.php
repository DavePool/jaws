<?php
/**
 * Class to manage jaws registry
 *
 * @category   Registry
 * @package    Core
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2004-2013 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class Jaws_Registry
{
    /**
     * Has the registry
     *
     * @var     array
     * @access  private
     */
    var $_Registry = array();

    /**
     * Array that has a *registry* of files that have been called
     *
     * @var     array
     * @access  private
     */
    var $_LoadedFiles = array();

    /**
     * Loads the data from the DB
     *
     * @access  public
     */
    function Init()
    {
        $tblReg = Jaws_ORM::getInstance()->table('registry');
        $result = $tblReg->select('component', 'key_name', 'key_value', 'custom:boolean')
            ->where('user', 0)
            ->and()
            ->openWhere('key_name', 'version')
            ->or()
            ->closeWhere('component', '')
            ->fetchAll('', JAWS_ERROR_NOTICE);
        if (Jaws_Error::IsError($result)) {
            if ($result->getCode() == MDB2_ERROR_NOSUCHFIELD) {
                // get 0.8.x jaws version
                $result = $tblReg->select('key_value')->where('key_name', '/version')->fetchOne();
                if (!Jaws_Error::IsError($result)) {
                    return $result;
                }
            }

            Jaws_Error::Fatal($result->getMessage());
        }

        foreach ($result as $regrec) {
            $this->_Registry[$regrec['component']][$regrec['key_name']] = $regrec;
        }

        return isset($this->_Registry['']['version'])? $this->_Registry['']['version']['key_value'] : null;
    }

    /**
     * Fetch the key value
     *
     * @access  public
     * @param   string  $key_name   Key name
     * @param   string  $component  Component name
     * @return  string  The value of the key
     */
    function fetch($key_name, $component = '')
    {
        if (!@array_key_exists($key_name, $this->_Registry[$component]) ||
            is_null($this->_Registry[$component][$key_name]['key_value'])
        ) {
            $tblReg = Jaws_ORM::getInstance()->table('registry');
            $rowVal = $tblReg->select('key_value', 'custom:boolean')
                ->where('user', 0)
                ->and()
                ->where('component', $component)
                ->and()
                ->where('key_name', $key_name)
                ->fetchRow();
            if (Jaws_Error::IsError($rowVal) || empty($rowVal)) {
                return null;
            }

            $this->_Registry[$component][$key_name] = $rowVal;
        }

        return $this->_Registry[$component][$key_name]['key_value'];
    }

    /**
     * Fetch all registry keys of the gadget
     *
     * @access  public
     * @param   string  $component  Component name
     * @param   string  $pattern    Key pattern
     * @return  mixed   Array of keys if successful or Jaws_Error on failure
     */
    function fetchAll($component = '', $pattern = '')
    {
        $tblReg = Jaws_ORM::getInstance()->table('registry');
        $tblReg->select('key_name', 'key_value')
            ->where('component', $component)
            ->and()
            ->where('user', 0);
        if (!empty($pattern)) {
            $tblReg->and()->where('key_name', $pattern, 'like');
        }

        $tblReg->orderBy('key_name');
        $result = $tblReg->fetchAll();
        if (Jaws_Error::IsError($result)) {
            return null;
        }

        return $result;
    }

    /**
     * Fetch user's registry key value
     *
     * @access  public
     * @param   int     $user       User ID
     * @param   string  $key_name   Key name
     * @param   string  $component  Component name
     * @return  mixed   User's value of the key if success otherwise default key value
     */
    function fetchByUser($user, $key_name, $component = '')
    {
        $value = $this->fetch($key_name, $component);
        if (!is_null($value) && $this->_Registry[$component][$key_name]['custom']) {
            $tblReg = Jaws_ORM::getInstance()->table('registry');
            $value  = $tblReg->select('key_value')
                ->where('user', (int)$user)
                ->and()
                ->where('component', $component)
                ->and()
                ->where('key_name', $key_name)
                ->fetchOne();
            if (!Jaws_Error::IsError($value) && !is_null($value)) {
                return $value;
            }
        }

        return $value;
    }

    /**
     * Fetch all user's registry keys of a gadget
     *
     * @access  public
     * @param   int     $user       User ID
     * @param   string  $component  Component name
     * @return  mixed   Array of keys if successful or Jaws_Error on failure
     */
    function fetchAllByUser($user, $component = '')
    {
        $tblReg = Jaws_ORM::getInstance()->table('registry');
        $result = $tblReg->select('key_name', 'key_value')
            ->where('component', $component)
            ->and()
            ->where('user', (int)$user)
            ->orderBy('key_name')
            ->fetchAll();
        if (Jaws_Error::IsError($result)) {
            return null;
        }

        return $result;
    }

    /**
     * Insert a new key
     *
     * @access  public
     * @param   string  $key_name   Key name
     * @param   string  $key_value  Key value
     * @param   bool    $custom     Customizable by user?
     * @param   string  $component  Component name
     * @return  bool    True is set otherwise False
     */
    function insert($key_name, $key_value, $custom = false, $component = '')
    {
        $tblReg = Jaws_ORM::getInstance()->table('registry');
        $tblReg->insert(array(
            'user'       => 0,
            'component'  => $component,
            'key_name'   => $key_name,
            'key_value'  => $key_value,
            'custom'     => (bool)$custom,
            'updatetime' => $GLOBALS['db']->Date(),
        ));
        $result = $tblReg->exec();
        if (!Jaws_Error::IsError($result)) {
            $this->_Registry[$component][$key_name] = $key_value;
        }

        return !Jaws_Error::IsError($result);
    }

    /**
     * Inserts array of keys
     *
     * @access  public
     * @param   array   $keys       Array of keys, values
     * @param   string  $component  Component name
     * @param   int     $user       User ID
     * @return  bool    True is set otherwise False
     */
    function insertAll($keys, $component = '', $user = 0)
    {
        if (empty($keys)) {
            return true;
        }

        $data = array();
        $user = (int)$user;
        $time = $GLOBALS['db']->Date();
        $columns = array('user', 'component', 'key_name', 'key_value', 'custom', 'updatetime');
        foreach ($keys  as $key) {
            @list($key_name, $key_value, $custom) = $key;
            $data[] = array($user, $component, $key_name, $key_value, (bool)$custom, $time);
        }

        $tblReg = Jaws_ORM::getInstance()->table('registry');
        return $tblReg->insertAll($columns, $data)->exec();;
    }

    /**
     * Updates the value of a key
     *
     * @access  public
     * @param   string  $key_name   Key name
     * @param   string  $key_value  Key value
     * @param   bool    $custom     Customizable by user?
     * @param   string  $component  Component name
     * @return  bool    True is set otherwise False
     */
    function update($key_name, $key_value, $custom = null, $component = '')
    {
        $data = array();
        if (!is_null($key_value)) {
            $data['key_value'] = $key_value;
        }
        if (!is_null($custom)) {
            $data['custom'] = (bool)$custom;
        }

        $tblReg = Jaws_ORM::getInstance()->table('registry');
        $tblReg->update($data)
            ->where('user', 0)
            ->and()
            ->where('component', $component)
            ->and()
            ->where('key_name', $key_name);
        $result = $tblReg->exec();
        if (Jaws_Error::IsError($result)) {
            return false;
        }

        $this->_Registry[$component][$key_name] = $data;
        return true;
    }

    /**
     * Deletes a key
     *
     * @access  public
     * @param   string  $component  Component name
     * @param   string  $key_name   Key name
     * @return  bool    True is set otherwise False
     */
    function delete($component, $key_name = '')
    {
        $tblReg = Jaws_ORM::getInstance()->table('registry');
        $tblReg->delete()->where('component', $component);
        if (!empty($key_name)) {
            $tblReg->and()->where('key_name', $key_name);
        }
        $result = $tblReg->exec();
        if (!Jaws_Error::IsError($result)) {
            if (empty($key_name)) {
                unset($this->_Registry[$component]);
            } else {
                unset($this->_Registry[$component][$key_name]);
            }
        }

        return !Jaws_Error::IsError($result);
    }

    /**
     * Delete all registry keys related to the user
     *
     * @access  public
     * @param   int     $user       User ID
     * @param   string  $component  Component name
     * @return  bool    True if success otherwise False
     */
    function deleteByUser($user, $component = '')
    {
        $tblACL = Jaws_ORM::getInstance()->table('registry');
        $tblACL->delete()->where('user', (int)$user);
        if (!empty($component)) {
            $tblACL->and()->where('component', $component);
        }
        $result = $tblACL->exec();
        return !Jaws_Error::IsError($result);
    }

}