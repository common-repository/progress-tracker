

var ASPTadmin = {
	
	init: function () {		
		this.moveDiv();
		this.addChangeHandler();
		this.getData( jQuery('#parent_id').val() );
	},
	
	moveDiv: function () {
		jQuery("#enableProgressTracking_div").appendTo("#pageparentdiv").css({ 'display' : 'block' });
	},
	
	addChangeHandler: function () {
		jQuery('#parent_id').on( 'change', function ( e ) {
			ASPTadmin.getData( jQuery( this ).val() );
		});
	},
	
	getData: function ( parentID ) {
		this.request( { parentID: parentID }, 'ASPT_parentHasTracking', this.onSuccess, this.onError );
	},
	
	onSuccess: function ( response, status ) {
		ASPTadmin.setUI( response );
	},
	
	onError: function ( jqXHR, status, error ) {
		jQuery('#enableProgressTracking_div').removeClass('aspt-spinner-on');
		//console.log( error );
	},
	
	setUI: function ( isTracked ) {
		jQuery('#enableProgressTracking_div').removeClass('aspt-spinner-on');
		( 'true' === isTracked ) ? this.showMessage() : this.showCheckbox();
	},
	
	request: function ( info, action, onSuccess, onError ) {
		var data = { 
			'info':		info,
			'action': 	action 
		};
		
		jQuery('#enableProgressTracking_div').addClass('aspt-spinner-on');
		
		jQuery.ajax({
			type: 	"POST",
			data: 	data,
			url: 	ASPTajax.WPajaxurl,
			success: function( response, status ) {
				onSuccess( response, status );
			},
			error: function ( jqXHR, status, error ) {
				onError( jqXHR, status, error );
			}
		});
	},
	
	showCheckbox: function () {
		jQuery('#ASPT_tickWrap').css({ 'display' : 'block' });
		jQuery('#ASPT_messageWrap').css({ 'display' : 'none' });
	},
	
	showMessage: function () {
		jQuery('#ASPT_tickWrap').css({ 'display' : 'none' });
		jQuery('#ASPT_messageWrap').css({ 'display' : 'block' });
	}
	
};

