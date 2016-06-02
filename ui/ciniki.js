//
// This file contains javascript functions which are generic for now, but may be moved
// into device specific files
//

window.M = {
	'version':'150101.1134',
	'menus':{},
	'curMenu':'',
	'startMenu':'ciniki.core.menu',
	'businessMenu':'ciniki.businesses.main',
	'menuHome':null,
	'menuHistory':[],
	'masterBusinessID':0,
	'curBusinessID':0,
	'curBusiness':null,
	'curHelpUID':'',
	'loadCounter':0,
	'apps':{},
	'dropHooks':{},
	'userPerms':0,
	'panels':{},
	'expired':'no',
	'scroller':null,
	'startTime':0,
	'reauth_apiresume':null,
	'helpScroller':null}

M.init = function(cfg) {
	M.device = cfg.device;
	M.browser = cfg.browser; 
	M.engine = cfg.engine; 
	M.touch = cfg.touch; 
	M.size = cfg.size;
	M.uiModeGuided = 'no';
	M.uiModeXHelp = 'no';
	M.months = [
		{'shortname':'Jan'},
		{'shortname':'Feb'},
		{'shortname':'Mar'},
		{'shortname':'Apr'},
		{'shortname':'May'},
		{'shortname':'Jun'},
		{'shortname':'Jul'},
		{'shortname':'Aug'},
		{'shortname':'Sep'},
		{'shortname':'Oct'},
		{'shortname':'Nov'},
		{'shortname':'Dec'}
		];

//	if( window.navigator.standalone ) {
//		var m = M.gE('apple_sbarstyle');
//		if( m != null ) {
//			m.parentNode.remoteChild(m);
//			m.setAttribute('content', 'black');
//		}
//	}

	// M.hideChildren('m_body', 'm_login');
	M.api.url = cfg.api_url;
	M.api.key = cfg.api_key;
	M.masterBusinessID = cfg.master_id;
	M.manage_root_url = cfg.root_url;
	M.themes_root_url = cfg.themes_root_url;
	if( cfg.start_menu != null && cfg.start_menu != '' ) {
		M.startMenu = cfg.start_menu;
	}
	if( cfg.business_menu != null && cfg.business_menu != '' ) {
		M.businessMenu = cfg.business_menu;
	}
	M.defaultBusinessColours = M.gE('business_colours').innerHTML;
	if( cfg.modules != null ) {
		M.cfg = cfg.modules;
	} else {
		M.cfg = {};
	}

	M.ciniki = {};
	M.gridSorting = {};
	// window.cinikiAPI = new cinikiAPI(apiURL, apiKey);

	if( (M.device == 'iphone' || M.device == 'ipad' || M.device == 'android') && M.engine == 'webkit' ) {
		// document.addEventListener('touchmove', function(e){ e.preventDefault(); });
		// M.scroller = new iScroll('mc_content_scroller');
		// M.helpScroller = new iScroll('mc_help_scroller');
	}
	if( M.device == 'hptablet' && M.engine == 'webkit' ) {
		if (window.PalmSystem) window.PalmSystem.stageReady();
		window.PalmSystem.enableFullScreenMode(true);
	}

	document.addEventListener('dragover', function(e) { e.preventDefault(); }, false);   // Required for Chrome bug
	document.addEventListener('drop', M.dropHandler, false);

	//
	// Check if username and password were passed to script, and auto-login
	//
    var uts = M.cookieGet('_UTS');
    var utk = M.cookieGet('_UTK');
	if( cfg.auth_token != null ) {
		M.authToken(this, cfg.auth_token);
	} else if( uts != null && uts != '' && utk != null && utk != '' ) {
        M.authUserToken(this, uts, utk);
    } else {
        M.gE('m_login').style.display = ''; 
    }

	// Setup TinyMCE Editor
	tinyMCE.init({
		selector:false,
		inline:true,
		theme:'modern',
		schema:'html5',
		skin:'ciniki',
//		toolbar:["bold italic underline strikethrough"],
		toolbar:false,
		menubar:false,
		statusbar:false,
		forced_root_block:false,
		resize:true,
		});
}

M.preLoad = function(s) {
	var i = new Image;
	i.src=s;
}

//
// This function will clear a DOM element of all children
//
// Arguments:
// i - The ID to look for in the DOM
//
M.clr = function(i) {
	var e = null;
	if( typeof i == 'object' ) {
		e = i;
	} else  {
		e = M.gE(i);
	}
	if( e != null && e.children != null ) {
		while( e.children.length > 0 ) {
			e.removeChild(e.children[0]);
		}
	}
	return e;
}

M.show = function(i) {
	if( typeof i == 'object' ) {
		i.style.display = 'block';
	} else {
		M.gE(i).style.display = 'block';
	}
}

M.hide = function(i) {
	if( typeof i == 'object' ) {
		i.style.display = 'block';
	} else {
		M.gE(i).style.display = 'none';
	}
}

// 
// This function will hide all children of 'i' except
// for the one with the id e
//
// Arguments:
// i = The ID of the element with the children to hide
// e = The ID of the child element to remain visible
//
M.hideChildren = function(i,e) {

	if( typeof i == 'object' ) {
		var c = i.children;
	} else {
		var c = M.gE(i).children;
	}
	for(var i=0;i < c.length; i++) {
		if( e != null && c[i].id == e ) {
			c[i].style.display = 'block';
		} else if( c[i].id != null && c[i].id == 'm_loading' ) {
			// Do nothing
		} else {
			c[i].style.display = 'none';
		}
	};
	window.scroll(0,0);
}

//
// This function will load the javascript for an App and issue the start method on that javascript.  
// The backFunction will be run when the App closes.
//
// ciniki_startAppCallback = function(app, startFunction, callback) {
// Arguments:
// a - The application name 'mapp_businessOwners', etc...
// sF - The starting function, if different from .start().
// cB - The call back to issue when the app closes, this is used to return to another app instead of the menu.
//
M.startModalApp = function(a, sF, cB) {
	M.startApp(a, sF, cB);
}

//
// This function will load the javascript for an App and issue the start method on that javascript.  
// The backFunction will be run when the App closes.
//
// FIXME: Change this to create a window when an app is started, if running in windowed mode.
//
// ciniki_startAppCallback = function(app, startFunction, callback) {
// Arguments:
// a - The application name 'mapp_businessOwners', etc...
// sF - The starting function, if different from .start().
// cB - The call back to issue when the app closes, this is used to return to another app instead of the menu.
// aP - The appPrefix to start with
// aG - The args to pass along to the function
//
M.startApp = function(a, sF, cB, aP, args) {
	//
	// Set the default appPrefix to 'mc';
	//
	if( aP == null ) {
		aP = 'mc';
	}
	if( sF == null ) {
		sF = 'start';
	}

//	var args = '';
//	if( args != null ) {
//		for(i in args) {
//			args += 
//		}
//	}

	var func = a;
	func = func.replace(/(.*)\.(.*)\.(.*)/, "$1_$2_$3");

	//
	// Check if the app is already loaded
	//
	if( M[func] != null ) {
		//
		// If a start function was specified, othersize use start.
		//
//		if( sF != null ) {
//			eval('M[\'' + a + '\'].' + sF + '(\'' + cB + '\',\'' + aP + '\',' + args + ')');
//		} else {
			M[func].start(cB, aP, args);
//		}
	} else {
		M.startLoad();
		// Load Javascript
		var script = document.createElement('script');
		script.type = 'text/javascript';
		// Hack to get around cached data
		var d = new Date();
		var t = d.getTime();
		// ciniki.users.prefs -> /ciniki-mods/users/ui/prefs.js
		var src = a;
		script.src = src.replace(/(.*)\.(.*)\.(.*)/, "/$1-mods/$2/ui/$3.js") + "?t=" + t;

		//
		var done = false;
		var head = document.getElementsByTagName('head')[0];

		script.onerror = function() {
			M.stopLoad();
			alert("Unable to load, please report this bug.");
		}

		// Attach handlers for all browsers
		script.onload = script.onreadystatechange = function() {
			M.stopLoad();
			if(!done&&(!this.readyState||this.readyState==="loaded"||this.readyState==="complete")){
				done = true;
				
				// Attach the APP and run the start 
				eval('M.' + func + ' = new ' + func + '();');
				// eval('M.' + a + '.init();');
                if( M[func].init != null ) {
                    M[func].init();
                }
				M[func].start(cB, aP, args);

				// Handle memory leak in IE
				script.onload = script.onreadystatechange = null;
				if(head&&script.parentNode){
					head.removeChild( script );
				}    
			}    
		};

		head.appendChild(script);
	}
}

//
// Arguments:
// aI = appID the DOM element ID to remove
// mF = menuFlag, should the menu be shown after the app closes
//
M.closeApp = function(aI, mF) {
	var a = M.gE('mc_apps');
	a.removeChild(M.gE(aI));
	if( mF == 'yes' ) {
		M.menu.show(M.menuHistory[M.menuHistory.length-1]);
	}
}

//  
// This function will close all windows, and issue a reload
//  
M.logout = function() {
    
    var uts = M.cookieGet('_UTS','');
    var utk = M.cookieGet('_UTK','');
    var c = '';
    if( uts != null && uts != '' && utk != null && utk != '' ) { 
        c = 'user_selector=' + encodeURIComponent(uts) + '&user_token=' + encodeURIComponent(utk);
    } 
    M.gE('m_container').style.display = 'none';
    M.gE('m_loading').style.display = '';
    M.api.postJSONCb('ciniki.users.logout', {}, c, function(rsp) {
        // Don't reset UTS, it's out computer ID
        M.cookieSet('_UTK','');
        M.api.token = ''; 
        M.userID = 0; 
        M.userPerms = 0;

        // Clear any business data
        M.businesses = null;
        M.curBusinessID = 0;

        //  
        // Issue a reload, which will reset all variables, and dump any open windows.
        //  
        M.reload();
        // window.location.reload();
    });
}

//
// This function will authenticate a token
//
M.authUserToken = function(e, s, t) {
    if( s != null && s != '' && t != null && t != '' ) {
        M.api.postAuthCb('ciniki.users.auth', {'format':'json'}, 'user_selector=' + encodeURIComponent(s) + '&user_token=' + encodeURIComponent(t), function(r) {
            if( r.stat == 'ok' ) {
                // Store the time this session started, used when expiring sessions.
                M.startTime = Math.round(+new Date()/1000);
                M.api.version = r.version;	// Set only when UI is loaded/first login
                M.api.token = r.auth.token;
                M.userID = r.auth.id;
                M.avatarID = r.auth.avatar_id;
                M.userPerms = r.auth.perms;
                M.userSettings = r.auth.settings;
                if( r.auth.settings['ui-mode-guided'] != null 
                    && r.auth.settings['ui-mode-guided'] == 'yes' ) {
                    // Set to off, so toggle can switch on
                    M.uiModeGuided = 'no';
                    M.toggleGuidedMode();
                } else {
                    // Set to on, so toggle can switch off
                    M.uiModeGuided = 'yes';
                    M.toggleGuidedMode();
                }
                if( r.auth.settings['ui-mode-xhelp'] != null 
                    && r.auth.settings['ui-mode-xhelp'] == 'yes' ) {
                    // Set to off, so toggle can switch on
                    M.uiModeXHelp = 'no';
                    M.toggleXHelpMode();
                } else {
                    // Set to on, so toggle can switch off
                    M.uiModeXHelp = 'yes';
                    M.toggleXHelpMode();
                }

                if( M.oldUserId == M.userID ) {
                    M.hide('m_login');
                    return true;
                }
                M.oldUserId = 0;

                M.hide('m_login');
                M.loadAvatar();
                // If they only have access to one business, go direct to that menu
                if( r.business != null && r.business > 0 && M.businessMenu != null ) {
                    M.startApp(M.businessMenu,null,null,'mc',{'id':r.business});
                } else {
                    M.startApp(M.startMenu);
                }
                
            } else {
                M.gE('m_login').style.display = '';
            }
        });
    }
}
//
// This function will authenticate a token
//
M.authToken = function(e, t) {
    if( t != null && t != '' ) {
        M.api.postAuthCb('ciniki.users.auth', {'format':'json'}, 'auth_token=' + encodeURIComponent(t), function(r) {
            if( r.stat == 'ok' ) {
                // Store the time this session started, used when expiring sessions.
                M.startTime = Math.round(+new Date()/1000);
                M.api.version = r.version;	// Set only when UI is loaded/first login
                M.api.token = r.auth.token;
                M.userID = r.auth.id;
                M.avatarID = r.auth.avatar_id;
                M.userPerms = r.auth.perms;
                M.userSettings = r.auth.settings;
                if( r.auth.settings['ui-mode-guided'] != null 
                    && r.auth.settings['ui-mode-guided'] == 'yes' ) {
                    // Set to off, so toggle can switch on
                    M.uiModeGuided = 'no';
                    M.toggleGuidedMode();
                } else {
                    // Set to on, so toggle can switch off
                    M.uiModeGuided = 'yes';
                    M.toggleGuidedMode();
                }
                if( r.auth.settings['ui-mode-xhelp'] != null 
                    && r.auth.settings['ui-mode-xhelp'] == 'yes' ) {
                    // Set to off, so toggle can switch on
                    M.uiModeXHelp = 'no';
                    M.toggleXHelpMode();
                } else {
                    // Set to on, so toggle can switch off
                    M.uiModeXHelp = 'yes';
                    M.toggleXHelpMode();
                }

                if( M.oldUserId == M.userID ) {
                    M.hide('m_login');
                    return true;
                }
                M.oldUserId = 0;

                M.hide('m_login');
                M.loadAvatar();
                // If they only have access to one business, go direct to that menu
                if( r.business != null && r.business > 0 && M.businessMenu != null ) {
                    M.startApp(M.businessMenu,null,null,'mc',{'id':r.business});
                } else {
                    M.startApp(M.startMenu);
                }
                
            }
        });
    }
}

//
// This function will authenticate the user against the cinikiAPI and get an auth_token
//
M.auth = function(e, t) {
//	if( u != null && p != null ) {
//		var c = 'username=' + encodeURIComponent(u)
//			+ '&password=' + encodeURIComponent(p);
//	} else {
	if( t != null ) {
		M.api.token = t;
//		var c = 'auth_token=' + encodeURIComponent(t);
		var c= '';
		M.username = '';
	} else {
		M.username = M.gE('username').value;
		var c = 'username=' + encodeURIComponent(M.gE('username').value) 
			+ '&password=' + encodeURIComponent(M.gE('password').value);
		M.gE('username').value = '';
		M.gE('password').value = '';
	}

    var rm = M.gE('rm');
    if( rm != null && rm.checked == true && document.cookie != null && document.cookie != '' ) {
        c += '&rm=yes';
    }

	M.api.postJSONCb('ciniki.users.auth', {}, c, function(r) {
        if( r == null ) {
            return false;
        }
		if( r.stat != 'ok' ) {
			M.api.err_alert(r);
			return false;
		}
		// Store the time this session started, used when expiring sessions.
		M.startTime = Math.round(+new Date()/1000);
		M.api.version = r.version;	// Set only when UI is loaded/first login
		M.api.token = r.auth.token;
        var rm = M.gE('rm');
        if( rm != null && rm.checked == true 
            && r.auth.user_selector != null && r.auth.user_selector != ''
            && r.auth.user_token != null && r.auth.user_token != '' ) {
            M.cookieSet('_UTS', r.auth.user_selector, 10);
            M.cookieSet('_UTK', r.auth.user_token, 10);
        }
		M.userID = r.auth.id;
		M.avatarID = r.auth.avatar_id;
		M.userPerms = r.auth.perms;
		M.userSettings = r.auth.settings;
		if( r.auth.settings['ui-mode-guided'] != null 
			&& r.auth.settings['ui-mode-guided'] == 'yes' ) {
			// Set to off, so toggle can switch on
			M.uiModeGuided = 'no';
			M.toggleGuidedMode();
		} else {
			// Set to on, so toggle can switch off
			M.uiModeGuided = 'yes';
			M.toggleGuidedMode();
		}
		if( r.auth.settings['ui-mode-xhelp'] != null 
			&& r.auth.settings['ui-mode-xhelp'] == 'yes' ) {
			// Set to off, so toggle can switch on
			M.uiModeXHelp = 'no';
			M.toggleXHelpMode();
		} else {
			// Set to on, so toggle can switch off
			M.uiModeXHelp = 'yes';
			M.toggleXHelpMode();
		}

		if( M.oldUserId == M.userID ) {
			M.hide('m_login');
			return true;
		}
		M.oldUserId = 0;

		M.hide('m_login');
		M.loadAvatar();
		// If they only have access to one business, go direct to that menu
		if( r.business != null && r.business > 0 && M.businessMenu != null ) {
			M.startApp(M.businessMenu,null,null,'mc',{'id':r.business});
		} else {
			M.startApp(M.startMenu);
		}
	});

	return true;
}

//
// This function will reauthenticate the user, used after session has expired
//
M.reauth = function() {
	var c = 'username=' + M.username
		+ '&password=' + encodeURIComponent(M.gE('reauthpassword').value);
	M.gE('reauthpassword').value = '';

	M.api.token = '';
	M.api.postJSONCb('ciniki.users.auth', {}, c, function(r) {
		if( r.stat != 'ok' ) {
			M.api.err_alert(r);
			return false;
		}
		if( M.api.version != r.version ) {
			alert("We've updated Ciniki!  Please logout and sign in again to ensure you are using the current version.");
//			alert('Please login again to ensure you are using the current version of Ciniki');
		}
		M.api.token = r.auth.token;
		M.expired = 'no';
		M.hide('m_relogin');
		M.show('m_container');
		if( M.reauth_apiresume != null ) {
			M.api.resume(M.reauth_apiresume);
		}
//		M.reauth_apiresume = null;
	});
	return false;
}

M.reauthToken = function(s, t) {
	var c = 'user_selector=' + encodeURIComponent(s) + '&user_token=' + encodeURIComponent(t);

	M.api.token = '';
	M.api.postJSONCb('ciniki.users.auth', {}, c, function(r) {
		if( r.stat != 'ok' ) {
            M.cookieSet('_UTK', '');
            return false;
		}
		if( M.api.version != r.version ) {
			alert("We've updated Ciniki!  Please logout and sign in again to ensure you are using the current version.");
		}
		M.api.token = r.auth.token;
		M.expired = 'no';
		if( M.reauth_apiresume != null ) {
			M.api.resume(M.reauth_apiresume);
		}
	});
	return false;
}

//
// The startLoadSpinner and stopLoadSpinner functions will start and stop the
// spining logo in the upper left corner.  This is useful to let the user know
// the system is busy loading info.
//
M.startLoad = function() {
	//
	// Increment the load counter so we can have multiple requests,
	// and the spinner won't stop until they are all complete.
	//
	if( M.loadCounter < 0 ) {
		M.loadCounter = 0;
	}
	M.loadCounter += 1;
	M.setHeight('m_loading', '0');
	M.setHeight('m_loading', '100%');
	M.show('m_loading');
}

M.stopLoad = function() {
	M.loadCounter -= 1;
	if( M.loadCounter < 0 ) {
		M.loadCounter = 0;
	}
	if( M.loadCounter == 0 ) {
		M.hide('m_loading');
	}
}

M.setHTML = function(i, h) {
	M.gE(i).innerHTML = h;
}

M.setWidth = function(i, w) {
	M.gE(i).style.width = w;
}

M.setHeight = function(i, h) {
	M.gE(i).style.height = h;
}

//
// t - the value to put inside
// c - the count, -1 if no count to be displayed
// j - javascript to attach to onclick
//
M.addSectionLabel = function(t, c, j) {
	if( c != null && c >= 0 ) {
		t += ' <span class="count">' + c + '</span>';
	}
	var h = M.aE('h2', null, null, t);
	if( j != null && j != '' ) {
		h.setAttribute('onclick', j);
	}
	return h;
}

//
// Arguments:
// aP - appPrefix, the prefix for the DIV containers, 'mc' or 'mh'
// aI - the app ID
// cF - clearFlag, specifies if the container is already found, should it be cleared?
//
M.createContainer = function(aP, aI, cF) {
	//
	// FIXME: Replace this function with one that creates the container in a new draggable window "div"
	//
	var c = M.gE(aI);
	if( c == null ) {
		c = M.aE('div', aI, 'mapp');
		var a = M.gE(aP + '_apps');
		a.appendChild(c);
	} else {
		if( cF == 'yes' ) {
			M.clr(aI);
		}
	}

	return c;
}

//
// This function will submit the error information as a bug through the API
//
M.submitErrBug = function() {
	var subject = 'UI Error at ' + M.curHelpUID;
	var followup = '';

	// Get the list of errors
	strErrs = function(e) { 
		var c = e.pkg + '.' + e.code + ' - ' + e.msg;
		if( e.pmsg != null ) { c += ' [' + e.pmsg + ']'; }
		c += '\n';
		if( e.err != null ) { 
			var recursive = arguments.callee;
			c += recursive(e.err);
		}
		return c;
	};

	if( M.api.curRC.stat != 'ok' && M.api.curRC.err != null ) {
		followup += 'An error has occured while calling the API.\n\n';
		followup += 'Business ID: ' + M.curBusinessID + '\n';
		followup += 'Business Name: ' + M.curBusiness.name + '\n';
		followup += 'UI Panel: ' + M.curHelpUID + '\n';
		if( M.api.curRC.method != null ) {
			followup += 'API method: ' + M.api.curRC.method + '\n';
		} else if( M.api.lastCall.m != null ) {
			followup += 'API method: ' + M.api.lastCall.m + '\n';
		} else {
			followup += 'API method: unknown\n';
		}
		followup += '\n';
		followup += 'API Errors:\n';
		followup += strErrs(M.api.curRC.err);
	}

	if( M.api.lastCall != null ) {
		followup += '\n';
		followup += 'API Function: ' + M.api.lastCall.f + '\n';
		followup += 'API Method: ' + M.api.lastCall.m + '\n';
		followup += 'API Parameters: \n';
		for(i in M.api.lastCall.p) {
			followup += '    ' + i + '=' + M.api.lastCall.p[i] + '\n';
		}
//		followup += 'API Parameters: ' + M.api.lastCall.p + '\n';
		var c = M.api.lastCall.c.split('&');
		followup += 'API Post Content: \n'
		for(i in c) {
			followup += '    ' + c[i] + '\n';
		}
//		followup += 'API Post Content: \n' + M.api.lastCall.c + '\n';
//		followup += 'API Callback: ' + M.api.lastCall.cb + '\n';
	}

	//
	// Submit the bug
	//
	var rsp = M.api.postJSONCb('ciniki.bugs.bugAdd',
		{'business_id':M.masterBusinessID, 'status':'1', 'source':'ciniki-manage', 'source_link':M.curHelpUID},
		'subject=' + encodeURIComponent(subject) + '&followup=' + encodeURIComponent(followup), function(rsp) {
			if( rsp.stat != 'ok' ) {
				alert("Now we had an error submitting the bug, please contact support.  " + "Error #" + rsp.err.code + ' -- ' + rsp.err.msg);
			} else {
				alert('The bug has been submitted');
			}

			M.hide('m_error');
		});
}

//
// Dummy function to dump event info
//
M.dumpEventInfo = function(event) {
	if (event === undefined) {
		event = window.event;
	}

	var firedOn = event.target ? event.target : event.srcElement;
	if (firedOn.tagName === undefined) {
		firedOn = firedOn.parentNode;
	}

	var info = ''
	if (firedOn.id == "source") {
		info += "<span style='color:#008000'>" + event.type + "</span>, ";
	}
	else {
		info += "<span style='color:#800000'>" + event.type + "</span>, ";
	}

	if (event.type == "dragover") {
			// the dragover event needs to be canceled in Google Chrome and Safari to allow firing the drop event
		if (event.preventDefault) {
			event.preventDefault ();
		}
	}
}

//
// Arguments:
// p - the panelRef of the panel to add the callback for
// c - the callback function
M.addDropHook = function(p, c) {
	M.dropHooks[p] = c;
}

//
// Arguments:
// p - the ID of the panel to add the callback for
// c - the callback function
M.delDropHook = function(p) {
	if( M.dropHooks[p] != null ) {
		delete M.dropHooks[p];
	}
}

//
// Arguments:
// e - the event info
//
M.dropHandler = function(e) {
	e.stopPropagation();
	e.preventDefault();

	//
	// Find active panel 
	//
	for(pRef in M.dropHooks) {
		var p = eval(pRef);
		// Make sure panel and app are displayed, which means top panel that was dropped onto
		if( M.gE(p.panelUID).style.display == 'block' && M.gE(p.panelUID).parentNode.style.display == 'block' ) {
			s = null;
			// Find the section it was dropped into
			if( e.toElement != null ) {
				var parent = e.toElement.parentElement;
			} else {
				var parent = e.target.parentElement;
			}
			var ps = p.panelUID + '_section_';
			while(parent != null && parent.localName != 'form' && parent.localName != 'body') {
				if( parent.id.substr(0, ps.length) == ps ) {
					s = parent.id.substr(ps.length);
				}
				parent = parent.parentElement;
			}
			eval('' + M.dropHooks[pRef](e, p, s));
		}
	}
}

M.setColourSwatchField = function(field, value) {
	var d = M.gE(field);
	d.setAttribute('value', value);
	for(i in d.children) {
		if( d.children[i].getAttribute != null ) {
			if( d.children[i].getAttribute('name') == value ) {
				d.children[i].className = 'colourswatch selected';
			} else {
				if( d.children[i].className != 'colourswatch' ) {
					d.children[i].className = 'colourswatch';
				}
			}
		}
	}
}

//
// Convert a timestamp in seconds to a time in 12 hour clock
//
M.dateMake12hourTime = function(ts) {
	if( typeof ts == 'number' ) {
		var dt = new Date(ts * 1000);
	} else {
		dt = ts;
	}
	str = '';
	if( dt.getHours() == 0 ) {
		str += '12';
	} else if( dt.getHours() < 10 ) {
		str += '0' + dt.getHours();
	} else if( dt.getHours() > 21 ) {
		str += '0' + dt.getHours() - 12;
	} else if( dt.getHours() > 12 ) {
		str += '0' + dt.getHours() - 12;
	} else {
		str += '' + (dt.getHours());
	}
	str += ':';
	if( dt.getMinutes() < 10 ) {
		str += '0';
	}
	str += '' + dt.getMinutes();

	return str;
}

M.dateMake12hourTime2 = function(ts) {
	if( typeof ts == 'number' ) {
		var dt = new Date(ts * 1000);
	} else {
		dt = ts;
	}
	str = '';
	if( dt.getHours() == 0 ) {
		str += '12';
	} else if( dt.getHours() < 10 ) {
		str += '0' + dt.getHours();
	} else if( dt.getHours() > 21 ) {
		str += '0' + dt.getHours() - 12;
	} else if( dt.getHours() > 12 ) {
		str += '0' + dt.getHours() - 12;
	} else {
		str += '' + (dt.getHours());
	}
	str += ':';
	if( dt.getMinutes() < 10 ) {
		str += '0';
	}
	str += '' + dt.getMinutes();

	if( dt.getHours() > 11 ) {
		str += ' pm';
	} else {
		str += ' am';
	}

	return str;
}

M.daysInMonth = function(year, month) {
	var isLeap = ((year % 4) == 0 && ((year % 100) != 0 || (year % 400) == 0));
	return [31, (isLeap ? 29 : 28), 31, 30, 31, 30, 31, 31, 30, 31, 30, 31][month];
}

M.dayOfWeek = function(d) {
	var days = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];
	return days[d.getDay()];
}

M.monthOfYear = function(d) {
	var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
	return months[d.getMonth()];
}

M.dateFormat = function(d) {
	if( typeof d == 'string' ) {
		if( d == '0000-00-00' ) {
			return '';
		}
		if( d.match(/[a-zA-Z]+ [0-9]+, [0-9]+/) ) {
			return d;
		}
		var p = d.split(/-/);
		d = new Date(p[0],p[1]-1,p[2]);
	}
	return M.monthOfYear(d) + ' ' +  d.getDate() + ', ' + d.getFullYear();
}

M.dateFormatWD = function(d) {
	var days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
	if( typeof d == 'string' ) {
		d = new Date(d);
	}
	return days[d.getDay()] + ' ' + M.monthOfYear(d) + ' ' +  d.getDate() + ', ' + d.getFullYear();
}

M.rgbToHex = function(rgb) {
	var hexDigits = ["0","1","2","3","4","5","6","7","8","9","a","b","c","d","e","f"];
	rgb = rgb.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
	if( rgb == null ) { return ''; }
	function hex(x) {
		return isNaN(x) ? "00" : hexDigits[(x - x % 16) / 16] + hexDigits[x % 16];
	}
	return "#" + hex(rgb[1]) + hex(rgb[2]) + hex(rgb[3]);
}

// Arguments:
// b - size in bytes, or by e (B,K,M,G,T,P)
// e - should be 0, unless in recurse
//
M.humanBytes = function(b, e) {
	if( b == '' ) { return ''; }
	if( b == undefined ) { b = 0; }
	if( e == null ) { e = 0; }
	exts = ['B','K','M','G','T','P'];
	if( b > 1024 ) {
		return M.humanBytes(b/1024, e+1);
	}
	else if( typeof b == 'number' && b != 0 ) {
		return b.toFixed(1) + exts[e];
	} 
	return b + exts[e];
}

//
// TreeGrid sort is complicated, because it must figure out which rows to move
// based on what is blank or "attached together".
//
// tid: 	table ID
// col: 	The column number in the table to sort
// type: 	The type of the column
// o:		The order to sort in, asc, or desc
// s:		The saveSort function to call to save settings
// d:		null, lookup table in document, otherwise sort the table in this object
M.sortTreeGrid = function(tid, col, type, o, save, d) {
	// This function is called whenever a sortable table is first displayed, 
	// to check if there are any predisplay sort settings
	if( (col == null || type == null) && M.gridSorting[tid] == null) {
		return false;
	}

	//
	// Sort example from http://www.kryogenix.org/code/browser/sorttable/sorttable.js
	//
	if( d == null ) {
		var t = M.gE(tid);
		var tb = t.getElementsByTagName('tbody')[0];
	} else {
		var t = d;
		var tb = t.getElementsByTagName('tbody')[0];
	}

	var o = 'asc';
	if( col == null ) {
		col = M.gridSorting[tid].col;
		o = M.gridSorting[tid].order;
	}
	if( type == null ) {
		type = M.gridSorting[tid].type;
	}

	if( type == 'text' || type == 'undefined' ) {
		var sorter_fn = function(a, b) {
			if( a == b ) return 0;
			if( a < b ) return -1;
			return 1;
		}
	} else if( type == 'date' ) {
		var sorter_fn = function(a, b) {
			if( a == b ) return 0;
			if( a < b ) return -1;
			return 1;
		}
	} else if( type == 'number' || type == 'size' || type == 'percent' ) {
		var sorter_fn = function(a, b) {
			aa = parseFloat(a.replace(/[^0-9.-]/g,''));
			if (isNaN(aa)) aa = 0;
			bb = parseFloat(b.replace(/[^0-9.-]/g,'')); 
			if (isNaN(bb)) bb = 0;
			return aa-bb;
		}
	}

	var s = 0;
	// Find the last entry in list, which might not be the last row
	for(l=(tb.children.length-1);l>s && tb.children[l].children[col].innerHTML == '' && tb.children[l].children[col].sort_value != '';l--);
	var swap = true;

	// Check if we are sorted the same column
	if( tb.last_sorted_col != null && tb.last_sorted_col == col ) {
		if( tb.last_sorted_order == 'asc' ) {
			o = 'desc';	
		} else {
			o = 'asc';
		}
		//
		// FIXME: Add quick swap if the grid is already sorted on the column
		//
		/*
		// If the same column, and already sorted, then just reverse it
		for(i=0;i<=Math.floor(l/2);i++) {
			var n = tb.children[l-i+1];
			tb.insertBefore(tb.children[l-i], tb.children[i]);
			if( i == 0 ) {
				tb.appendChild(tb.children[i+1]);
			} else {
				tb.insertBefore(tb.children[i+1], n);
			}
		}
		tb.last_sorted_order = o;
		return true;
		*/
	}

	while(swap) {
		swap = false;
		//
		// Sort from the top, a is the top element, b is second element
		//
		for(i=s;i < l;i++) {
			// console.log('sort-s: ' + i);
			// Skip blank entries
			if( tb.children[i].children[col].innerHTML == '' && tb.children[i].children[col].sort_value != '' ) { continue; }
			//
			// Find the next branch in the tree by skipping blank cells, offseta
			//
			for(oa=1;(i+oa)<l && tb.children[i+oa].children[col].innerHTML == '' && tb.children[i+oa].children[col].sort_value != '';oa++);
			// Check if this was the last element, only blank elements after this row
			if( i+oa > l ) { break; }
			a = tb.children[i].children[col].innerHTML;
			b = tb.children[i+oa].children[col].innerHTML;
			// Find the offset for the second element
			for(ob=1;(i+oa+ob)<tb.children.length && tb.children[i+oa+ob].children[col].innerHTML == '' && tb.children[i+oa+ob].children[col].sort_value != '';ob++);
			if( type == 'date' || type == 'size' || type == 'percent' ) {
				a = tb.children[i].children[col].sort_value;
				b = tb.children[i+oa].children[col].sort_value;
			}
			if( type == 'text' && a == '' && tb.children[i].children[col].sort_value != '' ) {
				a = tb.children[i].children[col].sort_value;
			}
			if( type == 'text' && b == '' && tb.children[i+oa].children[col].sort_value != '' ) {
				b = tb.children[i+oa].children[col].sort_value;
			}

			//console.log("compare-s: " + a + ' > ' + b + ' : (' + (i) + ',' + (i+oa) + ') - ' + s +',' + l + ',' + i + ',' + oa + ',' + ob);
			if( (o == 'asc' && sorter_fn(a, b) > 0) || (o == 'desc' && sorter_fn(b, a) > 0) ) {
				for(j=0;j<ob;j++) {
					//console.log("swap: " + (i+oa+j) + ',' + (i+j));
					tb.insertBefore(tb.children[i+oa+j], tb.children[i+j]);
				}
				swap = true;
			}
		}
		// l-=ob;
		// l-=oa;
		l--;

		if( !swap) break;

		//
		// Sort from the bottom, a is the bottom element, b is top element
		//
		//console.log('swap-l: ' + l);
		for(var i = l; i > s; i--) {
			// console.log('sort-l: ' + i);
			// Skip blank entries
			if( tb.children[i].children[col].innerHTML == '' && tb.children[i].children[col].sort_value != '' ) { continue; }
			// Find blank cells after if any
			for(oa=1;(i+oa)<tb.children.length && tb.children[i+oa].children[col].innerHTML == '' && tb.children[i+oa].children[col].sort_value != '';oa++);
			for(ob=1;(i-ob)>=0 && tb.children[i-ob].children[col].innerHTML == '' && tb.children[i-ob].children[col].sort_value != '';ob++);
			// Check if we're back at the first element
			if( i-ob < s ) { break; }
			a = tb.children[i].children[col].innerHTML;
			b = tb.children[i-ob].children[col].innerHTML;
			if( type == 'date' || type == 'size' || type == 'percent' ) {
				a = tb.children[i].children[col].sort_value;
				b = tb.children[i-ob].children[col].sort_value;
			}
			if( type == 'text' && a == '' && tb.children[i].children[col].sort_value != '' ) {
				a = tb.children[i].children[col].sort_value;
			}
			if( type == 'text' && b == '' && tb.children[i-ob].children[col].sort_value != '' ) {
				b = tb.children[i-ob].children[col].sort_value;
			}
			//console.log("compare-l: " + b + ' > ' + a + ' : (' + (i) + ',' + (i-ob) + ') - ' + s +',' + l + ',' + i + ',' + oa + ',' + ob);
			if( (o == 'asc' && sorter_fn(b, a) > 0) || (o == 'desc' && sorter_fn(a, b) > 0) ) {
				for(j=0;j<oa;j++) {
					//console.log("swap: " + (i+j) + ',' + (i-ob+j));
					tb.insertBefore(tb.children[i+j], tb.children[i-ob+j]);
				}
				swap = true;
			}
		}
		s++;
		// s+=ob;
	}

	tb.last_sorted_col = col;
	tb.last_sorted_order = o;

	//
	// Save the sort order for the panel for next time
	//
	if( save != null ) {
		save(tid, col, type, o);
	} else {
		M.gridSorting[tid] = {'col':col, 'type':type, 'order':o};
	}
}


// tid: 	table ID
// col: 	The column number in the table to sort
// type: 	The type of the column
// o:		The order to sort in, asc, or desc
// s:		The saveSort function to call to save settings
// d:		null, lookup table in document, otherwise sort the table in this object
M.sortGrid = function(tid, col, type, o, save, d) {
	// This function is called whenever a sortable table is first displayed, 
	// to check if there are any predisplay sort settings
	if( (col == null || type == null) && M.gridSorting[tid] == null) {
		return false;
	}

	//
	// Sort example from http://www.kryogenix.org/code/browser/sorttable/sorttable.js
	//
	if( d == null ) {
		var t = M.gE(tid);
		var tb = t.getElementsByTagName('tbody')[0];
	} else {
		var t = d;
		var tb = t.getElementsByTagName('tbody')[0];
	}

	if( tb == null || tb.children == null || tb.children.length == 0 || tb.children.length == 1 ) {
		return true;
	}

	var o = 'asc';
	if( col == null ) {
		col = M.gridSorting[tid].col;
		o = M.gridSorting[tid].order;
	}
	if( type == null ) {
		type = M.gridSorting[tid].type;
	}

	if( type == 'text' || type == 'alttext' || type == 'undefined' ) {
		var sorter_fn = function(a, b) {
//			console.log('sort:'+a+'--'+b);
			if( a == b ) return 0;
			if( a < b ) return -1;
			return 1;
		}
	} else if( type == 'date' || type == 'size' ) {
		var sorter_fn = function(a, b) {
			if( a == b ) return 0;
			if( a < b ) return -1;
			return 1;
		}
	} else if( type == 'number' || type == 'altnumber' ) {
		var sorter_fn = function(a, b) {
			if(isNaN(a)) {aa = parseFloat(a.replace(/[^0-9.-]/g,''));} else {aa = a;}
		    if(isNaN(aa)) aa = 0;
			if(isNaN(b)) {bb = parseFloat(b.replace(/[^0-9.-]/g,'')); } else {bb = b;}
			if(isNaN(bb)) bb = 0;
			return aa-bb;
		}
	}

	var s = 0;
	// Last entry in list
	if( tb.children != null && tb.children.length > 1 ) {
		var l = tb.children.length - 1;
	} else {
		var l = 0;
	}
	var swap = true;

	// Check if we are sorted the same column
	if( tb.last_sorted_col != null && tb.last_sorted_col == col ) {
		if( tb.last_sorted_order == 'asc' ) {
			o = 'desc';	
		} else {
			o = 'asc';
		}
		// If the same column, and already sorted, then just reverse it
		for(i=0;i<=Math.floor(l/2);i++) {
			var n = tb.children[l-i+1];
			tb.insertBefore(tb.children[l-i], tb.children[i]);
			if( i == 0 ) {
				tb.appendChild(tb.children[i+1]);
			} else {
				tb.insertBefore(tb.children[i+1], n);
			}
		}
		tb.last_sorted_order = o;
		return true;
	}

	while(swap) {
		swap = false;
		for(i=s;i < l;i++) {
			a = tb.children[i].children[col].innerHTML;
			b = tb.children[i+1].children[col].innerHTML;
			var sva = tb.children[i].children[col].sort_value;
			var svb = tb.children[i+1].children[col].sort_value;
			if( type == 'date' || type == 'size' || type == 'altnumber' || type == 'alttext' ) {
				a = sva;
				b = svb;
			}
			if( type == 'text' && a == '' && sva != null && sva != '' && sva != undefined) {
				a = sva;
			}
			if( type == 'text' && b == '' && svb != null && svb != '' && svb != undefined) {
				b = svb;
			}
			if( a == null ) { a = ''; }
			if( b == null ) { b = ''; }

			if( sorter_fn(a, b) > 0 ) {
				tb.insertBefore(tb.children[i+1], tb.children[i]);
				swap = true;
			}
		}
		l--;

		if( !swap) break;

		for(var i = l; i > s; i--) {
			a = tb.children[i].children[col].innerHTML;
			b = tb.children[i-1].children[col].innerHTML;
			var sva = tb.children[i].children[col].sort_value;
			var svb = tb.children[i-1].children[col].sort_value;
			if( type == 'date' || type == 'size' || type == 'altnumber' || type == 'alttext' ) {
				a = sva;
				b = svb;
			}
			if( type == 'text' && a == '' && sva != null && sva != '' ) {
				a = sva;
			}
			if( type == 'text' && b == '' && svb != null && svb != '' ) {
				b = svb;
			}
			if( sorter_fn(a,b) < 0 ) {
				tb.insertBefore(tb.children[i], tb.children[i-1]);
				swap = true;
			}
		}
		s++;
	}

	tb.last_sorted_col = col;
	tb.last_sorted_order = o;

	//
	// Save the sort order for the panel for next time
	//
	if( save != null ) {
		save(tid, col, type, o);
	} else {
		M.gridSorting[tid] = {'col':col, 'type':type, 'order':o};
	}
}

M.loadAvatar = function() {
	//
	// Only load an avatar if the user has uploaded one
	//
	if( M.avatarID > 0 ) {
		var l = M.gE('mc_home_button');
		l.className = 'homebutton avatar';
		var i = M.aE('img',null,'homebutton avatar');
		i.src = M.api.getBinaryURL('ciniki.users.avatarGet', {'user_id':M.userID, 'version':'thumbnail', 'maxlength':'100', 'refresh':Math.random()});
		M.clr(l);
		l.appendChild(i);
	} else {
		var l = M.gE('mc_home_button');
		M.clr(l);
		l.innerHTML = '<div class="button home"><span class="faicon">&#xf015;</span><span class="label">Home</span></div>';
	}
}

M.reload = function() {
	var newHref = window.location.href;
	window.location.href = newHref;
}

M.pwdReset = function() {
	var c = 'email=' + encodeURIComponent(M.gE('reset_email').value);

	var r = M.api.postJSONCb('ciniki.users.passwordRequestReset', {}, c, function(r) {
		if( r.stat != 'ok' ) {
			M.api.err_alert(r);
			return false;
		}
		alert("An email has been sent to you with a new password.");
		M.hide('m_forgot');
		M.show('m_login');
	});
	return true;
}

M.tempPassReset = function() {
	var email = encodeURIComponent(M.gE('recover_email').value);
	var temppwd = encodeURIComponent(M.gE('temp_password').value);
	var newpwd1 = encodeURIComponent(M.gE('new_password').value);
	var newpwd2 = encodeURIComponent(M.gE('new_password_again').value);

	if( newpwd1 != newpwd2 ) { 
		alert("The password's do not match.  Please enter them again");
		return false;
	}   
	if( newpwd1.length < 8 ) { 
		alert("Passwords must be at least 8 characters long");
		return false;
	}   
	var c = 'temppassword=' + temppwd + '&newpassword=' + newpwd1;
	var rsp = M.api.postJSONCb('ciniki.users.changeTempPassword', {'email':email}, c, function(rsp) {
		if( rsp.stat != 'ok' ) { 
			M.api.err_alert(rsp);
			return false;
		}   
		alert("Your password was changed, you can now login.");
		// Redirect	to the main login page
		var newHref = window.location.href.split("?")[0];
		window.location.href = newHref;
	});
	return false;
}

M.toggleSection = function(e, s) {
	var f = M.gE(s);
	if( f == null ) {return false; }
	var b = null;
	if( e.childNodes[0].className == 'icon' ) { b = e.childNodes[0]; }
	if( f.style.display == 'none' ) {
		f.style.display = 'block';
		if( b != null ) { b.innerHTML = '-'; }
	} else {
		f.style.display = 'none';
		if( b != null ) { b.innerHTML = '+'; }
	}
}

M.gE = function(i) {
	return document.getElementById(i);
}

//
// This is a shortcut to creating new elements
//
// Arguments:
// t = The type of element to create
// i = The id of the element
// c = The class of the element
// h = The innerHTML if specified of the element
// f = The onclick function for the element if supplied
//
M.aE = function(t, i, c, h, f) {
	var e = document.createElement(t);
	if( i != null ) { e.setAttribute('id', i); }
	if( c != null ) { e.className = c; }
	if( h != null ) { e.innerHTML = h; }
	if( f != null && f != '' ) { e.setAttribute('onclick', f); }
	return e;
}

//
// Arguments
// i  - The id to assign to the element, if not null
// c  - The class to assign to the element, if not null, but can be blank
//
M.addTable = function(i, c) {
	var t = M.aE('table', i, c);
	t.cellPadding = 0;
	t.cellSpacing = 0;

	return t;
}

M.strtotime = function(text, now) {
    // Convert string representation of date and time to a timestamp
    //
    // version: 1109.2015
    // discuss at: http://phpjs.org/functions/strtotime
    // +   original by: Caio Ariede (http://caioariede.com)
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +      input by: David
    // +   improved by: Caio Ariede (http://caioariede.com)
    // +   bugfixed by: Wagner B. Soares
    // +   bugfixed by: Artur Tchernychev
    // +   improved by: A. Matías Quezada (http://amatiasq.com)
    // +   improved by: preuter
    // +   improved by: Brett Zamir (http://brett-zamir.me)
    // %        note 1: Examples all have a fixed timestamp to prevent tests to fail because of variable time(zones)
    // *     example 1: strtotime('+1 day', 1129633200);
    // *     returns 1: 1129719600
    // *     example 2: strtotime('+1 week 2 days 4 hours 2 seconds', 1129633200);
    // *     returns 2: 1130425202
    // *     example 3: strtotime('last month', 1129633200);
    // *     returns 3: 1127041200
    // *     example 4: strtotime('2009-05-04 08:30:00');
    // *     returns 4: 1241418600
    var parsed, match, year, date, days, ranges, len, times, regex, i;

    if (!text) {
        return null;
    }

    // Unecessary spaces
    text = text.trim()
        .replace(/\s{2,}/g, ' ')
        .replace(/[\t\r\n]/g, '')
        .toLowerCase();

    if (text === 'now' || text === 'today') {
        return now === null || isNaN(now) ? new Date().getTime() / 1000 | 0 : now | 0;
    }
    if (!isNaN(parsed = Date.parse(text))) {
        return parsed / 1000 | 0;
    }
	if( text === 'yesterday' ) {
		return (new Date().getTime()/1000) - 86400;
	}
	if( text === 'tomorrow' ) {
		return (new Date().getTime()/1000) + 86400;
	}
//    if (text === 'now') {
//        return new Date().getTime() / 1000; // Return seconds, not milli-seconds
//    }
//    if (!isNaN(parsed = Date.parse(text))) {
//        return parsed / 1000;
//    }

    match = text.match(/^(\d{2,4})-(\d{2})-(\d{2})(?:\s(\d{1,2}):(\d{2})(?::\d{2})?)?(?:\.(\d+)?)?$/);
    if (match) {
        year = match[1] >= 0 && match[1] <= 69 ? +match[1] + 2000 : match[1];
        return new Date(year, parseInt(match[2], 10) - 1, match[3],
            match[4] || 0, match[5] || 0, match[6] || 0, match[7] || 0) / 1000;
    }

    date = now ? new Date(now * 1000) : new Date();
    days = {
        'sun': 0,
        'mon': 1,
        'tue': 2,
        'wed': 3,
        'thu': 4,
        'fri': 5,
        'sat': 6
    };
    ranges = {
        'yea': 'FullYear',
        'mon': 'Month',
        'day': 'Date',
        'hou': 'Hours',
        'min': 'Minutes',
        'sec': 'Seconds'
    };


    times = '(years?|months?|weeks?|days?|hours?|minutes?|min|seconds?|sec' +
        '|sunday|sun\\.?|monday|mon\\.?|tuesday|tue\\.?|wednesday|wed\\.?' +
        '|thursday|thu\\.?|friday|fri\\.?|saturday|sat\\.?)';
    regex = '([+-]?\\d+\\s' + times + '|' + '(last|next)\\s' + times + ')(\\sago)?';

    match = text.match(new RegExp(regex, 'gi'));
    if (!match) {
        return false;
    }

    for (i = 0, len = match.length; i < len; i++) {
        if (!M.strtotime_process(match[i])) {
            return false;
        }
    }

    // ECMAScript 5 only
    //if (!match.every(process))
    //    return false;
    return (date.getTime() / 1000);
}

//function lastNext(type, range, modifier) {
M.strtotime_lastNext = function(type, range, modifier) {
	var diff, day = days[range];

	if (typeof day !== 'undefined') {
		diff = day - date.getDay();

		if (diff === 0) {
			diff = 7 * modifier;
		}
		else if (diff > 0 && type === 'last') {
			diff -= 7;
		}
		else if (diff < 0 && type === 'next') {
			diff += 7;
		}

		date.setDate(date.getDate() + diff);
	}
}
//    function process(val) {
M.strtotime_process = function(val) {
	var splt = val.split(' '), // Todo: Reconcile this with regex using \s, taking into account browser issues with split and regexes
		type = splt[0],
		range = splt[1].substring(0, 3),
		typeIsNumber = /\d+/.test(type),
		ago = splt[2] === 'ago',
		num = (type === 'last' ? -1 : 1) * (ago ? -1 : 1);

	if (typeIsNumber) {
		num *= parseInt(type, 10);
	}

	if (ranges.hasOwnProperty(range) && !splt[1].match(/^mon(day|\.)?$/i)) {
		return date['set' + ranges[range]](date['get' + ranges[range]]() + num);
	}
	if (range === 'wee') {
		return date.setDate(date.getDate() + (num * 7));
	}

	if (type === 'next' || type === 'last') {
		M.strtotime_lastNext(type, range, num);
	}
	else if (!typeIsNumber) {
		return false;
	}
	return true;
}

// This function replaces API to ciniki.core.parseDate
M.parseDate = function(dt) {
	var pd = new Date((M.strtotime(dt))*1000);
	var I = pd.getHours()%12;
	var m = pd.getMinutes();
	var r = {'year':pd.getFullYear(),
		'month':pd.getMonth()+1,
		'day':pd.getDate(),
		'time':(I===0?12:I) + ':' + (m>9?m:'0'+m) + ' ' + (pd.getHours() > 11?'PM':'AM'),
		};
	return r;
}

M.formatAddress = function(addr) {
	var a = '';
	if( addr.name != null && addr.name != '' ) {
		a += addr.name + '<br/>';
	}
	if( addr.address1 != null && addr.address1 != '' ) {
		a += addr.address1 + '<br/>';
	}
	if( addr.address2 != null && addr.address2 != '' ) {
		a += addr.address2 + '<br/>';
	}
	var a3 = '';
	if( addr.city != null && addr.city != '' ) {
		a3 += addr.city;
	}
	if( addr.province != null && addr.province != '' ) {
		if( a3 != '' ) { a3 += ' '; }
		a3 += addr.province;
	}
	if( addr.postal != null && addr.postal != '' ) {
		if( a3 != '' ) { a3 += '  '; }
		a3 += addr.postal;
	}
	if( a3 != '' ) {
		a += a3 + '<br/>';
	}
	if( addr.country != null && addr.country != '' ) {
		a += addr.country + '<br/>';
	}
	if( addr.phone != null && addr.phone != '' ) {
		a += 'Phone: ' + addr.phone + '<br/>';
	}
	return a;
}

M.linkEmail = function(v) {
	if( typeof(v) == 'string' ) {
		v = v.replace(/(\w+([-+.']\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*)/, "<a class=\"mailto\" href=\"mailto:$1\" onclick=\"event.stopPropagation();\">$1</a>");
	}

	return v;
}

M.hyperlink = function(v) {
	if( typeof(v) == 'string' ) {
		v = '<a class="website" target="blank_" href="' + v + '" onclick="event.stopPropagation();">' + v + '</a>';
	}

	return v;
}

M.formatHtml = function(c) {
	return c.replace(/\n/, '<br/>');
}

M.length = function(o) {
	if( o.keys ) {
		return o.keys.length;
	}
	var l = 0;
	for(var i in o) {
		if( o.hasOwnProperty(i) ) {
			l++;
		}
	}
	return l;
}

// n = name, v = value, d = days
M.cookieSet = function(n,v,d) {
	if(d) { var date = new Date(); date.setTime(date.getTime()+(d*24*60*60*1000));var expires="; expires="+date.toUTCString();}
	else{var expires ='';}
    // Path cannot be manager for IE11
	document.cookie = n+'='+v+expires+'; path=/';
}

M.cookieGet = function(n) {
	var ne = n+'=';
	var ca = document.cookie.split(';');
	for(var i=0;i<ca.length;i++) {
		var c = ca[i];
		while (c.charAt(0)==' ') c = c.substring(1,c.length);
		if (c.indexOf(ne) == 0) return c.substring(ne.length,c.length);
	}
	return null;
}

M.showWebsite = function(url) {
	var e1 = M.gE('m_website');
	var e2 = M.gE('m_container');
	var iframe = M.gE('mc_website_iframe');
	var d = iframe.contentWindow.document;
	d.open();
	d.write("<html><body style='background:#fff;background:url(\"/ciniki-mods/core/ui/themes/default/img/background2.png\");'><div style='height: 100%; width: 100%; position:fixed; top:0px; left:0px; background: #fff; opacity: .5;'><table width='100%' style='width:100%;height:100%;border-collapse:separate;text-align:center;'><tbody style='vertical-align: middle;'><tr><td><img src='/ciniki-mods/core/ui/themes/default/img/spinner.gif'></td></tr></tbody></table></div></body></html>");
	d.close();
	if( e1.style.display == 'block' ) {
		e1.style.display = 'none';
		e2.style.display = 'block';
	} else {
		e2.style.display = 'none';
		e1.style.display = 'block';
		// Force links to keep within iframe
		iframe.onload = function() {
			var a=this.contentWindow.document.getElementsByTagName("a");
			for(var i=0;i<a.length;i++) {
				a[i].onclick=function() {
					iframe.src = this.getAttribute('href');
					return false;
				}
			}
		};
		var url = '/preview/' + M.curBusiness.modules['ciniki.web'].settings.sitename + url;
		iframe.src = url;
	}
	M.resize();
}

M.showPDF = function(m, p) {
	if( M.device == 'ipad' && window.navigator != null && window.navigator.standalone == true ) {
		var e1 = M.gE('m_pdf');
		var e2 = M.gE('m_container');
		var iframe = M.gE('mc_pdf_iframe');
		iframe.src = "about:blank";
		if( e1.style.display == 'block' ) {
			e1.style.display = 'none';
			e2.style.display = 'block';
		} else {
			var d = iframe.contentWindow.document;
			d.open();
			d.write("<html><body style='background:#fff;background:url(\"/ciniki-mods/core/ui/themes/default/img/background2.png\");'><div style='height: 100%; width: 100%; position:fixed; top:0px; left:0px; background: #fff; opacity: .5;'><table width='100%' style='width:100%;height:100%;border-collapse:separate;text-align:center;'><tbody style='vertical-align: middle;'><tr><td><img src='/ciniki-mods/core/ui/themes/default/img/spinner.gif'></td></tr></tbody></table></div></body></html>");
			d.close();
			e2.style.display = 'none';
			e1.style.display = 'block';
			var url = M.api.getUploadURL(m, p);
			iframe.src = url;
		}
		M.resize();
	} else {
		M.api.openPDF(m, p);
	}
}

M.printPDF = function() {
	window.print();
}

M.modFlags = function(m) {
	if( M.curBusiness != null && M.curBusiness.modules != null && M.curBusiness.modules[m] != null && M.curBusiness.modules[m].flags != null ) {
		return M.curBusiness.modules[m].flags;
	}
	return 0;
}

M.modFlagSet = function(m, f) {
	return (M.modFlags(m)&f)==f?'yes':'no';
}

M.modFlagAny = function(m, f) {
	return (M.modFlags(m)&f)>0?'yes':'no';
}
