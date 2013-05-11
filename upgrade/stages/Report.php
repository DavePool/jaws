<?php
/**
 * Report Stage
 *
 * @category   Application
 * @package    UpgradeStage
 * @author     Jon Wood <jon@substance-it.co.uk>
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2005-2013 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class Upgrader_Report extends JawsUpgraderStage
{
    /**
     * Builds the upgrader page.
     *
     * @access  public
     * @return  string A block of valid XHTML to display the status of old/current jaws versions
     */
    function Display()
    {
        include_once JAWS_PATH.'include/Jaws/DB.php';
        $GLOBALS['db'] = new Jaws_DB($_SESSION['upgrade']['Database']);

        require_once JAWS_PATH . 'include/Jaws.php';
        $GLOBALS['app'] = new Jaws();
        $GLOBALS['app']->loadObject('Jaws_Registry', 'Registry');
        $GLOBALS['app']->Registry->Init();
        $JawsInstalledVersion = $GLOBALS['app']->Registry->fetch('version');

        $supportedversions = array(
                                   array(
                                         'version'   => '0.9.0',
                                         'stage'     => '5',
                                         ),
                                   array(
                                         'version'   => '0.8.18',
                                         'stage'     => null,
                                         ),
                                   );

        _log(JAWS_LOG_DEBUG,"Checking/Reporting previous missed installations");
        $tpl = new Jaws_Template();
        $tpl->Load('display.html', 'stages/Report/templates');
        $tpl->SetBlock('Report');

        $tpl->setVariable('lbl_info',    _t('UPGRADE_REPORT_INFO', JAWS_VERSION));
        $tpl->setVariable('lbl_message', _t('UPGRADE_REPORT_MESSAGE'));
        $tpl->SetVariable('next',        _t('GLOBAL_NEXT'));

        $versions_to_upgrade = 0;
        $_SESSION['upgrade']['stagedVersions'] = array();
        foreach($supportedversions as $supported) {
            $tpl->SetBlock('Report/versions');
            $tpl->SetBlock('Report/versions/version');
            $tpl->SetVariable('description', $supported['version']);

            $_SESSION['upgrade']['versions'][$supported['version']] = array(
                        'version' => $supported['version'],
                        'stage' =>   $supported['stage'],
                        'file' =>    (isset($supported['file'])? $supported['file'] : ''),
                        'script' =>  (isset($supported['script'])? $supported['script'] : '')
            );

            if (version_compare($supported['version'], $JawsInstalledVersion, '<=')) {
                if ($supported['version'] == JAWS_VERSION) {
                    $tpl->SetVariable('status', _t('UPGRADE_REPORT_NO_NEED_CURRENT'));
                    _log(JAWS_LOG_DEBUG,$supported['version']." does not requires upgrade(is current)");
                } else {
                    $tpl->SetVariable('status', _t('UPGRADE_REPORT_NO_NEED'));
                    _log(JAWS_LOG_DEBUG,$supported['version']." does not requires upgrade");
                }
                $_SESSION['upgrade']['versions'][$supported['version']]['status'] = true;
            } else {
                $tpl->SetVariable('status', _t('UPGRADE_REPORT_NEED'));
                $_SESSION['upgrade']['versions'][$supported['version']]['status'] = false;
                $versions_to_upgrade++;
                _log(JAWS_LOG_DEBUG,$supported['version']." requires upgrade");
                $_SESSION['upgrade']['versions'][$supported['version']]['status'] = false;
            }

            if (!is_null($supported['stage'])) {
                $_SESSION['upgrade']['stagedVersions'][] = $supported['version'];
            }

            $tpl->ParseBlock('Report/versions/version');
            $tpl->ParseBlock('Report/versions');
        }
        $_SESSION['upgrade']['versions_to_upgrade'] = $versions_to_upgrade;

        $tpl->ParseBlock('Report');
        arsort($_SESSION['upgrade']['versions']);
        krsort($_SESSION['upgrade']['stagedVersions']);
        /**
         * Are we maitaining the last version? the current JAWS_VERSION?
         */
        _log(JAWS_LOG_DEBUG,"Checking if current version (".JAWS_VERSION.") really requires an upgrade");
        $lastSupportedVersion = $supportedversions[0]['version'];
        if ($lastSupportedVersion != JAWS_VERSION) {
            if (version_compare($lastSupportedVersion, JAWS_VERSION) === -1) {
                _log(JAWS_LOG_DEBUG,"Current version (".JAWS_VERSION.") does not require an upgrade");
                $_SESSION['upgrade']['upgradeLast'] = true;
            }
        }
        return $tpl->Get();
    }

    /**
     * Does any actions required to finish the stage, such as DB queries.
     *
     * @access  public
     * @return  bool|Jaws_Error  Either true on success, or a Jaws_Error
     *                          containing the reason for failure.
     */
    function Run()
    {
        if (is_dir(JAWS_DATA. "languages")) {
            // transform customized translated files
            $rootfiles = array('Global.php', 'Date.php', 'Install.php', 'Upgrade.php');
            $languages = scandir(JAWS_DATA. 'languages');
            foreach ($languages as $lang) {
                if($lang == '.' || $lang == '..') {
                    continue;
                }

                $ostr = "define('_".strtoupper($lang).'_';
                $nstr = "define('_".strtoupper($lang).'_DATA_';

                // gadgets
                if (is_dir(JAWS_DATA. "languages/$lang/gadgets")) {
                    $lGadgets = scandir(JAWS_DATA. "languages/$lang/gadgets");
                    foreach ($lGadgets as $lGadget) {
                        if($lGadget == '.' || $lGadget == '..') {
                            continue;
                        }

                        $fstring = @file_get_contents(JAWS_DATA. "languages/$lang/gadgets/$lGadget");
                        $fstring = strtr($fstring, array($nstr => $nstr, $ostr => $nstr));
                        @file_put_contents(JAWS_DATA. "languages/$lang/gadgets/$lGadget", $fstring);
                    }
                }

                // plugins
                if (is_dir(JAWS_DATA. "languages/$lang/plugins")) {
                    $lPlugins = scandir(JAWS_DATA. "languages/$lang/plugins");
                    foreach ($lPlugins as $lPlugin) {
                        if($lPlugin == '.' || $lPlugin == '..') {
                            continue;
                        }

                        $fstring = @file_get_contents(JAWS_DATA. "languages/$lang/plugins/$lPlugin");
                        $fstring = strtr($fstring, array($nstr => $nstr, $ostr => $nstr));
                        @file_put_contents(JAWS_DATA. "languages/$lang/plugins/$lPlugin", $fstring);
                    }
                }
            }

            // others
            foreach ($rootfiles as $rfile) {
                if (file_exists(JAWS_DATA. "languages/$lang/$rfile")) {
                    $fstring = @file_get_contents(JAWS_DATA. "languages/$lang/$rfile");
                    $fstring = strtr($fstring, array($nstr => $nstr, $ostr => $nstr));
                    @file_put_contents(JAWS_DATA. "languages/$lang/$rfile", $fstring);
                }
            }
        }

        foreach($_SESSION['upgrade']['stagedVersions'] as $stagedVersion) {
            if (!$_SESSION['upgrade']['versions'][$stagedVersion]['status']) {
                if ($_SESSION['upgrade']['stage'] < $_SESSION['upgrade']['versions'][$stagedVersion]['stage']) {
                    return true;
                } else {
                    $_SESSION['upgrade']['stage']++;
                }
            } else {
                $_SESSION['upgrade']['stage']++;
            }
        }

        return true;
    }

}