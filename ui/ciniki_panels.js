//
// This file contains the javascript functions for create panels
//
// Variable are really short names in this file to keep the compressed size as small as possible.  This is a main file and
// must be loaded before anything else, so load times are critical.
//
// Panel types:
//
//

M.panel = function(title, appID, panelID, appPrefix, size, type, helpUID) {
    this.title = title;
    this.appID = appID;
    this.name = panelID;
    this.panelID = appID + '_' + panelID;
    this.titleID = appPrefix + '_title';
    this.appPrefix = appPrefix;
    this.size = size;
    this.type = type;
    this.helpUID = helpUID;
    this.data = null;
    this.sections = {};
    //this.form = {};
    this.lastSearches = {};
    this.leftbuttons = {};
    this.rightbuttons = {};
    this.cb = '';
    this.panelUID = 'uid_' + ((new Date()).getTime() + "_" + Math.floor(Math.random() * 10001)).substr(0,18);
    this.panelRef = 'M.' + appID + '.' + panelID;
    this.fieldHistories = {};
    this.cbStack = [];
    this.cbStacked = 'no';
    this.liveSearchTables = [];
    this.autofocus = '';
    this.tinymce = [];
    this.gstep = 0;
    this.gstep_number = 0;
    this.gsteps = [];
    this.onShowCbs = [];

    //
    // Attach functions from other files.  These are typically located in s-compact and s-normal,
    // and present the information differently for the different sized devices.
    //
    this.setupFormFieldHistory = M.panel.setupFormFieldHistory;
    this.setupFormFieldCalendar = M.panel.setupFormFieldCalendar;
    this.setupThreadFollowup = null;
};

//
// Arguments
// n - The name of the button in the list
// l - The label for the button
// f - The function to call onclick
// 
M.panel.prototype.addButton = function(n,l,f,i) {
    this.rightbuttons[n] = {'label':l, 'icon':((i==null)?n:i), 'function':f};
};
M.panel.prototype.delButton = function(n) {
    if( this.rightbuttons[n] != null ) { delete(this.rightbuttons[n]); }
};

//
// Arguments
// n - The name of the button in the list
// l - The label for the button
// f - The function to call onclick
// 
M.panel.prototype.addLeftButton = function(n,l,f,i) {
    this.leftbuttons[n] = {'label':l, 'icon':((i==null)?n:i), 'function':f};
};

M.panel.prototype.setButtonFn = function(n, f) {
    if( this.rightbuttons[n] != null ) {
        this.rightbuttons[n].function = f;
    } else if( this.leftbuttons[n] != null ) {
        this.leftbuttons[n].function = f;
    }
};

//
// Arguments:
// l  - The label to attach the close button
// cb - The callback to perform after closing the panel
//
M.panel.prototype.addClose = function(l, cb) {
    this.leftbuttons.close = {'label':l, 'icon':'close', 'function':'M.' + this.appID + '.' + this.name + '.close();'};
};

M.panel.prototype.addBack = function(l) {
    this.leftbuttons.back = {'label':l, 'icon':'back', 'function':'M.' + this.appID + '.' + this.name + '.back();'};
};

M.panel.prototype.close = function(data) {
    //
    // Remove the panel
    //
//    this.destroy();
    if( this.tinymce.length > 0 ) {
        for(i in this.tinymce) {
            tinymce.execCommand("mceRemoveEditor", false, this.tinymce[i]);
        }
    }

    //
    // Remove any hooks
    //
//    M.delDropHook(this.panelRef);
    
    //
    // return to the calling panel, but close this panel
    //
    var rc = null;
    if( this.cbStacked == 'yes' ) {
        rc = eval(this.cbStack.pop());
    }
    else if( this.cb != null ) {
        rc = eval(this.cb);
    }
    if( rc != null && rc == true ) {
        this.destroy();
        M.delDropHook(this.panelRef);
    }
};

M.panel.prototype.show = function(cb) {
    var app = M.gE(this.appID);
    if( app == null ) {
        alert('not created');
        return false;
    }

    //
    // Set the callback
    //
    if( cb != null ) {
        if( this.cbStacked == 'yes' ) {
            this.cbStack.push(cb);
        } else {
            this.cb = cb;
        }
    }

    //
    // Check if it's on the screen, and we just need to display
    //
    var p = M.gE(this.panelUID);
    if( p != null ) {
        p.style.display = 'block';
    } else {
        p = this.addPanel();
        app.appendChild(p);
    }

    // FIXME: Might make sense to traverse tree and make sure all divs are visible
    // var element; //your clicked element
    // while(element.parentNode) {
    //     //display, log or do what you want with element
    //     element = element.parentNode;
    // }
    
    // Make sure the panel is displayed
    M.hideChildren(app, this.panelUID);

    // Make sure app is displayed
    M.hideChildren(app.parentNode, this.appID);

    // Make sure app container
    M.show(app.parentNode.parentNode.parentNode.parentNode);

//    if( M.uiModeGuided == 'yes' ) {
        this.gstepGoto(this.gstep);
//    }

    M.setHTML(this.titleID, this.title);

    this.showButtons('leftbuttons', this.leftbuttons);
    this.showButtons('rightbuttons', this.rightbuttons);

    if( this.appPrefix != 'mh' ) {
        M.curHelpUID = this.helpUID;

        // Check if help needs to be updated
        if( M.gE('m_help').style.display != 'none') {
            M.ciniki_core_help.showHelp(this.helpUID);
        }
    }
    M.resize();

    //
    // Align sections
    //
    if( this.sidePanel != null ) {
        var p1 = p.children[0].children[0].children;
        var p2 = p.children[1].children[0].children;
        var o1 = 0;
        var o2 = 0;
        for(i in p1) {
            if( isFinite(i) && p1[i].offsetTop != null && p2[i].offsetTop != null ) {
                if( o1 > 0 ) {
                    p1[i].style.position = 'relative';
                    p1[i].style.top = (o1) + 'px';
                }
                if( o2 > 0 ) {
                    p2[i].style.position = 'relative';
                    p2[i].style.top = (o2) + 'px';
                }

                if( p1[i].offsetHeight > p2[i].offsetHeight ) {
                    o2 += p1[i].offsetHeight - p2[i].offsetHeight;
                } else if( p1[i].offsetHeight < p2[i].offsetHeight ) {
                    o1 += p2[i].offsetHeight - p1[i].offsetHeight;
                }
            }
        }
        // If there is a difference in height between left and right, then add extra to 
        // create padding at the bottom, below the buttons.
        if( p.children[0].offsetHeight != p.children[1].offsetHeight) {
            if( o1 < o2 ) {
                p.style.height = (p.offsetHeight + o1) + 'px';
            } else {
                p.style.height = (p.offsetHeight + o2) + 'px';
            }
        }
    }

    // Setup TinyMCE Editor
    // Moved to ciniki init
//    tinyMCE.init({
//        mode:'textareas',
//        theme:'modern',
//        schema:'html5',
//        toolbar:["bold italic underline strikethrough | link unlink | bulllist numlist"],
//        menubar:'false',
//        statusbar:'false',
//        });

    if( this.tinymce.length > 0 ) {
        for(i in this.tinymce) {
            tinymce.execCommand("mceAddEditor", false, this.tinymce[i]);
        }
    }

    if( this.autofocus != '' && M.device != 'ipad' ) {
        var e = M.gE(this.autofocus);
        if( e != null ) { e.focus(); }
    }

    if( this.onShowCbs.length > 0 ) {
        for(var i in this.onShowCbs) {
            eval(this.onShowCbs[i]);
        }
    }
};

//
// This function will build a new div with the panel data inside.  This function
// can be called by show or refresh
//
M.panel.prototype.addPanel = function() {
    this.tinymce = [];
    var p = M.aE('div', this.panelUID, 'panel guided-disabled');
    if( this.sidePanel != null ) {
        var w = M.aE('div', '', this.size + ' leftpanel');
    } else {
        var w = M.aE('div', '', this.size);
    }

    //
    // Check for any menu tabs
    //
    if( this.menutabs != null && (this.menutabs.visible == null || this.menutabs.visible != 'no') ) {
        var pm = M.aE('div', null, 'menutabs');
        var t = M.addTable(this.panelUID + '_menutabs', 'list form paneltabs noheader');
        var tb = M.aE('tbody');
        var tr = M.aE('tr');
        var c = M.aE('td',null,'textfield aligncenter joinedtabs');
        var div = M.aE('div', null, 'buttons');
        for(i in this.menutabs.tabs) {
            if( this.menutabs.tabs[i].visible != null && this.menutabs.tabs[i].visible == 'no' ) {
                continue;
            }
            var e = null;
            var lt = this.menutabs.tabs[i].label;
            if( i == this.menutabs.selected ) {
                e = M.aE('span', null, 'toggle_on', lt);
            } else {
                e = M.aE('span', null, 'toggle_off', lt, this.panelRef + '.menutabSwitch("' + i + '");');
            }
            div.appendChild(e);
        }
        c.appendChild(div);
        tr.appendChild(c);
        tb.appendChild(tr);
        t.appendChild(tb);
        pm.appendChild(t);
        p.appendChild(pm);
    }

    
    if( this.type == 'sectioned' ) {
        // This is the new (Dec 2011) master section, which should convert the way panels are handled
        // The goal is to make all panels this type
        w.appendChild(this.createSections());
    } 

    else if( this.type == 'simplemedia' ) {
        w.appendChild(this.createSimpleMedia('', this.data, 'yes'));
    }

    //
    // Check if guided steps
    //
    if( this.gstep_number > 0 ) {
        p.className = p.className.replace(/guided-disabled/, 'guided-enabled');
    }

    p.appendChild(w);

    //
    // Allow images to be dropped on the media div
    //
    if( this.uploadDropFn != null ) {
        //
        // Add hook into drophandler
        //
        M.addDropHook(this.panelRef, this.uploadDropFn());
    } else if( this.addDropImage != null ) {
        M.addDropHook(this.panelRef, this.uploadDropImages);
    } else if( this.addDropFile != null ) {
        M.addDropHook(this.panelRef, this.uploadDropFiles);
    }

    //
    // Check if there is a side panel
    //
    if( this.sidePanel != null ) {
        var w2 = M.aE('div', '', this.sidePanel.size + ' rightpanel');
        if( this.sidePanel.type == 'sectioned' ) {
            w2.appendChild(this.sidePanel.createSections());
        }
        else if( this.sidePanel.type == 'simplemedia' ) {
            w2.appendChild(this.sidePanel.createSimpleMedia('', this.data, 'yes'));
        }
        p.appendChild(w2);
    }
    
    return p;
};

M.panel.prototype.refresh = function() {
    var c = M.gE(this.panelUID);
    if( c != null ) {
        this.onShowCbs = [];
        if( this.tinymce.length > 0 ) {
            for(i in this.tinymce) {
                tinymce.execCommand("mceRemoveEditor", false, this.tinymce[i]);
            }
        }
        var s = c.style.display;
        var p = c.parentNode;
        p.removeChild(c);
        var n = this.addPanel();
        p.appendChild(n);
        n.style.display = 'block';
    }
    M.resize();
};

M.panel.prototype.refreshSection = function(s) {
    var o = M.gE(this.panelUID + '_section_' + s);
    var n = null;
    if( o != null && this.type == 'sectioned' ) {
        n = this.createSection(s, this.sections[s]);
    }
    if( o != null && n != null ) {
        o.parentNode.insertBefore(n, o);
        o.parentNode.removeChild(o);
    }
};

M.panel.prototype.refreshSections = function(s) {
    for(var i in s) {
        this.refreshSection(s[i]);
    }
};

M.panel.prototype.showHideSection = function(s) {
    if( this.sections[s] == null ) { 
        return true;
    }
    if( this.sections[s].active != null && this.sections[s].active == 'no' ) {
        return true;
    }

    // 
    // Check if section is visible
    //
    var e = M.gE(this.panelUID + '_section_' + s);
    if( e == null ) {
        return true;
    }
    if( typeof this.sections[s].visible == 'function' && this.sections[s].visible() == 'hidden') {
        e.style.display = 'none';
    } else if( this.sections[s].visible != null && this.sections[s].visible == 'hidden' ) {
        e.style.display = 'none';
    } else {
        e.style.display = '';
    }
};

M.panel.prototype.showHideSections = function(s) {
    for(var i in s) {
        this.showHideSection(s[i]);
    }
};

M.panel.prototype.showHideSections = function(s) {
    if( s == null ) {
        for(var i in this.sections) {
            this.showHideSection(i);
        }
    } else {
        for(var i in s) {
            this.showHideSection(s[i]);
        }
    }
};

//
// This function will create the sections for a panel
//
M.panel.prototype.createSections = function() {
    //
    // FIXME: Check if there should be a form started, by default start form
    //
    var f = M.aE('form', null, null);
    f.setAttribute('onsubmit', 'return false;');
    f.setAttribute('target', '_blank');

    var gstepfound = 'no';
    var gprev = 0;
    var glast = 0;
    var gnext = 0;
    this.gsteps = [];

    if( this.formtabs != null ) {
        // Decide which form to display
        var fv = 0;
        if( this.formtab_field_id != null ) {
            fv = this.formtab_field_id;
        }
        if( this.formtab == null && this.data[this.formtabs.field] != null ) {
            if( this.formtab_field_id == null ) {
                fv = this.data[this.formtabs.field];
                this.formtab_field_id = fv;
            }
            for(i in this.formtabs.tabs) {
                if( this.formtabs.tabs[i].field_id == fv ) {
                    if( this.formtabs.tabs[i].form != null ) {
                        this.formtab = this.formtabs.tabs[i].form;
                    } else {
                        this.formtab = i;
                    }
                }
            }
        }
        if( this.formtabs.visible == null || this.formtabs.visible == 'yes' ) {
            var ps = M.aE('div', this.panelUID + '_section_formtabs', 'panelsection formtabs');
            if( this.formtabs.gstep != null && this.formtabs.gtitle != null && this.formtabs.gtitle != '' ) {
                var gt = M.aE('h2', null, 'guided-title guided-show', this.formtabs.gtitle);
                ps.appendChild(gt);
            }
            if( this.formtabs.gstep != null && this.formtabs.gtext != null && this.formtabs.gtext != '' ) {
                ps.appendChild(M.aE('p', null, 'guided-text guided-show', this.formtabs.gtext));
            }
            st = this.createFormTabs(this.formtabs, this.formtab, fv);
            ps.appendChild(st);
            if( this.formtabs.gstep != null && this.formtabs.gmore != null && this.formtabs.gmore != '' ) {
                ps.appendChild(M.aE('p', null, 'guided-text guided-show', this.formtabs.gmore));
            }
            f.appendChild(ps);
            if( this.formtabs.gstep != null && this.formtabs.gstep != 'hide' ) {
                gprev = this.formtabs.gstep;
                ps.className += ' guided-hide';
            }
            if( this.formtabs.gstep != null && this.formtabs.gstep != 'hide' ) {
                if( this.gsteps[this.formtabs.gstep] == null ) {
                    this.gsteps[this.formtabs.gstep] = {'elements':[]};
                }
                this.gsteps[this.formtabs.gstep].elements.push({'e':this.panelUID + '_section_formtabs'});
            }
            if( this.gstep > 0 ) {
                if( this.gstep == this.formtabs.gstep ) {
                    gstepfound = 'yes';
                }
            }
        }

        //
        // Check which form should be shown
        //
        if( this.formtab != '' ) {
            this.sections = this.forms[this.formtab];
        }
    }

    for(var i in this.sections) {
        var r = this.createSection(i, this.sections[i]);
        if( r != null ) {
            // Guided access mode - determine visibility of section
            if( this.sections[i].gstep != null && this.sections[i].gstep != 'hide' ) {
                if( this.gsteps[this.sections[i].gstep] == null ) {
                    this.gsteps[this.sections[i].gstep] = {'elements':[]};
                }
                this.gsteps[this.sections[i].gstep].elements.push({'e':this.panelUID + '_section_' + i});
            }
            if( this.gstep > 0 ) {
                glast = this.sections[i].gstep;
                if( (gstepfound == 'yes' && this.gstep == this.sections[i].gstep)
                    || (gstepfound == 'no' && this.gstep <= this.sections[i].gstep) ) {
                    gstepfound = 'yes';
                }
            }
            // Add the panel
            f.appendChild(r);
        }
    }

    //
    // Build the list of gstep buttons, but hide it until needed
    //
    this.gstep_number = Object.keys(this.gsteps).length;
    if( this.gstep_number > 1 ) {
        //
        // Setup nav bar
        //
        var t = M.addTable(this.panelUID + '_gstepnav_top', 'list stepnav stepnav-top noheader border guided-show');
        var th = M.aE('tbody');
        var tr = M.aE('tr');

        // Previous
        var c1 = M.aE('td',null,'prev clickable', '<span class="icon">l</span>');
        c1.setAttribute('onclick', this.panelRef + '.gstepPrev();');
        tr.appendChild(c1);
        var c1 = M.aE('td',null,'prev prevtext clickable', 'prev');
        c1.setAttribute('onclick', this.panelRef + '.gstepPrev();');
        tr.appendChild(c1);

        // Current Step
        var c2 = M.aE('td',this.panelUID + '_gstepnav_top_position','position', '');
        tr.appendChild(c2);

        // Next
        c3 = M.aE('td',null,'next nexttext clickable', 'next');
        c3.setAttribute('onclick', this.panelRef + '.gstepNext();');
        tr.appendChild(c3);
        c3 = M.aE('td',null,'next clickable', '<span class="icon">r</span>');
        c3.setAttribute('onclick', this.panelRef + '.gstepNext();');
        tr.appendChild(c3);
        th.appendChild(tr);
        t.appendChild(th);
        f.insertBefore(t, f.children[0]);

        var bts = {};
        if( this.gsaveBtn != null ) {
            bts._save = this.gsaveBtn;
        }
        var r = this.createSection('gstepbuttons', {'label':'', 'buttons':bts});
        r.className += ' guided-show';
        f.appendChild(r);

        //
        // Setup nav bar
        //
        var t = M.addTable(this.panelUID + '_gstepnav_bottom', 'list stepnav stepnav-bottom noheader border guided-show');
        var th = M.aE('tbody');
        var tr = M.aE('tr');

        // Previous
        var c1 = M.aE('td',null,'prev clickable', '<span class="icon">l</span>');
        c1.setAttribute('onclick', this.panelRef + '.gstepPrev();');
        tr.appendChild(c1);
        var c1 = M.aE('td',null,'prev prevtext clickable', 'back');
        c1.setAttribute('onclick', this.panelRef + '.gstepPrev();');
        tr.appendChild(c1);

        // Current Step
        var c2 = M.aE('td',this.panelUID + '_gstepnav_bottom_position','position', '');
        c3.setAttribute('onclick', this.panelRef + '.gstepNext();');
        tr.appendChild(c2);

        // Next
        c3 = M.aE('td',null,'next nexttext clickable', 'next');
        c3.setAttribute('onclick', this.panelRef + '.gstepNext();');
        tr.appendChild(c3);
        c3 = M.aE('td',null,'next clickable', '<span class="icon">r</span>');
        c3.setAttribute('onclick', this.panelRef + '.gstepNext();');
        tr.appendChild(c3);
        th.appendChild(tr);
        t.appendChild(th);
        f.appendChild(t);

        var bts = {};
        if( this.gsaveBtn != null ) {
            bts._save = this.gsaveBtn;
        }
        var r = this.createSection('gstepbuttons', {'label':'', 'buttons':bts});
        r.className += ' guided-show';
        f.appendChild(r);
    }

    return f;
};

M.panel.prototype.menutabSwitch = function(t) {
    this.menutabs.selected = t;
    eval(this.menutabs.tabs[t].fn);
}

M.panel.prototype.gstepPrev = function() {
    this.gstep--;
    while(this.gstep > 1 && this.gsteps[this.gstep] == null ) { this.gstep--; }
    this.gstepGoto(this.gstep);
};

M.panel.prototype.gstepNext = function() {
    var prev = 0;
    var next = 0;
    for(i in this.gsteps) {
        if( prev == this.gstep ) {
            this.gstep = i;
            break;
        }
        prev = i;
    }
    this.gstepGoto(this.gstep);
};

M.panel.prototype.gstepGoto = function(gstep) {
    var prev = 0;
    var next = 0;
    var gstepfound = 'no';
    this.gstep = gstep;
    var step_num = 1;
    var num_steps = 0;
    for(var i in this.gsteps) {
        i = parseInt(i);
        if( this.gstep > i ) {
            prev = i;
        } else if( this.gstep < i && next == 0 ) {
            next = i;
        }
        if( gstepfound == 'no' ) {
            if( this.gstep == i ) {
                gstepfound = 'yes';
            } else if( this.gstep < i ) {
                this.gstep = i;
                gstepfound = 'yes';
                next = 0;
            }
        }
        if( this.gstep == i ) {
            // Show the elements for this step
            for(var j in this.gsteps[i].elements) {
                var e = M.gE(this.gsteps[i].elements[j].e);
                e.className = e.className.replace(/guided-hide/, 'guided-selected');
            }
        } else {
            // Hide the other elements
            for(var j in this.gsteps[i].elements) {
                var e = M.gE(this.gsteps[i].elements[j].e);
                e.className = e.className.replace(/guided-selected/, 'guided-hide');
            }
        }
        if( i < this.gstep ) {
            step_num++;
        }
        num_steps++;
    }

    if( num_steps > 1 ) {
        var et = M.gE(this.panelUID + '_gstepnav_top_position');
        if( et != null ) { et.innerHTML = 'Step ' + step_num + ' of ' + num_steps; }
        var eb = M.gE(this.panelUID + '_gstepnav_bottom_position');
        if( eb != null ) { eb.innerHTML = 'Step ' + step_num + ' of ' + num_steps; }
        if( prev == 0 ) {
            et.previousSibling.innerHTML = '';
            eb.previousSibling.innerHTML = '';
            et.previousSibling.previousSibling.innerHTML = '<span class="icon"></span>';
            eb.previousSibling.previousSibling.innerHTML = '<span class="icon"></span>';
        } else {
            et.previousSibling.innerHTML = 'back';
            eb.previousSibling.innerHTML = 'back';
            et.previousSibling.previousSibling.innerHTML = '<span class="icon">l</span>';
            eb.previousSibling.previousSibling.innerHTML = '<span class="icon">l</span>';
        }
        if( next == 0 ) {
            et.nextSibling.innerHTML = '';
            eb.nextSibling.innerHTML = '';
            et.nextSibling.nextSibling.innerHTML = '<span class="icon"></span>';
            eb.nextSibling.nextSibling.innerHTML = '<span class="icon"></span>';
        } else {
            et.nextSibling.innerHTML = 'next';
            eb.nextSibling.innerHTML = 'next';
            et.nextSibling.nextSibling.innerHTML = '<span class="icon">r</span>';
            eb.nextSibling.nextSibling.innerHTML = '<span class="icon">r</span>';
        }
    }
    window.scrollTo(0, 0);
};

//
// Default section label, can be overriden in panel
//
M.panel.prototype.sectionLabel = function(i, s) {
    if( s.hidelabel != null && s.hidelabel == 'yes' ) { return null; }
    return s.label;
};

M.panel.prototype.sectionType = function(i, s) {
    return s.type;
};

M.panel.prototype.sectionData = function(i) {
    if( this.sections[i].type != null && this.sections[i].type == 'html' && this.sections[i].html != null ) { return this.sections[i].html; }
    if( this.sections[i].type != null && this.sections[i].type == 'htmlcontent' && this.sections[i].html != null ) { return this.sections[i].html; }
    if( (this.sections[i].type == null || this.sections[i].type == 'simplelist') && this.sections[i].list != null ) { return this.sections[i].list; }
    if( this.data[i] != null ) { return this.data[i]; }
    return {};
};

M.panel.prototype.createSection = function(i, s) {
    // 
    // Check if section is visible
    //
    if( typeof s.visible == 'function' && s.visible() == 'no' ) {
        return null;
    } else if( s.visible != null && s.visible == 'no' ) {
        return null;
    }
    if( typeof s.active == 'function' && s.active() == 'no' ) {
        return null;
    } else if( s.active != null && s.active == 'no' ) {
        return null;
    }
        
    var type = this.sectionType(i, s);
    
    if( s.aside != null ) {
        if( s.aside == 'yes' || s.aside == 'left' ) {
            var f = M.aE('div', this.panelUID + '_section_' + i, 'panelsection asideleft');
        } else if( s.aside == 'left' ) {
            var f = M.aE('div', this.panelUID + '_section_' + i, 'panelsection aside'); 
        } else {
            var f = M.aE('div', this.panelUID + '_section_' + i, 'panelsection'); 
        }
    } else if( s.aside != null && s.aside == 'fullwidth' ) {
        var f = M.aE('div', this.panelUID + '_section_' + i, 'panelsection fullwidth');
    } else if( type == 'paneltabs' ) {
        var f = M.aE('div', this.panelUID + '_section_' + i, 'panelsection paneltabs');
    } else if( type == 'menutabs' ) {
        var f = M.aE('div', this.panelUID + '_section_' + i, 'panelsection menutabs');
    } else if( type == 'copytext' ) {
        var f = M.aE('div', this.panelUID + '_section_' + i, 'panelsection copysection');
    } else {
        var f = M.aE('div', this.panelUID + '_section_' + i, 'panelsection');
    }

    if( s.gstep != null ) {
        f.className += ' guided-hide';
    }

    if( typeof s.visible == 'function' && s.visible() == 'hidden' ) {
        f.style.display = 'none';
    } else if( s.visible != null && s.visible == 'hidden' ) {
        f.style.display = 'none';
    }

    //
    // Check if there should be label
    //
    var lE = null;
    gt = null;
    if( s.gtitle != null && typeof s.gtitle == 'function' ) {
        gt = s.gtitle(this);
    } else if( this.sectionGuidedTitle != null ) {
        gt = this.sectionGuidedTitle(i);
    } else if( s.gtitle != null && s.gtitle != '' ) {
        gt = s.gtitle;
    }
    var t = this.sectionLabel(i, s);
    if( t != null && t != '' ) {
        if( s.multi != null && s.multi == 'yes' ) {
            // If the form supports ability for duplicate sections of the form to be created
            lE = M.addSectionLabel(s.label + ' <span class="rbutton_off clickable" onclick="M.' + this.appID + '.' + this.name + '.dupFormSection(\'' + i + '\');"><span class="icon">a</span></span>', -1);
//        } else if( this.sectionCount != null ) {
//            lE = M.addSectionLabel(t, this.sectionCount(i, s));
        } else {
            // -1 means don't display count
            lE = M.addSectionLabel(t, -1);
        }
        lE.className += ' guided-hide';
        f.appendChild(M.aE('h2', null, 'guided-title guided-show', (gt!=null&&gt!=''?gt:t)));
        f.appendChild(lE);
    } else if( gt != null ) {
        f.appendChild(M.aE('h2', null, 'guided-title guided-show', (gt!=null&&gt!=''?gt:t)));
    }

    // Check if addFn exists and display link to right of header
    if( lE != null && (
        (s.addTopFn != null && s.addTopFn != '') 
        || (s.addFn != null && s.addFn != '' && s.addTxt != null && s.addTxt != '' && (s.changeTxt == null || s.changeTxt == ''))
        ) ) {
        var data = null;
        if( this.sectionData != null ) {
            data = this.sectionData(i);
        } else if( sc.data != null ) {
            data = this.sections[i].data;
        }
        // If addTopFn is specified, then no bottom row is display, so it must show up at the top
        if( s.addTopFn != null || (data != null && (data.length > 0 || M.length(data) > 0) || M.length(this.sections[i].fields)) ) {
            var c = M.aE('span', null, 'addlink alignright clickable', '+ ' + s.addTxt);
            // Add arrow
            if( s.addTopFn != null ) {
                c.setAttribute('onclick', s.addTopFn);
            } else {
                c.setAttribute('onclick', s.addFn);
            }
            lE.appendChild(c);
        }
    }

    var gt = null;
    if( s.gtext != null && typeof s.gtext == 'function' ) {
        gt = s.gtext(this);    
    } else if( this.sectionGuidedText != null ) {
        gt = this.sectionGuidedText(i);
    } else if( s.gtext != null && s.gtext != '' ) {
        gt = s.gtext;
    }
    if( gt != null ) { f.appendChild(M.aE('p', null, 'guided-text guided-show', gt)); }

    //
    // Get the section 
    //
    var tid = null;        // Section table ID
    var st = null;         // Section table
    if( type == 'simplelist' || (type == null && s.list != null) ) {
        if( this.sectionData != null ) {
            st = this.createSimpleList(i, this.sectionData(i, s), s.as);
        } else {
            st = this.createSimpleList(i, s.list, s.as);
        }
    } else if( type == 'configtext' ) {
        st = this.createText(i, 'pre');
    } else if( type == 'pre' ) {
        st = this.createText(i, 'pre');
    } else if( type == 'simplethread' ) {
        st = this.createSimpleThread(i);
    } else if( type == 'simplebuttons' || (type == null && s.buttons != null) ) {
        st = this.createSimpleButtons(i, s.buttons, 'no');
    } else if( type == 'livesearchgrid' ) {
        st = this.createLiveSearchGrid(i, s);
    } else if( type == 'simplegrid' ) {
        if( s.visible == null || (s.visible != null && s.visible != 'no') ) {
            st = this.createSectionGrid(i);
            tid = st.childNodes[0].id;
        }
    } else if( type == 'treegrid' ) {
        if( s.visible == null || (s.visible != null && s.visible == 'yes') ) {
            st = this.createSectionTreeGrid(i, 0, null, this.sectionData(i, s));
            tid = st.childNodes[0].id;
        }
    } else if( type != null && type == 'gridform' ) {
        st = this.createFormGridFields(this.panelUID, i, s);
    } else if( type == 'simpleform' || type == 'imageform' || type == 'copytext' || (type == null && s.fields != null) ) {
        st = this.createSectionForm(i, s.fields);        
//        tb = M.aE('tbody');
//        var ct = this.createFormFields(i, tb, this.panelUID, s.fields);
//        tid = this.panelUID + '_' + i;
//        if( ct == 0 || ct > 1 ) {
//            st = M.addTable(tid, 'list noheader form outline');
//        } else {
//            st = M.addTable(tid, 'list noheader form outline');
//        }
//        st.appendChild(tb);
    } else if( type == 'datepicker' ) {
        st = this.createDatePicker(i, s);
    } else if( type == 'weekpicker' ) {
        st = this.createDatePicker(i, s);
    } else if( type == 'dayschedule' ) {
        st = this.createDailySchedule(i, s);
    } else if( type == 'mwschedule' ) {
        st = this.createMultiWeekSchedule(i, s);
    } else if( type == 'paneltabs' || type == 'menutabs' ) {
        st = this.createPanelTabs(i, this.sections[i]);
    } else if( type == 'html' ) {
        st = this.createHtml(i, this.sections[i]);
    } else if( type == 'htmlcontent' ) {
        st = this.createHtmlContent(i, this.sections[i]);
    } else if( type == 'simplethumbs' ) {
        st = this.createSimpleThumbnails(i);
    } else if( type == 'audiolist' ) {
        st = this.createAudioList(i);
    } else if( type == 'chart' ) {
        st = this.createChart(i);
    } else if( type == 'metricsgraphics' ) {
        st = this.createMetricsGraphics(i);
    } else if( type == 'heatmap' ) {
        st = this.createHeatmap(i);
    } else {
        console.log('Missing section type for: ' + s.label);
        st = document.createDocumentFragment();
    }

    // Add the section table
    f.appendChild(st);

    //
    // Check if section is collapsable
    //
    if( tid != null && tid != '' && lE != null && s.collapsable != null && s.collapsable == 'yes' ) {
        lE.className = 'clickable';
        lE.setAttribute('onclick', 'M.toggleSection(this, \'' + tid + '\');');
        if( s.collapse != null && ((M.size == 'compact' && s.collapse == 'compact') || s.collapse == 'all') ) {
            lE.innerHTML = '<span class="icon">+</span> ' + lE.innerHTML;
            st.style.display = 'none';
        } else {
            lE.innerHTML = '<span class="icon">-</span> ' + lE.innerHTML;
        }
    }

    var gt = null;
    if( s.gmore != null && typeof s.gmore == 'function' ) {
        gt = s.gmore(this);
    } else if( this.sectionGuidedMore != null ) {
        gt = this.sectionGuidedMore(i);
    } else if( s.gmore != null && s.gmore != '' ) {
        gt = s.gmore;
    }
    if( gt != null ) { f.appendChild(M.aE('p', null, 'guided-text guided-show', gt)); }
    return f;
};

M.panel.prototype.createHeatmap = function(s) {
    var f = document.createDocumentFragment();

    var t = M.addTable(this.panelUID + '_' + s, 'heatmap');
    var tb = M.aE('tbody');

    // Load the heatmap data
    var hm = this.heatmapData(s); 
    var range = hm.min - hm.max;
    var yscale = 10;
    var xscale = 20;

    if( hm.xlabels != null ) {
        var tr = M.aE('tr');
        var c = M.aE('th',null,'', '');
        tr.appendChild(c);
        for(var j in hm.xlabels) {
            if( (j%xscale) == 0 ) {
                if( (parseInt(j)+xscale) > hm.xlabels.length ) {
                    break;
                }
                var c = M.aE('th',null,'xlabel');
                c.innerHTML = hm.xlabels[j];
                c.colSpan = xscale;
                tr.appendChild(c);
            }
        }
        tb.appendChild(tr);
    }
    
    for(var i in hm.data) {
        var tr = M.aE('tr');
        if( (i%yscale) == 0 ) {
            var c = M.aE('th',null,'ylabel');
            c.innerHTML = hm.data[i].time;
            c.rowSpan = yscale;
            tr.appendChild(c);
        }

        for(var j in hm.data[i].samples) {
            if( hm.data[i].samples[j] == 0 ) {
                tr.appendChild(M.aE('td',null,'nodata','')); //hm.data[i].samples[j]));
            } else if( hm.data[i].samples[j] == 1 ) {
                tr.appendChild(M.aE('td',null,'scrubbed','')); //hm.data[i].samples[j]));
            } else {
                //
                // max -6287
                // min -6955
                // val -6500
                
                var n = Math.abs(((hm.data[i].samples[j] - hm.min)/range)*10);
                tr.appendChild(M.aE('td',null,'data-'+n.toFixed(0),'')); //n.toFixed(0)));
            }
        }
        tb.appendChild(tr);
    }
     
   /* 

    var tr = M.aE('tr');
    var c = M.aE('td',null,'');
    c.innerHTML = '<canvas id="' + this.panelUID + '_' + s + '_canvas"></canvas>';
    tr.appendChild(c);
    tb.appendChild(tr);
   */
    t.appendChild(tb);

    f.appendChild(t);
    return f;
}

M.panel.prototype.createMetricsGraphics = function(s) {
    var f = document.createDocumentFragment();

    var data = null;
    if( this.sectionData != null ) {
        data = this.sectionData(s);
    } else if( sc.data != null ) {
        data = sc.data;
    } 

    var t = M.addTable(this.panelUID + '_' + s, 'list metricsgraphics border noheader');
    var tb = M.aE('tbody');
    var tr = M.aE('tr');
    var c = M.aE('td',null,'');
    c.innerHTML = '<div id="' + this.panelUID + '_' + s + '_canvas"></div><div id="' + this.panelUID + '_' + s + '_legend" class="legend"></div>';
    tr.appendChild(c);
    tb.appendChild(tr);
    t.appendChild(tb);
    f.appendChild(t);

    if( typeof MG == "undefined" ) {
        M.startLoad();
        var script = document.createElement('script');
        script.type = 'text/javascript';
        script.src = '/ciniki-mods/core/ui/d3.v5.metricsgraphics.min.js?v=2.11.0';
        var done = false;
        var head = document.getElementsByTagName('head')[0];
        var cb = this.panelRef + '.createMetricsGraphicsContent("' + s + '");';

        script.onerror = function() {
            M.stopLoad();
            alert("Unable to load, please report this bug.");
        };

        // Attach handlers for all browsers
        script.onload = script.onreadystatechange = function() {
            M.stopLoad();
            if(!done&&(!this.readyState||this.readyState==="loaded"||this.readyState==="complete")){
                done = true;
                
                eval(cb);
               
                // Handle memory leak in IE
                script.onload = script.onreadystatechange = null;
                if(head&&script.parentNode){
                    head.removeChild( script );
                }    
            }    
        };
        head.appendChild(script);
    } else {
        this.onShowCbs.push(this.panelRef + '.createMetricsGraphicsContent("' + s + '");');
    }

    return f;
}

M.panel.prototype.createMetricsGraphicsContent = function(s) {

    var sc = this.sections[s];
    var data = null;
    if( this.sectionData != null ) {
        data = this.sectionData(s);
    } else if( sc.data != null ) {
        data = sc.data;
    } 
    if( data == null ) {
        console.log('No data for chart');
        return false;
    }

    if( sc.graphtype != null && sc.graphtype == 'multiline' ) {
        for(var i = 0;i < data.length; i++) {
            data[i] = MG.convert.date(data[i], 'date', '%Y-%m-%d %H:%M:%S');
        }
    } else {
        data = MG.convert.date(data, 'date', '%Y-%m-%d %H:%M:%S');
    }
//    var dt = new Date();
    MG.data_graphic({
        data:data,
        width: 700,
        height: 200,
        right: 0,
        top: 20,
        full_width: true,
        missing_is_hidden: true,
        target: '#' + this.panelUID + '_' + s + '_canvas',
        legend: this.sections[s].legend,
        legend_target: '#' + this.panelUID + '_' + s + '_legend',
        });

}

M.panel.prototype.createChart = function(s) {
    var f = document.createDocumentFragment();

    var t = M.addTable(this.panelUID + '_' + s, 'list chart border noheader');
    var tb = M.aE('tbody');
    var tr = M.aE('tr');
    var c = M.aE('td',null,'');
    c.innerHTML = '<canvas id="' + this.panelUID + '_' + s + '_canvas"></canvas>';
    tr.appendChild(c);
    tb.appendChild(tr);
    t.appendChild(tb);

    f.appendChild(t);

    if( typeof Chart == "undefined" ) {
        M.startLoad();
        var script = document.createElement('script');
        script.type = 'text/javascript';
        // Hack to get around cached data
        var d = new Date();
        var t = d.getTime();
        // ciniki.users.prefs -> /ciniki-mods/users/ui/prefs.js
        script.src = '/ciniki-mods/core/ui/Chart.min.js?v=2.1.4';
        var done = false;
        var head = document.getElementsByTagName('head')[0];
        var cb = this.panelRef + '.createChartContent("' + s + '");';

        script.onerror = function() {
            M.stopLoad();
            alert("Unable to load, please report this bug.");
        };

        // Attach handlers for all browsers
        script.onload = script.onreadystatechange = function() {
            M.stopLoad();
            if(!done&&(!this.readyState||this.readyState==="loaded"||this.readyState==="complete")){
                done = true;
                
                eval(cb);
               
                // Handle memory leak in IE
                script.onload = script.onreadystatechange = null;
                if(head&&script.parentNode){
                    head.removeChild( script );
                }    
            }    
        };
        head.appendChild(script);
    } else {
        this.onShowCbs.push(this.panelRef + '.createChartContent("' + s + '");');
    }

    return f;
};

M.panel.prototype.createChartContent = function(s) {

    var sc = this.sections[s];
    var data = null;
    if( this.sectionData != null ) {
        data = this.sectionData(s);
    } else if( sc.data != null ) {
        data = sc.data;
    } 
    if( data == null ) {
        console.log('No data for chart');
        return false;
    }
    if( this.sections[s].chart_overlay != null ) {  
        this.sections[s].chart_overlay.destroy();
    }

    this.sections[s].chart_overlay = new Chart(document.getElementById(this.panelUID + '_' + s + '_canvas').getContext("2d"), {
        type:'bar',
        data: data,
        options: {
            responsive: true,
            legend: {
                display: true,
                position: 'bottom',
            },
            scales: {
                xAxes: [{
                    ticks:{
                        beginAtZero: (sc.scaleBeginAtZero!=null?sc.scaleBeginAtZero:false),
                        },
                }],
                yAxes: [{
                    display: true,
                    ticks: {
                        beginAtZero: (sc.scaleBeginAtZero!=null?sc.scaleBeginAtZero:false),
                        userCallback: (sc.scaleLabelCallback!=null?sc.scaleLabelCallback:false),
//                        userCallback: function(dataLabel, index) { return dataLabel + '%';},
                    }
                }],
            },
            tooltips: {
                mode: 'single',
                callbacks: {
                    label: (sc.tooltipLabelCallback!=null?sc.tooltipLabelCallback:false),
//                    label: function(tooltipItem, data) { return tooltipItem.yLabel + '%';},
                },
            },
        }
//        scaleBeginAtZero: (sc.scaleBeginAtZero!=null?sc.scaleBeginAtZero:false), 
//        populateSparseData: (sc.populateSparseData!=null?sc.populateSparseData:true), 
//        scaleLabel: (sc.scaleLabel!=null?sc.scaleLabel:"<%=value%>"), 
//        tooltipTemplate: (sc.tooltopTemplate!=null?sc.tooltopTemplate:"<%=value%>"),
//        multiTooltipTemplate: (sc.multiTooltipTemplate!=null?sc.multiTooltipTemplate:"<%=value%>"),
//        responsive: true,
//        datasetFill: false,
        });
};

M.panel.prototype.createAudioList = function(s) {
    var sc = this.sections[s];
    var data = null;
    if( this.sectionData != null ) {
        data = this.sectionData(s);
    } else if( sc.data != null ) {
        data = sc.data;
    }
    var f = document.createDocumentFragment();

    //
    // Table header
    //
    var num_cols = sc.num_cols;
    var t = this.createSectionGridHeaders(s, sc);

    //
    // Table body
    //
    var tb = M.aE('tbody');
    var ct = 0;
    for(i in data) {
        if( sc.limit != null && sc.limit != '' ) {
            if( ct >= sc.limit ) { break; }
        }
        var tr = M.aE('tr');
        var ptr = tr;
        if( this.rowStyle != null ) { 
            tr.setAttribute('style', this.rowStyle(s, i, data[i]));
        }
        var rcl = '';
        if( this.rowClass != null ) {
            rcl = ' ' + this.rowClass(s, i, data[i]);
            tr.className = rcl;
        }

        v = this.cellValue(s, i, 0, data[i]);

        c = M.aE('td',null,'',v);
        tr.appendChild(c);

        // Add the arrow to click on
        if( this.rowFn != null && this.rowFn(s, i, data[i]) != null ) {
            c = M.aE('td', null, 'buttons noprint');
            var fn = this.rowFn(s, i, data[i]);
            if( fn != '' ) {
                ptr.setAttribute('onclick', this.rowFn(s, i, data[i]));
                c.innerHTML = '<span class="icon">r</span>';
                ptr.className = 'clickable' + rcl;
            }
            ptr.appendChild(c);
        }

        tb.appendChild(tr);
        ct++;
    }
    if( ct == 0 && this.noData != null ) {
        if( typeof this.noData == 'function' ) {
            var nd = this.noData(s);
        } else {
            var nd = this.noData;
        }
        if( nd != null && nd != '' ) {
            var tr = M.aE('tr');
            var td = M.aE('td', null, null, nd);
            if( M.size == 'compact' && sc.compact_split_at != null ) {
                td.colSpan = sc.compact_split_at + 1;
            } else {
                td.colSpan = num_cols + 1;
            }
            tr.appendChild(td);
            tb.appendChild(tr);
            t.appendChild(tb);
        }
    }
    else if( ct > 0 ) {
        t.appendChild(tb);
    }

    //
    // Add a row for the add button
    //
    var tf = M.aE('tfoot');
    if( this.footerValue(s, 0, sc) != null ) {
        var tr = this.createSectionGridFooters(s, sc);
        tf.appendChild(tr);
    }

    if( data != null && data.length > sc.limit && sc.moreTxt != null && sc.moreTxt != '' && sc.moreFn != null && sc.moreFn != '' ) {
        var tr = M.aE('tr');
        var td = M.aE('td', null, 'addlink aligncenter', sc.moreTxt);
        if( M.size == 'compact' && sc.compact_split_at != null ) {
            td.colSpan = sc.compact_split_at;
        } else {
            td.colSpan = num_cols;
        }
        tr.appendChild(td);
        // Add arrow
        c = M.aE('td', null, 'buttons noprint');
        tr.setAttribute('onclick', sc.moreFn);
        c.innerHTML = '<span class="icon">r</span>';
        tr.className = 'clickable';
        tr.appendChild(c);
        tf.appendChild(tr);
    }
    if( sc.addFn != null && sc.addFn != '' && sc.addTxt != null && sc.addTxt != '' ) {
        var tr = M.aE('tr');
        var td = M.aE('td', null, 'addlink aligncenter', sc.addTxt);
        if( M.size == 'compact' && sc.compact_split_at != null ) {
            td.colSpan = sc.compact_split_at;
        } else {
            td.colSpan = num_cols;
        }
        tr.appendChild(td);
        // Add arrow
        c = M.aE('td', null, 'buttons noprint');
        tr.setAttribute('onclick', sc.addFn);
        c.innerHTML = '<span class="icon">r</span>';
        tr.className = 'clickable';
        tr.appendChild(c);
        tf.appendChild(tr);
    }

    //
    // Add a row for the add button
    //
    if( sc.changeFn != null && sc.changeFn != '' && sc.changeTxt != '' ) {
        var tr = M.aE('tr');
        var td = M.aE('td', null, 'addlink aligncenter', sc.changeTxt);
        if( M.size == 'compact' && sc.compact_split_at != null ) {
            td.colSpan = sc.compact_split_at;
        } else {
            td.colSpan = num_cols;
        }
        tr.appendChild(td);
        // Add arrow
        c = M.aE('td', null, 'buttons noprint');
        tr.setAttribute('onclick', sc.changeFn);
        c.innerHTML = '<span class="icon">r</span>';
        tr.className = 'clickable';
        tr.appendChild(c);
        tf.appendChild(tr);
    }
    if( tf.childNodes.length > 0 ) {
        t.appendChild(tf);
    }
    
    f.appendChild(t);

    return f;
};

M.panel.prototype.createSimpleThumbnails = function(s) {
    var sc = this.sections[s];
    var data = null;
    if( this.sectionData != null ) {
        data = this.sectionData(s);
    } else if( sc.data != null ) {
        data = sc.data;
    }
//    var f = document.createDocumentFragment();
    var f = M.aE('div', null, 'media');
    if( data == null ) { return f; }

    for(i in data) {
        var src = this.thumbSrc(s, i, data[i]);
        var title = this.thumbTitle(s, i, data[i]);
        var id = this.thumbID(s, i, data[i]);
        var fn = null;
        if( this.thumbFn != null ) {
            fn = this.thumbFn(s, i, data[i]);
        }
        
//        var d1 = M.aE('div', 'media_' + id, 'media_thumb media_' + type + ' clickable', '');
        var d1 = M.aE('div', 'media_' + id, 'media_thumb media_image clickable', '');
        if( fn != null ) { d1.setAttribute('onclick', fn); }
        var d2 = M.aE('div', null, null, '');
        var d3 = M.aE('div', null, 'imgwrap');
        var im = new Image();
        im.setAttribute('draggable', 'false');
        im.src = src;
        d3.appendChild(im);

        var d4 = M.aE('div', null, 'titlewrap', title);
        d4.setAttribute('draggable', 'false');
        d3.setAttribute('draggable', 'false');
        d2.setAttribute('draggable', 'false');

        d2.appendChild(d3);
        d2.appendChild(d4);
        d1.appendChild(d2);

        // FIXME: ready to make dragdrop for organization of photos,
        // use code from createSimpleMedia
    
        f.appendChild(d1);
    }

    return f;
};

M.panel.prototype.thumbSrc = function(s, i, d) {
    if( d.image_id > 0 && d.image_data != null && d.image_data != '' ) {
        return d.image_data;
    } else if( d.image != null && d.image.image_id > 0 && d.image.image_data != null && d.image.image_data != '' ) {
        return d.image.image_data;
    } else {
        return '/ciniki-mods/core/ui/themes/default/img/noimage_75.jpg';
    }
};

M.panel.prototype.thumbTitle = function(s, i, d) {
    if( d.name != null ) { return d.name; }
    if( d.title != null ) { return d.title; }
    if( d.image != null && d.image.name != null ) { return d.image.name; }
    return '';
};

M.panel.prototype.thumbID = function(s, i, d) {
    if( d.id != null ) { return d.id; }
    if( d.image.id != null ) { return d.image.id; }
    return 0;
};

M.panel.prototype.createMultiWeekSchedule = function(s, d) {
    if( this.sectionData != null ) {
        data = this.sectionData(s);
    } else if( this.data[s] != null ) {
        data = this.data[s];
    }
    var t = this.generateMultiWeekScheduleTable(s, 'list header mwschedule', data, this.start_date, this.end_date);
    return t;
};

M.panel.prototype.createDailySchedule = function(s, d) {
    var adate = this.scheduleDate(s, this.sections[s]);
    if( this.sectionData != null ) {
        data = this.sectionData(s);
    } else if( this.data[s] != null ) {
        data = this.data[s];
    }

    var t = this.generateAppointmentScheduleTable(this.sections[s], null, 'list noheader dayschedule', data, 30, adate);
    return t;
};

M.panel.prototype.createText = function(s, type) {
    var f = document.createDocumentFragment();
    //
    // Setup the header for the list
    //
    // FIXME: Add ability to have title
    //    var th = M.aE('thead');
    //    var tr = M.aE('tr');
    //    var c = M.aE('th',null,null,this.threadSubject(s));
    //    c.colSpan = 2;
    //    tr.appendChild(c);
    //    th.appendChild(tr);
    //    t.appendChild(th);
    if( this.sectionData != null ) {
        data = this.sectionData(s, this.sections[s]);
    } else {
        data = '';
    }
    if( data == null ) { return f; }

    var tb = M.aE('tbody');
    t = M.addTable(this.panelUID + '_text', 'list noheader border text');
    var tr = M.aE('tr', null, 'text');
    var td = M.aE('td');
    td.appendChild(M.aE('pre', null, type + 'text', data));
    tr.appendChild(td);
    tb.appendChild(tr);

    t.appendChild(tb);
    f.appendChild(t);

    return f;
};

M.panel.prototype.createLiveSearchGrid = function(s, sd) {
    var frag = document.createDocumentFragment();

    //
    // Create the first table to show the search field
    //
    var t1 = M.addTable(this.panelUID + '_' + s + '_livesearch', 'list livesearch noheader form noborder');
    var tb = M.aE('tbody');
    var tr = M.aE('tr');
    var scount = '';
    if( sd.count != null && sd.count != '' ) {
        scount = ' <span class="count">' + sd.count + '</span>';
    }
    if( sd.searchlabel != null && sd.searchlabel != '') {
        tr.appendChild(M.aE('td',null,'',sd.searchlabel + scount, sd.fn));
        tr.className = 'livesearchgridbutton clickable';
        t1.className = 'list simplelist noheader border';
    } else {
        tr.className = 'textfield';
    }
    // Only add the field if this is not Compact and not a button
    if( (sd.fn == null || sd.fn == '' || M.size != 'compact') && (sd.hidesearch == null || sd.hidesearch != 'yes') ) {
        var c = M.aE('td', null, 'search');

        var f = M.aE('input', this.panelUID + '_' + s, 'search');
        f.setAttribute('name', s);
        f.setAttribute('type', 'search');
        if( sd.hint != null && sd.hint != '' ) {
            f.setAttribute('placeholder', sd.hint);
        }
        if( sd.autofocus != null && sd.autofocus == 'yes' ) {
            this.autofocus = this.panelUID + '_' + s;
        }
        if( sd.livesearchempty == 'yes' ) {
            f.setAttribute('onfocus', this.panelRef + '.liveSearchSection(\'' + s + '\',null,this,event);');
//        } else {
//            f.setAttribute('onfocus', this.panelRef + '.clearLiveSearches(\''+s+'\',null);');
        }
        f.setAttribute('onkeyup', this.panelRef + '.liveSearchSection(\'' + s + '\',null,this,event);');
        f.setAttribute('onclick', this.panelRef + '.liveSearchSection(\'' + s + '\',null,this,event);');
        //f.setAttribute('onblur', this.panelRef + '.removeLiveSearch(\'' + s + '\',null);');
        f.setAttribute('autocomplete', 'off');
        this.lastSearches[s] = '';
        c.appendChild(f);
        tr.appendChild(c);
    }
    // Check if there's an Add function for this section
    if( sd.addFn != null ) {
        if( sd.addFn != '' ) {
            tr.appendChild(M.aE('td',null,'addbutton noprint', '<span class="icon">a</span>', sd.addFn));
//        } else {
//            tr.appendChild(M.aE('td',null,'button noprint'));
        }
    }
    // Check if there's a function for this section
    if( sd.fn != null ) {
        if( sd.fn != '' ) {
            tr.appendChild(M.aE('td',null,'buttons noprint', '<span class="icon">r</span>', sd.fn));
//            tr.appendChild(M.aE('td',null,'buttons noprint', '<img src=\'' + M.themes_root_url + '/default/img/arrow.png\'>', sd.fn));
        } else {
            tr.appendChild(M.aE('td',null,'buttons noprint'));
        }
    }
    tb.appendChild(tr);
    t1.appendChild(tb);

    frag.appendChild(t1);

    //
    // Create the second table which will display the results, display hidden at first
    //
    frag.appendChild(this.liveSearchResultsTable(s, null, sd));
//    var t = M.addTable(this.panelUID + '_' + s + '_livesearchresults');
//    var tb = M.aE('tbody');
//    var tr = M.aE('tr');
//    var c = M.aE('td');
//
//    if( sd.headerValues != null ) {
//        var t = M.addTable(this.panelUID + '_' + s + '_livesearch_grid', 'list simplegrid header border');
//        var th = M.aE('thead');
//        var tr = M.aE('tr');
//        for(var i=0;i<sd.headerValues.length;i++) {
////            var c = M.aE('th',null,null, sd.headerValues[i]);
//            tr.appendChild(c);
//        }
//        // If there's the possiblity of row being clickable, then add extra column to header for > (arrow).
//        sd.num_cols = sd.headerValues.length;
//        if( this.liveSearchResultRowFn != null ) {
//            sd.num_cols = sd.headerValues.length + 1;
//////            tr.appendChild(M.aE('th', null, 'noprint'));
//        }
//        th.appendChild(tr);
//        t.appendChild(th);
//    } else {
//        var t = M.addTable(this.panelUID + '_' + s + '_livesearch_grid', 'list simplegrid noheader border');
//    }
//    var tb = M.aE('tbody', this.panelUID + '_' + s + '_livesearchresultsgrid');
//    
//    // Table body should be empty, and hide table until there are results
//    t.appendChild(tb);
//    t.style.display = 'none';
//    frag.appendChild(t);

    return frag;
};

M.panel.prototype.liveSearchResultsTable = function(s, f, sd) {
//    var t = M.addTable(this.panelUID + '_' + s + '_livesearchresults');
//    var tb = M.aE('tbody');
//    var tr = M.aE('tr');
//    var c = M.aE('td');

    var n = this.panelUID + '_' + s;    
    if( f != null ) { n += '_' + f; }
    if( sd.livesearchtype == 'appointments' ) {
        var t = M.addTable(n + '_livesearch_grid', 'list dayschedule noheader noborder');
    } else if( sd.fields != null && sd.fields[f] != null && sd.fields[f].headerValues != null ) {
        var t = M.addTable(n + '_livesearch_grid', 'list simplegrid header border');
        var th = M.aE('thead');
        var tr = M.aE('tr');
        for(var i=0;i<sd.fields[f].headerValues.length;i++) {
            var c = M.aE('th',null,null,sd.fields[f].headerValues[i]);
            tr.appendChild(c);
        }
        // If there's the possiblity of row being clickable, then add extra column to header for > (arrow).
        sd.num_cols = sd.fields[f].headerValues.length;
        if( this.liveSearchResultRowFn != null ) {
            sd.num_cols = sd.fields[f].headerValues.length + 1;
            tr.appendChild(M.aE('th', null, 'noprint'));
        }
        th.appendChild(tr);
        t.appendChild(th);
    } else if( sd.headerValues != null ) {
        var t = M.addTable(n + '_livesearch_grid', 'list simplegrid header border');
        var th = M.aE('thead');
        var tr = M.aE('tr');
        for(var i=0;i<sd.headerValues.length;i++) {
            var cl = '';
            if( sd.headerClasses != null && sd.headerClasses[i] != null ) {
                cl += ((cl!='')?' ':'')+sd.headerClasses[i];
            }
            var c = M.aE('th',null,cl,sd.headerValues[i]);
            tr.appendChild(c);
        }
        // If there's the possiblity of row being clickable, then add extra column to header for > (arrow).
        sd.num_cols = sd.headerValues.length;
        if( this.liveSearchResultRowFn != null ) {
            sd.num_cols = sd.headerValues.length + 1;
            tr.appendChild(M.aE('th', null, 'noprint'));
        }
        th.appendChild(tr);
        t.appendChild(th);
    } else {
        var t = M.addTable(n + '_livesearch_grid', 'list simplegrid noheader border');
    }
    var tb = M.aE('tbody', n + '_livesearchresultsgrid');
    
    // Table body should be empty, and hide table until there are results
    t.appendChild(tb);
    t.style.display = 'none';

    return t;
};

//
// The liveSearchSection is used for both searching from fields or sections
//
M.panel.prototype.liveSearchSection = function(s, i, inputElement, event) {
    // Don't clear live searches if it's for a section
    if( i != null ) { this.clearLiveSearches(s, i); }
    var t = null;
    if( i != null ) {
        t = M.gE(this.panelUID + '_' + s + '_' + i + '_livesearch_grid');
    } else {
        t = M.gE(this.panelUID + '_' + s + '_livesearch_grid');
    }
    // Add the results table if it doesn't already exist
    if( t == null && i != null ) {  
        var tr = M.aE('tr', null, 'searchresults');
        var rC = M.aE('td', null, 'searchresults');
        rC.colSpan = 3;
        t = this.liveSearchResultsTable(s, i, this.sections[s]);
        rC.appendChild(t);
        tr.appendChild(rC);
        inputElement.parentNode.parentNode.parentNode.insertBefore(tr, inputElement.parentNode.parentNode.nextSibling);
//      } else {
//          M.clr(rT);
    } 

    t.style.display = 'table';
    // Store a list of tables which have live searches
    this.liveSearchTables[t.id] = 'on';

    var sc = this.sections[s];
//    if( sc.livesearchempty && inputElement.value 
    //
    // Check for enter key, and submit search
    //
    if( event.which == 13 && this.liveSearchSubmitFn != null && inputElement.value != '' ) {
        // Remove search results
        this.liveSearchSubmitFn(s, inputElement.value);
    }

//    if( (inputElement.value == '' && ((i != null && (sc.fields[i] != null && sc.fields[i].livesearchempty != null && sc.fields[i].livesearchempty == 'yes'))
//            || (i == null && sc.livesearchempty != null && sc.livesearchempty == 'yes')) )
    if( (inputElement.value == '' && ((i != null && (sc.fields[i] != null && sc.fields[i].livesearchempty != null && sc.fields[i].livesearchempty == 'yes'))
            || (sc.livesearchempty != null && sc.livesearchempty == 'yes')) )
        || (inputElement.value != '' && (
            this.lastSearches[s] == ''
            || this.lastSearches[s] == null
            || (this.lastSearches[s] != null && inputElement.value.length <= this.lastSearches[s].length)
            || this.lastSearches[s] != inputElement.value.substring(0, this.lastSearches[s].length))) ) {
        //
        // Call the live search function, with a callback
        //
        this.liveSearchCb(s, i, inputElement.value);    // This then should call liveSearchShow 
    } 
    //
    // Decide if the search box should be displayed when an empty search string
    //
    if( inputElement.value == '' 
        && ((i != null && (sc.fields[i] != null && (sc.fields[i].livesearchempty == null || sc.fields[i].livesearchempty != 'yes')))
            || (i == null && (sc.livesearchempty == null || sc.livesearchempty != 'yes'))) ) {
        // Hidden search box
        t.style.display = 'none';
        this.liveSearchTables[t.id] = 'off';
    }
};

M.panel.prototype.liveSearchResultClass = function(s, f, i, j, d) {
    if( this.sections[s].cellClasses != null && this.sections[s].cellClasses[j] != null ) {
        return this.sections[s].cellClasses[j];
    }
    return '';
};

//
// Show the results of the live search
//
M.panel.prototype.liveSearchShow = function(s, f, inputElement, searchData) {
    var sc = null;
    var tb = null;
    if( f != null ) {
        tb = M.gE(this.panelUID + '_' + s + '_' + f + '_livesearchresultsgrid');
        sc = this.sections[s];
    } else {
        tb = M.gE(this.panelUID + '_' + s + '_livesearchresultsgrid');
        sc = this.sections[s];
    }
    var ct = 0;
    M.clr(tb);
    for(i in searchData) {
        var tr = M.aE('tr');
        if( this.liveSearchResultRowStyle != null ) { 
            tr.setAttribute('style', this.liveSearchResultRowStyle(s, f, i, searchData[i]));
        }
        var rcl = '';
        if( this.liveSearchResultRowClass != null ) { 
            rcl = ' ' + this.liveSearchResultRowClass(s, f, i, searchData[i]);
            tr.className = rcl;
        }
        var nc = sc.livesearchcols;
        if( f != null && sc.fields[f] != null && sc.fields[f].livesearchcols != null ) {
            nc = sc.fields[f].livesearchcols;
        }
        // Reset null to 1 column
        nc=(nc==null)?1:nc;
        for(var j=0;j<nc;j++) {
            var cl = '';
            if( this.liveSearchResultClass != null ) {
                cl = this.liveSearchResultClass(s, f, i, j, searchData[i]);
            }
            var c = M.aE('td', null, cl, this.liveSearchResultValue(s, f, i, j, searchData[i]));
            if( this.liveSearchResultCellColour != null ) {
                c.bgColor = this.liveSearchResultCellColour(s, f, i, j, searchData[i]);
            }
            if( this.liveSearchResultCellFn != null ) {
                var fn = this.liveSearchResultCellFn(s, f, i, j, searchData[i]);
                if( fn != null && fn != '' ) {
                    c.className += ' clickable';
                    c.setAttribute('onclick', 'event.stopPropagation();' + fn);
                }
            }
            tr.appendChild(c);
        }
        
        if( this.liveSearchResultRowFn != null ) {
            var fn = this.liveSearchResultRowFn(s, f, i, j, searchData[i]);
            if( fn != null ) {
                c = M.aE('td', null, 'buttons noprint');
                if( fn != '' ) {
                    tr.setAttribute('onclick', fn);
                    c.innerHTML = '<span class="icon">r</span>';
                    // c.innerHTML = '<img src=\'' + M.themes_root_url + '/default/img/arrow.png\'>';
                    tr.className = 'clickable' + rcl;
                }
                tr.appendChild(c);
            }
        }
        tb.appendChild(tr);    
        ct++;
    }
    if( ct == 0 && sc.noData != null ) {
        tb.innerHTML = '<tr><td colspan="' + sc.num_cols + '">' + sc.noData + '</td></tr>';
        this.lastSearches[s] = inputElement.value;
    }
};


//
// Create a datepicker to allow user to navigate panel information by date
//
M.panel.prototype.createDatePicker = function(s, sd) {
    // Get the current date to display
    if( this.datePickerValue != null ) {
        var v = this.datePickerValue(s, sd);
        if( v != null && v != '' && v != 'today' ) {
            if( v instanceof Date ) {
                var dt = new Date(v.getTime());
            } else {
                var dtpieces = v.split('-');
                var dt = new Date(dtpieces[0], Number(dtpieces[1])-1, dtpieces[2]);
            }
        } else {
            var dt = new Date();
        }
    } else {
        var dt = new Date();
    }
    var dtm = (dt.getTime())/1000;

    // format display date
//    var dts = dt.getFullYear() + '-' + (dt.getMonth()+1) + '-' + dt.getDate();
    var dts = dt.toISOString().substring(0,10);
    // var dtfs = this.dateFormat(dts).date;
    var dtfs = M.dateFormatWD(dt);
    
    if( sd.livesearch != null && sd.livesearch == 'yes' ) {
        var t = M.addTable(this.panelUID + '_datepicker', 'list datepicker datepickersearch noheader border');
    } else {
        var t = M.addTable(this.panelUID + '_datepicker', 'list datepicker noheader border');
    }
    var th = M.aE('tbody');
    var tr = M.aE('tr');

    // Previous
    //var c1 = M.aE('td',null,'prev', '<img src=\'' + M.themes_root_url + '/default/img/arrowleft.png\'>');
    var c1 = M.aE('td',null,'prev', '<span class="icon">l</span>');
    c1.className = 'prev clickable';
    if( sd.type == 'weekpicker' ) {
        dt.setTime((dtm-604800)*1000);
    } else {
        dt.setTime((dtm-86400)*1000);
    }
//    var dtprevs = dt.getFullYear() + '-' + (dt.getMonth()+1) + '-' + dt.getDate();
    var dtprevs = dt.toISOString().substring(0,10);
    c1.setAttribute('onclick', sd.fn + '(null, \'' + dtprevs + '\');');
    tr.appendChild(c1);

    // Date
    var c2 = M.aE('td',this.panelUID + '_datepicker_field','date', dtfs);
    c2.setAttribute('onclick', 'M.' + this.appID + '.' + this.name + '.toggleDatePickerCalendar(\'' + dts + '\',\'' + sd.fn + '\');');
    c2.className = 'date clickable';
    tr.appendChild(c2);

    // Search field
    if( sd.livesearch != null && sd.livesearch == 'yes' ) {
        var c4 = M.aE('td',this.panelUID + '_datepicker_search','search');
        c4.className = 'search clickable';
        tr.appendChild(c4);

        var f = M.aE('input', this.panelUID + '_' + s, 'search');
        f.setAttribute('name', s);
        f.setAttribute('type', 'search');
        if( sd.hint != null && sd.hint != '' ) {
            f.setAttribute('placeholder', sd.hint);
        }
//        if( sd.livesearchempty == 'yes' ) {
            f.setAttribute('onfocus', this.panelRef + '.liveSearchSection(\'' + s + '\',null,this,event);');
//        } else {
//            f.setAttribute('onfocus', this.panelRef + '.clearLiveSearches(\''+s+'\',null);');
//        }
        f.setAttribute('onkeyup', this.panelRef + '.liveSearchSection(\'' + s + '\',null,this,event);');
        // f.setAttribute('onblur', this.panelRef + '.removeLiveSearch(\'' + s + '\',null);');
        f.setAttribute('autocomplete', 'off');
        this.lastSearches[s] = '';
        c4.appendChild(f);
    }
    
    // Next
    // c3 = M.aE('td',null,'next', '<img src=\'' + M.themes_root_url + '/default/img/arrow.png\'>');
    c3 = M.aE('td',null,'next', '<span class="icon">r</span>');
    c3.className = 'next clickable';
    if( sd.type == 'weekpicker' ) {
        dt.setTime((dtm+604800)*1000);
    } else {
        dt.setTime((dtm+86400)*1000);
    }
//    var dtnexts = dt.getFullYear() + '-' + (dt.getMonth()+1) + '-' + dt.getDate();
    var dtnexts = dt.toISOString().substring(0,10);
    c3.setAttribute('onclick', sd.fn + '(null, \'' + dtnexts + '\');');
    tr.appendChild(c3);
    
    th.appendChild(tr);
    t.appendChild(th);

    if( sd.livesearch != null && sd.livesearch == 'yes' ) {
        var frag = document.createDocumentFragment();
        frag.appendChild(t);
        //
        // Create the second table which will display the results, display hidden at first
        //
        frag.appendChild(this.liveSearchResultsTable(s, null, sd));
        // frag.appendChild(t);

        return frag;
    }

    return t;
};

//
// This will display a calendar for the user to pick from for a date
//
M.panel.prototype.toggleDatePickerCalendar = function(curdate, fn) {
    var h = M.gE(this.panelUID + '_' + 'datepicker' + '_calendar');
    if( h != null ) {
        this.removeFormFieldCalendar('datepicker');
    } else {
        //
        // Setup the div containers
        //
        var fD = M.gE(this.panelUID + '_' + 'datepicker_field').parentNode;
        var hD = M.aE('tr', this.panelUID + '_' + 'datepicker' + '_calendar', 'fieldcalendar');
        var hC = M.aE('td', null, 'calendar');
        
        hC.colSpan = fD.children.length;

        hD.appendChild(hC);
        fD.parentNode.insertBefore(hD, fD.nextSibling);
        
        var dtpieces = curdate.split('-');
        this.showFieldCalendars('datepicker', Number(dtpieces[0]), Number(dtpieces[1])-1, {'year':Number(dtpieces[0]), 'month':Number(dtpieces[1]), 'day':Number(dtpieces[2]), 'hour':'', 'minute':''}, 'calendar', fn, null);
    }
};

//
// Field history should be removed and not hidden, so next update will
// fetch  new values from database
//
M.panel.prototype.removeDatePickerCalendar = function() {
    var h = M.gE(this.panelUID + '_' + 'datepicker' + '_calendar');
    //
    // Remove element, and delete the history object to save memory
    //
    h.parentNode.removeChild(h);
};

//
// This function assumes the top elements are the data of the list
// 
// Arguments:
// si - the section id if any
// l - The list data
// ef - autosplit flag
//
M.panel.prototype.createSimpleMedia = function(si, l, ef) {
    var f = M.aE('div', null, 'media');
    var u = '';
    for(i in this.data) {
        u = this.thumbURL(i, this.data[i]);
        var e = this.createMediaThumb(this.mediaIDFn(i, this.data[i]), this.typeFn(i, this.data[i]), u, this.titleFn(i, this.data[i]));
        f.appendChild(e);    
    }

    //
    // Setup move up album drop area
    //
    if( this.parentIDFn != null && this.parentIDFn() >= 0 ) {
        var e = this.createMediaThumb(this.parentIDFn(), 'parent', '' + M.themes_root_url + '/default/img/parent.png', 'Back');
        f.appendChild(e);
    }


    //
    // Setup the trash bin
    //
//        if( this.delMediaFn != null || this.delMediaCl != null ) {
        // Add trash element
        var e = this.createMediaThumb('trash', 'trash', '' + M.themes_root_url + '/default/img/trash.png', 'Trash');
        f.appendChild(e);
//        }

    return f;
};

//
// Arguments:
// id - the id of the element
// type - the type of element this should be:
//            - image (clickable, dragable, dropable)
//             - album (clickable, dragable, dropable)
//            - trash (clickable, dropable)
////             - addimage (clickable, dropable, iframe)
////            - addalbum (clickable, dropable, iframe)
// i - thumbnail
// t - title
// d - ondrop fn
// c - onclick fn
//
M.panel.prototype.createMediaThumb = function(id, type, i, t) {
    //
    //
    var d1 = M.aE('div', 'media_' + id, 'media_thumb media_' + type + ' clickable', '');
    var d2 = M.aE('div', null, null, '');
    var d3 = M.aE('div', null, 'imgwrap');
    var im = new Image();
    im.setAttribute('draggable', 'false');
    im.src = i;
    d3.appendChild(im);

    var d4 = M.aE('div', null, 'titlewrap', t);
    d4.setAttribute('draggable', 'false');
    d3.setAttribute('draggable', 'false');
    d2.setAttribute('draggable', 'false');

    d2.appendChild(d3);
    d2.appendChild(d4);
    d1.appendChild(d2);

    if( type == 'image' || type == 'album' && this.dropFn != null ) {
        d1.setAttribute('draggable', 'true');
        d1.media_id = id;
        d1.addEventListener('dragstart', function(e) { 
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('src_media_id', id);
            e.dataTransfer.setData('src_media_type', type);
            this.style.opacity = '0.5'; 
            return false;
            }, false);
        d1.addEventListener('dragend', function(e) { this.style.opacity = '1.0'; return false;}, false);
    }

    d1.media_type = type;
    d1.media_id = id;
    if( this.clickFn != null && this.clickFn(type, id) != null ) {
        d1.clickHandler = this.clickFn(type, id);
        if( d1.clickHandler != null ) {
            d1.onclick = function() {
                return this.clickHandler(this.media_type, this.media_id);
            }
        }
    }

    if( this.dropFn != null && this.dropFn(type, id) != null ) {
        d1.addEventListener('dragover', function(e) { 
            if( e.preventDefault ) { 
                e.preventDefault(); // required for Chrome bug
            }
            }, false);    
        d1.dropHandler = this.dropFn(type, id);
        if( d1.dropHandler != null ) {
            d1.addEventListener('drop', function(e) { 
                if( e != null && e.dataTransfer != null && e.dataTransfer.getData != null && e.dataTransfer.getData('src_media_id') > 0 ) {
                    e.stopPropagation();
                    e.preventDefault();
                    return this.dropHandler(e.dataTransfer.getData('src_media_type'), e.dataTransfer.getData('src_media_id'), 
                        this.media_type, this.media_id);
                }
                }, false);
        }
    }

    return d1;
};

//
// Args:
// s - section id
//
M.panel.prototype.createSimpleThread = function(s) {
    if( this.createThreadFollowup == null ) {
        this.createThreadFollowup = M.panel.createThreadFollowup;
    }

    //
    // Setup the header for the list
    //
    var t = null;
    if( this.threadSubject != null && this.threadSubject(s) != null ) {
        t = M.addTable(this.panelUID + '_thread', 'list header border thread');
        var th = M.aE('thead');
        var tr = M.aE('tr');
        var c = M.aE('th',null,null,this.threadSubject(s));
        c.colSpan = 2;
        tr.appendChild(c);
        th.appendChild(tr);
        t.appendChild(th);
    } else {
        t = M.addTable(null, 'list noheader border');
    }
    var tb = M.aE('tbody');
    if( this.sectionData != null ) {
        data = this.sectionData(s);
    } else {
        data = this.data;
    }

    for(i in data) {
        tb.appendChild(this.createThreadFollowup(s, i, data[i]));
    }
    if( tb.children.length > 0 ) {
        t.appendChild(tb);
    }

    return t;
};

//
// This function will display an xml/json tree in a grid format
//
M.panel.prototype.createSectionTreeGrid = function(s, depth, indexes, data) {

    var sc = this.sections[s];
    //
    // The tree describes the different levels or break points
    //
    var tree = sc.tree;
    if( depth == 0 && data == null ) {
        data = this.sectionData(s);
    }
    var f = document.createDocumentFragment();

    //
    // FIXME: Add outer layer to allow for section splitting within the tree
    //

    //
    // FIXME: Build tree walk to grid recursive function
    //

    if( depth == 0 ) {
        //
        // Table header
        //
        var num_cols = sc.num_cols;
        var t = this.createSectionGridHeaders(s, sc);

        //
        // Table body
        //
        var tb = M.aE('tbody');
        var ct = 0;
        this.previous_row = [];
        if( indexes == null ) { indexes = []; }
        t.appendChild(tb);
        f.appendChild(t);
    }

    if( depth == 0 && tree.length > 1 ) {
        for(i in data) {
            tb.appendChild(this.createSectionTreeGrid(s, depth+1, [i],
                data[i][tree[depth]['element']][tree[(depth+1)]['container']]));
        }

        if( tb.childNodes.length == 0 && this.noData != null ) {
            // var t = M.addTable(null, 'list noheader border');
            // var tb = M.aE('tbody');
            var tr = M.aE('tr');
            var td = M.aE('td', null, null, this.noData(s));
            td.colSpan = num_cols + 1;
            tr.appendChild(td);
            tb.appendChild(tr);
        }
        return f;
    }
    else if( depth > 0 && depth < (tree.length-1) ) {
        for(i in data) {
            indexes[depth] = i;
            f.appendChild(this.createSectionTreeGrid(s, depth+1, indexes, 
                data[i][tree[depth]['element']][tree[(depth+1)]['container']]));
        }
        return f;
    }

    for(i in data) {
        indexes[depth] = i;

        var tr = M.aE('tr');
    //    if( this.rowStyle != null ) { 
    //        tr.setAttribute('style', this.rowStyle(s, i, data[i]));
    //    }
        num_cols = sc.num_cols;

        var hide_value = 'no';    
        if( sc.duplicates != null && sc.duplicates == 'hide' ) {
            hide_value = 'yes';
        }
        
        for(var j=0;j<num_cols;j++) {
            var cl = '';
            if( this.cellTreeClass != null ) { cl = this.cellTreeClass(s, depth, indexes, j, data[i]); }
            var v = this.cellTreeValue(s, depth, indexes, j, data[i]);
            
            //
            // Check if previous row/cell contained the same information, and output blank cell for easier reading
            //
            if( hide_value == 'yes' && this.previous_row[j] == v ) {
                var c = M.aE('td',null,cl,'');
                c.sort_value = v;
            } else {
                var c = M.aE('td',null,cl,v);
                // Stop hiding cells after the first non dup.
                hide_value = 'no';
            }
            this.previous_row[j] = v;
            if( sc.sortTypes != null 
                && ( sc.sortTypes[j] == 'size' || sc.sortTypes[j] == 'percent' )
                && this.sortTreeValue != null ) {
                c.sort_value = this.sortTreeValue(s, depth, indexes, j, data[i]);
            }

            //
            // Check if the cell is clickable
            //
            if( this.cellTreeFn != null ) {
                var fn = this.cellTreeFn(s, depth, indexes, j, data[i]);
                if( fn != null && fn != '' ) {
                    c.setAttribute('onclick', 'event.stopPropagation(); ' + fn);
                    c.className = cl + ' clickable';
                }
            }

            tr.appendChild(c);
        }    

        // Add the arrow to click on
        if( this.rowTreeFn != null && this.rowTreeFn(s, depth, indexes, data[i]) != null ) {
            c = M.aE('td', null, 'buttons noprint');
            tr.setAttribute('onclick', this.rowTreeFn(s, depth, indexes, data[i]));
            c.innerHTML = '<span class="icon">r</span>';
            // c.innerHTML = '<img src=\'' + M.themes_root_url + '/default/img/arrow.png\'>';
            tr.className = 'clickable';
            tr.appendChild(c);
        }

        if( depth == 0 ) {
            tb.appendChild(tr);
        } else {
            f.appendChild(tr);
        }
    }

    return f;
};

//
// Provide a default headerValue which can be overwritted in panel
//
M.panel.prototype.headerValue = function(s, c, sc) {
    if( sc != null && sc.headerValues != null ) { return sc.headerValues[c]; }
    return null;
};

M.panel.prototype.createSectionGridHeaders = function(s, sc) {
    var cl = 'simplegrid';
    if( sc.class != null ) {
        cl = sc.class;
    } else {
        cl = 'simplegrid border';
    }
    if( this.headerValue(s, 0, sc) == null ) {
        if( sc.fields != null ) {
            return M.addTable(null, 'form list ' + cl + ' noheader');
        }
        return M.addTable(null, 'list ' + cl + ' noheader');
    }
    if( sc.fields != null ) {
        var t = M.addTable(this.panelUID + '_' + s + '_grid', 'form list ' + cl + ' header');
    } else {
        var t = M.addTable(this.panelUID + '_' + s + '_grid', 'list ' + cl + ' header');
    }
    var th = M.aE('thead');
    var tr = M.aE('tr');
    for(var i=0;i<sc.num_cols;i++) {
        // Check for a split column in compact view
        if( M.size == 'compact' && sc.compact_split_at != null && sc.compact_split_at == i ) {
            break;
        }
        tr.appendChild(this.createSectionGridHeader(s, i, sc));
    }
    // If there's the possiblity of row being clickable, then add extra column to header for > (arrow).
    if( this.rowFn != null || this.rowTreeFn != null ) {
        tr.appendChild(M.aE('th', null, 'noprint'));
    }
    th.appendChild(tr);
    t.appendChild(th);

    return t;
};

M.panel.prototype.createSectionGridHeader = function(s, i, sc) {
    var v = this.headerValue(s, i, sc);
    var c = M.aE('th',null,null, v);
    var cl = '';
    if( this.headerClass != null && this.headerClass(s, i) != null ) {
        cl = this.headerClass(s, i);
    }
    if( sc.headerClasses != null && sc.headerClasses[i] != null ) {
        cl += ((cl!='')?' ':'')+sc.headerClasses[i];
    }
    // Add sortable if turned on for section, and header has a value.
    if( sc.sortable != null && sc.sortable == 'yes' && v != '' && sc.sortTypes != null && sc.sortTypes[i] != 'none' ) {
        cl += ' sortable';
        if( sc.type == 'treegrid' ) {
            c.setAttribute('onclick', 'M.sortTreeGrid(\'' + this.panelUID + '_' + s + '_grid' + '\',\'' + i +'\',\'' + sc.sortTypes[i] + '\',\'asc\',' + sc.savesort + ', null);');
        } else {
            c.setAttribute('onclick', 'M.sortGrid(\'' + this.panelUID + '_' + s + '_grid' + '\',\'' + i +'\',\'' + sc.sortTypes[i] + '\',\'asc\',' + sc.savesort + ', null);');
        }
    }
    // Check if this is non-sortable, but has a Fn provided
    else if( this.headerFn != null && this.headerFn(s, i) != null ) {
        cl += ' sortable';
        c.setAttribute('onclick', this.headerFn(s, i));
    }
    c.className = cl;

    return c;
};

//
// Provide a default footerValue which can be overwritted in panel
//
M.panel.prototype.footerValue = function(s, c, sc) {
    if( sc != null && sc.footerValues != null ) { return sc.footerValues[c]; }
    return null;
};

M.panel.prototype.createSectionGridFooters = function(s, sc) {
    var tr = M.aE('tr');
    for(var i=0;i<sc.num_cols;i++) {
        // Check for a split column in compact view
        if( M.size == 'compact' && sc.compact_split_at != null && sc.compact_split_at == i ) {
            break;
        }
        tr.appendChild(this.createSectionGridFooter(s, i, sc));
    }
    // If there's the possiblity of row being clickable, then add extra column to header for > (arrow).
    if( this.rowFn != null || this.rowTreeFn != null ) {
        tr.appendChild(M.aE('th', null, 'noprint'));
    }

    return tr;
};

M.panel.prototype.createSectionGridFooter = function(s, i, sc) {
    var v = this.footerValue(s, i, sc);
    var c = M.aE('th',null,null, v);
    var cl = '';
    if( this.footerClass != null && this.footerClass(s, i) != null ) {
        cl = this.footerClass(s, i);
    }
    if( sc.footerClasses != null && sc.footerClasses[i] != null ) {
        cl += ((cl!='')?' ':'')+sc.footerClasses[i];
    }
    c.className = cl;

    return c;
};

M.panel.prototype.cellClass = function(s, i, j, d) {
    if( this.sections[s].cellClasses != null && this.sections[s].cellClasses[j] != null ) {
        return this.sections[s].cellClasses[j];
    }
    return '';
};

M.panel.prototype.createSectionGrid = function(s) {
    //
    // FIXME: Check the size of the data, and decide if there should be a search box
    //           added to the top of the panel.  If there's more than 50 items in the
    //        data, then we should add the search box.
    //          If the data is over 1000, then it should be a live search, and the complete data set
    //        should not be loaded into the client, just the live search results.
    //
    var sc = this.sections[s];
    var data = null;
    if( this.sectionData != null ) {
        data = this.sectionData(s);
    } else if( sc.data != null ) {
        data = sc.data;
    }
    var f = document.createDocumentFragment();
//        if( this.sections[s].searchable == 'yes' && data.length > 50 ) {
        // FIXME: Add code to allow searching of table content
//        }

    //
    // Table header
    //
    var num_cols = sc.num_cols;
    var t = this.createSectionGridHeaders(s, sc);

    //
    // Table body
    //
    var tb = M.aE('tbody');
    var ct = 0;
    for(i in data) {
        if( sc.limit != null && sc.limit != '' ) {
            if( ct >= sc.limit ) { break; }
        }
        var tr = M.aE('tr');
        var ptr = tr;
        if( this.rowStyle != null ) { 
            tr.setAttribute('style', this.rowStyle(s, i, data[i]));
        }
        var rcl = '';
        if( this.rowClass != null ) {
            rcl = ' ' + this.rowClass(s, i, data[i]);
            tr.className = rcl;
        }

        for(var j=0;j<num_cols;j++) {
            var cl = null;
            if( this.cellClass != null ) { cl = this.cellClass(s, i, j, data[i]); }
            //
            // Check if the table is split
            //
            if( M.size == 'compact' && sc.compact_split_at != null && j >= sc.compact_split_at  ) {
                if( sc.compact_split_at == j ) {
                    rcl += ' split';
                    tr.className = rcl;
                    tb.appendChild(tr);
                    ptr = tr;
                } 

                var tr = M.aE('tr', null, 'split_element');
                if( sc.headerValues != null ) {
                    tr.appendChild(this.createSectionGridHeader(s, j, sc));
                }
            }

            //
            // Check if this should be a form field
            //
            var c = null;
            if( sc.fields != null && sc.fields[i] != null && sc.fields[i][j] != null ) {
                var c = this.createFormField(i, i, sc.fields[i][j], sc.fields[i][j]['id']);
                tr.appendChild(c);
            } else {
                var v = this.cellValue(s, i, j, data[i]);
                if( this.cellId != null ) {
                    c = M.aE('td',this.cellId(s, i, j, data[i]),cl,v);
                } else {
                    c = M.aE('td',null,cl,v);
                }
                tr.appendChild(c);

                if( this.cellStyle != null ) {
                    var st = this.cellStyle(s, i, j, data[i]);
                    if( st != '' ) {
                        c.setAttribute('style', st);
                    }
                }

                //
                // Check if sortable, and a date field
                //
                if( sc.sortable != null && sc.sortable == 'yes' && sc.sortTypes != null && sc.sortTypes[j] == 'date' ) {
                    // Parse date
                    var monthMaps = {'jan':'01','feb':'02','mar':'03','apr':'04','may':'05','jun':'06','jul':'07','aug':'08','sep':'09','oct':'10','nov':'11','dec':'12'};
                    if( v == null ) {
                        c.sort_value = '';
                    } else if( (dfields = v.match(/([A-Za-z]+) ([0-9]+),? ([0-9][0-9][0-9][0-9])/)) != null ) {
                        c.sort_value = dfields[3] + monthMaps[dfields[1].toLowerCase()] + (dfields[2]<10?'0'+dfields[2]:dfields[2]);
                    } else if( (dfields = v.match(/([A-Za-z]+) ([0-9][0-9][0-9][0-9])/)) != null ) {
                        c.sort_value = dfields[2] + monthMaps[dfields[1].toLowerCase()];
                    } else {
                        c.sort_value = v;
                    }
                }
                //
                // Sort type to be used when complex number found within
                //
                if( sc.sortable != null && sc.sortable == 'yes' && sc.sortTypes != null 
                    && (sc.sortTypes[j] == 'altnumber' || sc.sortTypes[j] == 'alttext') ) {
                    c.sort_value = this.cellSortValue(s, i, j, data[i]);
                }
                // Check if a sortable size field, where we need to store the real size
//                if( sc.sortable != null && sc.sortable == 'yes' && sc.sortTypes != null && sc.sortTypes[j] == 'date' ) {
//                    c.sort_value = v;
//                }
                
                if( this.cellFn != null ) {
                    var fn = this.cellFn(s, i, j, data[i]);
                    if( fn != null && fn != '' ) {
                        c.setAttribute('onclick', fn);
                        c.className = cl + ' clickable';
                    }
                }

                if( this.cellUpdateFn != null ) {
                    var fn = this.cellUpdateFn(s, i, j, data[i]);
                    if( fn != null ) {
                        c.cell_section = s;
                        c.cell_row = i;
                        c.cell_col = j;
                        c.updateFn = fn;    
                        // Put cell content in DIV so it can be draggable
                        var d = M.aE('div', null, 'dragdrop_cell', this.cellValue(s, i, j, data[i]));
                        d.id = this.panelUID + '_' + s + '_' + i + '_' + j + '_draggable';
                        c.innerHTML = '';
                        c.appendChild(d);
                        if( M.device == 'ipad' ) {
                            var drag = new webkit_draggable(d, {revert:'always'});
                            webkit_drop.add(c, {revert:'always', onDrop:function(s, r, e, t) { 
                                // The droppable is attached to the cell.
                                // Copy the dragged content to the dropped cell's div.  
                                t.updateFn(t.cell_section, t.cell_row, t.cell_col, r.innerHTML);
                                // Don't need to copy info, it is refreshed with the call to updateFn
                                // t.children[0].innerHTML = r.innerHTML;
                                }
                            });
                            d.setAttribute('onclick', 'event.stopPropagation(); ' + this.panelRef + '.editSectionGridCell(\'' + s + '\',' + i + ',' + j + ',this.innerHTML);');
                        } else {
                            d.setAttribute('onclick', 'event.stopPropagation(); ' + this.panelRef + '.editSectionGridCell(\'' + s + '\',' + i + ',' + j + ',this.innerHTML);');
                            d.setAttribute('draggable', 'true');
                            d.addEventListener('dragstart', function(e) {
                                e.dataTransfer.effectAllowed = 'move';
                                e.dataTransfer.setData('src_grid_content', this.innerHTML);
                                this.style.opacity = '0.5';
                                return false;
                                }, false);
                            d.addEventListener('dragend', function(e) { this.style.opacity = '1.0'; return false;}, false);
                            c.addEventListener('dragover', function(e) {
                                if( e.preventDefault ) {
                                    e.preventDefault(); // required for Chrome bug
                                }
                                return false;
                                }, false);
                            c.addEventListener('drop', function(e) {
                                if( e != null && e.dataTransfer != null && e.dataTransfer.getData != null && e.dataTransfer.getData('src_grid_content') != null ) {
                                    e.stopPropagation();
                                    e.preventDefault();
                                    this.updateFn(this.cell_section, this.cell_row, this.cell_col, e.dataTransfer.getData('src_grid_content'));
                                    return false;
                                }
                                }, false);
                        }
                    }
                }
            }

            if( this.cellColour != null ) {
                c.bgColor = this.cellColour(s, i, j, data[i]);
            }

            //
            // Check if we need to split at this row
            //
            if( M.size == 'compact' && sc.compact_split_at != null && j >= sc.compact_split_at ) {
                if( sc.headerValues == null ) {
                    c.colSpan = sc.compact_split_at+1;
                } else {
                    c.colSpan = sc.compact_split_at;
                }
                if( j == (num_cols-1) ) {
                    tr.className += ' split_last';
                }
                tb.appendChild(tr);
            }
        }

        // Add the edit button to click on
        if( sc.editFn != null && sc.editFn(s, i, data[i]) != null ) {
            c = M.aE('td', null, 'buttons noprint');
            var fn = sc.editFn(s, i, data[i]);
            if( fn != '' ) {
                c.setAttribute('onclick', 'event.stopPropagation();' + sc.editFn(s, i, data[i]));
                c.innerHTML = '<span class="faicon">&#xf040;</span>';
                ptr.className = 'clickable' + rcl;
            }
            ptr.appendChild(c);
        }
        // Add the arrow to click on
        if( this.rowFn != null && this.rowFn(s, i, data[i]) != null ) {
            c = M.aE('td', null, 'buttons noprint');
            var fn = this.rowFn(s, i, data[i]);
            if( fn != '' ) {
                ptr.setAttribute('onclick', this.rowFn(s, i, data[i]));
                c.innerHTML = '<span class="icon">r</span>';
                ptr.className = 'clickable' + rcl;
            }
            ptr.appendChild(c);
        } else if( sc.addFn != null && sc.addFn != '' && sc.addTxt != null && sc.addTxt != '' ) {
            ptr.appendChild(M.aE('td', null, 'noprint'));
        }

        tb.appendChild(tr);
        ct++;
    }
    if( ct == 0 && (this.noData != null || sc.noData != null) ) {
        // var t = M.addTable(null, 'list noheader border');
        // var tb = M.aE('tbody');
        if( this.noData != null ) {
            var nd = this.noData(s);
        } else {
            var nd = sc.noData;
        }
        if( nd != null && nd != '' ) {
            var tr = M.aE('tr');
            var td = M.aE('td', null, null, nd);
            if( M.size == 'compact' && sc.compact_split_at != null ) {
                td.colSpan = sc.compact_split_at + 1;
            } else {
                if( sc.editFn != null ) {
                    td.colSpan = num_cols + 2;
                } else {
                    td.colSpan = num_cols + 1;
                }
            }
            tr.appendChild(td);
            tb.appendChild(tr);
            t.appendChild(tb);
        }
    }
    else if( ct > 0 ) {
        t.appendChild(tb);
    }

    //
    // Add a row for the add button
    //
    var tf = M.aE('tfoot');
    if( this.footerValue(s, 0, sc) != null ) {
        var tr = this.createSectionGridFooters(s, sc);
        tf.appendChild(tr);
    }

    if( data != null && data.length > sc.limit && sc.moreTxt != null && sc.moreTxt != '' && sc.moreFn != null && sc.moreFn != '' ) {
        var tr = M.aE('tr');
        var td = M.aE('td', null, 'addlink aligncenter', sc.moreTxt);
        if( M.size == 'compact' && sc.compact_split_at != null ) {
            td.colSpan = sc.compact_split_at;
        } else {
            td.colSpan = num_cols;
        }
        tr.appendChild(td);
        // Add arrow
        c = M.aE('td', null, 'buttons noprint');
        tr.setAttribute('onclick', sc.moreFn);
        c.innerHTML = '<span class="icon">r</span>';
        tr.className = 'clickable';
        tr.appendChild(c);
        tf.appendChild(tr);
    }
    if( sc.addFn != null && sc.addFn != '' && sc.addTxt != null && sc.addTxt != '' ) {
        var tr = M.aE('tr');
        var td = M.aE('td', null, 'addlink aligncenter', sc.addTxt);
        if( M.size == 'compact' && sc.compact_split_at != null ) {
            td.colSpan = sc.compact_split_at;
        } else {
            td.colSpan = num_cols;
        }
        tr.appendChild(td);
        // Add blank for edit
        if( sc.editFn != null ) {
            c = M.aE('td', null, 'buttons noprint');
            tr.appendChild(c);
        }
        // Add arrow
        c = M.aE('td', null, 'buttons noprint');
        tr.setAttribute('onclick', sc.addFn);
        c.innerHTML = '<span class="icon">r</span>';
        tr.className = 'clickable';
        tr.appendChild(c);
        tf.appendChild(tr);
    }

    //
    // Add a row for the add button
    //
    if( sc.changeFn != null && sc.changeFn != '' && sc.changeTxt != '' ) {
        var tr = M.aE('tr');
        var td = M.aE('td', null, 'addlink aligncenter', sc.changeTxt);
        if( M.size == 'compact' && sc.compact_split_at != null ) {
            td.colSpan = sc.compact_split_at;
        } else {
            td.colSpan = num_cols;
        }
        tr.appendChild(td);
        // Add blank for edit
        if( sc.editFn != null ) {
            c = M.aE('td', null, 'buttons noprint');
            tr.appendChild(c);
        }
        // Add arrow
        c = M.aE('td', null, 'buttons noprint');
        tr.setAttribute('onclick', sc.changeFn);
        c.innerHTML = '<span class="icon">r</span>';
        tr.className = 'clickable';
        tr.appendChild(c);
        tf.appendChild(tr);
    }
    if( tf.childNodes.length > 0 ) {
        t.appendChild(tf);
    }
    

    //
    // Check if the table should be sorted first
    //
    if( sc.sortable != null && sc.sortable == 'yes' && sc.sortTypes != null ) {
        var sorting = null;
        if( this.sortOrder != null ) {
            sorting = this.sortOrder(this.panelUID + '_' + s + '_grid');
        }
        if( sorting != null && sorting != undefined ) {
            M.sortGrid(this.panelUID + '_' + s + '_grid', sorting.col, sorting.type, sorting.order, null, t);
        } else {
            M.sortGrid(this.panelUID + '_' + s + '_grid', null, null, null, null, t);
        }
    }

    f.appendChild(t);

    return f;
};

M.panel.prototype.editSectionGridCell = function(s, i, j, data) {
    var new_data = prompt("Enter new information for cell", data);
    if( new_data != null && new_data != data ) {
        var fn = this.cellUpdateFn(s, i, j, data);
        if( fn != null ) {
            fn(s, i, j, new_data);
        }
    }
};

//
// This function assumes the top elements are the data of the list
// 
// Arguments:
// si - the section id if any
// l - The list data
// as - autosplit flag
//
M.panel.prototype.createSimpleList = function(si, l, as) {
    
    //
    // FIXME: Add feature to check length of data first, and then decide
    //        how to add breaks into the list.
    //        < 5 each item is it's own button
    //        < 20 split in half
    //        < 50 split in quarters
    //          < 100 split in eights
    //        < 500 split in alphabetical
    //
    var f = document.createDocumentFragment();
    var ss = 0;

    if( as != null ) {
        if( as == 'yes' ) {    
            var len = M.length(l);
            if( l != null && len < 6 ) { 
                ss = 1;
            } else if( l != null && len < 20 ) {
                ss = parseInt((len/2)+0.5);
            } else if( l != null && len < 50 ) {
                ss = parseInt((len/4)+0.5);
            } else if( l != null && len < 100 ) {
                ss = parseInt((len/8)+0.5);
            }
        } else if( as == 'always' ) {
            ss = 1;
        }
    }
    var ct = 0;
    var t = null;
    for(i in l) {
        if( typeof l[i].visible == 'function' ) {
            if( l[i].visible() != 'yes' ) { continue; }
        } else if( l[i].visible != null && l[i].visible != 'yes' ) {
            continue;
        }
        if( ct == 0 || (ss > 0 && (ct % ss) == 0) ) {
            if( ct > 0 && t != null ) { 
                t.appendChild(tb);
                f.appendChild(t);
            }
            var t = M.addTable(null, 'list simplelist noheader border');
            var tb = M.aE('tbody');
        }
        var cl = '';
        if( this.listClass != null ) { cl = this.listClass(si, i, l[i]); }
        var tr = M.aE('tr', null, cl);

        if( this.rowStyle != null ) { 
            tr.setAttribute('style', this.rowStyle(si, i, l[i]));
        }

        var cltrl = '';
        if( this.listLabel != null ) {
            var label = this.listLabel(si, i, l[i]);
            if( label != '' ) {
                tr.appendChild(M.aE('td', null, 'label', label));
                cltrl += label + ': ';
            }
        }
        // Add the list item
        var v = '';
        if( this.listValue != null ) {
            v = this.listValue(si, i, l[i]);
        } else {
            v = l[i].label;
        }
        cltrl += v;
        // FIXME: Add flag to hide count if zero
        if( this.listCount != null && this.listCount(si, i, l[i]) != '' ) {
            v += ' <span class="count">' + this.listCount(si, i, l[i]) + '</span>';
        }
        if( l[i].count != null ) {
            v += ' <span class="count">' + l[i].count + '</span>';
        } 
        tr.appendChild(M.aE('td', null, 'truncate', v));

        // Add the arrow to click on
        var fn = null;
        if( this.listFn != null ) {
            fn = this.listFn(si, i, l[i]);
        } else {
            fn = l[i].fn;
        }
        // Check if click tracker is turned on
        var cltr = '';
        if( M.curTenant != null && M.curTenant.modules['ciniki.clicktracker'] != null ) {
            cltr = 'M.api.getBg(\'ciniki.clicktracker.add\', {\'tnid\':M.curTenantID, \'panel_id\':\'' + this.panelID + '\', \'item\':\'' + cltrl.replace(/ <span .*/, '') + '\'});';
        }
        if( fn != null ) {
            var c = M.aE('td', null, 'buttons');
            if( fn != '' ) {
                tr.setAttribute('onclick', cltr + fn);
                c.innerHTML = '<span class="icon">r</span>';
                // c.innerHTML = '<img src=\'' + M.themes_root_url + '/default/img/arrow.png\'>';
                tr.className = cl + ' clickable';
            }
            tr.appendChild(c);
        }
        tb.appendChild(tr);
        ct++;
    }
    if( ct == 0 && this.noData != null ) {
        var nd = this.noData(si);
        var t = M.addTable(null, 'list noheader border');
        var tb = M.aE('tbody');
        var tr = M.aE('tr');
        tr.appendChild(M.aE('td', null, null, nd));
        tb.appendChild(tr);
    }
    if( tb != null ) {
        t.appendChild(tb);
        f.appendChild(t);
    }

    return f;
};

//
// This function assumes the top elements are the data of the list
// 
// Arguments:
// si - the section id if any
// l - The list data
// as - autosplit flag
//
M.panel.prototype.createSimpleButtons = function(si, l, as) {
    var f = document.createDocumentFragment();
    for(i in l) {
        if( typeof l[i].visible == 'function' && l[i].visible() == 'no' ) {
            continue;
        } else if( l[i].visible != null && l[i].visible == 'no' ) {    
            continue;
        }
        var t = M.addTable(null, 'list simplebuttons noheader border');
        var tb = M.aE('tbody');
        var tr = M.aE('tr', l[i].id);

    
        // Add the list item
        var cl = 'button ' + i;
        if( l[i].class != null && l[i].class != '' ) {
            cl += ' ' + l[i].class;
        }
        tr.appendChild(M.aE('td', null, cl, l[i].label));

        // Add the onclick function
        if( l[i].fn != null ) {
            tr.setAttribute('onclick', l[i].fn);
        }
        tb.appendChild(tr);
        t.appendChild(tb);
        f.appendChild(t);
    }

    return f;
};

M.panel.prototype.dupFormSection = function(s) {
    var i = 1;
//    var ps = M.gE(this.panelUID + '_' + s);
    for(i=0;i<=21;i++) {
        var e = M.gE(this.panelUID + '_' + s + '_' + i) 
        if( e == null ) { break; }
        ps = e;
    }
    // Only allow 20 additions
    if(i == 21) { return false; }
//    var ns = s + '_' + i;
    if( this.sections[s].fields != null ) {
//        this.sections[ns] = {'label':'', 'fields':{}};
//        for(var j in this.sections[s].fields) {
//            this.sections[ns].fields[j+'_'+i] = this.sections[s].fields[j];
//        }
        // Set the fields default values
        for(j in this.sections[s].fields) {
            this.setDefaultFieldValue(s, j, i);
        }
        tb = M.aE('tbody');
        var ct = this.createFormFields(s, tb, this.panelUID, this.sections[s]['fields'], i);
        if( ct == 0 || ct > 1 ) {
            var t = M.addTable(this.panelUID + '_' + s + '_' + i, 'list noheader form outline');
        } else {
            var t = M.addTable(this.panelUID + '_' + s + '_' + i, 'list noheader form outline');
        }
        t.appendChild(tb);
        
        // Append new fields after previous
        if( ps != null ) {
            if( this.sections[s].multiinsert != null && this.sections[s].multiinsert != 'first' ) {
                ps.parentNode.insertBefore(t, ps.nextSibling);
            } else {
                // ps = M.gE(this.panelUID + '_section_' + s);
                ps.parentNode.insertBefore(t, ps);
            }
        }
//        if( this.sections[s].livesearchempty != null ) {
//            this.sections[ns].livesearchempty = this.sections[s].livesearchempty;
//        }
    }
};

//
// Arguments
// nF - The new Form element passed from the calling function
// fI - The formID for the form
// grid - the list of fields for the grid

//
M.panel.prototype.createFormGridFields = function(fI, s, grid) {
    var ct = 0;
    var tb = M.aE('tbody');

    //
    // Add header row if specified
    //
    if( grid.header != null ) {
        // Add blank cell if labels present
        var tr = M.aE('tr');
        if( grid.labels != null && grid.labels.length > 0 ) {
            tr.appendChild(M.aE('td',null,'empty'));
        }
        for(i=0;i<grid.cols;i++) {
            if( M.size == 'compact' && grid.compact_header != null && grid.compact_header[i] != null ) {
                tr.appendChild(M.aE('td',null,'gridheaderlabel',grid.compact_header[i]));
            } else if( grid.header[i] != null ) {
                tr.appendChild(M.aE('td',null,'gridheaderlabel',grid.header[i]));
            } else {
                tr.appendChild(M.aE('td'));
            }
        }    
        tb.appendChild(tr);
    }

    for(var i=0;i<grid.rows;i++) {
            
        // var tr = M.aE('tr', this.panelUID + '_' + s + '_' + i,'textfield');
        var tr = M.aE('tr', null,'gridfields');
        if( grid.labels != null && grid.labels.length > 0 ) {
            if( grid.labels[i] != null ) {
                tr.appendChild(M.aE('td', null, 'label', grid.labels[i]));
            }
        }
        for(var j=0;j<grid.cols;j++) {
            if( grid.fields[i] != null && grid.fields[i][j] != null ) {
                var fid = grid.fields[i][j]['id'];
                var field = grid.fields[i][j];
                if( field.type == 'colour' ) {
                    var td = M.aE('td', null, 'gridfield');
                    var c = M.aE('span', this.panelUID + '_' + fid, 'colourswatch', '&nbsp;');
                    c.setAttribute('onclick', 'M.' + this.appID + '.' + this.name + '.toggleFormColourPicker(\'' + fid + '\');');
                    c.setAttribute('name', fid);
                    var v = this.fieldValue(s, fid, field);
                    if( v != null ) {
                        c.style.backgroundColor = v;
                    } else {
                        c.style.backgroundColor = '#ffffff';
                    }
                    td.appendChild(c);
                    tr.appendChild(td);
                } 
            
//                else if( field.type == 'text' 
//                    || field.type == 'email' 
//                    || field.type == 'integer'
//                    || field.type == 'search' 
//                    || field.type == 'hexcolour' 
//                    || field.type == 'date' ) {
//                    var f = M.aE('input', this.panelUID + '_' + fid, field.type);
//                    f.setAttribute('name', fid);
//                    if( field.type == 'date' || field.type == 'integer' ) {
//                        f.setAttribute('type', 'text');
//                    } else {
//                        f.setAttribute('type', field.type);
//                    }
//                    if( field.size == 'small' ) {
//                        f.setAttribute('class', field.type + ' small');
//                    }
//                    if( field.hint != null && field.hint != '' ) {
//                        f.setAttribute('placeholder', field.hint);
//                    }
//                    var v = this.fieldValue(s, fid, field);
//                    if( v != null ) {
//                        f.value = v;
//                    }
//                    if( field.livesearch != null && field.livesearch != '' ) {
//                        if( field.livesearchempty == 'yes' ) {
//                            f.setAttribute('onfocus', this.panelRef + '.liveSearchSection(\'' + s + '\',\'' + fid + '\',this,event);');
//                            // onblur won't work, result disappear before onclick can be processed
//                            // f.setAttribute('onblur', this.panelRef + '.removeLiveSearch(\'' + fid + '\');');
//                        }
//                        f.setAttribute('onkeyup', this.panelRef + '.liveSearchSection(\'' + s + '\',\'' + fid + '\',this,event);');
//                        // f.setAttribute('onblur', this.panelRef + '.removeLiveSearch(\'' + s + '\',\'' + fid + '\');');
//                        f.setAttribute('autocomplete', 'off');
//                        this.lastSearches[fid] = '';
//                    }
//                    var c = M.aE('td', null, 'input');
//                    c.appendChild(f);
//                    if( field.type == 'fkid' ) {
//                        c.appendChild(f2);
//                    }
//                    if( field.type == 'date' ) {
//                        c.appendChild(M.aE('span',null,'rbutton_off','D','M.' + this.appID + '.' + this.name + '.toggleFormFieldCalendar(\'' + fid + '\');'));
//                    }
//                    // Add time field
//                    if( field.type == 'datetime' ) {
//                        var f = M.aE('input', this.panelUID + '_' + fid, field.type);
//                        f.setAttribute('name', fid + '_time');
//                    }
//
//                    tr.appendChild(c);
//                }
                else if( field.type == 'label' ) {
                    tr.appendChild(M.aE('td',null,'', field.label));
                    // FIXME: Add code to deal with other input types
                }
                else {
                    var td = M.aE('td', null,'');
                    td.appendChild(this.createFormField(s, fid, field, fid));
                    tr.appendChild(td);
                }
            }

            //
            // No field, add empty cell
            //
            else {
                tr.appendChild(M.aE('td', null, null, '&nbsp;'));
            }
        }

        //
        // Check if there's history
        //
        if( this.gridRowHistory != null && this.gridRowHistory != '' ) {
            tr.appendChild(M.aE('td',null,'historybutton','<span class="rbutton_off">H</span>', 'M.' + this.appID + '.' + this.name + '.toggleFormGridHistory(\'' + s + '\',\'' + fid + '\');'));
        }

        tb.appendChild(tr);
    }

    var t = M.addTable(null, 'list noheader form outline');
    t.appendChild(tb);
    return t;
};

M.panel.prototype.createPanelTabs = function(s, sc) {
    var t = M.addTable(this.panelUID + '_' + s, 'list form paneltabs noheader');
    var tb = M.aE('tbody');
    var tr = M.aE('tr');
    if( sc.joined != null && sc.joined == 'no' ) {
        var c = M.aE('td',null,'textfield aligncenter tabs');
    } else {
        var c = M.aE('td',null,'textfield aligncenter joinedtabs');
    }
    var div = M.aE('div', null, 'buttons');
    for(i in sc.tabs) {
        var lt = sc.tabs[i].label;
        if( sc.tabs[i].visible != null && typeof sc.tabs[i].visible == 'function' && sc.tabs[i].visible() == 'no' ) { continue; }
        if( sc.tabs[i].visible != null && sc.tabs[i].visible == 'no' ) { continue; }
        if( sc.count != null ) {
            var ct = sc.count(i);
            if( ct != '' ) {
                lt += '<span class="count">' + ct + '</span>';
            }
        }
        var e= null;
        if( i == sc.selected ) {
            e = M.aE('span', null, 'toggle_on', lt);
        } else {
            e = M.aE('span', null, 'toggle_off', lt, sc.tabs[i].fn);
        }
        div.appendChild(e);
    }
    c.appendChild(div);
    tr.appendChild(c);
    tb.appendChild(tr);
    t.appendChild(tb);

    return t;
};

M.panel.prototype.createHtml = function(s, sc) {
    var t = M.addTable(this.panelUID + '_' + s, 'list form noheader border');
    var tb = M.aE('tbody');
    var tr = M.aE('tr');
    var c = M.aE('td',null,'');
    if( this.sectionData != null ) {
        c.innerHTML = this.sectionData(s);
    } else {
        c.innerHTML = sc.html;
    }
    tr.appendChild(c);
    tb.appendChild(tr);
    t.appendChild(tb);

    return t;
};

M.panel.prototype.createHtmlContent = function(s, sc) {
    var t = M.addTable(this.panelUID + '_' + s, 'list html noheader border');
    var tb = M.aE('tbody');
    var tr = M.aE('tr');
    var c = M.aE('td',null,'');
    if( this.sectionData != null ) {
        c.innerHTML = this.sectionData(s);
    } else {
        c.innerHTML = sc.html;
    }
    tr.appendChild(c);
    tb.appendChild(tr);
    t.appendChild(tb);

    return t;
};

//
// sc = section
// ft = formtab
// fv = form field value
M.panel.prototype.createFormTabs = function(sc, ft, fv) {
    var t = M.addTable(this.panelUID + '_formtabs', 'list form formtabs noheader');
    var tb = M.aE('tbody');
    var tr = M.aE('tr');
    var c = M.aE('td',null,'textfield aligncenter');
    var div = M.aE('div', null, 'buttons');
    for(i in sc.tabs) {
        if( sc.tabs[i].visible != null && sc.tabs[i].visible == 'no' ) { continue; }
        var e= null;
        if( (sc.tabs[i].form == null && i == ft) || (sc.tabs[i].form != null && sc.tabs[i].field_id == fv) ) {
            e = M.aE('span', null, 'toggle_on', sc.tabs[i].label);
        } else {
            if( sc.tabs[i].fn != null ) {
                e = M.aE('span', null, 'toggle_off', sc.tabs[i].label, sc.tabs[i].fn);
            } else if( sc.tabs[i].form != null ) {
                e = M.aE('span', null, 'toggle_off', sc.tabs[i].label, this.panelRef + '.switchForm("' + sc.tabs[i].form + '","' + sc.tabs[i].field_id + '");');
            } else {
                e = M.aE('span', null, 'toggle_off', sc.tabs[i].label, this.panelRef + '.switchForm("' + i + '");');
            }
        }
        div.appendChild(e);
    }
    c.appendChild(div);
    tr.appendChild(c);
    tb.appendChild(tr);
    t.appendChild(tb);

    return t;
};


//
// Arguments
// s - The section
// fields - the fields definitions
//
M.panel.prototype.createSectionForm = function(s, fields) {
    if( this.sections[s].multi != null && this.sections[s].multi == 'yes' ) {
        var c = this.sectionCount(s);
        var f = document.createDocumentFragment();
        if( this.sections[s].multiinsert != null && this.sections[s].multiinsert == 'first' ) {
            for(j=c-1;j>=0;j--) {
                var tb = M.aE('tbody');
                var ct = this.createFormFields(s, tb, this.panelUID, fields, j);
                st = M.addTable(this.panelUID + '_' + s + '_' + j, 'list noheader form outline');
                st.appendChild(tb);
                f.appendChild(st);
            }
        } else {
            for(j=0;j<c;j++) {
                var tb = M.aE('tbody');
                var ct = this.createFormFields(s, tb, this.panelUID, fields, j);
                st = M.addTable(this.panelUID + '_' + s + '_' + j, 'list noheader form outline');
                st.appendChild(tb);
                f.appendChild(st);
            }
        }
        return f;
    } else {
        tb = M.aE('tbody');
        var ct = this.createFormFields(s, tb, this.panelUID, this.sections[s].fields, null);
        tid = this.panelUID + '_' + s;
        if( this.sections[s].type != null && this.sections[s].type == 'imageform' ) {
            st = M.addTable(tid, 'list imageform noheader form');
        } else if( ct == 0 || ct > 1 ) {
            st = M.addTable(tid, 'list noheader form outline');
        } else {
            st = M.addTable(tid, 'list noheader form outline');
        }
        st.appendChild(tb);
        return st;
    }
};

//
// Arguments
// nF - The new Form element passed from the calling function
// fI - The formID for the form
// cH - The callback function for the field history
// cF - The callback function for the field help
// fS - The sections of the form
// mN
//
M.panel.prototype.createFormFields = function(s, nF, fI, fields, mN) {
    var ct = 0;
    var ef = 0;    // Keep track of the number of editable fields (used to display outline)
    for(i in fields) {
        //
        // Check if field should be shown
        //
        if( typeof fields[i].active == 'function' && fields[i].active() == 'no' ) {
            continue;
        }
        if( fields[i].active != null && fields[i].active == 'no' ) {
            continue;
        }
        //
        // Setup the field ID variable.
        //
        var fid = i;
        if( this.fieldID != null ) {
            fid = this.fieldID(s, i, fields[i]);
            if( mN != null ) {
                fid += '_' + mN;
            }
        }

        // Check for guided mode title
        if( fields[i].gtitle != null && fields[i].gtitle != '' ) {
            var rgt = M.aE('tr', null, 'guided-title guided-show');
            var c = M.aE('td', null, null, '<p>' + fields[i].gtitle + '</p>');
            c.colSpan = 2;
            rgt.appendChild(c);
            nF.appendChild(rgt);
        }

        // Create the new row element
        var r = M.aE('tr');
        var visible = 'yes';
        if( typeof fields[i].visible == 'function' && fields[i].visible() == 'no' ) {
            visible = 'no';
            r.style.display = 'none';
            if( rgt != null ) { rgt.style.display = 'none'; }
        } else if( fields[i].visible != null && fields[i].visible == 'no' ) {
            visible = 'no';
            r.style.display = 'none';
            if( rgt != null ) { rgt.style.display = 'none'; }
        }
        if( fields[i].hidelabel == null || fields[i].hidelabel != 'yes' ) {
            var l = M.aE('label');
            l.setAttribute('for', this.panelUID + '_' + i);
            l.setAttribute('id', this.panelUID + '_' + i + '_formlabel');
            l.appendChild(document.createTextNode(fields[i].label));
            var c = M.aE('td');
            c.className = 'label';
            if( fields[i].editFn != null && fields[i].editFn != '' ) {
                c.setAttribute('onclick', fields[i].editFn);
            }
            c.appendChild(l);
            r.appendChild(c);
        } 
        if( fields[i].type != 'noedit' ) {
            ef++;
        }

        //
        // Call the generic generate form field
        //
        r.className = 'textfield ' + fields[i].type;
        if( fields[i].editFn != null && fields[i].editFn != '' ) {
//            r.setAttribute('onclick', fields[i].editFn);
            r.className += ' clickable';
        }
        if( fields[i].separator != null && fields[i].separator == 'yes' ) {
            r.className += ' separator';
        }
        r.appendChild(this.createFormField(s, i, fields[i], fid, mN));
        nF.appendChild(r);

        // Check if there should be a help button for the field.
        if( this.fieldHelp != null && this.fieldHelp != '' ) {
            var h = M.aE('td');
            h.className = 'helpbutton';
            // h.setAttribute('onclick', 'M.toggleFormFieldHelp(\'' + fI + '\',\'' + i + '\',\'' + this.fieldHelp + '\');');
            h.setAttribute('onclick', this.fieldHelp(i, fields[i]));
            h.innerHTML = '<img src=\'' + M.themes_root_url + '/default/img/help.png\' />';
            r.appendChild(h);
        }

        // Check if there should be a history button displayed
        if( this.fieldHistory != null && this.fieldHistory != '' && (fields[i].history == null || fields[i].history == 'yes') ) {
            r.appendChild(M.aE('td',null,'historybutton guided-hide','<span class="rbutton_off">H</span>','M.' + this.appID + '.' + this.name + '.toggleFormFieldHistory(event, \'' + s + '\',\'' + fid + '\');'));
        } else if( this.fieldHistoryArgs != null && this.fieldHistoryArgs != '' && (fields[i].history == null || fields[i].history == 'yes') ) {
            r.appendChild(M.aE('td',null,'historybutton guided-hide','<span class="rbutton_off">H</span>','event.stopPropagation(); M.' + this.appID + '.' + this.name + '.toggleFormFieldHistory(event, \'' + s + '\',\'' + fid + '\');'));
        }
        if( fields[i].editFn != null && fields[i].editFn != '' ) {
            r.appendChild(M.aE('td', null, 'buttons noprint','<span class="icon">r</span>',fields[i].editFn));
        }

        ct++;

        //
        // If the field added was of type image_id, then extra buttons are required
        //
        if( fields[i].type == 'image_id' ) {
            var img_id = this.fieldValue(s, i, fields[i], mN);
            //
            // Create the form upload field, but hide it
            //
            var f = null;
            if( this.uploadImage != null || ((this.addDropImage != null || fields[i].addDropImage != null) && fields[i].controls == 'all') ) {
                f = M.aE('input', this.panelUID + '_' + i + '_upload', 'image_uploader');
                f.setAttribute('name', i);
                f.setAttribute('type', 'file');
                if( this.uploadImage != null ) {
                    f.setAttribute('onchange', this.uploadImage(i));
                } else {
                    f.setAttribute('onchange', this.panelRef + '.uploadDropImages(\'' + i + '\');');
                }
                f.setAttribute('onfocus', this.panelRef + '.clearLiveSearches(\''+s+'\',\''+i+'\');');
            }
            var btns = this.createImageControls(i, fields[i], img_id);
            if( btns != null && btns.childNodes != null && btns.childNodes.length > 0 ) {
                var r = M.aE('tr',null,'imagebuttons');
                var td = M.aE('td',null,'aligncenter');
                var d = M.aE('div', this.panelUID + '_' + i + '_controls', 'buttons');
                d.appendChild(btns);
                td.appendChild(d);
                if( f != null ) {
                    td.appendChild(f);
                }
                r.appendChild(td);
                nF.appendChild(r);
            }
        }
        //
        // Check if there is guided text for this field
        //
        if( fields[i].htext != null && fields[i].htext != '' ) {
            var r = M.aE('tr', null, 'xhelp-text');
            if( fields[i].hidelabel == null || fields[i].hidelabel != 'yes' ) {
                r.appendChild(M.aE('td',null,null,''));
            }
            if( visible == 'no' ) {
                r.style.display = 'none';
            }
            var c = M.aE('td', null, null, '<p>' + fields[i].htext + '</p>');
            r.appendChild(c);
            nF.appendChild(r);
        }
    }

    if( ct == 0 && this.noData != null ) {
        var tr = M.aE('tr');
        tr.appendChild(M.aE('td', null, null, this.noData()));
        tb.appendChild(tr);
    }

    return ef;
};

//
// Create the image buttons to change/rotate/delete/download images
//
M.panel.prototype.createImageControls = function(i, field, img_id) {
    var btns = document.createDocumentFragment();
    if( this.uploadImage != null || ((this.addDropImage != null || field.addDropImage != null) && field.controls == 'all') ) {
        // 
        // Show upload button, which will reveal a file upload form field
        //
        if( img_id > 0 ) {
            var btn = M.aE('span', null, 'toggle_off', 'Change Photo');
        } else {
            var btn = M.aE('span', null, 'toggle_off', 'Add Photo');
        }
        btn.setAttribute('onclick', this.panelRef + '.uploadFile(\'' + i + '\');');
        btns.appendChild(btn);
    }
    if( img_id > 0 ) {
        // Show buttons for rotate, etc...
        if( this.rotateImage != null ) {
            var btn = M.aE('span', null, 'toggle_off', '<span class="icon">I</span>');
            btn.setAttribute('onclick', this.panelRef + '.rotateImage(\'' + i + '\');');
            btns.appendChild(btn);
        } 
        // Show Edit button
        if( M.modFlagOn('ciniki.images', 0x01) && field.controls == 'all' ) {
            var btn = M.aE('span', null, 'toggle_off', 'Edit');
            btn.setAttribute('onclick', 'M.startApp(\'ciniki.images.editor\',null,\'' + this.panelRef + '.updateImage("' + i + '");\',\'mc\',{\'tnid\':M.curTenantID, \'image_id\':\'' + img_id + '\'});');
            btns.appendChild(btn);
        } else {
            if( field.controls == 'all' ) {
                var btn = M.aE('span', null, 'toggle_off', '<span class="icon">I</span>');
                btn.setAttribute('onclick', this.panelRef + '.rotateImg(\'' + i + '\',\'left\');');
                btns.appendChild(btn);
            }
            if( field.controls == 'all' ) {
                var btn = M.aE('span', null, 'toggle_off', '<span class="icon">J</span>');
                btn.setAttribute('onclick', this.panelRef + '.rotateImg(\'' + i + '\',\'right\');');
                btns.appendChild(btn);
            }
        }
        // Show delete button
        if( field.deleteImage != null ) {
            if( typeof field.deleteImage == 'function' ) {
                var btn = M.aE('span', null, 'toggle_off', '<span class="icon">V</span>');
                btn.onclick = function() { field.deleteImage(i); };
                btns.appendChild(btn);
            } else {
                var btn = M.aE('span', null, 'toggle_off', '<span class="icon">V</span>');
                btn.setAttribute('onclick', field.deleteImage + '(\'' + i + '\');');
                btns.appendChild(btn);
            }
        } else if( this.deleteImage != null ) {
            var btn = M.aE('span', null, 'toggle_off', '<span class="icon">V</span>');
            btn.setAttribute('onclick', this.panelRef + '.deleteImage(\'' + i + '\');');
            btns.appendChild(btn);
        }
        // Show download button
        if( field.controls == 'all' ) {
            var btn = M.aE('span', null, 'toggle_off', '<span class="icon">G</span>');
            btn.setAttribute('onclick', 'M.api.openFile(\'ciniki.images.get\', {\'tnid\':M.curTenantID, \'image_id\':\'' + img_id + '\', \'version\':\'original\', \'attachment\':\'yes\'});');
            btns.appendChild(btn);
        }
    }
    return btns;
}

//
// Placeholder for return from image edit. Could
M.panel.prototype.updateImage = function(field) {
    this.show();
}

M.panel.prototype.uploadFile = function(i) {
    var f = M.gE(this.panelUID + '_' + i + '_upload');
    if( f != null ) { f.click(); }
};

M.panel.prototype.refreshFormField = function(s, fid) {
    var o = M.gE(this.panelUID + '_' + fid);
    if( o == null || o.parentNode == null ) {
        return true;
    }
    o = o.parentNode;
    var l = M.gE(this.panelUID + '_' + fid + '_formlabel');
    if( l != null && l.innerHTML != null && l.innerHTML != this.sections[s].fields[fid].label ) {
        l.innerHTML = this.sections[s].fields[fid].label;
    }
    if( this.sections[s].fields[fid].visible != null && this.sections[s].fields[fid].visible == 'no' ) {
        o.parentNode.style.display = 'none';
    } else {
        o.parentNode.style.display = '';
        var n = this.createFormField(s, fid, this.formField(fid), fid);
        o.parentNode.insertBefore(n, o);
        o.parentNode.removeChild(o);
    }
};

M.panel.prototype.showHideFormField = function(s, fid) {
    var o = M.gE(this.panelUID + '_' + fid).parentNode;
    var l = M.gE(this.panelUID + '_' + fid + '_formlabel');
    if( l != null && l.innerHTML != null && l.innerHTML != this.sections[s].fields[fid].label ) {
        l.innerHTML = this.sections[s].fields[fid].label;
    }
    if( this.sections[s].fields[fid].visible != null && this.sections[s].fields[fid].visible == 'no' ) {
        o.parentNode.style.display = 'none';
    } else {
        o.parentNode.style.display = '';
    }
};

M.panel.prototype.createFormField = function(s, i, field, fid, mN) {
    // section field number, for forms which can have multiple of same field
    var sFN = '';
    if( mN != null ) {
        sFN = '_' + mN;
    }
    var c = M.aE('td', null, 'input');
    if( field.type == 'password' ) {
        var f = M.aE('input');
        f.setAttribute('id', this.panelUID + '_' + i + sFN);
        f.setAttribute('name', i + sFN);
        f.setAttribute('type', field.type);
        if( field.hint != null && field.hint != '' ) {
            f.setAttribute('placeholder', field.hint);
        }
        c.appendChild(f);
        f.setAttribute('onfocus', this.panelRef + '.clearLiveSearches(\''+s+'\',\''+i+sFN+'\');');
    }
    else if( field.type == 'fkid' ) {
        var f = M.aE('input', this.panelUID + '_' + i + sFN, field.type);
        f.setAttribute('name', i + sFN);
        f.setAttribute('type', 'hidden');
        var f2 = M.aE('input', this.panelUID + '_' + i + sFN + '_fkidstr', 'text');
        f2.setAttribute('name', i + '_fkidstr');
        f2.setAttribute('onfocus', this.panelRef + '.clearLiveSearches(\''+s+'\',\''+i+sFN+'\');');
        f2.setAttribute('onchange', 'M.gE(\'' + this.panelUID + '_' + i + sFN + '\').value = 0;');
        f2.setAttribute('autocomplete', 'off');
        if( field.hint != null && field.hint != '' ) {
            f2.setAttribute('placeholder', field.hint);
        }
        var v = this.fieldValue(s, i, field, mN);
        if( v != null ) {
            f.value = v;
        }
        v = this.fieldValue(s, i + '_fkidstr', field, mN);
        if( v != null ) {
            f2.value = v;
        }
        if( field.livesearch != null && field.livesearch == 'yes' ) {
//            if( field.livesearchempty == 'yes' ) {
                f2.setAttribute('onfocus', this.panelRef + '.liveSearchSection(\'' + s + '\',\'' + i + sFN + '\',this,event);');
                // onblur won't work, result disappear before onclick can be processed
                // f.setAttribute('onblur', this.panelRef + '.removeLiveSearch(\'' + i + '\');');
//            }
            f2.setAttribute('onkeyup', this.panelRef + '.liveSearchSection(\'' + s + '\',\'' + i + sFN + '\',this,event);');
            // f2.setAttribute('onblur', this.panelRef + '.removeLiveSearch(\'' + s + '\',\'' + i + '\');');
            f2.setAttribute('autocomplete', 'off');
            this.lastSearches[i + sFN] = '';
        }
        c.appendChild(f);
        c.appendChild(f2);
        if( field.size != '' ) {
            f2.setAttribute('class', field.type + ' ' + field.size);
        }
    }
    else if( field.type == 'timeduration' ) {
        var f = M.aE('input', this.panelUID + '_' + i + sFN, 'timeduration');
        f.setAttribute('name', i + sFN);
        f.setAttribute('onfocus', this.panelRef + '.clearLiveSearches(\''+s+'\',\''+i+sFN+'\');');
        // FIXME: onchange done, update to time format if integer
        var v = this.fieldValue(s, i, field, mN);
        v = parseInt(v);
        if( typeof v == 'number' && v > 60 ) {
            v = Math.floor(v/60) + ':' + (((v%60)<10)?'0':'') + (v%60);
        }
        if( v != null ) {
            f.value = v;
        }
        c.appendChild(f);
        var d = null;
        var f2 = null;
        if( field.buttons != null ) {
            d = M.aE('div', this.panelUID + '_' + i + sFN + '_buttons', 'buttons');
            for(var j in field.buttons) {
                f2 = M.aE('span', null, 'toggle_off', '' + j);
                f2.setAttribute('onclick', this.panelRef + '.setFromButton(this, \'' + i + sFN + '\',\'' + field.buttons[j] + '\');');
                d.appendChild(f2);
            }
            c.appendChild(d);
        }
        if( field.allday == 'yes' ) {
            var ad = this.fieldValue(s, i + '_allday', field, mN);
            var d2 = M.aE('div', this.panelUID + '_' + i + sFN + '_buttons_allday', 'buttons');
            var f3 = M.aE('span', null, 'toggle_off', 'All day');
            if( ad == 'yes' ) { 
                f.value = '24:00';
                f.oldvalue = '60';
                f.style.display = 'none';
                if( d != null ) { d.style.display = 'none'; }
                d2.className = 'buttons nopadbuttons';
                f3.className = 'toggle_on';
            }
            f3.setAttribute('onclick', this.panelRef + '.setFromButton(this,\'' + i + sFN + '\',\'allday\');');
            d2.appendChild(f3);
            c.appendChild(d2);
        }
    }
    else if( field.type == 'info' ) {
        var v = this.fieldValue(s, i, field, mN);
        var f = M.aE('span', this.panelUID + '_' + i + sFN, field.type, v);
        c.appendChild(f);
    }
    else if( field.type == 'text' || field.type == 'email' 
        || field.type == 'integer'
        || field.type == 'number'
        || field.type == 'search' 
        || field.type == 'hexcolour' 
        || field.type == 'date' ) {
        var f = M.aE('input', this.panelUID + '_' + i + sFN, field.type);
        f.setAttribute('name', i);
        if( field.autofocus != null && field.autofocus == 'yes' ) {
//            f.setAttribute('autofocus', '');
            this.autofocus = this.panelUID + '_' + i + sFN;
        }
        f.setAttribute('onfocus', this.panelRef + '.clearLiveSearches(\''+s+'\',\''+i+sFN+'\');');
        if( field.type == 'date' || field.type == 'integer' ) {
            f.setAttribute('type', 'text');
        } else {
            f.setAttribute('type', field.type);
        }
        if( field.size != null && field.size != '' ) {
            f.setAttribute('class', field.type + ' ' + field.size);
        }
        if( field.editable != null && field.editable == 'no' ) {
            f.setAttribute('readonly', 'yes');
            f.className = f.className + ' readonly';
        }
        if( field.maxlength != null && field.maxlength > 0 ) {
            f.setAttribute('maxlength', field.maxlength);
        }
        if( field.hint != null && field.hint != '' ) {
            f.setAttribute('placeholder', field.hint);
        }
        var v = this.fieldValue(s, i, field, mN);
        if( v != null ) {
            f.value = v;
        }
        f.setAttribute('autocomplete', 'off');
        if( field.livesearch != null && field.livesearch == 'yes' ) {
//            if( field.livesearchempty == 'yes' ) {
                f.setAttribute('onfocus', this.panelRef + '.liveSearchSection(\'' + s + '\',\'' + i + sFN + '\',this,event);');
                // onblur won't work, result disappear before onclick can be processed
                // f.setAttribute('onblur', this.panelRef + '.removeLiveSearch(\'' + i + '\');');
//            }
            f.setAttribute('onkeyup', this.panelRef + '.liveSearchSection(\'' + s + '\',\'' + i + sFN + '\',this,event);');
//            f.setAttribute('autocomplete', 'off');
            this.lastSearches[i] = '';
        }
        if( field.enterFn != null && field.enterFn != '' ) {
            f.setAttribute('onkeyup', 'if( event.keyCode == 13 ) { ' + field.enterFn + ' };');
        }
        if( field.onchangeFn != null && field.onchangeFn != '' ) {
            f.setAttribute('onchange', field.onchangeFn + '(\'' + s + '\',\'' + i+sFN+'\');');
        }
        if( field.onkeyupFn != null && field.onkeyupFn != '' ) {
            f.setAttribute('onkeyup', field.onkeyupFn + '(\'' + s + '\',\'' + i+sFN+'\');');
        }
        c.appendChild(f);
//            if( field.type == 'fkid' ) {
//                c.appendChild(f2);
//            }
        if( field.type == 'date' && (field.editable == null || field.editable == 'yes') ) {
            c.appendChild(M.aE('span',null,'rbutton_off','D','M.' + this.appID + '.' + this.name + '.toggleFormFieldCalendar(\'' + fid + sFN + '\');'));
            //var img = M.aE('img', null, 'calendarbutton');
            //img.src = '' + M.themes_root_url + '/default/img/calendarA.png';
            //img.setAttribute('onclick', 'M.' + this.appID + '.' + this.name + '.toggleFormFieldCalendar(\'' + i + '\');');
            //c.appendChild(img);
        }
        // Display extra options for a field
//        if( field.option_field != null && field.option_field != '' && field.options != null ) {
//            d = M.aE('div', this.panelUID + '_' + i + sFN + '_options', 'toggles');
//            for(var j in field.options) {
//                f2 = M.aE('span', null, 'toggle_off', '' + j);
//                f2.setAttribute('onfocus', this.panelRef + '.clearLiveSearches(\''+s+'\',\''+i+sFN+'\');');
//                f2.setAttribute('onclick', this.panelRef + '.setFromButton(this, \'' + i + sFN + '\',\'' + field.options[j] + '\');');
//                f2.setAttribute('onclick', this.panelRef + '.setToggleField(this, \'' + i + sFN + '\',\'' + field.none + '\',\'' + field.fn + '\');');
//                d.appendChild(f2);
//            }
//            c.appendChild(d);

//            var f = M.aE('span', this.panelUID + '_' + fid + sFN + '_' + j);
//            f.setAttribute('onfocus', this.panelRef + '.clearLiveSearches(\''+s+'\',\''+i+sFN+'\');');
//            if( v == j ) {
//                f.className = 'toggle_on';
//            } else {
//                f.className = 'toggle_off';
//            }
//            f.innerHTML = field.toggles[j];
//            f.setAttribute('onclick', this.panelRef + '.setToggleField(this, \'' + i + sFN + '\',\'' + field.none + '\',\'' + field.fn + '\');');
//            div.appendChild(f);
//        }
        // Add time field
//            if( field.type == 'datetime' ) {
//                var f = M.aE('input', this.panelUID + '_' + i, field.type);
//                f.setAttribute('name', i + '_time');
//            }
    }
    else if( field.type == 'appointment' ) {
        var f = M.aE('input', this.panelUID + '_' + i + sFN + '', 'datetime');
        f.setAttribute('name', i + sFN + '');
        f.setAttribute('type', 'text');
        f.setAttribute('onfocus', this.panelRef + '.clearLiveSearches(\''+s+'\',\''+i+sFN+'\');');
        var v = this.fieldValue(s, i, field, mN);
        if( v != null ) {
            f.value = v;
        }
        if( field.duration != null ) {
            var df = this.formField(field.duration);
            if( df.allday == 'yes' ) {
                var dfv = this.fieldValue(s, field.duration + '_allday', df, mN);
                if( dfv == 'yes' ) {
                    // default to 8am
                    f.oldtime = '08:00 am';
// FIXME: change Aug 18, 2015 and need to remove commented line below after testing
//                    f.value = M.dateFormat(this.fieldValue(s, i + '_date', field, mN));
                    if( v != null ) {
                        f.value = M.dateFormat(v);
                    }
                }
            }
        }
        c.appendChild(f);
        c.appendChild(M.aE('span',null,'rbutton_off','D','M.' + this.appID + '.' + this.name + '.toggleFormFieldAppointment(\'' + i + sFN + '\');'));
    }
    else if( field.type == 'htmlarea' ) {
        var f = M.aE('div', this.panelUID + '_' + i + sFN, (field.type=='textarea'?null:field.type) + (field.size!=null?' ' + field.size:''));
        f.setAttribute('name', i + sFN);
        f.setAttribute('onfocus', this.panelRef + '.clearLiveSearches(\''+s+'\',\''+i+sFN+'\');');
//        if( field.size != null && field.size == 'small' ) {
//            f.setAttribute('rows', 2);
//            f.setAttribute('class', 'small');
//        } else if( field.size != null && field.size == 'large' ) {
//            f.setAttribute('rows', 12);
//            f.setAttribute('class', 'large');
//        } else {
//            f.setAttribute('rows', 6);
//        }
//        if( field.hint != null && field.hint != '' ) {
//            f.setAttribute('placeholder', field.hint);
//        }
        var v = this.fieldValue(s, i, field, mN);
        if( v != null ) {
            f.innerHTML = v.replace(/\n/g,"<br />");
        }
        c.className = field.type + (field.size!=null?' ' + field.size:'');
        this.tinymce.push(this.panelUID + '_' + i + sFN);
        c.appendChild(f);
        // Create the toolbar
        var f = M.aE('div', this.panelUID + '_' + i + sFN + '_htmlarea_toolbar', 'htmlarea-toolbar');
//                tinymce.execCommand("mceRemoveEditor", false, this.tinymce[i]);
        f.innerHTML = '<span class="button_off" onmousedown="tinymce.get(\'' + this.panelUID + '_' + i + sFN + '\').execCommand(\'Bold\'); return false;"><strong>B</strong></span>'
            + '<span class="button_off" onmousedown="tinymce.get(\'' + this.panelUID + '_' + i + sFN + '\').execCommand(\'Italic\'); return false;"><em>I</em></span>'
            + '<span class="button_off" onmousedown="tinymce.get(\'' + this.panelUID + '_' + i + sFN + '\').execCommand(\'Underline\'); return false;"><u>U</u></span>'
            + '<span class="button_off" onmousedown="tinymce.get(\'' + this.panelUID + '_' + i + sFN + '\').execCommand(\'Strikethrough\'); return false;"><strike>S</strike></span>'
            + '';
        c.appendChild(f);
    }
    else if( field.type == 'textarea' || field.type == 'htmlarea' ) {
        var f = M.aE('textarea', this.panelUID + '_' + i + sFN, (field.type=='textarea'?null:field.type));
        f.setAttribute('name', i + sFN);
        f.setAttribute('onfocus', this.panelRef + '.clearLiveSearches(\''+s+'\',\''+i+sFN+'\');');
        if( field.size != null && field.size == 'small' ) {
            f.setAttribute('rows', 2);
            f.setAttribute('class', 'small');
        } else if( field.size != null && field.size == 'large' ) {
            f.setAttribute('rows', 12);
            f.setAttribute('class', 'large');
        } else if( field.size != null && field.size == 'xlarge' ) {
            f.setAttribute('rows', 30);
            f.setAttribute('class', 'xlarge');
        } else {
            f.setAttribute('rows', 6);
        }
        if( field.monospace != null && field.monospace == 'yes' ) {
            f.classList.add('monospace');
        }
        if( field.hint != null && field.hint != '' ) {
            f.setAttribute('placeholder', field.hint);
        }
        var v = this.fieldValue(s, i, field, mN);
        if( v != null ) {
            f.value = v; 
        }
        if( field.livesearch != null && field.livesearch == 'yes' ) {
            f.setAttribute('onkeyup', this.panelRef + '.liveSearchSection(\'' + s + '\',\'' + i + sFN + '\',this, event);');
            f.setAttribute('autocomplete', 'off');
            this.lastSearches[i] = '';
        }
        c.className = field.type; // 'textarea';
        c.appendChild(f);
        if( field.type == 'htmlarea' ) {
            this.tinymce.push(this.panelUID + '_' + i + sFN);
        }
    }
    else if( field.type == 'select' ) {
        var sel = M.aE('select', this.panelUID + '_' + fid + sFN);
        sel.setAttribute('name', fid + sFN);
        sel.setAttribute('onfocus', this.panelRef + '.clearLiveSearches(\''+s+'\',\''+fid+sFN+'\');');
        if( field.onchangeFn != null && field.onchangeFn != '' ) {
            sel.setAttribute('onchange', field.onchangeFn + '(\'' + s + '\',\'' + i+sFN+'\');');
        }
        var o = field.options;
        var fv = this.fieldValue(s, i, field, mN);
        for(j in o) {
            var n = o[j];
            var v = j;
            // If option_name is specified, then option is a complex object  
            // These are the result of an object sent back through cinikiAPI
            if( field.complex_options != null ) { 
                if( field.complex_options.subname != null ) {
                    n = o[j][field.complex_options.subname][field.complex_options.name];
                    v = o[j][field.complex_options.subname][field.complex_options.value];
                } else {
                    n = o[j][field.complex_options.name];
                    if( field.complex_options.value != null ) {
                        v = o[j][field.complex_options.value];
                    }
                }
            }

            //
            // Add the options to the select, and choose which one to have selected
            //
            if( v == fv ) {
                var op = new Option(n, v, 0, 1);
            } else {
                var op = new Option(n, v);
            }
// Code which can display a background colour behind select option, but does not work in all browsers.
//                    if( field['complex_options'] != null && field['complex_options']['bgcolor'] != null ) { 
//                        //op.setAttribute('background','#' + field['complex_options']['bgcolor']);
//                        op.style.background = '#' + o[j][field['complex_options']['subname']][field['complex_options']['bgcolor']];
//                    }
            sel.appendChild(op);
        }
        c.appendChild(sel);
    }
    else if( field.type == 'colourswatches' ) {
        var d = M.aE('div', this.panelUID + '_' + i + sFN, 'colourswatches');    
        d.setAttribute('name', fid + sFN);
        d.setAttribute('onfocus', this.panelRef + '.clearLiveSearches(\''+s+'\',\''+i+sFN+'\');');
        var o = field.colours;
        var v = this.fieldValue(s, i, field, mN);
        d.setAttribute('value', '');
        var found = 0;
        var j = 0;
        for(j in o) {
            if( j == v || (v == '' && j == '*') ) {
                d.setAttribute('value', j);
                var sw = M.aE('span', null, 'colourswatch selected', '&nbsp;');
                found = 1;
            } else {
                var sw = M.aE('span', null, 'colourswatch', '&nbsp;');
            }
            sw.setAttribute('name', j);
            sw.setAttribute('onclick', 'M.setColourSwatchField(\'' + this.panelUID + '_' + i + sFN + '\',\'' + j + '\');');
            sw.style.background = j;
            d.appendChild(sw);
        }
        if( found == 0 && v != null && v != '' ) {
            d.setAttribute('value', j);
            var sw = M.aE('span', null, 'colourswatch selected', '&nbsp;');
            sw.setAttribute('name', v);
            sw.setAttribute('onclick', 'M.setColourSwatchField(\'' + this.panelUID + '_' + i + sFN + '\',\'' + v + '\');');
            sw.style.background = v;
            d.appendChild(sw);
        }
        c.appendChild(d);
    }
    else if( field.type == 'colour' ) {
        var d = M.aE('span', this.panelUID + '_' + fid + sFN, 'colourswatch', '&nbsp;');
        d.setAttribute('onclick', 'M.' + this.appID + '.' + this.name + '.toggleFormColourPicker(\'' + fid + sFN + '\');');
        d.setAttribute('name', fid);
        d.setAttribute('onfocus', this.panelRef + '.clearLiveSearches(\''+s+'\',\''+i+sFN+'\');');
        var v = this.fieldValue(s, fid, field, mN);
        if( v != null ) {
            d.style.backgroundColor = v;
        } else {
            d.style.backgroundColor = '#ffffff';
        }
        c.appendChild(d);
    } 
    else if( field.type == 'flags' ) {
        if( field.join != null && field.join == 'yes' ) {
            c.className = 'joinedflags';
        } else {
            c.className = 'flags';
        }
        var div = M.aE('div', this.panelUID + '_' + fid + sFN);
        var v = this.fieldValue(s, fid, field, mN);
        // Javascript can't handle 64bit integers, need to split into hi and lo
        var vhi = parseInt(v, 10).toString(16);
        var vlo = vhi.substr(-8);
        vhi = vhi.length > 8 ? vhi.substr(0, vhi.length - 8) : '';
        vlo = parseInt(vlo, 16);
        vhi = parseInt(vhi, 16);
        for(j in field.flags) {
            if( field.flags[j] == null ) { continue; }
            if( field.flags[j].active != null && field.flags[j].active == 'no' ) { continue; }
            var f = M.aE('span', this.panelUID + '_' + fid + sFN + '_' + j);
            f.setAttribute('onfocus', this.panelRef + '.clearLiveSearches(\''+s+'\',\''+i+sFN+'\');');
//            var bit_value = (v&Math.pow(2,j-1))==Math.pow(2,j-1)?1:0;
            var bit_value = j > 32 ? (vhi>>(j-33)&0x01) : (vlo>>(j-1)&0x01);
            if( bit_value == 1 ) {
                f.className = 'flag_on';
            } else {
                f.className = 'flag_off';
            }
            f.innerHTML = field.flags[j].name;
            f.onclick = function() {
                if( this.className == 'flag_on' ) {
                    this.className = 'flag_off';
                } else {
                    this.className = 'flag_on';
                }
            };
            // Use a different onclick function if this is a toggle field, where only one can be active at a time
            if( field.toggle != null && field.toggle == 'yes' ) {
                f.onclick = function(event) {
                    event.stopPropagation();
                    if( this.className == 'flag_off' ) {
                        for(k in this.parentNode.children) {
                            this.parentNode.children[k].className = 'flag_off';
                        }
                        this.className = 'flag_on';
                    } else if( field.none != null && field.none == 'yes' && e.className == 'flag_on' ) {
                        this.className = 'flag_off';
                    }
                };
            } else {
                f.onclick = function(event) { 
                    event.stopPropagation();
                    if( this.className == 'flag_on' ) { this.className = 'flag_off'; } 
                    else { this.className = 'flag_on'; }
                };
            }
            div.appendChild(f);
        }
        c.appendChild(div)
    }
    else if( field.type == 'flagspiece' ) {
        if( field.join != null && field.join == 'yes' ) {
            c.className = 'joinedflags';
        } else {
            c.className = 'flags';
        }
        var div = M.aE('div', this.panelUID + '_' + fid + sFN);
        var v = this.fieldValue(s, field.field, field, mN);
        // Javascript can't handle 64bit integers, need to split into hi and lo
        var vhi = parseInt(v, 10).toString(16);
        var vlo = vhi.substr(-8);
        vhi = vhi.length > 8 ? vhi.substr(0, vhi.length - 8) : '';
        vlo = parseInt(vlo, 16);
        vhi = parseInt(vhi, 16);
        for(j in field.flags) {
            if( field.flags[j] == null ) { continue; }
            if( field.flags[j].active != null && field.flags[j].active == 'no' ) { continue; }
            var f = M.aE('span', this.panelUID + '_' + fid + sFN + '_' + j);
            f.setAttribute('onfocus', this.panelRef + '.clearLiveSearches(\''+s+'\',\''+i+sFN+'\');');
//            var bit_value = (v&Math.pow(2,j-1))==Math.pow(2,j-1)?1:0;
            var bit_value = j > 32 ? (vhi>>(j-33)&0x01) : (vlo>>(j-1)&0x01);
            if( bit_value == 1 ) {
                f.className = 'flag_on';
            } else {
                f.className = 'flag_off';
            }
            f.innerHTML = field.flags[j].name;
            f.onclick = function() {
                if( this.className == 'flag_on' ) {
                    this.className = 'flag_off';
                } else {
                    this.className = 'flag_on';
                }
            };
            if( field.onchange != null && field.onchange != '' ) {
                f.onChangeCb = field.onchange + '(null,\'' + s + '\',\'' + fid + '\');';
            }
            // Use a different onclick function if this is a toggle field, where only one can be active at a time
            if( field.toggle != null && field.toggle == 'yes' ) {
                f.onclick = function(event) {
                    event.stopPropagation();
                    if( this.className == 'flag_off' ) {
                        for(k in this.parentNode.children) {
                            this.parentNode.children[k].className = 'flag_off';
                        }
                        this.className = 'flag_on';
                    } else if( field.none != null && field.none == 'yes' && e.className == 'flag_on' ) {
                        this.className = 'flag_off';
                    }
                    if( this.onChangeCb != null ) {
                        eval(this.onChangeCb);
                    }
                };
            } else {
                f.onclick = function(event) { 
                    event.stopPropagation();
                    if( this.className == 'flag_on' ) { this.className = 'flag_off'; } 
                    else { this.className = 'flag_on'; }
                    if( this.onChangeCb != null ) {
                        eval(this.onChangeCb);
                    }
                };
            }
            div.appendChild(f);
        }
        c.appendChild(div)
    }
    else if( field.type == 'multitoggle' || field.type == 'toggle' ) {
        if( field.join != null && field.join == 'no' ) {
            c.className = 'multiselect';
        } else {
            c.className = 'multitoggle';
        }
        var div = M.aE('div', this.panelUID + '_' + fid + sFN);
        var v = this.fieldValue(s, i, field, mN);
        if( (v == null || v == '') && field.default != null && field.default != '' ) {
            v = field.default;
        }
        var onchange = '';
        if( field.onchange != null && field.onchange != '' ) {
            onchange = field.onchange;
        }
        for(j in field.toggles) {
            var f = M.aE('span', this.panelUID + '_' + fid + sFN + '_' + j);
//            f.setAttribute('onfocus', this.panelRef + '.clearLiveSearches(\''+s+'\',\''+i+sFN+'\');');
            if( v == j ) {
                f.className = 'toggle_on';
            } else {
                f.className = 'toggle_off';
            }
            f.innerHTML = field.toggles[j];
            f.setAttribute('onclick', 'event.stopPropagation();' + this.panelRef + '.setToggleField(this, \'' + i + sFN + '\',\'' + field.none + '\',\'' + field.fn + '\');' + onchange + '(event,\'' + s + '\',\'' + fid + '\');');
            div.appendChild(f);
        }
        c.appendChild(div)
        if( field.hint != null && field.hint != '' ) {
            c.appendChild(M.aE('span', this.panelUID + '_' + fid + sFN + '_hint', 'hint', field.hint));
        }
    }
    else if( field.type == 'flagtoggle' ) {
        c.className = 'multitoggle';
        var div = M.aE('div', this.panelUID + '_' + fid + sFN);
        var v = this.fieldValue(s, field.field, field, mN);
        if( typeof v != 'number' && (v == null || v == '') && field.default != null && field.default != '' ) {
            v = field.default;
        } else {
            if( (v&field.bit) > 0 ) {
                v = 'on';
            } else {
                v = 'off';
            }
        }
        var onchangeFn = '';
        if( field.onchange != null && field.onchange != '' ) {
            onchangeFn = field.onchange + '(event,\'' + s + '\',\'' + fid + '\');';
        }
        var updateFn = '';    
        if( field.on_fields != null || field.off_fields != null || field.on_sections != null || field.off_sections != null ) {
            updateFn = this.panelRef + '.updateFlagToggleFields(\'' + i + sFN + '\');';
        }
        if( field.on_sections != null && field.on_sections.length > 0 ) {
            for(var i in field.on_sections) {
                if( v == 'on' ) {
                    this.sections[field.on_sections[i]].visible = 'yes';
                } else {
                    this.sections[field.on_sections[i]].visible = 'hidden';
                }
            }
        }
        if( field.off_sections != null && field.off_sections.length > 0 ) {
            for(var i in field.off_sections) {
                if( v == 'off' ) {
                    this.sections[field.off_sections[i]].visible = 'yes';
                } else {
                    this.sections[field.off_sections[i]].visible = 'hidden';
                }
            }
        }
        if( field.reverse != null && field.reverse == 'yes' ) {
            var off = M.aE('span', this.panelUID + '_' + fid + sFN + '_off', 
                (v=='off'?'toggle_on':'toggle_off'), (field.off!=null&&field.off!=''?field.off:'Yes'));
            off.setAttribute('onclick', this.panelRef + '.setToggleField(this, \'' + i + sFN + '\',\'' + field.none + '\',\'' + field.fn + '\');' + updateFn + onchangeFn);
            var on = M.aE('span', this.panelUID + '_' + fid + sFN + '_on', 
                (v=='on'?'toggle_on':'toggle_off'), (field.on!=null&&field.on!=''?field.on:'No'));
            on.setAttribute('onclick', this.panelRef + '.setToggleField(this, \'' + i + sFN + '\',\'' + field.none + '\',\'' + field.fn + '\');' + updateFn + onchangeFn);
            div.appendChild(on);
            div.appendChild(off);
        } else {
            var off = M.aE('span', this.panelUID + '_' + fid + sFN + '_off', 
                (v=='off'?'toggle_on':'toggle_off'), (field.off!=null&&field.off!=''?field.off:'No'));
            off.setAttribute('onclick', this.panelRef + '.setToggleField(this, \'' + i + sFN + '\',\'' + field.none + '\',\'' + field.fn + '\');' + updateFn + onchangeFn);
            var on = M.aE('span', this.panelUID + '_' + fid + sFN + '_on', 
                (v=='on'?'toggle_on':'toggle_off'), (field.on!=null&&field.on!=''?field.on:'Yes'));
            on.setAttribute('onclick', this.panelRef + '.setToggleField(this, \'' + i + sFN + '\',\'' + field.none + '\',\'' + field.fn + '\');' + updateFn + onchangeFn);
            div.appendChild(off);
            div.appendChild(on);
        }
        c.appendChild(div)
    }
    else if( field.type == 'multiselect' ) {
        if( field.joined != null && field.joined == 'no' ) {
            c.className = 'multiselect';
        } else {
            c.className = 'joinedflags';
        }
        var div = M.aE('div', this.panelUID + '_' + fid + sFN);
        // The field value for this type should be a comma delimited list of values
        var v = this.fieldValue(s, i, field, mN);
        if( v != null && v != '' ) {
            var vs = v.split(',');
        } else {
            var vs = [];
        }
        var viewed = null;
        var deleted = null;
        if( field.viewed != null ) {
            var t = this.fieldValue(s, field.viewed, field, mN);
            viewed = t.split(',');
        }
        if( field.deleted != null ) {
            var t = this.fieldValue(s, field.deleted, field, mN);
            deleted = t.split(',');
        }
        for(j in field.options) {
            var f = M.aE('span', this.panelUID + '_' + fid + sFN + '_' + j);
            f.setAttribute('onfocus', this.panelRef + '.clearLiveSearches(\''+s+'\',\''+i+sFN+'\');');
            f.className = 'toggle_off';
            for(k in vs) {
                if( vs[k] == j ) { f.className = 'toggle_on'; }
            }
            f.innerHTML = '';
            if( viewed != null || deleted != null ) {
                if( deleted.indexOf(j) >= 0 ) {
                    f.innerHTML = '<span class="icon">V</span> ';
                } else if( viewed.indexOf(j) >= 0 ) {
                    f.innerHTML = '<span class="icon">v</span> ';
                } else {
                    f.innerHTML = '<span class="icon">U</span> ';
                }
            }
            f.innerHTML += field.options[j];
            f.setAttribute('onclick', this.panelRef + '.setSelectField(this, \'' + i + sFN + '\',\'' + field.none + '\',\'' + field.fn + '\');');
            div.appendChild(f);
        }
        c.appendChild(div);
        if( field.hint != null && field.hint != '' ) {
            c.appendChild(M.aE('span', this.panelUID + '_' + fid + sFN + '_hint', 'hint', field.hint));
        }
    }
    else if( field.type == 'idlist' ) {
        c.className = 'multiselect';
        var div = M.aE('div', this.panelUID + '_' + fid + sFN);
        var v = this.fieldValue(s, i, field, mN);
        if( typeof v == 'object' ) {
            vs = v;
        } else if( v != null && v != '' ) {
            vs = v.split(',');
        } else {
            vs = [];
        }
//        if( this.data['_' + fid] != null ) { var idlist = this.data['_' + fid]; } 
        if( field.list != null ) { var idlist = field.list; }
        var iname = (field.itemname!=null?field.itemname:'');
        for(j in idlist) {
            var f = M.aE('span', this.panelUID + '_' + fid + sFN + '_' + j);
            f.setAttribute('onfocus', this.panelRef + '.clearLiveSearches(\''+s+'\',\''+i+sFN+'\');');
            f.className = 'toggle_off';
            if( iname != '' ) {
                if( vs.indexOf(idlist[j][iname].id) >= 0 ) {
                    f.className = 'toggle_on';
                }
                f.innerHTML = idlist[j][iname].name;
            } else {
                if( vs.indexOf(idlist[j].id) >= 0 ) {
                    f.className = 'toggle_on';
                }
                f.innerHTML = idlist[j].name;
            }
            f.setAttribute('onclick', this.panelRef + '.setSelectField(this, \'' + i + sFN + '\',\'yes\',\'' + field.fn + '\');');
            if( idlist[j].hovertxt != null && idlist[j].hovertxt != '' ) {
                f.setAttribute('title', idlist[j].hovertxt);
            }
            div.appendChild(f);
        }
        c.appendChild(div);
    }
    else if( field.type == 'collection' ) {
        c.className = 'multiselect';
        var div = M.aE('div', this.panelUID + '_' + fid + sFN);
        var v = this.fieldValue(s, i, field, mN);
        if( v != null && v != '' ) {
            vs = v.split(',');
        } else {
            vs = [];
        }
        if( this.data['_' + fid] != null ) { var collections = this.data['_' + fid]; } 
        if( field.collections != null ) { var collections = field.collections; }
        for(j in collections) {
            var f = M.aE('span', this.panelUID + '_' + fid + sFN + '_' + j);
            f.setAttribute('onfocus', this.panelRef + '.clearLiveSearches(\''+s+'\',\''+i+sFN+'\');');
            f.className = 'toggle_off';
            if( vs.indexOf(collections[j].collection.id) >= 0 ) {
                f.className = 'toggle_on';
            }
            f.innerHTML = collections[j].collection.name;
            f.setAttribute('onclick', this.panelRef + '.setSelectField(this, \'' + i + sFN + '\',\'yes\',\'' + field.fn + '\');');
            div.appendChild(f);
        }
        c.appendChild(div);
    }
    else if( field.type == 'tags' ) {
        c.className = 'multiselect';
        var div = M.aE('div', this.panelUID + '_' + fid + sFN);
        // The field value for this type should be a comma delimited list of values
        var v = this.fieldValue(s, i, field, mN);
        if( v != null && v != '' ) {
            var vs = v.split(/::/);
        } else {
            var vs = [];
        }
        var tags = [];
        if( this.tags != null && this.tags[fid] != null ) {
            tags = this.tags[fid];
        } else if( field.tags != null ) {
            tags = field.tags;
        }
        tags.sort();
        for(j in tags) {
            var f = M.aE('span', this.panelUID + '_' + fid + sFN + '_' + j);
            f.setAttribute('onfocus', this.panelRef + '.clearLiveSearches(\''+s+'\',\''+fid+sFN+'\');');
            f.className = 'toggle_off';
            if( vs.indexOf(tags[j]) >= 0 ) {
                f.className = 'toggle_on';
            }
            f.innerHTML = tags[j];
            f.setAttribute('onclick', this.panelRef + '.setSelectField(this, \'' + i + sFN + '\',\'yes\',null);');
            div.appendChild(f);
        }
        //
        // Add the add button
        //
        var f = M.aE('span', this.panelUID + '_' + fid + sFN + '_addBtn', 'rbutton_off');
        f.innerHTML = 'a';
        f.setAttribute('onclick', this.panelRef + '.addSelectField(\''+i+'\',\''+(mN!=null?mN:'')+'\',\''+(field.hint!=null?field.hint:'Add')+'\');');
        div.appendChild(f);
        c.appendChild(div);

        // Create a small text field for adding new tags
//        var f = M.aE('input', this.panelUID + '_' + fid + sFN + '_new', 'text');
//        f.setAttribute('name', i + sFN + '_new');
//        if( field.hint != null && field.hint != '' ) {
//            f.setAttribute('placeholder', field.hint);
//        }
//        c.appendChild(f);    
    }
    else if( field.type == 'image' ) {
        var d = M.aE('div', this.panelUID + '_' + i + sFN + '_preview', 'image_preview');
        var img = this.fieldValue(s, i + sFN + '_img', field, mN);
        if( img != null && img != '' ) {
            d.innerHTML = img;
        }
        c.appendChild(d);
        // File upload doesn't work on ios and will break the field history button. :(
//        if( M.device != 'ipad' && M.device != 'iphone' ) {
            var f = M.aE('input', this.panelUID + '_' + i + sFN, 'file');
            f.setAttribute('name', i + sFN);
            f.setAttribute('onfocus', this.panelRef + '.clearLiveSearches(\''+s+'\',\''+i+sFN+'\');');
            f.setAttribute('type', 'file');
            c.appendChild(f);
//        }
    }
    else if( field.type == 'file' ) {
        // File upload doesn't work on ios and will break the field history button. :(
//        if( M.device != 'ipad' && M.device != 'iphone' ) {
            var f = M.aE('input', this.panelUID + '_' + i + sFN, 'file');
            f.setAttribute('onfocus', this.panelRef + '.clearLiveSearches(\''+s+'\',\''+i+sFN+'\');');
            f.setAttribute('name', i + sFN);
            f.setAttribute('type', 'file');
            c.appendChild(f);
//        }
    }
    else if( field.type == 'image_id' ) {
        var d = M.aE('div', this.panelUID + '_' + i + sFN + '_preview', 'image_preview');
        var img_id = this.fieldValue(s, i, field, mN);
        if( img_id != null && img_id != '' && img_id > 0 ) {
            if( field.size != null && field.size == 'large' ) {
                d.innerHTML = '<img src=\'' + M.api.getBinaryURL('ciniki.images.get', {'tnid':M.curTenantID, 'image_id':img_id, 'version':(field.version != null ? field.version : 'original'), 'maxwidth':'0', 'maxheight':'600'}) + '&ts=' + new Date().getTime() + '\' />';
            } else {
                d.innerHTML = '<img src=\'' + M.api.getBinaryURL('ciniki.images.get', {'tnid':M.curTenantID, 'image_id':img_id, 'version':(field.version != null ? field.version : 'original'), 'maxwidth':'0', 'maxheight':'300'}) + '&ts=' + new Date().getTime() + '\' />';
            }
        } else {
            d.innerHTML = '<img src=\'/ciniki-mods/core/ui/themes/default/img/noimage_200.jpg\' />';
        }
        c.appendChild(d);
        // File upload doesn't work on ios and will break the field history button. :(
//        if( M.device != 'ipad' && M.device != 'iphone' ) {
            var f = M.aE('input', this.panelUID + '_' + i + sFN, 'text');
            f.setAttribute('name', i + sFN);
            f.setAttribute('type', 'hidden');
            f.value = img_id;
            c.appendChild(f);
//        }
    }
    else if( field.type == 'audio_id' ) {
        var aid = this.fieldValue(s, i, field, mN);
//        var d = M.aE('input', this.panelUID + '_' + i + sFN + '_audio_filename', 'audio_filename medium');
//        d.value = this.fieldValue(s, i + '_filename', field, mN);
//        d.readOnly = true;
        var d = M.aE('span', this.panelUID + '_' + i + sFN + '_audio_filename', 'audio_filename');
        d.innerHTML = this.fieldValue(s, i + '_filename', field, mN);
        if( aid > 0 ) { d.style.display = 'inline-block'; }
        else { d.style.display = 'none'; }
        c.appendChild(d);
        if( (this.addDropFile != null || fields.addDropFile != null) && field.controls == 'all' ) {
            var btns = M.aE('div', this.panelUID + '_' + i + sFN + '_add_buttons', 'buttons');
            var btn = M.aE('span', null, 'toggle_off', 'Add Audio');
            btn.setAttribute('onclick', this.panelRef + '.uploadFile(\'' + i + '\');');
            if( aid > 0 ) {
                btns.style.display = 'none';
            } else {
                btns.style.display = 'inline-block';
            }
            btns.appendChild(btn);
            c.appendChild(btns);

            var btns = M.aE('div', this.panelUID + '_' + i + sFN + '_edit_buttons', 'buttons');
            var btn = M.aE('span', null, 'toggle_off', 'Change Audio');
            if( aid > 0 ) {
                btns.style.display = 'inline-block';
            } else {
                btns.style.display = 'none';
            }
            btn.setAttribute('onclick', this.panelRef + '.uploadFile(\'' + i + '\');');
            btns.appendChild(btn);

            if( field.deleteFile != null ) {
                var btn = M.aE('span', null, 'toggle_off', 'Delete');
                btn.setAttribute('onclick', field.deleteFile + '(\'' + i + '\');');
                btns.appendChild(btn);
            } else if( this.deleteFile != null ) {
                var btn = M.aE('span', null, 'toggle_off', 'Delete');
                btn.setAttribute('onclick', this.panelRef + '.deleteFile(\'' + i + '\');');
                btns.appendChild(btn);
            }
            c.appendChild(btns);
            //
            // Create the form upload field, but hide it
            //
            f = M.aE('input', this.panelUID + '_' + i + '_upload', 'file_uploader');
            f.setAttribute('name', i);
            f.setAttribute('type', 'file');
            f.setAttribute('onchange', this.panelRef + '.uploadDropFiles(\'' + i + '\');');
            f.setAttribute('onfocus', this.panelRef + '.clearLiveSearches(\''+s+'\',\''+i+'\');');
            c.appendChild(f);
        }
        var d = M.aE('audio', this.panelUID + '_' + i + sFN + '_audio', 'audio_controls');
        c.className = 'audiocontrols';
        d.controls=true;
        if( aid != null && aid != '' && aid > 0 ) {
            d.style.display = 'inline-block';
            d.src = M.api.getBinaryURL('ciniki.audio.download', {'tnid':M.curTenantID, 'audio_id':aid}) + '&ts=' + new Date().getTime();
        } else {
            d.style.display = 'none';
        }
        c.appendChild(d);
        var f = M.aE('input', this.panelUID + '_' + i + sFN, 'text');
        f.setAttribute('name', i + sFN);
        f.setAttribute('type', 'hidden');
        f.value = aid;
        c.appendChild(f);
    }
    else if( field.type == 'noedit' ) {
        c.className = 'noedit';
        c.setAttribute('id', this.panelUID + '_' + i + sFN);
        c.setAttribute('name', i + sFN);
//        f.setAttribute('onfocus', this.panelRef + '.clearLiveSearches(\''+s+'\',\''+i+sFN+'\');');
        c.setAttribute('type', field.type);
        if( this.fieldValue != null ) {
            c.innerHTML = this.fieldValue(s, i, field, mN);    
        } else {
            c.innerHTML = '';
        }
    }

    return c;
};

M.panel.prototype.updateFlagToggleFields = function(fid) {
    var f = this.formField(fid);

    var v = this.formValue(fid);
    if( f.on_fields != null && f.on_fields.length > 0 ) {
        for(var i in f.on_fields) {
            var field = this.formField(f.on_fields[i]);
            var e = M.gE(this.panelUID + '_' + f.on_fields[i]);
            if( e == null ) { continue; }
            e = e.parentNode.parentNode;
            if( v == 'on' ) {
                if( field != null ) {
                    field.visible = 'yes';
                }
                e.style.display = 'table-row';
                if( e.previousSibling != null && e.previousSibling.className.match(/guided-title/) ) {
                    e.previousSibling.style.display = '';
                }
                if( e.nextSibling != null && e.nextSibling.className.match(/guided-text/) ) {
                    e.nextSibling.style.display = '';
                }
            } else {
                if( field != null ) {
                    field.visible = 'no';
                }
                e.style.display = 'none';
                if( e.previousSibling != null && e.previousSibling.className.match(/guided-title/) ) {
                    e.previousSibling.style.display = 'none';
                }
                if( e.nextSibling != null && e.nextSibling.className.match(/guided-text/) ) {
                    e.nextSibling.style.display = 'none';
                }
            }
        }
    }
    if( f.off_fields != null && f.off_fields.length > 0 ) {
        for(var i in f.off_fields) {
            var field = this.formField(f.off_fields[i]);
            var s = this.formFieldSection(i);
            var e = M.gE(this.panelUID + '_' + f.off_fields[i]);
            if( e == null ) { continue; }
            e = e.parentNode.parentNode;
            if( v == 'off' ) {
                if( field != null ) {
                    field.visible = 'yes';
                }
                e.style.display = 'table-row';
                if( e.previousSibling != null && e.previousSibling.className.match(/guided-title/) ) {
                    e.previousSibling.style.display = '';
                }
                if( e.nextSibling != null && e.nextSibling.className.match(/guided-text/) ) {
                    e.nextSibling.style.display = '';
                }
            } else {
                if( field != null ) {
                    field.visible = 'no';
                }
                e.style.display = 'none';
                if( e.previousSibling != null && e.previousSibling.className.match(/guided-title/) ) {
                    e.previousSibling.style.display = 'none';
                }
                if( e.nextSibling != null && e.nextSibling.className.match(/guided-text/) ) {
                    e.nextSibling.style.display = 'none';
                }
            }
        }
    }
    if( f.on_sections != null && f.on_sections.length > 0 ) {
        for(var i in f.on_sections) {
            var e = M.gE(this.panelUID + '_section_' + f.on_sections[i]);
            if( e == null ) { continue; }
            if( v == 'on' ) {
                e.style.display = '';
            } else {
                e.style.display = 'none';
            }
        }
    }
    if( f.off_sections != null && f.off_sections.length > 0 ) {
        for(var i in f.off_sections) {
            var e = M.gE(this.panelUID + '_section_' + f.off_sections[i]);
            if( e == null ) { continue; }
            if( v == 'off' ) {
                e.style.display = '';
            } else {
                e.style.display = 'none';
            }
        }
    }
    M.resize();
};

M.panel.prototype.updateImgPreview = function(fid, img_id) {
    var f = this.formField(fid);
    var d = M.gE(this.panelUID + '_' + fid + '_preview');
    if( img_id != null && img_id != '' ) {
        if( f != null && f.size == 'large' ) {
            d.innerHTML = '<img src=\'' + M.api.getBinaryURL('ciniki.images.get', {'tnid':M.curTenantID, 'image_id':img_id, 'version':'original', 'maxwidth':'0', 'maxheight':'600'}) + '&ts=' + new Date().getTime() + '\' />';
        } else {
            d.innerHTML = '<img src=\'' + M.api.getBinaryURL('ciniki.images.get', {'tnid':M.curTenantID, 'image_id':img_id, 'version':'original', 'maxwidth':'0', 'maxheight':'300'}) + '&ts=' + new Date().getTime() + '\' />';
        }
    } else {
        d.innerHTML = '<img src=\'/ciniki-mods/core/ui/themes/default/img/noimage_200.jpg\' />';
    }
    var btns = this.createImageControls(fid, this.formField(fid), img_id);
    var c = M.gE(this.panelUID + '_' + fid + '_controls');
    M.clr(c);
    c.appendChild(btns);
};

M.panel.prototype.updateAudioPreview = function(fid, aid) {
    var d = M.gE(this.panelUID + '_' + fid + '_audio');
    var abtns = M.gE(this.panelUID + '_' + fid + '_add_buttons');
    var ebtns = M.gE(this.panelUID + '_' + fid + '_edit_buttons');
    var filename = M.gE(this.panelUID + '_' + fid + '_audio_filename');
    if( aid != null && aid != '' && aid > 0 ) {
        d.style.display = 'inline-block';
        abtns.style.display = 'none';
        ebtns.style.display = 'inline-block';
        filename.style.display = 'inline-block';
        d.src = M.api.getBinaryURL('ciniki.audio.download', {'tnid':M.curTenantID, 'audio_id':aid}) + '&ts=' + new Date().getTime();
    } else {
        abtns.style.display = 'inline-block';
        ebtns.style.display = 'none';
        filename.style.display = 'none';
        d.style.display = 'none';
        d.src = '';

    }
};

M.panel.prototype.setToggleField = function(e, i, noval, fn) {
    if( e.className == 'toggle_off' ) {
        for(k in e.parentNode.children) {
            e.parentNode.children[k].className = 'toggle_off';
        }
        e.className = 'toggle_on';
    } else if( noval != null && noval == 'yes' && e.className == 'toggle_on' ) {
        e.className = 'toggle_off';
    }
    if( fn != null && fn != 'undefined' && fn != 'null' ) {
        // fn(i, e.className);
        eval(fn + '(\'' + i + '\',\'' + e.innerHTML + '\',\'' + e.className + '\')');
    }
};

M.panel.prototype.setSelectField = function(e, i, noval, fn) {
    if( e.className == 'toggle_off' ) {
        e.className = 'toggle_on';
    } else if( noval != null && noval == 'yes' && e.className == 'toggle_on' ) {
        e.className = 'toggle_off';
    }
    if( fn != null && fn != 'undefined' && fn != 'null' ) {
        eval(fn + '(\'' + i + '\',\'' + e.innerHTML + '\',\'' + e.className + '\')');
    }
};

// M.panel.prototype.setFieldValue = function(field, v, vnum, hide, nM, action) {
M.panel.prototype.addSelectField = function(i, nM, txt) {
    var tag = prompt(((txt!=null&&txt!='')?txt:'Add'));
    if( tag != null && tag != '' ) {
        this.setFieldValue(i,tag,0,0,nM,1);
    }
};

//
// This function will set the current form field value to the value
// that was in the history for the field
//
// Arguments:
// field - the field id
// value - the array index to the history for the field
//
M.panel.prototype.setFromFieldHistory = function(field, history_field, value) {
    //var f = this.formField(field);
    //
    // Set the value selected by the user from the field history
    //
    var v = this.fieldHistories[history_field]['history'][value]['action']['value'];
    if( this.fieldHistories[history_field].history[value].action.action != null ) {
        this.setFieldValue(field, v, value, 0, null, this.fieldHistories[history_field].history[value].action.action);
    } else {
        this.setFieldValue(field, v, value, 0);
    }

    //
    // Remove the history
    //
    this.removeFormFieldHistory(history_field);
};

M.panel.prototype.setFromButton = function(e, field, v) {
    var f = this.formField(field);
    if( f.type == 'timeduration' ) {
        var x = this.formFieldValue(f, field);
        if( v[0] == '+' || v[0] == '-') {
            x = parseInt(x) + parseInt(v);
            // Check for a minimum value
            if( f.min != null && x < f.min ) { x = f.min; }
        }
        if( f.allday == 'yes' && v == 'allday' ) {
            var dt = null;
            var dtf = null;
            var dtv = null;
            if( f.date != null ) {
                dt = this.formField(f.date);
                dtf = this.formFieldValue(dt, f.date);
                dtv = M.parseDate(dtf); // this.parseDate(dtf);
            }
            if( v == 'allday' && e.className == 'toggle_off' ) {
                // Save the old value first
                M.gE(this.panelUID + '_' + field).oldvalue = x;
                x = 1440;
                this.setFieldValue(field, x, null, 1);
                e.className = 'toggle_on';
                e.parentNode.className = 'buttons nopadbuttons';
                if( dt != null ) {
                    // Save the old time
                    M.gE(this.panelUID + '_' + f.date).oldtime = dtv.time;
                    if( dtf != '' ) {
                        this.setFieldValue(f.date, M.dateFormat(dtv.year + '-' + dtv.month + '-' + dtv.day), null, 0);
                    }
                }
            } else {
                if( e.className == 'toggle_on' ) {
                    // Recover saved value
                    x = M.gE(this.panelUID + '_' + field).oldvalue;
                    if( x == null ) {
                        x = 60;
                    }
                }
                this.setFieldValue(field, x, null, 2);
                e.className = 'toggle_off';
                e.parentNode.className = 'buttons';
                if( dt != null ) {
                    // Recover the saved time
                    ot = M.gE(this.panelUID + '_' + f.date).oldtime;
                    if( dtf != '' ) {
                        this.setFieldValue(f.date, M.dateFormat(dtv.year + '-' + dtv.month + '-' + dtv.day) + ' ' + ot, null, 0);
                    }
                }
            }
        } else {
            this.setFieldValue(field, x, null, 0);
        }
    }
};

M.panel.prototype.setFieldValue = function(field, v, vnum, hide, nM, action) {
    var f = this.formField(field);

    var sFN = '';
    if( nM != null && nM != '' ) {
        sFN = '_'+nM;
    }

    if( f.type == 'select' ) {
        //
        // Loop through the form field options, and decide which to select
        // FIXME: Allow for complex_options
        //
        var s = M.gE(this.panelUID + '_' + field + sFN);
        for(var i=0;i<s.options.length;i++) {
            if( s.options[i].value == v) {
                s.selectedIndex = i;
            }
        }
    } else if( f.type == 'flags' ) {
        if( typeof v == 'string' ) { 
            v = parseInt(v, 10);
        }
        var vhi = v.toString(16);
        var vlo = vhi.substr(-8);
        vhi = vhi.length > 8 ? vhi.substr(0, vhi.length - 8) : '';
        vlo = parseInt(vlo, 16);
        vhi = parseInt(vhi, 16);
        for(j in f.flags) {
            if( f.flags[j] == null ) { continue; }
            if( f.flags[j].active != null && f.flags[j].active == 'no' ) { continue; }
            var e = M.gE(this.panelUID + '_' + field + sFN + '_' + j);
//            if( (v&Math.pow(2, j-1)) == Math.pow(2,j-1) ) {
            var bit_value = j > 32 ? (vhi>>(j-33)&0x01) : (vlo>>(j-1)&0x01);
            if( bit_value == 1 ) {
                // Turn off all other options
                if( f.toggle == 'yes' ) {
                    for(k in e.parentNode.children) {
                        e.parentNode.children[k].className = 'flag_off';
                    }
                }
                e.className = 'flag_on';
            } else {
                e.className = 'flag_off';
            }
        }
    } else if( f.type == 'multitoggle' || f.type == 'toggle' ) {
        for(j in f.toggles) {
            var e = M.gE(this.panelUID + '_' + field + sFN + '_' + j);
            if( v == j ) {
                e.className = 'toggle_on';
            } else {
                e.className = 'toggle_off';
            }
        }
    } else if( f.type == 'flagtoggle' ) {
        if( (v&f.bit) == f.bit ) {
            M.gE(this.panelUID + '_' + field + sFN + '_off').className = 'toggle_off';
            M.gE(this.panelUID + '_' + field + sFN + '_on').className = 'toggle_on';
        } else {
            M.gE(this.panelUID + '_' + field + sFN + '_off').className = 'toggle_on';
            M.gE(this.panelUID + '_' + field + sFN + '_on').className = 'toggle_off';
        }
    } else if( f.type == 'multiselect' ) {
        if( v != '' ) {
            var vs = v.split(',');
        } else {
            var vs = [];
        }
        for(j in f.options) {
            var e = M.gE(this.panelUID + '_' + field + sFN + '_' + j);
            if( vs[j] != null ) {
                e.className = 'toggle_on';
            } else {
                e.className = 'toggle_off';
            }
        }
    } else if( f.type == 'tags' ) {
        if( action != null ) {
            // Check if tags are stored with field or with form
            if( this.tags != null && this.tags[field] != null ) {
                var tags = this.tags[field];
            } else {
                var tags = f.tags;
            }
            if( action == 1 ) {
                var upd = 0;
                // Check if added tag doesn't exist
                j = tags.indexOf(v);
                if( j < 0 ) {
                    // Save the existing settings
                    var ev = this.formFieldValue(f, field);
                    for(k in tags) {
                        upd = 0;
                        // Insert tag into list
                        if( tags[k] > v ) {
                            tags.splice(k, 0, v);
                            upd = 1;
                            break;
                        }
                    }
                    if( upd == 0 ) {
                        tags.push(v);
                        upd = 1;
                    }
                } else {
                    var e = M.gE(this.panelUID + '_' + field + sFN + '_' + j);
                    e.className = 'toggle_on';
                }
                if( upd == 1 ) {
                    if( ev != null && ev != '' ) {
                        var vs = ev.split(/::/);
                    } else {
                        var vs = [];
                    }
                    var div = M.gE(this.panelUID + '_' + field + sFN);
                    M.clr(div);
                    tags.sort();
                    for(j in tags) {
                        var e = M.aE('span', this.panelUID + '_' + field + sFN + '_' + j);
                        // set on if already on or added
                        if( vs.indexOf(tags[j]) >= 0 || tags[j] == v ) {
                            e.className = 'toggle_on';
                        } else {
                            e.className = 'toggle_off';
                        }
                        e.innerHTML = tags[j];
                        e.setAttribute('onclick', this.panelRef + '.setSelectField(this, \'' + field + sFN + '\',\'yes\',null);');
                        div.appendChild(e);
                    }
                    // Add the add button
                    var s = M.aE('span', this.panelUID + '_' + field + sFN + '_addBtn', 'rbutton_off');
                    s.innerHTML = 'a';
                    s.setAttribute('onclick', this.panelRef + '.addSelectField(\''+field+'\',\''+(nM!=null?nM:'')+'\',\''+(f.hint!=null?f.hint:'Add')+'\');');
                    div.appendChild(s);
                }
            } else if( action == 3 ) {
                j = tags.indexOf(v);
                if( j >= 0 ) {
                    var e = M.gE(this.panelUID + '_' + field + sFN + '_' + j);
                    e.className = 'toggle_off';
                }
            }
        } else {    
            // Code to update tags based on :: delimited list
            if( v != '' ) {
                var vs = v.split(/::/);
            } else {
                var vs = [];
            }
            // Add missing tags
            var upd = 0;
            for(j in vs) {
                if( f.tags.indexOf(vs[j]) < 0 ) {
                    for(k in f.tags) {
                        upd = 0;
                        // Insert tag into list
                        if( f.tags[k] > vs[j] ) {
                            f.tags.splice(k, 0, vs[j]);
                            upd = 1;
                            break;
                        }
                    }
                    if( upd == 0 ) {
                        f.tags.push(vs[j]);
                        upd = 1;
                    }
                }
            }
            // Redraw the tags list
            if( upd == 1 ) {
                var div = M.gE(this.panelUID + '_' + field + sFN);
                M.clr(div);
                f.tags.sort();
                for(j in f.tags) {
                    var s = M.aE('span', this.panelUID + '_' + field + sFN + '_' + j);
                    s.className = 'toggle_off';
                    if( vs.indexOf(f.tags[j]) >= 0 ) {
                        s.className = 'toggle_on';
                    }
                    s.innerHTML = f.tags[j];
                    s.setAttribute('onclick', this.panelRef + '.setSelectField(this, \'' + field + sFN + '\',\'yes\',null);');
                    div.appendChild(s);
                }
                // Add the add button
                var s = M.aE('span', this.panelUID + '_' + field + sFN + '_addBtn', 'rbutton_off');
                s.innerHTML = 'a';
                s.setAttribute('onclick', this.panelRef + '.addSelectField(\''+field+'\',\''+(nM!=null?nM:'')+'\',\''+(f.hint!=null?f.hint:'Add')+'\');');
                div.appendChild(s);
            } else {
                // Toggle existing tags
                for(j in f.tags) {
                    var e = M.gE(this.panelUID + '_' + field + sFN + '_' + j);
                    if( vs.indexOf(f.tags[j]) >= 0 ) {
                        e.className = 'toggle_on';
                    } else {
                        e.className = 'toggle_off';
                    }
                }
            }
        }
    } else if( f.type == 'colourswatches' ) {
        M.setColourSwatchField(this.panelUID + '_' + field + sFN, v);
    } else if( f.type == 'date' || f.type == 'datetime' || f.type == 'appointment' ) {
        if( vnum != null && this.fieldHistories[field].history[vnum].action.formatted_value != null ) {
            M.gE(this.panelUID + '_' + field + sFN).value = this.fieldHistories[field].history[vnum].action.formatted_value;
        } else {
            M.gE(this.panelUID + '_' + field + sFN).value = v;
        }
    } else if( f.type == 'colour' ) {
        M.gE(this.panelUID + '_' + field).style.backgroundColor = v;
    } else if( f.type == 'timeduration' ) {
        if( typeof v == 'number' && v > 60 ) {
            v = Math.floor(v/60) + ':' + (((v%60)<10)?'0':'') + (v%60);
        }
        M.gE(this.panelUID + '_' + field + sFN).value = v;
    } else if( f.type == 'image_id' ) {
        M.gE(this.panelUID + '_' + field + sFN).value = v;
        this.updateImgPreview(field + sFN, v);
    } else if( f.type == 'audio_id' ) {
        M.gE(this.panelUID + '_' + field + sFN).value = v;
        this.updateAudioPreview(field + sFN, v);
    } else if( f.type == 'htmlarea' ) {
        tinymce.get(this.panelUID + '_' + field + sFN).setContent(v.replace(/\n/g,"<br />"));
    } else {
        //
        // If not a special type, then set the input field value to the
        // 
        M.gE(this.panelUID + '_' + field + sFN).value = v;
    }

    if( f.type == 'fkid' && this.fieldHistories[field] != null ) {
        var v2 = this.fieldHistories[field].history[vnum].action.fkidstr_value;
        M.gE(this.panelUID + '_' + field + sFN + '_fkidstr').value = v2;
    }

    if( hide == 1 ) {
        if( f.type == 'timeduration' && f.allday == 'yes' ) {
            M.gE(this.panelUID + '_' + field + sFN).style.display = 'none';
            M.gE(this.panelUID + '_' + field + sFN + '_buttons').style.display = 'none';
        } else {
            M.gE(this.panelUID + '_' + field + sFN).parentNode.parentNode.style.display = 'none';
        }
    } if( hide == 2 ) {
        if( f.type == 'timeduration' && f.allday == 'yes' ) {
            M.gE(this.panelUID + '_' + field + sFN).style.display = 'inline-block';
            M.gE(this.panelUID + '_' + field + sFN + '_buttons').style.display = 'inline-block';
        } else {
            M.gE(this.panelUID + '_' + field + sFN).parentNode.parentNode.style.display = 'table-row';
        }
    }
};

//
// Field history should be removed and not hidden, so next update will
// fetch  new values from database
//
M.panel.prototype.removeFormFieldHistory = function(field) {
    var h = M.gE(this.panelUID + '_' + field + '_history');
    //
    // Toggle image
    //
    //h.previousSibling.children[h.previousSibling.children.length-1].children[0].src ='' + M.themes_root_url + '/default/img/historyA.png';
    var r = h.previousSibling.getElementsByClassName('historybutton');
    r[0].children[0].className = 'rbutton_off';


//    h.previousSibling.children[h.previousSibling.children.length-1].children[0].className = 'rbutton_off';

    //
    // Remove element, and delete the history object to save memory
    //
    h.parentNode.removeChild(h);
    delete(this.fieldHistories[field]);
};

//
// Remove the colour picker.
//
M.panel.prototype.removeFormColourPicker = function(field) {
    var h = M.gE(this.panelUID + '_' + field + '_colourpicker');
    h.parentNode.removeChild(h);
};

//
// Field history should be removed and not hidden, so next update will
// fetch  new values from database
//
M.panel.prototype.removeLiveSearch = function(s, f) {
    if( f != null ) { s += '_' + f; }
    var h = M.gE(this.panelUID + '_' + s + '_livesearch_grid').parentNode.parentNode;
    h.parentNode.removeChild(h);
    this.liveSearchTables[this.panelUID + '_' + s + '_livesearch_grid'] = 'off';
};

M.panel.prototype.clearLiveSearches = function(s, f) {
    if( f != null ) { s += '_' + f; }
    var sid = this.panelUID + '_' + s + '_livesearch_grid';
    for(i in this.liveSearchTables) {
        if( this.liveSearchTables[i] == 'on' && i != sid ) {
            var h = M.gE(i);
            if( h != null && h.parentNode != null && h.parentNode.parentNode != null ) { 
                h = h.parentNode.parentNode;
                h.parentNode.removeChild(h); 
            }
            this.liveSearchTables[i] = 'off';
        }
    }
};

//
// Field history should be removed and not hidden, so next update will
// fetch  new values from database
//
M.panel.prototype.removeFormFieldCalendar = function(field) {
    var h = M.gE(this.panelUID + '_' + field + '_calendar');
    //
    // Toggle image
    //
    // h.previousSibling.children[h.previousSibling.children.length-1].innerHTML = '<img name=\'calendar\' src=\'' + M.themes_root_url + '/default/img/calendarA.png\' />';

    //
    // Remove element, and delete the history object to save memory
    //
    h.parentNode.removeChild(h);
};

//
// Field history should be removed and not hidden, so next update will
// fetch  new values from database
//
M.panel.prototype.removeFormFieldAppointment = function(field) {
    var h = M.gE(this.panelUID + '_' + field + '_appointment');
    //
    // Toggle image
    //
    // h.previousSibling.children[h.previousSibling.children.length-1].innerHTML = '<img name=\'calendar\' src=\'' + M.themes_root_url + '/default/img/calendarA.png\' />';

    //
    // Remove element, and delete the history object to save memory
    //
    h.parentNode.removeChild(h);
};

//
// This function will select a field from a sectioned form and return the field object.
//
M.panel.prototype.formField = function(field) {
    var e = 0;
    for(e in this.sections) {
        var s = this.sections[e];
        if( s.type != null && s.type == 'gridform' ) {
            for(j in s.fields) {
                for(k in s.fields[j]) {
                    if( s.fields[j][k]['id'] == field ) {
                        return s.fields[j][k];
                    }
                }
            }
        } else if( s.fields != null && s.fields[field] != null ) {
            return s.fields[field];
        } else {
            for(j in s.fields) {
                if( s.fields[j].id != null && s.fields[j].id == field ) {
                    return s.fields[j];
                }
            }
        }
    }
    return null;
};

//   
// This function will select a field from a sectioned form and return the field object.
//   
M.panel.prototype.formFieldSection = function(field) {
    var e = 0; 
    for(e in this.sections) {
        var sc = this.sections[e];
        if( sc.type != null && sc.type == 'gridform' ) {
            for(j in sc.fields) {
                for(k in sc.fields[j]) {
                    if( sc.fields[j][k].id == field ) {
                        return e;
                    }    
                }    
            }    
        } else if( sc.fields != null && sc.fields[field] ) {
            return e;
        }    
    }    
    return null;
}    

//
// This function will check for an existing field history, and remove it,
// or add a new one 
//
M.panel.prototype.toggleFormGridHistory = function(s, row) {
    var field = section + '_' + row;
    var h = M.gE(this.panelUID + '_' + field + '_history');
    if( h != null ) {
        this.removeFormFieldHistory(field);
    } else {
        //
        // Issue callback to get the history for this field
        //
        this.fieldHistories[field] = this.fieldHistory(s,field);

        //
        // Send the first field, assume all fields are the same type
        //
        this.setupFormFieldHistory(field, this.sections[section].fields[row][0]);
        
        //
        // Toggle the image
        //
        var h = M.gE(this.panelUID + '_' + field + '_history');
        h.previousSibling.children[h.previousSibling.children.length-1].children[0].className = 'rbutton_on';
        //h.previousSibling.children[h.previousSibling.children.length-1].children[0].src = '' + M.themes_root_url + '/default/img/historyB.png';
    }
};

//
// This function will check for an existing field history, and remove it,
// or add a new one 
//
M.panel.prototype.toggleFormFieldHistory = function(e, s, field) {
    e.stopPropagation();
    var h = M.gE(this.panelUID + '_' + field + '_history');
    if( h != null ) {
        this.removeFormFieldHistory(field);
    } else {
        //
        // Issue callback to get the history for this field
        //
        if( e.target.nodeName == 'SPAN' ) {
            e.target.className = 'rbutton_on';
        } else {
            e.target.firstChild.className = 'rbutton_on';
        }
        if( this.fieldHistoryArgs != null ) {
            var r = this.fieldHistoryArgs(s,field);
            var p = this;
            M.api.getJSONCb(r.method, r.args, function(r) {
                if( r.stat != 'ok' ) {
                    return false;
                }
                p.fieldHistories[field] = r;
                //
                // Setup the history on the screen.  This function is size dependent
                //
                p.setupFormFieldHistory(field, p.formField(field));
                
                //
                // Toggle the image
                //
//                var h = M.gE(p.panelUID + '_' + field + '_history');
//                h.previousSibling.children[h.previousSibling.children.length-1].children[0].className = 'rbutton_on';
            });
        } else {
            this.fieldHistories[field] = this.fieldHistory(s,field);

            //
            // Setup the history on the screen.  This function is size dependent
            //
            this.setupFormFieldHistory(field, this.formField(field));
            
            //
            // Toggle the image
            //
//            var h = M.gE(this.panelUID + '_' + field + '_history');
//            h.previousSibling.children[h.previousSibling.children.length-1].children[0].className = 'rbutton_on';
            // h.previousSibling.children[h.previousSibling.children.length-1].innerHTML = '<img name=\'history\' src=\'' + M.themes_root_url + '/default/img/historyB.png\' />';
            }
    }
};

//
// Display a colour picker, with a list of currently used colour swatches
//
M.panel.prototype.toggleFormColourPicker = function(field) {
    var h = M.gE(this.panelUID + '_' + field + '_colourpicker');
    if( h != null ) {
        this.removeFormColourPicker(field);
    } else {
        var v = this.formValue(field);
        if( v == null || v == '' ) {
            v = '#ffffff';    
        }
        //
        // Setup the div containers
        //
        var fD = M.gE(this.panelUID + '_' + field).parentNode.parentNode;
        var hD = M.aE('tr', this.panelUID + '_' + field + '_colourpicker', 'fieldcolourpicker');
        var hC = M.aE('td', null, 'colourpicker');

        hC.colSpan = fD.children.length;
        hD.appendChild(hC);
        fD.parentNode.insertBefore(hD, fD.nextSibling);

        // Check through the form section for all existing colours
        var colours = {};
        var s = this.sections[this.formFieldSection(field)];
        if( s.type != null && s.type == 'gridform' ) {
            for(j in s.fields) {
                for(k in s.fields[j]) {
                    if( s.fields[j][k].type == 'colour' ) {
                        colours[this.formFieldValue(s.fields[j][k], s.fields[j][k]['id'])] = 1;
                    }
                }
            }
        } else if( s.fields != null ) {
            for(i in s.fields) {
                if( s.fields[i].type == 'colour' ) {
                    colours[this.formFieldValue(s.fields[i], i)] = 1;
                }
            }
        }

        var d1 = M.aE('div',null,'colours');
        var t = M.aE('table', null, 'colours');
        t.setAttribute('cellspacing', '0');
        t.setAttribute('cellpadding', '0');
        var tb = M.aE('tbody');
        var tr = M.aE('tr');
        var td = M.aE('td');
        for(i in colours) {
            var c = M.aE('span', null, 'colourswatch', '&nbsp;');
            c.setAttribute('onclick', this.panelRef + '.setColourField(\'' + field + '\',\'' + i + '\');');
            td.appendChild(c);
            c.style.background = i;
        }
        tr.appendChild(td);
        tb.appendChild(tr);
        t.appendChild(tb);
        d1.appendChild(t);

        hC.appendChild(d1);

        
        var d2 = M.aE('div',null,'colourpicker');

        var t = M.aE('table', null, 'colourpicker');
        t.setAttribute('cellspacing', '0');
        t.setAttribute('cellpadding', '0');
        var tb = M.aE('tbody');
        var tr = M.aE('tr');
        var td = M.aE('td');
        var s = M.aE('span', this.panelUID + '_' + field + '_pickedcolour', 'colourswatch', '&nbsp;');
        s.style.background = v;
        s.style.background = '#000055';
        s.setAttribute('onclick', this.panelRef + '.setColourField(\'' + field + '\',M.rgbToHex(this.style.backgroundColor));');
        td.appendChild(s);
        tr.appendChild(td);

        td = M.aE('td');
        var colourpicker = new Color.Picker({element: td, hexBox: s, margin: 10,
            callback: function(hex) {
                // this.element.parentNode.children[0].style.background = '#' + hex;
                // M.gE(this.panelUID + '_' + field + '_pickedcolour').style.background = hex;
                // this.panelRef + '.setColourField(\'' + field + '\',hex\')';
            }
        });
        tr.appendChild(td);

        tb.appendChild(tr);
        t.appendChild(tb);
        d2.appendChild(t);
        hC.appendChild(d2);
    }
};

M.panel.prototype.setColourField = function(field, value) {
    var d = M.gE(this.panelUID + '_' + field);
    d.style.background = value;
    this.removeFormColourPicker(field);
};

//
// This will display a calendar for the user to pick from for a date
//
M.panel.prototype.toggleFormFieldCalendar = function(field) {
    var h = M.gE(this.panelUID + '_' + field + '_calendar');
    if( h != null ) {
        this.removeFormFieldCalendar(field);
    } else {
        var v = this.formValue(field);
        if( v == null || v == '' ) {
            v = 'now';    
        }
        var f = this.formField(field);
        if( f.type == 'date' ) {
            v = M.parseDate(v); // this.parseDate(v);
        } else if( f.type == 'datetime' || f.type == 'appointment' ) {
            v = M.parseDate(v); // this.parseDate(v);
        } else {
            return false;
        }
        if( v == null || v.year == null || v.month == null ) {
            v = M.parseDate('now'); // this.parseDate('now');
        }
        //
        // Setup the div containers
        //
        var fD = M.gE(this.panelUID + '_' + field).parentNode.parentNode;
        var hD = M.aE('tr', this.panelUID + '_' + field + '_calendar', 'fieldcalendar');
        var hC = M.aE('td', null, 'calendar');
        
        hC.colSpan = fD.children.length;

        hD.appendChild(hC);
        fD.parentNode.insertBefore(hD, fD.nextSibling);

        if( this.formValue(field) != '' ) {
            this.showFieldCalendars(field, Number(v.year), Number(v.month)-1, v, 'calendar', (f.fn!=null?f.fn:null), v.time);
        } else {
            this.showFieldCalendars(field, Number(v.year), Number(v.month)-1, {'year':'', 'month':'', 'day':'', 'hour':'', 'minute':''}, 'calendar', (f.fn!=null?f.fn:null), v.time);
        }
    }
};

M.panel.prototype.toggleFormFieldAppointment = function(field) {
    var h = M.gE(this.panelUID + '_' + field + '_appointment');
    if( h != null ) {
        this.removeFormFieldAppointment(field);
    } else {
        var v = this.formValue(field);
        if( v == null || v == '' ) {
            v = 'now';    
        }
        var f = this.formField(field);
        f.newselection_year = null;
        f.newselection_month = null;
        f.newselection_day = null;
        if( f.type == 'date' ) {
            v = M.parseDate(v); // this.parseDate(v);
        } else if( f.type == 'datetime' || f.type == 'appointment' ) {
            v = M.parseDate(v); // this.parseDate(v);
        } else {
            return false;
        }
        //
        // Setup the div containers
        //
        var fD = M.gE(this.panelUID + '_' + field).parentNode.parentNode;
        var hD = M.aE('tr', this.panelUID + '_' + field + '_appointment', 'fieldcalendar');
        var hC = M.aE('td', null, 'calendar');
        
        hC.colSpan = fD.children.length;

        hD.appendChild(hC);
        fD.parentNode.insertBefore(hD, fD.nextSibling);

        if( this.formValue(field) != '' ) {
            this.showFieldCalendars(field, Number(v.year), Number(v.month)-1, v, 'appointment', null, v.time);
        } else {
            this.showFieldCalendars(field, Number(v.year), Number(v.month)-1, {'year':'', 'month':'', 'day':'', 'hour':'', 'minute':''}, 'appointment', null, v.time);
        }
    }
};

//
// field - the id of the field
// start_year - where to start the calendar from
// start_month - where to start the calendar from
// cur_date - the current parsed date value from the form field
//
M.panel.prototype.showFieldCalendars = function(field, start_year, start_month, cur_date, fieldtype, fn, stime) {
    if( fieldtype != null ) {
        var h = M.gE(this.panelUID + '_' + field + '_' + fieldtype);
    } else {
        var h = M.gE(this.panelUID + '_' + field + '_calendar');
    }
    var hC = h.children[0];
    M.clr(hC);
    var num_months = 3;
    var cur_month = start_month;
    var cur_year = start_year;
    if( start_month == 13 ) {    
        start_month = 1;
        cur_month = start_month;
        start_year = start_year + 1;
        cur_year = start_year;
    } else if ( start_month == 0 ) {
        start_month = 12;
        cur_month = start_month;
        start_year = start_year - 1;
        cur_year = start_year;
    }


    var f = this.formField(field);
    if( f != null ) {
        if( cur_date != null ) {
            f.cur_date = cur_date;
        } else {
            cur_date = f.cur_date;
        }
    }

    if( M.size == 'compact' ) {
        num_months = 1;
    } else {
        if(f != null && f.caloffset != null ) {
            cur_month += f.caloffset;
        } else {
            cur_month--;
        }
        if( cur_month < 0 ) {
            cur_year-=parseInt((cur_month/12), 10);
            cur_month = Math.abs(parseInt((cur_month%12), 10));
        } else if( cur_month > 11 ) {
            cur_year+=parseInt((cur_month/12), 10);
            cur_month = parseInt((cur_month%12), 10);
        }
    }

    for(i=0;i<num_months;i++) {
        var d = M.aE('div', null, 'calendar');
        var t = M.aE('table', null, 'calendar noheader');
        if( f != null && f.colourize != null && (f.colourize == 'bg' || f.colourize == 'all') && this.calBgColour != null ) {
            t.className = 'calendar colourize';
        }
        t.setAttribute('cellspacing', '0');
        t.setAttribute('cellpadding', '0');
        var tb = M.aE('tbody');
        var tr = M.aE('tr');
    
        if( cur_month > 11 ) {
            cur_year++;
            cur_month = 0;
        }
    
        //
        // Display arrow to previous month
        //
        if( i == 0 ) {
            var c = M.aE('td', null, null, '&lt;');
            c.setAttribute('onclick', this.panelRef + '.showFieldCalendars(\'' + field + '\',' + start_year + ',' + (start_month-1) + ', null, \'' + fieldtype + '\',\'' + fn + '\',\'' + stime + '\');');
            tr.appendChild(c);
        } 
        var c = M.aE('td', null, null, M.months[cur_month].shortname + ' ' + cur_year);
        if( num_months == 1 ) {
            c.colSpan = 5;
        } else if( (i == 0 || i == (num_months-1)) && num_months > 1 ) {
            c.colSpan = 6;
        } else {
            c.colSpan = 7;
        }
        tr.appendChild(c);
        if( i == (num_months-1) ) {
            var c = M.aE('td', null, null, '&gt;');
            c.setAttribute('onclick', this.panelRef + '.showFieldCalendars(\'' + field + '\',' + start_year + ',' + (start_month+1) + ', null, \'' + fieldtype + '\',\'' + fn + '\',\'' + stime + '\');');
            tr.appendChild(c);
        }
        tb.appendChild(tr);
        tb.appendChild(M.aE('tr',null,null,'<td>S</td><td>M</td><td>T</td><td>W</td><td>T</td><td>F</td><td>S</td>'));
    
        var today = new Date();
        var dt = new Date(cur_year, cur_month, 1, 0, 0, 0);
        var cur_dow = dt.getDay();
        num_days = M.daysInMonth(cur_year, cur_month);

        tr = M.aE('tr');
        for(j=0;j<cur_dow;j++) {
            tr.appendChild(M.aE('td', null, 'empty'));
        }
        var weeks = 0;
        var cl = '';
        for(j=1;j<=num_days;j++) {
            if( cur_date != null && j == cur_date['day'] && cur_month == (Number(cur_date['month'])-1) && cur_year == cur_date['year'] ) {
                cl = 'selected';
            } else if( j == today.getDate() && cur_month == today.getMonth() && cur_year == today.getFullYear() ) {
                cl = 'today';
            } else {
                cl = '';
            }
            if( f != null && f.newselection_year != null && j == f.newselection_day && cur_month == (Number(f.newselection_month)-1) && cur_year == f.newselection_year ) {
                f.last_target_class = cl;
                cl = 'newselection';
            }
            c = M.aE('td', null, cl, j);
            // Add any specific colouring for this day
            if( f != null && f.colourize != null && (f.colourize == 'bg' || f.colourize == 'all') && this.calBgColour != null ) {
                c.style.backgroundColor = this.calBgColour(field, cur_year, cur_month, j);
            }
            if( fieldtype == 'appointment' ) {
                // FIXME: Need to make it into appointment type
                c.setAttribute('onclick', this.panelRef + '.updateAppointmentSchedule(event, \'' + field + '\',\'' + cur_year + '\',\'' + (cur_month + 1) + '\',\'' + j + '\',\'' + stime + '\');');
            } else if( fn != null && fn != '' && fn != 'null' && fn != 'undefined' ) {
                c.setAttribute('onclick', fn + '(\'' + field + '\',\'' + cur_year + '-' + (cur_month<9?'0':'') + (cur_month + 1) + '-' + (j<10?'0':'') + j + '\');');
            } else {
                c.setAttribute('onclick', this.panelRef + '.setFromCalendar(\'' + field + '\',\'' + cur_year + '-' + (cur_month<9?'0':'') + (cur_month + 1) + '-' + (j<10?'0':'') + j + '\');');
            }
            if( f != null && f.newselection_year != null && j == f.newselection_day && cur_month == (Number(f.newselection_month)-1) && cur_year == f.newselection_year ) {
                f.last_target = c;
            }
            tr.appendChild(c);    
            cur_dow++;
            if( cur_dow > 6 ) {
                weeks++;
                tb.appendChild(tr);
                tr = M.aE('tr');
                cur_dow = 0;
            }
        }
        for(j=cur_dow;j<7;j++) {
            tr.appendChild(M.aE('td', null, 'empty', '&nbsp;'));
        }
        tb.appendChild(tr);
        if( weeks < 5 ) {
            tr = M.aE('tr');
            for(j=0;j<7;j++) {
                tr.appendChild(M.aE('td', null, 'empty', '&nbsp;'));
            }
            tb.appendChild(tr);
        }
        t.appendChild(tb);
        d.appendChild(t);
        hC.appendChild(d);

        cur_month++;
        if( cur_month == 13 ) {
            cur_month = 1;
            cur_year++;
        }
    }
    //
    // Setup the appointment listings if this is an appointment
    //
    if( fieldtype == 'appointment' ) {
        var d = M.aE('div', this.panelUID + '_' + field + '_appointments', 'appointments');
        hC.appendChild(d);
        if( f.newselection_year != null && f.newselection_year != '' ) {
            this.updateAppointmentSchedule(null, field, f.newselection_year, f.newselection_month, f.newselection_day, stime);
        } else if( cur_date != null ) {
            this.updateAppointmentSchedule(null, field, cur_date['year'], cur_date['month'], cur_date['day'], stime);
        }
    }
};

M.panel.prototype.setFromCalendar = function(field, date) {
    //v = this.dateFormat(date).date;
    v = M.dateFormat(date);
    var input = M.gE(this.panelUID + '_' + field);
    input.value = v;
    this.removeFormFieldCalendar(field);
    // Trigger onchange
    if( input.onchange != null ) {
        input.onchange();
    }
};

M.panel.prototype.setFromAppointment = function(field, date, time, ad) {
    v = M.dateFormat(date);
    var f = this.formField(field);
    var e = M.gE(this.panelUID + '_' + field);
    // Remove the leading 0 on the time
    if( time.match(/0[1-9]/) ) {
        time = time.replace(/^0/,'');
    }
    if( ad == 0 ) {
        e.value = v + ' ' + time;
        if( f.duration != null ) {
            // Recover the old value
            var ov = M.gE(this.panelUID + '_' + f.duration);
            if( ov != null && ov.value == '24:00' && ov.oldvalue != null ) { 
                this.setFieldValue(f.duration, ov.oldvalue, null, 2);
            } else {
                this.setFieldValue(f.duration, ov.value, null, 2);
            }
            var df = this.formField(f.duration);
            if( df.allday == 'yes' ) {
                M.gE(this.panelUID + '_' + f.duration + '_buttons_allday').childNodes[0].className = 'toggle_off';
                M.gE(this.panelUID + '_' + f.duration + '_buttons_allday').className = 'buttons';
            }
        }
    } else {
        // Save the old time
//        M.gE(this.panelUID + '_' + field).oldtime = this.parseDate(M.gE(this.panelUID + '_' + field).value)['time'];
        M.gE(this.panelUID + '_' + field).oldtime = M.parseDate(M.gE(this.panelUID + '_' + field).value)['time'];
        if( f.duration != null ) {
            // Save the old value, and set to all day duration of 24 hours
            var df = this.formField(f.duration);
            M.gE(this.panelUID + '_' + f.duration).oldvalue = this.formFieldValue(df, f.duration);
            this.setFieldValue(f.duration, 1440, null, 1);
            if( df.allday == 'yes' ) {
                M.gE(this.panelUID + '_' + f.duration + '_buttons_allday').childNodes[0].className = 'toggle_on';
                M.gE(this.panelUID + '_' + f.duration + '_buttons_allday').className = 'buttons nopadbuttons';
            }
        }
        e.value = v;
    }
    this.removeFormFieldAppointment(field);
    f.newselection_year = '';
    f.newselection_month = '';
    f.newselection_day = '';
};

M.panel.prototype.updateAppointmentSchedule = function(event, field, year, month, day, stime) {
    var d = M.gE(this.panelUID + '_' + field + '_appointments');
    //
    // Set the field values to the DOM object
    //
    var f = this.formField(field);
    if( event != null ) {
        if( f.last_target != null ) {
            f.last_target.className = f.last_target_class;    
        }
        if( event.target.getAttribute('class') != null ) {
            f.last_target_class = event.target.getAttribute('class');
        } else {
            f.last_target_class = '';
        }
        event.target.className = 'newselection';
        f.last_target = event.target;
        f.newselection_year = year;
        f.newselection_month = month;
        f.newselection_day = day;
    }

    if( year == null || year == '' ) {
        var dt = new Date();
        year = dt.getFullYear();
        month = dt.getMonth()+1;
        day = dt.getDate();
    }

    //
    // Set date field
    // FIXME: This should not display the time if an allday appointment (12:00 AM) is currently being displayed.
    //
    v = M.dateFormat(year + '-' + month + '-' + day);
    M.gE(this.panelUID + '_' + field).value = v + ' ' + stime;

    //
    // Get the schedule for that day from the server
    //
    var p = this;
    var appointments = this.liveAppointmentDayEvents(field, year + '-' + month + '-' + day, function(rsp) {
        if( rsp.stat != 'ok' ) {
            M.api.err(rsp);
            return false;
        }
        var appointments = rsp.appointments;
        var t = p.generateAppointmentScheduleTable(f, field, 'dayschedule', appointments, 15, year + '-' + month + '-' + day);
        // Clear existing table
        if( d.children.length > 0 ) {
            M.clr(d);
        }
        d.appendChild(t);
    });
};

// t - the date/time for the appointment time cell
// ti - time interval
M.panel.prototype.generateAppointmentScheduleTimeCell = function(t, ti, f, d) {
    var c = M.aE('td', null, 'schedule_time slice_' + (t/(ti*60))%(60/ti), M.dateMake12hourTime(t));
    c.rowSpan = (ti/5);
    if( f != null ) {
        c.setAttribute('onclick', this.panelRef + '.setFromAppointment(\'' + f + '\',\'' + d + '\',\'' + M.dateMake12hourTime2(t) + '\', 0);');
        c.className += ' clickable';
    } else if( this.appointmentTimeFn != null ) {
        c.setAttribute('onclick', this.appointmentTimeFn(d, M.dateMake12hourTime2(t),0));
        c.className += ' clickable';
    }
    return c;
};

// tinterval - the time between displaying time increments
M.panel.prototype.generateAppointmentScheduleTable = function(f, field, cl, appointments, tinterval, adate) {
    var t = M.aE('table', null, cl);
    t.setAttribute('cellspacing', '0');
    t.setAttribute('cellpadding', '0');
    var tb = M.aE('tbody');

    var dt = new Date();
    var ti = f.start; // '10:00'; // rsp['schedule_start'];
    var st = ti.split(':');
    dt.setHours(st[0], st[1], 0, 0);
    var start_ts = Math.round(dt.getTime() / 1000);

    var ti = f.end; // '20:00'; // rsp['schedule_end'];
    var st = ti.split(':');
    dt.setHours(st[0], st[1], 0, 0);
    var end_ts = Math.round(dt.getTime() / 1000);

    // var interval = f.interval * 60; // 30 * 60;
    var interval = 5 * 60;
    var cur_ts = start_ts;

    if( appointments != null && appointments.length > 0 ) {
        var pev = null;
        var pc = null;
        for(i in appointments) {
            var ev = appointments[i]['appointment'];
            // Check for all day events
            if( (ev['allday'] != null && ev['allday'] == 'yes') || i == 0 ) {
                var tr = M.aE('tr');
                if( i == 0 ) {
                    var c = null;
                    if( f != null && f.notimelabel != null ) {
                        c = M.aE('td', null, 'schedule_time allday', f.notimelabel);
                    } else {
                        c = M.aE('td', null, 'schedule_time allday', 'All Day');
                    }
                    if( field != null ) {
                        c.setAttribute('onclick', this.panelRef + '.setFromAppointment(\'' + field + '\',\'' + adate + '\',\'12:00 AM\', 1);');
                        c.className += ' clickable';
                    } else if( this.appointmentTimeFn != null ) {
                        c.setAttribute('onclick', this.appointmentTimeFn(adate, '12:00 AM',1));
                        c.className += ' clickable';
                    }
                    pc = c;
                    c.colSpan = 2;
                    tr.appendChild(c);
                } else if(i > 0) {
                    pc.rowSpan = parseInt(i)+1;
                }
                // This element is used to setup fixed sizes for rows
//                tr.appendChild(M.aE('td', null, 'schedule_interval schedule_interval_allday', ''));

                if( ev['allday'] == 'yes' ) {
                    c = M.aE('td', null, 'schedule_appointment', '<p class=\'size_6\'>' + this.appointmentEventText(ev) + '</p>');
                    c.colSpan = 10;
                    // Check if there is a specific colour for this appointment
                    if( this.appointmentColour != null ) {
                        c.bgColor = this.appointmentColour(ev);
                    }
                    if( this.appointmentFn != null ) {
                        c.setAttribute('onclick', this.appointmentFn(ev));
                        c.className += ' clickable';
                    }
                    tr.appendChild(c);
                } else {
                    c = M.aE('td', null, 'empty');
                    c.colSpan = 10;
                    tr.appendChild(c);
                }
                tb.appendChild(tr);
            } 
            if( ev['time'] != '00:00' && ev.allday != 'yes' ) {
                var ti = ev['time'];
                var st = ti.split(':');
                dt.setHours(st[0], st[1], 0, 0);
                var ev_ts = Math.round(dt.getTime() / 1000)
                
                var str = '';
                while( (cur_ts) < ev_ts ) {
                    var tr = M.aE('tr');
                    if( (cur_ts%(tinterval*60)) == 0 ) {
                        tr.appendChild(this.generateAppointmentScheduleTimeCell(cur_ts, tinterval, field, adate));
                    }
                    // This element is used to setup fixed sizes for rows
                    tr.appendChild(M.aE('td', null, 'schedule_interval', ''));

//                        var c = M.aE('td', null, 'empty')
//                        c.colSpan = 10;
//                        tr.appendChild(c);

                    tb.appendChild(tr);
                    cur_ts += interval;
                }
                if( cur_ts <= ev_ts ) {
                    cur_ts += interval;
                }

                //
                // Add the appointment
                //
                if( pev == null || pev['time'] != ev['time'] ) {
                    var tr = M.aE('tr');
                    if( (ev_ts%(tinterval*60)) == 0 ) {
                        tr.appendChild(this.generateAppointmentScheduleTimeCell(ev_ts, tinterval, field, adate));
                    }
                    // This element is used to setup fixed sizes for rows
                    tr.appendChild(M.aE('td', null, 'schedule_interval', ''));
                }
                // element contains the appointment
                var rs = Math.max(Math.round(parseInt(ev['duration'])/5), 3);
                var c = M.aE('td', null, 'schedule_appointment', '<p class=\'size_' + rs + '\'>' + this.appointmentEventText(ev) + '</p>');
                c.rowSpan = rs;
                // Check if there is a specific colour for this appointment
                if( this.appointmentColour != null ) {
                    c.bgColor = this.appointmentColour(ev);
                }
                if( this.appointmentFn != null ) {
                    c.setAttribute('onclick', this.appointmentFn(ev));
                    c.className = 'schedule_appointment clickable';
                }

                //c.appendChild(adiv);
                tr.appendChild(c);
                tb.appendChild(tr);
            }
            pev = ev;
        }

        //
        // Fill in the end of the schedule after all appointments are finished
        //
        while( (cur_ts) < end_ts ) {
            // this.schedule.data[count++] = {'label':M.dateMake12hourTime(cur_ts)};
            var tr = M.aE('tr');
            if( (cur_ts%(tinterval*60)) == 0 ) {
                tr.appendChild(this.generateAppointmentScheduleTimeCell(cur_ts, tinterval, field, adate));
            }
            // This element is used to setup fixed sizes for rows
            tr.appendChild(M.aE('td', null, 'schedule_interval', ''));

//                var c = M.aE('td', null, 'empty');
//                c.colSpan = 10;
//                tr.appendChild(c);
            tb.appendChild(tr);
            cur_ts += interval;
        }
    } else if( adate != '' && adate != '' ) {
        // Add unknown time, or Call To Book row
        var tr = M.aE('tr');
        if( f != null && f.notimelabel != null ) {
            var c = M.aE('td', null, 'schedule_time allday', f.notimelabel);
        } else {
            var c = M.aE('td', null, 'schedule_time allday', 'All Day');
        }
        if( field != null ) {
            c.setAttribute('onclick', this.panelRef + '.setFromAppointment(\'' + field + '\',\'' + adate + '\',\'12:00 AM\', 1);');
            c.className += ' clickable';
        } else if( this.appointmentTimeFn != null ) {
            c.setAttribute('onclick', this.appointmentTimeFn(adate, M.dateMake12hourTime2(cur_ts),1));
            c.className += ' clickable';
        }
        c.colSpan = 2;
        tr.appendChild(c);
        var c = M.aE('td', null, 'empty');
        c.colSpan = 10;
        tr.appendChild(c);

        tb.appendChild(tr);

        // Add empty time slots
        while( (cur_ts) < end_ts ) {
            var tr = M.aE('tr');
            if( (cur_ts%(tinterval*60)) == 0 ) {
                tr.appendChild(this.generateAppointmentScheduleTimeCell(cur_ts, tinterval, field, adate));
            }
            // This element is used to setup fixed sizes for rows
            tr.appendChild(M.aE('td', null, 'schedule_interval', ''));

    //        tr.appendChild(M.aE('td', null, 'empty'));
            tb.appendChild(tr);
            cur_ts += interval;
        }
    }

    //
    // Add blank row at the end to support rounded corners
    //
    var tr = M.aE('tr');
    tr.appendChild(M.aE('td', null, 'schedule_time'));
    tr.appendChild(M.aE('td', null, 'schedule_interval'));
    var c = M.aE('td', null, 'empty');
    c.colSpan = 10;
    tr.appendChild(c);
    tb.appendChild(tr);


    t.appendChild(tb);
    return t;
};

M.panel.prototype.generateMultiWeekScheduleTable = function(s, cl, data, sdate, edate) {
    var t = M.aE('table', null, cl);
    t.setAttribute('cellspacing', '0');
    t.setAttribute('cellpadding', '0');

    var cur_date = new Date(sdate.getTime());

    var th = M.aE('thead');
    var tr = M.aE('tr',null,'days');
    if( M.size == 'compact' ) {
        tr.appendChild(M.aE('th',null,'monthlabel',''));
        tr.appendChild(M.aE('th',null,null,'Sun'));
        tr.appendChild(M.aE('th',null,null,'Mon'));
        tr.appendChild(M.aE('th',null,null,'Tue'));
        tr.appendChild(M.aE('th',null,null,'Wed'));
        tr.appendChild(M.aE('th',null,null,'Thu'));
        tr.appendChild(M.aE('th',null,null,'Fri'));
        tr.appendChild(M.aE('th',null,null,'Sat'));
    } else {
        tr.appendChild(M.aE('th',null,'monthlabel',''));
        tr.appendChild(M.aE('th',null,null,'Sunday'));
        tr.appendChild(M.aE('th',null,null,'Monday'));
        tr.appendChild(M.aE('th',null,null,'Tuesday'));
        tr.appendChild(M.aE('th',null,null,'Wednesday'));
        tr.appendChild(M.aE('th',null,null,'Thursday'));
        tr.appendChild(M.aE('th',null,null,'Friday'));
        tr.appendChild(M.aE('th',null,null,'Saturday'));
    }
    th.appendChild(tr);
    t.appendChild(th);

    //
    // Build the table
    //
    var tb = M.aE('tbody');
    col = 0;
    var tr = M.aE('tr');
    tr.appendChild(M.aE('td',null,'monthlabel','<span>' + M.monthOfYear(cur_date) + '</span>'));
    while(cur_date <= edate) {
        var cds = cur_date.toISOString().substring(0,10);
        if( col >= 7 ) { 
            col = 0; 
            tb.appendChild(tr);
            tr = M.aE('tr');
            tr.appendChild(M.aE('td',null,'monthlabel','<span>' + M.monthOfYear(cur_date) + '</span>'));
        }
        
        var c = M.aE('td');
        if( this.newFn != null ) {
            c.setAttribute('onclick', 'event.stopPropagation(); ' + this.newFn(cds));
            c.className += ' clickable';
        }

        //
        // Add the day and special notes for the day
        //
        var d = M.aE('div');
        var sp = M.aE('span',null,'day', cur_date.getDate());    
        if( this.sections[s].dayfn != null && this.sections[s].dayfn != '' ) {
            sp.setAttribute('onclick', 'event.stopPropagation(); ' + this.sections[s].dayfn + '(\'' + s + '\',\'' + cds + '\');');
            sp.className += ' clickable';
        }
        d.appendChild(sp);
        var sp = M.aE('span',null,'notes', this.multiWeekDayNotes(cds));    
        d.appendChild(sp);    
        c.appendChild(d);

        //
        // Add the appointments
        //
        var d = M.aE('div');
        if( data[cds] != null ) {
            // Create a div to contain each appointment
            for(var i in data[cds]) {
                var e = M.aE('div',null,'appointment');
                var ev = data[cds][i]['appointment'];
                // Check if there is a specific colour for this appointment
                if( this.appointmentColour != null ) {
                    e.style.background = this.appointmentColour(ev);
                }
                if( this.appointmentFn != null ) {
                    e.setAttribute('onclick', 'event.stopPropagation(); ' + this.appointmentFn(ev));
                    e.className = 'schedule_appointment clickable';
                }
                if( ev['12hour'] != null && ev['12hour'] != '' && (ev.allday == null || ev.allday == 'no') ) {    
                    var time = M.aE('span',null,'time',ev['12hour']);
                    e.appendChild(time);
                }
                var su = M.aE('span',null,'subject', this.appointmentAbbrSubject(ev));
                e.appendChild(su);
                var se = M.aE('span',null,'secondary', this.appointmentAbbrSecondary(ev));
                e.appendChild(se);
                d.appendChild(e);
            }
        }

        c.appendChild(d);
        tr.appendChild(c);

        cur_date.setDate(cur_date.getDate() + 1);
        col++;
    }
    // Append the last row
    tb.appendChild(tr);
    t.appendChild(tb);
    return t;
};

M.panel.prototype.multiWeekDayNotes = function(ev) {
    return '';
};

M.panel.prototype.appointmentAbbrSubject = function(ev) {
    if( ev != null && ev.abbr_subject != null ) { return ev.abbr_subject; }
    return '';
};

M.panel.prototype.appointmentAbbrSecondary = function(ev) {
    if( ev != null && ev.abbr_secondary_text != null ) { return ev.abbr_secondary_text; }
    return '';
};

M.panel.prototype.appointmentEventText = function(ev) {
    if( ev != null && ev.subject != null ) { return ev.subject; }
    return '';
};

M.panel.prototype.appointmentColour = function(ev) {
    if( ev != null && ev.colour != null && ev.colour != '' ) {
        return ev.colour;
    }
    return '#aaddff';
};

//
// Arguments:
// wID - the element ID for the button bar
// 
M.panel.prototype.showButtons = function(wID, buttons) {
    //  
    // Create the menu buttons
    //
    var c = 0;
    for(var i in buttons) {
        var icn = '';
        switch(buttons[i].icon) {
//            case 'rewind': icn = 'B'; break;
//            case 'prev': icn = 'p'; break;
//            case 'next': icn = 'n'; break;
//            case 'add': icn = 'a'; break;
//            case 'settings': icn = 's'; break;
//            case 'save': icn = 'S'; break;
//            case 'edit': icn = 'y'; break;
//            case 'download': icn = 'G'; break;
//            case 'exit': 
//            case 'close': 
//            case 'cancel': icn = 'X'; break;
//            case 'more': icn = 'm'; break;
//            case 'tools': icn = 'A'; break;
//            case 'admin': icn = 'A'; break;
//            case 'account': icn = 'w'; break;
//            case 'logout': icn = 'L'; break;
//            case 'forward': icn = 'f'; break;
            case 'back': icn = '&#xf060;'; break;
            case 'rewind': icn = '&#xf048;'; break;
            case 'prev': icn = '&#xf053;'; break;
            case 'next': icn = '&#xf054;'; break;
            case 'add': icn = '&#xf067;'; break;
            case 'settings': icn = '&#xf013;'; break;
            case 'save': icn = '&#xf0c7;'; break;
            case 'edit': icn = '&#xf040;'; break;
            case 'download': icn = '&#xf019;'; break;
            case 'exit': 
            case 'close': 
            case 'print': icn = '&#xf02f;'; break;
            case 'cancel': icn = '&#xf00d;'; break;
            case 'more': icn = '&#xf141;'; break;
            case 'tools': icn = '&#xf0ad;'; break;
            case 'admin': icn = '&#xf0ad;'; break;
            case 'account': icn = '&#xf007;'; break;
            case 'logout': icn = '&#xf08b;'; break;
            case 'forward': icn = '&#xf061;'; break;
            case 'bigboard': icn = '&#xf0ae;'; break;
            case 'website': icn = '&#xf08e;'; break;
            case 'mwcalendar': icn = '&#xf073;'; break;
            case 'daycalendar': icn = '&#xf0c9;'; break;
        }
        switch(buttons[i].label) {
            case 'Home': icn = '&#xf015;';break;
            case 'Back': icn = '&#xf060;';break;
            case 'Close':
            case 'Cancel': icn = '&#xf00d;';break;
        }
        var l = M.clr(this.appPrefix + '_' + wID + '_' + c);
        var bfn = null;
        if( i == 'prev' && this.prevButtonFn != null ) {
            bfn = this.prevButtonFn();
        } else if( i == 'next' && this.nextButtonFn != null ) {
            bfn = this.nextButtonFn();
        } else {
            bfn = buttons[i]['function'];
        }
        if( bfn != null ) {
            l.appendChild(M.aE('div', null, 'button ' + i, '<span class="faicon">' + icn + '</span><span class="label">' + buttons[i].label + '</span>'));
            l.setAttribute('onclick', bfn + 'return false;');
            l.className = wID;
            c++;
        }
    }
    // Clear unused spaces
    for(;c<3;c++){
        var l = M.clr(this.appPrefix + '_' + wID + '_' + c);
        if( l != null ) {
            l.className = wID + ' hide';
            l.setAttribute('onclick', '');
        }
    }
};

//
// Go through the form details, and all sections to find all the elements 
// of the form.  Then compare to see if any are updated.
//
// Arguments:
// fs - The flag to specifiy if all fields should be included, even if not updated.  This is used
//        for forms which add information.  When a form is for editing data, it should be 'no'.
//
M.panel.prototype.serializeForm = function(fs) {
    // The content variable to store the encoded variables
    var c = '';    
    var count = 0;        // The number of changes

    //
    // Check for formtabs
    //
    if( this.formtabs != null && this.formtabs.field != null && this.formtabs.field != '' ) {
        var o = '';
        if( this.fieldValue != null ) {
            o = this.fieldValue('', this.formtabs.field, null);
        }
        if( o == undefined ) { o = ''; }
        if( this.formtab_field_id != null ) {
            var n = this.formtab_field_id;
        } else if( this.formtab != null && this.formtab != '' && this.formtabs.tabs[this.formtab] != null ) {
            var n = this.formtabs.tabs[this.formtab].field_id;
        }
        if( n != null && (n != o || fs == 'yes') ) {
            c += encodeURIComponent(this.formtabs.field) + '=' + encodeURIComponent(n) + '&';
            count++;
        }
    }

    var flags = {};
    for(i in this.sections) {
        //
        // Grid elements
        //
        var s = this.sections[i];
        if( s.multi != null && s.multi == 'yes' ) {
            continue;
        }
        if( s.active != null && typeof s.active == 'function' && s.active() == 'no' ) {
            continue;
        }
        if( s.active != null && s.active == 'no' ) {
            continue;    // Skip inactive sections
        }
        if( s.type != null && (s.type == 'gridform' || s.type == 'simplegrid') ) {
            for(j in s.fields) {
                for(k in s.fields[j]) {
                    var f = s.fields[j][k];
                    var fid = f.id;
                    var o = '';
                    if( this.fieldValue != null ) {
                        o = this.fieldValue(i, fid, f);
                    }
                    // Set to blank if not defined
                    if( o == undefined ) { o = ''; }
                    var n = this.formFieldValue(f, fid);
                    if( n != o || fs == 'yes' ) {
                        c += encodeURIComponent(fid) + '=' + encodeURIComponent(n) + '&';
                    }
                }
            }
        } 
        //
        // Check if paneltabs is a form element
        //
        else if( s.type != null && s.type == 'paneltabs' && s.field_id != null && s.selected != null ) {
            var o = this.fieldValue(i, s.field_id);
            var n = s.selected;
            if( o != n || fs == 'yes' ) {
                c += encodeURIComponent(s.field_id) + '=' + encodeURIComponent(s.selected) + '&';
            }
        }
        //
        // All non-grid elements
        //
        else {
            // Skip multi sections, they need to be serialized another way
            for(j in s.fields) {
                var f = s.fields[j];
                if( f.type == null || f.type == 'noedit' ) { continue; }
                if( f.active != null && ((typeof f.active == 'function' && f.active() == 'no') || f.active == 'no') ) { continue; }
                var fid = j;
                if( this.fieldID != null ) {
                    fid = this.fieldID(i, j, f);
                }
                var o = '';
                if( this.fieldValue != null ) {
                    o = this.fieldValue(i, fid, f);
                }
                // Set to blank if not defined
                if( o == undefined ) { o = ''; }
                var n = this.formFieldValue(f, fid);
                if( f.type != 'flagtoggle' && f.type != 'flagspiece' && (n != o || fs == 'yes') ) {
                    c += encodeURIComponent(fid) + '=' + encodeURIComponent(n) + '&';
                }
                // Check if secondary field
                if( f.option_field != null ) {
                    var o = '';
                    if( this.fieldValue != null ) { o = this.fieldValue(i, fid, f); }
                    if( o == undefined ) { o = ''; }
                    var n = this.formFieldValue(f, f.option_field);
                    if( n != o || fs == 'yes' ) {
                        c += encodeURIComponent(fid) + '=' + encodeURIComponent(n) + '&';
                    }
                }
                // Check if flagtoggle and field specified
                if( f.type == 'flagtoggle' && f.field != null ) {
                    if( flags[f.field] == null ) {
                        flags[f.field] = {'f':f, 'v':this.fieldValue('', f.field, f)};
                    }
                    if( n == 'on' || (f.reverse != null && f.reverse == 'yes' && n == 'off') ) {
                        flags[f.field].v |= f.bit;
                    } else if( (flags[f.field].v&f.bit) > 0 ) {
                        flags[f.field].v ^= f.bit;
                    }
                } else if( f.type == 'flagspiece' && f.field != null ) {
                    if( flags[f.field] == null ) {
                        flags[f.field] = {'f':f, 'v':this.fieldValue('', f.field, f)};
                    }
                    var n = this.formFieldValue(f, fid);
                    flags[f.field].v = flags[f.field].v ^ ((flags[f.field].v ^ n) & f.mask);
                }
            }
        }
    }

    //
    // Check for flags that need to be updated
    //
    for(var i in flags) {
        var o = 0;
        if( this.fieldValue != null ) {
            o = this.fieldValue('', flags[i].f.field, flags[i].f);
        }
        var n = flags[i].v;
        if( n != o || fs == 'yes' ) {
            c += encodeURIComponent(flags[i].f.field) + '=' + encodeURIComponent(n) + '&';
            count++;
        }
    }


    return c;
};

//
// Go through the form fields and make sure required ones are filled in
//
M.panel.prototype.checkForm = function() {
    // FIXME: Add check for required formtabs field_id

    // FIXME: Add checks for required GridSection

    for(i in this.sections) {
        var s = this.sections[i];
        // Skip multi sections, they need to be serialized another way
        for(j in s.fields) {
            var f = s.fields[j];
            if( f.required != null && f.required == 'yes' ) {
                var fid = j;
                if( this.fieldID != null ) {
                    fid = this.fieldID(i, j, f);
                }
                var n = this.formFieldValue(f, fid);
                if( n == null || n == '' ) {
                    alert('You must enter ' + f.label);
                    return false;
                }
            }
        }
    }
    return true;
};

//
// This function will serialize the data from one section only
//
// fs - Include all fields, or just changed ones
// s - the section
// nM - The multi item number
//
M.panel.prototype.serializeFormSection = function(fs, i, nM) {
    // The content variable to store the encoded variables
    var c = '';    

    var s = this.sections[i];
    //
    //
    // Check if paneltabs is a form element
    //
    if( s.type != null && s.type == 'paneltabs' && s.field_id != null && s.selected != null ) {
        var o = this.fieldValue(i, s.field_id);
        var n = s.selected;
        if( o != n || fs == 'yes' ) {
            c += encodeURIComponent(s.field_id) + '=' + encodeURIComponent(s.selected) + '&';
        }
    }
    //
    // FIXME: Untested code from serializeForm...
    //
/*    for(j in s.fields) {
        var f = s.fields[j];
        if( f.type == null || f.type == 'noedit' || (f.active != null && f.active == 'no') ) { continue; }
        var fid = j;
        if( this.fieldID != null ) {
            fid = this.fieldID(i, j, f);
        }
        var o = '';
        if( this.fieldValue != null ) {
            o = this.fieldValue(i, fid, f, nM);
        }
        // Set to blank if not defined
        if( o == undefined ) { o = ''; }
        if( nM != null ) {
            var n = this.formFieldValue(f, fid + '_' + nM);
        } else {
            var n = this.formFieldValue(f, fid);
        }
        if( n != o || fs == 'yes' ) {
            c += encodeURIComponent(fid) + '=' + encodeURIComponent(n) + '&';
        }
        // Check if secondary field
        if( f.option_field != null ) {
            var o = '';
            if( this.fieldValue != null ) { o = this.fieldValue(i, fid, f); }
            if( o == undefined ) { o = ''; }
            var n = this.formFieldValue(f, f.option_field);
            if( n != o || fs == 'yes' ) {
                c += encodeURIComponent(fid) + '=' + encodeURIComponent(n) + '&';
            }
        }
    } */
    var flags = {};
    for(j in s.fields) {
        var f = s.fields[j];
        if( f.type == null || f.type == 'noedit' ) { continue; }
        if( f.active != null && ((typeof f.active == 'function' && f.active() == 'no') || f.active == 'no') ) { continue; }
        var fid = j;
        if( this.fieldID != null ) {
            fid = this.fieldID(i, j, f);
        }
        var o = '';
        if( this.fieldValue != null ) {
            o = this.fieldValue(i, fid, f);
        }
        // Set to blank if not defined
        if( o == undefined ) { o = ''; }
        if( nM != null ) {
            var n = this.formFieldValue(f, fid + '_' + nM);
        } else {
            var n = this.formFieldValue(f, fid);
        }
        if( f.type != 'flagtoggle' && f.type != 'flagspiece' && (n != o || fs == 'yes') ) {
            c += encodeURIComponent(fid) + '=' + encodeURIComponent(n) + '&';
        }
        // Check if secondary field
        if( f.option_field != null ) {
            var o = '';
            if( this.fieldValue != null ) { o = this.fieldValue(i, fid, f); }
            if( o == undefined ) { o = ''; }
            var n = this.formFieldValue(f, f.option_field);
            if( n != o || fs == 'yes' ) {
                c += encodeURIComponent(fid) + '=' + encodeURIComponent(n) + '&';
            }
        }
        // Check if flagtoggle and field specified
        if( f.type == 'flagtoggle' && f.field != null ) {
            if( flags[f.field] == null ) {
                flags[f.field] = {'f':f, 'v':this.fieldValue('', f.field, f)};
            }
            if( n == 'on' || (f.reverse != null && f.reverse == 'yes' && n == 'off') ) {
                flags[f.field].v |= f.bit;
            } else if( (flags[f.field].v&f.bit) > 0 ) {
                flags[f.field].v ^= f.bit;
            }
        } else if( f.type == 'flagspiece' && f.field != null ) {
            if( flags[f.field] == null ) {
                flags[f.field] = {'f':f, 'v':this.fieldValue('', f.field, f)};
            }
            var n = this.formFieldValue(f, fid);
            flags[f.field].v = flags[f.field].v ^ ((flags[f.field].v ^ n) & f.mask);
        }
    }

    //
    // Check for flags that need to be updated
    //
    for(var i in flags) {
        var o = 0;
        if( this.fieldValue != null ) {
            o = this.fieldValue('', flags[i].f.field, flags[i].f);
        }
        var n = flags[i].v;
        if( n != o || fs == 'yes' ) {
            c += encodeURIComponent(flags[i].f.field) + '=' + encodeURIComponent(n) + '&';
        }
    }

    return c;
};

//
// Go through the form details, and all sections to find all the elements 
// of the form.  Then compare to see if any are updated.
// The result should be a FormData object to be uploaded to the server, or null if nothing has changed.
//
// Arguments:
// fs - The flag to specifiy if all fields should be included, even if not updated.  This is used
//        for forms which add information.  When a form is for editing data, it should be 'no'.
//
M.panel.prototype.serializeFormData = function(fs) {
    // The content variable to store the encoded variables
    var c = new FormData;    
    var count = 0;        // The number of changes

    //
    // Check for formtabs
    //
    if( this.formtabs != null && this.formtabs.field != null && this.formtabs.field != '' ) {
        var o = '';
        if( this.fieldValue != null ) {
            o = this.fieldValue('', this.formtabs.field, null);
        }
        if( o == undefined ) { o = ''; }
        if( this.formtab != null && this.formtab != '' ) {
            var n = this.formtabs.tabs[this.formtab].field_id;
        }
        if( n != null && (n != o || fs == 'yes') ) {
            c.append(this.formtabs.field, n);
            count++;
        }
    }

    var flags = {};
    for(i in this.sections) {
        //
        // Grid elements
        //
        var s = this.sections[i];
        if( s.active != null && typeof s.active == 'function' && s.active() == 'no' ) {
            continue;   // Skip inactive sections
        }
        if( s.active != null && s.active == 'no' ) {
            continue;   // Skip inactive sections
        }
        if( s.type != null && (s.type == 'gridform' || s.type == 'simplegrid') ) {
            for(j in s.fields) {
                for(k in s.fields[j]) {
                    var f = s.fields[j][k];
                    var fid = f.id;
                    var o = '';
                    if( this.fieldValue != null ) {
                        o = this.fieldValue(i, fid, f);
                    }
                    // Set to blank if not defined
                    if( o == undefined ) { o = ''; }
                    var n = this.formFieldValue(f, fid);
                    if( n != o || fs == 'yes' ) {
                        c.append(fid, n);
                        count++;
                    }
                }
            }
        } 
        //
        // FIXME: Untested code from serializeForm...
        // Check if paneltabs is a form element
        //
//      if( s.type != null && s.type == 'paneltabs' && s.field_id != null && s.selected != null ) {
//          c += encodeURIComponent(s.field_id) + '=' + encodeURIComponent(s.selected) + '&';
//      }
    
        //
        // All non-grid elements
        //
        else {
            for(j in s.fields) {
                var f = s.fields[j];
                if( f.type == null || f.type == 'noedit' || (f.active != null && f.active == 'no') ) { continue; }
                var fid = j;
                if( this.fieldID != null ) {
                    fid = this.fieldID(i, j, f);
                }
                var o = '';
                if( this.fieldValue != null ) {
                    o = this.fieldValue(i, fid, f);
                }
                // Set to blank if not defined
                if( o == undefined ) { o = ''; }
                if( f.type == 'image' || f.type == 'file' ) {
                    var file = document.getElementById(this.panelUID + '_' + fid);
                    if( file != null && file.files != null && file.files[0] != null ) {
                        c.append(fid, file.files[0]);
                        count++;
                    }
                } else if( f.type == 'flagtoggle' && f.field != null ) {
                    if( flags[f.field] == null ) {
                        flags[f.field] = {'f':f, 'v':0};
                    }
                    var n = this.formFieldValue(f, fid);
                    if( n == 'on' || (f.reverse != null && f.reverse == 'yes' && n == 'off') ) {
                        flags[f.field].v |= f.bit;
                    }
                } else if( f.type == 'flagspiece' && f.field != null ) {
                    if( flags[f.field] == null ) {
                        flags[f.field] = {'f':f, 'v':this.fieldValue('', f.field, f)};
                    }
                    var n = this.formFieldValue(f, fid);
                    flags[f.field].v = flags[f.field].v ^ ((flags[f.field].v ^ n) & f.mask);
                } else {
                    var n = this.formFieldValue(f, fid);
                    if( n != o || fs == 'yes' ) {
                        c.append(fid, n);
                        count++;
                    }
                    // Check if secondary field
                    if( f.option_field != null ) {
                        var o = '';
                        if( this.fieldValue != null ) { o = this.fieldValue(i, fid, f); }
                        if( o == undefined ) { o = ''; }
                        var n = this.formFieldValue(f, f.option_field);
                        if( n != o || fs == 'yes' ) {
                            c.append(fid, n);
                        }
                    }
                }
            }
        }
    } // Done the sections

    //
    // Check for flags that need to be updated
    //
    for(var i in flags) {
        var o = 0;
        if( this.fieldValue != null ) {
            o = this.fieldValue('', flags[i].f.field, flags[i].f);
        }
        var n = flags[i].v;
        if( n != o || fs == 'yes' ) {
            c.append(flags[i].f.field, n);
            count++;
        }
    }

    // No changes, return null so nothing will get submited to the database
    if( count == 0 ) { return null; }

    return c;
};

//
// Arguments:  f - the form element from the panel
// nM - The number of the multi type section form
//
M.panel.prototype.formFieldValue = function(f,fid) {
    var n = null;
    if( f == null ) { 
        return null; 
    }
    if( f.option_field != null && f.option_field == fid ) {
        // Get the secondary option field value
        for(var i in f.options) {
            if( M.gE(this.panelUID + '_' + fid + '_' + i).className == 'toggle_on' ) {
                n = f.options[i];
            }
        }
    } else if( f.type == 'colourswatches' ) {
        n = M.gE(this.panelUID + '_' + fid).getAttribute('value');
        // Check if no value was found, and save as blank instead of the string null.
        if( n == null ) { n = ''; }
    } else if( f.type == 'date' ) {
        n = M.gE(this.panelUID + '_' + fid).value;
    } else if( f.type == 'datetime' ) {
        n = M.gE(this.panelUID + '_' + fid + '').value + ' ' + M.gE(this.panelUID + '_' + fid + '_time').value;
    } else if( f.type == 'colour' ) {
        n = M.rgbToHex(M.gE(this.panelUID + '_' + fid).style.backgroundColor);
    } else if( f.type == 'flags' ) {
        n = 0;
        // By starting with the existing value, not all bits have to be specified in each form.
        // This was created for Members/Dealers/Distributors in customers
        var s = this.sections[this.formFieldSection(f)];
        n = this.fieldValue(s, fid, f);
        if( typeof(n) == 'string' ) {
            n = parseInt(n, 10);
        }
        if( n == null || n == '' ) { n = 0; }
        var nhi = n.toString(16);
        var nlo = nhi.substr(-8);
        nhi = nhi.length > 8 ? nhi.substr(0, nhi.length - 8) : '0';
        nlo = parseInt(nlo, 16);
        nhi = parseInt(nhi, 16);
        for(j in f.flags) {
            if( f.flags[j] == null ) { continue; }
            if( f.flags[j].active != null && f.flags[j].active == 'no' ) { continue; }
            if( M.gE(this.panelUID + '_' + fid + '_' + j).className == 'flag_on' ) {
                // Toggle bit on
                if( j > 32 ) {
                    nhi |= Math.pow(2, j-33);
                } else {
                    nlo |= Math.pow(2, j-1);
                }
            } else {
                // Toggle bit off
                if( j > 32 ) {
                    if( (nhi&Math.pow(2, j-33)) > 0 ) { nhi ^= Math.pow(2, j-33); }
                } else {
                    if( (nlo&Math.pow(2, j-1)) > 0 ) { nlo ^= Math.pow(2, j-1); }
                }
            }
        }
        if( nhi > 0 ) {
            var nhex = nhi.toString(16) + ('00000000' + nlo.toString(16)).substr(-8);
            n = parseInt(nhex, 16).toString(10);
        } else {
            n = nlo.toString(10);
        }
    } else if( f.type == 'flagspiece' && f.mask != null ) {
        n = 0;
        // By starting with the existing value, not all bits have to be specified in each form.
        // This was created for Members/Dealers/Distributors in customers
        var s = this.sections[this.formFieldSection(f)];
        n = this.fieldValue(s, f.field, f);
        if( typeof(n) == 'string' ) {
            n = parseInt(n, 10);
        }
        if( n == null || n == '' ) { n = 0; }
        var nhi = n.toString(16);
        var nlo = nhi.substr(-8);
        nhi = nhi.length > 8 ? nhi.substr(0, nhi.length - 8) : '0';
        nlo = parseInt(nlo, 16);
        nhi = parseInt(nhi, 16);
        for(j in f.flags) {
            if( f.flags[j] == null ) { continue; }
            if( f.flags[j].active != null && f.flags[j].active == 'no' ) { continue; }
            if( M.gE(this.panelUID + '_' + fid + '_' + j).className == 'flag_on' ) {
                // Toggle bit on
                //n |= Math.pow(2, j-1);
                if( j > 32 ) {
                    nhi |= Math.pow(2, j-33);
                } else {
                    nlo |= Math.pow(2, j-1);
                }
            } else {
                // Toggle bit off
                //if( (n&Math.pow(2, j-1)) > 0 ) { n ^= Math.pow(2, j-1); }
                if( j > 32 ) {
                    if( (nhi&Math.pow(2, j-33)) > 0 ) { nhi ^= Math.pow(2, j-33); }
                } else {
                    if( (nlo&Math.pow(2, j-1)) > 0 ) { nlo ^= Math.pow(2, j-1); }
                }
            }
        }
        if( nhi > 0 ) {
            var nhex = nhi.toString(16) + ('00000000' + nlo.toString(16)).substr(-8);
            n = parseInt(nhex, 16).toString(10);
        } else {
            n = nlo.toString(10);
        }
    } else if( f.type == 'multitoggle' || f.type == 'toggle' ) {
        n = 0;
        for(j in f.toggles) {
            var e = M.gE(this.panelUID + '_' + fid + '_' + j);
            if( e != null && e.className == 'toggle_on' ) {
                n = j;
            }
        }
    } else if( f.type == 'flagtoggle' ) {
        n = 0;
        var on = M.gE(this.panelUID + '_' + fid + '_on');
        if( on != null && on.className == 'toggle_on' ) {
            return 'on';
        } else {
            return 'off';
        }
    } else if( f.type == 'multiselect' ) {
        n = '';
        c = '';
        for(j in f.options) {
            if( M.gE(this.panelUID + '_' + fid + '_' + j).className == 'toggle_on' ) {
                n += c + j;
                c = ',';
            }
        }
    } else if( f.type == 'idlist' ) {
        n = '';
        if( this.data['_' + fid] != null ) { var list = this.data['_' + fid]; } 
        if( f.list != null ) { var list = f.list; }
        var iname = (f.itemname!=null?f.itemname:'');
        for(j in list) {
            if( M.gE(this.panelUID + '_' + fid + '_' + j).className == 'toggle_on' ) {
                if( iname != '' ) {
                    n += (n!=''?',':'') + list[j][iname].id;
                } else {
                    n += (n!=''?',':'') + list[j].id;
                }
            }
        }
    } else if( f.type == 'collection' ) {
        n = '';
        if( this.data['_' + fid] != null ) { var collections = this.data['_' + fid]; } 
        if( f.collections != null ) { var collections = f.collections; }
        for(j in collections) {
            if( M.gE(this.panelUID + '_' + fid + '_' + j).className == 'toggle_on' ) {
                n += (n!=''?',':'') + collections[j].collection.id;
            }
        }
    } else if( f.type == 'tags' ) {
        n = '';
        c = '';
//        var v = M.gE(this.panelUID + '_' + fid + '_new').value;
//        if( v == null ) { v = ''; }
        v = '';
        if( this.tags != null && this.tags[fid] != null ) {
            var tags = this.tags[fid];
        } else {
            var tags = f.tags;
        }
        for(j in tags) {
            if( v != '' && v < tags[j] && v != tags[j] ) {
                n += c + v;
                c = '::';
                v = '';
            }
            if( M.gE(this.panelUID + '_' + fid + '_' + j).className == 'toggle_on' ) {
                n += c + tags[j];
                c = '::';
            }
        }
        if( v != '' ) {
            n += c + v;
        }
    } else if( f.type == 'timeduration' ) {
        n = M.gE(this.panelUID + '_' + fid).value;
        if( n.match(/:/) ) {
            var t = n.split(/:/);
            n = (parseInt(t[0])*60) + parseInt(t[1]);
        }
    } else if( f.type == 'htmlarea' ) {
        n = tinymce.get(this.panelUID + '_' + fid).getContent();
        n = n.replace(/<br \/>/g, "\n");
    } else {
        var e = M.gE(this.panelUID + '_' + fid);
        if( e != null && e.value != null ) {
            n = e.value;
        }
    }

    return n;
};

//
// This function will retrieve the current value for an input field
//
M.panel.prototype.formValue = function(field) {
    return this.formFieldValue(this.formField(field), field);
};

M.panel.prototype.clearData = function() {
    this.data = null;
};

M.panel.prototype.addData = function(new_data) {
    //
    // Check if there is already any data
    //
    var last_id = 0;
    if( this.data == null ) {
        this.data = [];
    } else {
        last_id = this.data.length;
    }

    var t = M.gE(this.panelUID + '_grid');
    var tb = null;
    if( t != null ) {
        tb = t.getElementsByTagName('tbody')[0];
    }

    //
    // Add new data both to the data array, and to the panel HTML
    //
    for(i in new_data) {
        //
        // Add it to the data array
        //
        this.data[last_id + i] = new_data[i];
        
        //
        // Then add it to the grid, if the table is setup
        //
        if( tb != null && this.type == 'simplegrid' ) {
            var tr = M.aE('tr');
            for(var j=0;j<this.num_cols;j++) {
                var cl = null;
                if( this.cellClass != null ) { cl = this.cellClass(i, j, this.data[i]); }
                tr.appendChild(M.aE('td', null, cl, this.cellValue(i, j, new_data[i])));
            }
            if( this.dataOrder == 'reverse' ) {
                tb.insertBefore(tr, tb.firstChild);
                // tb.appendChild(tr);    
            } else {
                tb.appendChild(tr);    
            }
        }
    }
};

M.panel.prototype.saveData = function() {
    var f = this;
    var org_data = {};
    for(i in this.sections) {
        var s = this.sections[i];
        for(var j in s.fields) {
            if( s.type == 'gridform' ) {
                for(k in s.fields[j]) {
                    var fid = s.fields[j][k].id;
                    org_data[fid] = this.fieldValue(i, fid, s.fields[j][k]);
                    if( s.fields[j][k].active == null || s.fields[j][k].active == 'yes' ) {
                        var n = this.formFieldValue(s.fields[j][k], fid);
                        if( n != null ) {
                            this.storeFieldValue(i,fid, n, j);
                        }
                    }
                }
            } else if( s.multi != null && s.multi == 'yes' ) {
                var c = this.sectionCount(i);
                for(k=0;k<c;k++) {
                    org_data[j+'_'+k] = this.fieldValue(i, j, s.fields[j], k);
                    if( s.fields[j].active == null || s.fields[j].active == 'yes' ) {
                        var n = this.formFieldValue(s.fields[j], j+'_'+k);
                        // Store the new field value in the data array, so the panel refresh uses new value
                        if( n != null ) {
                            this.storeFieldValue(i, j, n, k);
                        }
                    }
                }
            } else {
                org_data[j] = this.data[j];
                // Get the new data if the field is active
                if( s.fields[j].active == null || s.fields[j].active == 'yes' ) {
                    var n = this.formFieldValue(s.fields[j], j);
                    if( n != null ) {
                        this.data[j] = n;
                        if( j == 'appointment_duration') {
                            if( n == '1440' || n == '24:00' ) { 
                                this.data.appointment_duration_allday = 'yes'; 
                                this.data.appointment_date_date = this.data.appointment_date;
                            }
                            else { this.data.appointment_duration_allday = 'no'; }
                        }
                        else if( j == 'due_duration') {
                            if( n == '1440' || n == '24:00' ) { 
                                this.data.due_duration_allday = 'yes'; 
                                this.data.due_date_date = this.data.due_date;
                            }
                            else { this.data.due_duration_allday = 'no'; }
                        }
                    }
                }
            }
        }
    }
    this.org_data = org_data;
};

//
// This function will allow the form to be switched for another, while preserving
// the original data, and copying any changes to the new form
//
// Vars:
// f - form object
// form_id - the id selected
M.panel.prototype.switchForm = function(form, fv) {
    var f = this;
    // Save the data currently entered
    var org_data = {};
    for(i in this.sections) {
        var s = this.sections[i];
        if( s.active != null && s.active == 'no' ) {
            continue;
        }
        for(var j in s.fields) {
            if( s.type == 'gridform' ) {
                for(k in s.fields[j]) {
                    var fid = s.fields[j][k].id;
                    org_data[fid] = this.fieldValue(i, fid, s.fields[j][k]);
                    if( s.fields[j][k].active == null || s.fields[j][k].active == 'yes' ) {
                        var n = this.formFieldValue(s.fields[j][k], fid);
                        if( n != null ) {
                            this.storeFieldValue(i,fid, n, j);
                        }
                    }
                }
            } else if( s.multi != null && s.multi == 'yes' ) {
                var c = this.sectionCount(i);
                for(k=0;k<c;k++) {
                    org_data[j+'_'+k] = this.fieldValue(i, j, s.fields[j], k);
                    if( s.fields[j].active == null || s.fields[j].active == 'yes' ) {
                        var n = this.formFieldValue(s.fields[j], j+'_'+k);
                        // Store the new field value in the data array, so the panel refresh uses new value
                        if( n != null ) {
                            this.storeFieldValue(i, j, n, k);
                        }
                    }
                }
            } else {
                if( this.data[j] != null ) {
                    org_data[j] = this.data[j];
                }
                if( s.fields[j].type == 'flagtoggle' ) {
                    if( org_data[s.fields[j].field] == null ) {
                        org_data[s.fields[j].field] = 0;
                    }
                    if( n == 'on' ) {
                        org_data[s.fields[j].field] |= s.fields[j].bit;
                    }
                }
                // Get the new data if the field is active
                if( s.fields[j].active == null || s.fields[j].active == 'yes' ) {
                    var n = this.formFieldValue(s.fields[j], j);
                    if( n != null ) {
                        this.data[j] = n;
                        if( j == 'appointment_duration') {
                            if( n == '1440' || n == '24:00' ) { 
                                this.data.appointment_duration_allday = 'yes'; 
                                this.data.appointment_date_date = this.data.appointment_date;
                            }
                            else { this.data.appointment_duration_allday = 'no'; }
                        }
                        else if( j == 'due_duration') {
                            if( n == '1440' || n == '24:00' ) { 
                                this.data.due_duration_allday = 'yes'; 
                                this.data.due_date_date = this.data.due_date;
                            }
                            else { this.data.due_duration_allday = 'no'; }
                        }
                        if( s.fields[j].type == 'flagtoggle' ) {
                            if( this.data[s.fields[j].field] == null ) {
                                this.data[s.fields[j].field] = 0;
                            }
                            if( n == 'on' ) {
                                this.data[s.fields[j].field] |= s.fields[j].bit;
                            } else {
                                this.data[s.fields[j].field] = this.data[s.fields[j].field]&~s.fields[j].bit;
                            }
                        }
                    }
                }
            }

        }
    }

    //
    // Set the new ID
    //
    this.formtab = form;

    // Set the field_id value if specified.  If not specified, then basic formtabs.
    this.formtab_field_id = fv;

    //
    // Set the new form
    //
    this.sections = this.forms[form];

    this.refresh();
    this.show();

    // Reset to the original data before form swap.
    // this is required so when the form is saved, it recognizes the difference between old and new data.
    for(i in this.sections) {
        var s = this.sections[i];
        for(var j in s.fields) {
            if( s.multi != null && s.multi == 'yes' ) {
                var c = this.sectionCount(i);
                for(k=0;k<c;k++) {
                    if( (s.fields[j].active == null || s.fields[j].active == 'yes') && org_data[j+'_'+k] != null ) {
                        // Reset the data value to the original one retrieved from the API
                        this.storeFieldValue(i, j, org_data[j+'_'+k], k);
                    }
                }
            } else {
                if( org_data[j] != null ) {
                    this.data[j] = org_data[j];
                }
                if( s.fields[j].type == 'flagtoggle' ) {
                    this.data[s.fields[j].field] = org_data[s.fields[j].field];
                } else if( s.fields[j].type == 'flagspiece' && f.field != null ) {
                    this.data[s.fields[j].field] = org_data[s.fields[j].field];
                }
            }
        }
    }
};

M.panel.prototype.isVisible = function() {
    //
    // Check the panel, app, app container, and m_container are displayed
    //
    if( M.gE(this.panelUID) != null 
        && M.gE(this.panelUID).style.display != 'none'
        && M.gE(this.appID).style.display != 'none'
        && M.gE('m_container').style.display != 'none' 
        ) {
        return true;
    }
    return false;
};

//
// This function will remove the DOM element from the page
//
M.panel.prototype.destroy = function() {
    //
    // Remove the panel
    //
    var d = M.gE(this.panelUID);
    if( d != null ) {
        d.parentNode.removeChild(d);
    }
};

//
// This function will destory any visible HTML panels, and 
// remove all the data.
//
M.panel.prototype.reset = function() {
    this.gstep = 0;
    if( this.formtab_field_id != null ) {
        this.formtab_field_id = null;
    }
    if( this.formtab != null ) {
        delete(this.formtab);
    }
    this.destroy();
    this.clearData();
};

//
// Handle drag/drop images into simplethumbs gallery
//
M.panel.prototype.uploadDropImages = function(e, p, s) {
    if( p == null ) {
        p = this;
    }

    M.startLoad();

    //
    // Check for external files dropped
    //
    var files = null;
    p._uploadAddDropImage = null;
    p._uploadAddDropImageRefresh = null;
    if( typeof(e) == 'string' ) {
        // Add Photo button used
        var f = M.gE(this.panelUID + '_' + e + '_upload');
        files = f.files;
        var field = p.formField(e);
        if( field != null ) {
            if( field.addDropImage != null ) {
                p._uploadAddDropImage = field.addDropImage;
            }
        }
    } else if( e.dataTransfer != null ) {
        // Photos dropped on browser
        e.stopPropagation();
        files = e.dataTransfer.files;
        // If a section is specified, see if there is a specific addDropImage funciton
        // This allows for multiple sections to accept dropped images
        if( s != null && p.sections[s] != null ) {
            for(i in p.sections[s].fields) {
                if( p.sections[s].fields[i].addDropImage != null ) {
                    p._uploadAddDropImage = p.sections[s].fields[i].addDropImage;
                }
            }
        }
    }

    // Build the list of files to be uploaded.
    if( files != null ) {
        p._uploadCount = 0;
        p._uploadCurrent = 0;
        p._uploadFiles = [];
        for(i in files) {
            if( files[i].type == null ) { continue; }
            if( files[i].type != 'image/jpeg' 
                && files[i].type != 'image/png'
// Remove following lines as causing crashes
//                && files[i].fileName.match(/.JPG$/) == null
//                && files[i].fileName.match(/.jpg$/) == null
                ) {
                alert("I'm sorry, we only allow jpeg images to be uploaded.");
                M.stopLoad();
                return false;
            }
            p._uploadFiles[p._uploadCount] = files[i];
            p._uploadCount++;
        }
        if( p._uploadCount > 0 ) {
            p.uploadDropImagesNext();
        } else {
            alert("I'm sorry, we couldn't add that photo, please use the Add Photo button."); 
            M.stopLoad();
        }
    } else {
        alert("I'm sorry, we couldn't add that photo, please use the Add Photo button."); 
        M.stopLoad();
    }
};

M.panel.prototype.uploadDropImagesNext = function() {
    var p = this;
    // Check if we are done with upload
    if( p._uploadCurrent == p._uploadCount ) {
        M.stopLoad();
        p._uploadCurrent = 0;
        p._uploadCount = 0;
        p._uploadFiles = [];
        if( p._uploadAddDropImageRefresh != null ) {
            p._uploadAddDropImageRefresh();
        } else if( p.addDropImageRefresh != null ) {
            p.addDropImageRefresh();
        }
        return true;
    }

    // Upload next image
    if( p.addDropImageAPI == null ) {
        p.addDropImageAPI = 'ciniki.images.add';
    }
    var rsp = M.api.postJSONFile(p.addDropImageAPI, 
        {'tnid':M.curTenantID}, 
        p._uploadFiles[p._uploadCurrent],  // File
        function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.stopLoad();
                M.api.err(rsp);
                return false;
            } // else {
            if( p._uploadAddDropImage != null ) {
                if( !p._uploadAddDropImage(rsp.id) ) {
                    M.stopLoad();
                    return false;
                }
            } else if( !p.addDropImage(rsp.id) ) {
                M.stopLoad();
                return false;
            }
//            }
            p._uploadCurrent++;
            p.uploadDropImagesNext();
        });
    if( rsp == null ) {
        alert('Unknown error occured, please try again');
        M.stopLoad();
        return false;
    }
//    if( rsp.stat != 'ok' ) {
//        M.stopLoad();
//        M.api.err(rsp);
//        return false;
//    }
};

//
// Handle drag/drop images into simplethumbs gallery
//
M.panel.prototype.uploadDropFiles = function(e, p, s) {
    if( p == null ) {
        p = this;
    }

    M.startLoad();

    //
    // Check for external files dropped
    //
    var files = null;
    p._uploadAddDropFile = null;
    p._uploadAddDropFileRefresh = null;
    if( typeof(e) == 'string' ) {
        // Add File button used
        var f = M.gE(this.panelUID + '_' + e + '_upload');
        files = f.files;
        var field = p.formField(e);
        if( field != null ) {
            if( field.addDropFile != null ) {
                p._uploadAddDropFile = field.addDropFile;
            } else if( p.addDropFile != null ) {
                p._uploadAddDropFile = p.addDropFile;
            }
        }
        p._uploadAddDropFileField = e;
        p._uploadAddDropFileSection = p.formFieldSection(e);
    } else if( e.dataTransfer != null ) {
        // Photos dropped on browser
        e.stopPropagation();
        files = e.dataTransfer.files;
        // If a section is specified, see if there is a specific addDropFile funciton
        // This allows for multiple sections to accept dropped images
        if( s != null && p.sections[s] != null ) {
            for(i in p.sections[s].fields) {
                if( p.sections[s].fields[i].addDropFile != null ) {
                    p._uploadAddDropFile = p.sections[s].fields[i].addDropFile;
                    p._uploadAddDropFileSection = s;
                    p._uploadAddDropFileField = i;
                }
            }
        }
    }

    // Build the list of files to be uploaded.
    if( files != null ) {
        p._uploadCount = 0;
        p._uploadCurrent = 0;
        p._uploadFiles = [];
        for(i in files) {
            if( files[i].type == null ) { continue; }
            // FIXME: Change to be controlled by form
            if( files[i].type != 'audio/mpeg' 
                && files[i].type != 'audio/mp3'
                && files[i].type != 'audio/ogg'
                && files[i].type != 'audio/vnd.wave'
                && files[i].type != 'audio/wav'
                ) {
                alert("I'm sorry, we only do not currently allow that format.");
                M.stopLoad();
                return false;
            }
            p._uploadFiles[p._uploadCount] = files[i];
            p._uploadCount++;
        }
        if( p._uploadCount > 0 ) {
            p.uploadDropFilesNext();
        }
    }
};

M.panel.prototype.uploadDropFilesNext = function() {
    var p = this;
    // Check if we are done with upload
    if( p._uploadCurrent == p._uploadCount ) {
        M.stopLoad();
        p._uploadCurrent = 0;
        p._uploadCount = 0;
        p._uploadFiles = [];
        if( p._uploadAddDropFileRefresh != null ) {
            p._uploadAddDropFileRefresh();
        } else if( p.addDropFileRefresh != null ) {
            p.addDropFileRefresh();
        }
        return true;
    }
    // Upload next image
    if( p._uploadFiles[p._uploadCurrent].type.match(/audio\/.*/) ) {
        p.addDropFileAPI = 'ciniki.audio.add';
    }
    var rsp = M.api.postJSONFile(p.addDropFileAPI, 
        {'tnid':M.curTenantID}, 
        p._uploadFiles[p._uploadCurrent],  // File
        function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.stopLoad();
                M.api.err(rsp);
                return false;
            } 
            if( p._uploadAddDropFile != null ) {
                if( !p._uploadAddDropFile(p._uploadAddDropFileSection, p._uploadAddDropFileField, rsp.id,p._uploadFiles[p._uploadCurrent]) ) {
                    M.stopLoad();
                    return false;
                }
            } else if( !p.addDropFile(p._uploadAddDropFileSection, p._uploadAddDropFileField, rsp.id, p._uploadFiles[p._uploadCurrent]) ) {
                M.stopLoad();
                return false;
            }
            p._uploadCurrent++;
            p.uploadDropFilesNext();
        });
    if( rsp == null ) {
        alert('Unknown error occured, please try again');
        M.stopLoad();
        return false;
    }
};

M.panel.prototype.rotateImg = function(fid, dir) {
    var iid = this.formValue(fid);
    var p = this;
    var rsp = M.api.getJSONCb('ciniki.images.rotate', {'tnid':M.curTenantID,
        'image_id':iid, 'direction':dir}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            p.updateImgPreview(fid, iid);
        });
};
