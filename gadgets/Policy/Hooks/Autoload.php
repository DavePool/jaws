<?php
/**
 * Policy Gadget - Autoload
 *
 * @category   GadgetAutoload
 * @package    Policy
 * @author     Amir Mohammad Saied <amir@gluegadget.com>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2007-2013 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class Policy_Hooks_Autoload extends Jaws_Gadget_Hook
{
    /**
     * Autoload load method
     *
     */
    function Execute()
    {
        $this->BlockIPHook();
        $this->BlockAgentHook();
    }

    /**
     * Block IP hook
     *
     * @access  private
     */
    function BlockIPHook()
    {
        $model  = $this->gadget->loadModel('IP');
        $res    = $model->IsIPBlocked($_SERVER['REMOTE_ADDR']);
        if ($res) {
            require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
            echo Jaws_HTTPError::Get(403);
            exit;
        }
    }

    /**
     * Block Agent hook
     *
     * @access  private
     */
    function BlockAgentHook()
    {
        $model = $this->gadget->loadModel('Agent');
        $res   = $model->IsAgentBlocked($_SERVER["HTTP_USER_AGENT"]);
        if ($res) {
            require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
            echo Jaws_HTTPError::Get(403);
            exit;
        }
    }
}
