<?php
/**
 * Contact Actions file
 *
 * @category    GadgetActions
 * @package     Directory
 * @author      Mohsen Khahani <mkhahani@gmail.com>
 * @copyright   2013 Jaws Development Group
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
$actions = array();

/* Public Actions */
$actions['Directory'] = array(
    'normal' => true,
    'layout' => true,
    'file' => 'Directory'
);
$actions['GetFiles'] = array(
    'standalone' => true,
    'file' => 'Directory'
);
$actions['GetFile'] = array(
    'standalone' => true,
    'file' => 'Directory'
);
$actions['GetPath'] = array(
    'standalone' => true,
    'file' => 'Directory'
);
$actions['GetTree'] = array(
    'standalone' => true,
    'file' => 'Directory'
);
$actions['Move'] = array(
    'standalone' => true,
    'file' => 'Directory'
);
$actions['Search'] = array(
    'standalone' => true,
    'file' => 'Directory'
);

/* Directory Actions */
$actions['DirectoryForm'] = array(
    'standalone' => true,
    'file' => 'Directories'
);
$actions['CreateDirectory'] = array(
    'standalone' => true,
    'file' => 'Directories'
);
$actions['UpdateDirectory'] = array(
    'standalone' => true,
    'file' => 'Directories'
);
$actions['DeleteDirectory'] = array(
    'standalone' => true,
    'file' => 'Directories'
);

/* File Actions */
$actions['FileForm'] = array(
    'standalone' => true,
    'file' => 'Files'
);
$actions['CreateFile'] = array(
    'standalone' => true,
    'file' => 'Files'
);
$actions['UpdateFile'] = array(
    'standalone' => true,
    'file' => 'Files'
);
$actions['DeleteFile'] = array(
    'standalone' => true,
    'file' => 'Files'
);
$actions['PublishFile'] = array(
    'standalone' => true,
    'file' => 'Files'
);
$actions['UploadFile'] = array(
    'standalone' => true,
    'file' => 'Files'
);
$actions['DownloadFile'] = array(
    'standalone' => true,
    'file' => 'Files'
);
$actions['GetDownloadURL'] = array(
    'standalone' => true,
    'file' => 'Files'
);
$actions['PlayMedia'] = array(
    'standalone' => true,
    'file' => 'Files'
);

/* Sharing Actions */
$actions['GetUsers'] = array(
    'standalone' => true,
    'file' => 'Share'
);
$actions['GetShareForm'] = array(
    'standalone' => true,
    'file' => 'Share'
);
$actions['GetFileUsers'] = array(
    'standalone' => true,
    'file' => 'Share'
);
$actions['UpdateFileUsers'] = array(
    'standalone' => true,
    'file' => 'Share'
);
