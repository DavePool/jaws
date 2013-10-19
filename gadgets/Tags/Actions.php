<?php
/**
 * Tags Actions
 *
 * @category    GadgetActions
 * @package     Tags
 * @author      Mojtaba Ebrahimi <ebrahimi@zehneziba.ir>
 * @copyright   2013 Jaws Development Group
 * @license     http://www.gnu.org/copyleft/lesser.html
 */
$actions = array();

$actions['TagCloud'] = array(
    'layout' => true,
    'parametric' => true,
    'file'   => 'Tags',
);
$actions['ViewTag'] = array(
    'normal' => true,
    'file'   => 'Tags',
);
$actions['ManageTags'] = array(
    'normal' => true,
    'file'   => 'ManageTags',
);
$actions['EditTagUI'] = array(
    'normal' => true,
    'file'   => 'ManageTags',
);
$actions['UpdateTag'] = array(
    'standalone' => true,
    'file'       => 'ManageTags',
);
$actions['DeleteTags'] = array(
    'standalone' => true,
    'file'       => 'ManageTags',
);
$actions['MergeTags'] = array(
    'standalone' => true,
    'file'       => 'ManageTags',
);
