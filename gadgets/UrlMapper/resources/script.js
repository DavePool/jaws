/**
 * UrlMapper Javascript actions
 *
 * @category   Ajax
 * @package    UrlMapper
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2006-2012 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
/**
 * UrlMapper CallBack
 */
var UrlMapperCallback = {
    /**
     * Updates a map
     */
    updatemap: function(response) {
        if (response[0]['css'] == 'notice-message') {
            enableMapEditingArea(false);
            showActionMaps();
        }
        showResponse(response);
    },

    /**
     * Update settings
     */
    updatesettings: function(response) {
        showResponse(response);
    },

    /**
     * Adds a new alias
     */
    addalias: function(response) {
        if (response[0]['css'] == 'notice-message') {
            rebuildAliasCombo();
        }
        showResponse(response);
    },

    /**
     * Updates a new alias
     */
    updatealias: function(response) {
        if (response[0]['css'] == 'notice-message') {
            rebuildAliasCombo();
        }
        showResponse(response);
    },

    /**
     * Deletes a new alias
     */
    deletealias: function(response) {
        if (response[0]['css'] == 'notice-message') {
            rebuildAliasCombo();
        }
        showResponse(response);
    },

    /**
     * Add a new error map
     */
    adderrormap: function(response) {
        if (response[0]['css'] == 'notice-message') {
            stopErrorMapAction();
            $('errormaps_datagrid').addItem();
            $('errormaps_datagrid').lastPage();
            getDG('errormaps_datagrid');
        }
        showResponse(response);
    },

    /**
     * delete an  error map
     */
    deleteerrormap: function(response) {
        if (response[0]['css'] == 'notice-message') {
            stopErrorMapAction();
            $('errormaps_datagrid').deleteItem();
            getDG('errormaps_datagrid');
        }
        showResponse(response);
    },

    /**
     * update an  error map
     */
    updateerrormap: function(response) {
        if (response[0]['css'] == 'notice-message') {
            stopErrorMapAction();
            getDG('errormaps_datagrid');
        }
        showResponse(response);
    }
}

/**
 * Build the 'big' alias combo 
 */
function rebuildAliasCombo()
{
    var combo = $('alias-combo');
    while(combo.options.length != 0) {
        combo.options[0] = null;
    }
    var aliases = UrlMapperAjax.callSync('getaliases');
    if (aliases != false) {
        var i =0;
        aliases.each(function(value, index) {
            var op = new Option(' + ' + value['alias_url'], value['id']);
            if (i % 2 == 0) {
                op.style.backgroundColor = evenColor;
            } else {
                op.style.backgroundColor = oddColor;
            }
            combo.options[combo.options.length] = op;
            i++;
        });
        stopAction();
    }
}

/**
 * Edits an alias
 */
function editAlias(id)
{
    var alias = UrlMapperAjax.callSync('getalias', id);
    $('alias_id').value   = id;
    $('custom_url').value = alias['real_url'];
    $('alias').value      = alias['alias_url'];
    $('delete_button').style.visibility = 'visible';
}

/**
 * Saves an alias
 */
function saveAlias()
{
    if ($('alias_id').value == '-') {
        UrlMapperAjax.callAsync('addalias',
                                $('alias').value,
                                $('custom_url').value);
    } else {
        UrlMapperAjax.callAsync('updatealias',
                                $('alias_id').value,
                                $('alias').value,
                                $('custom_url').value);
    }
}

/**
 * Deletes an alias
 */
function deleteCurrentAlias()
{
    var aliasCombo = $('alias-combo');
    if (aliasCombo.selectedIndex != -1) {
        UrlMapperAjax.callAsync('deletealias', aliasCombo.value);
    }
    stopAction();
}

/**
 * Update UrlMapper settings
 */
function updateProperties(form)
{
    UrlMapperAjax.callAsync('updatesettings',
                            form.elements['enabled'].value,
                            form.elements['use_aliases'].value,
                            form.elements['custom_precedence'].value,
                            form.elements['extension'].value);
}

/**
 * Add/Edit a map
 */
function saveMap()
{
    UrlMapperAjax.callAsync('updatemap',
                            selectedMap,
                            $('custom_map_route').value,
                            $('custom_map_ext').value,
                            $('map_order').value);
}

/**
 * Prepares the UI to edit an error map
 */
function editErrorMap(element, emid)
{
    selectedErrorMap = emid;
    $('legend_title').innerHTML = editErrorMap_title;
    selectDataGridRow(element.parentNode.parentNode);

    var errorMapInfo = UrlMapperAjax.callSync('geterrormap', selectedErrorMap);
    $('url').value = errorMapInfo['url'];
    $('code').value = errorMapInfo['code'];
    $('new_url').value = errorMapInfo['new_url'];
    $('new_code').value = errorMapInfo['new_code'];

    $('btn_cancel').style.visibility = 'visible';
}

/**
 * Prepares the UI to edit a map
 */
function editMap(element, mid)
{
    enableMapEditingArea(true);

    selectedMap = mid;
    $('legend_title').innerHTML = editMap_title;
    selectDataGridRow(element.parentNode.parentNode);

    var mapInfo = UrlMapperAjax.callSync('getmap', selectedMap);
    $('map_route').value  = mapInfo['map'];
    $('map_ext').value    = mapInfo['extension'];
    $('map_order').value  = mapInfo['order'];

    if (mapInfo['custom_map'] == null || mapInfo['custom_map'] == '') {
        $('custom_map_route').value  = mapInfo['map'];
    } else {
        $('custom_map_route').value  = mapInfo['custom_map'];
    }
    $('custom_map_ext').value = mapInfo['custom_extension'];
}

/**
 * Prepares a datagrid with maps of each action
 */
function showActionMaps()
{
    if ($('gadgets_combo').value.blank() ||
        $('actions_combo').value.blank())
    {
        return false;
    }

    resetGrid('maps_datagrid', '');
    //Get maps of this action and gadget
    var result = UrlMapperAjax.callSync('getactionmaps', $('gadgets_combo').value, $('actions_combo').value);
    resetGrid('maps_datagrid', result);
    enableMapEditingArea(false);
}

/**
 * Cleans the action combo and fill its again
 */
function rebuildActionCombo()
{
    var combo = $('actions_combo');
    var selectedGadget = $('gadgets_combo').value;
    var actions = UrlMapperAjax.callSync('getgadgetactions', selectedGadget);

    combo.options.length = 0;
    if (actions != false) {
        var i =0;
        actions.each(function(text, index) {
            var op = new Option(text, text);
            if (i % 2 == 0) {
                op.style.backgroundColor = evenColor;
            } else {
                op.style.backgroundColor = oddColor;
            }
            combo.options[combo.options.length] = op;
            i++;
        });
    }

    enableMapEditingArea(false);
    resetGrid('maps_datagrid', '');
}

/**
 * Enable/Disable Map editing area
 */
function enableMapEditingArea(status)
{
    if (status) {
        $('custom_map_route').disabled  = false;
        $('custom_map_ext').disabled    = false;
        $('btn_save').disabled   = false;
        $('btn_cancel').disabled = false;
    } else {
        selectedMap = null;
        unselectDataGridRow();
        $('map_order').value  = '';
        $('map_route').value  = '';
        $('map_ext').value    = '';
        $('custom_map_route').value  = '';
        $('custom_map_ext').value    = '';
        $('custom_map_route').disabled  = true;
        $('custom_map_ext').disabled    = true;
        $('btn_save').disabled   = true;
        $('btn_cancel').disabled = true;
    }
}

/**
 * Get error maps list
 */
function getErrorMaps(name, offset, reset)
{
    var result = UrlMapperAjax.callSync('geterrormaps', 10, offset);
    if (reset) {
        $(name).setCurrentPage(0);
        var total = UrlMapperAjax.callSync('geterrormapscount');
    }
    resetGrid(name, result, total);
}

/**
 * Delete error map
 */
function deleteErrorMap(element, emid)
{
    stopErrorMapAction();
    selectDataGridRow(element.parentNode.parentNode);
    var answer = confirm(confirmErrorMapDelete);
    if (answer) {
        UrlMapperAjax.callAsync('deleteerrormap', emid);
    }
    unselectDataGridRow();
}

/**
 * Add/Edit an error map
 */
function saveErrorMap() {

    if ($('url').value.blank()) {
        alert(incompleteFieldsMsg);
        return false;
    }

    if (selectedErrorMap != null && selectedErrorMap > 0) {
        UrlMapperAjax.callAsync('updateerrormap',
            selectedErrorMap,
            $('url').value,
            $('code').value,
            $('new_url').value,
            $('new_code').value);

    } else {
        UrlMapperAjax.callAsync('adderrormap',
            $('url').value,
            $('code').value,
            $('new_url').value,
            $('new_code').value);
    }
}

/**
 * Stops doing a certain action
 */
function stopAction()
{
    $('alias_id').value = '-';     
    $('alias').value    = '';
    $('custom_url').value = '';
    $('delete_button').style.visibility = 'hidden';
    $('alias-combo').selectedIndex = -1;
}

/**
 * Stops doing error map action
 */
function stopErrorMapAction()
{
    $('legend_title').innerHTML = addErrorMap_title;
    $('btn_cancel').style.visibility = 'hidden';
    unselectDataGridRow();
    selectedErrorMap = null;

    $('url').value = '';
    $('code').selectedIndex = -1;
    $('new_url').value = '';
    $('new_code').selectedIndex = -1;
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

var UrlMapperAjax = new JawsAjax('UrlMapper', UrlMapperCallback);

var evenColor = '#fff';
var oddColor  = '#edf3fe';

//Current map
var selectedMap = null;

//Current error map
var selectedErrorMap = null;

var cacheMapTemplate = null;
var cacheEditorMapTemplate = null;

var aliasesComboDiv = null;

//Which row selected in DataGrid
var selectedRow = null;
var selectedRowColor = null;
