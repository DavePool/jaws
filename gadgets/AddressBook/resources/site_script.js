/**
 * AddressBook Javascript actions
 *
 * @category   Ajax
 * @package    AddressBook
 * @author     HamidReza Aboutalebi <hamid@aboutalebi.com>
 * @copyright  2013 Jaws Development Group
 */
/**
 * Use async mode, create Callback
 */
var AddressBookCallback = {
    DeleteAddress: function(response) {
        AddressBookAjax.showResponse(response);
        FilterAddress();
    },
    DeleteGroup:  function(response) {
        AddressBookAjax.showResponse(response);
        //ReloadGroups();
    }
}

function AddTellItem()
{
    lastID = lastID + 1;
    $('removeTelButton').style.display = 'inline'
    var div = $('tel_p').getElementsByTagName('div')[0].cloneNode(true);
    div.className = 'tel';
    div.id = "tel_" + lastID;
    div.getElementsByTagName('select')[0].name = 'tel_type[]';
    div.getElementsByTagName('input')[0].name  = 'tel_number[]';
    div.getElementsByTagName('input')[0].value = '';
    div.getElementsByTagName('select')[0].selectedIndex = 0;
    $('tel_p').appendChild(div);
}

function AddEmailItem()
{
    lastID = lastID + 1;
    $('removeEmailButton').style.display = 'inline'
    var div = $('email_p').getElementsByTagName('div')[0].cloneNode(true);
    div.className = 'email';
    div.id = "email_" + lastID;
    div.getElementsByTagName('select')[0].name = 'email_type[]';
    div.getElementsByTagName('input')[0].name  = 'email[]';
    div.getElementsByTagName('input')[0].value = '';
    div.getElementsByTagName('select')[0].selectedIndex = 0;
    $('email_p').appendChild(div);
}

function AddAdrItem()
{
    lastID = lastID + 1;
    $('removeAdrButton').style.display = 'inline'
    var div = $('adr_p').getElementsByTagName('div')[0].cloneNode(true);
    div.className = 'adr';
    div.id = "adr_" + lastID;
    div.getElementsByTagName('select')[0].name = 'adr_type[]';
    div.getElementsByTagName('textarea')[0].name  = 'adr[]';
    div.getElementsByTagName('textarea')[0].value = '';
    div.getElementsByTagName('select')[0].selectedIndex = 0;
    $('adr_p').appendChild(div);
}

function AddUrlItem()
{
    lastID = lastID + 1;
    $('removeUrlButton').style.display = 'inline'
    var div = $('url_p').getElementsByTagName('div')[0].cloneNode(true);
    div.className = 'url';
    div.id = "url_" + lastID;
    div.getElementsByTagName('input')[0].name  = 'url[]';
    div.getElementsByTagName('input')[0].value = '';
    $('url_p').appendChild(div);
}

function RemoveItem(inputObject)
{
    remain = $(inputObject).parentNode.parentNode.getElementsByTagName('div').length;
    parent = $(inputObject).parentNode.parentNode.getElementsByTagName('div');
    Element.destroy($(inputObject).parentNode);
    if (remain == 2) {
        parent[0].getElementsByTagName('button')[1].style.display = 'none';
    }
}

/**
 * Get user information and set in address book
 */
function GetUserInfo()
{
    if ($('addressbook_user_link').value == 0) {
        return;
    }
    $('last_refreh_user_link').value = $('addressbook_user_link').value;
    var userInfo = AddressBookAjax.callSync('LoadUserInfo', {'uid': $('addressbook_user_link').value});
    $('addressbook_firstname').value = userInfo['fname'];
    $('addressbook_lastname').value = userInfo['lname'];
    $('addressbook_nickname').value = userInfo['nickname'];
    $('person_image').src = userInfo['avatar'];
    $('image').value = userInfo['avatar_file_name'];
}


/**
 * Filter AddressBooks and show results
 */
function FilterAddress()
{
    var filterResult = AddressBookAjax.callSync('AddressList', {'gid': $('addressbook_group').value, 'term': $('addressbook_term').value});
    $('addressbook_result').innerHTML = filterResult;
    lastGroup = $('addressbook_group').value;
    lastTerm = $('addressbook_term').value;
}

/**
 * Save Address Info
 */
function SaveAddress()
{
    if ($('addressbook_firstname').value == '' && $('addressbook_lastname').value == '') {
        alert(nameEmptyWarning);
        return;
    }
    $('edit_addressbook').submit();
}

/**
 * Delete Address
 */
function DeleteAddress(aid)
{
    msg = confirmDelete.substr(0, confirmDelete.indexOf('%s%'))+
          $('aid_'+aid).innerHTML+
          confirmDelete.substr(confirmDelete.indexOf('%s%') + 3);
    if (confirm(msg)) {
        window.location.href = deleteURL + aid;
    }
}

/**
 * Execute Selected Action In Selected Addresses
 */
function ExAction()
{
    var action = $('addressbook_gaction').value;
    if (action == 'DeleteAddress') {
        $$('.table-checkbox').each(function(el) { el.name = el.name.replace('[]', '');});
        AddressBookAjax.callAsync('DeleteAddress', $(document).getElement('form[name=AddressBookAction]').toQueryString().parseQueryString());
    } else if (action == 'VCardBuild') {
        //AddressBookAjax.callSync('VCardBuild', $(document).getElement('form[name=AddressBookAction]').toQueryString().parseQueryString());
        $('AddressBookAction').submit();
    } else if (action == 'DeleteGroup') {
        $$('.table-checkbox').each(function(el) { el.name = el.name.replace('[]', '');});
        AddressBookAjax.callAsync('DeleteGroup', $(document).getElement('form[name=AddressBookAction]').toQueryString().parseQueryString());
    }
    return false;
}

/**
 * Delete Address Group
 */
function DeleteGroup(gid)
{
    msg = confirmDelete.substr(0, confirmDelete.indexOf('%s%'))+
          $('ag_'+gid).innerHTML+
          confirmDelete.substr(confirmDelete.indexOf('%s%') + 3);
    if (confirm(msg)) {
        window.location.href = deleteURL + gid;
    }
}

/**
 * Add Relation Between Address And Group
 */
function AddAddressToGroup()
{
    if ($('addressbook_group').value) {
        $('addressbook_bonding').submit();
    }
}

function ReloadToggle()
{
    $('group_p').toggle();
    var mDiv = $('tel_p').getElementsByTagName('div')[0];
    if ($('tel_p').getElementsByTagName('div').length == 1 && mDiv.getElementsByTagName('input')[0].value == '') {
        $('tel_p').toggle();
    } else {
        ChangeToggleIcon($('legend_tel'));
    }

    var mDiv = $('email_p').getElementsByTagName('div')[0];
    if ($('email_p').getElementsByTagName('div').length == 1 && mDiv.getElementsByTagName('input')[0].value == '') {
        $('email_p').toggle();
    } else {
        ChangeToggleIcon($('legend_email'));
    }

    var mDiv = $('adr_p').getElementsByTagName('div')[0];
    if ($('adr_p').getElementsByTagName('div').length == 1 && mDiv.getElementsByTagName('textarea')[0].value == '') {
        $('adr_p').toggle();
    } else {
        ChangeToggleIcon($('legend_adr'));
    }

    var mDiv = $('url_p').getElementsByTagName('div')[0];
    if ($('url_p').getElementsByTagName('div').length == 1 && mDiv.getElementsByTagName('input')[0].value == '') {
        $('url_p').toggle();
    } else {
        ChangeToggleIcon($('legend_urls'));
    }

    if ($('other_p').getElementsByTagName('textarea')[0].value == '') {
        $('other_p').toggle();
    } else {
        ChangeToggleIcon($('legend_other'));
    }
}

function ChangeToggleIcon(obj)
{
    if ($(obj).get('toggle-status') == 'min') {
        $(obj).getElementsByTagName('img')[0].src = toggleMin;
        $(obj).set('toggle-status', 'max');
    } else {
        $(obj).getElementsByTagName('img')[0].src = toggleMax;
        $(obj).set('toggle-status', 'min');
    }
}

/**
 * Uploads the image
 */
function upload() {
    //showWorkingNotification();
    var iframe = new Element('iframe', {id:'ifrm_upload', name:'ifrm_upload'});
    iframe.style.display = 'none';
    $('addressbook_image').adopt(iframe);
    $('frm_person_image').submit();
}

/**
 * Loads and sets the uploaded image
 */
function onUpload(response) {
    //hideWorkingNotification();
    if (response.type === 'error') {
        alert(response.message);
        $('frm_person_image').reset();
    } else {
        var filename = response.message + '//time//' + (new Date()).getTime();
        $('person_image').src = loadImageUrl + filename;
        $('image').value = response.message;
    }
    $('ifrm_upload').destroy();
}


/**
 * Removes the image
 */
function removeImage() {
    $('image').value = '';
    $('frm_person_image').reset();
    $('person_image').src = baseSiteUrl + '/gadgets/AddressBook/images/photo128px.png?' + (new Date()).getTime();
}

function toggleCheckboxes(){
    checkStatus = !checkStatus;
    $$('.table-checkbox').each(function(el) { el.checked = checkStatus; });
}


var AddressBookAjax = new JawsAjax('AddressBook', AddressBookCallback);
AddressBookAjax.backwardSupport();
var lastGroup = 0;
var lastTerm = '';
var checkStatus = false;