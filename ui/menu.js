//
// This class will display the form to allow admins and tenant owners to 
// change the details of their tenant
//
function ciniki_core_menu() {
    this.tenants = null;

    this.init = function() {}

    this.start = function(cb) {
        //
        // Get the list of tenants the user has access to
        //
        M.api.getJSONCb('ciniki.tenants.getUserTenants', {}, function(r) {
            if( r.stat != 'ok' ) {
                M.api.err(r);
                return false;
            }
            M.ciniki_core_menu.setupMenu(cb, r);
            });
    }

    this.setupMenu = function(cb, r) {
        if( (r.categories == null && r.tenants == null) 
            || (r.categories != null && r.categories.length < 1)
            || (r.tenants != null && r.tenants.length < 1) ) {
            M.alert('Error - no tenants found');
            return false;
        } else if( r.tenants != null && r.tenants.length == 1 ) {
            //
            // If only 1 tenant, then go direct to that menu
            //
            M.startApp(M.tenantMenu,null,null,'mc',{'id':r.tenants[0].tenant.id});
            M.multiTenant = 'no';
        } else {
            //
            // Create the app container if it doesn't exist, and clear it out
            // if it does exist.
            //
            M.multiTenant = 'yes';
            var appContainer = M.createContainer('mc', 'ciniki_core_menu', 'yes');
            if( appContainer == null ) {
                M.alert('App Error');
                return false;
            } 
        
            // setup home panel as list of tenants
            if( (M.userPerms&0x01) == 0x01 && r.categories != null && r.categories.length > 1 ) {
                this.tenants = new M.panel('Tenants', 
                    'ciniki_core_menu', 'tenants',
                    'mc', 'medium narrowaside', 'sectioned', 'ciniki.core.menu.tenants');
                this.tenants.data = {};
            } else {
                this.tenants = new M.panel('Tenants', 
                    'ciniki_core_menu', 'tenants',
                    'mc', 'narrow', 'sectioned', 'ciniki.core.menu.tenants');
                this.tenants.data = {};
            }
            this.tenants.curCategory = 0;
            this.tenants.addButton('account', 'Account', 'M.startApp(\'ciniki.users.main\',null,\'M.ciniki_core_menu.tenants.show();\');');
            if( M.userID > 0 && (M.userPerms&0x01) == 0x01 ) {
                this.tenants.addButton('admin', 'Admin', 'M.startApp(\'ciniki.sysadmin.main\',null,\'M.ciniki_core_menu.tenants.show();\');');
            }

            if( r.categories != null ) {
                if( r.categories.length > 1 ) {
                    this.tenants.data = r;
                    this.tenants.sections['categories'] = {'label':'Categories', 
                        'visible':function() { return M.size == 'compact' ? 'no' : 'yes'; }, 
                        'aside':'yes', 'type':'simplegrid', 'num_cols':1};
                    this.tenants.sections['_'] = {'label':'',
                        'autofocus':'yes', 'type':'livesearchgrid', 'livesearchcols':1,
                        'hint':'Search', 
                        'noData':'No items found',
                        'headerValues':null,
                        };
                    this.tenants.sections['list'] = {'label':'', 'type':'simplegrid', 'num_cols':1};
                } else {
                    for(i in r.categories) {
                        this.tenants.sections['_'+i] = {'label':r.categories[i].category.name, 
                            'type':'simplelist'};
                        this.tenants.data['_'+i] = r.categories[i].category.tenants;
                    }
                }
                this.tenants.sectionData = function(s) { 
                    if( s == 'list' ) {
                        return this.data.categories[this.curCategory].category.tenants;
                    }
                    return this.data[s]; 
                }
            } else {
                // Display the master tenant at the top of the list
                if( r.tenants[0].tenant.ismaster == 'yes' ) {
                    this.tenants.sections = {    
                        '_master':{'label':'', 'as':'no', 'list':{
                            'master':{'tenant':r.tenants[0].tenant}}
                            },
                        '_':{'label':'', 'as':'yes', 'list':{}},
                        };
                    r.tenants.shift();
                } else {
                    this.tenants.sections = {
                        '_':{'label':'', 'as':'yes', 'list':{}}};
                }
                this.tenants.sections._.list = r.tenants;
            }

            this.tenants.listValue = function(s, i, d) { return d.tenant.name; }
            this.tenants.listFn = function(s, i, d) { 
                return 'M.startApp(M.tenantMenu,null,\'M.ciniki_core_menu.tenants.show();\',\'mc\',{\'id\':' + d.tenant.id + '});';
            }
            this.tenants.cellValue = function(s, i, j, d) {
                switch(s) {
                    case 'categories': return (d.category.name!=''?d.category.name:'Default') + ' <span class="count">' + d.category.tenants.length + '</span>';
                    case 'list': return d.tenant.name;
                }
            };
            this.tenants.switchCategory = function(i) {
                this.curCategory = i;
                this.refreshSection('list');
            };
            this.tenants.rowFn = function(s, i, d) {
                switch (s) {
                    case 'categories': return 'M.ciniki_core_menu.tenants.switchCategory(\'' + i + '\');';
                    case 'list': return 'M.startApp(M.tenantMenu,null,\'M.ciniki_core_menu.tenants.show();\',\'mc\',{\'id\':' + d.tenant.id + '});';
                }
            };
            this.tenants.addLeftButton('logout', 'Logout', 'M.logout();');
            if( r.bigboard != null && r.bigboard == 'yes' && M.userID > 0 && (M.userPerms&0x01) == 0x01 ) {
                this.tenants.addLeftButton('bigboard', 'bigboard', 'M.startApp(\'ciniki.sysadmin.bigboard\',null,\'M.ciniki_core_menu.tenants.show();\');');
            }

            // Add searching
            this.tenants.liveSearchCb = function(s, i, v) {
                if( v != '' ) {
                    M.api.getJSONBgCb('ciniki.tenants.searchTenants', {'tnid':M.curTenantID, 'start_needle':v, 'limit':'15'},
                        function(rsp) {
                            M.ciniki_core_menu.tenants.liveSearchShow(s, null, M.gE(M.ciniki_core_menu.tenants.panelUID + '_' + s), rsp.tenants);
                        });
                }
                return true;
            };
            this.tenants.liveSearchResultValue = function(s, f, i, j, d) {
                return d.tenant.name;
            };
            this.tenants.liveSearchResultRowFn = function(s, f, i, j, d) {
                return 'M.startApp(M.tenantMenu,null,\'M.ciniki_core_menu.tenants.show();\',\'mc\',{\'id\':\'' + d.tenant.id + '\'});';
            };
            this.tenants.liveSearchResultRowStyle = function(s, f, i, d) { return ''; };

            M.menuHome = this.tenants;
            
            if( typeof(localStorage) !== 'undefined' && localStorage.getItem('lastTenantID') > 0 ) {
                M.startApp(M.tenantMenu,null,'M.ciniki_core_menu.tenants.show();','mc',{'id':localStorage.getItem('lastTenantID')});
            } else {
                M.menuHome.show();
            }

            //
            // Check if there is a set of login actions to perform
            //
            if( r.loginActions != null ) {
                eval(r.loginActions);
            }
        }
    }
}
