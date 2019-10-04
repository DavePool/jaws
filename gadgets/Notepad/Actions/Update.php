<?php
/**
 * Notepad Gadget
 *
 * @category    Gadget
 * @package     Notepad
 * @author      Mohsen Khahani <mkhahani@gmail.com>
 * @copyright   2013-2015 Jaws Development Group
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
$GLOBALS['app']->Layout->addLink('gadgets/Notepad/Resources/site_style.css');
class Notepad_Actions_Update extends Jaws_Gadget_Action
{
    /**
     * Builds form to edit a note
     *
     * @access  public
     * @return  string  XHTML form
     */
    function EditNote()
    {
        // Response
        $response = $this->gadget->session->pop('Response');
        if ($response) {
            $note = $response['data'];
        }

        if (!isset($note) || empty($note)) {
            $id = (int)$this->gadget->request->fetch('id', 'get');
            $model = $this->gadget->model->load('Notepad');
            $user = (int)$GLOBALS['app']->Session->GetAttribute('user');
            $note = $model->GetNote($id, $user);
            if (Jaws_Error::IsError($note) ||
                empty($note) ||
                $note['user'] != $user)
            {
                return;
            }
        }

        $this->AjaxMe('site_script.js');
        $tpl = $this->gadget->template->load('Form.html');
        $tpl->SetBlock('form');
        $tpl->SetVariable('title', _t('NOTEPAD_EDIT_NOTE'));
        $tpl->SetVariable('errorIncompleteData', _t('NOTEPAD_ERROR_INCOMPLETE_DATA'));
        $tpl->SetVariable('action', 'editnote');
        $tpl->SetVariable('form_action', 'UpdateNote');
        $tpl->SetVariable('id', $note['id']);

        // Title
        $tpl->SetVariable('note_title', $note['title']);
        $tpl->SetVariable('lbl_title', _t('NOTEPAD_NOTE_TITLE'));

        // Editor
        $editor =& $GLOBALS['app']->LoadEditor('Notepad', 'content', $note['content']);
        $editor->setID('content');
        $tpl->SetVariable('note_content', $editor->Get());
        $tpl->SetVariable('lbl_content', _t('NOTEPAD_NOTE_CONTENT'));

        // Actions
        $tpl->SetVariable('lbl_ok', _t('GLOBAL_OK'));
        $tpl->SetVariable('lbl_cancel', _t('GLOBAL_CANCEL'));
        $tpl->SetVariable('url_back', $this->gadget->urlMap('Notepad'));

        $tpl->ParseBlock('form');
        return $tpl->Get();
    }

    /**
     * Updates note
     *
     * @access  public
     * @return  array   Response array
     */
    function UpdateNote()
    {
        $data = $this->gadget->request->fetch(array('id', 'title', 'content'), 'post');
        if (empty($data['id']) || empty($data['title']) || empty($data['content'])) {
            $this->gadget->session->push(
                _t('NOTEPAD_ERROR_INCOMPLETE_DATA'),
                'Response',
                RESPONSE_ERROR,
                $data
            );
            Jaws_Header::Referrer();
        }

        // Validate note
        $model = $this->gadget->model->load('Notepad');
        $id = (int)$data['id'];
        $user = (int)$GLOBALS['app']->Session->GetAttribute('user');
        $note = $model->GetNote($id, $user);
        if (Jaws_Error::IsError($note)) {
            $this->gadget->session->push(
                _t('NOTEPAD_ERROR_RETRIEVING_DATA'),
                'Response',
                RESPONSE_ERROR
            );
            Jaws_Header::Referrer();
        }

        // Verify owner
        if ($note['user'] != $user) {
            $this->gadget->session->push(
                _t('NOTEPAD_ERROR_NO_PERMISSION'),
                'Response',
                RESPONSE_ERROR
            );
            Jaws_Header::Referrer();
        }

        $data['title'] = Jaws_XSS::defilter($data['title']);
        $data['content'] = Jaws_XSS::defilter($data['content']);
        $result = $model->Update($id, $data);
        if (Jaws_Error::IsError($result)) {
            $this->gadget->session->push(
                _t('NOTEPAD_ERROR_NOTE_UPDATE'),
                'Response',
                RESPONSE_ERROR,
                $data
            );
            Jaws_Header::Referrer();
        }

        $this->gadget->session->push(
            _t('NOTEPAD_NOTICE_NOTE_UPDATED'),
            'Response'
        );
        return Jaws_Header::Location($this->gadget->urlMap('Notepad'));
    }
}