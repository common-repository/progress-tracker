var AS_ptracker = {
	
	unMarkedText:	'Mark as read: ',
	markedText:		'Completed ',
	pluginURL:		'',
	
	init: function () {
		this.addClickHandlers();
	},
	
	addClickHandlers: function () {
		
		jQuery('#markAsReadButton').on( 'click', function ( e ) {
			
			var state = jQuery('#markAsReadButtonState').val();
			var newVal = ( 'red' === state ) ? 'green' : 'red';
			jQuery('#aspt_spinner').addClass('aspt-spinner-on'); 
			
			var success = function ( response, status ) {
				jQuery('#markAsReadButtonState').val( newVal );
				jQuery('img#markAsReadImage').attr( 'src', AS_ptracker.pluginURL + '/css/images/slidebutton-' + newVal + '.png' );
				if ( 'green' === newVal ) {
					jQuery('#isMarkedText').addClass('isMarked').text( AS_ptracker.markedText );
				} else {
					jQuery('#isMarkedText').removeClass('isMarked').text( AS_ptracker.unMarkedText );
				}
				jQuery('#aspt_spinner').removeClass('aspt-spinner-on');
			};
			
			var error = function ( jqXHR, status, error ) {
				jQuery('#aspt_spinner').removeClass('aspt-spinner-on');
			};
			
			AS_ptracker.request( { read_status: newVal }, 'ASPT_updateUserPage', success, error );
		});
		
	},
	
	request: function ( info, action, onSuccess, onError ) {
		
		var data = { 
			'action':	action, 
			'info':		info,
			'user_id':	ASPTajax.user_id,
			'post_id':	ASPTajax.post_id
		};		
		
		jQuery.ajax({
			type: 		"POST",
			data: 		data,
			url: 		ASPTajax.WPajaxurl,
			success: function( response, status ) {
				onSuccess( response, status );
			},
			error: function ( jqXHR, status, error ) {
				onError( jqXHR, status, error );
			}
		});
		
		
	}
	
};


// Add listeners for reset

jQuery( document ).ready(function() {

	jQuery( "#progressReset" ).click(function() {
		jQuery( "#resetConfirmPopup" ).toggle( "fast" );
	}); 
	
	jQuery( "#cancelReset" ).click(function() {
		jQuery( "#resetConfirmPopup" ).toggle( "fast" );
	}); 	



});


