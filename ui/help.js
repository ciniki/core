//
// This class will display the form to allow admins and tenant owners to 
// change the details of their tenant
//
function ciniki_core_help() {
    //
    // Panels
    //
    this.curHelpUID = '';
    this.toggles = {'no':'Off', 'yes':'On'};

    this.init = function() {
        //
        // Bug panel
        //
        this.bug = new M.panel('Bug/Feature/Question',
            'ciniki_core_help', 'bug',
            'mh', 'medium', 'sectioned', 'ciniki.core.help.question');
        this.bug.data = null;
        this.bug.users = null;
        this.bug.sections = {
            'thread':{'label':'', 'type':'simplethread'},
            'followup':{'label':'Add your response', 'fields':{
                'content':{'label':'Details', 'hidelabel':'yes', 'type':'textarea'},
            }},
            '_buttons':{'label':'', 'buttons':{
                'add':{'label':'Submit response', 'fn':'M.ciniki_core_help.addFollowup();'},
            }},
        };
        this.bug.subject = '';
        this.bug.noData = function() { return 'Not yet implemented'; }
        this.bug.sectionData = function(s) {
            if( s == 'thread' ) {
                return this.data;
            }
            return this.data[s];
        }
        this.bug.fieldValue = function(s, i, d) { return ''; }
        this.bug.threadSubject = function() { return this.subject; }
        this.bug.threadFollowupUser = function(s, i, d) { return d.followup.user_display_name; }
        this.bug.threadFollowupAge = function(s, i, d) { return d.followup.age; }
        this.bug.threadFollowupContent = function(s, i, d) { return d.followup.html_content; }
        this.bug.addClose('Back');

        //
        // Questions listing panel, includes bugs and features
        //
        this.list = new M.panel('Help',
            'ciniki_core_help', 'list',
            'mh', 'medium', 'sectioned', 'ciniki.core.help.list');
        this.list.data = {'type':'3'};
        this.list.sections = {
            'bugs':{'label':'', 'visible':'yes', 'type':'simplelist'},
            'main':{'label':'Ask a question', 'fields':{
                'subject':{'label':'Subject', 'hidelabel':'yes', 'type':'text'},
                }},
            '_followup':{'label':'Details', 'fields':{
                'followup':{'label':'Details', 'hidelabel':'yes', 'type':'textarea'},
            }},
            '_buttons':{'label':'', 'buttons':{
                'add':{'label':'Submit Question', 'fn':'M.ciniki_core_help.submitBug();'},
            }},
            '_ui_options_':{'label':'', 'type':'html', 'html':'The following extended help settings are currently experimental.'
                + ' They do not work everywhere in the Ciniki Manager, we are currenlty in the process of adding more support.'
                + ' If you have any problems, please ask a question above. '
                + ' You can change these settings anytime by opening help.',
                'visible':function() { return ((M.uiModeGuided != null && M.uiModeGuided == 'yes') || (M.uiModeXHelp != null && M.uiModeXHelp == 'yes') ) ? 'yes' : 'no' ; },
                },
            '_ui_options':{'label':'Extended Help', 
                'visible':function() { return ((M.uiModeGuided != null && M.uiModeGuided == 'yes') || (M.uiModeXHelp != null && M.uiModeXHelp == 'yes') ) ? 'yes' : 'no' ; },
                'fields':{
                    'ui-mode-guided':{'label':'Guided Mode', 'type':'toggle', 'fn':'M.ciniki_core_help.updateModeGuided', 'toggles':this.toggles},
                    'ui-mode-xhelp':{'label':'Field Descriptions', 'type':'toggle', 'fn':'M.ciniki_core_help.updateModeXHelp', 'toggles':this.toggles},
                }},
        };
        this.list.sectionData = function(s) { 
            if( s == '_ui_options_' ) {
                return this.sections[s].html;
            }
            return this.data[s]; 
        };
        this.list.fieldValue = function(s, i, d) { 
            if( s == '_ui_options' && i == 'ui-mode-guided' ) {
                return M.uiModeGuided;
            }
            if( s == '_ui_options' && i == 'ui-mode-xhelp' ) {
                return M.uiModeXHelp;
            }
            return ''; }
        this.list.listValue = function(s, i, d) { return d.bug.subject; }
        this.list.listFn = function(s, i, d) { return 'M.ciniki_core_help.showBug(\'' + i + '\');'; }
        this.list.addButton('exit', 'Close', 'M.toggleHelp(null);'); 

        //
        // Questions listing panel, includes bugs and features
        //
        this.online = new M.panel('Help', 'ciniki_core_help', 'online', 'mh', 'medium', 'sectioned', 'ciniki.core.help.online');
        this.online.data = {};
        this.online.sections = {
            '_msg':{'label':'', 'type':'htmlcontent', 'html':'All help is available at <a target=_blank href="' + M.helpURL + '">' + M.helpURL + '</a>.'},
        };
        this.online.sectionData = function(s) { 
            if( s == '_msg' ) {
                return this.sections[s].html;
            }
            return this.data[s]; 
        };
        this.online.open = function(cb) {
            this.refresh();
            this.show();
        }
        this.online.addButton('exit', 'Close', 'M.toggleHelp(null);'); 
    }

    //
    // The panel to display the internal embedded help
    //
    this.internal = new M.panel('Help', 'ciniki_core_help', 'internal', 'mh', 'medium', 'sectioned', 'ciniki.core.help.internal');
    this.internal.data = {};
    this.internal.sections = {
        '_msg':{'label':'', 'type':'html', 'html':'INTERNAL HELP'},
    };
    this.internal.open = function(cb) {
        this.sections = {};
        var pieces = M.curHelpUID.split('.');
        var p = M[pieces[0] + '_' + pieces[1] + '_' + pieces[2]];
        if( p != null && p[pieces[3]] != null ) {
            p = p[pieces[3]];
        }
        if( p != null && p.helpSections != null ) {
            if( typeof p.helpSections == 'function' ) {
                this.sections = p.helpSections();
            } else {
                this.sections = p.helpSections;
            }
        }
        this.sections['_msg'] = {'label':'Additional Help', 'type':'htmlcontent', 'html':'More help is available at <a target=_blank href="' + M.helpURL + '">' + M.helpURL + '</a>.'};
        this.refresh();
        this.show();
    }
    this.internal.addButton('exit', 'Close', 'M.toggleHelp(null);'); 

    //
    // The panel to display the custom tenant specific help
    //
    this.custom = new M.panel('Help', 'ciniki_core_help', 'internal', 'mh', 'medium', 'sectioned', 'ciniki.core.help.custom');
    this.custom.data = {};
    this.custom.sections = {
//        '_content':{'label':'', 'scrollHeight':'80vh', 'fields':{   
//            'content':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'editable':'yes', 'size':'large'},
//            }},
        'content_display':{'label':'', 'scrollHeight':'80vh', 'type':'html'},
    };
    this.custom.open = function(cb) {
        //
        // Clear existing help
        //
        this.data.content = 'Loading...';
        this.refresh();
        this.show(cb);

        //
        // Load the custom help for this panel
        //
        M.api.getJSONBgCb('ciniki.tenants.uihelpGet', {'tnid':M.curTenantID, 'helpUID':M.ciniki_core_help.curHelpUID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_core_help.custom;
            p.data = rsp.content;
            p.delButton('edit');
            if( M.curTenant.permissions.owners != null && M.curTenant.permissions.owners == 'yes' ) {
                p.addLeftButton('edit', 'Edit', 'M.ciniki_core_help.customedit.open(\'M.ciniki_core_help.custom.open();\',\'' + M.ciniki_core_help.curHelpUID + '\');'); 
            }
            p.refresh();
            p.show();
        });
    }
    this.custom.addButton('exit', 'Close', 'M.toggleHelp(null);'); 

    //
    // The panel to edit UI Help
    //
    this.customedit = new M.panel('Edit Help', 'ciniki_core_help', 'customedit', 'mh', 'medium', 'sectioned', 'ciniki.core.help.customedit');
    this.customedit.data = null;
    this.customedit.helpuid = '';
    this.customedit.sections = {
        'general':{'label':'', 'fields':{
            'content':{'label':'Content', 'hidelabel':'yes', 'required':'yes', 'type':'textarea', 'size':'xlarge', 'history':'no'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_core_help.customedit.save();'},
            }},
        };
    this.customedit.fieldValue = function(s, i, d) { return this.data[i]; }
    this.customedit.open = function(cb, hid) {
        if( hid != null ) { this.helpuid = hid; }
        M.api.getJSONCb('ciniki.tenants.uihelpGet', {'tnid':M.curTenantID, 'helpUID':this.helpuid}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_core_help.customedit;
            p.data = rsp.content;
            p.refresh();
            p.show(cb);
        });
    }
    this.customedit.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_core_help.customedit.close();'; }
        if( !this.checkForm() ) { return false; }
        var c = this.serializeForm('yes');
        if( c != '' ) {
            M.api.postJSONCb('ciniki.tenants.uihelpUpdate', {'tnid':M.curTenantID, 'helpUID':this.helpuid}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                eval(cb);
            });
        } else {
            eval(cb);
        }
    }
    this.customedit.addButton('save', 'Save', 'M.ciniki_core_help.customedit.save();');
    this.customedit.addClose('Cancel');

    
    //
    // Arguments:
    // aG - The arguments to be parsed into args
    //
    this.start = function(cb, appPrefix, aG) {
        args = {};
        if( aG != null ) { args = eval(aG); }

        //
        // Create the app container if it doesn't exist, and clear it out
        // if it does exist.
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_core_help', 'yes');
        if( appContainer == null ) {
            M.alert('App Error');
            return false;
        } 

        //
        // Load items from database
        //
        if( args['helpUID'] != null ) {
            this.curHelpUID = args['helpUID'];
        }

//        this.list.sections._ui_options.visible = 'yes';
        this.list.data['ui-mode-guided'] = M.uiModeGuided;
        this.list.data['ui-mode-xhelp'] = M.uiModeXHelp;
        if( M.curHelpUID != 'ciniki.core.menu.tenants' && M.modFlagOn('ciniki.tenants', 0x0200) ) {
            this.custom.open(cb);
        } else if( M.helpMode != null && M.helpMode == 'internal' ) {
            this.internal.open(cb);
        } else if( M.helpMode != null && M.helpMode == 'online' && M.helpURL != null ) {
            this.online.open(cb);
        } else {
            this.loadBugs();
        }
    }

    this.reset = function() {
        this.list.reset();
        this.bug.reset();
    }


    //
    // The showHelp is called each time the panel is changed
    //
    this.showHelp = function(helpUID) {
        //
        // Check if edit is open
        //
        if( M.curHelpUID != 'ciniki.core.menu.tenants' && M.modFlagOn('ciniki.tenants', 0x0200) ) {
            var e = M.gE(M.ciniki_core_help.customedit.panelUID);
            if( e != null && e.style.display != 'none' ) {
                M.ciniki_core_help.customedit.save('');
            }
        }

        //
        // Set the helpUID which is to be shown.  This variable is used
        // by the bugs and features apps
        //
        this.curHelpUID = helpUID;

        //
        // Remove any panels which won't be useful when help context is switched
        //

        //
        // Figure out what panel is active, and refresh that panel
        //
        // if question panel, return to list.  The displayed questions won't be valid
        // when we switch context
        // if bug, display bugs
        // if feature, display feature requests
        // if menu, ask, report or request, leave along
        //
        if( M.curHelpUID != 'ciniki.core.menu.tenants' && M.modFlagOn('ciniki.tenants', 0x0200) ) {
            this.custom.open();
        } else if( M.helpMode != null && M.helpMode == 'internal' ) {
            this.internal.open();
        } else if( M.helpMode != null && M.helpMode == 'online' && M.helpURL != null ) {
            this.online.open();
        } else if( this.bug.isVisible() == true || this.list.isVisible() == true ) {
            this.loadBugs(helpUID);    
            if( M.ciniki_help_bugs != null ) { M.ciniki_help_bugs.reset(); }
            if( M.ciniki_help_features != null ) { M.ciniki_help_features.reset(); }
        } else if( M.ciniki_help_bugs != null 
            && (M.ciniki_help_bugs.bug.isVisible() == true || M.ciniki_help_bugs.bugs.isVisible() == true) ) {
            M.ciniki_help_bugs.showBugs();    
            this.list.reset();
            if( M.ciniki_help_features != null ) { M.ciniki_help_features.reset(); }
        } else if( M.ciniki_help_features != null 
            && (M.ciniki_help_features.feature.isVisible() == true || M.ciniki_help_features.features.isVisible() == true) ) {

            M.ciniki_help_features.showFeatures();    
            this.list.reset();
            if( M.ciniki_help_bugs != null ) { M.ciniki_help_bugs.reset(); }
        }
    }

    this.close = function() {
        this.bug.reset();
        this.list.close();
    }

    this.submitBug = function() {
        var c = this.list.serializeForm('yes');
        if( c == '' ) {
            M.alert("Nothing specified");
            return false;
        }

        M.api.postJSONCb('ciniki.bugs.bugAdd',
            {'tnid':M.masterTenantID, 'source':'ciniki-manage', 'source_link':M.ciniki_core_help.curHelpUID},
            c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_core_help.loadBugs();
            });
    };

    this.showBugs = function() {
        this.bug.reset();
        this.loadBugs();
    }

    this.loadBugs = function() {
        M.api.getJSONCb('ciniki.bugs.bugList', {'tnid':M.masterTenantID, 'status':'1',
            'source':'ciniki-manage', 'source_link':M.ciniki_core_help.curHelpUID}, function(rsp) {
                if( rsp.stat != 'ok' ) { 
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_core_help.list;
                if( rsp.bugs != null && rsp.bugs.length > 0 ) {
                    p.sections.bugs.visible = 'yes';
                } else {
                    p.sections.bugs.visible = 'no';
                }
                p.data = rsp;
                p.refresh();
                p.show();
            });
    }   

    this.showBug = function(bNUM) {
        //  
        // Reset the panel, incase there was existing data from another question
        //  
        if( bNUM != null ) {
            this.bug.bNUM = bNUM;
        } else {
            bNUM = this.bug.bNUM;
        }
        this.bug.reset();
        this.bug.subject = this.list.data.bugs[bNUM].bug.subject;

        //  
        // Setup the details for the question
        //  
        M.api.getJSONCb('ciniki.bugs.bugGetFollowups', {'tnid':M.masterTenantID, 
            'bug_id':this.list.data.bugs[bNUM].bug.id}, function(rsp) {
                if( rsp.stat != 'ok' ) { 
                    M.api.err(rsp);
                    this.bug.data = null;
                }   
                var p = M.ciniki_core_help.bug;
                p.bug_id = M.ciniki_core_help.list.data.bugs[bNUM].bug.id;
                p.data = rsp.followups;
                p.users = rsp.users;
                p.refresh();
                p.show('M.ciniki_core_help.list.show();');
            });
    }

    this.addFollowup = function() {
        var c = this.bug.serializeForm('yes');

        if( c != '' ) {
            var rsp = M.api.postJSONCb('ciniki.bugs.bugAddFollowup', 
                {'tnid':M.masterTenantID, 'bug_id':this.bug.bug_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) { 
                        M.api.err(rsp);
                        return false;
                    }   
                    M.ciniki_core_help.showBug();
                });
        } else {
            M.ciniki_core_help.showBug();
        }
    }

    this.updateModeGuided = function(f, a, b) {
        if( b == 'toggle_on' && M.uiModeGuided != 'yes' ) {
            M.api.getJSONBg('ciniki.users.updateDetails', {'user_id':M.userID, 'ui-mode-guided':'yes'});
            M.toggleGuidedMode();
        } else {
            M.api.getJSONBg('ciniki.users.updateDetails', {'user_id':M.userID, 'ui-mode-guided':'no'});
            M.toggleGuidedMode();
        }
    }
    this.updateModeXHelp = function(f, a, b) {
        if( b == 'toggle_on' && M.uiModeXHelp != 'yes' ) {
            M.api.getJSONBg('ciniki.users.updateDetails', {'user_id':M.userID, 'ui-mode-xhelp':'yes'});
            M.toggleXHelpMode();
        } else {
            M.api.getJSONBg('ciniki.users.updateDetails', {'user_id':M.userID, 'ui-mode-xhelp':'no'});
            M.toggleXHelpMode();
        }
    }
}
