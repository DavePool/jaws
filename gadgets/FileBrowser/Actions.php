<?php
/**
 * FileBrowser Actions file
 *
 * @category    GadgetActions
 * @package     FileBrowser
 * @author      Pablo Fischer <pablo@pablo.com.mx>
 * @author      Ali Fazelzadeh <afz@php.net>
 * @copyright   2004-2013 Jaws Development Group
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
$actions = array();

$actions['Display'] = array(
    'normal' => true,
    'file' => 'Files',
);
$actions['FileInfo'] = array(
    'normal' => true,
    'file' => 'Files',
);
$actions['Download'] = array(
    'standalone' => true,
    'file' => 'Files',
);
$actions['InitialFolder'] = array(
    'layout' => true,
    'file' => 'Directory',
);