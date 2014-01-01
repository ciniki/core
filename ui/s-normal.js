//
// This file contains the javascript functions to specific to a normal sized screen.
// 
//

//
// Arguments:
// e - the element to resize
//
M.resize = function(e) {	
	var mh = document.getElementById('m_help');
	var mc = document.getElementById('m_container');
	if( mh.style.display == 'block' ) {
		if( mh.className == 'noborder' ) {
			M.setWidth('m_help', (mh.parentNode.offsetWidth - document.getElementById('m_container').offsetWidth) + 'px');
		} else {
			M.setWidth('m_help', (mh.parentNode.offsetWidth - document.getElementById('m_container').offsetWidth - 2) + 'px');
			M.setWidth('mh_header', (mh.parentNode.offsetWidth - document.getElementById('m_container').offsetWidth - 1) + 'px');
		}
		var h = document.getElementById('mh_header').offsetHeight;
		if( mh.offsetHeight < window.innerHeight ) {
			mh.style.height = (window.innerHeight) + 'px';
		} else if( mc.style.display != 'none' && mh.offsetHeight < mc.offsetHeight ) {
//			alert(mc.offsetHeight + 'px');
//			mh.style.height = (mc.offsetHeight) + 'px';
		} else if( mc.offsetHeight < window.innerHeight ) {
			mh.style.height = (window.innerHeight) + 'px';
		}
	} else {

	}
}

M.toggleHelp = function(helpUID) {
	//
	// Check if help is loaded
	// 
	if( helpUID == null || (M.ciniki_core_help != null && document.getElementById('m_help').style.display != 'none' && M.ciniki_core_help.curHelpUID == helpUID) ) {
		M.ciniki_core_help.close();
		M.hide('m_help');
		M.setWidth('m_container', '100%');
		M.setWidth('mc_header', '100%');
		M.show('m_container');
	} else {
		// M.curHelpUID = helpUID;
		M.startApp('ciniki.core.help', null, null, 'mh', {'helpUID':helpUID});
		if( window.innerWidth < 800 ) {
			M.hide('m_container');
			document.getElementById('m_help').className = 'noborder';
			M.setWidth('m_help', '100%');
			M.setWidth('mh_header', '100%');
		} else {
			M.setWidth('m_container', '66%');
			M.setWidth('mc_header', '66%');
			M.setWidth('mh_header', '34%');
			M.setWidth('m_help', (window.innerWidth - document.getElementById('m_container').offsetWidth - 1) + 'px');
			document.getElementById('m_help').className = 'leftborder';
		}
		M.show('m_help');
		M.resize();
	}
}

//
// This function will display a calendar for the user to pick a date from
//
M.panel.setupFormFieldCalendar = function(fieldID, field, dt) {
	//
	// Get the form field, and find the parent row
	//
	var fD = document.getElementById(this.panelUID + '_' + fieldID).parentNode.parentNode;
	var hD = M.aE('tr', this.panelUID + '_' + fieldID + '_calendar', 'fieldcalendar');
	var hC = M.aE('td', null, 'calendar');
	
	//
	// Get the number of cells from the field row in the table.
	//
	hC.colSpan = fD.children.length;
	hC.innerHTML = 'Calendar for ' + dt.year + '-' + dt.month;

	hD.appendChild(hC);
	fD.parentNode.insertBefore(hD, fD.nextSibling);
}


//
// This function will display the history information associated with 
// a form element.
//
// M.setupFormFieldHistory = function(formID, field, history, users, options, toggleOptions) {

M.panel.setupFormFieldHistory = function(fieldID, field) {
	
	//
	// Get the form field, and find the parent row
	//
	var fD = M.gE(this.panelUID + '_' + fieldID).parentNode.parentNode;
	var hD = M.aE('tr', this.panelUID + '_' + fieldID + '_history', 'fieldhistory');
	var hC = M.aE('td', null, 'history');

	//
	// Get the number of cells from the field row in the table.
	//
	hC.colSpan = fD.children.length;

	var history = this.fieldHistories[fieldID].history;
	var users = this.fieldHistories[fieldID].users;

	var t = M.addTable(null, 'fieldhistory noheader border');
	var tb = M.aE('tbody');
	if( history.length == 0 ) {
		var tr = M.aE('tr');
		var c1 = M.aE('td', null, 'fieldvalue', 'No history');
		tr.appendChild(c1);
		tb.appendChild(tr);
	} else {
		for(i in history) {
			var tr = M.aE('tr', null, 'singleline');
			//
			// Create the cell for username and age
			//
			var c1 = M.aE('td');
			var age = '';
			if( M.userSettings == null || M.userSettings['ui.history.date.display'] == null || M.userSettings['ui.history.date.display'] == 'age' ) {
				age = ', <span class=\'age\'>' + history[i].action.age + ' ago</span>';
			} else if( M.userSettings['ui.history.date.display'] == 'datetime' ) {
				age = ', <span class=\'age\'>' + history[i].action.date + '</span>';
			} else if( M.userSettings['ui.history.date.display'] == 'datetimeage' ) {
				age = ', <span class=\'age\'>' + history[i].action.date + ' (' + history[i].action.age + ' ago)</span>';
			}
			c1.innerHTML = '<span class=\'username\'>' 
				+ history[i].action.user_display_name + '</span>'
				+ age;

			//
			// Create cell for the value from the history
			//
			var c3 = M.aE('td', null, 'fieldvalue');
		
			if( field != null && field.type == 'select' && field.options != null ) {
				if( field.complex_options != null ) {
					// 
					// Find the label for the history through a complex_options mapping information
					//
					for(j in field.options) {
						if( field.options[j][field.complex_options.subname][field.complex_options.value] == history[i].action.value ) {
							c3.innerHTML = field.options[j][field.complex_options.subname][field.complex_options.name];
							break;
						}
					}
				} else {
					c3.innerHTML = field.options[history[i].action.value];
				}
			} else if( field != null && field.type == 'flags' && field.flags != null ) {
				for(j in field.flags) {
					if( (history[i].action.value&Math.pow(2, j-1)) == Math.pow(2,j-1) ) {
						if( c3.innerHTML != '' ) { c3.innerHTML += ', '; }
						c3.innerHTML += field.flags[j].name;
					}
				}
			} else if( field != null && field.type == 'toggle' && field.toggles != null ) {
				for(j in field.toggles) {
					if( j == history[i].action.value ) {
						c3.innerHTML = field.toggles[j];
					}
				}
			} else if( field != null && field.type == 'tags' ) {
				if( history[i].action.action == '1' ) {
					c3.innerHTML = '+ ' + history[i].action.value;
				} else if( history[i].action.action == '3' ) {
					c3.innerHTML = '- ' + history[i].action.value;
				}
//				c3.innerHTML = history[i].action.value.replace(/::/g, ', ');
			} else if( field != null && field.type == 'colourswatches' && field.colours != null ) {
				if( history[i].action.value != null && history[i].action.value != '' ) {
					c3.innerHTML = "<span class='colourswatch' style='background-color: " + history[i].action.value + ";'>&nbsp;</span>";
				} else {
					c3.innerHTML = "<span class='colourswatch' style='background-color: #ffffff;'>&nbsp</span>";
				}
			} else if( field != null && field.type == 'colour' ) {
				c3.innerHTML = "<span class='colourswatch' style='background-color: " + history[i].action.value + ";'>&nbsp</span>";
			} else if( history[i].action.formatted_value != null ) {
				c3.innerHTML = history[i].action.formatted_value;
			} else if( history[i].action.fkidstr_value != null ) {
				c3.innerHTML = history[i].action.fkidstr_value;
			} else { 
				c3.innerHTML = history[i].action.value;
			}

			if( history[i].action.label != null ) {
				c3.innerHTML = history[i].action.label + c3.innerHTML;
			}
			
			if( history[i].action.field_id != null ) {
				c3.setAttribute('onclick', this.panelRef + '.setFromFieldHistory(\'' + history[i].action.field_id + '\',\'' + fieldID + '\',\'' + i + '\');');
			} else {
				c3.setAttribute('onclick', this.panelRef + '.setFromFieldHistory(\'' + fieldID + '\',\'' + fieldID + '\',\'' + i + '\');');
			}
			
			//
			// Append cells and row
			//
			tr.appendChild(c1);
			tr.appendChild(c3);
			tb.appendChild(tr);
		}
	}

	t.appendChild(tb);
	hC.appendChild(t);

	hD.appendChild(hC);
	fD.parentNode.insertBefore(hD, fD.nextSibling);
	// M.resize();
}

//
// This function will return the DOM elements required for a thread followup
//
// Arguments:
// s - The section the thread is located in a sectionedcomplex structure
// i - The number in the array of the data
// d - The data for the followup
//
M.panel.createThreadFollowup = function(s, i, d) {
	var f = document.createDocumentFragment();	

	var r = M.aE('tr', null, 'followup');
	var c = M.aE('td', null, 'userdetails');
	if( this.threadFollowupUser != null ) {
		c.appendChild(M.aE('span', '', 'username', this.threadFollowupUser(s, i, d)));
	}
	c.appendChild(M.aE('br'));
	if( this.threadFollowupAge != null ) {
		c.appendChild(M.aE('span', '', 'age', this.threadFollowupAge(s, i, d) + ' ago'));
		if( this.threadFollowupDateTime != null ) {
			c.appendChild(M.aE('br'));
			c.appendChild(M.aE('span', '', 'age', '(' + this.threadFollowupDateTime(s, i, d) + ')'));
		}
	}
	r.appendChild(c);

	if( this.threadFollowupContent != null ) {
		c = M.aE('td', '', 'content', this.threadFollowupContent(s, i, d));
	} else {
		c = M.aE('td', '', 'content');
	}
	r.appendChild(c);
	f.appendChild(r);

	return f;
}
