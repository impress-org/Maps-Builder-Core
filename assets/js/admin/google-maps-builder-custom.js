/**
 * Custom js added for preview map
 */
var gmb_data;
jQuery( document ).ready( function( $ ) {
	jQuery( '.gmb-load-map' ).on( 'click', function( e ) {
		e.preventDefault();
		var map_id = $( this ).attr( 'data-id' );
		var data = {
			'action': 'preview_map_action',
			'map_id': map_id
		};
		jQuery.post( ajaxurl, data, function( response ) {
			jQuery( '#TB_ajaxContent' ).html( response.maphtml );
			for ( var prop in response[ 'localized' ] ) {
				gmb_data[ prop ] = response[ 'localized' ][ prop ];
			}
			MapsBuilder.init();
			var gmb_init = document.createEvent( 'Event' );
			gmb_init.initEvent( 'MapBuilderInit', true, true );

			window.google_maps_builder_load = function( map_canvas ) {
				return MapsBuilder.global_load( map_canvas );
			};
		} );
	} );

	jQuery( 'body' ).on( 'thickbox:removed', function() {
		jQuery( '#gmb-preview-map' ).html( '' );
	} );

	$('.cls-gmb-import').on('click', function(event) {
		event.preventDefault(); // Prevent the form from submitting via the browser
		var form = $('#gmb-import-form');
		$('.gmb-import-submit-spinner .spinner').css('visibility','visible');
		$.ajax({
			type: form.attr('method'),
			url: form.attr('action'),
			data: form.serialize()
		}).done(function(data) {
			window.location.href = data;
		}).fail(function(data) {
			window.location.href = data;
		});
	});

} );