//
// Device: generic
// Engine: webkit
// Browsers: chrome, safari
// Platforms: windows, mac, linux
//
M.xmlHttpCreate = function() {
    var req = null;
    try{
		req=new XMLHttpRequest();
	}catch(e){
		req=null;
	};
    return req; 
};
