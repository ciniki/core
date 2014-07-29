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
//	this.destroy();

	//
	// Remove any hooks
	//
//	M.delDropHook(this.panelRef);
	
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
	// 	element = element.parentNode;
	// }
	
	// Make sure the panel is displayed
	M.hideChildren(app, this.panelUID);

	// Make sure app is displayed
	M.hideChildren(app.parentNode, this.appID);

	// Make sure app container
	M.show(app.parentNode.parentNode.parentNode.parentNode);

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

	if( this.autofocus != '' ) {
		var e = M.gE(this.autofocus);
		e.focus();
	}
};

//
// This function will build a new div with the panel data inside.  This function
// can be called by show or refresh
//
M.panel.prototype.addPanel = function() {
	var p = M.aE('div', this.panelUID, 'panel');
	if( this.sidePanel != null ) {
		var w = M.aE('div', '', this.size + ' leftpanel');
	} else {
		var w = M.aE('div', '', this.size);
	}
	
	if( this.type == 'sectioned' ) {
		// This is the new (Dec 2011) master section, which should convert the way panels are handled
		// The goal is to make all panels this type
		w.appendChild(this.createSections());
	} 

	else if( this.type == 'simplemedia' ) {
		w.appendChild(this.createSimpleMedia('', this.data, 'yes'));
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
			var ps = M.aE('div', this.panelUID + '_formtabs', 'panelsection formtabs');
			st = this.createFormTabs(this.formtabs, this.formtab, fv);
			ps.appendChild(st);
			f.appendChild(ps);
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
			f.appendChild(r);
		}
//		// Check if this is a multi section, and we need more to be created
//		if( this.sections[i].multi != null && this.sections[i].multi == 'yes' && this.sectionCount != null ) {
//			var c = this.sectionCount(i);
//			for(j=1;j<c;j++) {
//				this.dupFormSection(i);
//				var r = this.createSection(i + '_' + j, this.sections[i + '_' + j]);
//				if( r != null ) {
//					f.appendChild(r);
//				}
//			}
//		}
	}
	return f;
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

M.panel.prototype.createSection = function(i, s) {
	// 
	// Check if section is visible
	//
	if( s.visible != null && s.visible != 'yes' ) {
		return null;
	}
	if( s.active != null && s.active != 'yes' ) {
		return null;
	}
		
	var type = this.sectionType(i, s);
	
	if( s.aside != null ) {
		if( s.aside == 'yes' || s.aside == 'left' ) {
			var f = M.aE('div', this.panelUID + '_section_' + i, 'panelsection asideleft');
		} else {
			var f = M.aE('div', this.panelUID + '_section_' + i, 'panelsection aside');
		}
	} else if( s.aside != null && s.aside == 'fullwidth' ) {
		var f = M.aE('div', this.panelUID + '_section_' + i, 'panelsection fullwidth');
	} else if( type == 'paneltabs' ) {
		var f = M.aE('div', this.panelUID + '_section_' + i, 'panelsection paneltabs');
	} else {
		var f = M.aE('div', this.panelUID + '_section_' + i, 'panelsection');
	}

	//
	// Check if there should be label
	//
	var lE = null;
	var t = this.sectionLabel(i, s);
	if( t != null && t != '' ) {
		if( s.multi != null && s.multi == 'yes' ) {
			// If the form supports ability for duplicate sections of the form to be created
			lE = M.addSectionLabel(s.label + ' <span class="rbutton_off clickable" onclick="M.' + this.appID + '.' + this.name + '.dupFormSection(\'' + i + '\');"><span class="icon">a</span></span>', -1);
//		} else if( this.sectionCount != null ) {
//			lE = M.addSectionLabel(t, this.sectionCount(i, s));
		} else {
			// -1 means don't display count
			lE = M.addSectionLabel(t, -1);
		}
		f.appendChild(lE);
	}

	//
	// Get the section 
	//
	var tid = null;		// Section table ID
	var st = null; 		// Section table
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
		if( s.visible == null || (s.visible != null && s.visible == 'yes') ) {
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
	} else if( type == 'simpleform' || (type == null && s.fields != null) ) {
		st = this.createSectionForm(i, s.fields);		
//		tb = M.aE('tbody');
//		var ct = this.createFormFields(i, tb, this.panelUID, s.fields);
//		tid = this.panelUID + '_' + i;
//		if( ct == 0 || ct > 1 ) {
//			st = M.addTable(tid, 'list noheader form outline');
//		} else {
//			st = M.addTable(tid, 'list noheader form outline');
//		}
//		st.appendChild(tb);
	} else if( type == 'datepicker' ) {
		st = this.createDatePicker(i, s);
	} else if( type == 'dayschedule' ) {
		st = this.createDailySchedule(i, s);
	} else if( type == 'paneltabs' ) {
		st = this.createPanelTabs(i, this.sections[i]);
	} else if( type == 'html' ) {
		st = this.createHtml(i, this.sections[i]);
	} else if( type == 'htmlcontent' ) {
		st = this.createHtmlContent(i, this.sections[i]);
	} else if( type == 'simplethumbs' ) {
		st = this.createSimpleThumbnails(i);
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
//	var f = document.createDocumentFragment();
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
		
//		var d1 = M.aE('div', 'media_' + id, 'media_thumb media_' + type + ' clickable', '');
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
	//	var th = M.aE('thead');
	//	var tr = M.aE('tr');
	//	var c = M.aE('th',null,null,this.threadSubject(s));
	//	c.colSpan = 2;
	//	tr.appendChild(c);
	//	th.appendChild(tr);
	//	t.appendChild(th);
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
		if( sd.livesearchempty == 'yes' ) {
			f.setAttribute('onfocus', this.panelRef + '.liveSearchSection(\'' + s + '\',null,this,event);');
//		} else {
//			f.setAttribute('onfocus', this.panelRef + '.clearLiveSearches(\''+s+'\',null);');
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
//		} else {
//			tr.appendChild(M.aE('td',null,'button noprint'));
		}
	}
	// Check if there's a function for this section
	if( sd.fn != null ) {
		if( sd.fn != '' ) {
			tr.appendChild(M.aE('td',null,'buttons noprint', '<span class="icon">r</span>', sd.fn));
//			tr.appendChild(M.aE('td',null,'buttons noprint', '<img src=\'' + M.themes_root_url + '/default/img/arrow.png\'>', sd.fn));
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
//	var t = M.addTable(this.panelUID + '_' + s + '_livesearchresults');
//	var tb = M.aE('tbody');
//	var tr = M.aE('tr');
//	var c = M.aE('td');
//
//	if( sd.headerValues != null ) {
//		var t = M.addTable(this.panelUID + '_' + s + '_livesearch_grid', 'list simplegrid header border');
//		var th = M.aE('thead');
//		var tr = M.aE('tr');
//		for(var i=0;i<sd.headerValues.length;i++) {
////			var c = M.aE('th',null,null, sd.headerValues[i]);
//			tr.appendChild(c);
//		}
//		// If there's the possiblity of row being clickable, then add extra column to header for > (arrow).
//		sd.num_cols = sd.headerValues.length;
//		if( this.liveSearchResultRowFn != null ) {
//			sd.num_cols = sd.headerValues.length + 1;
//////			tr.appendChild(M.aE('th', null, 'noprint'));
//		}
//		th.appendChild(tr);
//		t.appendChild(th);
//	} else {
//		var t = M.addTable(this.panelUID + '_' + s + '_livesearch_grid', 'list simplegrid noheader border');
//	}
//	var tb = M.aE('tbody', this.panelUID + '_' + s + '_livesearchresultsgrid');
//	
//	// Table body should be empty, and hide table until there are results
//	t.appendChild(tb);
//	t.style.display = 'none';
//	frag.appendChild(t);

	return frag;
};

M.panel.prototype.liveSearchResultsTable = function(s, f, sd) {
	var t = M.addTable(this.panelUID + '_' + s + '_livesearchresults');
	var tb = M.aE('tbody');
	var tr = M.aE('tr');
	var c = M.aE('td');

	var n = this.panelUID + '_' + s;	
	if( f != null ) { n += '_' + f; }
	if( sd.livesearchtype == 'appointments' ) {
		var t = M.addTable(n + '_livesearch_grid', 'list dayschedule noheader noborder');
	} else if( sd.headerValues != null ) {
		var t = M.addTable(n + '_livesearch_grid', 'list simplegrid header border');
		var th = M.aE('thead');
		var tr = M.aE('tr');
		for(var i=0;i<sd.headerValues.length;i++) {
			var c = M.aE('th',null,null,sd.headerValues[i]);
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
//	if( sc.livesearchempty && inputElement.value 
	//
	// Check for enter key, and submit search
	//
	if( event.which == 13 && this.liveSearchSubmitFn != null && inputElement.value != '' ) {
		// Remove search results
		this.liveSearchSubmitFn(s, inputElement.value);
	}

//	if( (inputElement.value == '' && ((i != null && (sc.fields[i] != null && sc.fields[i].livesearchempty != null && sc.fields[i].livesearchempty == 'yes'))
//			|| (i == null && sc.livesearchempty != null && sc.livesearchempty == 'yes')) )
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
		this.liveSearchCb(s, i, inputElement.value);	// This then should call liveSearchShow 
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
		var nc = sc.livesearchcols;
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
					tr.className = 'clickable';
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
			var dtpieces = v.split('-');
			var dt = new Date(dtpieces[0], Number(dtpieces[1])-1, dtpieces[2]);
		} else {
			var dt = new Date();
		}
	} else {
		var dt = new Date();
	}
	var dtm = (dt.getTime())/1000;

	// format display date
	var dts = dt.getFullYear() + '-' + (dt.getMonth()+1) + '-' + dt.getDate();
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
	dt.setTime((dtm-86400)*1000);
	var dtprevs = dt.getFullYear() + '-' + (dt.getMonth()+1) + '-' + dt.getDate();
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
//		if( sd.livesearchempty == 'yes' ) {
			f.setAttribute('onfocus', this.panelRef + '.liveSearchSection(\'' + s + '\',null,this,event);');
//		} else {
//			f.setAttribute('onfocus', this.panelRef + '.clearLiveSearches(\''+s+'\',null);');
//		}
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
	dt.setTime((dtm+86400)*1000);
	var dtnexts = dt.getFullYear() + '-' + (dt.getMonth()+1) + '-' + dt.getDate();
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
//		if( this.delMediaFn != null || this.delMediaCl != null ) {
		// Add trash element
		var e = this.createMediaThumb('trash', 'trash', '' + M.themes_root_url + '/default/img/trash.png', 'Trash');
		f.appendChild(e);
//		}

	return f;
};

//
// Arguments:
// id - the id of the element
// type - the type of element this should be:
//			- image (clickable, dragable, dropable)
// 			- album (clickable, dragable, dropable)
//			- trash (clickable, dropable)
//// 			- addimage (clickable, dropable, iframe)
////			- addalbum (clickable, dropable, iframe)
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
	//	if( this.rowStyle != null ) { 
	//		tr.setAttribute('style', this.rowStyle(s, i, data[i]));
	//	}
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
	// 		  added to the top of the panel.  If there's more than 50 items in the
	//        data, then we should add the search box.
	//		  If the data is over 1000, then it should be a live search, and the complete data set
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
//		if( this.sections[s].searchable == 'yes' && data.length > 50 ) {
		// FIXME: Add code to allow searching of table content
//		}

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
				c = M.aE('td',null,cl,v);
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
					} else {
						c.sort_value = v;
					}
				}
				//
				// Sort type to be used when complex number found within
				//
				if( sc.sortable != null && sc.sortable == 'yes' && sc.sortTypes != null && sc.sortTypes[j] == 'altnumber' ) {
					c.sort_value = this.cellSortValue(s, i, j, data[i]);
				}
				// Check if a sortable size field, where we need to store the real size
//				if( sc.sortable != null && sc.sortable == 'yes' && sc.sortTypes != null && sc.sortTypes[j] == 'date' ) {
//					c.sort_value = v;
//				}
				
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
//	if( ct == 0 && this.noData != null && sc.addFn == null ) {
	if( ct == 0 && this.noData != null ) {
		// var t = M.addTable(null, 'list noheader border');
		// var tb = M.aE('tbody');
		var nd = this.noData(s);
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
//	if( (sc.addFn != null && sc.addFn != '') 
//		|| (sc.changeFn != null && sc.changeFn != '')
//		) {
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
	//		  < 100 split in eights
	//        < 500 split in alphabetical
	//
	var f = document.createDocumentFragment();
	var ss = 0;

	if( as != null ) {
		if( as == 'yes' ) {
			if( l != null && l.length < 6 ) { 
				ss = 1;
			} else if( l != null && l.length < 20 ) {
				ss = parseInt((l.length/2)+0.5);
			} else if( l != null && l.length < 50 ) {
				ss = parseInt((l.length/4)+0.5);
			} else if( l != null && l.length < 100 ) {
				ss = parseInt((l.length/8)+0.5);
			}
		} else if( as == 'always' ) {
			ss = 1;
		}
	}
	var ct = 0;
	var t = null;
	for(i in l) {
		if( l[i].visible != null && l[i].visible != 'yes' ) {
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
		if( M.curBusiness != null && M.curBusiness.modules['ciniki.clicktracker'] != null ) {
			cltr = 'M.api.getBg(\'ciniki.clicktracker.add\', {\'business_id\':M.curBusinessID, \'panel_id\':\'' + this.panelID + '\', \'item\':\'' + cltrl.replace(/ <span .*/, '') + '\'});';
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
		var t = M.addTable(null, 'list noheader border');
		var tb = M.aE('tbody');
		var tr = M.aE('tr');
		tr.appendChild(M.aE('td', null, null, this.noData(si)));
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
		if( l[i].visible != null && l[i].visible != 'yes' ) {	
			continue;
		}
		var t = M.addTable(null, 'list simplebuttons noheader border');
		var tb = M.aE('tbody');
		var tr = M.aE('tr');
	
		// Add the list item
		tr.appendChild(M.aE('td', null, 'button ' + i, l[i].label));

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
//	var ps = M.gE(this.panelUID + '_' + s);
	for(i=0;i<=21;i++) {
		var e = M.gE(this.panelUID + '_' + s + '_' + i) 
		if( e == null ) { break; }
		ps = e;
	}
	// Only allow 20 additions
	if(i == 21) { return false; }
//	var ns = s + '_' + i;
	if( this.sections[s].fields != null ) {
//		this.sections[ns] = {'label':'', 'fields':{}};
//		for(var j in this.sections[s].fields) {
//			this.sections[ns].fields[j+'_'+i] = this.sections[s].fields[j];
//		}
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
//		if( this.sections[s].livesearchempty != null ) {
//			this.sections[ns].livesearchempty = this.sections[s].livesearchempty;
//		}
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
			
//				else if( field.type == 'text' 
//					|| field.type == 'email' 
//					|| field.type == 'integer'
//					|| field.type == 'search' 
//					|| field.type == 'hexcolour' 
//					|| field.type == 'date' ) {
//					var f = M.aE('input', this.panelUID + '_' + fid, field.type);
//					f.setAttribute('name', fid);
//					if( field.type == 'date' || field.type == 'integer' ) {
//						f.setAttribute('type', 'text');
//					} else {
//						f.setAttribute('type', field.type);
//					}
//					if( field.size == 'small' ) {
//						f.setAttribute('class', field.type + ' small');
//					}
//					if( field.hint != null && field.hint != '' ) {
//						f.setAttribute('placeholder', field.hint);
//					}
//					var v = this.fieldValue(s, fid, field);
//					if( v != null ) {
//						f.value = v;
//					}
//					if( field.livesearch != null && field.livesearch != '' ) {
//						if( field.livesearchempty == 'yes' ) {
//							f.setAttribute('onfocus', this.panelRef + '.liveSearchSection(\'' + s + '\',\'' + fid + '\',this,event);');
//							// onblur won't work, result disappear before onclick can be processed
//							// f.setAttribute('onblur', this.panelRef + '.removeLiveSearch(\'' + fid + '\');');
//						}
//						f.setAttribute('onkeyup', this.panelRef + '.liveSearchSection(\'' + s + '\',\'' + fid + '\',this,event);');
//						// f.setAttribute('onblur', this.panelRef + '.removeLiveSearch(\'' + s + '\',\'' + fid + '\');');
//						f.setAttribute('autocomplete', 'off');
//						this.lastSearches[fid] = '';
//					}
//					var c = M.aE('td', null, 'input');
//					c.appendChild(f);
//					if( field.type == 'fkid' ) {
//						c.appendChild(f2);
//					}
//					if( field.type == 'date' ) {
//						c.appendChild(M.aE('span',null,'rbutton_off','D','M.' + this.appID + '.' + this.name + '.toggleFormFieldCalendar(\'' + fid + '\');'));
//					}
//					// Add time field
//					if( field.type == 'datetime' ) {
//						var f = M.aE('input', this.panelUID + '_' + fid, field.type);
//						f.setAttribute('name', fid + '_time');
//					}
//
//					tr.appendChild(c);
//				}
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
	var c = M.aE('td',null,'textfield aligncenter');
	var div = M.aE('div', null, 'buttons');
	for(i in sc.tabs) {
		if( sc.tabs[i].visible != null && sc.tabs[i].visible == 'no' ) { continue; }
		var e= null;
		if( i == sc.selected ) {
			e = M.aE('span', null, 'toggle_on', sc.tabs[i].label);
		} else {
			e = M.aE('span', null, 'toggle_off', sc.tabs[i].label, sc.tabs[i].fn);
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
	var t = M.addTable(this.panelUID + '_' + s, 'list form noheader noborder');
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
	var t = M.addTable(this.panelUID + '_formtabs', 'list form paneltabs noheader');
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
		if( ct == 0 || ct > 1 ) {
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
// M.createFormFields = function(nF, fI, cH, cF, fields) {
M.panel.prototype.createFormFields = function(s, nF, fI, fields, mN) {
	var ct = 0;
	var ef = 0;	// Keep track of the number of editable fields (used to display outline)
	for(i in fields) {
		//
		// Check if field should be shown
		//
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

		// Create the new row element
		var r = M.aE('tr');
		if( fields[i].hidelabel == null && fields[i].hidelabel != 'yes' ) {
			var l = M.aE('label');
			l.setAttribute('for', this.panelUID + '_' + i);
			l.appendChild(document.createTextNode(fields[i].label));
			var c = M.aE('td');
			c.className = 'label';
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
			r.appendChild(M.aE('td',null,'historybutton','<span class="rbutton_off">H</span>','M.' + this.appID + '.' + this.name + '.toggleFormFieldHistory(\'' + s + '\',\'' + fid + '\');'));
		} else if( this.fieldHistoryArgs != null && this.fieldHistoryArgs != '' && (fields[i].history == null || fields[i].history == 'yes') ) {
			r.appendChild(M.aE('td',null,'historybutton','<span class="rbutton_off">H</span>','M.' + this.appID + '.' + this.name + '.toggleFormFieldHistory(\'' + s + '\',\'' + fid + '\');'));
		}
		ct++;

		//
		// If the field added was of type image_id, then extra buttons are required
		//
		if( fields[i].type == 'image_id' ) {
			var img_id = this.fieldValue(s, i, fields[i], mN);
			var btns = M.aE('div', null, 'buttons');
			if( img_id > 0 ) {
				// Show buttons for rotate, etc...
				if( this.rotateImage != null ) {
					var btn = M.aE('span', null, 'toggle_off', 'Rotate');
					btn.setAttribute('onclick', this.panelRef + '.rotateImage(\'' + i + '\');');
					btns.appendChild(btn);
				} 
				if( fields[i].controls == 'all' ) {
					var btn = M.aE('span', null, 'toggle_off', 'Rotate');
					btn.setAttribute('onclick', this.panelRef + '.rotateImg(\'' + i + '\');');
					btns.appendChild(btn);
				}
			}
			var f = null;
			if( this.uploadImage != null || ((this.addDropImage != null || fields[i].addDropImage != null) && fields[i].controls == 'all') ) {
				
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
				//
				// Create the form upload field, but hide it
				//
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
			if( img_id > 0 ) {
				// Show delete button
				if( fields[i].deleteImage != null ) {
					var btn = M.aE('span', null, 'toggle_off', 'Delete');
					btn.setAttribute('onclick', fields[i].deleteImage + '(\'' + i + '\');');
					btns.appendChild(btn);
				} else if( this.deleteImage != null ) {
					var btn = M.aE('span', null, 'toggle_off', 'Delete');
					btn.setAttribute('onclick', this.panelRef + '.deleteImage(\'' + i + '\');');
					btns.appendChild(btn);
				}
			}
			if( btns.children.length > 0 ) {
				var r = M.aE('tr');
				var td = M.aE('td',null,'aligncenter');
				td.appendChild(btns);
				if( f != null ) {
					td.appendChild(f);
				}
				r.appendChild(td);
				nF.appendChild(r);
			}
		}
	}

	if( ct == 0 && this.noData != null ) {
		var tr = M.aE('tr');
		tr.appendChild(M.aE('td', null, null, this.noData()));
		tb.appendChild(tr);
	}

	return ef;
};

M.panel.prototype.uploadFile = function(i) {
	var f = M.gE(this.panelUID + '_' + i + '_upload');
	if( f != null ) { f.click(); }
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
//			if( field.livesearchempty == 'yes' ) {
				f2.setAttribute('onfocus', this.panelRef + '.liveSearchSection(\'' + s + '\',\'' + i + sFN + '\',this,event);');
				// onblur won't work, result disappear before onclick can be processed
				// f.setAttribute('onblur', this.panelRef + '.removeLiveSearch(\'' + i + '\');');
//			}
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
	else if( field.type == 'text' || field.type == 'email' 
		|| field.type == 'integer'
		|| field.type == 'search' 
		|| field.type == 'hexcolour' 
		|| field.type == 'date' ) {
		var f = M.aE('input', this.panelUID + '_' + i + sFN, field.type);
		f.setAttribute('name', i);
		if( field.autofocus != null && field.autofocus == 'yes' ) {
//			f.setAttribute('autofocus', '');
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
		if( field.hint != null && field.hint != '' ) {
			f.setAttribute('placeholder', field.hint);
		}
		var v = this.fieldValue(s, i, field, mN);
		if( v != null ) {
			f.value = v;
		}
		if( field.livesearch != null && field.livesearch == 'yes' ) {
//			if( field.livesearchempty == 'yes' ) {
				f.setAttribute('onfocus', this.panelRef + '.liveSearchSection(\'' + s + '\',\'' + i + sFN + '\',this,event);');
				// onblur won't work, result disappear before onclick can be processed
				// f.setAttribute('onblur', this.panelRef + '.removeLiveSearch(\'' + i + '\');');
//			}
			f.setAttribute('onkeyup', this.panelRef + '.liveSearchSection(\'' + s + '\',\'' + i + sFN + '\',this,event);');
			f.setAttribute('autocomplete', 'off');
			this.lastSearches[i] = '';
		}
		c.appendChild(f);
//			if( field.type == 'fkid' ) {
//				c.appendChild(f2);
//			}
		if( field.type == 'date' ) {
			c.appendChild(M.aE('span',null,'rbutton_off','D','M.' + this.appID + '.' + this.name + '.toggleFormFieldCalendar(\'' + fid + sFN + '\');'));
			//var img = M.aE('img', null, 'calendarbutton');
			//img.src = '' + M.themes_root_url + '/default/img/calendarA.png';
			//img.setAttribute('onclick', 'M.' + this.appID + '.' + this.name + '.toggleFormFieldCalendar(\'' + i + '\');');
			//c.appendChild(img);
		}
		// Display extra options for a field
//		if( field.option_field != null && field.option_field != '' && field.options != null ) {
//			d = M.aE('div', this.panelUID + '_' + i + sFN + '_options', 'toggles');
//			for(var j in field.options) {
//				f2 = M.aE('span', null, 'toggle_off', '' + j);
//				f2.setAttribute('onfocus', this.panelRef + '.clearLiveSearches(\''+s+'\',\''+i+sFN+'\');');
//				f2.setAttribute('onclick', this.panelRef + '.setFromButton(this, \'' + i + sFN + '\',\'' + field.options[j] + '\');');
//				f2.setAttribute('onclick', this.panelRef + '.setToggleField(this, \'' + i + sFN + '\',\'' + field.none + '\',\'' + field.fn + '\');');
//				d.appendChild(f2);
//			}
//			c.appendChild(d);

//			var f = M.aE('span', this.panelUID + '_' + fid + sFN + '_' + j);
//			f.setAttribute('onfocus', this.panelRef + '.clearLiveSearches(\''+s+'\',\''+i+sFN+'\');');
//			if( v == j ) {
//				f.className = 'toggle_on';
//			} else {
//				f.className = 'toggle_off';
//			}
//			f.innerHTML = field.toggles[j];
//			f.setAttribute('onclick', this.panelRef + '.setToggleField(this, \'' + i + sFN + '\',\'' + field.none + '\',\'' + field.fn + '\');');
//			div.appendChild(f);
//		}
		// Add time field
//			if( field.type == 'datetime' ) {
//				var f = M.aE('input', this.panelUID + '_' + i, field.type);
//				f.setAttribute('name', i + '_time');
//			}
	}
	else if(  field.type == 'appointment' ) {
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
					f.value = M.dateFormat(this.fieldValue(s, i + '_date', field, mN));
				}
			}
		}
		c.appendChild(f);
		c.appendChild(M.aE('span',null,'rbutton_off','D','M.' + this.appID + '.' + this.name + '.toggleFormFieldAppointment(\'' + i + sFN + '\');'));
	}
	else if( field.type == 'textarea' ) {
		var f = M.aE('textarea', this.panelUID + '_' + i + sFN);
		f.setAttribute('name', i + sFN);
		f.setAttribute('onfocus', this.panelRef + '.clearLiveSearches(\''+s+'\',\''+i+sFN+'\');');
		if( field.size != null && field.size == 'small' ) {
			f.setAttribute('rows', 2);
			f.setAttribute('class', 'small');
		} else if( field.size != null && field.size == 'large' ) {
			f.setAttribute('rows', 12);
			f.setAttribute('class', 'large');
		} else {
			f.setAttribute('rows', 6);
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
		c.className = 'textarea';
		c.appendChild(f);
	}
	else if( field.type == 'select' ) {
		var sel = M.aE('select', this.panelUID + '_' + fid + sFN);
		sel.setAttribute('name', fid + sFN);
		sel.setAttribute('onfocus', this.panelRef + '.clearLiveSearches(\''+s+'\',\''+fid+sFN+'\');');
		var o = field.options;
		for(j in o) {
			var n = o[j];
			var v = j;
			// If option_name is specified, then option is a complex object  
			// These are the result of an object sent back through cinikiAPI
			if( field.complex_options != null ) { 
				n = o[j][field.complex_options.subname][field.complex_options.name];
				v = o[j][field.complex_options.subname][field.complex_options.value];
			} 
			//
			// Add the options to the select, and choose which one to have selected
			//
			if( v == this.fieldValue(s, i, field, mN) ) {
				var op = new Option(n, v, 0, 1);
			} else {
				var op = new Option(n, v);
			}
// Code which can display a background colour behind select option, but does not work in all browsers.
//					if( field['complex_options'] != null && field['complex_options']['bgcolor'] != null ) { 
//						//op.setAttribute('background','#' + field['complex_options']['bgcolor']);
//						op.style.background = '#' + o[j][field['complex_options']['subname']][field['complex_options']['bgcolor']];
//					}
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
		for(j in field.flags) {
			if( field.flags[j] == null ) { continue; }
			if( field.flags[j].active != null && field.flags[j].active == 'no' ) { continue; }
			var f = M.aE('span', this.panelUID + '_' + fid + sFN + '_' + j);
			f.setAttribute('onfocus', this.panelRef + '.clearLiveSearches(\''+s+'\',\''+i+sFN+'\');');
			var bit_value = (v&Math.pow(2,j-1))==Math.pow(2,j-1)?1:0;
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
				f.onclick = function(event) {event.stopPropagation();
					if( this.className == 'flag_on' ) { this.className = 'flag_off'; } 
					else {
						for(k in this.parentNode.children) {
							this.parentNode.children[k].className = 'flag_off';
						}
						this.className = 'flag_on';
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
	else if( field.type == 'multitoggle' || field.type == 'toggle' ) {
		if( field.join != null && field.join == 'no' ) {
			c.className = 'multiselect';
		} else {
			c.className = 'multitoggle';
		}
		var div = M.aE('div', this.panelUID + '_' + fid + sFN);
		var v = this.fieldValue(s, i, field, mN);
		if( v == '' && field.default != null && field.default != '' ) {
			v = field.default;
		}
		for(j in field.toggles) {
			var f = M.aE('span', this.panelUID + '_' + fid + sFN + '_' + j);
			f.setAttribute('onfocus', this.panelRef + '.clearLiveSearches(\''+s+'\',\''+i+sFN+'\');');
			if( v == j ) {
				f.className = 'toggle_on';
			} else {
				f.className = 'toggle_off';
			}
			f.innerHTML = field.toggles[j];
			f.setAttribute('onclick', this.panelRef + '.setToggleField(this, \'' + i + sFN + '\',\'' + field.none + '\',\'' + field.fn + '\');');
			div.appendChild(f);
		}
		c.appendChild(div)
		if( field.hint != null && field.hint != '' ) {
			c.appendChild(M.aE('span', this.panelUID + '_' + fid + sFN + '_hint', 'hint', field.hint));
		}
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
		c.appendChild(div)
		if( field.hint != null && field.hint != '' ) {
			c.appendChild(M.aE('span', this.panelUID + '_' + fid + sFN + '_hint', 'hint', field.hint));
		}
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
//		var f = M.aE('input', this.panelUID + '_' + fid + sFN + '_new', 'text');
//		f.setAttribute('name', i + sFN + '_new');
//		if( field.hint != null && field.hint != '' ) {
//			f.setAttribute('placeholder', field.hint);
//		}
//		c.appendChild(f);	
	}
	else if( field.type == 'image' ) {
		var d = M.aE('div', this.panelUID + '_' + i + sFN + '_preview', 'image_preview');
		var img = this.fieldValue(s, i + sFN + '_img', field, mN);
		if( img != null && img != '' ) {
			d.innerHTML = img;
		}
		c.appendChild(d);
		// File upload doesn't work on ios and will break the field history button. :(
		if( M.device != 'ipad' && M.device != 'iphone' ) {
			var f = M.aE('input', this.panelUID + '_' + i + sFN, 'file');
			f.setAttribute('name', i + sFN);
			f.setAttribute('onfocus', this.panelRef + '.clearLiveSearches(\''+s+'\',\''+i+sFN+'\');');
			f.setAttribute('type', 'file');
			c.appendChild(f);
		}
	}
	else if( field.type == 'file' ) {
		// File upload doesn't work on ios and will break the field history button. :(
		if( M.device != 'ipad' && M.device != 'iphone' ) {
			var f = M.aE('input', this.panelUID + '_' + i + sFN, 'file');
			f.setAttribute('onfocus', this.panelRef + '.clearLiveSearches(\''+s+'\',\''+i+sFN+'\');');
			f.setAttribute('name', i + sFN);
			f.setAttribute('type', 'file');
			c.appendChild(f);
		}
	}
	else if( field.type == 'image_id' ) {
		var d = M.aE('div', this.panelUID + '_' + i + sFN + '_preview', 'image_preview');
		var img_id = this.fieldValue(s, i, field, mN);
		if( img_id != null && img_id != '' && img_id > 0 ) {
			d.innerHTML = '<img src=\'' + M.api.getBinaryURL('ciniki.images.get', {'business_id':M.curBusinessID, 'image_id':img_id, 'version':'original', 'maxwidth':'0', 'maxheight':'300'}) + '&ts=' + new Date().getTime() + '\' />';
		} else {
			d.innerHTML = '<img src=\'/ciniki-mods/core/ui/themes/default/img/noimage_200.jpg\' />';
		}
		c.appendChild(d);
		// File upload doesn't work on ios and will break the field history button. :(
//		if( M.device != 'ipad' && M.device != 'iphone' ) {
			var f = M.aE('input', this.panelUID + '_' + i + sFN, 'text');
			f.setAttribute('name', i + sFN);
			f.setAttribute('type', 'hidden');
			f.value = img_id;
			c.appendChild(f);
//		}
	}
	else if( field.type == 'noedit' ) {
		c.className = 'noedit';
		c.setAttribute('id', this.panelUID + '_' + i + sFN);
		c.setAttribute('name', i + sFN);
//		f.setAttribute('onfocus', this.panelRef + '.clearLiveSearches(\''+s+'\',\''+i+sFN+'\');');
		c.setAttribute('type', field.type);
		if( this.fieldValue != null ) {
			c.innerHTML = this.fieldValue(s, i, field, mN);	
		} else {
			c.innerHTML = '';
		}
	}

	return c;
};

M.panel.prototype.updateImgPreview = function(fid, img_id) {
	var d = M.gE(this.panelUID + '_' + fid + '_preview');
	if( img_id != null && img_id != '' ) {
		d.innerHTML = '<img src=\'' + M.api.getBinaryURL('ciniki.images.get', {'business_id':M.curBusinessID, 'image_id':img_id, 'version':'original', 'maxwidth':'0', 'maxheight':'300'}) + '&ts=' + new Date().getTime() + '\' />';
	} else {
		d.innerHTML = '<img src=\'/ciniki-mods/core/ui/themes/default/img/noimage_200.jpg\' />';
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
		if( typeof v == 'string' ) { v = parseInt(v, 10); }
		for(j in f.flags) {
			if( f.flags[j] == null ) { continue; }
			if( f.flags[j].active != null && f.flags[j].active == 'no' ) { continue; }
			var e = M.gE(this.panelUID + '_' + field + sFN + '_' + j);
			if( (v&Math.pow(2, j-1)) == Math.pow(2,j-1) ) {
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
	} else {
		//
		// If not a special type, then set the input field value to the
		// 
		M.gE(this.panelUID + '_' + field + sFN).value = v;
	}

	if( f.type == 'fkid' ) {
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
	h.previousSibling.children[h.previousSibling.children.length-1].children[0].className = 'rbutton_off';

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
M.panel.prototype.toggleFormFieldHistory = function(s, field) {
	var h = M.gE(this.panelUID + '_' + field + '_history');
	if( h != null ) {
		this.removeFormFieldHistory(field);
	} else {
		//
		// Issue callback to get the history for this field
		//
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
				var h = M.gE(p.panelUID + '_' + field + '_history');
				h.previousSibling.children[h.previousSibling.children.length-1].children[0].className = 'rbutton_on';
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
			var h = M.gE(this.panelUID + '_' + field + '_history');
			h.previousSibling.children[h.previousSibling.children.length-1].children[0].className = 'rbutton_on';
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
			this.showFieldCalendars(field, Number(v.year), Number(v.month)-1, v, 'calendar', null, v.time);
		} else {
			this.showFieldCalendars(field, Number(v.year), Number(v.month)-1, {'year':'', 'month':'', 'day':'', 'hour':'', 'minute':''}, 'calendar', null, v.time);
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
				c.setAttribute('onclick', fn + '(\'' + field + '\',\'' + cur_year + '-' + (cur_month + 1) + '-' + j + '\');');
			} else {
				c.setAttribute('onclick', this.panelRef + '.setFromCalendar(\'' + field + '\',\'' + cur_year + '-' + (cur_month + 1) + '-' + j + '\');');
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
	M.gE(this.panelUID + '_' + field).value = v;
	this.removeFormFieldCalendar(field);
};

M.panel.prototype.setFromAppointment = function(field, date, time, ad) {
	v = M.dateFormat(date);
	var f = this.formField(field);
	var e = M.gE(this.panelUID + '_' + field);
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
//		M.gE(this.panelUID + '_' + field).oldtime = this.parseDate(M.gE(this.panelUID + '_' + field).value)['time'];
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
					tr.appendChild(c);
				} else if(i > 0) {
					pc.rowSpan = parseInt(i)+1;
				}
				// This element is used to setup fixed sizes for rows
				tr.appendChild(M.aE('td', null, 'schedule_interval', ''));

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

//						var c = M.aE('td', null, 'empty')
//						c.colSpan = 10;
//						tr.appendChild(c);

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

//				var c = M.aE('td', null, 'empty');
//				c.colSpan = 10;
//				tr.appendChild(c);
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
		tr.appendChild(c);
		// This element is used to setup fixed sizes for rows
		tr.appendChild(M.aE('td', null, 'schedule_interval', ''));
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

	//		tr.appendChild(M.aE('td', null, 'empty'));
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

//
// Arguments:
// wID - the element ID for the button bar
// 
M.panel.prototype.showButtons = function(wID, buttons) {
	//  
	// Create the menu buttons
	//
	var c = 0;
	for(i in buttons) {
		var icn = '';
		switch(buttons[i].icon) {
			// case 'back': icn = 'b'; break;
			case 'rewind': icn = 'B'; break;
			case 'prev': icn = 'p'; break;
			case 'next': icn = 'n'; break;
			case 'add': icn = 'a'; break;
			case 'settings': icn = 's'; break;
			case 'save': icn = 'S'; break;
			case 'edit': icn = 'y'; break;
			case 'exit': 
			case 'close': 
			case 'cancel': icn = 'X'; break;
			case 'more': icn = 'm'; break;
			case 'tools': icn = 'A'; break;
			case 'admin': icn = 'A'; break;
			case 'account': icn = 'w'; break;
			case 'logout': icn = 'L'; break;
			case 'forward': icn = 'f'; break;
		}
		switch(buttons[i].label) {
			case 'Home': icn = 'h';break;
			case 'Back': icn = 'b';break;
			case 'Close':
			case 'Cancel': icn = 'X';break;
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
			l.appendChild(M.aE('div', null, 'button ' + i, '<span class="icon">' + icn + '</span><span class="label">' + buttons[i].label + '</span>'));
			l.setAttribute('onclick', bfn + 'return false;');
			l.className = wID;
			c++;
		}
	}
	// Clear unused spaces
	for(;c<2;c++){
		var l = M.clr(this.appPrefix + '_' + wID + '_' + c);
		l.className = wID + ' hide';
		l.setAttribute('onclick', '');
	}
};

//
// Go through the form details, and all sections to find all the elements 
// of the form.  Then compare to see if any are updated.
//
// Arguments:
// fs - The flag to specifiy if all fields should be included, even if not updated.  This is used
//		for forms which add information.  When a form is for editing data, it should be 'no'.
//
M.panel.prototype.serializeForm = function(fs) {
	// The content variable to store the encoded variables
	var c = '';	
	var count = 0;		// The number of changes

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

	for(i in this.sections) {
		//
		// Grid elements
		//
		var s = this.sections[i];
		if( s.multi != null && s.multi == 'yes' ) {
			continue;
		}
		if( s.active != null && s.active == 'no' ) {
			continue;	// Skip inactive sections
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
		// All non-grid elements
		//
		else {
			// Skip multi sections, they need to be serialized another way
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
				var n = this.formFieldValue(f, fid);
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
			}
		}
	}

	return c;
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
	for(j in s.fields) {
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
//		for forms which add information.  When a form is for editing data, it should be 'no'.
//
M.panel.prototype.serializeFormData = function(fs) {
	// The content variable to store the encoded variables
	var c = new FormData;	
	var count = 0;		// The number of changes

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

	for(i in this.sections) {
		//
		// Grid elements
		//
		var s = this.sections[i];
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
					if( file != null && file.files[0] != null ) {
						c.append(fid, file.files[0]);
						count++;
					}
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
							c += encodeURIComponent(fid) + '=' + encodeURIComponent(n) + '&';
						}
					}
				}
			}
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
		if( n == null || n == '' ) { n = 0; }
		for(j in f.flags) {
			if( f.flags[j] == null ) { continue; }
			if( f.flags[j].active != null && f.flags[j].active == 'no' ) { continue; }
			if( M.gE(this.panelUID + '_' + fid + '_' + j).className == 'flag_on' ) {
				// Toggle bit on
				n |= Math.pow(2, j-1);
			} else {
				// Toggle bit off
				if( (n&Math.pow(2, j-1)) > 0 ) { n ^= Math.pow(2, j-1); }
			}
		}
	} else if( f.type == 'multitoggle' || f.type == 'toggle' ) {
		n = 0;
		for(j in f.toggles) {
			if( M.gE(this.panelUID + '_' + fid + '_' + j).className == 'toggle_on' ) {
				n = j;
			}
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
	} else if( f.type == 'tags' ) {
		n = '';
		c = '';
//		var v = M.gE(this.panelUID + '_' + fid + '_new').value;
//		if( v == null ) { v = ''; }
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
	} else {
		n = M.gE(this.panelUID + '_' + fid).value;
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
		}
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
		{'business_id':M.curBusinessID}, 
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
//			}
			p._uploadCurrent++;
			p.uploadDropImagesNext();
		});
	if( rsp == null ) {
		alert('Unknown error occured, please try again');
		M.stopLoad();
		return false;
	}
//	if( rsp.stat != 'ok' ) {
//		M.stopLoad();
//		M.api.err(rsp);
//		return false;
//	}
};

M.panel.prototype.rotateImg = function(fid) {
	var iid = this.formValue(fid);
	var p = this;
	var rsp = M.api.getJSONCb('ciniki.images.rotate', {'business_id':M.curBusinessID,
		'image_id':iid}, function(rsp) {
			if( rsp.stat != 'ok' ) {
				M.api.err(rsp);
				return false;
			}
			p.updateImgPreview(fid, iid);
		});
};
