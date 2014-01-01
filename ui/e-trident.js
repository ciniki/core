//
// Device: generic
// Engine: gecko
// Browsers: firefox
// Platforms: windows, mac, linux
//
M.xmlHttpCreate = function() {
	var req = null;
	try{
		req=new ActiveXObject("Msxml2.XMLHTTP");
	}catch(e){
		try{
			req = new ActiveXObject("Microsoft.XMLHTTP");
		}catch(sc){
			req=null;
		}
	};
	if(!req && typeof XMLHttpRequest != "undefined"){req=new XMLHttpRequest();};

	return req; 
};
