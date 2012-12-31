/**
 * TMS (Theme Management System) Javascript actions
 *
 * @category   Ajax
 * @package    Tms
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @copyright  2007-2013 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
/**
 * Use async mode, create Callback
 */
var TmsCallback = {
    sharetheme: function(response) {
        var optionSelected = $('themes_combo').options[$('themes_combo').selectedIndex];
        if (response[0]['css'] == 'notice-message') {
            optionSelected.className = 'isshared';
            $('unshare_button').style.display = 'block';
            $('share_button').style.display   = 'none';
        } else {
            optionSelected.className          = 'isnotshared';
            $('unshare_button').style.display = 'none';
            $('share_button').style.display   = 'block';
        }
        showResponse(response);
    },
    
    unsharetheme: function(response) {
        var optionSelected = $('themes_combo').options[$('themes_combo').selectedIndex];
        if (response[0]['css'] == 'notice-message') {
            optionSelected.className = 'isnotshared';
            $('unshare_button').style.display = 'none';
            $('share_button').style.display   = 'block';
        } else {
            optionSelected.className = 'isshared';
            $('unshare_button').style.display = 'block';
            $('share_button').style.display   = 'none';
        }
        showResponse(response);
    },    
    
    installtheme: function(response) {
        if (response[0]['css'] == 'notice-message') {
            $('themes_combo').value = selectedTheme;
            editTheme(selectedTheme);
        }
        showResponse(response);
    },

    uninstalltheme: function(response) {
        if (response[0]['css'] == 'notice-message') {
        }
        showResponse(response);
    },

    newrepository: function(response) {
        if (response[0]['css'] == 'notice-message') {
            $('repositories_datagrid').addItem();
            $('repositories_datagrid').setCurrentPage(0);
        }
        showResponse(response);
        getDG();
    },

    deleterepository: function(response) {
        if (response[0]['css'] == 'notice-message') {
            $('repositories_datagrid').deleteItem();          
        }
        showResponse(response);
        getDG();
    },

    getrepository: function(response) {
        updateForm(response);
    },

    updaterepository: function(response) {
        showResponse(response);
        getDG();
    },
    
    savesettings: function(response) {
        showResponse(response);
    }
}

/**
 * Show the buttons depending on the current tab and
 * the items to show
 */
function showButtons()
{
    if ($('download').value == 'true') {
        $('download_button').style.display = 'block';
    } else {
        $('download_button').style.display = 'none';
    }
}

/**
 * Edits a theme showing basic info about it
 */
function editTheme(theme)
{
    if (theme.blank()) {
        return false;
    }

    cleanWorkingArea(true);

    var themeInfo = TmsAjax.callSync('getthemeinfo', theme);
    if (themeInfo == null) {
        return false; //Check
    }
    selectedTheme = theme;
    $('theme_area').innerHTML = themeInfo;
    showButtons();
}

/**
 * Clean the working area
 */
function cleanWorkingArea(hideButtons)
{
    $('theme_area').innerHTML = '';
    if (hideButtons != undefined) {
        if (hideButtons == true) {
            var buttons = new Array('uninstall_button', 'share_button', 
                                    'unshare_button', 'install_button');
            for(var i=0; i<buttons.length; i++) {
                if ($(buttons[i]) != undefined) {
                    $(buttons[i]).style.display = 'none';
                }
            }
        }
    }
}

/**
 * Download theme
 */
function downloadTheme()
{
    window.location= base_script + '?gadget=Tms&action=DownloadTheme&theme=' + selectedTheme;
}

function uploadTheme()
{
    document.theme_upload_form.submit();
}

/**
 * Cleans the form
 */
function cleanForm(form) 
{
    form.elements['name'].value   = '';
    form.elements['url'].value    = 'http://';  
    form.elements['id'].value     = '';    
    form.elements['action'].value = 'AddRepository';
}

/**
 * Updates form with new values
 */
function updateForm(repositoryInfo) 
{
    $('repositories_form').elements['name'].value   = repositoryInfo['name'];
    $('repositories_form').elements['url'].value    = repositoryInfo['url'];
    $('repositories_form').elements['id'].value     = repositoryInfo['id'];
    $('repositories_form').elements['action'].value = 'UpdateRepository';
}

/**
 * Add a repository
 */
function addRepository(form)
{
    var name = form.elements['name'].value;
    var url  = form.elements['url'].value;
    
    TmsAjax.callAsync('newrepository', name, url);
    cleanForm(form);
}

/**
 * Updates a repository
 */
function updateRepository(form)
{
    var name = form.elements['name'].value;
    var url  = form.elements['url'].value;
    var id   = form.elements['id'].value;

    TmsAjax.callAsync('updaterepository', id, name, url);
    cleanForm(form);
}

/**
 * Submit the 
 */
function submitForm(form)
{
    if (form.elements['action'].value == 'UpdateRepository') {
        updateRepository(form);
    } else {
        addRepository(form);
    }
}

/**
 * Deletes a repository
 */
function deleteRepository(id)
{
    TmsAjax.callAsync('deleterepository', id);
    cleanForm($('repositories_form'));
}

/**
 * Edits a repository
 */
function editRepository(id)
{
    TmsAjax.callAsync('getrepository', id);
}

/**
 * Saves settings
 */
function saveSettings()
{
    TmsAjax.callAsync('savesettings', $('share_themes').value);
}

var TmsAjax = new JawsAjax('Tms', TmsCallback),
    selectedTheme = null,
    evenColor = '#fff',
    oddColor  = '#edf3fe';
