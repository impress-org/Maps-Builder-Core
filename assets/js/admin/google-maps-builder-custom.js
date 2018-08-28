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
} );