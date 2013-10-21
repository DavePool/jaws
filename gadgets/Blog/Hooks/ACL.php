<?php
/**
 * Blog - ACL hook
 *
 * @category    GadgetHook
 * @package     Blog
 * @author      Hamid Reza Aboutalebi <hamid@aboutalebi.com>
 * @copyright   2013 Jaws Development Group
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class Blog_Hooks_ACL extends Jaws_Gadget_Hook
{
    /**
     * Defines translate statements of dynamic ACL keys
     *
     * @access  public
     * @return  void
     */
    function Execute()
    {
        $language = $this->gadget->registry->fetch('admin_language', 'Settings');
        $cModel = $this->gadget->loadModel('Model', 'Categories');
        $items = $cModel->GetCategories();
        if (!Jaws_Error::IsError($items)) {
            foreach ($items as $item) {
                define(
                    strtoupper('_'. $language. '_'. $this->gadget->name. '_ACL_CATEGORYACCESS_'. $item['id']),
                    _t('BLOG_ACL_CATEGORY_ACCESS', $item['name'])
                );
                define(
                    strtoupper('_'. $language. '_'. $this->gadget->name. '_ACL_CATEGORYMANAGE_'. $item['id']),
                    _t('BLOG_ACL_CATEGORY_MANAGE', $item['name'])
                );
            }
        }
    }

}