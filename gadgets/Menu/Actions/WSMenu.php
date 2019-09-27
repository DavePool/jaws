<?php
/**
 * Menu Gadget
 *
 * @category    Gadget
 * @package     Menu
 */
class Menu_Actions_WSMenu extends Jaws_Gadget_Action
{
    /**
     * Returns the menus with their items
     *
     * @access  public
     * @param   int     $gid    Menu group ID
     * @return  array   Menu array
     */
    function Menu($gid = 0)
    {
        $gModel = $this->gadget->model->load('Group');
        $group = $gModel->GetGroups($gid);
        if (Jaws_Error::IsError($group) || empty($group) || !$group['published']) {
            return array();
        }

        return $this->GetNextLevel($group['id'], 0, -1);
    }

    /**
     * Returns the next level of parent menu
     *
     * @access  public
     * @param   int     $gid        Group ID
     * @param   int     $pid        Parent Menu
     * @param   int     $level      Menu level
     * @return  array   Menu array with sub menu items
     */
    function GetNextLevel($gid, $pid, $level)
    {
        $level++;
        $menus = $this->gadget->model->load('Menu')->GetLevelsMenus($pid, $gid, true);
        if (Jaws_Error::IsError($menus) || empty($menus)) {
            return array();
        }


        $availableMenus = array();
        $logged = $GLOBALS['app']->Session->Logged();
        foreach ($menus as $i => $menu) {
            // is menu viewable?
            if ($menu['status'] == 0) {
                continue;
            }
            if ($menu['status'] != 1) {
                if ($logged xor $menu['status'] == Menu_Info::STATUS_LOGGED_IN) {
                    continue;
                }
            }

            // check default ACL
            if ($menu['type'] != 'url') {
                if (!Jaws_Gadget::IsGadgetInstalled($menu['type'])) {
                    continue;
                }

                if (!$GLOBALS['app']->Session->GetPermission($menu['type'], 'default')) {
                    continue;
                }

                // check permission
                if (!empty($menu['permission'])) {
                    $permission = unserialize($menu['permission']);
                    if (isset($permission['gadget'])) {
                        if (!$GLOBALS['app']->Session->GetPermission($permission['gadget'], 'default')) {
                            continue;
                        }
                    } else {
                        $permission['gadget'] = $menu['type'];
                    }

                    if (!$GLOBALS['app']->Session->GetPermission(
                        $permission['gadget'],
                        $permission['key'],
                        $permission['subkey']
                    )) {
                        continue;
                    }
                }
            }

            // replace menu variables
            if (!empty($menu['variables'])) {
                $objGadget = Jaws_Gadget::getInstance($menu['type']);
                if (Jaws_Error::IsError($objGadget)) {
                    continue;
                }

                $params = array();
                $vars = unserialize($menu['variables']);
                $url  = unserialize($menu['url']);
                foreach ($vars as $var => $val) {
                    $val = $GLOBALS['app']->Session->GetAttribute($val);
                    if (is_null($val)) {
                        continue 2;
                    }
                    // set url variables
                    foreach ($url['params'] as $param => $str) {
                        $params[$param] = Jaws_UTF8::str_replace('{' . $var . '}', $val, $str);
                    }
                    // set title variables
                    $menu['title'] = Jaws_UTF8::str_replace('{' . $var . '}', $val, $menu['title']);
                }

                // generate url map
                $menu['url'] = $objGadget->urlMap(
                    $url['action'],
                    $params,
                    array(),
                    isset($url['gadget'])? $url['gadget'] : ''
                );
            }

            $menu['url'] = $menu['url']?: 'javascript:void(0);';
            //get sub level menus
            $menu['submenu'] = $this->GetNextLevel($gid, $menu['id'], $level);
            unset(
                $menu['id'], $menu['gid'], $menu['type'], $menu['variables'],
                $menu['permission'], $menu['target'], $menu['status'], $menu['image']
            );
            $availableMenus[] = $menu;
        }

        return $availableMenus;
    }

}