<!-- BEGIN JawsConfig --><?php
/**
 * JawsConfig.php - Configuration variables
 *
 * @author      Jonathan Hernandez <ion@suavizado.com>
 * @author      Ali Fazelzadeh <afz@php.net>
 * @copyright   2004-2020 Jaws Development Group
 */
// Path where Jaws is installed
define('ROOT_PATH', realpath($_SERVER['DOCUMENT_ROOT']) . DIRECTORY_SEPARATOR);
define('JAWS_PATH', substr(dirname(__DIR__) . DIRECTORY_SEPARATOR, strlen(ROOT_PATH)));
define('ROOT_JAWS_PATH', ROOT_PATH . JAWS_PATH);
<!-- BEGIN data_path -->
define('DATA_PATH', '{{data_path}}');
define('ROOT_DATA_PATH', ROOT_PATH . DATA_PATH);
<!-- END data_path -->
<!-- BEGIN base_data_path -->
define('BASE_DATA_PATH', '{{base_data_path}}');
define('ROOT_BASE_DATA_PATH', ROOT_PATH . BASE_DATA_PATH);
<!-- END base_data_path -->
<!-- BEGIN themes_path -->
define('THEMES_PATH', '{{themes_path}}');
define('ROOT_THEMES_PATH', ROOT_PATH . THEMES_PATH);
<!-- END themes_path -->
<!-- BEGIN base_themes_path -->
define('BASE_THEMES_PATH', '{{base_themes_path}}');
define('ROOT_BASE_THEMES_PATH', ROOT_PATH . BASE_THEMES_PATH);
<!-- END base_themes_path -->
<!-- BEGIN cache_path -->
define('CACHE_PATH', '{{cache_path}}');
define('ROOT_CACHE_PATH', ROOT_PATH . CACHE_PATH);
<!-- END cache_path -->

$db = array(); //DONT RENAME/DELETE THIS VARIABLE!!
/**
 * DB Configuration
 *
 * In this section you configure some params of your DB connection, such as
 * username, password, name, host and driver.
 * The prefix is optional, just make sure it has an empty value
 */
$db['driver']   = '{{db_driver}}';
$db['host']     = '{{db_host}}';
$db['port']     = '{{db_port}}';
$db['user']     = '{{db_user}}';
$db['password'] = '{{db_pass}}';
$db['isdba']    = '{{db_isdba}}';
$db['path']     = '{{db_path}}';
$db['name']     = '{{db_name}}';
$db['prefix']   = '{{db_prefix}}';

/**
 * Logs
 *
 * If you want to enable logging Jaws, maybe to track the errors, or to debug a good
 * idea is to configure/enable it.
 *
 * Debug: false Disable log
 *        0     Disabled
 *        1     Emergency log level
 *        2     Alert log and utmost levels
 *        3     Critical log and utmost levels
 *        4     Error log and utmost levels
 *        5     Warning log and utmost levels
 *        6     Notice log and utmost levels
 *        7     Info log and utmost levels
 *        8     Debug log and utmost levels
 */
define('LOG_ACTIVATED', {{log_level}});

/**
 * Log Method
 *
 * How do you want to print/save the log?. Currently we just support:
 *
 *    LogToFile: Logs the message to a specified file.
 *     Options:
 *      file (required): File where you want to save data, IMPORTANT. PHP needs write-access to that file
 *      maxlines (optional): How many lines will contain the file. Default = 500
 *      rotatelimit (optional): How many rotated files will be created (i.e. jaws.log.1, jaws.log.2 etc). Default = 1
 *     Example:
 *        $GLOBALS['logger']['method'] = 'LogToFile';
 *        $GLOBALS['logger']['options'] = array();
 *        $GLOBALS['logger']['options']['file'] = "/tmp/jaws.log";
 *        $GLOBALS['logger']['options']['size'] = 2097152;
 *
 *    LogToSyslog: Logs the message to the syslog, you can find the log of this blog just by looking to the tag you
 *    define
 *      Options:
 *       indent: String ident is added to each message. Default: "Jaws_Log"
 *      Example:
 *        $GLOBALS['logger']['method'] = 'LogToSyslog';
 *        $GLOBALS['logger']['options'] = array();
 *        $GLOBALS['logger']['options']['indent'] = 'Jaws_Log';
 *
 *    LogToWindow: All log messages are printed to new window
 *       Example:
 *        $GLOBALS['logger']['method'] = 'LogToWindow';
 *
 *    LogToApache': Prints the message to the apache error log file
 *       Example:
 *        $GLOBALS['logger']['method'] = 'LogToApache';
 *
 *    LogToFirebug: Prints the messages into the Firebugs console (The firebug extensions is required)
 *       Example:
 *        $GLOBALS['logger']['method'] = 'LogToFirebug';
 */

$GLOBALS['logger']['method'] = 'LogToFile';
$GLOBALS['logger']['options'] = array();
$GLOBALS['logger']['options']['file'] = ROOT_DATA_PATH . 'logs/.jaws.log';
$GLOBALS['logger']['options']['size'] = 2097152;
<!-- END JawsConfig -->
