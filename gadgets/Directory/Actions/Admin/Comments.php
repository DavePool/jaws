<?php
/**
 * Directory Admin HTML file
 *
 * @category    GadgetAdmin
 * @package     Directory
 */
class Directory_Actions_Admin_Comments extends Directory_Actions_Admin_Common
{
    /**
     * Displays comments manager for Directory gadget
     *
     * @access  public
     * @return  string  XHTML template content
     */
    function ManageComments()
    {
        $this->gadget->CheckPermission('ManageComments');
        if (!Jaws_Gadget::IsGadgetInstalled('Comments')) {
            return Jaws_Header::Location(BASE_SCRIPT . '?reqGadget=Blog');
        }

        $cHTML = Jaws_Gadget::getInstance('Comments')->action->loadAdmin('Comments');
        return $cHTML->Comments($this->gadget->name, $this->MenuBar('Comments'));
    }
}