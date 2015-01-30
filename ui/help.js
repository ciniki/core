//
// This class will display the form to allow admins and business owners to 
// change the details of their business
//
function ciniki_core_help() {
	//
	// Panels
	//
	this.curHelpUID = '';

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
        this.bug.fieldValue = function(s, i, d) { return ''; }
        this.bug.threadSubject = function() { return this.subject; }
        this.bug.threadFollowupUser = function(s, i, d) { return d['followup']['user_display_name']; }
        this.bug.threadFollowupAge = function(s, i, d) { return d['followup']['age']; }
        this.bug.threadFollowupContent = function(s, i, d) { return d['followup']['content']; }
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
			'_ui_options':{'label':'Extend Help', 'visible':'no', 'fields':{
				'ui-mode-guided':{'label':'Guided Mode', 'type':'toggle', 'fn':'M.ciniki_core_help.updateUI', 'toggles':{
					'no':'Off',
					'yes':'On',
					}},
				}},
		};
		this.list.sectionData = function(s) { return this.data[s]; };
        this.list.fieldValue = function(s, i, d) { 
			if( s == '_ui_options' && i == 'ui-mode-guided' ) {
				return M.uiModeGuided;
			}
			return ''; }
        this.list.listValue = function(s, i, d) { return d.bug.subject; }
        this.list.listFn = function(s, i, d) { return 'M.ciniki_core_help.showBug(\'' + i + '\');'; }
        this.list.addButton('exit', 'Close', 'M.toggleHelp(null);'); 
	}
	
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
			alert('App Error');
			return false;
		} 

		//
		// Load items from database
		//
		if( args['helpUID'] != null ) {
			this.curHelpUID = args['helpUID'];
		}

		if( (M.userPerms&0x01) > 0 ) {
			this.list.sections._ui_options.visible = 'yes';
			this.list.data['ui-mode-guided'] = M.uiModeGuided;
		} else {
			this.list.sections._ui_options.visible = 'no';
		}

		this.loadBugs();
	}

    this.reset = function() {
        this.list.reset();
        this.bug.reset();
    }


	this.showHelp = function(helpUID) {
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
		if( this.bug.isVisible() == true || this.list.isVisible() == true ) {
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
            alert("Nothing specified");
            return false;
        }

        var rsp = M.api.postJSONCb('ciniki.bugs.bugAdd',
            {'business_id':M.masterBusinessID, 'source':'ciniki-manage', 'source_link':M.ciniki_core_help.curHelpUID},
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
        var r = M.api.getJSONCb('ciniki.bugs.bugList', 
            {'business_id':M.masterBusinessID, 'status':'1',
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
        var r = M.api.getJSONCb('ciniki.bugs.bugGetFollowups', 
            {'business_id':M.masterBusinessID, 
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
				{'business_id':M.masterBusinessID, 'bug_id':this.bug.bug_id}, c, function(rsp) {
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

	this.updateUI = function(f, a, b) {
		if( b == 'toggle_on' && M.uiModeGuided != 'yes' ) {
			M.api.getJSONBg('ciniki.users.updateDetails', {'user_id':M.userID, 'ui-mode-guided':'yes'});
			M.toggleGuidedMode();
		} else {
			M.api.getJSONBg('ciniki.users.updateDetails', {'user_id':M.userID, 'ui-mode-guided':'no'});
			M.toggleGuidedMode();
		}
	}
}
