<?php
/**
 * Contact admin model
 *
 * @category   GadgetModel
 * @package    Contact
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2006-2013 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class Contact_Model extends Jaws_Gadget_Model
{
    /**
     * Get information of a Contact
     *
     * @access  public
     * @param   int     $id     Contact ID
     * @return  mixed   Array of Contact Information or Jaws_Error on failure
     */
    function GetContact($id)
    {
        $cntctTable = Jaws_ORM::getInstance()->table('contacts');
        $cntctTable->select(
            'id:integer', 'ip', 'name', 'email', 'company', 'url', 'tel', 'fax', 'mobile', 'address',
            'recipient:integer', 'subject', 'msg_txt', 'attachment', 'createtime', 'updatetime'
        );
        return $cntctTable->where('id', $id)->getRow();
    }

    /**
     * Get information of one Recipient
     *
     * @access  public
     * @param   string  $id     ID of the Recipient
     * @return  array  Array with the information of a Recipient or Jaws_Error on failure
     */
    function GetRecipient($id)
    {
        $rcpTable = Jaws_ORM::getInstance()->table('contacts_recipients');
        $rcpTable->select('id:integer', 'name', 'email', 'tel', 'fax', 'mobile', 'inform_type:integer', 'visible:integer');
        return $rcpTable->where('id', $id)->getRow();
    }

    /**
     * Get a list of the available Recipients
     *
     * @access  public
     * @param   bool    $onlyVisible
     * @param   bool    $limit
     * @param   bool    $offset
     * @return  mixed   Array of Recipients or Jaws_Error on failure
     */
    function GetRecipients($onlyVisible = false, $limit = false, $offset = null)
    {
        $rcpTable = Jaws_ORM::getInstance()->table('contacts_recipients');
        $rcpTable->select('id:integer', 'name', 'email', 'tel', 'fax', 'mobile', 'visible:integer');
        if ($onlyVisible) {
            $rcpTable->where('visible', 1);
        }
        $rcpTable->orderBy('id');
        $rcpTable->limit($limit, $offset);
        return $rcpTable->getAll();
    }

    /**
     * Sends email to user
     *
     * @access  public
     * @param   string  $name       Name
     * @param   string  $email      Email address
     * @param   string  $company
     * @param   string  $url
     * @param   string  $tel
     * @param   string  $fax
     * @param   string  $mobile
     * @param   string  $address
     * @param   string  $rcipient   Rcipient ID
     * @param   string  $subject    Subject of message
     * @param   string  $attachment Attachment filename
     * @param   string  $message    Message content
     * @return  bool    True on Success or False on Failure
     */
    function InsertContact($name, $email, $company, $url, $tel, $fax, $mobile,
                           $address, $recipient, $subject, $attachment, $message)
    {
        $now = $GLOBALS['db']->Date();
        $data = array();
        $data['[user]']     = $GLOBALS['app']->Session->GetAttribute('user');
        $data['ip']         = $_SERVER['REMOTE_ADDR'];
        $data['name']       = $name;
        $data['email']      = $email;
        $data['company']    = $company;
        $data['url']        = $url;
        $data['tel']        = $tel;
        $data['fax']        = $fax;
        $data['mobile']     = $mobile;
        $data['address']    = $address;
        $data['recipient']  = (int)$recipient;
        $data['subject']    = $subject;
        $data['attachment'] = $attachment;
        $data['msg_txt']    = $message;
        $data['reply']      = '';
        $data['reply_sent'] = 0;
        $data['createtime'] = $now;
        $data['updatetime'] = $now;

        $cntctTable = Jaws_ORM::getInstance()->table('contacts');
        $result = $cntctTable->insert($data)->exec();
        if (Jaws_Error::IsError($result)) {
            return false;
        }

        $GLOBALS['app']->Session->SetCookie('visitor_name',  $name,  60*24*150);
        $GLOBALS['app']->Session->SetCookie('visitor_email', $email, 60*24*150);
        $GLOBALS['app']->Session->SetCookie('visitor_url',   $url,   60*24*150);

        return true;
    }

}