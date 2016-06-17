//
// Device: generic
// Engine: gecko
// Browsers: firefox
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
