<?php
/**
 * Users URL maps
 *
 * @category   GadgetMaps
 * @package    Users
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2006-2013 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
$maps[] = array(
    'LoginBox',
    'users/login[/referrer/{referrer}]',
    array('referrer' => '.*')
);
$maps[] = array('Registration', 'users/registration');
$maps[] = array('Registered', 'users/registered');
$maps[] = array('Logout', 'users/logout');
$maps[] = array('Account', 'users/account');
$maps[] = array('Personal', 'users/personal');
$maps[] = array('Preferences', 'users/preferences');
$maps[] = array('Contacts', 'users/contacts');
$maps[] = array('Groups', 'users/groups');
$maps[] = array('AddGroupUI', 'users/add/group');
$maps[] = array('ManageGroup', 'users/manage/group/{id}');
$maps[] = array('ForgotLogin', 'users/forget');
$maps[] = array('ChangePassword', 'users/recover[/{key}]');
$maps[] = array('ActivateUser', 'users/activate[/{key}]');
$maps[] = array(
    'Profile',
    'users/{user}',
    array('user' => '[[:alnum:]-_.@]+')
);
