//
// This class will display the form to allow admins and business owners to 
// change the details of their business
//
function ciniki_core_menu() {
	this.businesses = null;

	this.init = function() {

	}


	this.start = function(cb) {
		//
		// Get the list of businesses the user has access to
		//
		var r = M.api.getJSONCb('ciniki.businesses.getUserBusinesses', {}, function(r) {
			if( r.stat != 'ok' ) {
				M.api.err(r);
				return false;
			}
			M.ciniki_core_menu.setupMenu(cb, r);
			});
	}

	this.setupMenu = function(cb, r) {
			if( (r.categories == null && r.businesses == null) 
				|| (r.categories != null && r.categories.length < 1)
				|| (r.businesses != null && r.businesses.length < 1) ) {
				alert('Error - no businesses found');
				return false;
			} else if( r.businesses != null && r.businesses.length == 1 ) {
				//
				// If only 1 business, then go direct to that menu
				//
				M.startApp(M.businessMenu,null,null,'mc',{'id':r.businesses[0].business.id});
			} else {
				//
				// Create the app container if it doesn't exist, and clear it out
				// if it does exist.
				//
				var appContainer = M.createContainer('mc', 'ciniki_core_menu', 'yes');
				if( appContainer == null ) {
					alert('App Error');
					return false;
				} 
			
				// setup home panel as list of businesses
				this.businesses = new M.panel('Businesses', 
					'ciniki_core_menu', 'businesses',
					'mc', 'narrow', 'sectioned', 'ciniki.core.menu.businesses');
				this.businesses.data = {};
				this.businesses.addButton('account', 'Account', 'M.startApp(\'ciniki.users.main\',null,\'M.menuHome.show();\');');
				if( M.userID > 0 && (M.userPerms&0x01) == 0x01 ) {
					this.businesses.addButton('admin', 'Admin', 'M.startApp(\'ciniki.sysadmin.main\',null,\'M.menuHome.show();\');');
				}

				if( r.categories != null ) {
					for(i in r.categories) {
						this.businesses.sections[i] = {'label':r.categories[i].category.name, 
							'type':'simplelist'};
						this.businesses.data[i] = r.categories[i].category.businesses;
					}
					this.businesses.sectionData = function(s) {
						return this.data[s];
					}
				} else {
					// Display the master business at the top of the list
					if( r.businesses[0].business.ismaster == 'yes' ) {
						this.businesses.sections = {	
							'_master':{'label':'', 'as':'no', 'list':{
								'master':{'business':r.businesses[0].business}}
								},
							'_':{'label':'', 'as':'yes', 'list':{}},
							};
						r.businesses.shift();
					} else {
						this.businesses.sections = {'_':{'label':'', 'as':'yes', 'list':{}}};
					}
					this.businesses.sections._.list = r.businesses;
				}

				this.businesses.listValue = function(s, i, d) { return d.business.name; }
				this.businesses.listFn = function(s, i, d) { 
					return 'M.startApp(M.businessMenu,null,\'M.ciniki_core_menu.businesses.show();\',\'mc\',{\'id\':' + d.business.id + '});';
				}
				this.businesses.addLeftButton('logout', 'Logout', 'M.logout();');
				if( M.userID > 0 && (M.userPerms&0x01) == 0x01 ) {
					this.businesses.addLeftButton('bigboard', 'bigboard', 'M.startApp(\'ciniki.sysadmin.bigboard\',null,\'M.menuHome.show();\');');
				}

				M.menuHome = this.businesses;

				M.menuHome.show();
			}
	}
}
