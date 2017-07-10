/**
 * Maps Builder JS.
 *
 * Frontend form rendering.
 */

(function ($, gmb) {
	'use strict';

	var map;
	var places_service;
	var place;
	var directionsDisplay = [];
	var search_markers = [];

	gmb.maps = [];

	gmb.init = function () {
		var google_maps = $('.google-maps-builder');

		// Loop through and initialize maps.
		google_maps.each(function (index, value) {
			gmb.initialize_map($(google_maps[index]));
		});

		// Add support for popular tab solutions.
		gmb.add_tab_support();
	};

	/*
	 * Global load function for other plugins / themes to use.
	 *
	 * ex: MapsBuilder.google_maps_builder_load( object );
	 */
	gmb.global_load = function (map_canvas) {
		if (!$(map_canvas).hasClass('google-maps-builder')) {
			return 'invalid Google Maps Builder';
		}
		gmb.initialize_map(map_canvas);
	};

	/**
	 * Initializes or resizes a map when it is revealed after page load.
	 *
	 * Gets all map canvases contained by the parent. If the map does not yet
	 * exist, it is initialized. If the map already exists, a resize is
	 * triggered so that it displays correctly. Resizing is a much less
	 * expensive operation compared to initialization. Useful for tabs,
	 * accordions, or any case where hidden map is revealed.
	 *
	 * @since 2.0
	 * @since 2.1.2 Resize existing maps instead of re-initializing them.
	 *
	 * @param {string} parent Container holding one or more map canvases.
	 */
	gmb.load_hidden_map = function ( parent ) {
		// Get all map canvases under the parent element.
		var map_canvases = $(parent).find( '.google-maps-builder' );

		if ( undefined === map_canvases || 0 === map_canvases.length ) {
			// No map canvases found.
			return;
		}

		// Get array of all maps already initialized on page.
		var maps = window.MapsBuilder.maps;

		// Loop through canvases to initialize or resize map.
		map_canvases.each(function( index, element ) {
			var map_id = $( element ).data( 'map-id' );

			if ( undefined === map_id || 0 === map_id.length ) {
				// No map ID could be retrieved from data attribute.
				return;
			}

			if ( undefined === maps[ map_id ] ) {
				// Map does not exist. Initialize map.
				gmb.initialize_map( map_canvas );
			} else {
				// Map already exists. Resize so it renders correctly.
				google.maps.event.trigger( maps[ map_id ], 'resize' );

				// Re-center map.
				var center_lat = gmb_data[map_id].map_params.latitude;
				var center_lng = gmb_data[map_id].map_params.longitude;
				var center = new google.maps.LatLng( center_lat, center_lng );
				maps[ map_id ].setCenter( center );
			}
		});
	};

	/**
	 * Map Initialize.
	 *
	 * Sets up and configures the Google Map.
	 *
	 * @param map_canvas
	 */
	gmb.initialize_map = function (map_canvas) {

		var map_id = $(map_canvas).data('map-id');
		var map_data = gmb_data[map_id];
		var latitude = (map_data.map_params.latitude) ? map_data.map_params.latitude : '32.713240';
		var longitude = (map_data.map_params.longitude) ? map_data.map_params.longitude : '-117.159443';
		var map_options = {
			center: new google.maps.LatLng(latitude, longitude),
			zoom: parseInt(map_data.map_params.zoom),
			styles: [
				{
					stylers: [
						{visibility: 'simplified'}
					]
				},
				{
					elementType: 'labels', stylers: [
					{visibility: 'off'}
				]
				}
			]
		};

		map = new google.maps.Map(map_canvas[0], map_options);
		places_service = new google.maps.places.PlacesService(map);

		gmb.set_map_options(map, map_data);
		gmb.set_map_theme(map, map_data);
		gmb.set_map_markers(map, map_data);
		gmb.set_mashup_markers(map, map_data);
		gmb.set_map_directions(map, map_data);
		gmb.set_map_layers(map, map_data);
		gmb.set_map_places_search(map, map_data);

		//Display places?
		if (map_data.places_api.show_places === 'yes') {
			perform_places_search(map, map_data);
		}

		// Store map for future reference.
		gmb.maps[ map_id ] = map;

		/**
		 * Adds custom event so map can be manipulated after it is initialized.
		 *
		 * @since 2.1.2
		 * @author Tobias Malikowski tobias.malikowski@gmail.com
		 * @see http://api.jquery.com/trigger/
		 * @see http://api.jquery.com/on/
		 */
		$( document ).trigger( 'gmb.initialize_map', [map, places_service, map_canvas] );

	}; //end initialize_map.

	/**
	 * Set Map Theme.
	 *
	 * Sets up map theme.
	 *
	 */
	gmb.set_map_theme = function (map, map_data) {

		var map_type = map_data.map_theme.map_type.toUpperCase();
		var map_theme = map_data.map_theme.map_theme_json;

		//Custom (Snazzy) Theme.
		if (map_type === 'ROADMAP' && map_theme !== 'none') {

			map.setOptions({
				mapTypeId: google.maps.MapTypeId.ROADMAP,
				styles: eval(map_theme)
			});

		} else {
			//Standard theme.
			map.setOptions({
				mapTypeId: google.maps.MapTypeId[map_type],
				styles: false
			});

		}


	};

	/**
	 * Set Map Options.
	 *
	 * Sets up map controls.
	 */
	gmb.set_map_options = function (map, map_data) {

		//Zoom control.
		var zoom_control = map_data.map_controls.zoom_control.toLowerCase();

		if (zoom_control == 'none') {
			map.setOptions({
				zoomControl: false
			});
		} else {
			map.setOptions({
				zoomControl: true,
				zoomControlOptions: {
					style: google.maps.ZoomControlStyle[zoom_control]
				}
			});
		}

		//Mouse Wheel Zoom.
		var mouse_zoom = map_data.map_controls.wheel_zoom.toLowerCase();
		if (mouse_zoom === 'none') {
			map.setOptions({
				scrollwheel: false
			});
		} else {
			map.setOptions({
				scrollwheel: true
			});
		}

		//Pan Control.
		var pan = map_data.map_controls.pan_control.toLowerCase();
		if (pan === 'none') {
			map.setOptions({
				panControl: false
			});
		} else {
			map.setOptions({
				panControl: true
			});
		}

		//Mouse Type Control.
		var map_type_control = map_data.map_controls.map_type_control;
		if (map_type_control == 'none') {
			map.setOptions({
				mapTypeControl: false
			});
		} else {
			map.setOptions({
				mapTypeControl: true,
				mapTypeControlOptions: {
					style: google.maps.MapTypeControlStyle[map_type_control]
				}
			});
		}

		//Street View Control.
		var street_view = map_data.map_controls.street_view.toLowerCase();
		if (street_view === 'none') {
			map.setOptions({
				streetViewControl: false
			});
		} else {
			map.setOptions({
				streetViewControl: true
			});
		}

		//Map Double Click.
		var double_click_zoom = map_data.map_controls.double_click_zoom.toLowerCase();
		if (double_click_zoom === 'none') {
			map.setOptions({
				disableDoubleClickZoom: true
			});
		} else {
			map.setOptions({
				disableDoubleClickZoom: false
			});
		}

		//Map Draggable.
		var draggable = map_data.map_controls.draggable.toLowerCase();
		if (draggable === 'none') {
			map.setOptions({
				draggable: false
			});
		} else {
			map.setOptions({
				draggable: true
			});
		}

	};

	/**
	 * Set Map Markers.
	 *
	 * @param map
	 * @param map_data
	 */
	gmb.set_map_markers = function (map, map_data) {

		gmb.info_window_args = {
			map: map,
			map_data: map_data,
			shadowStyle: gmb_data.infobubble_args.shadowStyle,
			padding: gmb_data.infobubble_args.padding,
			backgroundColor: gmb_data.infobubble_args.backgroundColor,
			borderRadius: gmb_data.infobubble_args.borderRadius,
			arrowSize: gmb_data.infobubble_args.arrowSize,
			minHeight: gmb_data.infobubble_args.minHeight,
			maxHeight: gmb_data.infobubble_args.maxHeight,
			minWidth: gmb_data.infobubble_args.minWidth,
			maxWidth: gmb_data.infobubble_args.maxWidth,
			borderWidth: gmb_data.infobubble_args.borderWidth,
			disableAutoPan: gmb_data.infobubble_args.disableAutoPan,
			disableAnimation: gmb_data.infobubble_args.disableAnimation,
			backgroundClassName: gmb_data.infobubble_args.backgroundClassName,
			closeSrc: gmb_data.infobubble_args.closeSrc
		};

		var map_markers = map_data.map_markers;
		var markers = [];
		map.info_window = new GMB_InfoBubble(gmb.info_window_args);

		//Loop through repeatable field of markers
		$(map_markers).each(function (index, marker_data) {

			// Make sure we have latitude and longitude before creating the marker.
			if (marker_data.lat == '' || marker_data.lng == '') {
				return;
			}

			var marker_label = '';

			//check for custom marker and label data.
			var custom_marker_icon = (marker_data.marker_img && !isNaN(marker_data.marker_img_id)) ? marker_data.marker_img : '';
			var marker_icon = map_data.map_params.default_marker; //Default marker icon here
			var included_marker_icon = marker_data.marker_included_img !== '' ? marker_data.marker_included_img : '';

			//Plugin included marker image.
			if (included_marker_icon) {
				marker_icon = map_data.plugin_url + included_marker_icon;
			} else if (custom_marker_icon) {
				//Custom Marker Upload? Check if image is set
				marker_icon = custom_marker_icon;
			} else if ((typeof marker_data.marker !== 'undefined' && marker_data.marker.length > 0) && (typeof marker_data.label !== 'undefined' && marker_data.label.length > 0)) {
				//SVG Icon
				marker_icon = eval('(' + marker_data.marker + ')');
				marker_label = marker_data.label
			}

			//Default marker args
			var marker_args = {
				position: new google.maps.LatLng(marker_data.lat, marker_data.lng),
				map: map,
				zIndex: index,
				icon: marker_icon,
				custom_label: marker_label
			};

			//Marker for map
			var location_marker = new Marker(marker_args);
			markers.push(location_marker);
			location_marker.setVisible(true);

			//Add event listener for infowindows upon a marker being clicked.
			google.maps.event.addListener(location_marker, 'click', function () {
				map.info_window.close();
				//Set marker content in info_window.
				gmb.set_info_window_content(marker_data, map, map_data).done(function () {
					map.info_window.open(map, location_marker, map_data);

					//Center markers on click option.
					//Timeout required to calculate height properly.
					if (map_data.marker_centered == 'yes') {
						window.setTimeout(function () {
							map.info_window.panToView();
						}, 300);
					}
				});

			});

			//Should this marker's info_window be opened by default?
			if (typeof marker_data.infowindow_open !== 'undefined' && marker_data.infowindow_open == 'opened') {
				google.maps.event.addListenerOnce(map, 'idle', function () {

					gmb.set_info_window_content(marker_data, map, map_data).done(function () {
						map.info_window.open(map, location_marker, map_data);
					});

				});
			}

		}); //end $.each()

		//Cluster the markers?
		if (map_data.marker_cluster === 'yes') {
			var markerCluster = new MarkerClusterer(map, markers);
		}


	};

	/**
	 * Set Infowindow Content
	 *
	 * Queries to get Google Place Details information
	 *
	 * @param marker_data
	 * @param map
	 * @param map_data
	 */
	gmb.set_info_window_content = function (marker_data, map, map_data) {

		//Create a deferred object.
		//This will allow us to wait for the Google places getDetails call via jquery's .done method.
		var done_trigger = $.Deferred();

		//The info_window content string.
		var info_window_content = '';

		//The place name if present.
		if (typeof marker_data.title !== 'undefined' && marker_data.title.length > 0) {
			info_window_content += '<p class="place-title">' + marker_data.title + '</p>';
		}

		//The place description if present.
		if (typeof marker_data.description !== 'undefined' && marker_data.description.length > 0) {
			info_window_content += '<div class="place-description">' + marker_data.description + '</div>';
		}

		//Conditions to output place information
		// a. Does this marker have a place_id?
		// b. Does the marker have a place ID value set
		// c. Ensure the hide details override isn't on.
		if (typeof marker_data.place_id !== 'undefined'
			&& marker_data.place_id
			&& marker_data.hide_details !== 'on') {

			var request = {
				key: gmb_data.api_key,
				placeId: marker_data.place_id
			};

			//Get details from Google on this place.
			places_service.getDetails(request, function (place, status) {

				if (status == google.maps.places.PlacesServiceStatus.OK) {
					info_window_content += gmb.set_place_content_in_info_window(place);
					map.info_window.setContent(info_window_content);
					map.info_window.updateContent_();
					done_trigger.resolve();

					//Marker Centers Map on Click?
					// This ensures that the map centers AFTER the loaded via AJAX.
					if (map_data.marker_centered == 'yes') {
						window.setTimeout(function () {
							// Pan into view, done in a time out to make it feel nicer :)
							map.info_window.panToView();
						}, 300);
					}


				}

			});

		} else {

			done_trigger.resolve();
			map.info_window.setContent(info_window_content); //set marker content

		}

		return done_trigger;

	};


	/**
	 * info_window Content for Place Details
	 *
	 * This marker contains more information about the place
	 *
	 * @param place
	 */
	gmb.set_place_content_in_info_window = function (place) {

		var info_window_content;

		//additional info wrapper
		info_window_content = '<div class="marker-info-wrapper">';

		//place address
		if (place.adr_address) {
			info_window_content += '<div class="place-address">';
			info_window_content += place.adr_address;
			//Directions Option
			if (place.formatted_address) {
				info_window_content += '<a href="https://www.google.com/maps/dir/Current+Location/' + encodeURIComponent(place.formatted_address) + '" class="place-directions-link" target="_blank" title="' + gmb_data.i18n.get_directions + '"><span class="place-icon"></span>' + gmb_data.i18n.get_directions + '</a>';
			}

			info_window_content += '</div>';
		}


		//Star rating.
		if (place.rating) {
			info_window_content += '<div class="rating-wrap">' +
				'<p class="numeric-rating">' + place.rating + '</p>' +
				'<div class="star-rating-wrap">' +
				'<div class="star-rating-size" style="width:' + (65 * place.rating / 5) + 'px;"></div>' +
				'</div>' +
				'</div>'
		}

		//place phone
		info_window_content += ((place.formatted_phone_number) ? '<div class="place-phone"><a href="tel:' + place.international_phone_number.replace(/\s+/g, '') + '" class="place-tel-link"><span  class="place-icon"></span>' + place.formatted_phone_number + '</a></div>' : '' );

		//place website
		info_window_content += ((place.website) ? '<div class="place-website"><a href="' + place.website + '" target="_blank" rel="nofollow"><span class="place-icon"></span>' + gmb_data.i18n.visit_website + '</a></div>' : '' );

		//close wrapper
		info_window_content += '</div>';

		return info_window_content;

	};

	/**
	 * Google Places Nearby Search
	 */
	function perform_places_search(map, map_data) {

		var map_center = map.getCenter();
		var types_array = map_data.places_api.search_places;

		//remove existing markers
		for (var i = 0; i < search_markers.length; i++) {
			search_markers[i].setMap(null);
		}
		search_markers = [];

		//Check if any place types are selected
		if (types_array.length > 0) {

			//perform search request
			var request = {
				key: gmb_data.api_key,
				location: new google.maps.LatLng(map_center.lat(), map_center.lng()),
				types: types_array,
				radius: map_data.places_api.search_radius
			};
			places_service.nearbySearch(request, function (results, status, pagination) {

				var i = 0;
				var result;

				//setup new markers
				if (status == google.maps.places.PlacesServiceStatus.OK) {

					//place new markers
					for (i = 0; result = results[i]; i++) {
						gmb.create_search_result_marker(map, results[i], map_data);
					}

					//show all pages of results @see: http://stackoverflow.com/questions/11665684/more-than-20-results-by-pagination-with-google-places-api
					if (pagination.hasNextPage) {
						pagination.nextPage();
					}

				}

			});
		}

	};

	/**
	 * Create Search Result Marker
	 *
	 * Used with Places Search to place markers on map
	 *
	 * @param map
	 * @param place
	 */
	gmb.create_search_result_marker = function (map, place, map_data) {

		var search_marker = new google.maps.Marker({
			map: map
		});

		//setup marker icon
		search_marker.setIcon(/** @type {google.maps.Icon} */({
			url: place.icon,
			size: new google.maps.Size(24, 24),
			origin: new google.maps.Point(0, 0),
			anchor: new google.maps.Point(17, 34),
			scaledSize: new google.maps.Size(24, 24)
		}));

		search_marker.setPosition(place.geometry.location);
		search_marker.setVisible(true);

		google.maps.event.addListener(search_marker, 'click', function () {

			map.info_window.close();

			var marker_data = {
				title: place.name,
				place_id: place.place_id
			};

			gmb.set_info_window_content(marker_data, map, map_data).done(function () {
				map.info_window.open(map, search_marker, map_data);
				//Center markers on click option.
				//Timeout required to calculate height properly.
				if (map_data.marker_centered == 'yes') {
					window.setTimeout(function () {
						map.info_window.panToView();
					}, 300);
				}
			});

		});

		search_markers.push(search_marker)

	};

	/**
	 * Create Mashup Marker
	 *
	 * Loops through data and creates mashup markers
	 * @param map
	 * @param map_data
	 */
	gmb.set_mashup_markers = function (map, map_data) {

		if (typeof map_data.mashup_markers === 'undefined' || !map_data.mashup_markers) {
			return false;
		}

		// Store the markers
		var markers = [];

		$(map_data.mashup_markers).each(function (index, mashup_value) {

			//Setup our vars
			var post_type = typeof mashup_value.post_type !== 'undefined' ? mashup_value.post_type : '';
			var taxonomy = typeof mashup_value.taxonomy !== 'undefined' ? mashup_value.taxonomy : '';
			var lat_field = typeof mashup_value.latitude !== 'undefined' ? mashup_value.latitude : '';
			var lng_field = typeof mashup_value.longitude !== 'undefined' ? mashup_value.longitude : '';
			var terms = typeof mashup_value.terms !== 'undefined' ? mashup_value.terms : '';

			var data = {
				action: 'get_mashup_markers',
				post_type: post_type,
				taxonomy: taxonomy,
				terms: terms,
				index: index,
				lat_field: lat_field,
				lng_field: lng_field
			};

			jQuery.post(map_data.ajax_url, data, function (response) {

				//Loop through marker data
				$.each(response, function (index, marker_data) {
					var marker = gmb.set_mashup_marker(map, data.index, marker_data, mashup_value, map_data);
					if (marker instanceof Marker) {
						markers.push(marker);
					}
				});

				//Cluster?
				if (map_data.marker_cluster === 'yes') {
					var markerCluster = new MarkerClusterer(map, markers);
				}

			}, 'json');

		});

	};

	/**
	 * Add support for popular tab solutions.
	 *
	 * @since 2.1.2
	 */
	gmb.add_tab_support = function () {
		// Tabby Tabs.
		$( '.responsive-tabs' ).on( 'click', '.responsive-tabs__heading, .responsive-tabs__list__item', function() {
			gmb.load_hidden_map( '.responsive-tabs__panel--active' );
		});

		// Elementor Tabs (maps work in front-end tabs but don't display in editor).
		$( '.elementor-tabs' ).on( 'click', '.elementor-tab-title', function() {
			var tab = $( this ).data( 'tab' );
			gmb.load_hidden_map( '.elementor-tab-content[data-tab="' + tab + '"]' );
		});

		// Divi Theme and Divi Builder Tabs.
		$( document ).on( 'simple_slider_after_move_to', function() {
			gmb.load_hidden_map( '.et-pb-active-slide' );
		});

		// Bootstrap Tabs.
		$( 'a[data-toggle="tab"]' ).on( 'shown.bs.tab', function ( e ) {
			gmb.load_hidden_map( $( e.target ).attr( 'href' ) );
		});

		// Beaver Builder Tabs.
		$( '.fl-tabs-label' ).on( 'click', function () {
			gmb.load_hidden_map( $( '.fl-tab-active' ) );
		});

		// Visual Composer Tabs.
		$( '.vc_tta-tabs' ).on( 'show.vc.tab', function () {
			gmb.load_hidden_map( $( '.vc_tta-panel.vc_active' ) );
		});
	};

	//pro only functions
	gmb.set_map_directions = function (map, map_data) {
	};
	gmb.set_map_layers = function (map, map_data) {
	};
	gmb.set_map_places_search = function (map, map_data) {
	};


}(jQuery, window.MapsBuilder || ( window.MapsBuilder = {} )) );

jQuery(document).ready(function () {
	var gmb_data;

	MapsBuilder.init();

	/**
	 * Event for after the MapsBuilder Front-end JS loads
	 *
	 * @since 2.1.0
	 *
	 * @type {CustomEvent}
	 */
	var gmb_init = document.createEvent('Event');
	gmb_init.initEvent('MapBuilderInit', true, true);

});


/*
 * Backwards compatibility function
 * Instead use:

 document.addEventListener("MapBuilderInit", function(){
 MapsBuilder.global_load( map_canvas );
 }, false);

 */
window.google_maps_builder_load = function (map_canvas) {
	return MapsBuilder.global_load(map_canvas);
};
