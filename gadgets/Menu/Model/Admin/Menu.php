<?php
/**
 * Menu Gadget
 *
 * @category    GadgetModel
 * @package     Menu
 * @author      Jonathan Hernandez <ion@suavizado.com>
 * @author      Pablo Fischer <pablo@pablo.com.mx>
 * @author      Jon Wood <jon@substance-it.co.uk>
 * @author      Ali Fazelzadeh <afz@php.net>
 * @copyright   2004-2013 Jaws Development Group
 * @license     http://www.gnu.org/copyleft/lesser.html
 */
class Menu_Model_Admin_Menu extends Jaws_Gadget_Model
{
    /**
     * Inserta a new menu
     *
     * @access  public
     * @param    int     $pid
     * @param    int     $gid           Group ID
     * @param    string  $type
     * @param    string  $title
     * @param    string  $url
     * @param    string  $url_target
     * @param    string  $rank
     * @param    bool    $published     Published status
     * @param    string  $image
     * @return   bool    True on success or False on failure
     */
    function InsertMenu($pid, $gid, $type, $title, $url, $url_target, $rank, $published, $image)
    {
        $mData['pid']        = $pid;
        $mData['gid']        = $gid;
        $mData['menu_type']  = $type;
        $mData['title']      = $title;
        $mData['url']        = $url;
        $mData['url_target'] = $url_target;
        $mData['rank']       = $rank;
        $mData['published']  = (bool)$published;
        if (empty($image)) {
            $mData['image']  = null;
        } else {
            $image = preg_replace("/[^[:alnum:]_\.-]*/i", "", $image);
            $filename = Jaws_Utils::upload_tmp_dir(). '/'. $image;
            $mData['image']  = array('File://' . $filename, 'blob');
        }

        $menusTable = Jaws_ORM::getInstance()->table('menus');
        $mid = $menusTable->insert($mData)->exec();

        if (Jaws_Error::IsError($mid)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
            return false;
        }

        if (isset($filename)) {
            Jaws_Utils::Delete($filename);
        }

        $this->MoveMenu($mid, $gid, $gid, $pid, $pid, $rank, null);
        $GLOBALS['app']->Session->PushLastResponse($mid.'%%' . _t('MENU_NOTICE_MENU_CREATED'), RESPONSE_NOTICE);

        return true;
    }

    /**
     * Updates the menu
     *
     * @access  public
     * @param    int     $mid        menu ID
     * @param    int     $pid
     * @param    int     $gid        group ID
     * @param    string  $type
     * @param    string  $title
     * @param    string  $url
     * @param    string  $url_target
     * @param    string  $rank
     * @param    bool    $published     Published status
     * @param    string  $image
     * @return   bool    True on success or False on failure
     */
    function UpdateMenu($mid, $pid, $gid, $type, $title, $url, $url_target, $rank, $published, $image)
    {
        $model = $GLOBALS['app']->LoadGadget('Menu', 'Model', 'Menu');
        $oldMenu = $model->GetMenu($mid);
        if (Jaws_Error::IsError($oldMenu)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('MENU_ERROR_GET_MENUS'), RESPONSE_ERROR);
            return false;
        }

        $mData['pid']        = $pid;
        $mData['gid']        = $gid;
        $mData['menu_type']  = $type;
        $mData['title']      = $title;
        $mData['url']        = $url;
        $mData['url_target'] = $url_target;
        $mData['rank']       = $rank;
        $mData['published']  = (bool)$published;
        if ($image !== 'true') {
            if (empty($image)) {
                $mData['image'] = null;
            } else {
                $image = preg_replace("/[^[:alnum:]_\.-]*/i", "", $image);
                $filename = Jaws_Utils::upload_tmp_dir(). '/'. $image;
                $mData['image'] = array('File://' . $filename, 'blob');
            }
        }

        $menusTable = Jaws_ORM::getInstance()->table('menus');
        $res = $menusTable->update($mData)->where('id', $mid)->exec();
        if (Jaws_Error::IsError($res)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
            return false;
        }

        if (isset($filename)) {
            Jaws_Utils::Delete($filename);
        }

        $this->MoveMenu($mid, $gid, $oldMenu['gid'], $pid, $oldMenu['pid'], $rank, $oldMenu['rank']);
        $GLOBALS['app']->Session->PushLastResponse(_t('MENU_NOTICE_MENU_UPDATED'), RESPONSE_NOTICE);
        return true;
    }

    /**
     * Deletes the menu
     *
     * @access  public
     * @param   int     $mid    menu ID
     * @return  bool    True if query was successful and Jaws_Error on error
     */
    function DeleteMenu($mid)
    {
        $model = $GLOBALS['app']->LoadGadget('Menu', 'Model', 'Menu');
        $menu = $model->GetMenu($mid);
        if (Jaws_Error::IsError($menu)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
            return false;
        }

        if(isset($menu['id'])) {
            $menusTable = Jaws_ORM::getInstance()->table('menus');
            $pids = $menusTable->select('id')->where('pid', $mid)->fetchAll();
            if (Jaws_Error::IsError($pids)) {
                $GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
                return false;
            }

            foreach ($pids as $pid) {
                if (!$this->DeleteMenu($pid['id'])) {
                    return false;
                }
            }

            $this->MoveMenu($mid, $menu['gid'], $menu['gid'], $menu['pid'], $menu['pid'], 0xfff, $menu['rank']);
            $res = $menusTable->delete()->where('id', $mid)->exec();
            if (Jaws_Error::IsError($res)) {
                $GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
                return false;
            }
        }

        return true;
    }

    /**
     * Update publish status of all menu related the gadget
     *
     * @access  public
     * @param   string  $gadget     Gadget name
     * @param   bool    $published  Publish status
     * @return  bool    True if query was successful and Jaws_Error on error
     */
    function PublishGadgetMenus($gadget, $published)
    {
        $menusTable = Jaws_ORM::getInstance()->table('menus');
        $res = $menusTable->update(array('published'=>(bool)$published))->where('menu_type', $gadget)->exec();
        return $res;
    }

    /**
     * Delete all menu related the gadget
     *
     * @access  public
     * @param   string  $gadget Gadget name
     * @return  bool    True if query was successful and Jaws_Error on error
     */
    function DeleteGadgetMenus($gadget)
    {
        $menusTable = Jaws_ORM::getInstance()->table('menus');
        $mids = $menusTable->select('id')->where('menu_type', $gadget)->fetchAll();
        if (Jaws_Error::IsError($mids)) {
            return false;
        }

        foreach ($mids as $mid) {
            if (!$this->DeleteMenu($mid['id'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * function for change gid, pid and rank of menus
     *
     * @access  public
     * @param   int     $mid        menu ID
     * @param   int     $new_gid    new group ID
     * @param   int     $old_gid    old group ID
     * @param   int     $new_pid
     * @param   int     $old_pid
     * @param   string  $new_rank
     * @param   string  $old_rank
     * @return  bool    True on success or False on failure
     */
    function MoveMenu($mid, $new_gid, $old_gid, $new_pid, $old_pid, $new_rank, $old_rank)
    {
        $menusTable = Jaws_ORM::getInstance()->table('menus');
        if ($new_gid != $old_gid) {
            // set gid of submenu items
            $model = $GLOBALS['app']->LoadGadget('Menu', 'Model', 'Menu');
            $sub_menus = $model->GetLevelsMenus($mid);
            if (!Jaws_Error::IsError($sub_menus)) {
                foreach ($sub_menus as $menu) {
                    $menusTable->update(array('gid' => $new_gid))->where('id', $menu['id'])->or();
                    $res = $menusTable->where('pid', $menu['id'])->exec();
                    if (Jaws_Error::IsError($res)) {
                        $GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
                        return false;
                    }
                }
            }
        }

        if (($new_pid != $old_pid) || ($new_gid != $old_gid)) {
            // resort menu items in old_pid
            $res = $menusTable->update(
                array(
                    'rank' => $menusTable->expr('rank - ?', 1)
                )
            )->where('pid', $old_pid)->and()->where('gid', $old_gid)->and()->where('rank', $old_rank, '>')->exec();

            if (Jaws_Error::IsError($res)) {
                $GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
                return false;
            }

            // resort menu items in new_pid
            $menusTable->update(
                array(
                    'rank' => $menusTable->expr('rank + ?', 1)
                )
            )->where('id', $mid, '<>')->and()->where('gid', $new_gid)->and()->where('pid', $new_pid);
            $res = $menusTable->and()->where('rank', $new_rank, '>=')->exec();
            if (Jaws_Error::IsError($res)) {
                $GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
                return false;
            }
        } elseif (empty($old_rank)) {
            $menusTable->update(
                array(
                    'rank' => $menusTable->expr('rank + ?', 1)
                )
            )->where('id', $mid, '<>')->and()->where('gid', $new_gid)->and()->where('pid', $new_pid);
            $res = $menusTable->and()->where('rank', $new_rank, '>=')->exec();
            if (Jaws_Error::IsError($res)) {
                $GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
                return false;
            }
        } elseif ($new_rank > $old_rank) {
            // resort menu items in new_pid
            $menusTable->update(
                array(
                    'rank' => $menusTable->expr('rank - ?', 1)
                )
            )->where('id', $mid, '<>')->and()->where('gid', $new_gid)->and()->where('pid', $new_pid);
            $res = $menusTable->and()->where('rank', $old_rank, '>')->and()->where('rank', $new_rank, '<=')->exec();
            if (Jaws_Error::IsError($res)) {
                $GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
                return false;
            }
        } elseif ($new_rank < $old_rank) {
            // resort menu items in new_pid
            $menusTable->update(
                array(
                    'rank' => $menusTable->expr('rank + ?', 1)
                )
            )->where('id', $mid, '<>')->and()->where('gid', $new_gid)->and()->where('pid', $new_pid);
            $res = $menusTable->and()->where('rank', $new_rank, '>=')->and()->where('rank', $old_rank, '<')->exec();
            if (Jaws_Error::IsError($res)) {
                $GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
                return false;
            }
        }

        //$GLOBALS['app']->Session->PushLastResponse(_t('MENU_NOTICE_MENU_MOVED'), RESPONSE_NOTICE);
        return true;
    }

    /**
     * function for get menus tree
     *
     * @access  public
     * @param   int     $pid
     * @param   int     $gid            Group ID
     * @param   string  $excluded_mid
     * @param   string  $result         Result reference
     * @param   array   $menu_str
     * @return  bool    True on success or False on failure
     */
    function GetParentMenus($pid, $gid, $excluded_mid, &$result, $menu_str = '')
    {
        $model = $GLOBALS['app']->LoadGadget('Menu', 'Model', 'Menu');
        $parents = $model->GetLevelsMenus($pid, $gid);
        if (empty($parents)) return false;
        foreach ($parents as $parent) {
            if ($parent['id'] == $excluded_mid) continue;
            $result[] = array('pid'=> $parent['id'],
                'title'=> $menu_str . '\\' . $parent['title']);
            $this->GetParentMenus($parent['id'], $gid, $excluded_mid, $result, $menu_str . '\\' . $parent['title']);
        }
        return true;
    }
}
