/**
 * Directory Javascript actions
 *
 * @category    Ajax
 * @package     Directory
 * @author      Mohsen Khahani <mkhahani@gmail.com>
 * @copyright   2013 Jaws Development Group
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
/**
 * Use async mode, create Callback
 */
var DirectoryCallback = {
    CreateDirectory: function(response) {
        if (response.type === 'response_notice') {
            cancel();
            updateFiles(currentDir);
        }
        DirectoryAjax.showResponse(response);
    },

    UpdateDirectory: function(response) {
        if (response.type === 'response_notice') {
            cancel();
            updateFiles(currentDir);
        }
        DirectoryAjax.showResponse(response);
    },

    CreateFile: function(response) {
        if (response.type === 'response_notice') {
            cancel();
            updateFiles(currentDir);
        }
        DirectoryAjax.showResponse(response);
    },

    UpdateFile: function(response) {
        if (response.type === 'response_notice') {
            cancel();
            updateFiles(currentDir);
        }
        DirectoryAjax.showResponse(response);
    },

    Delete: function(response) {
        if (response.type === 'response_notice') {
            cancel();
            updateFiles(currentDir);
        }
        DirectoryAjax.showResponse(response);
    },

    PublishFile: function(response) {
        if (response.type === 'response_notice') {
            fileById[selectedIds.join(',')]['public'] = response.data;
            showFileURL(response.data);
        }
        DirectoryAjax.showResponse(response);
    },

    Move: function(response) {
        if (response.type === 'response_notice') {
            cancel();
            updateFiles(currentDir);
        }
        DirectoryAjax.showResponse(response);
    },

    UpdateFileUsers: function(response) {
        if (response.type === 'response_notice') {
            cancel();
            updateFiles(currentDir);
        }
        DirectoryAjax.showResponse(response);
    },

    Search: function(response) {
        if (response.type === 'response_notice') {
            $('dir_pathbar').hide();
            $('dir_searchbar').show();
            $('search_res').innerHTML = ' > ' + response.message;
            displayFiles(response.data);
        } else {
            DirectoryAjax.showResponse(response);
        }
    }
};

/**
 * Initiates Directory
 */
function initDirectory()
{
    DirectoryAjax.backwardSupport();
    imgDeleteFile = new Element('img', {src:imgDeleteFile});
    imgDeleteFile.addEvent('click', removeFile);
    fileTemplate = $('file_arena').get('html');
    pageBody = document.body;

    // Builds icons map (ext => icon)
    Object.each(fileTypes, function (values, type) {
        values.each(function (ext) {
            if (!iconByExt[ext]) {
                iconByExt[ext] = type;
            }
        });
    });
    iconByExt.folder = 'folder';

    currentDir = Number(DirectoryStorage.fetch('current_dir'));
    openDirectory(currentDir);
}

/**
 * Re-feches files and directories
 */
function updateFiles(parent)
{
    if (parent === undefined) {
        parent = currentDir;
    }
    var shared = ($('file_filter').value === 'shared')? true : null,
        foreign = ($('file_filter').value === 'foreign')? true : null,
        files = DirectoryAjax.callSync('GetFiles', 
            {'id':parent, 'shared':shared, 'foreign':foreign});
    if (files[0] && files[0].user != UID) {
        $('dir_path').innerHTML = ' > ' + files[0].username;
    } else {
        updatePath();
    }
    displayFiles(files);
    $('dir_pathbar').show();
    $('dir_searchbar').hide();
}

/**
 * Displays files and directories
 */
function displayFiles(files)
{
    // Creates a file element from raw data
    function getFileElement(data)
    {
        var html = fileTemplate.substitute(data),
            tr = Elements.from(html)[0];
        tr.addEvent('click', fileSelect);
        tr.addEvent('dblclick', fileOpen);
        tr.getElement('input').addEvent('click', fileCheck);
        return tr;
    }

    var ws = $('file_arena').empty().show('table-row-group');
    fileById = {};
    filesCount = files.length;
    files.each(function (file) {
        file.ext = file.is_dir? 'folder' : file.filename.split('.').pop();
        file.type = iconByExt[file.ext] || 'file-generic';
        file.icon = '<img src="' + icon_url + file.type + '.png" />';
        file.size = formatSize(file.filesize, 0);
        file.foreign = (file.user !== file.owner);
        fileById[file.id] = Object.clone(file);
        file.filename = (file.filename === null)? '' : file.filename;
        file.shared = file.shared? 'shared' : '';
        file.foreign = file.foreign? 'foreign' : '';
        file['public'] = file['public']? 'public' : '';
        ws.grab(getFileElement(file));
    });
}

/**
 * Highlights file/directory on click
 */
function fileSelect(e)
{
    if (e.target.tagName === 'INPUT') {
        return;
    }
    var ws = $('file_arena');
    ws.getElements('tr').removeClass('selected');
    ws.getElements('input').set('checked', false);
    this.addClass('selected');
    this.getElement('input').set('checked', true);
    selectedIds = getSelected();
    updateActions();
    $('form').innerHTML = '';
    //console.log(selectedIds);
}

/**
 * Checks/Unchecks file/directory
 */
function fileCheck(e)
{
    if (this.checked) {
        this.getParent('tr').addClass('selected');
    } else {
        this.getParent('tr').removeClass('selected');
    }
    selectedIds = getSelected();
    updateActions();
    $('form').innerHTML = '';
    //console.log(selectedIds);
}

/**
 * Checks/Unchecks all files/directories
 */
function checkAll(checked)
{
    $('file_arena').getElements('input').set('checked', checked);
    if (checked) {
        $('file_arena').getElements('tr').addClass('selected');
    } else {
        $('file_arena').getElements('tr').removeClass('selected');
    }
}

/**
 * Fetches ID set of selected files/directories
 */
function getSelected()
{
    return $('file_arena').getElements('input:checked').get('value');
}

/**
 * Opens, plays or downloads the file/directory on dblclick
 */
function fileOpen()
{
    var file = fileById[selectedIds],
        id = file.id;
    if (file.is_dir) {
        if (file.foreign) {
            id = file.reference;
        }
        openDirectory(id);
    } else {
        if (['wav', 'mp3', 'ogg'].indexOf(file.ext) !== -1) {
            openMedia(id, 'audio');
        } else if (['webm', 'mp4', 'ogg'].indexOf(file.ext) !== -1) {
            openMedia(id, 'video');
        } else {
            downloadFile();
        }
    }
}

/**
 * Navigates into the directory
 */
function openDirectory(id)
{
    currentDir = id;
    selectedIds = null;
    DirectoryStorage.update('current_dir', id);
    updateFiles(id);
    cancel();
}

/**
 * Plays audio/video file
 */
function openMedia(id, type)
{
    var tpl = DirectoryAjax.callSync('PlayMedia', {'id':id, 'type':type});
    $('form').innerHTML = tpl;
    pageBody.removeEvent('click', cancel);
}

/**
 * Downloads the file
 */
function downloadFile()
{
    if (selectedIds === null) return;
    var id = selectedIds,
        file = fileById[id];
    if (!file) {
        file = fileById[id] = DirectoryAjax.callSync('GetFile', {'id':id});
    }
    if (!file.dl_url) {
        fileById[id].dl_url = DirectoryAjax.callSync(
            'GetDownloadURL',
            {'id':id}
        );
    }
    window.location.assign(fileById[id].dl_url);
}

/**
 * Builds the directory path
 */
function updatePath()
{
    var pathArr = DirectoryAjax.callSync('GetPath', {'id':currentDir}),
        path = $('dir_path').set('html', '');
    pathArr.reverse().each(function (dir, i) {
        path.appendText(' > ');
        if (i === pathArr.length - 1) {
            path.appendText(dir.title);
        } else {
            var link = new Element('span');
            link.set('html', dir.title);
            link.addEvent('click', openDirectory.pass(dir.id));
            path.grab(link);
        }
    });
}

/**
 * Shows/Hides appropriate buttons
 */
function updateActions()
{
    $('file_actions').getElements('img').addClass('disabled');

    // if (fileById[Object.keys(fileById)[0]].user != UID) {  // we are in another user's files
        // $('btn_dl').removeClass('disabled');
        // return;
    // }

    $('btn_new_file').removeClass('disabled');
    $('btn_new_dir').removeClass('disabled');
    if (selectedIds.length === 0) {
        return;
    }

    $('btn_delete').removeClass('disabled');
    if (selectedIds.length === 1) {
        var selId = selectedIds[0];
        if (fileById[selId].foreign) {
            $('btn_share').addClass('disabled');
        } else {
            $('btn_share').removeClass('disabled');
        }
        if (fileById[selId].is_dir) {
            $('btn_dl').addClass('disabled');
        } else {
            $('btn_dl').removeClass('disabled');
        }
        $('btn_props').removeClass('disabled');
        $('btn_edit').removeClass('disabled');
        $('btn_move').removeClass('disabled');
    }
}

/**
 * Displays file/directory properties
 */
function props()
{
    if (selectedIds.length === 0) return;
    var idSet = selectedIds.join(','),
        data = fileById[idSet],
        form;
    if (!data.users) {
        var users = DirectoryAjax.callSync('GetFileUsers', {id:idSet}),
            id_set = [];
        users.each(function (user) {
            id_set.push(user.username);
        });
        data.users = id_set.join(', ');
    }
    if (data.is_dir) {
        form = cachedForms.viewDir;
        if (!form) {
            form = DirectoryAjax.callSync('DirectoryForm');
        }
        cachedForms.viewDir = form;
    } else {
        form = cachedForms.viewFile;
        if (!form) {
            form = DirectoryAjax.callSync('FileForm');
        }
        cachedForms.viewFile = form;
    }
    $('form').set('html', form.substitute(data));
    if (data['public'] && !data.dl_url) {
        data.dl_url = DirectoryAjax.callSync('GetDownloadURL', {id:idSet});
    }
    if (data.dl_url) {
        showFileURL(data.dl_url);
    }
    pageBody.removeEvent('click', cancel);
}

/**
 * Calls file/directory edit function
 */
function edit()
{
    if (selectedIds.length === 0) return;
    var idSet = selectedIds.join(',');
    if (fileById[idSet].is_dir) {
        editDirectory();
    } else {
        editFile();
    }
    pageBody.removeEvent('click', cancel);
}

/**
 * Deletes selected files/directories
 */
function del()
{
    if (selectedIds.length === 0) {
        return;
    }
    if (confirm(confirmDelete)) {
        DirectoryAjax.callAsync('Delete', {'id_set':selectedIds.join(',')});
    }
}

/**
 * Moves selected directory/file to another directory
 */
function move() {
    if (selectedIds.length === 0) return;
    var idSet = selectedIds.join(',');
    var tree = DirectoryAjax.callSync('GetTree', {'id':idSet}),
        form = $('form');
    form.set('html', tree);
    form.getElements('a').addEvent('click', function () {
        $('form').getElements('a').removeClass('selected');
        this.className = 'selected';
    });
    pageBody.removeEvent('click', cancel);
}

/**
 * Performs moving file/directory
 */
function submitMove() {
    var tree = $('dir_tree'),
        selected = tree.getElement('a.selected'),
        target = selected.id.substr(5, selected.id.length - 5);
    DirectoryAjax.callAsync('Move', {'id':selectedIds.join(','), 'target':target});
}

/**
 * Deselects file and hides active form
 */
function cancel()
{
    selectedIds = [];
    $('form').set('html', '');
    $('file_arena').getElements('.selected').removeClass('selected');
    $('file_arena').getElements('input').set('checked', false);
    updateActions();
    //pageBody.addEvent('click', cancel);
}

/**
 * Brings the directory creation UI up
 */
function newDirectory()
{
    cancel();
    if (!cachedForms.editDir) {
        cachedForms.editDir = DirectoryAjax.callSync('DirectoryForm', {mode:'edit'});
    }
    $('form').set('html', cachedForms.editDir);
    $('frm_dir').title.focus();
    $('frm_dir').parent.value = currentDir;
    pageBody.removeEvent('click', cancel);
}

/**
 * Brings the edit directory UI up
 */
function editDirectory()
{
    if (!cachedForms.editDir) {
        cachedForms.editDir = DirectoryAjax.callSync('DirectoryForm', {mode:'edit'});
    }
    $('form').set('html', cachedForms.editDir);
    var data = fileById[selectedIds.join(',')],
        form = $('frm_dir');
    form.id.value = selectedIds.join(',');
    form.title.value = data.title;
    form.description.value = data.description;
    form.parent.value = data.parent;
}

/**
 * Brings the file creation UI up
 */
function newFile()
{
    cancel();
    if (!cachedForms.editFile) {
        cachedForms.editFile = DirectoryAjax.callSync('FileForm', {mode:'edit'});
    }
    $('form').set('html', cachedForms.editFile);
    $('tr_file').hide();
    $('frm_upload').show();
    $('frm_file').parent.value = currentDir;
    $('frm_file').title.focus();
    pageBody.removeEvent('click', cancel);
}

/**
 * Brings the edit file UI up
 */
function editFile()
{
    if (!cachedForms.editFile) {
        cachedForms.editFile = DirectoryAjax.callSync('FileForm', {mode:'edit'});
    }
    $('form').set('html', cachedForms.editFile);
    var form = $('frm_file'),
        file = fileById[selectedIds.join(',')];
    if (file.foreign) {
        $('frm_upload').remove();
        $('parent').remove();
        $('filename').remove();
        $('filetype').remove();
        $('filesize').remove();
        $('tr_file').remove();
        $('tr_url').remove();
    } else {
        form.url.value = file.url;
        form.parent.value = file.parent;
        form.filetype.value = file.filetype;
        form.filesize.value = file.filesize;
        if (file.filename) {
            var url = file.dl_url;
            if (!url) {
                url = DirectoryAjax.callSync('GetDownloadURL', {id:selectedIds.join(',')});
                fileById[selectedIds.join(',')].dl_url = url;
            }
            setFilename(file.filename, url);
            $('filename').value = ':nochange:';
        } else {
            $('tr_file').hide();
            $('frm_upload').show();
        }
    }
    form.action.value = 'UpdateFile';
    form.id.value = selectedIds.join(',');
    form.title.value = file.title;
    form.description.value = file.description;
}

/**
 * Uploads file on the server
 */
function uploadFile() {
    var iframe = new Element('iframe', {id:'ifrm_upload'});
    document.body.grab(iframe);
    $('frm_upload').submit();
}

/**
 * Applies uploaded file into the form
 */
function onUpload(response) {
    if (response.type === 'error') {
        alert(response.message);
        $('frm_upload').reset();
    } else {
        var filename = encodeURIComponent(response.filename);
        setFilename(filename, '');
        $('filename').value = filename;
        $('filetype').value = response.filetype;
        $('filesize').value = response.filesize;
        if ($('frm_file').title.value === '') {
            $('frm_file').title.value = filename;
        }
    }
    $('ifrm_upload').destroy();
}

/**
 * Sets file (not)to be available publicly
 */
function publishFile(published)
{
    DirectoryAjax.callAsync('PublishFile', {
        'id':selectedIds.join(','),
        'public':published
    });
}

/**
 * Shows/Hides file URL
 */
function showFileURL(url)
{
    var link = $('public_url');
    if (url !== '') {
        link.innerHTML = site_url + url;
        link.href = url;
        link.show();
        $('btn_unpublic').show();
        $('btn_public').hide();
    } else {
        link.hide();
        $('btn_public').show();
        $('btn_unpublic').hide();
    }
}

/**
 * Sets download link of the file
 */
function setFilename(filename, url)
{
    var link = new Element('a', {'html':filename});
    if (url !== '') {
        link.href = url;
    }
    $('filelink').grab(link);
    $('filelink').grab(imgDeleteFile);
    $('tr_file').show();
    $('frm_upload').hide();
}

/**
 * Removes the attached file
 */
function removeFile()
{
    $('filename').value = '';
    $('filelink').set('html', '');
    $('frm_upload').reset();
    $('tr_file').hide();
    $('frm_upload').show();
}

/**
 * Submits directory data to create or update
 */
function submitDirectory()
{
    var action = (selectedIds.length === 0)? 'CreateDirectory' : 'UpdateDirectory';
    DirectoryAjax.callAsync(action, $('frm_dir').toQueryString().parseQueryString());
}

/**
 * Submits file data to create or update
 */
function submitFile()
{
    var action = (selectedIds.length === 0)? 'CreateFile' : 'UpdateFile';
    DirectoryAjax.callAsync(action, $('frm_file').toQueryString().parseQueryString());
}

/**
 * Brings the share UI up
 */
function share()
{
    if (selectedIds.length === 0) return;
    if (!cachedForms.share) {
        cachedForms.share = DirectoryAjax.callSync('ShareForm');
    }
    $('form').set('html', cachedForms.share);
    $('groups').selectedIndex = -1;

    var users = DirectoryAjax.callSync('GetFileUsers', {'id':selectedIds.join(',')});
    sharedFileUsers = {};
    users.each(function (user) {
        sharedFileUsers[user.id] = user.username;
    });
    updateShareUsers();
    pageBody.removeEvent('click', cancel);
}

/**
 * Fetches and displays users of selected group
 */
function toggleUsers(gid)
{
    var container = $('users').empty();
    if (usersByGroup[gid] === undefined) {
        usersByGroup[gid] = DirectoryAjax.callSync('GetUsers', {'gid':gid});
    }
    usersByGroup[gid].each(function (user) {
        if (user.id == UID) return;
        var div = new Element('div'),
            input = new Element('input', {type:'checkbox', id:'chk_'+user.id, value:user.id}),
            label = new Element('label', {'for':'chk_'+user.id});
        input.set('checked', (sharedFileUsers[user.id] !== undefined));
        input.addEvent('click', selectUser);
        label.set('html', user.nickname + ' (' + user.username + ')');
        div.adopt(input, label);
        container.grab(div);
    });
}

/**
 * Adds/removes user to/from shares
 */
function selectUser()
{
    if (this.checked) {
        sharedFileUsers[this.value] = this.getNext('label').get('html');
    } else {
        delete sharedFileUsers[this.value];
    }
    updateShareUsers();
}

/**
 * Updates list of file users
 */
function updateShareUsers()
{
    var list = $('share_users').empty();
    Object.each(sharedFileUsers, function(name, id) {
        list.options[list.options.length] = new Option(name, id);
    });
}

/**
 * Submits share data
 */
function submitShare()
{
    var users = [];
    Array.each($('share_users').options, function(opt) {
        users.push(opt.value);
    });
    DirectoryAjax.callAsync(
        'UpdateFileUsers',
        {'id':selectedIds.join(','), 'users':users.join(',')}
    );
}

/**
 * Search among files and directories
 */
function performSearch()
{
    var shared = ($('file_filter').value === 'shared')? true : null,
        foreign = ($('file_filter').value === 'foreign')? true : null,
        query = $('file_search').value;
    if (query.length < 2) {
        alert(alertShortQuery);
        $('file_search').focus();
        return;
    }
    DirectoryAjax.callAsync(
        'Search',
        {'id':currentDir, 'shared':shared, 'foreign':foreign, 'query':query}
    );
}

/**
 * Formats size in bytes to human readbale
 */
function formatSize(size, precision)
{
    var i = -1,
        byteUnits = [' KB', ' MB', ' GB', ' TB'];
    if (size === null) return '';
    size = Number(size);
    if (precision > 0 && size < 1024) return size + ' bytes';
    do {
        size = size / 1024;
        i++;
    } while (size > 1024);

    return Math.max(size, 1).toFixed(precision) + byteUnits[i];
}

var DirectoryAjax = new JawsAjax('Directory', DirectoryCallback),
    DirectoryStorage = new JawsStorage('Directory'),
    fileById = {},
    iconByExt = {},
    usersByGroup = {},
    sharedFileUsers = {},
    cachedForms = {},
    currentDir = 0,
    filesCount = 0,
    fileTemplate = '',
    statusTemplate = '',
    wsClickEvent = null,
    pageBody,
    selectedIds = [];

var fileTypes = {
    'font-generic' : ['ttf', 'otf', 'fon', 'pfa', 'afm', 'pfb'],
    'audio-generic' : ['mp3', 'wav', 'aac', 'flac', 'ogg', 'wma', 'cda', 'voc', 'midi', 'ac3', 'bonk', 'mod'],
    'image-generic' : ['gif', 'png', 'jpg', 'jpeg', 'raw', 'bmp', 'tiff', 'svg'],
    'package-generic' : ['tar', 'tar.gz', 'tgz', 'zip', 'gzip', 'rar', 'rpm', 'deb', 'iso', 'bz2', 'bak', 'gz'],
    'video-generic' : ['mpg', 'mpeg', 'avi', 'wma', 'rm', 'asf', 'flv', 'mov', 'mp4'],
    'help-contents' : ['hlp', 'chm', 'manual', 'man'],
    'text-generic' : ['txt', ''],
    'text-html' : ['html', 'htm', 'mht'],
    'text-java' : ['jsp', 'java', 'jar'],
    'text-python' : ['py'],
    'text-script' : ['sh', 'pl', 'asp', 'c', 'css', 'htaccess'],
    'office-document-template' : ['stw', 'ott'],
    'office-document' : ['doc', 'docx', 'sxw', 'odt', 'rtf', 'sdw'],
    'office-presentation-template' : ['pot', 'otp', 'sti'],
    'office-presentation' : ['ppt', 'odp', 'sxi'],
    'office-spreadsheet-template' : ['xlt', 'ots', 'stc'],
    'office-spreadsheet' : ['xls', 'ods', 'sxc', 'sdc'],
    'office-drawing-template' : [],
    'office-drawing' : ['sxd', 'sda', 'sdd', 'odg'],
    'application-executable' : ['exe'],
    'application-php' : ['php', 'phps'],
    'application-rss+xml' : ['xml', 'rss', 'atom', 'rdf'],
    'application-pdf' : ['pdf'],
    'application-flash' : ['swf'],
    'application-ruby' : ['rb']
};