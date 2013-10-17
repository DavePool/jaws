<?php
/**
 * Tags - URL List gadget hook
 *
 * @category    GadgetHook
 * @package     Tags
 * @author      Mojtaba Ebrahimi <ebrahimi@zehneziba.ir>
 * @copyright   2013 Jaws Development Group
 * @license     http://www.gnu.org/copyleft/lesser.html
 */
class Tags_Hooks_Menu extends Jaws_Gadget_Hook
{
    /**
     * Returns an array with all available items the Menu gadget can use
     *
     * @access  public
     * @return  array   URLs array
     */
    function Execute()
    {
        $urls = array();
        $site_language = $this->gadget->registry->fetch('site_language', 'Settings');
        $model = $GLOBALS['app']->loadGadget('Tags', 'Model', 'Tags');
        $gadgets = $model->GetTagRelativeGadgets();

        $urls[] = array(
            'url' => $this->gadget->urlMap('ManageTags'),
            'title' => _t('TAGS_MANAGE_TAGS')
        );

        $urls[] = array(
            'url' => $this->gadget->urlMap('TagCloud'),
            'title' => _t('TAGS_TAG_CLOUD', _t('GLOBAL_ALL'))
        );

        foreach ($gadgets as $gadget) {
            $GLOBALS['app']->Translate->LoadTranslation($gadget, JAWS_COMPONENT_GADGET, $site_language);
            $urls[] = array('url' => $this->gadget->urlMap('TagCloud', array('gname' => $gadget)),
                            'title' => _t('TAGS_TAG_CLOUD', _t(strtoupper($gadget) . '_NAME')));
        }

        return $urls;
    }

}