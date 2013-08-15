<?php
/**
 * Manage Access control lists
 *
 * @category    ACL
 * @package     Core
 * @author      Ivan Chavero <imcsk8@gluch.org.mx>
 * @author      Jonathan Hernandez <ion@suavizado.com>
 * @author      Ali Fazelzadeh <afz@php.net>
 * @copyright   2005-2013 Jaws Development Group
 * @license     http://www.gnu.org/copyleft/lesser.html
 */
class Jaws_ACL
{
    /**
     * Fetch the key value
     *
     * @access  public
     * @param   string  $key_name   Key name
     * @param   string  $key_subkey Subkey name
     * @param   string  $component  Component name
     * @return  string  The value of the key
     */
    function fetch($key_name, $subkey, $component)
    {
        $tblACL = Jaws_ORM::getInstance()->table('acl');
        $value = $tblACL->select('key_value:integer')
            ->where('component', $component)->and()
            ->where('key_name', $key_name)->and()
            ->where('key_subkey', $subkey)->and()
            ->where('user', 0)->and()->where('group', 0)
            ->fetchOne();
        if (Jaws_Error::IsError($value)) {
            return null;
        }

        return $value;
    }

    /**
     * Fetch all acl keys of the gadget
     *
     * @access  public
     * @param   string  $component  Component name
     * @return  mixed   Array of keys if successful or Jaws_Error on failure
     */
    function fetchAll($component)
    {
        $tblACL = Jaws_ORM::getInstance()->table('acl');
        $result = $tblACL->select('key_name', 'key_subkey', 'key_value:integer')
            ->where('component', $component)->and()
            ->where('user', 0)->and()->where('group', 0)
            ->fetchAll();
        if (Jaws_Error::IsError($result)) {
            return null;
        }

        return $result;
    }

    /**
     * Fetch the ACL key value by user
     *
     * @access  public
     * @param   int     $user       User ID
     * @param   string  $key_name   Key name
     * @param   string  $key_subkey Subkey name
     * @param   string  $component  Component name
     * @return  mixed   Value of the key if success otherwise Null
     */
    function fetchByUser($user, $key_name, $subkey, $component)
    {
        if (!empty($user)) {
            $tblACL = Jaws_ORM::getInstance()->table('acl');
            $value  = $tblACL->select('key_value:integer')
                ->where('component', $component)->and()
                ->where('key_name', $key_name)->and()
                ->where('key_subkey', $subkey)->and()
                ->where('user', (int)$user)
                ->fetchOne();
            if (!Jaws_Error::IsError($value)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Fetch all ACL keys/values releated to the user
     *
     * @access  public
     * @param   int     $user       User ID
     * @param   string  $component  Component name
     * @return  mixed   Array of ACLs if success otherwise Null
     */
    function fetchAllByUser($user, $component = '')
    {
        if (!empty($user)) {
            $tblACL = Jaws_ORM::getInstance()->table('acl');
            $tblACL->select('component', 'key_name', 'key_subkey', 'key_value:integer')->where('user', (int)$user);
            if (!empty($component)) {
                $tblACL->and()->where('component', $component);
            }

            $result = $tblACL->fetchAll();
            if (!Jaws_Error::IsError($result)) {
                return $result;
            }
        }

        return null;
    }

    /**
     * Fetch the ACL key value by groups
     *
     * @access  public
     * @param   array   $groups     Array of groups IDs
     * @param   string  $key_name   Key name
     * @param   string  $key_subkey Subkey name
     * @param   string  $component  Component name
     * @return  mixed   Array of values if success otherwise Null
     */
    function fetchByGroups($groups, $key_name, $subkey, $component)
    {
        if (!empty($groups)) {
            $tblACL = Jaws_ORM::getInstance()->table('acl');
            $values = $tblACL->select('key_value:integer')
                ->where('component', $component)->and()
                ->where('key_name', $key_name)->and()
                ->where('key_subkey', $subkey)->and()
                ->where('group', $groups, 'in')
                ->fetchColumn();
            if (!Jaws_Error::IsError($values)) {
                return $values;
            }
        }

        return null;
    }

    /**
     * Fetch all ACL keys/values releated to the group
     *
     * @access  public
     * @param   int     $group      Group ID
     * @param   string  $component  Component name
     * @return  mixed   Array of ACLs if success otherwise Null
     */
    function fetchAllByGroup($group, $component = '')
    {
        if (!empty($group)) {
            $tblACL = Jaws_ORM::getInstance()->table('acl');
            $tblACL->select('component', 'key_name', 'key_subkey', 'key_value:integer')->where('group', (int)$group);
            if (!empty($component)) {
                $tblACL->and()->where('component', $component);
            }

            $result = $tblACL->fetchAll();
            if (!Jaws_Error::IsError($result)) {
                return $result;
            }
        }

        return null;
    }

    /**
     * Insert a new key
     *
     * @access  public
     * @param   string  $key_name   Key name
     * @param   string  $subkey     Subkey name
     * @param   int     $key_value  Key value
     * @param   string  $component  Component name
     * @return  bool    True is set otherwise False
     */
    function insert($key_name, $subkey, $key_value, $component)
    {
        $tblACL = Jaws_ORM::getInstance()->table('acl');
        $tblACL->insert(array(
            'component' => $component,
            'key_name'  => $key_name,
            'key_subkey' => $subkey,
            'key_value' => (int)$key_value,
            'user'      => 0,
            'group'     => 0,
        ));
        $result = $tblACL->exec();
        return !Jaws_Error::IsError($result);
    }

    /**
     * Inserts array of keys
     *
     * @access  public
     * @param   array   $keys       Pairs of keys/values
     * @param   string  $component  Component name
     * @param   int     $user       User ID
     * @param   int     $group      Group ID
     * @return  bool    True is set otherwise False
     */
    function insertAll($keys, $component, $user = 0, $group = 0)
    {
        if (empty($keys)) {
            return true;
        }

        $params = array();
        $params['user']      = (int)$user;
        $params['group']     = (int)$group;
        $params['component'] = $component;

        $ndx = 0;
        $sqls = '';
        $dbDriver  = $GLOBALS['db']->getDriver();
        $dbVersion = $GLOBALS['db']->getDBVersion();
        foreach ($keys as $key) {
            list($key_name, $subkey, $key_value) = $key;
            $params["name_$ndx"]   = $key_name;
            $params["subkey_$ndx"] = $subkey;
            $params["value_$ndx"]  = (int)$key_value;

            // Ugly hack to support all databases
            switch ($dbDriver) {
                case 'oci8':
                    $sqls.= (empty($sqls)? '' : "\n UNION ALL").
                        "\n SELECT {component}, {name_$ndx}, {subkey_$ndx}, {value_$ndx}, {user}, {group} FROM DUAL";
                    break;
                case 'ibase':
                    $sqls[] = " VALUES ({component}, {name_$ndx}, {subkey_$ndx}, {value_$ndx}, {user}, {group})";
                    break;
                case 'pgsql':
                    if (version_compare($dbVersion, '8.2.0', '>=')) {
                        $sqls .= (empty($sqls)? "\n VALUES" : ",").
                                 "\n ({component}, {name_$ndx}, {subkey_$ndx}, {value_$ndx}, {user}, {group})";
                    } else {
                        $sqls[] = " VALUES ({component}, {name_$ndx}, {subkey_$ndx}, {value_$ndx}, {user}, {group})";
                    }
                    break;
                default:
                    $sqls .= (empty($sqls)? '' : "\n UNION ALL").
                             "\n SELECT {component}, {name_$ndx}, {subkey_$ndx}, {value_$ndx}, {user}, {group}";
                    break;
            }

            $ndx++;
        }

        if (is_array($sqls)) {
            foreach ($sqls as $sql) {
                $qsql = '
                    INSERT INTO [[acl]]
                        ([component], [key_name], [key_subkey], [key_value], [user], [group])
                    '. $sql;
                $result = $GLOBALS['db']->query($qsql, $params);
                if (Jaws_Error::IsError($result)) {
                    return $result;
                }
            }
        } else {
            $qsql = '
                INSERT INTO [[acl]]
                    ([component], [key_name], [key_subkey], [key_value], [user], [group])
                '. $sqls;
            $result = $GLOBALS['db']->query($qsql, $params);
            if (Jaws_Error::IsError($result)) {
                return $result;
            }
        }

        return true;
    }

    /**
     * Updates the value of a key
     *
     * @access  public
     * @param   string  $key_name   Key name
     * @param   string  $subkey     Subkey name
     * @param   int     $key_value  Key value
     * @param   string  $component  Component name
     * @return  bool    True is set otherwise False
     */
    function update($key_name, $subkey, $key_value, $component)
    {
        $tblACL = Jaws_ORM::getInstance()->table('acl');
        $result = $tblACL->update(array('key_value' => (int)$key_value))
            ->where('component', $component)->and()
            ->where('key_name', $key_name)->and()
            ->where('key_subkey', $subkey)->and()
            ->where('user', 0)->and()->where('group', 0)
            ->exec();
        return !Jaws_Error::IsError($result);
    }

    /**
     * Get the real/full permission of a gadget (and group if it has) for a certain key/subkey
     *
     * @access  public
     * @param   int     $user           User ID
     * @param   int     $groups         Array of group's ID or empty string
     * @param   string  $gadget         Gadget to use
     * @param   string  $key            ACL key name
     * @param   string  $subkey         ACL subkey name
     * @param   bool    $is_super_admin
     * @return  integer Permission value: Granted (1) or Denied (0)
     */
    function GetFullPermission($user, $groups, $gadget, $key, $subkey = '', $is_super_admin = false)
    {
        // is in forbidden acls?
        if (defined('JAWS_FORBIDDEN_ACLS')) {
            static $forbidden_acls;
            if (!isset($forbidden_acls)) {
                $forbidden_acls = array_filter(array_map('trim', explode(',', strtolower(JAWS_FORBIDDEN_ACLS))));
            }

            if (in_array(strtolower("$gadget:$key"), $forbidden_acls)) {
                return 0;
            }
        }

        if (defined('JAWS_GODUSER_ACLS') && defined('JAWS_GODUSER') && JAWS_GODUSER !== $user) {
            static $goduser_acls;
            if (!isset($goduser_acls)) {
                $goduser_acls = array_filter(array_map('trim', explode(',', strtolower(JAWS_GODUSER_ACLS))));
            }
            if (in_array(strtolower("$gadget:$key"), $goduser_acls)) {
                return 0;
            }
        }

        if ($is_super_admin === true) {
            return 0xff;
        }

        // 1. Check for user permission
        $perm['user'] = $this->fetchByUser($user, $key, $subkey, $gadget);
        if (!is_null($perm['user'])) {
            return $perm['user'];
        }

        // 2. Check for groups permission
        $perm['groups'] = $this->fetchByGroups($groups, $key, $subkey, $gadget);
        if (!empty($perm['groups'])) {
            return @max($perm['groups']);
        }

        // 3. Check for default
        $perm['default'] = $this->fetch($key, $subkey, $gadget);
        if (is_null($perm['default']) && ($subkey !== '')) {
            $perm['default'] = $this->fetch($key, '', $gadget);
        }

        return (int)$perm['default'];
    }

    /**
     * Deletes a key or all ACLS related to the component
     *
     * @access  public
     * @param   string  $component  Component name
     * @param   string  $key_name   Key name
     * @return  bool    True is set otherwise False
     */
    function delete($component, $key_name = '')
    {
        $tblACL = Jaws_ORM::getInstance()->table('acl');
        $tblACL->delete()->where('component', $component);
        if (!empty($key_name)) {
            $tblACL->and()->where('key_name', $key_name);
        }

        $result = $tblACL->exec();
        return !Jaws_Error::IsError($result);
    }

    /**
     * Delete all ACLs related to the user
     *
     * @access  public
     * @param   int     $user       User ID
     * @param   string  $component  Component name
     * @return  bool    True if success otherwise False
     */
    function deleteByUser($user, $component = '')
    {
        $tblACL = Jaws_ORM::getInstance()->table('acl');
        $tblACL->delete()->where('user', (int)$user);
        if (!empty($component)) {
            $tblACL->and()->where('component', $component);
        }
        $result = $tblACL->exec();
        return !Jaws_Error::IsError($result);
    }

    /**
     * Delete all ACLs related to the group
     *
     * @access  public
     * @param   int     $group      Group ID
     * @param   string  $component  Component name
     * @return  bool    True if success otherwise False
     */
    function deleteByGroup($group, $component = '')
    {
        $tblACL = Jaws_ORM::getInstance()->table('acl');
        $tblACL->delete()->where('group', (int)$group);
        if (!empty($component)) {
            $tblACL->and()->where('component', $component);
        }
        $result = $tblACL->exec();
        return !Jaws_Error::IsError($result);
    }

}