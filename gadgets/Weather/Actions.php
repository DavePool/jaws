<?php
/**
 * Weather Actions file
 *
 * @category    GadgetActions
 * @package     Weather
 * @author      Pablo Fischer <pablo@pablo.com.mx>
 * @author      Ali Fazelzadeh <afz@php.net>
 * @copyright   2004-2013 Jaws Development Group
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
$actions = array();

$actions['RegionWeather'] = array(
    'normal' => true,
    'layout' => true,
    'parametric' => true,
    'file' => 'RegionWeather',
);
$actions['AllRegionsWeather'] = array(
    'normal' => true,
    'layout' => true,
    'file' => 'RegionWeather',
);

/**
 * Admin actions
 */
$admin_actions['Regions'] = array(
    'normal' => true,
    'file' => 'Regions',
);
$admin_actions['Properties'] = array(
    'normal' => true,
    'file' => 'Properties',
);
$admin_actions['GetGoogleMapImage'] = array(
    'standalone' => true,
    'file' => 'GoogleMap',
);
