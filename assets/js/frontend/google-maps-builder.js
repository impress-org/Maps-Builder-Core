/**
 * Maps Builder JS
 *
 * Frontend form rendering
 */

(function ($, gmb) {
    "use strict";

    var map;
    var places_service;
    var place;
    var info_window;
    var directionsDisplay = [];
    var search_markers = [];
    var info_window_args = {
        maxWidth: 355,
        disableAutoPan: true
    };

    gmb.init = function () {
        var google_maps = $('.google-maps-builder');
        /*
         * Loop through maps and initialize
         */
        google_maps.each(function (index, value) {

            gmb.initialize_map($(google_maps[index]));

        });

        // fix for bootstrap tabs
        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            var panel = $(e.target).attr('href');
            gmb.load_hidden_map(panel);
        });
        //Beaver Builder Tabs
        $('.fl-tabs-label').on('click', function (e) {
            var panel = $('.fl-tabs-panel-content.fl-tab-active').get(0);
            gmb.load_hidden_map(panel);
        });
        //Tabby Tabs:
        $('.responsive-tabs__list__item').on('click', function (e) {
            var panel = $('.responsive-tabs__panel--active').get(0);
            gmb.load_hidden_map(panel);
        });
        //jQuery UI Accordions
        $('.ui-accordion-header').on('click', function (e) {
            var panel = $('.ui-accordion-content-active').get(0);
            gmb.load_hidden_map(panel);
        });
        //VC Tabs
        $('.vc_tta-tabs a').on('show.vc.tab', function () {
            google.maps.event.trigger(window, 'resize', {});
        });

    };

    /*
     * Global load function for other plugins / themes to use
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
     * Map Init After the fact
     *
     * @description Good for tabs / ajax - pass in wrapper div class/id
     * @since 2.0
     */
    gmb.load_hidden_map = function (parent) {
        var google_hidden_maps = $(parent).find('.google-maps-builder');
        if (!google_hidden_maps.length) {
            return;
        }
        google_hidden_maps.each(function (index, value) {
            //google.maps.event.trigger( map, 'resize' ); //TODO: Ideally we'd resize the map rather than reinitialize for faster performance, but that requires a bit of rewrite in how the plugin works
            gmb.initialize_map($(google_hidden_maps[index]));
        });
    };

    /**
     * Map Initialize
     *
     * Sets up and configures the Google Map
     *
     * @param map_canvas
     */
    gmb.initialize_map = function (map_canvas) {

        var map_id = $(map_canvas).data('map-id');
        var map_data = gmb_data[map_id];

        //info_window - Contains the place's information and content
        gmb.info_window = new google.maps.InfoWindow(info_window_args);

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
        gmb.set_map_markers(map, map_data, info_window);
        gmb.set_mashup_markers(map, map_data);
        gmb.set_map_directions(map, map_data);
        gmb.set_map_layers(map, map_data);
        gmb.set_map_places_search(map, map_data);

        //Display places?
        if (map_data.places_api.show_places === 'yes') {
            perform_places_search(map, map_data, info_window);
        }


    }; //end initialize_map


    /**
     * Set Map Theme
     *
     * Sets up map theme
     *
     */
    gmb.set_map_theme = function (map, map_data) {

        var map_type = map_data.map_theme.map_type.toUpperCase();
        var map_theme = map_data.map_theme.map_theme_json;

        //Custom (Snazzy) Theme
        if (map_type === 'ROADMAP' && map_theme !== 'none') {

            map.setOptions({
                mapTypeId: google.maps.MapTypeId.ROADMAP,
                styles: eval(map_theme)
            });

        } else {
            //standard theme
            map.setOptions({
                mapTypeId: google.maps.MapTypeId[map_type],
                styles: false
            });

        }


    };

    /**
     * Set Map Options
     *
     * Sets up map controls
     *
     */
    gmb.set_map_options = function (map, map_data) {

        //Zoom control
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

        //Mouse Wheel Zoom
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

        //Pan Control
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

        //Mouse Type Control
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

        //Street View Control
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

        //Map Double Click
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

        //Map Draggable
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
     * Set Map Markers
     *
     * @param map
     * @param map_data
     * @param info_window_content
     */
    gmb.set_map_markers = function (map, map_data, info_window_content) {

        var map_markers = map_data.map_markers;
        var markers = [];

        //Loop through repeatable field of markers
        $(map_markers).each(function (index, marker_data) {

            // Make sure we have latitude and longitude before creating the marker
            if (marker_data.lat == '' || marker_data.lng == '') {
                return;
            }

            var marker_label = '';

            //check for custom marker and label data
            var custom_marker_icon = (marker_data.marker_img && !isNaN(marker_data.marker_img_id)) ? marker_data.marker_img : '';
            var marker_icon = map_data.map_params.default_marker; //Default marker icon here
            var included_marker_icon = marker_data.marker_included_img !== '' ? marker_data.marker_included_img : '';

            //Plugin included marker image
            if (included_marker_icon) {
                marker_icon = map_data.plugin_url + included_marker_icon;
            }
            //Custom Marker Upload? Check if image is set
            else if (custom_marker_icon) {
                marker_icon = custom_marker_icon;
            }
            //SVG Icon
            else if ((typeof marker_data.marker !== 'undefined' && marker_data.marker.length > 0) && (typeof marker_data.label !== 'undefined' && marker_data.label.length > 0)) {
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

            //Is sign in enabled? And, do we have a place ID for this marker location?
            if (marker_data.place_id && map_data.signed_in_option === 'enabled') {

                //Remove unnecessary array params
                delete marker_args.position;

                //Add Proper Params
                marker_args.place = {
                    location: {lat: parseFloat(marker_data.lat), lng: parseFloat(marker_data.lng)},
                    placeId: marker_data.place_id
                };
                marker_args.attribution = {
                    source: map_data.site_name,
                    webUrl: map_data.site_url
                };

            }

            //Marker for map
            var location_marker = new Marker(marker_args);
            markers.push(location_marker);

            location_marker.setVisible(true);

            google.maps.event.addListener(location_marker, 'click', function () {
                gmb.set_info_window_content(marker_data, info_window_content);
                gmb.info_window.open(map, location_marker);

                //Marker Centers Map on Click?
                if (map_data.marker_centered == 'yes') {
                    window.setTimeout(function () {
                        // Pan into view, done in a time out to make it feel nicer :)
                        gmb.info_window.panToView();
                    }, 200);
                }
            });

            //Opened by default?
            if (typeof marker_data.infowindow_open !== 'undefined' && marker_data.infowindow_open == 'opened') {
                google.maps.event.addListenerOnce(map, 'idle', function () {

                    gmb.info_window.setContent('<div id="infobubble-content" class="loading"></div>');
                    gmb.set_info_window_content(marker_data, gmb.info_window);
                    gmb.info_window.open(map, location_marker);


                });
            }

        }); //end $.each()

        //Cluster?
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
     * @param gmb.info_window
     */
    gmb.set_info_window_content = function (marker_data, info_window) {

        var info_window_content = '';

        info_window_content += '<div class="gmb-infobubble">';

        //place name
        if (marker_data.title) {
            info_window_content += '<p class="place-title">' + marker_data.title + '</p>';
        }

        if (marker_data.description) {
            info_window_content += '<div class="place-description">' + marker_data.description + '</div>';
        }

        //Does this marker have a place_id
        if (marker_data.place_id && marker_data.hide_details !== 'on') {

            var request = {
                key: gmb_data.api_key,
                placeId: marker_data.place_id
            };

            //Get details from Google on this place
            places_service.getDetails(request, function (place, status) {

                if (status == google.maps.places.PlacesServiceStatus.OK) {

                    info_window_content += gmb.set_place_content_in_info_window(place);

                    info_window_content += '</div>';

                    gmb.info_window.setContent(info_window_content); //set marker content

                }
            });
        } else {
            info_window_content += '</div>';

            gmb.info_window.setContent(info_window_content); //set marker content

        }


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
        info_window_content += ((place.formatted_address) ? '<div class="place-address">' + place.formatted_address + '</div>' : '' );

        //place phone
        info_window_content += ((place.formatted_phone_number) ? '<div class="place-phone">' + place.formatted_phone_number + '</div>' : '' );

        //place website
        info_window_content += ((place.website) ? '<div class="place-website"><a href="' + place.website + '" target="_blank" rel="nofollow" title="Click to visit the ' + place.name + ' website">Website</a></div>' : '' );

        //rating
        if (place.rating) {
            info_window_content += '<div class="rating-wrap clear">' +
                '<p class="numeric-rating">' + place.rating + '</p>' +
                '<div class="star-rating-wrap">' +
                '<div class="star-rating-size" style="width:' + (65 * place.rating / 5) + 'px;"></div>' +
                '</div>' +
                '</div>'
        }

        //close wrapper
        info_window_content += '</div>';

        return info_window_content;

    }

    /**
     * Google Places Nearby Search
     */
    function perform_places_search(map, map_data, info_window) {

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
                        gmb.create_search_result_marker(map, results[i], info_window);
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
     * @param map
     * @param place
     * @param info_window
     */
    gmb.create_search_result_marker = function (map, place, info_window) {

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

            info_window.close();
            info_window.setContent('<div class="gmb-infobubble loading"></div>');

            var marker_data = {
                title: place.name,
                place_id: place.place_id
            };

            gmb.set_info_window_content(marker_data, info_window);
            info_window.open(map, search_marker);

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
    var gmb_init = new CustomEvent('MapBuilderInit');
    window.dispatchEvent(gmb_init);


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



