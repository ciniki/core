//
// This file contains the functions which support a small screen mobile device.
//

//
// Arguments:
// e - the element to resize
//
M.resize = function(e) {
    var mw = M.gE('m_website');
    var mwi = M.gE('mc_website_iframe');
    if( mw.style.display == 'block' ) {
        var h = document.getElementById('mw_header').offsetHeight;
//        if( mh.offsetHeight < window.innerHeight ) {
//        mw.style.height = (window.innerHeight) + 'px';
        mwi.style.height = (window.innerHeight-h-5) + 'px';
    }
    var mp = M.gE('m_pdf');
    var mpi = M.gE('mc_pdf_iframe');
    if( mp.style.display == 'block' ) {
        var h = document.getElementById('mp_header').offsetHeight;
        mpi.style.height = (window.innerHeight-h-5) + 'px';
    }
}

M.toggleHelp = function(helpUID) {
    if( helpUID == null || (M.ciniki_core_help != null && document.getElementById('m_help').style.display != 'none' && M.ciniki_core_help.curHelpUID == helpUID) ) {
        M.hideChildren('m_body', 'm_container');
    } else {
        M.curHelpUID = helpUID;
        M.hideChildren('m_body', 'm_help');
        M.startApp('ciniki.core.help', null, null, 'mh', {'helpUID':helpUID});
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
    hC.innerHTML = 'Calendar for ' + dt.date;

    hD.appendChild(hC);
    fD.parentNode.insertBefore(hD, fD.nextSibling);
}

//
// This function will display the history information associated with 
// a form element.
//
M.panel.setupFormFieldHistory = function(fieldID, field) {
    
    //
    // Get the form field, and find the parent row
    //
    var fD = document.getElementById(this.panelUID + '_' + fieldID).parentNode.parentNode;
    var hD = M.aE('tr', this.panelUID + '_' + fieldID + '_history', 'fieldhistory');
    var hC = M.aE('td', null, 'history');

    //
    // Get the number of cells from the field row in the table.
    //
    hC.colSpan = fD.children.length;

    var history = this.fieldHistories[fieldID].history;
    var users = this.fieldHistories[fieldID].users;

    var t = M.addTable(null, 'fieldhistory noheader');
    var tb = M.aE('tbody');
    if( history.length == 0 ) {
        var tr = M.aE('tr');
        var c1 = M.aE('td', null, 'fieldvalue', 'No history');
        tr.appendChild(c1);
        tb.appendChild(tr);
    } else {
        for(i in history) {
            //
            // Put the username and age on the first row
            //
            var tr = M.aE('tr');
            var c1 = M.aE('td');
            var age = '';
            if( M.userSettings == null || M.userSettings['ui-history-date-display'] == null || M.userSettings['ui-history-date-display'] == 'age' ) {
                age = ', <span class=\'age\'>' + history[i].action.age + ' ago</span>';
            } else if( M.userSettings['ui-history-date-display'] == 'datetime' ) {
                age = ', <span class=\'age\'>' + history[i].action.date + '</span>';
            } else if( M.userSettings['ui-history-date-display'] == 'datetimeage' ) {
                age = ', <span class=\'age\'>' + history[i].action.date + ' (' + history[i].action.age + ' ago)</span>';
            }
            c1.innerHTML = '<span class=\'username\'>' 
                + history[i].action.user_display_name + '</span>'
                + age;
            tr.appendChild(c1);
            if( field != null && field.type == 'colourswatches' ) {
                tr.className = 'singleline';
            } else {
                c1.className = 'user';
                tb.appendChild(tr);
                tr = document.createElement('tr');
            }

            var c3 = document.createElement('td');
            c3.className = 'fieldvalue';
            c3.colSpan = 2;
            if( field != null && field.type == 'select' && field.options != null ) {
                if( field.complex_options != null ) {
                    // 
                    // Find the label for the history through a complex_options mapping information
                    //
                    for(j in field.options) {
                        if( field.complex_options.subname != null && field.options[j][field.complex_options.subname][field.complex_options.value] == history[i].action.value ) {
                            c3.innerHTML = field.options[j][field.complex_options.subname][field.complex_options.name];
                            break;
                        }
                        else if( field.options[j][field.complex_options.value] == history[i].action.value ) {
                            c3.innerHTML = field.options[j][field.complex_options.name];
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
            } else if( field != null && field.type == 'flagspiece' && field.flags != null && field.toggle != null && field.toggle == 'yes' ) {
                for(j in field.flags) {
                    if( (j == '0' || j == 0) && j == history[i].action.value ) {
                        c3.innerHTML = field.flags[j].name;
                    } else if( (history[i].action.value&Math.pow(2, j-1)) == Math.pow(2,j-1) ) {
                        c3.innerHTML = (c3.innerHTML != '' ? ', ' : '' ) + field.flags[j].name;
                    }
                }
            } else if( field != null && field.type == 'flagspiece' && field.flags != null && (field.toggle == null || field.toggle == 'no') ) {
                c3.innerHTML = '';
                for(j in field.flags) {
                    if( (j == '0' || j == 0) && (history[i].action.value == '0' || history[i].action.value == 0) ) {
                        c3.innerHTML += (c3.innerHTML != '' ? ', ' : '' ) + field.flags[j].name;
                    } else if( (history[i].action.value&Math.pow(2, j-1)) == Math.pow(2,j-1) ) {
                        c3.innerHTML += (c3.innerHTML != '' ? ', ' : '' ) + field.flags[j].name;
                    }
                }
            } else if( field != null && field.type == 'minsec' ) {
                var v = parseInt(history[i].action.value);
                var minutes = Math.floor(v/60);
                var seconds = v%60;
                if( minutes > 0 ) {
                    c3.innerHTML = minutes + ' min';
                }
                if( seconds > 0 ) {
                    c3.innerHTML += ' ' + seconds + ' sec';
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
//                c3.innerHTML = history[i].action.value.replace(/::/g, ', ');
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

            tr.appendChild(c3);
            tb.appendChild(tr);

            //
            // Check for notes
            //
            if( history[i].action.notes != null && history[i].action.notes != '' ) {
                tr = M.aE('tr', null, '');
                var c4 = M.aE('td', null, 'fieldnotes', history[i].action.notes);
                c4.colSpan='2';
                tr.appendChild(c4);
                tb.appendChild(tr);
            }
        }
        hC.appendChild(t);
    }

    t.appendChild(tb);
    hC.appendChild(t);
    hD.appendChild(hC);
    fD.parentNode.insertBefore(hD, fD.nextSibling);

    if( M.scroller != null ) {
        M.scroller.refresh();
    }
}

//
// This function will display the history information associated with 
// a simple grid element.
//
M.panel.setupGridHistory = function(s, i) {
    
    //
    // Get the form field, and find the parent row
    //
    var fD = M.gE(this.panelUID + '_' + s + '_' + i).parentNode;
    var hD = M.aE('tr', this.panelUID + '_' + s + '_' + i + '_history', 'fieldhistory');
    var hC = M.aE('td', null, 'history');

    //
    // Get the number of cells from the field row in the table.
    //
    hC.colSpan = fD.children.length;

    var history = this.fieldHistories[s + '_' + i].history;
    var users = this.fieldHistories[s + '_' + i].users;

    var t = M.addTable(null, 'fieldhistory noheader');
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
            if( M.userSettings == null || M.userSettings['ui-history-date-display'] == null || M.userSettings['ui-history-date-display'] == 'age' ) {
                age = ', <span class=\'age\'>' + history[i].action.age + ' ago</span>';
            } else if( M.userSettings['ui-history-date-display'] == 'datetime' ) {
                age = ', <span class=\'age\'>' + history[i].action.date + '</span>';
            } else if( M.userSettings['ui-history-date-display'] == 'datetimeage' ) {
                age = ', <span class=\'age\'>' + history[i].action.date + ' (' + history[i].action.age + ' ago)</span>';
            }
            c1.innerHTML = '<span class=\'username\'>' 
                + history[i].action.user_display_name + '</span>'
                + age;

            //
            // Create cell for the value from the history
            //
            var c3 = M.aE('td', null, 'fieldvalue');
        
            c3.innerHTML = history[i].action.value;

            if( history[i].action.label != null ) {
                c3.innerHTML = history[i].action.label + c3.innerHTML;
            }
            //
            // Append cells and row
            //
            tr.appendChild(c1);
            tr.appendChild(c3);
            tb.appendChild(tr);

            //
            // Check for notes
            //
            if( history[i].action.notes != null && history[i].action.notes != '' ) {
                tr = M.aE('tr', null, '');
                var c4 = M.aE('td', null, 'fieldnotes', history[i].action.notes);
                c4.colSpan='2';
                tr.appendChild(c4);
                tb.appendChild(tr);
            }
        }
    }

    t.appendChild(tb);
    hC.appendChild(t);

    hD.appendChild(hC);
    fD.parentNode.insertBefore(hD, fD.nextSibling);

    if( M.scroller != null ) {
        M.scroller.refresh();
    }
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
    // c.appendChild(M.aE('br'));
    if( this.threadFollowupAge != null ) {
        c.appendChild(M.aE('span', '', 'age', ' ' + this.threadFollowupAge(s, i, d) + ' ago'));
        if( this.threadFollowupDateTime != null ) {
            c.appendChild(M.aE('span', '', 'age', ' (' + this.threadFollowupDateTime(s, i, d) + ')'));
        }
    }
    r.appendChild(c);
    f.appendChild(r);

    r = M.aE('tr', null, 'followup');
    if( this.threadFollowupContent != null ) {
        c = M.aE('td', '', 'content', this.threadFollowupContent(s, i, d));
    } else {
        c = M.aE('td', '', 'content');
    }
    r.appendChild(c);
    f.appendChild(r);

    return f;
}
