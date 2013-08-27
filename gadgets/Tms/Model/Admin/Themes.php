<?php
/**
 * TMS (Theme Management System) Gadget
 *
 * @category   GadgetModel
 * @package    TMS
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2007-2013 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class Tms_Model_Admin_Themes extends Jaws_Gadget_Model
{
    /**
     * Creates a .zip file of the theme in themes/ directory
     *
     * @access  public
     * @param   string  $theme      Name of the theme
     * @param   string  $srcDir     Source directory
     * @param   string  $destDir    Target directory
     * @param   bool    $copy_example_to_repository  If copy example.png too or not
     * @return  bool    Returns true if:
     *                    - Theme exists
     *                    - Theme exists and could be packed
     *                  Returns false if:
     *                    - Theme doesn't exist
     *                    - Theme doesn't exists and couldn't be packed
     */
    function packTheme($theme, $srcDir, $destDir, $copy_example_to_repository = true)
    {
        $themeSrc = $srcDir. '/'. $theme;
        if (!is_dir($themeSrc)) {
            return new Jaws_Error(_t('TMS_ERROR_THEME_DOES_NOT_EXISTS', $theme), _t('TMS_NAME'));
        }

        if (!Jaws_Utils::is_writable($destDir)) {
            return new Jaws_Error(_t('GLOBAL_ERROR_FAILED_DIRECTORY_UNWRITABLE', $destDir),
                                  _t('TMS_NAME'));
        }

        $themeDest = $destDir. '/'. $theme. '.zip';
        //If file exists.. delete it
        if (file_exists($themeDest)) {
            @unlink($themeDest);
        }

        require_once 'File/Archive.php';
        $res = File_Archive::extract(File_Archive::read($themeSrc, $theme),
                                     File_Archive::toArchive($themeDest,
                                                             File_Archive::toFiles()
                                                            )
                                    );
        if (PEAR::isError($res)) {
            return new Jaws_Error(_t('TMS_ERROR_COULD_NOT_PACK_THEME'), _t('TMS_NAME'));
        }
        Jaws_Utils::chmod($themeDest);

        if ($copy_example_to_repository) {
            //Copy image to repository/images
            if (file_exists($srcDir . '/example.png')) {
                @copy($srcDir. '/example.png', JAWS_DATA. "themes/repository/images/$theme.png");
                Jaws_Utils::chmod(JAWS_DATA . 'themes/repository/images/' . $theme . '.png');
            }
        }

        return $themeDest;
    }
}