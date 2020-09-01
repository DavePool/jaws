<?php
/**
 * Jaws Gadget Users
 *
 * @category    Gadget
 * @package     Core
 * @author      Ali Fazelzadeh <afz@php.net>
 * @copyright   2020 Jaws Development Group
 * @license     http://www.gnu.org/copyleft/lesser.html
 */
class Jaws_Gadget_Users
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
     * Insert custom user's attributes of gadget
     *
     * @access  public
     * @param   int     $user   User ID
     * @param   array   $attrs  User's attributes
     * @return  bool    Returns True or Jaws_Error on Failure
     */
    function insertAttributes($user, $attrs = array())
    {
        $attrs['user'] = (int)$user;
        $tableName = strtolower('users_'.$this->gadget->name);
        $objORM = Jaws_ORM::getInstance()->table($tableName);
        return $objORM->insert($attrs)->exec();
    }

    /**
     * Update custom user's attributes of gadget
     *
     * @access  public
     * @param   int     $user   User ID
     * @param   array   $attrs  User's attributes
     * @return  bool    Returns True or Jaws_Error on Failure
     */
    function updateAttributes($user, $attrs = array())
    {
        $tableName = strtolower('users_'.$this->gadget->name);
        $objORM = Jaws_ORM::getInstance()->table($tableName);
        return $objORM->update($attrs)->where('user', (int)$user)->exec();
    }

    /**
     * delete custom user's attributes of gadget
     *
     * @access  public
     * @param   int     $user   User ID
     * @return  bool    Returns True or Jaws_Error on Failure
     */
    function deleteAttributes($user)
    {
        $tableName = strtolower('users_'.$this->gadget->name);
        $objORM = Jaws_ORM::getInstance()->table($tableName);
        return $objORM->delete()->where('user', (int)$user)->exec();
    }

    /**
     * Fetch user's attributes
     *
     * @access  public
     * @param   int     $user           User ID
     * @param   array   $attributes     User's custom/default attributes
     * @return  mixed   Returns array of user's attributes or Jaws_Error on Failure
     */
    function fetch($user, $attributes)
    {
        $tableName = strtolower('users_'.$this->gadget->name);
        array_walk(
            $attributes['custom'],
            function(&$value, $key, $prefix) {
                $value = $prefix. '.'. $value;
            },
            $tableName
        );

        array_walk(
            $attributes['default'],
            function(&$value, $key) {
                $value = 'users.'. $value;
            }
        );

        return Jaws_ORM::getInstance()->table('users')
            ->select(array_merge($attributes['default'], $attributes['custom']))
            ->join($tableName, $tableName.'.user', 'users.id', 'left')
            ->where('users.id', (int)$user)
            ->fetchRow();
    }

    /**
     * Build filter conditions query
     *
     * @access  public
     * @param   object  $objORM     Jaws_ORM object
     * @param   array   $filters    Filters array
     * @return  void
     */
    private function buildFilters(&$objORM, $tableName, &$filters, $op = 'and')
    {
        foreach ($filters as $filter) {
            if (is_array($filter[0])) {
                $objORM->openWhere();
                $this->buildFilters($objORM, $tableName, $filter, 'or');
                $objORM->closeWhere();
            } else {
                $objORM->where(
                    $tableName. '.'. $filter[0],
                    $filter[1],
                    array_key_exists(2, $filter)? $filter[2] : '=',
                    array_key_exists(3, $filter)? (bool)$filter[3] : false
                );
            }

            $op == 'and'? $objORM->and() : $objORM->or();
        }
    }

    /**
     * Fetch users include default/custom attributes
     *
     * @access  public
     * @param   array   $attributes     User's custom/default attributes
     * @param   array   $filters        Filters
     * @param   int     $limit          Count of users to be returned
     * @param   int     $offset         Offset of data array
     * @return  mixed   Returns array of users or Jaws_Error on Failure
     */
    function fetchAll($attributes, $filters, $limit = false, $offset = null)
    {
        $tableName = strtolower('users_'.$this->gadget->name);
        array_walk(
            $attributes['custom'],
            function(&$value, $key, $prefix) {
                $value = $prefix. '.'. $value;
            },
            $tableName
        );

        array_walk(
            $attributes['default'],
            function(&$value, $key) {
                $value = 'users.'. $value;
            }
        );

        $objORM = Jaws_ORM::getInstance()
            ->table('users')
            ->select(array_merge($attributes['default'], $attributes['custom']))
            ->join($tableName, $tableName.'.user', 'users.id', 'left');

        // default attributes filters
        $this->buildFilters($objORM, 'users', $filters['default']);
        // custom attributes filters
        $this->buildFilters($objORM, $tableName, $filters['custom']);

        return $objORM->orderBy('users.id')->limit((int)$limit, $offset)->fetchAll();
    }

    /**
     * Count of users filtered by default/custom filters
     *
     * @access  public
     * @param   array   $filters    Filters
     * @return  mixed   Returns count of filtered users array or Jaws_Error on Failure
     */
    function count($filters)
    {
        $tableName = strtolower('users_'.$this->gadget->name);
        $objORM = Jaws_ORM::getInstance()
            ->table('users')
            ->select('count(users.id):integer')
            ->join($tableName, $tableName.'.user', 'users.id', 'left');

        // default attributes filters
        $this->buildFilters($objORM, 'users', $filters['default']);
        // custom attributes filters
        $this->buildFilters($objORM, $tableName, $filters['custom']);

        return $objORM->fetchOne();
    }
}