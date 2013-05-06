/**
 * Components Javascript actions
 *
 * @category   Ajax
 * @package    Components
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Mohsen Khahani <mkhahani@gmail.com>
 * @copyright  2004-2013 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
/**
 * Use async mode, create Callback
 */
var ComponentsCallback = {
    upgradegadget: function(response) {
        if (response[0]['css'] == 'notice-message') {
            components[selectedComponent].state = 
                components[selectedComponent].core_gadget? 'core' : 'installed';
            buildComponentList();
            closeUI();
        }
        showResponse(response);
    },

    installgadget: function(response) {
        if (response[0]['css'] == 'notice-message') {
            components[selectedComponent].state = 
                components[selectedComponent].core_gadget? 'core' : 'installed';
            buildComponentList();
            closeUI();
        }
        showResponse(response);
    },

    uninstallgadget: function(response) {
        if (response[0]['css'] == 'notice-message') {
            components[selectedComponent].state = 'notinstalled';
            buildComponentList();
            closeUI();
        }
        showResponse(response);
    },

    enablegadget: function(response) {
        if (response[0]['css'] == 'notice-message') {
            components[selectedComponent].state = 'installed';
            components[selectedComponent].disabled = false;
            buildComponentList();
            closeUI();
        }
        showResponse(response);
    },

    disablegadget: function(response) {
        if (response[0]['css'] == 'notice-message') {
            components[selectedComponent].state = 'installed';
            components[selectedComponent].disabled = true;
            buildComponentList();
            closeUI();
        }
        showResponse(response);
    },

    installplugin: function(response) {
        if (response[0]['css'] == 'notice-message') {
            components[selectedComponent].state = 'installed';
            buildComponentList();
            closeUI();
        }
        showResponse(response);
    },

    uninstallplugin: function(response) {
        if (response[0]['css'] == 'notice-message') {
            components[selectedComponent].state = 'notinstalled';
            buildComponentList();
            closeUI();
        }
        showResponse(response);
    },

    updatepluginusage: function(response) {
        closeUI();
        showResponse(response);
    }
}

/**
 * Initiates Components gadgets/plugins
 */
function init()
{
    components = pluginsMode?
        ComponentsAjax.callSync('getplugins'):
        ComponentsAjax.callSync('getgadgets');
    buildComponentList();
    $('components').getElements('h3').addEvent('click', toggleSection);
    $('tabs').getElements('li').addEvent('click', switchTab);
    updateSummary();
}

/**
 * Builds the gadgets/plugins listbox
 */
function buildComponentList()
{
    var sections = {};
    sections.outdated = $('outdated').set('html', '');
    sections.notinstalled = $('notinstalled').set('html', '');
    sections.installed = $('installed').set('html', '');
    sections.core = $('core').set('html', '');
    Object.keys(components).sort().each(function(name) {
        sections[components[name]['state']].grab(getComponentItem(components[name]));
    });
    $('components').getElements('h3').show();
    $('components').getElements('ul:empty').getPrevious('h3').hide();
}

/**
 * Builds and returns a gadget/plugin item
 */
function getComponentItem(comp)
{
    var li = new Element('li', {id:comp.realname}),
        span = new Element('span').set('html', comp.name),
        img = new Element('img', {alt:comp.realname}),
        a = new Element('a').set('html', actions[comp.state]);
    if (comp.disabled) {
        a.set('html', actions['disabled']);
    }
    li.adopt(img, span);
    img.src = pluginsMode?
        'gadgets/Components/images/plugin.png' :
        'gadgets/' + comp.realname + '/images/logo.png';
    if (comp.state !== 'core') {
        a.href = 'javascript:void(0);';
        a.addEvent('click', function(e) {
            e.stop();
            selectedComponent = comp.realname;
            setupComponent();
        });
        li.grab(a);
    }
    if (comp.disabled) {
        li.addClass('disabled');
    }
    li.addEvent('click', selectComponent);
    return li;
}

/**
 * Updates gadgets/plugins summary
 */
function updateSummary()
{
    var count = {
        outdated: 0,
        disabled: 0,
        installed: 0,
        notinstalled: 0,
        core: 0,
        total: 0
    };
    Object.keys(components).each(function(comp) {
        switch (components[comp].state) {
            case 'outdated':
                count.outdated++;
                break;
            case 'notinstalled':
                count.notinstalled++;
                break;
            case 'installed':
                count.installed++;
                if (components[comp].disabled) {
                    count.disabled++;
                }
                break;
            case 'core':
                count.core++;
                break;
        }
        count.total++;
    });
    $('sum_installed').innerHTML = count.installed;
    $('sum_notinstalled').innerHTML = count.notinstalled;
    $('sum_total').innerHTML = count.total;
    if (!pluginsMode) {
        $('sum_disabled').innerHTML = count.disabled;
        $('sum_outdated').innerHTML = count.outdated;
        $('sum_core').innerHTML = count.core;
    }
    summaryUI = $('summary').innerHTML;
}

/**
 * Expands/collapses gadget/plugin section
 */
function toggleSection()
{
    this.toggleClass('collapsed');
    this.getNext('ul').toggle();
}

/**
 * Switches between component Info/Regsitry/ACL UIs
 */
function switchTab(tab)
{
    tab = (typeof tab === 'string')? tab : this.id;
    $('tabs').getElement('li.active').removeClass('active');
    $(tab).addClass('active');
    switch (tab) {
        case 'tab_info':
            componentInfo();
            break;
        case 'tab_registry':
            componentRegistry();
            break;
        case 'tab_acl':
            break;
    }
}

/**
 * Highlights clicked item in the component list
 */
function selectComponent()
{
    var comp = this.id,
        img = new Element('img'),
        h1 = new Element('h1');
    img.src = pluginsMode? 
        'gadgets/Components/images/plugin.png': 
        'gadgets/' + components[comp]['realname'] + '/images/logo.png';
    img.alt = components[comp]['name'];
    h1.innerHTML = components[comp]['name'] + ': ' + components[comp]['description'];
    $('component_head').innerHTML = '';
    $('component_head').adopt(img, h1);
    $$('#components li.selected').removeClass('selected');
    this.addClass('selected');

    selectedComponent = comp;
    editPluginMode = false;
    uiCache = {};
    switchTab($('tabs').getElement('li.active').id);
}

/**
 * Deselects component in the list and hides it's UI
 */
function closeUI()
{
    selectedComponent = null;
    editPluginMode = false;
    $$('#components li.selected').removeClass('selected');
    $('summary').show();
    $('component').hide();
    updateSummary();
}

/**
 * Displays useful information about gadget/plugin
 */
function componentInfo()
{
    if (typeof uiCache.info === 'undefined') {
        uiCache.info = pluginsMode?
            ComponentsAjax.callSync('getplugininfo', selectedComponent):
            ComponentsAjax.callSync('getgadgetinfo', selectedComponent);
    }
    $('component_info').innerHTML = uiCache.info;
    $('summary').hide();
    $('component').show();
    showButtons();
}

/**
 * Displays registry keys/values of the gadget/plugin
 */
function componentRegistry()
{
    var form = new Element('form', {id:'frm_registry'}),
        table = new Element('table');
    if (typeof uiCache.registry === 'undefined') {
        uiCache.registry = ComponentsAjax.callSync('getregistry', selectedComponent);
    }
    uiCache.registry.each(function(reg) {
        var label = new Element('label', {html:reg.key_name, 'for':reg.key_name}),
            th = new Element('th').grab(label),
            input = new Element('input', {id:reg.key_name, value:reg.key_value}),
            td = new Element('td').grab(input),
            tr = new Element('tr').adopt(th, td);
        table.grab(tr);
    });
    $('component_info').innerHTML = '';
    $('component_info').grab(form.grab(table));
    $('summary').hide();
    $('component').show();
}

/**
 * Installs, uninstalls or updates the gadget/plugin
 */
function setupComponent()
{
    var comp = components[selectedComponent];
    switch (comp.state) {
        case 'outdated':
            ComponentsAjax.callAsync('upgradegadget', selectedComponent);
            break;
        case 'notinstalled':
            if (pluginsMode) {
                ComponentsAjax.callAsync('installplugin', selectedComponent);
            } else {
                ComponentsAjax.callAsync('installgadget', selectedComponent);
            }
            break;
        case 'installed':
            if (pluginsMode) {
                if (confirm(confirmUninstallPlugin)) {
                    ComponentsAjax.callAsync('uninstallplugin', selectedComponent);
                }
            } else {
                if (comp.disabled) {
                    ComponentsAjax.callAsync('enablegadget', selectedComponent);
                } else if (confirm(confirmUninstallGadget)) {
                    ComponentsAjax.callAsync('uninstallgadget', selectedComponent);
                }
            }
            break;
    }
}

/**
 * Enables the gadget
 */
function enableGadget()
{
    ComponentsAjax.callAsync('enablegadget', selectedComponent);
}

/**
 * Disables the gadget
 */
function disableGadget()
{
    if (confirm(confirmDisableGadget)) {
        ComponentsAjax.callAsync('disablegadget', selectedComponent);
    }
}

/**
 * Shows/hides buttons depending on the current tab and selected component
 */
function showButtons()
{
    var comp = components[selectedComponent];
    $('actions').getElements('button').hide();
    if (pluginsMode) {
        switch(comp.state) {
        case 'notinstalled':
            $('btn_install').show('inline');
            break;
        case 'installed':
            if (editPluginMode == true) {
                $('btn_save').show('inline');
            } else {
                $('btn_uninstall').show('inline');
                $('btn_usage').show('inline');
            }
            break;
        }
    } else {
        switch(comp.state) {
        case 'outdated':
            $('btn_update').show('inline');
            break;
        case 'notinstalled':
            $('btn_install').show('inline');
            break;
        case 'installed':
            $('btn_uninstall').show('inline');
            if (comp.disabled) {
                $('btn_enable').show('inline');
            } else {
                $('btn_disable').show('inline');
            }
            break;
        }
    }
}

/**
 * Displays the plugin usage tree
 */
function pluginUsage()
{
    var gadgets = ComponentsAjax.callSync('getpluginusage', selectedComponent),
        tree = new WebFXTree(pluginUsageDesc),
        div = new Element('div'),
        label = new Element('label', {'for':'use_always'}),
        chkbox = new Element('input', {
            type: 'checkbox',
            id: 'use_always',
            value: 'use_always',
            checked: gadgets['always'].value
        }),
        rootNode;
    label.innerHTML = gadgets['always'].text;
    tree.openIcon = 'gadgets/Components/images/gadgets.png';
    tree.icon = 'gadgets/Components/images/gadgets.png';
    div.adopt(chkbox, label);
    rootNode = new WebFXTreeItem(div.innerHTML);
    tree.add(rootNode);
    // does not work because of webfxTree bug
    // rootNode.expand();

    delete gadgets['always'];
    Object.keys(gadgets).each(function(gadget) {
        var div = new Element('div'),
            label = new Element('label', {'for':gadget}),
            chkbox = new Element('input', {
                type: 'checkbox',
                id: gadget,
                value: gadget,
                checked: gadgets[gadget].value
            });
        label.innerHTML = gadgets[gadget].text;
        div.adopt(chkbox, label);
        rootNode.add(new WebFXTreeItem(div.innerHTML));
    });

    $('component').innerHTML = tree.toString();
    editPluginMode = true;
    showButtons();
}

/**
 * Saves the plugin usage
 */
function savePluginUsage()
{
    var selection = '*';
    if (!$('use_always').checked) {
        selection = $('bigTree').getElements('input:checked').get('value');
        selection = selection.erase('use_always').join(',');
    }
    ComponentsAjax.callAsync('updatepluginusage', selectedComponent, selection);
}

/**
 * Variables
 */
var ComponentsAjax = new JawsAjax('Components', ComponentsCallback),
    components = {},
    selectedComponent = null,
    editPluginMode = false,
    uiCache = {},
    summaryUI;
