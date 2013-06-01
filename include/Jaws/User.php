<?php
define('PASSWORD_SALT_LENGTH', 24);
define('AVATAR_PATH', JAWS_DATA. 'avatar'. DIRECTORY_SEPARATOR);

/**
 * This class is for Jaws_User table operations
 *
 * @category   User
 * @package    Core
 * @author     Ivan -sk8- Chavero <imcsk8@gluch.org.mx>
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2005-2013 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class Jaws_User
{
    /**
     * Get hashed password
     *
     * @access  private
     * @param   string  $password
     * @param   string  $salt
     * @return  string  Returns hashed password
     */
    function GetHashedPassword($password, $salt = null)
    {
        if (is_null($salt)) {
            $salt = substr(md5(uniqid(rand(), true)), 0, PASSWORD_SALT_LENGTH);
        } else {
            $salt = substr($salt, 0, PASSWORD_SALT_LENGTH);
        }

        return $salt . sha1($salt . $password);
    }

    /**
     * Validate a user
     *
     * @access  public
     * @param   string  $user      User to validate
     * @param   string  $password  Password of the user
     * @param   bool    $onlyAdmin Only validate for admins
     * @return  bool    Returns true if the user is valid and false if not
     */
    function Valid($user, $password, $onlyAdmin = false)
    {
        $usersTable = Jaws_ORM::getInstance()->table('users');
        $usersTable->select(
            'id:integer', 'password', 'superadmin:boolean', 'bad_password_count',
            'concurrents:integer', 'logon_hours', 'expiry_date', 'last_access', 'status:integer'
        );
        $result = $usersTable->where('lower(username)', Jaws_UTF8::strtolower($user))->getRow();
        if (Jaws_Error::IsError($result)) {
            return $result;
        }

        if (!empty($result)) {
            // bad_password_count & lockedout time
            if ($result['bad_password_count'] >= $GLOBALS['app']->Registry->fetch('password_bad_count', 'Policy') &&
               ((time() - $result['last_access']) <= $GLOBALS['app']->Registry->fetch('password_lockedout_time', 'Policy')))
            {
                return Jaws_Error::raiseError(
                    _t('GLOBAL_ERROR_LOGIN_LOCKED_OUT'),
                    __FUNCTION__,
                    JAWS_ERROR_NOTICE
                );
            }

            // password
            if ($result['password'] === Jaws_User::GetHashedPassword($password, $result['password'])) {
                // only superadmin
                if ($onlyAdmin && !$result['superadmin']) {
                    return Jaws_Error::raiseError(
                        _t('GLOBAL_ERROR_LOGIN_ONLY_ADMIN'),
                        __FUNCTION__,
                        JAWS_ERROR_NOTICE
                    );
                }

                // status
                if ($result['status'] !== 1) {
                    return Jaws_Error::raiseError(
                        _t('GLOBAL_ERROR_LOGIN_STATUS_'. $result['status']),
                        __FUNCTION__,
                        JAWS_ERROR_NOTICE
                    );
                }

                // expiry date
                if (!empty($result['expiry_date']) && $result['expiry_date'] <= time()) {
                    return Jaws_Error::raiseError(
                        _t('GLOBAL_ERROR_LOGIN_EXPIRED'),
                        __FUNCTION__,
                        JAWS_ERROR_NOTICE
                    );
                }

                // logon hours
                $wdhour = explode(',', $GLOBALS['app']->UTC2UserTime(time(), 'w,G', true));
                $lhByte = hexdec($result['logon_hours']{$wdhour[0]*6 + intval($wdhour[1]/4)});
                if ((pow(2, fmod($wdhour[1], 4)) & $lhByte) == 0) {
                    return Jaws_Error::raiseError(
                        _t('GLOBAL_ERROR_LOGIN_LOGON_HOURS'),
                        __FUNCTION__,
                        JAWS_ERROR_NOTICE
                    );
                }

                return array('id' => $result['id'],
                            'superadmin' => $result['superadmin'],
                            'concurrents' => $result['concurrents']);

            } else {
                // bad_password_count + 1
                $usersTable->update(
                    array(
                        'last_access' => time(),
                        'bad_password_count' => $usersTable->expr('bad_password_count + ?', 1)
                    )
                )->where('id', $result['id'])->exec();
            }
        }

        return Jaws_Error::raiseError(
            _t('GLOBAL_ERROR_LOGIN_WRONG'),
            __FUNCTION__,
            JAWS_ERROR_NOTICE
        );
    }

    /**
     * Updates the last login time for the given user
     *
     * @param $user_id integer user id of the user being updated
     * @return  bool    true if all is ok, false if error
     */
    function updateLoginTime($user_id)
    {
        $usersTable = Jaws_ORM::getInstance()->table('users');
        $result = $usersTable->update(array('bad_password_count' => 0))->where('id', (int)$user_id)->exec();
        if (Jaws_Error::isError($result)) {
            return false;
        }

        return true;
    }

    /**
     * Get the info of an user by the username or ID
     *
     * @access  public
     * @param   mixed   $user           The username or ID
     * @param   bool    $account        Account information
     * @param   bool    $personal       Personal information
     * @param   bool    $preferences    Preferences options
     * @param   bool    $contacts       Contacts information
     * @return  mixed   Returns an array with the info of the user and false on error
     */
    function GetUser($user, $account = true, $personal = false, $preferences = false, $contacts = false)
    {
        $columns = array('id:integer',);
        // account information
        if ($account) {
            $columns = array_merge($columns, array('username', 'nickname', 'email', 'superadmin:boolean',
                'concurrents', 'logon_hours', 'expiry_date', 'registered_date', 'status:integer',
                'last_update',)
            );
        }

        if ($personal) {
            $columns = array_merge($columns, array('fname', 'lname', 'gender', 'dob', 'url', 'avatar',
                'public:boolean', 'privacy:boolean', 'signature', 'about', 'experiences', 'occupations',
                'interests',)
            );
        }

        if ($preferences) {
            $columns = array_merge($columns, array('language', 'theme', 'editor', 'timezone'));
        }

        if ($contacts) {
            $columns = array_merge($columns, array('country', 'city', 'address', 'postal_code', 'phone_number',
                'mobile_number', 'fax_number'));
        }

        $usersTable = Jaws_ORM::getInstance()->table('users');
        $usersTable->select($columns);
        if (is_int($user)) {
            $usersTable->where('id', $user);
        } else {
             $usersTable->where('lower(username)', Jaws_UTF8::strtolower($user));
        }

        return $usersTable->getRow();
    }

    /**
     * Get the info of an user(s) by the email address
     *
     * @access  public
     * @param   int     $email  The email address
     * @return  mixed   Returns an array with the info of the user(s) and false on error
     */
    function GetUserInfoByEmail($email)
    {
        $usersTable = Jaws_ORM::getInstance()->table('users');
        $usersTable->select('id:integer', 'username', 'nickname', 'email', 'superadmin:boolean', 'status:integer');
        $usersTable->where('lower(email)', $email);
        return $usersTable->getAll();
    }

    /**
     * Get the info of an user(s) by the email verification key
     *
     * @access  public
     * @param   string  $key  Verification key
     * @return  mixed   Returns an array with the info of the user(s) and false on error
     */
    function GetUserByEmailVerifyKey($key)
    {
        $usersTable = Jaws_ORM::getInstance()->table('users');
        $usersTable->select('id:integer', 'username', 'nickname', 'email', 'status:integer');
        $usersTable->where('email_verify_key', trim($key));
        return $usersTable->getRow();
    }

    /**
     * Get the info of an user(s) by the password verification key
     *
     * @access  public
     * @param   string  $key  Verification key
     * @return  mixed   Returns an array with the info of the user(s) and false on error
     */
    function GetUserByPasswordVerifyKey($key)
    {
        $usersTable = Jaws_ORM::getInstance()->table('users');
        $usersTable->select('id:integer', 'username', 'nickname', 'email', 'status:integer');
        $usersTable->where('password_verify_key', trim($key));
        return $usersTable->getRow();
    }

    /**
     * Check and email address already exists
     *
     * @access  public
     * @param   string  $email      The email address
     * @param   int     $exclude    Excluded user ID
     * @return  mixed   Returns an array with the info of the user and false on error
     */
    function UserEmailExists($email, $exclude = 0)
    {
        $usersTable = Jaws_ORM::getInstance()->table('users');
        $usersTable->select('count(id)');
        $usersTable->where('email', Jaws_UTF8::strtolower($email));
        $usersTable->and()->where('id', $exclude, '<>');
        $howmany = $usersTable->getOne();
        return !empty($howmany);
    }

    /**
     * Get the avatar url
     * @access  public
     * @param   string   $avatar    User's avatar
     * @param   string   $email     User's email address
     * @param   integer  $size      Avatar size
     * @param   integer  $time      An integer for force browser to refresh it cache
     * @return  string   Url to avatar image
     */
    function GetAvatar($avatar, $email, $size = 48, $time = '')
    {
        if (empty($avatar) || !file_exists(AVATAR_PATH . $avatar)) {
            require_once JAWS_PATH . 'include/Jaws/Gravatar.php';
            $uAvatar = Jaws_Gravatar::GetGravatar($email, $size);
        } else {
            $uAvatar = $GLOBALS['app']->getDataURL(). "avatar/$avatar";
            $uAvatar.= !empty($time)? "?$time" : '';
        }

        return $uAvatar;
    }

    /**
     * Get the info of a group
     *
     * @access  public
     * @param   mixed   $group  The group ID/Name
     * @return  mixed   Returns an array with the info of the group and false on error
     */
    function GetGroup($group)
    {
        $groupsTable = Jaws_ORM::getInstance()->table('groups');
        $groupsTable->select('id:integer', 'name', 'title', 'description', 'enabled:boolean');
        if (is_int($group)) {
            $groupsTable->where('id', $group);
        } else {
            $groupsTable->where('lower(name)', Jaws_UTF8::strtolower($group));
        }

        return $groupsTable->getRow();
    }

    /**
     * Get list of users
     *
     * @access  public
     * @param   mixed   $group      Group ID of users
     * @param   mixed   $superadmin Type of user(null = all types, true = superadmin, false = normal)
     * @param   int     $status     User's status (null: all users, 0: disabled, 1: enabled, 2: not verified)
     * @param   string  $term       Search term(searched in username, nickname and email)
     * @param   string  $orderBy    Field to order by
     * @param   int     $limit
     * @param   int     $offset
     * @return  array   Returns an array of the available users and false on error
     */
    function GetUsers($group = false, $superadmin = null, $status = null, $term = '', $orderBy = 'nickname',
        $limit = 0, $offset = null)
    {
        $fields = array(
            'id', 'id DESC',
            'username', 'username DESC',
            'nickname', 'nickname DESC', 'email'
        );
        if (!in_array($orderBy, $fields)) {
            $orderBy = 'username';
        }

        $usersTable = Jaws_ORM::getInstance()->table('users');
        $usersTable->select(
            'users.id:integer', 'username', 'email', 'url', 'nickname', 'fname', 'lname',
            'superadmin:boolean', 'language', 'theme', 'editor', 'timezone', 'users.status:integer'
        );
        if ($group !== false) {
            $usersTable->join('users_groups', 'users_groups.user_id', 'users.id');
            $usersTable->where('group_id', $group);
        }

        if (!is_null($superadmin)) {
            $usersTable->and()->where('superadmin', (bool)$superadmin);
        }

        if (!is_null($status)) {
            $usersTable->and()->where('status', (int)$status);
        }

        if (!empty($term)) {
            $term = Jaws_UTF8::strtolower($term);
            $usersTable->and()->openWhere('lower(username)', '%'.$term.'%', 'like');
            $usersTable->or()->where('lower(nickname)',      '%'.$term.'%', 'like');
            $usersTable->or()->closeWhere('lower(email)',    '%'.$term.'%', 'like');
        }

        $usersTable->orderBy('users.'.$orderBy);
        $usersTable->limit($limit, $offset);
        return $usersTable->getAll();
    }

    /**
     * Get count of users
     *
     * @access  public
     * @param   mixed   $group      Group ID of users
     * @param   mixed   $superadmin Type of user(null = all types, true = superadmin, false = normal)
     * @param   int     $status     user's status (null: all users, 0: disabled, 1: enabled, 2: not verified)
     * @param   string  $term       Search term(searched in username, nickname and email)
     * @return  int     Returns users count
     */
    function GetUsersCount($group = false, $superadmin = null, $status = null, $term = '')
    {
        $usersTable = Jaws_ORM::getInstance()->table('users');
        $usersTable->select('count(users.id):integer');
        if ($group !== false) {
            $usersTable->join('users_groups', 'users_groups.user_id', 'users.id');
            $usersTable->where('group_id', $group);
        }

        if (!is_null($superadmin)) {
            $usersTable->and()->where('superadmin', (bool)$superadmin);
        }

        if (!is_null($status)) {
            $usersTable->and()->where('status', (int)$status);
        }

        if (!empty($term)) {
            $term = Jaws_UTF8::strtolower($term);
            $usersTable->and()->openWhere('lower(username)', $term, 'like');
            $usersTable->or()->where('lower(nickname)',      $term, 'like');
            $usersTable->or()->closeWhere('lower(email)',    $term, 'like');
        }

        $result = $usersTable->getOne();
        if (Jaws_Error::IsError($result)) {
            return 0;
        }

        return (int)$result;
    }

    /**
     * Get a list of all groups
     *
     * @access  public
     * @param   bool    $enabled    enabled groups?(null for both)
     * @param   string  $orderBy    field to order by
     * @param   int     $limit
     * @param   int     $offset
     * @return  array   Returns an array of the available groups and false on error
     */
    function GetGroups($enabled = null, $orderBy = 'name', $limit = 0, $offset = null)
    {
        $fields  = array('id', 'name', 'title');
        if (!in_array($orderBy, $fields)) {
            $GLOBALS['log']->Log(JAWS_LOG_WARNING, _t('GLOBAL_ERROR_UNKNOWN_COLUMN'));
            $orderBy = 'name';
        }

        $groupsTable = Jaws_ORM::getInstance()->table('groups');
        $groupsTable->select('id:integer', 'name', 'title', 'description', 'enabled:boolean');
        if (!is_null($enabled)) {
            $groupsTable->where('enabled', (bool)$enabled);
        }
        $groupsTable->limit($limit, $offset)->orderBy($orderBy);
        return $groupsTable->getAll();
    }

    /**
     * Get count of groups
     *
     * @access  public
     * @param   bool    $enabled    enabled groups?(null for both)
     * @return  int     Returns groups count
     */
    function GetGroupsCount($enabled = null)
    {
        $groupsTable = Jaws_ORM::getInstance()->table('groups');
        $groupsTable->select('count(id):integer');
        if (!is_null($enabled)) {
            $groupsTable->where('enabled', (bool)$enabled);
        }
        $result = $groupsTable->getOne();
        if (Jaws_Error::IsError($result)) {
            return 0;
        }

        return (int)$result;
    }

    /**
     * Get a list of groups where a user is
     *
     * @access  public
     * @param   mixed  $user  Username or UserID
     * @return  array  Returns an array of the available groups and false on error
     */
    function GetGroupsOfUser($user)
    {
        $ugroupsTable = Jaws_ORM::getInstance()->table('users_groups');
        $ugroupsTable->select('groups.id:integer', 'groups.name');
        $ugroupsTable->join('users',  'users.id',  'users_groups.user_id');
        $ugroupsTable->join('groups', 'groups.id', 'users_groups.group_id');
        if (is_int($user)) {
            $ugroupsTable->where('users.id', $user);
        } else {
            $ugroupsTable->where('users.username', $user);
        }

        $result = $ugroupsTable->getAll();
        if (!Jaws_Error::IsError($result)) {
            $result = array_column($result, 'name', 'id');
        }

        return $result;
    }

    /**
     * Adds a new user
     *
     * @access  public
     * @param   array   $uData  User information data
     * @return  mixed   Returns user's id if user was successfully added, otherwise Jaws_Error
     */
    function AddUser($uData)
    {
        // username
        $uData['username'] = trim($uData['username'], '-_.@');
        if (!preg_match('/^[[:alnum:]-_.@]{3,32}$/', $uData['username'])) {
            return Jaws_Error::raiseError(
                _t('GLOBAL_ERROR_INVALID_USERNAME'),
                __FUNCTION__,
                JAWS_ERROR_NOTICE
            );
        }
        $uData['username'] = strtolower($uData['username']);

        // nickname
        $uData['nickname'] = $GLOBALS['app']->UTF8->trim($uData['nickname']);
        if (empty($uData['nickname'])) {
            return Jaws_Error::raiseError(
                _t('GLOBAL_ERROR_INCOMPLETE_FIELDS'),
                __FUNCTION__,
                JAWS_ERROR_NOTICE
            );
        }

        // email
        $uData['email'] = trim($uData['email']);
        if (!preg_match ("/^[[:alnum:]-_.]+\@[[:alnum:]-_.]+\.[[:alnum:]-_]+$/", $uData['email'])) {
            return Jaws_Error::raiseError(
                _t('GLOBAL_ERROR_INVALID_EMAIL_ADDRESS'),
                __FUNCTION__,
                JAWS_ERROR_NOTICE
            );
        }
        $uData['email'] = strtolower($uData['email']);

        // password & complexity
        $min = (int)$GLOBALS['app']->Registry->fetch('password_min_length', 'Policy');
        $min = ($min == 0)? 1 : $min;
        if ($uData['password'] == '' ||
            !preg_match("/^[[:print:]]{{$min},24}$/", $uData['password'])
        ) {
            return Jaws_Error::raiseError(
                _t('GLOBAL_ERROR_INVALID_PASSWORD', $min),
                __FUNCTION__,
                JAWS_ERROR_NOTICE
            );
        }

        if ($GLOBALS['app']->Registry->fetch('password_complexity', 'Policy') == 'yes') {
            if (!preg_match('/(?=.*[[:lower:]])(?=.*[[:upper:]])(?=.*[[:digit:]])(?=.*[[:punct:]])/',
                    $uData['password'])
            ) {
                return Jaws_Error::raiseError(
                    _t('GLOBAL_ERROR_INVALID_COMPLEXITY'),
                    __FUNCTION__,
                    JAWS_ERROR_NOTICE
                );
            }
        }

        $uData['last_update'] = time();
        $uData['registered_date'] = time();
        $uData['superadmin'] = isset($uData['superadmin'])? (bool)$uData['superadmin'] : false;
        $uData['status'] = isset($uData['status'])? (int)$uData['status'] : 1;
        $uData['concurrents'] = isset($uData['concurrents'])? (int)$uData['concurrents'] : 0;
        $uData['password'] = Jaws_User::GetHashedPassword($uData['password']);
        $uData['logon_hours'] = empty($uData['logon_hours'])? str_pad('', 42, 'F') : $uData['logon_hours'];
        if (isset($uData['expiry_date'])) {
            if (empty($uData['expiry_date'])) {
                $uData['expiry_date'] = 0;
            } else {
                $objDate = $GLOBALS['app']->loadDate();
                $uData['expiry_date'] = $GLOBALS['app']->UserTime2UTC(
                    (int)$objDate->ToBaseDate(preg_split('/[- :]/', $uData['expiry_date']), 'U')
                );
            }
        }

        $usersTable = Jaws_ORM::getInstance()->table('users');
        $result = $usersTable->insert($uData)->exec();
        if (Jaws_Error::IsError($result)) {
            if (MDB2_ERROR_CONSTRAINT == $result->getCode()) {
                $result->SetMessage(_t('USERS_USERS_ALREADY_EXISTS', $uData['username']));
            }
            return $result;
        }

        // Let everyone know a user has been added
        $res = $GLOBALS['app']->Listener->Shout('AddUser', $result);
        if (Jaws_Error::IsError($res)) {
            return false;
        }

        return $result;
    }

    /**
     * Update the info of an user
     *
     * @access  public
     * @param   int     $id     User's ID
     * @param   array   $uData  User information data
     * @return  bool    Returns true if user was successfully updated, false if not
     */
    function UpdateUser($id, $uData)
    {
        // unset invalid keys
        $invalids = array_diff(
            array_keys($uData),
            array('username', 'nickname', 'email', 'password',
                'superadmin', 'status', 'concurrents', 'logon_hours', 'expiry_date',
            )
        );
        foreach ($invalids as $invalid) {
            unset($uData[$invalid]);
        }

        // username
        $uData['username'] = trim($uData['username'], '-_.@');
        if (!preg_match('/^[[:alnum:]-_.@]{3,32}$/', $uData['username'])) {
            return Jaws_Error::raiseError(
                _t('GLOBAL_ERROR_INVALID_USERNAME'),
                __FUNCTION__,
                JAWS_ERROR_NOTICE
            );
        }
        $uData['username'] = strtolower($uData['username']);

        // nickname
        $uData['nickname'] = $GLOBALS['app']->UTF8->trim($uData['nickname']);
        if (empty($uData['nickname'])) {
            return Jaws_Error::raiseError(
                _t('GLOBAL_ERROR_INCOMPLETE_FIELDS'),
                __FUNCTION__,
                JAWS_ERROR_NOTICE
            );
        }

        // email
        $uData['email'] = trim($uData['email']);
        if (!preg_match ("/^[[:alnum:]-_.]+\@[[:alnum:]-_.]+\.[[:alnum:]-_]+$/", $uData['email'])) {
            return Jaws_Error::raiseError(
                _t('GLOBAL_ERROR_INVALID_EMAIL_ADDRESS'),
                __FUNCTION__,
                JAWS_ERROR_NOTICE
            );
        }
        $uData['email'] = strtolower($uData['email']);

        // password & complexity
        if (isset($uData['password']) && $uData['password'] !== '') {
            $min = (int)$GLOBALS['app']->Registry->fetch('password_min_length', 'Policy');
            if (!preg_match("/^[[:print:]]{{$min},24}$/", $uData['password'])) {
                return Jaws_Error::raiseError(
                    _t('GLOBAL_ERROR_INVALID_PASSWORD', $min),
                    __FUNCTION__,
                    JAWS_ERROR_NOTICE
                );
            }

            if ($GLOBALS['app']->Registry->fetch('password_complexity', 'Policy') == 'yes') {
                if (!preg_match('/(?=.*[[:lower:]])(?=.*[[:upper:]])(?=.*[[:digit:]])(?=.*[[:punct:]])/',
                        $uData['password'])
                ) {
                    return Jaws_Error::raiseError(
                        _t('GLOBAL_ERROR_INVALID_COMPLEXITY'),
                        __FUNCTION__,
                        JAWS_ERROR_NOTICE
                    );
                }
            }

            // password hash
            $uData['password'] = Jaws_User::GetHashedPassword($uData['password']);
            $uData['password_verify_key'] = '';
        } else {
            unset($uData['password']);
        }

        // get user information, we need it for rename avatar
        $user = Jaws_User::GetUser((int)$id, true, true);
        if (Jaws_Error::IsError($user) || empty($user)) {
            return false;
        }

        // set new avatar name if username changed
        if (($uData['username'] !== $user['username']) && !empty($user['avatar'])) {
            $fileinfo = pathinfo($user['avatar']);
            if (isset($fileinfo['extension']) && !empty($fileinfo['extension'])) {
                $uData['avatar'] = $uData['username']. '.'. $fileinfo['extension'];
            }
        }
        $uData['last_update'] = time();
        if (isset($uData['status'])) {
            $uData['status'] = (int)$uData['status'];
            if ($uData['status'] == 1) {
                $uData['email_verify_key'] = '';
            }
        }
        if (isset($uData['expiry_date'])) {
            if (empty($uData['expiry_date'])) {
                $uData['expiry_date'] = 0;
            } else {
                $objDate = $GLOBALS['app']->loadDate();
                $uData['expiry_date'] = $GLOBALS['app']->UserTime2UTC(
                    (int)$objDate->ToBaseDate(preg_split('/[- :]/', $uData['expiry_date']), 'U')
                );
            }
        }

        $usersTable = Jaws_ORM::getInstance()->table('users');
        $result = $usersTable->update($uData)->where('id', $id)->exec();
        if (Jaws_Error::IsError($result)) {
            if (MDB2_ERROR_CONSTRAINT == $result->getCode()) {
                $result->SetMessage(_t('USERS_USERS_ALREADY_EXISTS', $uData['username']));
            }
            return $result;
        }

        // rename avatar name
        if (isset($uData['avatar'])) {
            Jaws_Utils::Delete(AVATAR_PATH. $uData['avatar']);
            @rename(AVATAR_PATH. $user['avatar'],
                    AVATAR_PATH. $uData['avatar']);
        }

        if (isset($GLOBALS['app']->Session) && $GLOBALS['app']->Session->GetAttribute('user') == $id) {
            $GLOBALS['app']->Session->SetAttribute('username', $uData['username']);
            $GLOBALS['app']->Session->SetAttribute('nickname', $uData['nickname']);
            $GLOBALS['app']->Session->SetAttribute('email',    $uData['email']);
            if (isset($uData['avatar'])) {
                $GLOBALS['app']->Session->SetAttribute(
                    'avatar',
                    $this->GetAvatar($uData['avatar'], $uData['email'], 48, $uData['last_update'])
                );
            }
        }

        // Let everyone know a user has been updated
        $res = $GLOBALS['app']->Listener->Shout('UpdateUser', $id);
        if (Jaws_Error::IsError($res)) {
            return false;
        }

        return true;
    }

    /**
     * Update personal information of a user such as fname, lname, gender, etc..
     *
     * @access  public
     * @param   int     $id     User's ID
     * @param   array   $pData  Personal information data
     * @return  bool    Returns true on success, false on failure
     */
    function UpdatePersonal($id, $pData)
    {
        // unset invalid keys
        $invalids = array_diff(
            array_keys($pData),
            array('fname', 'lname', 'gender', 'dob',
                'url', 'signature', 'about', 'experiences',
                'occupations', 'interests', 'avatar', 'privacy',
            )
        );
        foreach ($invalids as $invalid) {
            unset($pData[$invalid]);
        }

        if (array_key_exists('avatar', $pData)) {
            // get user information
            $user = Jaws_User::GetUser((int)$id, true, true);
            if (Jaws_Error::IsError($user) || empty($user)) {
                return false;
            }

            if (!empty($user['avatar'])) {
                Jaws_Utils::Delete(AVATAR_PATH. $user['avatar']);
            }

            if (!empty($pData['avatar'])) {
                $fileinfo = pathinfo($pData['avatar']);
                if (isset($fileinfo['extension']) && !empty($fileinfo['extension'])) {
                    if (!in_array($fileinfo['extension'], array('gif','jpg','jpeg','png'))) {
                        return false;
                    } else {
                        $new_avatar = $user['username']. '.'. $fileinfo['extension'];
                        @rename(Jaws_Utils::upload_tmp_dir(). '/'. $pData['avatar'],
                                AVATAR_PATH. $new_avatar);
                        $pData['avatar'] = $new_avatar;
                    }
                }
            }
        }

        $pData['last_update'] = time();
        $usersTable = Jaws_ORM::getInstance()->table('users');
        $result = $usersTable->update($pData)->where('id', $id)->exec();
        if (Jaws_Error::IsError($result)) {
            return $result;
        }

        if (isset($GLOBALS['app']->Session) && $GLOBALS['app']->Session->GetAttribute('user') == $id) {
            foreach($pData as $k => $v) {
                if ($k == 'avatar') {
                    $GLOBALS['app']->Session->SetAttribute(
                        $k,
                        $this->GetAvatar($v, $user['email'], 48, $pData['last_update'])
                    );
                } else {
                    $GLOBALS['app']->Session->SetAttribute($k, $v);
                }
            }
        }

        return true;
    }

    /**
     * Update advanced options of a user such as language, theme, editor, etc..
     *
     * @access  public
     * @param   int     $id     User's ID
     * @param   array   $pData  Preferences information data
     * @return  bool    Returns true on success, false on failure
     */
    function UpdatePreferences($id, $pData)
    {
        // unset invalid keys
        $invalids = array_diff(array_keys($pData), array('language', 'theme', 'editor', 'timezone'));
        foreach ($invalids as $invalid) {
            unset($pData[$invalid]);
        }

        $pData['last_update'] = time();
        $usersTable = Jaws_ORM::getInstance()->table('users');
        $result = $usersTable->update($pData)->where('id', $id)->exec();
        if (Jaws_Error::IsError($result)) {
            return $result;
        }

        if (isset($GLOBALS['app']->Session) && $GLOBALS['app']->Session->GetAttribute('user') == $id) {
            foreach($pData as $k => $v) {
                $GLOBALS['app']->Session->SetAttribute($k, $v);
            }
        }

        return true;
    }

    /**
     * Update contacts information of a user such as country, city, address, postal_code, etc..
     *
     * @access  public
     * @param   int     $id     User's ID
     * @param   array   $cData  Contacts information data
     * @return  bool    Returns true on success, false on failure
     */
    function UpdateContacts($id, $cData)
    {
        // unset invalid keys
        $invalids = array_diff(
            array_keys($cData),
            array('country', 'city', 'address', 'postal_code', 'phone_number', 'mobile_number', 'fax_number')
        );
        foreach ($invalids as $invalid) {
            unset($cData[$invalid]);
        }

        $cData['last_update'] = time();
        $usersTable = Jaws_ORM::getInstance()->table('users');
        $result = $usersTable->update($cData)->where('id', $id)->exec();
        if (Jaws_Error::IsError($result)) {
            return $result;
        }

        return true;
    }

    /**
     * Adds a new group
     *
     * @access  public
     * @param   array   $gData  Group information data
     * @return  bool    Returns true if group  was sucessfully added, false if not
     */
    function AddGroup($gData)
    {
        // name
        $gData['name'] = trim($gData['name'], '-_.@');
        if (!preg_match('/^[[:alnum:]-_.@]{3,32}$/', $gData['name'])) {
            return Jaws_Error::raiseError(
                _t('GLOBAL_ERROR_INVALID_GROUPNAME'),
                __FUNCTION__,
                JAWS_ERROR_NOTICE
            );
        }
        $gData['name'] = strtolower($gData['name']);

        // title
        $gData['title'] = $GLOBALS['app']->UTF8->trim($gData['title']);
        if (empty($gData['title'])) {
            return Jaws_Error::raiseError(
                _t('GLOBAL_ERROR_INCOMPLETE_FIELDS'),
                __FUNCTION__,
                JAWS_ERROR_NOTICE
            );
        }

        $gData['removable'] = isset($gData['removable'])? (bool)$gData['removable'] : true;
        $gData['enabled'] = isset($gData['enabled'])? (bool)$gData['enabled'] : true;
        $groupsTable = Jaws_ORM::getInstance()->table('groups');
        $result = $groupsTable->insert($gData)->exec();
        if (Jaws_Error::IsError($result)) {
            if (MDB2_ERROR_CONSTRAINT == $result->getCode()) {
                $result->SetMessage(_t('USERS_GROUPS_ALREADY_EXISTS', $gData['name']));
            }
            return $result;
        }

        // Let everyone know a group has been added
        $res = $GLOBALS['app']->Listener->Shout('AddGroup', $result);
        if (Jaws_Error::IsError($res)) {
            //do nothing
        }

        return $result;
    }

    /**
     * Update the info of a group
     *
     * @access  public
     * @param   array   $gData  Group information data
     * @return  bool    Returns true if group was sucessfully updated, false if not
     */
    function UpdateGroup($id, $gData)
    {
        // unset invalid keys
        $invalids = array_diff(array_keys($gData), array('name', 'title', 'description', 'enabled'));
        foreach ($invalids as $invalid) {
            unset($gData[$invalid]);
        }

        // name
        $gData['name'] = trim($gData['name'], '-_.@');
        if (!preg_match('/^[[:alnum:]-_.@]{3,32}$/', $gData['name'])) {
            return Jaws_Error::raiseError(
                _t('GLOBAL_ERROR_INVALID_GROUPNAME'),
                __FUNCTION__,
                JAWS_ERROR_NOTICE
            );
        }
        $gData['name'] = strtolower($gData['name']);

        // title
        $gData['title'] = $GLOBALS['app']->UTF8->trim($gData['title']);
        if (empty($gData['title'])) {
            return Jaws_Error::raiseError(
                _t('GLOBAL_ERROR_INCOMPLETE_FIELDS'),
                __FUNCTION__,
                JAWS_ERROR_NOTICE
            );
        }

        if (isset($gData['enabled'])) {
            $gData['enabled'] = (bool)$gData['enabled'];
        }

        $groupsTable = Jaws_ORM::getInstance()->table('groups');
        $result = $groupsTable->update($gData)->where('id', $id)->exec();
        if (Jaws_Error::IsError($result)) {
            if (MDB2_ERROR_CONSTRAINT == $result->getCode()) {
                $result->SetMessage(_t('USERS_GROUPS_ALREADY_EXISTS', $gData['name']));
            }
            return $result;
        }

        // Let everyone know a group has been updated
        $res = $GLOBALS['app']->Listener->Shout('UpdateGroup', $id);
        if (Jaws_Error::IsError($res)) {
            //do nothing
        }

        return true;
    }

    /**
     * Deletes an user
     *
     * @access  public
     * @param   int     $id     User's ID
     * @return  bool    Returns true if user was successfully deleted, false if not
     */
    function DeleteUser($id)
    {
        $user = Jaws_User::GetUser((int)$id);
        if (Jaws_Error::IsError($user) || empty($user)) {
            return false;
        }

        $objORM = Jaws_ORM::getInstance();
        $result = $objORM->delete()->table('users')->where('id', $id)->exec();
        if (Jaws_Error::IsError($result)) {
            return false;
        }

        $result = $objORM->delete()->table('users_groups')->where('user_id', $id)->exec();
        if (Jaws_Error::IsError($result)) {
            return false;
        }

        $GLOBALS['app']->loadObject('Jaws_ACL', 'ACL');
        $GLOBALS['app']->ACL->deleteByUser($id);
        if (isset($GLOBALS['app']->Session)) {
            $res = $GLOBALS['app']->Session->DeleteUserSessions($id);
        }

        // Let everyone know that a user has been deleted
        $res = $GLOBALS['app']->Listener->Shout('DeleteUser', $id);
        if (Jaws_Error::IsError($res)) {
            return false;
        }

        return true;
    }

    /**
     * Deletes a group
     *
     * @access  public
     * @param   int     $id     Group's ID
     * @return  bool    Returns true if group was successfully deleted, false if not
     */
    function DeleteGroup($id)
    {
        $objORM = Jaws_ORM::getInstance();
        $objORM->delete()->table('groups');
        $objORM->where('id', $id)->and()->where('removable', true);
        $result = $objORM->exec();
        if (Jaws_Error::IsError($result)) {
            return false;
        }

        $result = $objORM->delete()->table('users_groups')->where('group_id', $id);
        if (Jaws_Error::IsError($result)) {
            return false;
        }

        $GLOBALS['app']->ACL->deleteByGroup($id);

        // Let everyone know a group has been deleted
        $res = $GLOBALS['app']->Listener->Shout('DeleteGroup', $id);
        if (Jaws_Error::IsError($res)) {
            return false;
        }

        return true;
    }

    /**
     * Adds an user to a group
     *
     * @access  public
     * @param   int     $user   User's ID
     * @param   int     $group  Group's ID
     * @return  bool    Returns true if user was sucessfully added to the group, false if not
     */
    function AddUserToGroup($user, $group)
    {
        $usrgrpTable = Jaws_ORM::getInstance()->table('users_groups');
        return $usrgrpTable->insert(array('user_id' => $user, 'group_id' => $group))->exec();
    }

    /**
     * Deletes an user from a group
     *
     * @access  public
     * @param   int     $user   User's ID
     * @param   int     $group  Group's ID
     * @return  bool    Returns true if user was sucessfully deleted from a group, false if not
     */
    function DeleteUserFromGroup($user, $group)
    {
        $usrgrpTable = Jaws_ORM::getInstance()->table('users_groups');
        $usrgrpTable->delete();
        $usrgrpTable->where('user_id', $user)->and()->where('group_id', $group);
        return $usrgrpTable->exec();
    }

    /**
     * Checks if a user is in a group
     *
     * @access  public
     * @param   int     $user   User's ID
     * @param   int     $group  Group's ID
     * @return  bool    Returns true if user in in the group or false if not
     */
    function UserIsInGroup($user, $group)
    {
        $usrgrpTable = Jaws_ORM::getInstance()->table('users_groups');
        $usrgrpTable->select('count(user_id):integer');
        $usrgrpTable->where('user_id', $user)->and()->where('group_id', $group);
        $howmany = $usrgrpTable->getOne();
        if (Jaws_Error::IsError($howmany)) {
            return false;
        }

        return (bool)$howmany;
    }

    /**
     * Update the email verification key of a certain user
     *
     * @access  public
     * @param   int     $uid  User's ID
     * @return  mixed   Generated key if success or Jaws_Error on failure
     */
    function UpdateEmailVerifyKey($uid)
    {
        $key = md5(uniqid(rand(), true)) . time() . floor(microtime()*1000);
        $usersTable = Jaws_ORM::getInstance()->table('users');
        $result = $usersTable->update(array('email_verify_key' => $key))->where('id', (int)$uid)->exec();
        if (Jaws_Error::IsError($result)) {
            return $result;
        }

        return $key;
    }

    /**
     * Update the change password verification key of a certain user
     *
     * @access  public
     * @param   int     $uid  User's ID
     * @return  mixed   Generated key if success or Jaws_Error on failure
     */
    function UpdatePasswordVerifyKey($uid)
    {
        $key = md5(uniqid(rand(), true)) . time() . floor(microtime()*1000);
        $usersTable = Jaws_ORM::getInstance()->table('users');
        $result = $usersTable->update(array('password_verify_key' => $key))->where('id', (int)$uid)->exec();
        if (Jaws_Error::IsError($result)) {
            return $result;
        }

        return $key;
    }

}