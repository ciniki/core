//
// This class is the wrapper for the ciniki API over http/JSON/XML
//

window.M.api = {
    'url':'',
    'key':'',
    'version':'',        // Keep track of the version of the api current deployed
    'token':''}

//
// Arguments:
// r - The response code from Ciniki to check for the error
//
M.api.checkResult = function(r, m, p, c) {
    if( r.stat == 'fail' && (r.err.code == 'ciniki.core.146' || r.err.code == 'ciniki.core.147') && m != 'ciniki.users.auth' ) {
        return M.api.expired(r);
    }
    if( r.stat == 'fail' ) {
        r.method = m;
    }
    M.expired = 'no';

    return r;
}

//
// Arguments:
// m - public API method
// p - Arguments to be passed to the method via REST URL
//
M.api.getJSON = function(m, p) {
    if( M.expired == 'yes' ) {
        return {'stat':'fail'};
    }
    p.format = 'json';
    return M.api.checkResult(M.api.get(m, p), m, p, null);
}

//
// Arguments:
// m - public API method
// p - Arguments to be passed to the method via REST URL
//
M.api.getJSONBg = function(m, p) {
    if( M.expired == 'yes' ) {
        return {'stat':'fail'};
    }
    p.format = 'json';
    return M.api.getBg(m, p);
}

//
// Arguments:
// m - public API method
// p - Arguments to be passed to the method via REST URL
// c - callback after the API call has completed
//
M.api.getJSONCb = function(m, p, c) {
    if( M.expired == 'yes' ) {
        return {'stat':'fail'};
    }
    p.format = 'json';
    return M.api.checkResult(M.api.getCb(m, p, c), m, p, c);
}

//
// Arguments:
// m - public API method
// p - Arguments to be passed to the method via REST URL
// c - callback after the API call has completed
//
// Set this call in the background, which means don't display loading spinner
//
M.api.getJSONBgCb = function(m, p, c) {
    if( M.expired == 'yes' ) {
        return {'stat':'fail'};
    }
    p.format = 'json';
    return M.api.checkResult(M.api.getBgCb(m, p, c), m, p, c);
}

//
// Arguments:
// m - public API method
// p - Arguments to be passed to the method via REST URL
// c - Arguments to be passed in POST
//
M.api.postJSON = function(m, p, c) {
    // Check if session is already expired, and only allow auth call
    if( M.expired == 'yes' && m != 'ciniki.users.auth' ) {
        return {'stat':'fail'};
    }
    p.format = 'json';
    return M.api.checkResult(M.api.post(m, p, c), m, p, c);
}

//
// Arguments:
// m - public API method
// p - Arguments to be passed to the method via REST URL
// c - Arguments to be passed in POST
//
M.api.postJSONCb = function(m, p, c, cb) {
    // Check if session is already expired, and only allow auth call
    if( M.expired == 'yes' && m != 'ciniki.users.auth' ) {
        return {'stat':'fail'};
    }
    p.format = 'json';
    return M.api.checkResult(M.api.postCb(m, p, c, cb), m, p, c);
}

//
// Arguments:
// m - public API method
// p - Arguments to be passed to the method via REST URL
// f - file to be uploaded
//
M.api.postJSONFile = function(m, p, f, c) {
    // Check if session is already expired, and only allow auth call
    if( M.expired == 'yes' && m != 'ciniki.users.auth' ) {
        return {'stat':'fail'};
    }
    p.format = 'json';
    return M.api.postFile(m, p, f, c);
}

//
// Arguments:
// m - public API method
// p - Arguments to be passed to the method via REST URL
// f - form data to be sent FormData format
// c - the callback to be issued when finished
//
M.api.postJSONFormData = function(m, p, f, c) {
    // Check if session is already expired, and only allow auth call
    if( M.expired == 'yes' && m != 'ciniki.users.auth' ) {
        return {'stat':'fail'};
    }
    p.format = 'json';
    return M.api.postFormData(m, p, f, c);
}

//
// Arguments:
// m - public API method
// p - Arguments to be passed to the method via REST URL
// b - binary data
//
M.api.postJSONAsBinary = function(m, p, b) {
    // Check if session is already expired, and only allow auth call
    if( M.expired == 'yes' && m != 'ciniki.users.auth' ) {
        return {'stat':'fail'};
    }
    p.format = 'json';
    return M.api.checkResult(M.api.postAsBinary(m, p, b), m, p, c);
}

//
// This function should only be used when uploading files (images, video, documents)
// to the API, which are not able to be submitted through Javascript, and must
// be submitted by a form submit through an iFrame.
//
// There is no way to wait for the iFrame to load without the use of callbacks, so
// when the iframe loads, the callback function below stops the loading spinner,
// and then eval's the callback_str.  This allows the calling function to specify
// it's own callback function to handle the return.
//
// Arguments:
// i - iFrame ID to be used to post to the API.
// m - public Ciniki method
// p - Arguments to be passed to the method via REST URL
// f - form_id
// cb - the callback_string to eval when the iframe has loaded
//
M.api.iFramePostJSON = function(iframe_id, m, p, form_id, callback_str) {

    // Set the from to post
    var form = document.getElementById(form_id);
    form.setAttribute('method', 'POST');
    p.format = 'json';
    form.setAttribute('action', M.api.getUploadURL(m, p));
    form.setAttribute('enctype', 'multipart/form-data');

    // Set the target to iframe
    form.setAttribute('target', iframe_id);

    // Set onload of iFrame to Callback
    var iframe = document.getElementById(iframe_id);
    iframe.onload = function() { 
        M.stopLoad(); 
        eval(callback_str); 
        };

    // Submit Form
    M.startLoad();
    form.submit();

    // Parse response in iFrame
    return eval('(' + iframe.contentWindow.document.innerHTML + ')');
}

//
// This function should be called after iFramePostJSON, 
// as part of the callback, and will eval the contents of the iFrame for JSON and 
// return the response.
// 
M.api.iFramePostJSONRsp = function(iframe_id) {
    var iframe = document.getElementById(iframe_id);
    return eval('(' + iframe.contentWindow.document.body.innerHTML + ')');
}

M.api.openPDF = function(m, p) {
    return M.api.openFile(m, p);
}

M.api.openFile = function(m, p) {
    if( M.engine == 'trident' || M.engine == 'gecko' || M.engine == 'webkit' ) {
        window.open(M.api.getUploadURL(m, p));
    } else {
        var a = M.aE('a');
        a.setAttribute("href", M.api.getUploadURL(m, p));
        a.setAttribute("target", "_blank");

        var dispatch = document.createEvent("Event");
        dispatch.initEvent("click", true, true);
        a.dispatchEvent(dispatch);
    }
    return false;
}

M.api.getUploadURL = function(m, p) {
    var u = M.api.url + '?method=' + m + '&api_key=' + M.api.key + '&auth_token=' + M.api.token;
    for(k in p) {
        u += '&' + k + '=' + p[k];
    }
    return u;
}

M.api.getBinaryURL = function(m, p) {
    var u = M.api.url + '?method=' + m + '&api_key=' + M.api.key + '&auth_token=' + M.api.token;
    for(k in p) {
        u += '&' + k + '=' + p[k];
    }
    return u;
}

//
// This function will make the api call and fetch the results
//
// Arguments:
// m  - method
// p  - params
//
M.api.get = function(m, p) {
    M.startLoad();
    var u = M.api.url + '?method=' + m + '&api_key=' + M.api.key + '&auth_token=' + M.api.token;
    for(k in p) {
        u += '&' + k + '=' + p[k];
    }
    var x = M.xmlHttpCreate();
    try {
        x.open("GET", u, false);
        x.send(null);
    } catch(e) {
        M.stopLoad();
        return {'stat':'fail', 'err':{'code':'00', 'msg':'Network Error, please try again'}};
    }
    if( x.status == 200 ) {
        if(p.format == 'json') {
            M.stopLoad();
            var r = eval('(' + x.responseText + ')');
            return r;
        }
        M.stopLoad();
        return x.responseXML;
    } else if( x.readyState > 2 && (x.status >= 300) ) {
        M.stopLoad();
        return {'stat':'fail','err':{'code':'HTTP-' + x.status, 'msg':'Unable to transfer.'}};
    }
    M.stopLoad();
    return {'stat':'fail','err':{'code':'00','msg':'Server Error'}};
}

//
// This function will make the api call in the background, without blocking the interface.  This was
// developed for the live search
//
// Arguments:
// m  - method
// p  - params
//
M.api.getBg = function(m, p) {
    var u = M.api.url + '?method=' + m + '&api_key=' + M.api.key + '&auth_token=' + M.api.token;
    for(k in p) {
        u += '&' + k + '=' + p[k];
    }
    var x = M.xmlHttpCreate();
    x.open("GET", u, false);
    x.send(null);
    if( x.status == 200 ) {
        if(p.format == 'json') {
            var r = eval('(' + x.responseText + ')');
            return r;
        }
        return x.responseXML;
    } else if( x.readyState > 2 && (x.status >= 300) ) {
        return {'stat':'fail','err':{'code':'HTTP-' + x.status, 'msg':'Unable to transfer.'}};
    }
    return {'stat':'fail','err':{'code':'00','msg':'Server Error'}};
}

//
// This function will make the api call and execute a callback when finished, 
// but will not display a loading spinner
//
// Arguments:
// m  - method
// p  - params
// c  - callback
//
M.api.getBgCb = function(m, p, c) {
    var u = M.api.url + '?method=' + m + '&api_key=' + M.api.key + '&auth_token=' + M.api.token;
    for(k in p) {
        u += '&' + k + '=' + p[k];
    }
    var x = M.xmlHttpCreate();
    x.open("GET", u, true);
    x.onreadystatechange = function() {
        if( x.readyState == 4 && x.status == 200 ) {
            if(p.format == 'json') {
                var r = eval('(' + x.responseText + ')');
                if( r.stat != 'ok' && (r.err.code == 'ciniki.core.146' || r.err.code == 'ciniki.core.147')) {
                    M.reauth_apiresume = {'f':'getBgCb', 'm':m, 'p':p, 'cb':c};
                    return M.api.expired(r);
                } else {
                    c(r);
                }
            } else {
                c(x.responseXML);
            }
        } 
        // alert(x.readyState + '--' + x.status);
        if( x.readyState > 2 && x.status >= 300 ) {
            c({'stat':'fail','err':{'code':'HTTP-' + x.status, 'msg':'Unable to transfer.'}});
        } 
    };
    x.send(null);

    return {'stat':'ok'};
}

//
// This function will make the api call and execute a callback when finished
//
// Arguments:
// m  - method
// p  - params
// c  - callback
//
M.api.getCb = function(m, p, c) {
    M.startLoad();
    var u = M.api.url + '?method=' + m + '&api_key=' + M.api.key + '&auth_token=' + M.api.token;
    for(k in p) {
        u += '&' + k + '=' + encodeURIComponent(p[k]);
    }
    M.api.lastCall = {'f':'getCb', 'm':m, 'p':p, 'c':'', 'cb':c};
    var x = M.xmlHttpCreate();
    x.open("GET", u, true);
    x.onreadystatechange = function() {
        if( x.readyState == 4 && x.status == 200 ) {
            M.stopLoad();
            if(p.format == 'json') {
//                var r = eval('(' + x.responseText + ')');
                try {
                    var r = JSON.parse(x.responseText);
                } catch(e) {
                    c({'stat':'fail', 'err':{'code':'JSON-ERR', 'msg':'API Error', 'pmsg':'Unable to parse (' + x.responseText + ')'}});
                }
                if( r.stat != 'ok' && (r.err.code == 'ciniki.core.146' || r.err.code == 'ciniki.core.147')) {
                    M.reauth_apiresume = {'f':'getCb', 'm':m,'p':p,'cb':c};
                    return M.api.expired(r);
                } else {
                    c(r);
                }
            } else {
                c(x.responseXML);
            }
        } 
        else if( x.readyState > 2 && x.status >= 300 ) {
            M.stopLoad();
            c({'stat':'fail','err':{'code':'HTTP-' + x.status, 'msg':'Unable to transfer.'}});
        } 
        else if( x.readyState == 4 && x.status == 0 ) {
//        else if( x.status == 0 ) {
            M.stopLoad();
            c({'stat':'fail','err':{'code':'network', 'msg':"We had a problem communicating with the server. Please try again or if the problem persists check your network connection."}});
        }
    };
    x.send(null);
    return {'stat':'ok'};
}

//
// This function will make the api call and fetch the results
//
M.api.post = function(m, p, c) {
    M.startLoad();
    var u = M.api.url + '?method=' + m + '&api_key=' + M.api.key + '&auth_token=' + M.api.token;
    for(k in p) {
        u += '&' + k + '=' + p[k];
    }
    M.api.lastCall = {'f':'post', 'm':m, 'p':p, 'c':c, 'cb':''};
    var x = M.xmlHttpCreate();
    x.open("POST", u, false);
//    x.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    x.send(c);
    if( x.status == 200 ) {
        if(p.format == 'json') {
            M.stopLoad();
            return eval('(' + x.responseText + ')');
        }
        M.stopLoad();
        return x.responseXML;
    } else if( x.readyState > 2 && (x.status >= 300) ) {
        M.stopLoad();
        return {'stat':'fail','err':{'code':'HTTP-' + x.status, 'msg':'Unable to transfer.'}};
    } 

    M.stopLoad();
    return {'stat':'fail','err':{'code':'00','msg':'Server Error'}};
}

//
// This function will make the api call and fetch the results
//
M.api.postCb = function(m, p, c, cb) {
    M.startLoad();
    var u = M.api.url + '?method=' + m + '&api_key=' + M.api.key + '&auth_token=' + M.api.token;
    for(k in p) {
        u += '&' + k + '=' + p[k];
    }
    M.api.lastCall = {'f':'postCb', 'm':m, 'p':p, 'c':c, 'cb':cb};
    var x = M.xmlHttpCreate();
    x.open("POST", u, true);
    x.onreadystatechange = function() {
//        if( x == null ) { return true; }
        if( this.readyState == 4 && this.status == 200 ) {
            M.stopLoad();
            if(p.format == 'json') {
                try {
                    var r = JSON.parse(this.responseText);
                } catch(e) {
                    cb({'stat':'fail', 'err':{'code':'JSON-ERR', 'msg':'API Error', 'pmsg':'Unable to parse (' + this.responseText + ')'}});
                }
                if( r != null && r.stat != 'ok' && (r.err.code == 'ciniki.core.146' || r.err.code == 'ciniki.core.147')) {
                    M.reauth_apiresume = {'f':'postCb', 'm':m, 'p':p, 'c':c, 'cb':cb};
                    return M.api.expired(r);
                } 
                cb(r);
            } else {
                cb(this.responseXML);
            }
        } 
        else if( this.readyState > 2 && this.status >= 300 ) {
            M.stopLoad();
            cb({'stat':'fail','err':{'code':'HTTP-' + this.status, 'msg':'Network error - unable to transfer.'}});
        } 
//        else if( M.browser != 'ie' && this.status == 0 ) {
        else if( x.readyState == 4 && x.status == 0 ) {
            M.stopLoad();
            cb({'stat':'fail','err':{'code':'HTTP-' + this.status, 'msg':'API Error'}});
        }
    };
    x.send(c);
    return {'stat':'ok'};
}

M.api.postAuthCb = function(m, p, c, cb) {
    M.startLoad();
    var u = M.api.url + '?method=' + m + '&api_key=' + M.api.key + '&auth_token=' + M.api.token;
    for(k in p) {
        u += '&' + k + '=' + p[k];
    }
    M.api.lastCall = {'f':'postCb', 'm':m, 'p':p, 'c':c, 'cb':cb};
    var x = M.xmlHttpCreate();
    x.open("POST", u, true);
    x.onreadystatechange = function() {
//        if( x == null ) { return true; }
        if( this.readyState == 4 && this.status == 200 ) {
            M.stopLoad();
            if(p.format == 'json') {
                try {
                    var r = JSON.parse(this.responseText);
                } catch(e) {
                    cb({'stat':'fail', 'err':{'code':'JSON-ERR', 'msg':'API Error', 'pmsg':'Unable to parse (' + this.responseText + ')'}});
                }
                cb(r);
            } else {
                cb(this.responseXML);
            }
        } 
        else if( this.readyState > 2 && this.status >= 300 ) {
            M.stopLoad();
            cb({'stat':'fail','err':{'code':'HTTP-' + this.status, 'msg':'Network error - unable to transfer.'}});
        } 
//        else if( M.browser != 'ie' && this.status == 0 ) {
        else if( x.readyState == 4 && x.status == 0 ) {
            M.stopLoad();
            cb({'stat':'fail','err':{'code':'HTTP-' + this.status, 'msg':'API Error'}});
        }
    };
    x.send(c);
    return {'stat':'ok'};
}

//
// This function will make the api call and fetch the results
//
M.api.postFile = function(m, p, f, c) {
    M.startLoad();
    var u = M.api.url + '?method=' + m + '&api_key=' + M.api.key + '&auth_token=' + M.api.token;
    for(k in p) {
        u += '&' + k + '=' + p[k];
    }
    M.api.lastCall = {'f':'postFile', 'm':m, 'p':p, 'c':'', 'cb':c};
    var x = M.xmlHttpCreate();
    x.open("POST", u, true);
    var fd = new FormData();
    fd.append("uploadfile", f);
    x.onreadystatechange = function() {
        if( x.readyState == 4 && x.status == 200 ) {
            M.stopLoad();
            if(p.format == 'json') {
                c(eval('(' + x.responseText + ')'));
            } else {
                c(x.responseXML);
            }
        } 
        else if( x.readyState > 2 && (x.status >= 300) ) {
            M.stopLoad();
            c({'stat':'fail','err':{'code':'HTTP-' + x.status, 'msg':'Unable to transfer.'}});
        } 
    };
    x.send(fd);

    return {'stat':'ok'};
}

//
// This function will make the api call and fetch the results
//
M.api.postAsBinary = function(m, p, f) {
    M.startLoad();
    var u = M.api.url + '?method=' + m + '&api_key=' + M.api.key + '&auth_token=' + M.api.token;
    for(k in p) {
        u += '&' + k + '=' + p[k];
    }
    M.api.lastCall = {'f':'postAsBinary', 'm':m, 'p':p, 'c':'', 'cb':''};
    var x = M.xmlHttpCreate();
    x.open("POST", u, false);
//    x.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    x.sendAsBinary(b);
    if( x.status == 200 ) {
        if(p.format == 'json') {
            M.stopLoad();
            return eval('(' + x.responseText + ')');
        }
        M.stopLoad();
        return x.responseXML;
    } 

    M.stopLoad();
    return {'stat':'fail','err':{'code':'00','msg':'Server Error'}};
}

//
// This function will make the api call and fetch the results
//
M.api.postFormData = function(m, p, f, c) {
    M.startLoad();
    var u = M.api.url + '?method=' + m + '&api_key=' + M.api.key + '&auth_token=' + M.api.token;
    for(k in p) {
        u += '&' + k + '=' + p[k];
    }
    M.api.lastCall = {'f':'postFormData', 'm':m, 'p':p, 'c':'', 'cb':c};
    var x = M.xmlHttpCreate();
    x.open("POST", u, true);
//    x.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    x.onreadystatechange = function() {
        if( x.readyState == 4 && x.status == 200 ) {
            M.stopLoad();
            if(p.format == 'json') {
                var r = eval('(' + x.responseText + ')');
                if( r.stat != 'ok' && (r.err.code == 'ciniki.core.146' || r.err.code == 'ciniki.core.147')) {
                    M.reauth_apiresume = {'f':'postFormData', 'm':m, 'p':p, 'f':f, 'cb':c};
                    return M.api.expired(r);
                } 
                c(r);
            } else {
                c(x.responseXML);
            }
        } 
        else if( x.readyState > 2 && (x.status >= 300) ) {
            M.stopLoad();
            c({'stat':'fail','err':{'code':'HTTP-' + x.status, 'msg':'Unable to transfer.'}});
        } else if( x.readyState == 4 && x.status == 0 ) {
            M.stopLoad();
            c({'stat':'fail','err':{'code':'HTTP-' + x.status, 'msg':'Unable to transfer.'}});
        }
    };
    x.send(f);
}

//
// This function will alert the user with the error returned from the api call.
//
M.api.err = function(r) {
    if( r.err != null ) {
        //
        // Check if session has expired
        //
        if( r.err.code == 'ciniki.core.146' || r.err.code == 'ciniki.core.147' ) {
            return M.api.expired(r);
        }

        if( r.err.code == 'network' ) {
            M.alert(r.err.msg);
        } else {
            //
            // Store the current return code so it can be used to submit a bug
            //
            M.api.curRC = r;
            M.show('m_error');
            var l = document.getElementById('me_error_list');
            M.clr(l);
            M.api.listErr(l, r.err);
            var e = document.getElementById('m_error');

            //
            // Make sure the m_error div will be large enough to cover the container
            //
            var c = document.getElementById('m_container');
            e.style.height = c.offsetHeight + 'px';
            if( window.innerHeight > c.offsetHeight ) {
                e.style.height = window.innerHeight + 'px';
            }
            window.scrollTo(0, 0);
        }
    }
}

M.api.listErr = function(l, e) {
    var tr = M.aE('tr');
    c = M.aE('td', null, 'label', e.msg + ' <span class="subdue">(' + e.code + ')</span>');
    tr.appendChild(c);
    l.appendChild(tr);

    if(e.err != null ) {
        M.api.listErr(l, e.err);
    }
}

//
// This function will alert with a simple dialog box, to be used
// for login screen
//
M.api.err_alert = function(r) {
    if( r.err != null ) {
        alert("Error: #" + r.err.code + ' - ' + r.err.msg);
    }
}

// This function will re-run a api call, when a reauth has occurred
M.api.resume = function(apicall) {
    switch (apicall.f) {
        case 'getCb': M.api.getCb(apicall.m, apicall.p, apicall.cb); return false;
        case 'getBgCb': M.api.getBgCb(apicall.m, apicall.p, apicall.cb); return false;
        case 'postCb': M.api.postCb(apicall.m, apicall.p, apicall.c, apicall.cb); return false;
        case 'postFormData': M.api.postFormData(apicall.m, apicall.p, apicall.f, apicall.cb); return false;
    }
}

M.api.expired = function(r) {
//    var s = M.cookieGet('_UTS');
//    var t = M.cookieGet('_UTK');
    var s = localStorage.getItem('_UTS');
    var t = localStorage.getItem('_UTK');
    if( s != null && s != '' && t != null && t != '' ) {
        M.reauthToken(s, t);
    } else {
        M.api.token = '';
        M.expired = 'yes';
        // Users will need to verify their password based on API timeout, but only if
        // the code version they are using is no older than 12 hours.  This means users must
        // relogin within 12 hours of a code update.
        if( M.api.version != r.version && ((Math.round(+new Date()/1000))-M.startTime) > 43200 ) {
            alert('Session expired, please login again');
            M.oldUserId = M.userID;
            M.userID = 0;
            M.userPerms = 0;
            M.logout();
            M.businesses = null;
            M.curBusinessID = 0;
            M.reload();
        } else {
            M.show('m_relogin');
            M.hide('m_container');
        }
    }

    return {'stat':'fail'};
}
