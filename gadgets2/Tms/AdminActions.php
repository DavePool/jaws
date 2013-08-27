<?php
/**
 * TMS (Theme Management System) Gadget actions
 *
 * @category    GadgetActions
 * @package     TMS
 * @author      Ali Fazelzadeh <afz@php.net>
 * @copyright   2012-2013 Jaws Development Group
 * @license     http://www.gnu.org/copyleft/lesser.html
 */
$actions = array();

$actions['Admin'] = array(
    'normal' => true,
    'file' => 'Themes'
);
$actions['Themes'] = array(
    'normal' => true,
    'file' => 'Themes'
);
$actions['UploadTheme'] = array(
    'normal' => true,
    'file' => 'Themes'
);
$actions['DownloadTheme'] = array(
    'standalone' => true,
    'file' => 'Themes'
);
