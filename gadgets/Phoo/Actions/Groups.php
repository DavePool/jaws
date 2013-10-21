<?php
/**
 * Phoo Gadget
 *
 * @category   GadgetAdmin
 * @package    Phoo
 * @author     Hamid Reza Aboutalebi <hamid@aboutalebi.com>
 * @copyright  2013 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class Phoo_Actions_Groups extends Jaws_Gadget_HTML
{
    /**
     * Displays phoo groups layout
     *
     * @access  public
     * @return  string  XHTML template content
     */
    function Groups()
    {
        $tpl = $this->gadget->loadTemplate('Groups.html');
        $tpl->SetBlock('groups');
        $tpl->SetVariable('title', _t('PHOO_ALBUMS_GROUPS'));

        $gModel = $this->gadget->loadModel('Groups');
        $groups = $gModel->GetGroups();
        if (is_array($groups)) {
            foreach ($groups as $group) {
                $url = $GLOBALS['app']->Map->GetURLFor('Phoo', 'AlbumList', array('group' => $group['id']));
                $tpl->SetBlock('groups/group');
                $tpl->SetVariable('url', $url);
                $tpl->SetVariable('name', $group['name']);
                $tpl->ParseBlock('groups/group');
            }
        }

        $tpl->ParseBlock('groups');
        return $tpl->Get();
    }
}