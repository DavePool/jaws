<?php
/**
 * Notification Notify event
 *
 * @category    Gadget
 * @package     Notification
 */
class Notification_Events_Notify extends Jaws_Gadget_Event
{
    /**
     * Grabs notification and sends it out via available drivers
     *
     * @access  public
     * @param   string  $shouter    The shouting gadget
     * @param   array   $params     [user, group, title, summary, description, priority, send]
     * @return  bool
     */
    function Execute($shouter, $params)
    {
        if (isset($params['send']) && $params['send'] === false) {
            return false;
        }

        $model = $this->gadget->model->load('Notification');
        $gadget = empty($params['gadget']) ? $shouter : $params['gadget'];

        $params['time'] = !isset($params['time']) ? time() : $params['time'];

        // detect if time = 0 then must delete the notifications
        if ($params['time'] < 0) {
            return $model->DeleteNotificationsByKey($params['key']);
        }

        $users = array();
        $jUser = new Jaws_User;
        if (isset($params['group']) && !empty($params['group'])) {
            $group_users = $jUser->GetGroupUsers($params['group'], true, false, true);
            if (!Jaws_Error::IsError($group_users) && !empty($group_users)) {
                $users = $group_users;
            }
        }

        if (isset($params['emails']) && !empty($params['emails'])) {
            foreach ($params['emails'] as $email) {
                if (!empty($email)) {
                    $users[] = array('email' => $email);
                }
            }
        }

        if (isset($params['mobiles']) && !empty($params['mobiles'])) {
            foreach ($params['mobiles'] as $mobile) {
                if (!empty($mobile)) {
                    $users[] = array('mobile_number' => $mobile);
                }
            }
        }

        if (isset($params['user']) && !empty($params['user'])) {
            $user = $jUser->GetUser($params['user'], true, false, true);
            if (!Jaws_Error::IsError($user) && !empty($user)) {
                $users[] = $user;
            }
        }

        // FIXME: increase performance for getting users data
        if (isset($params['users']) && !empty($params['users'])) {
            foreach ($params['users'] as $userId) {
                if (!empty($userId)) {
                    $user = $jUser->GetUser($userId, true, false, true);
                    if (!Jaws_Error::IsError($user) && !empty($user)) {
                        $users[] = $user;
                    }
                }
            }
        }

        // add webpush subscription to users array
        foreach ($users as $user) {
            if (array_key_exists('id', $user)) {
                $sessions = $this->app->session->getSessions($user['id']);
                if (!Jaws_Error::isError($sessions) && !empty($sessions)) {
                    foreach ($sessions as $session) {
                        if (!empty(@unserialize($session['webpush']))) {
                            $users[] = array('webpush' => $session['webpush']);
                        }
                    }
                }
            }
        }

        if (empty($users)) {
            return false;
        }

        // get gadget driver settings
        $configuration = unserialize($this->gadget->registry->fetch('configuration'));
        $notificationsEmails  = array();
        $notificationsMobiles = array();
        $notificationsWebPush = array();

        // notification for this gadget was disabled
        if (empty($configuration[$gadget])) {
            return false;
        }

        if ($configuration[$gadget] == 1) {
            $notificationsEmails  = array_filter(array_column($users, 'email'));
            $notificationsMobiles = array_filter(array_column($users, 'mobile_number'));
            $notificationsWebPush = array_filter(array_column($users, 'webpush'));
        } else {
            $objDModel = $this->gadget->model->load('Drivers');
            $objDriver = $objDModel->LoadNotificationDriver($configuration[$gadget]);
            if (Jaws_Error::IsError($objDriver)) {
                return false;
            }

            switch ($objDriver->getType()) {
                case Jaws_Notification::EML_DRIVER:
                    // generate email array
                    $notificationsEmails = array_filter(array_column($users, 'email'));
                    break;

                case Jaws_Notification::SMS_DRIVER:
                    // generate mobile array
                    $notificationsMobiles = array_filter(array_column($users, 'mobile_number'));
                    break;

                case Jaws_Notification::WEB_DRIVER:
                    // generate webpush array
                    $notificationsWebPush = array_filter(array_column($users, 'webpush'));
                    break;

                default:
                    return false;
            }
        }

        if (!empty($notificationsEmails) || !empty($notificationsMobiles) || !empty($notificationsWebPush)) {
            $res = $model->InsertNotifications(
                array(
                    'emails'  => $notificationsEmails,
                    'mobiles' => $notificationsMobiles,
                    'webpush' => $notificationsWebPush
                ),
                $params['key'],
                strip_tags($params['title']),
                strip_tags($params['summary']),
                $params['description'],
                $params['time'],
                isset($params['callback'])? $params['callback'] : '',
                isset($params['image'])? $params['image'] : '',
                isset($params['template'])? $params['template'] : ''
            );
            if (Jaws_Error::IsError($res)) {
                return $res;
            }
            return true;
        }

        return false;
    }
}
