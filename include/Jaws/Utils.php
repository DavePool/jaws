<?php
/**
 * Some utils functions. Random functions
 *
 * @category   JawsType
 * @package    Core
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2005-2012 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
define('JAWS_OS_WIN', strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
class Jaws_Utils
{
    /**
     * Change the color of a row from a given color
     *
     * @param  string  $color  Original color(so we don't return the same color)
     * @return string  New color
     * @access public
     */
    function RowColor($color)
    {
        if ($color == '#fff') {
            return '#eee';
        }

        return '#fff';
    }

    /**
     * Create a random text
     *
     * @access  public
     * @param   int     $length Random text length
     * @return  string  The random string
     */
    function RandomText($length = 8)
    {
        include_once 'Text/Password.php';
        $word = Text_Password::create($length, 'unpronounceable', 'alphanumeric');
        return $word;
    }

    /**
     * Convert a number in bytes, kilobytes,...
     *
     * @access  public
     * @param   int     $num
     * @return  string  The converted number in string
     */
    function FormatSize($num)
    {
        $unims = array("B", "KB", "MB", "GB", "TB");
        $i = 0;
        while ($num >= 1024) {
            $i++;
            $num = $num/1024;
        }

        return number_format($num, 2). " ". $unims[$i];
    }

    /**
     * Get base url
     *
     * @access  public
     * @param   string  suffix for add to base url
     * @param   string  rel_url relative url
     * @return  string  url of base script
     */
    function getBaseURL($suffix = '', $rel_url = false)
    {
        static $site_url;
        if (!isset($site_url)) {
            $site_url = array();
            $site_url['scheme'] = (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on')? 'https' : 'http';
            //$site_url['host'] = $_SERVER['SERVER_NAME'];
            $site_url['host'] = reset(explode(':', $_SERVER['HTTP_HOST']));
            $site_url['port'] = $_SERVER['SERVER_PORT']==80? '' : (':'.$_SERVER['SERVER_PORT']);

            $path = strip_tags($_SERVER['PHP_SELF']);
            if (false === stripos($path, BASE_SCRIPT)) {
                $path = strip_tags($_SERVER['SCRIPT_NAME']);
                if (false === stripos($path, BASE_SCRIPT)) {
                    $pInfo = isset($_SERVER['PATH_INFO'])? $_SERVER['PATH_INFO'] : '';
                    $pInfo = (empty($pInfo) && isset($_SERVER['ORIG_PATH_INFO']))? $_SERVER['ORIG_PATH_INFO'] : '';
                    $pInfo = (empty($pInfo) && isset($_ENV['PATH_INFO']))? $_ENV['PATH_INFO'] : '';
                    $pInfo = (empty($pInfo) && isset($_ENV['ORIG_PATH_INFO']))? $_ENV['ORIG_PATH_INFO'] : '';
                    $pInfo = strip_tags($pInfo);
                    if (!empty($pInfo)) {
                        $path = substr($path, 0, strpos($path, $pInfo)+1);
                    }
                }
            }

            $site_url['path'] = substr($path, 0, stripos($path, BASE_SCRIPT)-1);
            $site_url['path'] = explode('/', $site_url['path']);
            $site_url['path'] = implode('/', array_map('rawurlencode', $site_url['path']));
            $site_url['path'] = rtrim($site_url['path'], '/');
        }

        $url = $site_url['path'];
        if (!$rel_url) {
            $url = $site_url['scheme']. '://'. $site_url['host']. $site_url['port']. $url;
        }

        return $url . (is_bool($suffix)? '' : $suffix);
    }

    /**
     * Get request url
     *
     * @access  public
     * @param   boolean rel_url relative or full URL
     * @return  string  get url without base url
     */
    function getRequestURL($rel_url = true)
    {
        static $uri;
        if (!isset($uri)) {
            if (isset($_SERVER['REQUEST_URI']) && !empty($_SERVER['REQUEST_URI'])) {
                $uri = $_SERVER['REQUEST_URI'];
            } elseif (isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING'])) {
                $uri = $_SERVER['PHP_SELF'] . '?' .$_SERVER['QUERY_STRING'];
            } else {
                $uri = '';
            }

            $rel_base = Jaws_Utils::getBaseURL('', true);
            $uri = htmlspecialchars(urldecode($uri), ENT_NOQUOTES, 'UTF-8');
            $uri = substr($uri, strlen($rel_base));
        }

        return $rel_url? ltrim($uri, '/') : (Jaws_Utils::getBaseURL() .$uri);
    }

    /**
     * is directory writeable?
     *
     * @access  public
     * @param   string  $path directory path
     * @return  boolean True/False
     */
    function is_writable($path)
    {
        clearstatcache();
        $path = rtrim($path, "\\/");
        if (!file_exists($path)) {
            return false;
        }

        /* Take care of the safe mode limitations if safe_mode=1 */
        if (ini_get('safe_mode')) {
            if (is_dir($path)) {
                $tmpdir = $path.'/'. uniqid(mt_rand());
                if (!Jaws_Utils::mkdir($tmpdir)) {
                    return false;
                }
                return Jaws_Utils::delete($tmpdir);
            } else {
                if (false === $file = @fopen($pat, 'r+')) {
                    return false;
                }
                return fclose($file);
            }
        }

        return is_writeable($path);
    }

    /**
     * Write a string to a file
     * @access  public
     * @see http://www.php.net/file_put_contents
     */
    function file_put_contents($file, $data, $flags = null, $resource_context = null)
    {
        $res = @file_put_contents($file, $data, $flags, $resource_context);
        if ($res !== false) {
            $mode = @fileperms(dirname($file));
            if (!empty($mode)) {
                Jaws_Utils::chmod($file, $mode);
            }
        }

        return $res;
    }

    /**
     * Change file/directory mode
     *
     * @access  public
     * @param   string  $path file/directory path
     * @param   integer $mode see php chmod() function
     * @return  boolean True/False
     */
    function chmod($path, $mode = null)
    {
        $result = false;
        if (is_null($mode)) {
            $php_as_owner = (function_exists('posix_getuid') && posix_getuid() === @fileowner($path));
            $php_as_group = (function_exists('posix_getgid') && posix_getgid() === @filegroup($path));
            if (is_dir($path)) {
                $mode = $php_as_owner? 0755 : ($php_as_group? 0775 : 0777);
            } else {
                $mode = $php_as_owner? 0644 : ($php_as_group? 0664 : 0666);
            }
        }

        $mode = is_int($mode)? $mode : octdec($mode);
        $mask = umask(0);
        /* Take care of the safe mode limitations if safe_mode=1 */
        if (ini_get('safe_mode')) {
            /* GID check */
            if (ini_get('safe_mode_gid')) {
                if (@filegroup($path) == getmygid()) {
                    $result = @chmod($path, $mode);
                }
            } else {
                if (@fileowner($path) == @getmyuid()) {
                    $result = @chmod($path, $mode);
                }
            }
        } else {
            $result = @chmod($path, $mode);
        }

        umask($mask);
        return $result;
    }

    /**
     * Make directory
     *
     * @access  public
     * @param   string  $path   Path to the directory
     * @param   integer $mode   see php chmod() function
     * @return  boolean True/False
     */
    function mkdir($path, $recursive = 0, $mode = null)
    {
        $result = true;
        if (!file_exists($path) || !is_dir($path)) {
            if ($recursive && !file_exists(dirname($path))) {
                $recursive--;
                Jaws_Utils::mkdir(dirname($path), $recursive, $mode);
            }
            $result = @mkdir($path);
        }

        if (empty($mode)) {
            $mode = @fileperms(dirname($path));
        }

        if ($result && !empty($mode)) {
            Jaws_Utils::chmod($path, $mode);
        }

        return $result;
    }

    /**
     * Makes a copy of the source file or directory to dest
     *
     * @access  public
     * @param   text    $source Path to the source file or directory
     * @param   text    $dest   The destination path
     * @param   integer $mode   see php chmod() function
     * @see http://www.php.net/copy
     */
    function copy($source, $dest, $mode = null)
    {
        $result = false;
        if (file_exists($source)) {
            if (is_dir($source)) {
                if (false !== $hDir = @opendir($source)) {
                    if ($result = Jaws_Utils::mkdir($dest, 0, $mode)) {
                        while(false !== ($file = @readdir($hDir))) {
                            if($file == '.' || $file == '..') {
                                continue;
                            }

                            $result = Jaws_Utils::copy($source. DIRECTORY_SEPARATOR . $file,
                                                       $dest. DIRECTORY_SEPARATOR . $file,
                                                       $mode);
                            if (!$result) {
                                break;
                            }
                        }
                    }

                    closedir($hDir);
                }
            } else {
                $result = @copy($source, $dest);
                if ($result && !empty($mode)) {
                    Jaws_Utils::chmod($dest, $mode);
                }
            }
        }

        return $result;
    }

    /**
     * Delete directories and files
     *
     * @access  public
     * @param   boolean $dirs_include
     * @param   boolean $self_include
     * @see http://www.php.net/rmdir & http://www.php.net/unlink
     */
    function delete($path, $dirs_include = true, $self_include = true)
    {
        if (!file_exists($path)) {
            return true;
        }

        if (is_file($path) || is_link($path)) {
            // unlink can't delete read-only files in windows os
            if (JAWS_OS_WIN) {
                @chmod($path, 0777); 
            }

            return @unlink($path);
        }

        if (false !== $files = @scandir($path)) {
            foreach ($files as $file) {
                if($file == '.' || $file == '..') {
                    continue;
                }

                if (!Jaws_Utils::delete($path. DIRECTORY_SEPARATOR. $file, $dirs_include)) {
                    return false;
                }
            }
        }

        if($dirs_include && $self_include) {
            return @rmdir($path);
        }

        return true;
    }

    /**
     * get upload temp directory
     *
     */
    function upload_tmp_dir()
    {
        $upload_dir = ini_get('upload_tmp_dir')? ini_get('upload_tmp_dir') : sys_get_temp_dir();
        return rtrim($upload_dir, "\\/");
    }

    /**
     * Upload Files
     *
     * @access  public
     * @param   array   $files        $_FILES array
     * param    string  $dest         destination directory(include end directory separator)
     * param    string  $allowFormats permitted file format
     * param    string  $denyFormats  not permitted file format
     * @param   boolean $overwrite    overwite file if exist
     * @param   boolean $move_files   moving or only copying files. this param avail for non-uploaded files
     * @param   integer $max_size     max size of file
     * @return  boolean True/False
     */
    function UploadFiles($files, $dest, $allowFormats = '', $denyFormats = '',
                         $overwrite = true, $move_files = true, $max_size = null)
    {
        if (empty($files) || !is_array($files)) {
            return false;
        }

        $result = array();
        if (isset($files['tmp_name'])) {
            $files = array($files);
        }

        $dest = rtrim($dest, "\\/"). DIRECTORY_SEPARATOR;
        $allowFormats = array_filter(explode(',', $allowFormats));
        $denyFormats  = array_filter(explode(',', $denyFormats));
        foreach($files as $key => $listFiles) {
            if (!is_array($listFiles['tmp_name'])) {
                $listFiles = array_map(create_function('$item','return array($item);'), $listFiles);
            }

            for($i=0; $i < count($listFiles['name']); ++$i) {
                $file = array();
                $file['name']     = $listFiles['name'][$i];
                $file['tmp_name'] = $listFiles['tmp_name'][$i];
                $file['size']     = $listFiles['size'][$i];
                if (isset($listFiles['error'])) {
                    $file['error']    = $listFiles['error'][$i];
                }

                if (empty($file['tmp_name'])) {
                    continue;
                }

                $filename = isset($file['name']) ? $file['name'] : '';
                if (isset($file['error']) && !empty($file['error'])) {
                    return new Jaws_Error(_t('GLOBAL_ERROR_UPLOAD_'.$file['error']),
                                          __FUNCTION__);
                }

                $filename = strtolower(preg_replace("/[^[:alnum:]_\.-]*/i", "", $filename));
                $fileinfo = pathinfo($filename);
                if (isset($fileinfo['extension']) && !empty($fileinfo['extension'])) {
                    if (in_array($fileinfo['extension'], $denyFormats) ||
                       (!empty($allowFormats) && !in_array($fileinfo['extension'], $allowFormats)))
                    {
                        return new Jaws_Error(_t('GLOBAL_ERROR_UPLOAD_INVALID_FORMAT', $filename),
                                              __FUNCTION__);
                    }
                }

                if (empty($fileinfo['filename']) || (!$overwrite && file_exists($dest . $filename))) {
                    $filename = time() . '_' . $filename;
                }
                $uploadfile = $dest . $filename;

                if (is_uploaded_file($file['tmp_name'])) {
                    if (!move_uploaded_file($file['tmp_name'], $uploadfile)) {
                        return new Jaws_Error(_t('GLOBAL_ERROR_UPLOAD', $filename),
                                              __FUNCTION__);
                    }
                } else {
                    // On windows-systems we can't rename a file to an existing destination,
                    // So we first delete destination file
                    if (file_exists($uploadfile)) {
                        @unlink($uploadfile);
                    }
                    $res = $move_files? @rename($file['tmp_name'], $uploadfile) : @copy($file['tmp_name'], $uploadfile);
                    if (!$res) {
                        return new Jaws_Error(_t('GLOBAL_ERROR_UPLOAD', $filename),
                                              __FUNCTION__);
                    }
                }

                // Check if the file has been altered or is corrupted
                if (filesize($uploadfile) != $file['size']) {
                    @unlink($uploadfile);
                    return new Jaws_Error(_t('GLOBAL_ERROR_UPLOAD_CORRUPTED', $filename),
                                             __FUNCTION__);
                }

                Jaws_Utils::chmod($uploadfile);
                $result[$key][$i] = $filename;
            }
        }

        return $result;
    }

    /**
     * Extract archive Files
     *
     * @access  public
     * @param   array   $files        $_FILES array
     * param    string  $dest         destination directory(include end directory separator)
     * param    string  $extractToDir create separate directory for extracted files
     * @param   boolean $overwrite    overwite directory if exist
     * @param   integer $max_size     max size of file
     * @return  boolean True/False
     */
    function ExtractFiles($files, $dest, $extractToDir = true, $overwrite = true, $max_size = null)
    {
        if (empty($files) || !is_array($files)) {
            return new Jaws_Error(_t('GLOBAL_ERROR_UPLOAD'),
                                     __FUNCTION__);
        }

        $result = array();
        if (isset($files['name'])) {
            $files = array($files);
        }

        require_once 'File/Archive.php';
        foreach($files as $key => $file) {
            if ((isset($file['error']) && !empty($file['error'])) || !isset($file['name'])) {
                return new Jaws_Error(_t('GLOBAL_ERROR_UPLOAD_'.$file['error']),
                                      __FUNCTION__);
            }

            if (empty($file['tmp_name'])) {
                continue;
            }

            $ext = strrchr($file['name'], '.');
            $filename = substr($file['name'], 0, -strlen($ext));
            if (false !== stristr($filename, '.tar')) {
                $filename = substr($filename, 0, strrpos($filename, '.'));
                switch ($ext) {
                    case '.gz':
                        $ext = '.tgz';
                        break;

                    case '.bz2':
                    case '.bzip2':
                        $ext = '.tbz';
                        break;

                    default:
                        $ext = '.tar' . $ext;
                }
            }

            $ext = strtolower(substr($ext, 1));
            if (!File_Archive::isKnownExtension($ext)) {
                return new Jaws_Error(_t('GLOBAL_ERROR_UPLOAD_INVALID_FORMAT', $file['name']),
                                      __FUNCTION__);
            }

            if ($extractToDir) {
                $dest = $dest . $filename;
            }

            if ($extractToDir && !Jaws_Utils::mkdir($dest)) {
                return new Jaws_Error(_t('GLOBAL_ERROR_FAILED_CREATING_DIR', $dest),
                                      __FUNCTION__);
            }

            if (!Jaws_Utils::is_writable($dest)) {
                return new Jaws_Error(_t('GLOBAL_ERROR_FAILED_DIRECTORY_UNWRITABLE', $dest),
                                      __FUNCTION__);
            }

            $archive = File_Archive::readArchive($ext, $file['tmp_name']);
            if (PEAR::isError($archive)) {
                return new Jaws_Error($archive->getMessage(),
                                      __FUNCTION__);
            }
            $writer = File_Archive::_convertToWriter($dest);
            $result = $archive->extract($writer);
            if (PEAR::isError($result)) {
                return new Jaws_Error($result->getMessage(),
                                      __FUNCTION__);
            }

            //@unlink($file['tmp_name']);
        }

        return true;
    }

    /**
     * Get information of remote IP address
     *
     * @access  public
     * @return  array   include proxy and client ip addresses
     */
    function GetRemoteAddress()
    {
        static $proxy, $client;

        if (!isset($proxy) and !isset($client)) {
            if (!empty($_SERVER) && isset($_SERVER['REMOTE_ADDR'])) {
                $direct = $_SERVER['REMOTE_ADDR'];
            } else if (!empty($_ENV) && isset($_ENV['REMOTE_ADDR'])) {
                $direct = $_ENV['REMOTE_ADDR'];
            } else if (@getenv('REMOTE_ADDR')) {
                $direct = getenv('REMOTE_ADDR');
            }

            $proxy_flags = array('HTTP_CLIENT_IP',
                                 'HTTP_X_FORWARDED_FOR',
                                 'HTTP_X_FORWARDED',
                                 'HTTP_FORWARDED_FOR',
                                 'HTTP_FORWARDED',
                                 'HTTP_VIA',
                                 'HTTP_X_COMING_FROM',
                                 'HTTP_COMING_FROM',
                                );

            $client = '';
            foreach ($proxy_flags as $flag) {
                if (!empty($_SERVER) && isset($_SERVER[$flag])) {
                    $client = $_SERVER[$flag];
                    break;
                } else if (!empty($_ENV) && isset($_ENV[$flag])) {
                    $client = $_ENV[$flag];
                    break;
                } else if (@getenv($flag)) {
                    $client = getenv($flag);
                    break;
                }
            }

            if (empty($client)) {
                $proxy  = '';
                $client = $direct;
            } else {
                $is_ip = preg_match('|^([0-9]{1,3}\.){3,3}[0-9]{1,3}|', $client, $regs);
                $client = $is_ip? $regs[0] : '';
                $proxy  = $direct;
            }

        }

        return array('proxy' => $proxy, 'client' => $client);
    }

    /**
     * Returns an array of languages
     *
     * @access  public
     * @return  array   A list of available languages
     */
    function GetLanguagesList($use_data_lang = true)
    {
        static $langs;
        if (!isset($langs)) {
            $langs = array();
            $langdir = JAWS_PATH . 'languages/';
            $files = @scandir($langdir);
            if ($files !== false) {
                foreach($files as $file) {
                    if ($file{0} != '.'  && is_dir($langdir . $file)) {
                        if (is_file($langdir.$file.'/FullName')) {
                            $fullname = implode('', @file($langdir.$file.'/FullName'));
                            if (!empty($fullname)) {
                                $langs[$file] = $fullname;
                            }
                        }
                    }
                }
                asort($langs);
            }
        }

        if ($use_data_lang) {
            static $dLangs;
            if (!isset($dLangs)) {
                $dLangs = array();
                $langdir = JAWS_DATA . 'languages/';
                $files = @scandir($langdir);
                if ($files !== false) {
                    foreach($files as $file) {
                        if ($file{0} != '.'  && is_dir($langdir . $file)) {
                            if (is_file($langdir.$file.'/FullName')) {
                                $fullname = implode('', @file($langdir.$file.'/FullName'));
                                if (!empty($fullname)) {
                                    $dLangs[$file] = $fullname;
                                }
                            }
                        }
                    }
                }
                $dLangs = array_unique(array_merge($langs, $dLangs));
                asort($dLangs);
            }

            return $dLangs;
        }

        return $langs;
    }

    /**
     * Get a list of the themes the site is running
     *
     * @access  public
     * @return  array   A list of themes(filenames)
     */
    function GetThemesList($include_base_themes = true)
    {
        /**
         * is theme valid?
         */
        if (!function_exists('is_vaild_theme')) {
            function is_vaild_theme(&$item, $key, $path)
            {
                if ($item{0} == '.' ||
                    !is_dir($path . $item) ||
                    !file_exists($path . $item . DIRECTORY_SEPARATOR. 'layout.html'))
                {
                    $item = '';
                }
                return true;
            }
        }

        static $pThemes;
        if (!isset($pThemes)) {
            $theme_path = JAWS_DATA . 'themes/';
            $pThemes = scandir($theme_path);
            array_walk($pThemes, 'is_vaild_theme', $theme_path);
            $pThemes = array_filter($pThemes);
            sort($pThemes);
            $pThemes = array_flip($pThemes);
            foreach($pThemes as $theme => $key) {
                $pThemes[$theme] = array('name'     => $theme,
                                         'desc'     => '',
                                         'image'    => '',
                                         'local'    => true,
                                         'version'  => '0.1',
                                         'license'  => '',
                                         'authors'  => array());
                if (file_exists($theme_path. $theme. '/example.png')) {
                    $pThemes[$theme]['image'] = $GLOBALS['app']->getDataURL("themes/$theme/example.png");
                }

                $iniFile = $theme_path. $theme. '/Info.ini';
                if (file_exists($iniFile)) {
                    $tInfo = @parse_ini_file($iniFile, true);
                    if (!empty($tInfo) && array_key_exists('info', $tInfo)) {
                        $pThemes[$theme] = array_merge($pThemes[$theme], $tInfo['info']);
                    }
                }
            }
        }

        if ($include_base_themes) {
            static $themes;
            if (!isset($themes)) {
                $themes = array();
                if (JAWS_DATA != JAWS_BASE_DATA) {
                    $theme_path = JAWS_BASE_DATA . 'themes/';
                    $themes = scandir($theme_path);
                    array_walk($themes, 'is_vaild_theme', $theme_path);
                    $themes = array_filter($themes);
                    sort($themes);
                    $themes = array_flip($themes);
                    foreach($themes as $theme => $key) {
                        $themes[$theme] = array('name'     => $theme,
                                                 'desc'     => '',
                                                 'image'    => '',
                                                 'local'    => false,
                                                 'version'  => '0.1',
                                                 'license'  => '',
                                                 'authors'  => array());
                        if (file_exists($theme_path. $theme. '/example.png')) {
                            $themes[$theme]['image'] = $GLOBALS['app']->getDataURL("themes/$theme/example.png", true, true);
                        }
                        $iniFile = $theme_path. $theme. '/Info.ini';
                        if (file_exists($iniFile)) {
                            $tInfo = @parse_ini_file($iniFile, true);
                            if (!empty($tInfo) && array_key_exists('info', $tInfo)) {
                                $themes[$theme] = array_merge($themes[$theme], $tInfo['info']);
                            }
                        }
                    }
                }
                $themes = array_merge($pThemes, $themes);
            }

            return $themes;
        }

        return $pThemes;
    }

    /**
     * Providing download file
     *
     * @access  public
     * @return  string  file content
     */
    function Download($fpath, $fname)
    {
        if (false === $fhandle = @fopen($fpath, 'rb')) {
            return false;
        }

        $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $fsize  = @filesize($fpath);
        $fstart = 0;
        $fstop  = $fsize - 1;

        if (isset($_SERVER['HTTP_RANGE']) && !empty($_SERVER['HTTP_RANGE'])) {
            $frange = explode('-', substr($_SERVER['HTTP_RANGE'], strlen('bytes=')));
            $fstart = (int) $frange[0];
            if (isset($frange[1]) && ($frange[1] > 0)) {
                $fstop = (int) $frange[1];
            }

            header($xss->filter($_SERVER['SERVER_PROTOCOL'])." 206 Partial Content");
            header('Content-Range: bytes '.$fstart.'-'.$fstop.'/'.$fsize);
        }

        // ranges unit
        header("Accept-Ranges: bytes");
        // browser must download file from server instead of cache
        header("Expires: 0");
        header("Pragma: public");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        // force download dialog
        header("Content-Type: application/force-download");
        // set data type, size and filename
        header("Content-Disposition: attachment; filename=\"{$fname}\"");
        header("Content-Transfer-Encoding: binary");
        header('Content-Length: '.($fstop - $fstart + 1));

        //jump to start position
        if ($fstart > 0) {
            fseek($fhandle, $fstart);
        }

        $fposition = $fstart;
        while (!feof($fhandle) &&
               !connection_aborted() &&
               (connection_status() == 0) &&
               $fposition <= $fstop)
        {
            $fposition += 64*1024; //64 kbytes
            print(fread($fhandle, 64*1024));
            flush();
        }
        fclose($fhandle);
        return true;
    }

}