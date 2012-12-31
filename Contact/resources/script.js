/**
 * Contact Javascript actions
 *
 * @category   Ajax
 * @package    Contact
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @author     Mohsen Khahani <mohsen@khahani.com>
 * @copyright  2005-2013 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
/**
 * Use async mode, create Callback
 */
var ContactCallback = {
    updatecontact: function(response) {
        if (response[0]['css'] == 'notice-message') {
            getDG('contacts_datagrid');
            stopAction();
        }
        showResponse(response);
    },

    updatereply: function(response) {
        if (response[0]['css'] == 'notice-message') {
            selectedRow.getElement('label').set({style:'font-weight:normal'});
            stopAction();
        }
        showResponse(response);
    },

    deleterecipient: function(response) {
        if (response[0]['css'] == 'notice-message') {
            $('recipient_datagrid').deleteItem();          
            getDG();
            stopAction();
        }
        showResponse(response);
    },

    insertrecipient: function(response) {
        if (response[0]['css'] == 'notice-message') {
            $('recipient_datagrid').addItem();
            $('recipient_datagrid').setCurrentPage(0);
            getDG();
            stopAction();
        }
        showResponse(response);
    },

    updaterecipient: function(response) {
        if (response[0]['css'] == 'notice-message') {
            getDG();
            stopAction();
        }
        showResponse(response);
    },

    updateproperties: function(response) {
        showResponse(response);
    },

    deletecontact: function(response) {
        if (response[0]['css'] == 'notice-message') {
            $('contacts_datagrid').deleteItem();          
            getDG('contacts_datagrid');
            stopAction();
        }
        showResponse(response);
    },

    sendemail: function(response) {
        if (response[0]['css'] == 'notice-message') {
            newEmail();
        }
        showResponse(response);
    }
}

function isValidEmail(email) {
    return (/^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,4})+$/.test(email));
}

/**
 * Select DataGrid row
 *
 */
function selectDataGridRow(rowElement)
{
    if (selectedRow) {
        selectedRow.style.backgroundColor = selectedRowColor;
    }
    selectedRowColor = rowElement.style.backgroundColor;
    rowElement.style.backgroundColor = '#ffffcc';
    selectedRow = rowElement;
}

/**
 * Unselect DataGrid row
 *
 */
function unselectDataGridRow()
{
    if (selectedRow) {
        selectedRow.style.backgroundColor = selectedRowColor;
    }
    selectedRow = null;
    selectedRowColor = null;
}

/**
 * Clean the form
 *
 */
function stopAction() 
{
    switch(currentAction) {
    case 'Recipients':
        $('id').value      = 0;
        $('name').value    = '';
        $('email').value   = '';
        $('tel').value     = '';
        $('fax').value     = '';
        $('mobile').value  = '';
        $('inform_type').value  = 0;
        $('visible').value = 1;
        unselectDataGridRow();
        $('name').focus();
        break;
    case 'Contacts':
        $('id').value      = 0;
        $('contact_ip').set('html', '');
        $('name').value    = '';
        $('email').value   = '';
        $('company').value = '';
        $('url').value     = '';
        $('tel').value     = '';
        $('fax').value     = '';
        $('mobile').value  = '';
        $('address').value = '';
        $('rid').selectedIndex = -1;
        $('subject').value = '';
        $('message').value = '';
        $('btn_save_send').style.visibility   = 'hidden';
        $('tr_attachment').hide();
        $('btn_save').style.visibility   = 'hidden';
        $('btn_cancel').style.visibility = 'hidden';
        unselectDataGridRow();
        $('name').focus();
        break;
    case 'Reply':
        $('id').value      = 0;
        $('name').value    = '';
        $('email').value   = '';
        $('subject').value = '';
        $('message').value = '';
        $('reply').value   = '';
        $('reply').readOnly = true;
        $('btn_save_send').style.visibility   = 'hidden';
        $('btn_save').style.visibility   = 'hidden';
        $('btn_cancel').style.visibility = 'hidden';
        unselectDataGridRow();
    }
}

/**
 * Edit a Contact
 *
 */
function editContact(element, id)
{
    currentAction = 'Contacts';
    $('legend_title').innerHTML = messageDetail_title;
    if (cacheContactForm != null) {
        $('c_work_area').innerHTML = cacheContactForm;
    }

    selectDataGridRow(element.parentNode.parentNode);

    var contact = ContactAjax.callSync('getcontact', id);
    $('id').value      = contact['id'];
    $('contact_ip').set('html', contact['ip']);
    $('name').value    = contact['name'];
    $('email').value   = contact['email'];
    $('company').value = contact['company'];
    $('url').value     = contact['url'];
    $('tel').value     = contact['tel'];
    $('fax').value     = contact['fax'];
    $('mobile').value  = contact['mobile'];
    $('address').value = contact['address'];
    $('rid').value     = contact['recipient'];
    $('subject').value = contact['subject'].defilter();
    $('message').value = contact['msg_txt'].defilter();
    $('btn_save_send').style.visibility   = 'hidden';
    $('btn_save').style.visibility   = 'visible';
    $('btn_cancel').style.visibility = 'visible';

    if (contact['attachment']) {
        $('attachment').href = dataURL + contact['attachment'];
        $('attachment').set('html', contact['attachment']);
        $('tr_attachment').show();
    } else {
        $('tr_attachment').hide();
    }
}

/**
 * Edit Poll Answers
 */
function editReply(element, id)
{
    if (cacheContactForm == null) {
        cacheContactForm = $('c_work_area').innerHTML;
    }

    selectDataGridRow(element.parentNode.parentNode);

    if (cacheReplyForm == null) {
        cacheReplyForm = ContactAjax.callSync('replyui');
    }
    currentAction = 'Reply';

    selectedContact = id;
    $('legend_title').innerHTML = contactReply_title;
    $('c_work_area').innerHTML = cacheReplyForm;
    var replyData = ContactAjax.callSync('getreply', selectedContact);
    $('id').value      = replyData['id'];
    $('name').value    = replyData['name'];
    $('email').value   = replyData['email'];
    $('subject').value = replyData['subject'].defilter();
    $('message').value = replyData['msg_txt'].defilter();
    $('reply').value   = replyData['reply'].defilter();
    $('btn_save').style.visibility   = 'visible';
    $('btn_cancel').style.visibility = 'visible';
    $('btn_save_send').style.visibility = 'visible';
    $('reply').readOnly = Boolean(replyData['readonly']);
    $('reply').focus();
}

/**
 * Update a Contact
 */
function updateContact(send_reply)
{
    switch(currentAction) {
    case 'Contacts':
        ContactAjax.callAsync('updatecontact',
                        $('id').value,
                        $('name').value,
                        $('email').value,
                        $('company').value,
                        $('url').value,
                        $('tel').value,
                        $('fax').value,
                        $('mobile').value,
                        $('address').value,
                        $('rid').value,
                        $('subject').value,
                        $('message').value);
        break;
    case 'Reply':
        ContactAjax.callAsync('updatereply',
                        $('id').value,
                        $('reply').value,
                        send_reply);
        break;
    }
}

/**
 * Delete contact
 *
 */
function deleteContact(element, id)
{
    stopAction();
    selectDataGridRow(element.parentNode.parentNode);
    if (confirm(confirmContactDelete)) {
        ContactAjax.callAsync('deletecontact', id);
    }
    unselectDataGridRow();
}

/**
 * Get contacts
 *
 */
function getContacts(name, offset, reset)
{
    var result = ContactAjax.callSync('getcontacts', $('recipient_filter').value, offset);
    if (reset) {
        $(name).setCurrentPage(0);
        var total = ContactAjax.callSync('getcontactscount', $('recipient_filter').value);
    }
    resetGrid(name, result, total);
}

/**
 * Edit a Recipient
 *
 */
function editRecipient(element, id)
{
    currentAction = 'Recipients';
    selectDataGridRow(element.parentNode.parentNode);
    var recipient = ContactAjax.callSync('getrecipient', id);
    $('id').value      = recipient['id'];
    $('name').value    = recipient['name'].defilter();
    $('email').value   = recipient['email'];
    $('tel').value     = recipient['tel'];
    $('fax').value     = recipient['fax'];
    $('mobile').value  = recipient['mobile'];
    $('inform_type').value = recipient['inform_type'];
    $('visible').value = recipient['visible'];
}

/**
 * Add/Update a Recipient
 */
function updateRecipient()
{
    if ($('name').value.blank() ||
        $('email').value.blank() ||
        !isValidEmail($('email').value.trim())) {
        alert(incompleteRecipientFields);
        return;
    }

    if($('id').value == 0) {
        ContactAjax.callAsync('insertrecipient',
                        $('name').value,
                        $('email').value,
                        $('tel').value,
                        $('fax').value,
                        $('mobile').value,
                        $('inform_type').value,
                        $('visible').value);
    } else {
        ContactAjax.callAsync('updaterecipient',
                        $('id').value,
                        $('name').value,
                        $('email').value,
                        $('tel').value,
                        $('fax').value,
                        $('mobile').value,
                        $('inform_type').value,
                        $('visible').value);
    }
}

/**
 * Delete a Recipient
 */
function deleteRecipient(element, id)
{
    stopAction();
    selectDataGridRow(element.parentNode.parentNode);
    if (confirm(confirmRecipientDelete)) {
        ContactAjax.callAsync('deleterecipient', id);
    }
    unselectDataGridRow();
}

/**
 * Update the properties
 *
 */
function updateProperties()
{
    ContactAjax.callAsync('updateproperties',
                        $('use_antispam').value,
                        $('email_format').value,
                        $('enable_attachment').value,
                        getEditorValue('comments'));
}

/**
 * Switches between two UIs for Email target
 */
function switchEmailTarget(value)
{
    switch (value) {
        case '1':
            if ($('batch_mail').isDisplayed()) break;
            $('free_mail').hide();
            $('batch_mail').show();
            break;
        case '2':
            if ($('free_mail').isDisplayed()) break;
            $('batch_mail').hide();
            $('free_mail').show();
            break;
    }
}

/**
 * Updates users combo according to selected group
 */
function updateUsers(group)
{
    if (group == '0') {
        $('users').setValue(0);
        group = false;
    }
    var users = ContactAjax.callSync('getusers', group);
    $('users').options.length = 0;
    $('users').options[0] = new Option(lblAllGroupUsers, 0);
    users.each(function(user, i) {
        $('users').options[$('users').options.length] = new Option(user['nickname'], user['id']);
    });
}

/**
 * Clears the form for a new Email
 */
function newEmail()
{
    $('groups').value = 0;
    $('users').value = 0;
    $('to').value = '';
    $('cc').value = '';
    $('bcc').value = '';
    $('from').value = '';
    $('subject').value = '';
    $('message').value = '';
    $('filename').value = '';
    $('frm_file').reset();

    $('attachment').show();
    $('btn_upload').show();
    $('attach_loading').hide();
    $('attach_actions').hide();
    toggleDisableForm(false);
}

/**
 * Disables/Enables form elements
 */
function toggleDisableForm(disabled)
{
    $('options_1').disabled   = disabled;
    $('options_2').disabled   = disabled;
    $('to').disabled          = disabled;
    $('cc').disabled          = disabled;
    $('bcc').disabled         = disabled;
    $('groups').disabled      = disabled;
    $('users').disabled       = disabled;
    $('subject').disabled     = disabled;
    $('message').disabled     = disabled;
    $('btn_send').disabled    = disabled;
    $('btn_preview').disabled = disabled;
    $('btn_new').disabled     = disabled;
}

/**
 * Uploads the attachment file
 */
function uploadFile() {
    showWorkingNotification();
    var iframe = new Element('iframe', {id:'ifrm_upload', name:'ifrm_upload'});
    $('send_email').adopt(iframe);
    $('attachment').hide();
    $('btn_upload').hide();
    $('attach_loading').show();
    toggleDisableForm(true);
    $('frm_file').submit();
}

/**
 * Sets the uploaded file as attachment
 */
function onUpload(response) {
    hideWorkingNotification();
    toggleDisableForm(false);
    if (response.type === 'error') {
        alert(response.message);
        $('frm_file').reset();
        $('btn_upload').show();
        $('attachment').show();
    } else {
        $('filename').value = response.filename;
        $('file_link').set('html', response.filename);
        $('file_size').set('html', response.filesize);
        $('attach_actions').show();
        $('attachment').hide();
    }
    $('attach_loading').hide();
    $('ifrm_upload').destroy();
}

/**
 * Removes the attachment
 */
function removeAttachment() {
    $('filename').value = '';
    $('frm_file').reset();
    $('attach_actions').hide();
    $('file_link').set('html', '');
    $('file_size').set('html', '');
    $('btn_upload').show();
    $('attachment').show();
}

/**
 * Opens popup window with a preview of the message body
 */
function previewMessage()
{
    var preview  = ContactAjax.callSync('getmessagepreview', getEditorValue('message')),
        width    = 750,
        height   = 500,
        docDim   = document.getSize(),
        left     = (docDim.x - width) / 2,
        top      = (docDim.y - height) / 2,
        specs    = 'width=' + width + ',height=' + height + ',left=' + left + ',top=' + top,
        popup    = window.open('about:blank', '', specs, true);
    popup.document.write(preview);
}

/**
 * Sends the Email
 */
function sendEmail()
{
    if ($('options_1').checked) {
        if ($('users').options.length <= 1) {
            alert(groupHasNoUser);
            $('groups').focus();
            return;
        }
        var target = {'group': $('groups').value,
                      'user' : $('users').value};
    } else {
        // Already we have isValidEmail() but validation becomes 
        // too complicated in case of 3 fields (to, cc, bcc) so let server do the job
        if ($('to').value.blank() &&
            $('cc').value.blank() &&
            $('bcc').value.blank())
        {
            alert(incompleteMailerFields);
            $('to').focus();
            return;
        }
        var target = {'to' : $('to').value,
                      'cc' : $('cc').value,
                      'bcc': $('bcc').value};
    }

    if ($('subject').value.blank()) {
        alert(incompleteMailerFields);
        $('subject').focus();
        return;
    }

    var body = getEditorValue('message');
    if (body.blank()) {
        alert(incompleteMailerFields);
        $('message').focus();
        return;
    }

    ContactAjax.callAsync('sendemail', target, $('subject').value, body, $('filename').value);
}

var ContactAjax = new JawsAjax('Contact', ContactCallback),
    cacheContactForm = null,
    cacheReplyForm = null,
    currentAction = null,
    selectedRow = null,
    selectedRowColor = null;
