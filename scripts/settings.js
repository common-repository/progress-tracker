
var ASadmin = {
	
	openTab: 0,
	
	init: function () {
		this.initTabs();
	},
	
	initTabs: function () {
		jQuery( '.as-tabbutton').each( function ( j ) {
			ASadmin.add_tab_listener( j );
		});
		jQuery('#as_tabbutton_' + this.openTab ).addClass('active-tab');
		jQuery('#as_tab_' + this.openTab ).show();
	},
	
	add_tab_listener: function ( j ) {
		var that = this;
		jQuery('#as_tabbutton_' + j).click( function (e) {
			that.changeTab( j );
		});
	},
	
	changeTab: function ( j ) {
		if ( j !== this.openTab ) {
			jQuery('#as_tab_' + this.openTab).hide();
			jQuery('#as_tabbutton_' + this.openTab).removeClass('active-tab');
			jQuery('#as_tab_' + j).show();
			jQuery('#as_tabbutton_' + j).addClass('active-tab');
			jQuery('#currentTab').val( j );
			this.openTab = j;
		}
	}

};


jQuery( document ).ready( function () {
	
	ASadmin.init();

});

