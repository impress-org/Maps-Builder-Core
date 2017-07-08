/**
 * Admin Google Maps
 *
 * Enqueued on the single `google_maps` CPT and responsible for interface map creation; the methods are extendable by admin-free.js and admin-pro.js
 */
var gmb_data;

(function ($, gmb) {

    "use strict";

    /**
     * Initialize on window load
     */
    gmb.init = function () {
        gmb.toggle_metabox_fields();

        //tooltips
        gmb.initialize_tooltips();

        //Map type Metabox on load
        gmb.initialize_map($('#map'));

        //Latitude on Change
        $('#gmb_lat_lng-latitude').on('change', function () {
            gmb.lat_lng_field_change(map);
        });
        //Longitude on Change
        $('#gmb_lat_lng-longitude').on('change', function () {
            gmb.lat_lng_field_change(map);
        });

        //click to add marker
        $('.drop-marker').on('click', function (e) {
            e.preventDefault();
            if ($(this).hasClass('active')) {
                $(this).html(gmb_data.i18n.btn_drop_marker).removeClass('active');
                map.setOptions({draggableCursor: null}); //reset cursor
            } else {
                $(this).text(gmb_data.i18n.btn_drop_marker_click).addClass('active');
                map.setOptions({draggableCursor: 'crosshair'});
                var dropped_marker_event = google.maps.event.addListener(map, 'click', function (event) {
                    gmb.drop_marker(event.latLng, dropped_marker_event);
                });
            }
        });

        //Radius Fields
        var current_radius;

        //Search Radius Circle
        $('#gmb_search_radius').on('focus', function () {
            google.maps.event.trigger(map, 'resize'); //refresh map to get exact center
            current_radius = $(this).val();
            gmb.calc_radius(map, parseInt($(this).val()));
        }).focusout(function () {
            if (current_radius !== $(this).val()) {
                gmb.perform_places_search();
            }
            radius_circle.setMap(null); //removes circle on focus out
            radius_marker.setMap(null); //removes circle on focus out
        });

        //Places Type Field
        $('[name^="gmb_places_search_multicheckbox"]').on('change', function () {

            //Show message if not already displayed
            if ($('.places-change-message').length === 0) {
                $('.cmb2-id-gmb-places-search-multicheckbox ul').prepend('<div class="wpgp-message places-change-message clear"><p>' + gmb_data.i18n.places_selection_changed + '</p><a href="#" class="button update-places-map">' + gmb_data.i18n.set_place_types + '</a></div>');
                $('.places-change-message').slideDown();
            }

        });

        $('.cmb-multicheck-toggle').on('click', function () {
            if ($('.places-change-message').length === 0) {
                $('.cmb2-id-gmb-places-search-multicheckbox ul').prepend('<div class="wpgp-message places-change-message clear"><p>' + gmb_data.i18n.places_selection_changed + '</p><a href="#" class="button update-places-map">' + gmb_data.i18n.set_place_types + '</a></div>');
                $('.places-change-message').slideDown();
            }
        });

        //Places Update Map Button
        $(document).on('click', '.update-places-map', function (e) {
            e.preventDefault();
            gmb.scroll_to_field("#google_maps_preview_metabox");
            gmb.perform_places_search();
            $(this).parent().fadeOut(function () {
                $(this).remove();
            });
        });

        //Update lat lng message
        $('.lat-lng-update-btn, .update-lat-lng').on('click', function (e) {
            e.preventDefault();
            $('.lat-lng-update-btn, .update-lat-lng').attr('disabled', 'disabled').removeClass('button-primary');
            $('.lat-lng-change-message').slideUp();
            $('#gmb_lat_lng-latitude').val($(this).attr('data-lat'));
            $('#gmb_lat_lng-longitude').val($(this).attr('data-lng'));
        });


        //Add New Marker
        $(document).on('click', '.add-marker', function (e) {

            e.preventDefault();
            hover_circle.setVisible(false);

            //update marker with set marker
            var location_marker = new google.maps.Marker({
                position: tentative_location_marker.getPosition(),
                map: map,
                icon: gmb_data.default_marker,
                zIndex: google.maps.Marker.MAX_ZINDEX + 1,
                optimized: false
            });

            //hide tentative green marker
            tentative_location_marker.setVisible(false);

            //get current number of repeatable rows ie markers
            var index = gmb.get_marker_index();

            var place_id = $(this).data('place_id');

            //add data to fields
            gmb.get_editable_info_window(index, location_marker);

            $('input[data-field="#gmb_markers_group_' + index + '_title"]').val($(this).data('title'));
            $('input#gmb_markers_group_' + index + '_lat').val($(this).data('lat'));
            $('input#gmb_markers_group_' + index + '_lng').val($(this).data('lng'));
            $('input#gmb_markers_group_' + index + '_place_id').val(place_id);


            //location clicked
            google.maps.event.addListener(location_marker, 'click', function () {
                gmb.get_info_window_content(index, location_marker);
            });

        });

        //Map Marker Set
        gmb.set_map_marker_icon();

        //Map Type
        $('#gmb_type').change(function () {
            gmb.set_map_type(true);
        });
        //Map Theme
        $('#gmb_theme').change(function () {
            gmb.set_map_theme();
        });
        //street view
        $('#gmb_street_view').change(function () {
            gmb.set_street_view();
        });
        //Pan
        $('#gmb_pan').change(function () {
            gmb.set_pan_control();
        });
        //Draggable
        $('#gmb_draggable').change(function () {
            gmb.set_draggable();
        });
        //Double Click Zoom
        $('#gmb_double_click').change(function () {
            gmb.set_double_click_zoom();
        });
        //Double Click Zoom
        $('#gmb_wheel_zoom').change(function () {
            gmb.set_mouse_wheel_scroll();
        });
        //Map Type Control
        $('#gmb_map_type_control').change(function () {
            gmb.set_map_type_control();
        });
        //Zoom Control
        $('#gmb_zoom_control').change(function () {
            gmb.set_map_zoom_control();
        });
        //Marker Animation
        $('#gmb_marker_animate1').change(function () {
            gmb.clear_main_markers();
        });


        //Close repeaters
        $('.cmb-repeatable-grouping').addClass('closed');

        //Add Repeater toggle button
        $('.toggle-repeater-groups').on('click', function (e) {
            e.preventDefault();
            $('#gmb_markers_group_repeat').find('.cmb-repeatable-grouping').toggleClass('closed');
        });

        //Window resize
        $(window).on('resize', function () {
            //Ensure window resizes triggers map resize
            google.maps.event.trigger(map, 'resize');
        });

    };


    var map;
    var places_service;
    var lat_lng;
    var zoom;
    var lat_field;
    var lng_field;
    var radius_circle;
    var radius_marker;
    var place;
    var autocomplete;
    var info_bubble;
    var info_bubble_array = [];
    var tentative_location_marker;
    var location_marker;
    var location_marker_array = [];
    var search_markers = [];
    var hover_circle;
    var initial_location;
    var delay = (function () {
        var timer = 0;
        return function (callback, ms) {
            clearTimeout(timer);
            timer = setTimeout(callback, ms);
        };
    })();

    /**
     * Place marker on Map on Click
     *
     * @param lat_lng
     * @param event
     */
    gmb.drop_marker = function (lat_lng, event) {

        var lat = lat_lng.lat();
        var lng = lat_lng.lng();

        //hide any tentative markers already in place
        if (typeof drop_location_marker !== 'undefined') {
            drop_location_marker.setVisible(false);
        }

        $('.drop-marker').removeClass('active').html(gmb_data.i18n.btn_drop_marker); //reset drop button
        map.setOptions({draggableCursor: null}); //reset cursor
        google.maps.event.removeListener(event); //remove map click event

        //add marker at clicked location
        var drop_location_marker = new Marker({
            position: lat_lng,
            map: map,
            icon: gmb_data.default_marker,
            zIndex: google.maps.Marker.MAX_ZINDEX + 1,
            optimized: false
        });

        //get current number of repeatable rows ie markers
        var index = gmb.get_marker_index();

        //add data to fields
        $('#gmb_markers_group_' + index + '_title').val('Point ' + parseInt(index + 1)); //increment index to match visual ID (actually 0)
        $('#gmb_markers_group_' + index + '_lat').val(lat);
        $('#gmb_markers_group_' + index + '_lng').val(lng);

        gmb.get_editable_info_window(index, drop_location_marker);

        google.maps.event.addListener(drop_location_marker, 'click', function () {
            gmb.get_info_window_content(index, drop_location_marker);
        });

    };

    /**
     * Map Intialize
     *
     * Sets up and configures the Google Map
     *
     * @param map_canvas
     */
    gmb.initialize_map = function (map_canvas) {

        lat_field = $('#gmb_lat_lng-latitude');
        lng_field = $('#gmb_lat_lng-longitude');
        var lat_toolbar = $('.live-latitude');
        var lng_toolbar = $('.live-longitude');
        var latitude = ((lat_field.val()) ? lat_field.val() : '');
        var longitude = ((lng_field.val()) ? lng_field.val() : '');
        zoom = parseInt($('#gmb_zoom').val());
        lat_lng = new google.maps.LatLng(latitude, longitude);

        var mapOptions = {
            zoom: zoom,
            streetViewControl: false,
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

        map = new google.maps.Map(map_canvas[0], mapOptions);
        window.map = map;
        places_service = new google.maps.places.PlacesService(map);

        //Handle Map Geolocation
        if (navigator.geolocation && gmb_data.geolocate_setting === 'yes' && longitude == '' && latitude == '' && location.protocol === 'https:') {

            navigator.geolocation.getCurrentPosition(function (position) {
                initial_location = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);
                map.setCenter(initial_location); //set map with location
                lat_field.val(position.coords.latitude); //set lat field
                lng_field.val(position.coords.longitude); //set lng field
                lat_toolbar.text(position.coords.latitude); //update toolbar
                lng_toolbar.text(position.coords.longitude); //update toolbar
            });
        }
        // Presaved longitude and latitude is in place
        else if (latitude !== '' && longitude !== '') {

            //set map with saved lat/lng
            map.setCenter(new google.maps.LatLng(latitude, longitude));

        } else {

            initial_location = new google.maps.LatLng(gmb_data.default_lat, gmb_data.default_lng);
            lat_field.val(gmb_data.default_lat); //set lat field
            lng_field.val(gmb_data.default_lng); //set lng field
            lat_toolbar.text(gmb_data.default_lat); //update toolbar
            lng_toolbar.text(gmb_data.default_lng); //update toolbar
            map.setCenter(initial_location);
        }

        //Set various map view options
        gmb.set_map_type(false);
        gmb.set_map_theme();
        gmb.set_street_view();
        gmb.set_pan_control();
        gmb.set_draggable();
        gmb.set_double_click_zoom();
        gmb.set_mouse_wheel_scroll();
        gmb.set_map_type_control();
        gmb.set_map_zoom_control();

        //Setup Autocomplete field if undefined
        if (typeof(autocomplete) == 'undefined') {

            var autocomplete_el = $('#gmb_geocoder');

            autocomplete = new google.maps.places.Autocomplete(autocomplete_el[0]);
            autocomplete.bindTo('bounds', map);

            //Tame the enter key to not save the widget while using the autocomplete input
            google.maps.event.addDomListener(autocomplete_el[0], 'keydown', function (e) {
                if (e.keyCode == 13) {
                    e.preventDefault();
                }
            });

            //Autocomplete event listener
            google.maps.event.addListener(autocomplete, 'place_changed', function () {

                //Clear autocomplete input value
                autocomplete_el.one('blur', function () {
                    autocomplete_el.val('');
                });
                setTimeout(function () {
                    autocomplete_el.val('');
                }, 10);

                if (typeof tentative_location_marker !== 'undefined') {
                    tentative_location_marker.setVisible(false);
                }

                //Close a modal if applicable
                $('.cmb2-id-gmb-geocoder').find('.gmb-modal-close').trigger('click');
                $('.cmb2-id-gmb-geocoder').find('.mfp-close').trigger('click');

                //get place information
                place = autocomplete.getPlace();

                //set lat lng input values
                lat_field.val(place.geometry.location.lat());
                lng_field.val(place.geometry.location.lng());

                if (!place.geometry) {
                    alert('Error: Place not found!');
                    return;
                }

                map.setCenter(place.geometry.location);
                gmb.add_tentative_marker(map, place.place_id);

            });
        }

        //InfoBubble - Contains the place's information and content
        info_bubble = new google.maps.InfoWindow({
            maxWidth: 350
        });

        /**
         * Map Event Listeners
         */
        //map loaded fully (fires once)
        google.maps.event.addListenerOnce(map, 'idle', function () {
            gmb.handle_map_zoom(map);
            gmb.add_markers(map);

            //toggle places
            if (typeof $('.cmb2-id-gmb-show-places input:radio').prop('checked') !== 'undefined' && $('.cmb2-id-gmb-show-places input:radio:checked').val() === 'yes') {
                gmb.perform_places_search();
            }

        });

        //map Zoom Changed
        google.maps.event.addListener(map, 'zoom_changed', function () {
            gmb.handle_map_zoom(map);
        });

        //Update lng and lat on map drag
        google.maps.event.addListener(map, 'dragend', function () {
            var map_center = map.getCenter();
            $('.lat-lng-change-message').slideDown();
            $('.lat-lng-update-btn').attr('data-lat', map_center.lat());
            $('.lat-lng-update-btn').attr('data-lng', map_center.lng());
        });


    }; //end initialize_map

    /**
     * Shows a Marker when Autocomplete search is used
     * @param map
     * @param place_id
     */
    gmb.add_tentative_marker = function (map, place_id) {

        var map_center = map.getCenter();

        //Marker for map
        tentative_location_marker = new google.maps.Marker({
            map: map,
            title: 'Map Icons',
            animation: google.maps.Animation.DROP,
            position: new google.maps.LatLng(map_center.lat(), map_center.lng()),
            icon: new google.maps.MarkerImage(gmb_data.plugin_url + "assets/img/default-icon-green-no-dot.png"),
            zIndex: google.maps.Marker.MAX_ZINDEX + 1,
            optimized: false
        });

        //EVENTS
        var location_marker_mouseover = google.maps.event.addListener(tentative_location_marker, 'mouseover', function (event) {
            gmb.add_circle(place_id);
        });
        var location_marker_mouseout = google.maps.event.addListener(tentative_location_marker, 'mouseout', function (event) {
            hover_circle.setVisible(false);
        });

        //location clicked
        google.maps.event.addListener(tentative_location_marker, 'click', function () {
            //remove event listeners
            google.maps.event.removeListener(location_marker_mouseover);
            google.maps.event.removeListener(location_marker_mouseout);
            //show circle
            hover_circle.setVisible(true);
            //update marker icons
            //Get initial place details from place_id
            gmb.add_tenative_info_window(place_id, tentative_location_marker);
        });


        //Update map with marker position according to lat/lng
        tentative_location_marker.setVisible(true);
        map.setZoom(zoom);

    };

    /**
     * Set the editable marker window content
     */
    gmb.add_tenative_info_window = function (place_id, marker) {

        var request = {
            key: gmb_data.api_key,
            placeId: place_id
        };

        places_service.getDetails(request, function (place, status) {

            if (status == google.maps.places.PlacesServiceStatus.OK) {

                var lat = place.geometry.location.lat();
                var lng = place.geometry.location.lng();

                var info_window_content = '<p class="place-title">' + place.name + '</p>';

                info_window_content += gmb.add_place_content_to_info_window(place);

                info_window_content += '<div class="infowindow-toolbar clear"><a href="#" class="add-marker" data-title="' + place.name + '" data-place_id="' + place.place_id + '"  data-lat="' + lat + '" data-lng="' + lng + '">Add to Map</a></div>';

                info_window_content = gmb.set_info_window_wrapper(info_window_content); //wraps the content in div and returns

                info_bubble.setContent(info_window_content); //sets the info window content

                info_bubble.open(map, marker); //opens the info window

                //close info window button
                google.maps.event.addListener(info_bubble, 'closeclick', function () {
                    //Get initial place details from place_id
                    hover_circle.setVisible(false);

                });


            }

        });

    };

    /**
     * info_bubble Content for Place Details
     *
     * This marker contains more information about the place
     *
     * @param place
     */
    gmb.add_place_content_to_info_window = function (place) {

        var info_window_content;

        //additional info wrapper
        info_window_content = '<div class="marker-info-wrapper">';

        //place address
        info_window_content += ((place.formatted_address) ? '<div class="place-address">' + place.formatted_address + '</div>' : '' );

        //place phone
        info_window_content += ((place.formatted_phone_number) ? '<div class="place-phone">' + place.formatted_phone_number + '</div>' : '' );

        //place website
        info_window_content += ((place.website) ? '<div class="place-website"><a href="' + place.website + '" target="_blank" rel="nofollow" title="Click to visit the ' + place.name + ' website">' + gmb_data.i18n.visit_website + '</a></div>' : '' );

        //rating
        if (place.rating) {
            info_window_content += '<div class="rating-wrap clear">' +
                '<p class="numeric-rating">' + place.rating + '</p>' +
                '<div class="star-rating-wrap">' +
                '<div class="star-rating-size" style="width:' + (65 * place.rating / 5) + 'px;"></div>' +
                '</div>' +
                '</div>'
        }

        //Directions Option
        if (place.formatted_address) {
            info_window_content += '<a href="https://www.google.com/maps/dir/Current+Location/' + encodeURIComponent(place.formatted_address) + '" target="_blank" title="' + gmb_data.i18n.get_directions + '">' + gmb_data.i18n.get_directions + '</a>';
        }

        //close wrapper
        info_window_content += '</div>';


        return info_window_content;

    };

    /**
     * info_bubble Content for Place Details
     *
     * This marker contains more information about the place.
     * @TODO: AJAXify & Clean up
     */
    gmb.get_editable_info_window = function (index, marker) {

        info_bubble.close();

        info_bubble.setContent('<div id="infobubble-content" class="loading"></div>');

        info_bubble.open(map, marker);

        var info_window_data = gmb.get_info_window_saved_data(index);

        var info_window_content;

        //default title
        if (!info_window_data.title) {
            info_window_data.title = 'Point ' + index;
        }

        //place name
        if (info_window_data.title) {
            info_window_content = '<input class="edit-place-title" data-field="#gmb_markers_group_' + index + '_title" type="text" value="' + info_window_data.title + '">';
        }

        if (info_window_data.desc) {
            info_window_content += '<textarea class="edit-place-description" data-field="#gmb_markers_group_' + index + '_description">' + info_window_data.desc + '</textarea>';
        } else {
            info_window_content += '<textarea class="edit-place-description" data-field="#gmb_markers_group_' + index + '_description"></textarea>';
        }

        //toolbar
        info_window_content += '<div class="infowindow-toolbar clear"><ul id="save-toolbar">' +
            '<li class="info-window-save"><div class="google-btn-blue google-btn google-save-btn" data-tooltip="Save changes" data-index="' + index + '">Save</div></li>' +
            '<li class="info-window-cancel"><div class="google-btn-default google-btn google-cancel-btn" data-tooltip="Cancel edit" data-index="' + index + '">Cancel</div></li>' +
            '</ul>' +
            '<span class="marker-edit-link-wrap" data-index="' + index + '"><a href="#" data-target="marker-icon-modal" data-tooltip="Change icon" data-mfp-src="#marker-icon-modal" class="marker-edit-link gmb-magnific-marker gmb-magnific-inline"></a></span>' +
            '</div>';

        //Set info_window content
        info_window_content = gmb.set_info_window_wrapper(info_window_content);
        info_bubble.setContent(info_window_content);
        gmb.initialize_tooltips(); //refresh tooltips

        //Save info window content
        google.maps.event.addDomListener($('.google-save-btn')[0], 'click', function () {

            //take info window vals and save to markers' repeatable group
            var title_field_id = $('.edit-place-title').data('field');
            var title_field_val = $('.edit-place-title').val();

            var desc_field_id = $('.edit-place-description').data('field');
            var desc_field_val = $('.edit-place-description').val();

            $(title_field_id).val(title_field_val);
            $(desc_field_id).val(desc_field_val);

            //close info window and remove marker circle
            gmb.get_info_window_content($(this).data('index'), marker);
            google.maps.event.removeListener(save_icon_listener); //remove this event listener
            google.maps.event.removeListener(edit_marker_icon_button_click); //remove this event listener

        });

        //Remove row button/icon also removes icon (CMB2 buttons)
        $('#gmb_markers_group_' + index + '_title').parents('.cmb-repeatable-grouping').find('.cmb-remove-group-row').each(function () {
            google.maps.event.addDomListener($(this)[0], 'click', function () {
                var index = $(this).parents('.cmb-repeatable-grouping').data('index');
                //close info window and remove marker
                info_bubble.close();
                marker.setVisible(false);
            });
        });

        //Close Click
        google.maps.event.addDomListener(info_bubble, 'closeclick', function () {
            google.maps.event.removeListener(save_icon_listener); //remove this event listener
            google.maps.event.removeListener(edit_marker_icon_button_click); //remove this event listener
        });

        //Cancel info window content
        google.maps.event.addDomListener($('.google-cancel-btn')[0], 'click', function () {
            //close info window and remove marker circle
            gmb.get_info_window_content($(this).data('index'), marker);
            google.maps.event.removeListener(save_icon_listener); //remove this event listener
            google.maps.event.removeListener(edit_marker_icon_button_click); //remove this event listener

        });

        //Infowindow pin icon click to open magnific modal
        var edit_marker_icon_button_click = google.maps.event.addDomListener($('.marker-edit-link-wrap')[0], 'click', function () {
            $('.save-marker-button').attr('data-marker-index', $(this).data('index')); //Set the index for this marker
        });

        //Marker Modal Update Icon
        var save_icon_listener = google.maps.event.addDomListener($('.save-marker-button')[0], 'click', function (e) {

            e.preventDefault();
            var marker_position = marker.getPosition();
            var marker_icon = $(this).data('marker');
            var marker_icon_color = $(this).data('marker-color');
            var label_color = $(this).data('label-color');
            var marker_icon_data;

            //Inline style for marker to set
            var marker_label_inline_style = 'color:' + label_color + '; ';
            if (marker_icon === 'MAP_PIN') {
                marker_label_inline_style += 'font-size: 20px;position: relative; top: -3px;'; //position: relative; top: -44px; font-size: 24px;
            } else if (marker_icon == 'SQUARE_PIN') {
                marker_label_inline_style += 'font-size: 20px;position: relative; top: 12px;';
            }

            //collect marker data from submit button
            var marker_label_data = '<i class="' + $(this).data('label') + '" style="' + marker_label_inline_style + '"></i>';


            //Clear marker vals
            gmb.clear_marker_values(index);

            //Determine which type of marker to place
            if (marker_icon == 'mapicons' || marker_icon == 'upload' || marker_icon == 'default') {

                marker_icon_data = $(this).data('marker-image');
                marker_label_data = ''; //no label here (img marker)

                //If marker image is an upload set full path
                if (marker_icon == 'upload') {
                    $('#gmb_markers_group_' + index + '_marker_img').val(marker_icon_data);
                } else {
                    //else set marker image relative path
                    var new_marker_img_path = marker_icon_data.replace(gmb_data.plugin_url, '');
                    $('#gmb_markers_group_' + index + '_marker_included_img').val(new_marker_img_path);
                }

            }
            //custom SVG markers
            else if (marker_icon == 'MAP_PIN' || marker_icon == 'SQUARE_PIN') {
                //maps-icon
                marker_icon_data = '{ path : ' + marker_icon + ', fillColor : "' + marker_icon_color + '", fillOpacity : 1, strokeColor : "", strokeWeight: 0, scale : 1 / 3 }';
                //Update fields with necessary data
                $('#gmb_markers_group_' + index + '_marker').val(marker_icon_data);
                $('#gmb_markers_group_' + index + '_label').val(marker_label_data);
                marker_icon_data = eval('(' + marker_icon_data + ')');
                $('#gmb_markers_group_' + index + '_marker_img').val(''); //set marker image field
            }

            //remove current marker from map
            marker.setMap(null);

            var marker_args = {
                position: marker_position,
                map: map,
                zIndex: 9,
                icon: marker_icon_data,
                custom_label: marker_label_data
            };

            //Update Icon
            marker = new Marker(marker_args);

            //Add event listener to new marker
            google.maps.event.addListener(marker, 'click', function () {
                gmb.get_info_window_content(index, marker);
            });

            //Clean up modal and close
            $('.icon, .marker-item').removeClass('marker-item-selected'); //reset modal
            $('.marker-icon-row, .save-marker-icon').hide(); //reset modal
            $(this).removeData('marker'); //Remove data
            $(this).removeData('marker-color'); //Remove data
            $(this).removeData('marker-img'); //Remove data
            $(this).removeData('label'); //Remove data
            $(this).removeData('label-color'); //Remove data
            if ($('.magnific-builder').length === 0) {
                $.magnificPopup.close(); // Close popup that is currently opened (shorthand)
            } else {
                $('.gmb-modal-close').trigger('click');
            }
            google.maps.event.removeListener(save_icon_listener); //remove this event listener
            google.maps.event.removeListener(edit_marker_icon_button_click); //remove this event listener

        });

    };

    /**
     * Wrap Info Window Content
     *
     * Help function that sets a div container around info window
     * @param content
     */
    gmb.set_info_window_wrapper = function (content) {

        var info_window_content = '<div id="infobubble-content" class="main-place-infobubble-content">';

        info_window_content += content;

        info_window_content += '</div>';

        return info_window_content;

    };

    /**
     * Adds a marker circle
     */
    gmb.add_circle = function (place_id) {

        hover_circle = new google.maps.Marker({
            position: tentative_location_marker.getPosition(),
            zIndex: google.maps.Marker.MAX_ZINDEX - 1,
            optimized: false,
            icon: {
                path: google.maps.SymbolPath.CIRCLE,
                scale: 20,
                strokeWeight: 3,
                strokeOpacity: 0.9,
                strokeColor: '#FFF',
                fillOpacity: .3,
                fillColor: '#FFF'
            },
            map: map
        });


        google.maps.event.addListener(hover_circle, 'click', function () {
            //Get initial place details from place_id
            gmb.add_tenative_info_window(place_id, tentative_location_marker);
        });
        google.maps.event.addListener(tentative_location_marker, 'click', function () {
            //Get initial place details from place_id
            hover_circle.setVisible(true);
        });

    };

    /**
     *  Add Markers
     *
     * @description This is the marker that first displays on load for the main location or place
     *
     * @param map
     */
    gmb.add_markers = function (map) {

        gmb.clear_main_markers();
        var time = 500;
        var markers = [];
        var cluster_markers = $('#gmb_marker_cluster1').prop('checked');

        //Loop through repeatable field of markers
        $('#gmb_markers_group_repeat').find('.cmb-repeatable-grouping').each(function (index) {

            var marker_icon = gmb_data.default_marker;
            var marker_label = '';

            //check for custom marker and label data
            var custom_marker_svg = $('#gmb_markers_group_' + index + '_marker').val();
            var custom_marker_img = $('#gmb_markers_group_' + index + '_marker_img').val();
            var included_marker_img = $('#gmb_markers_group_' + index + '_marker_included_img').val();

            //Plugin included marker image
            if (included_marker_img) {
                marker_icon = gmb_data.plugin_url + included_marker_img;
            } else if (custom_marker_img) {
                //Uploaded marker image
                marker_icon = custom_marker_img;
            } else if (custom_marker_svg.length > 0 && custom_marker_svg.length > 0) {
                //SVG Marker
                var custom_label = $('#gmb_markers_group_' + index + '_label').val();
                marker_icon = eval('(' + custom_marker_svg + ')');
                marker_label = custom_label;
            }

            var marker_lat = parseFloat($('#gmb_markers_group_' + index + '_lat').val());
            var marker_lng = parseFloat($('#gmb_markers_group_' + index + '_lng').val());
            var place_id = $('#gmb_markers_group_' + index + '_place_id').val();
            var position = new google.maps.LatLng(marker_lat, marker_lng);

            //Default marker args
            var marker_args = {
                position: position,
                map: map,
                zIndex: index,
                icon: marker_icon,
                custom_label: marker_label
            };

            //Marker for map
            var location_marker = new Marker(marker_args);
            markers.push(location_marker);

            location_marker.setVisible(true);

            //Set click action for marker to open infowindow
            google.maps.event.addListener(location_marker, 'click', function () {
                gmb.get_info_window_content(index, location_marker);
            });

            time += 500;

            //Remove row button/icon also removes icon (CMB2 buttons)
            $('#gmb_markers_group_' + index + '_title').parents('.cmb-repeatable-grouping').find('.cmb-remove-group-row').each(function () {
                google.maps.event.addDomListener($(this)[0], 'click', function () {
                    var index = $(this).parents('.cmb-repeatable-grouping').data('index');
                    //close info window and remove marker
                    info_bubble.close();
                    location_marker.setVisible(false);
                });
            });

        }); //end $.each()

        //Cluster?
        if (cluster_markers === true) {
            var markerCluster = new MarkerClusterer(map, markers);
        }

    };

    /**
     * Get Info Window Saved Data
     *
     * @param index
     * @returns {{}}
     */
    gmb.get_info_window_saved_data = function (index) {

        var info_window_data = {};

        info_window_data.title = $('#gmb_markers_group_' + index + '_title').val();
        info_window_data.desc = $('#gmb_markers_group_' + index + '_description').val();
        info_window_data.reference = $('#gmb_markers_group_' + index + '_reference').val();
        info_window_data.place_id = $('#gmb_markers_group_' + index + '_place_id').val();
        info_window_data.lat = $('#gmb_markers_group_' + index + '_lat').val();
        info_window_data.lng = $('#gmb_markers_group_' + index + '_lng').val();
        info_window_data.hide_place_info = $('#gmb_markers_group_' + index + '_hide_details').prop('checked');

        return info_window_data;

    };

    /**
     * Queries to get Google Place Details information
     *
     * Help function
     * @param index
     * @param marker
     */
    gmb.get_info_window_content = function (index, marker) {

        info_bubble.close();

        info_bubble.setContent('<div id="infobubble-content" class="loading"></div>');

        info_bubble.open(map, marker);

        var info_window_data = gmb.get_info_window_saved_data(index);

        //Start building infowindow content
        var info_window_content = '<p class="place-title">' + info_window_data.title + '</p>';

        info_window_content += '<div class="place-description">' + info_window_data.desc + '</div>';

        //Show place information within this infowindow?
        if (info_window_data.place_id && info_window_data.hide_place_info === false) {

            var request = {
                key: gmb_data.api_key,
                placeId: info_window_data.place_id
            };
            places_service.getDetails(request, function (place, status) {

                if (status == google.maps.places.PlacesServiceStatus.OK) {

                    info_window_content += gmb.add_place_content_to_info_window(place);
                    info_window_content += gmb.set_marker_edit_icons(index);
                    gmb.add_edit_events(info_window_content, marker);

                }

            }); //end getPlaces

        } else {
            info_window_content += gmb.set_marker_edit_icons(index);
            gmb.add_edit_events(info_window_content, marker);
        }
    };

    /**
     * Add Edit Events
     *
     * Sets up Google Map event listeners and other setup for info bubbles
     *
     * @param content
     * @param marker
     */
    gmb.add_edit_events = function (content, marker) {

        content = gmb.set_info_window_wrapper(content); //wraps the content in div and returns
        info_bubble.setContent(content); //set infowindow content
        gmb.initialize_tooltips(); //refresh tooltips

        //edit button event
        google.maps.event.addDomListener($('.edit-info')[0], 'click', function () {
            //Edit Marker
            gmb.get_editable_info_window($(this).data('index'), marker);
        });

        //trash button event
        google.maps.event.addDomListener($('.trash-marker')[0], 'click', function () {
            var index = $(this).data('index');
            //Clear our input values
            $('div[data-iterator="' + index + '"] ').find('input,textarea').val('');
            //trigger remove row button click for this specific markers row
            $('div[data-iterator="' + index + '"]').find('.cmb-remove-group-row').trigger('click');
            //close info window and remove marker
            info_bubble.close();
            marker.setVisible(false);
        });

    };

    /**
     * Marker Index
     *
     * @description Helper function that returns the appropriate index for the repeatable group
     * @returns {Number}
     */
    gmb.get_marker_index = function () {

        var marker_repeatable = $('#gmb_markers_group_repeat');
        var marker_repeatable_group = marker_repeatable.find(' div.cmb-repeatable-grouping');
        var marker_add_row_btn = marker_repeatable.find('.cmb-add-group-row.button');

        //Create a new marker repeatable meta group
        var index = parseInt(marker_repeatable_group.last().attr('data-iterator'));
        var existing_vals = marker_repeatable_group.first().find('input,textarea').val();

        //Ensure appropriate index is used for marker
        if (existing_vals && index === 0) {
            marker_add_row_btn.trigger('click');
            index = 1;
        } else if (index !== 0) {
            marker_add_row_btn.trigger('click');
            //recount rows
            index = parseInt(marker_repeatable.find(' div.cmb-repeatable-grouping').last().attr('data-iterator'));
        }

        return index;

    };

    /**
     * Google Places Marker Info Window
     *
     * @param place
     * @param marker
     */
    gmb.get_place_info_window_content = function (place, marker) {

        info_bubble.setContent('<div id="infobubble-content" class="loading"></div>');

        info_bubble.open(map, marker);

        var request = {
            key: gmb_data.api_key,
            placeId: place.place_id
        };

        places_service.getDetails(request, function (place, status) {

            if (status == google.maps.places.PlacesServiceStatus.OK) {

                var info_window_content;

                //place name
                info_window_content = '<p class="place-title">' + place.name + '</p>';

                info_window_content += gmb.add_place_content_to_info_window(place);

                info_window_content = gmb.set_info_window_wrapper(info_window_content); //wraps the content in div and returns

                info_bubble.setContent(info_window_content);

                gmb.initialize_tooltips(); //refresh tooltips

            } else {
                //There was an API error; display it for the user:
                info_bubble.setContent('<p class="place-error">Google API Error: ' + status + '</p>');

            }
        });
    };

    /**
     * Get Places Types Array
     *
     * Loops through checkboxes and returns array of checked values
     *
     * @returns get_places_type
     */
    gmb.get_places_type_array = function () {

        var types_array = [];

        $('.cmb2-id-gmb-places-search-multicheckbox input[type="checkbox"]').each(function () {
            if ($(this).is(':checked')) {
                types_array.push($(this).val());
            }

        });

        return types_array;

    };

    /**
     * Google Places Nearby Search
     */
    gmb.perform_places_search = function () {

        $('.places-loading').fadeIn();
        $('.warning-message').hide().empty();

        var types_array = gmb.get_places_type_array();

        gmb.clear_search_markers();

        //Check if any place types are selected
        if (types_array.length > 0) {

            //perform search request
            var request = {
                key: gmb_data.api_key,
                location: gmb.return_lat_lng(),
                types: types_array,
                radius: parseInt($('#gmb_search_radius').val())
            };
            places_service.nearbySearch(request, gmb.places_search_callback);
        }
        //Display notice that no places are selected
        else {

            gmb.show_warning_message('<strong>Notice: No Place Types are selected</strong><br/> Please select the types of places you would like to display on this map using the Place Type field checkboxes found below.');

        }

    };

    /**
     * Warning Messages
     *
     * Helper function that shows a warning message below the google map
     * @param message
     */
    gmb.show_warning_message = function (message) {
        $('.wpgp-loading').fadeOut(); //fade out all loading items
        $('.warning-message').empty().append('<p>' + message + '</p>').fadeIn();
    };

    /**
     *
     * Returns Maps current Long and Latitude Object
     *
     * Helper Function
     *
     * @returns lat_lng
     */
    gmb.return_lat_lng = function () {
        var map_center = map.getCenter();
        var lat_lng = new google.maps.LatLng(map_center.lat(), map_center.lng());
        return lat_lng;
    };

    /**
     * Map Zoom
     *
     * Sets the map zoom field and variable
     *
     */
    gmb.handle_map_zoom = function (map) {

        var new_zoom = map.getZoom();

        $('#gmb_zoom').val(new_zoom);

        $('#gmb_zoom').on('change', function () {
            map.setZoom(parseInt($(this).val()));
        });

    };

    /**
     * Map Lat Lng
     *
     * Sets the map zoom field and variable
     */
    gmb.lat_lng_field_change = function (map) {
        var pan_point = new google.maps.LatLng($(lat_field).val(), $(lng_field).val());
        map.panTo(pan_point);
    };

    /**
     * Places Search Callback
     *
     * Used to loop through results and call function to create search result markers
     *
     * @param results
     * @param status
     * @param pagination
     */
    gmb.places_search_callback = function (results, status, pagination) {

        var i = 0;
        var result;

        //setup new markers
        if (status == google.maps.places.PlacesServiceStatus.OK) {

            //place new markers
            for (i = 0; result = results[i]; i++) {
                gmb.create_search_result_marker(results[i]);
            }

            //show all pages of results
            //@see: http://stackoverflow.com/questions/11665684/more-than-20-results-by-pagination-with-google-places-api
            if (pagination.hasNextPage) {
                pagination.nextPage();
            } else {
                $('.places-loading').fadeOut();
            }

        }
    };

    /**
     * Create Search Result Marker
     *
     * Used with Places Search to place markers on map
     *
     * @param place
     */
    gmb.create_search_result_marker = function (place) {

        var search_marker = new Marker({
            map: map,
            zIndex: 0,
            optimized: false
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
            gmb.get_place_info_window_content(place, search_marker);
        });

        search_markers.push(search_marker)

    };

    /**
     * Clears Main Markers
     *
     * Used to clear out main location marker to prevent from displaying multiple
     */
    gmb.clear_main_markers = function () {

        //clear markers
        for (var i = 0; i < location_marker_array.length; i++) {
            location_marker_array[i].setMap(null);
        }
        location_marker_array.length = 0;

        //clear infowindows
        for (i = 0; i < info_bubble_array.length; i++) {
            info_bubble_array[i].close();
            google.maps.event.trigger(info_bubble_array[i], 'closeclick');
        }
        info_bubble_array.length = 0;
    };

    /**
     * Toggle Marker Animation
     */
    gmb.toggle_marker_animation = function () {
        gmb.clear_main_markers();
    };

    /**
     * Clears Search Markers
     *
     * Used to clear out main search markers
     */
    gmb.clear_search_markers = function () {

        //remove existing markers
        for (var i = 0; i < search_markers.length; i++) {
            search_markers[i].setMap(null);
        }
        search_markers = [];

    };

    /**
     * Geocode new marker position
     *
     * Perform nearby search request to see if the marker landed on a place
     *
     * @see: http://stackoverflow.com/questions/5688745/google-maps-v3-draggable-marker
     * @param pos
     */
    gmb.geocode_position = function (pos) {

        var request = {
            key: gmb_data.api_key,
            location: pos,
            radius: 10
        };
        places_service.nearbySearch(request, function (results, status) {

            if (status == google.maps.places.PlacesServiceStatus.OK) {

                var info_bubble_content = '';
                info_bubble.close();

                //if more than one result ask the user which one?
                if (results.length > 1) {

                    info_bubble_content = '<div id="infobubble-content"><p>' + gmb_data.i18n.multiple_places + '</p>';

                    for (var i = 0; i < results.length; i++) {
                        info_bubble_content += '<a class="marker-confirm-place"  data-place_id="' + results[i].place_id + '" data-name-address="' + results[i].name + ', ' + results[i].vicinity + '">' + results[i].name + '</a>';
                    }

                    info_bubble_content += '</div>';

                    //setup click event for links
                    google.maps.event.addDomListener(info_bubble, 'domready', function () {
                        $('.marker-confirm-place').on('click', function (e) {
                            e.preventDefault();
                            $('#gmb_geocoder').val($(this).data('name-address'));
                            $('#gmb_place_id').val($(this).data('place_id'));
                            info_bubble.close();
                            gmb.get_info_window_content($(this).data('place_id'));
                            //info_bubble.open( location_marker );
                        });
                    });


                }

                info_bubble.setContent(info_bubble_content);

                info_bubble.open(map, location_marker);


            }

        });

    };

    /**
     * Scroll to Selector
     *
     * Helper function that scroll the user up to the map
     */
    gmb.scroll_to_field = function (selector) {
        //scroll to the map
        $('html, body').animate({
            scrollTop: parseInt($(selector).offset().top)
        }, 600);
    };

    /**
     * Marker Drag End
     *
     * Executes after a user drags the initial marker
     *
     * @param marker
     */
    gmb.marker_drag_end = function (marker) {

        var map_center = marker.getPosition();
        gmb.geocode_position(map_center);
        //update with new map coordinates
        $(lat_field).val(map_center.lat());
        $(lng_field).val(map_center.lng());

        //Map centered on this location
        map.panTo(map_center);
    };

    /**
     * Radius Circle
     *
     * Draws a circle when user focuses on the radius input
     *
     * @see: http://jsfiddle.net/yV6xv/3730/
     * @param map
     * @param radiusVal
     */
    gmb.calc_radius = function (map, radiusVal) {

        //update marker with set marker
        radius_marker = new Marker({
            position: map.getCenter(),
            map: map,
            icon: {
                path: MAP_PIN,
                fillColor: '#0E77E9',
                fillOpacity: 0,
                strokeColor: '',
                strokeWeight: 0,
                scale: 1 / 4
            },
            custom_label: '<i class="map-icon-crosshairs radius-label"></i>',
            zIndex: google.maps.Marker.MAX_ZINDEX + 1,
            optimized: false
        });

        radius_circle = new google.maps.Circle({
            map: map,
            fillColor: '#BBD8E9',
            fillOpacity: 0.3,
            radius: radiusVal,
            strokeColor: '#BBD8E9',
            strokeOpacity: 0.9,
            strokeWeight: 2
        });

        radius_circle.bindTo('center', radius_marker, 'position');

    };

    /**
     * Show/ Hide Map Fields
     *
     * Helper function that handles all the toggle elements within the CPT admin post screen
     *
     */
    gmb.toggle_metabox_fields = function () {

        var show_places = $('.cmb2-id-gmb-show-places input:radio');

        //Places Metabox
        if (show_places.prop('checked')) {
            $('.cmb2-id-gmb-search-radius, .cmb2-id-gmb-places-search-multicheckbox, .cmb2-id-gmb-places-search').toggle();
        }

        //Nothing checked yet so select 'No' by default
        if (!show_places.prop('checked')) {
            $('#gmb_show_places2').prop('checked', true);
        }

        //Places
        $('.cmb2-id-gmb-show-places li input:radio').on('click', function () {

            $(this).find('input:radio').prop('checked', true);

            if ($(this).val() === 'no') {
                gmb.clear_search_markers();
                $('.cmb2-id-gmb-search-radius, .cmb2-id-gmb-places-search-multicheckbox, .cmb2-id-gmb-places-search').hide();
            } else {
                gmb.perform_places_search();
                $('.cmb2-id-gmb-search-radius, .cmb2-id-gmb-places-search-multicheckbox, .cmb2-id-gmb-places-search').show();
            }

        });

    };

    /**
     * Set Zoom Control
     */
    gmb.set_map_zoom_control = function () {

        var zoom_control = $('#gmb_zoom_control').val().toLowerCase();

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
    };

    /**
     * Set Map Type Control
     */
    gmb.set_map_type_control = function () {
        var map_type_control = $('#gmb_map_type_control').val().toLowerCase();
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
    };

    /**
     * Sets Mouse Wheel Scroll
     */
    gmb.set_mouse_wheel_scroll = function () {
        var mouse_wheel_scroll = $('#gmb_wheel_zoom').val();
        if (mouse_wheel_scroll === 'none') {
            map.setOptions({
                scrollwheel: false
            });
        } else {
            map.setOptions({
                scrollwheel: true
            });
        }
    };

    /**
     * Sets Double Click Zoom on Map
     */
    gmb.set_double_click_zoom = function () {
        var double_click_zoom = $('#gmb_double_click').val();
        if (double_click_zoom === 'none') {
            map.setOptions({
                disableDoubleClickZoom: true
            });
        } else {
            map.setOptions({
                disableDoubleClickZoom: false
            });
        }
    };

    /**
     * Sets Draggable Map
     */
    gmb.set_draggable = function () {
        var draggable = $('#gmb_draggable').val();
        if (draggable == 'none') {
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
     * Sets the Pan Control
     */
    gmb.set_pan_control = function () {

        var pan = $('#gmb_pan').val();
        if (pan === 'none') {
            map.setOptions({
                panControl: false
            });
        } else {
            map.setOptions({
                panControl: true
            });
        }
    };

    /**
     * Set Street View
     * @description Sets the Street View Control
     */
    gmb.set_street_view = function () {

        var street_view = $('#gmb_street_view').val();
        if (street_view === 'none') {
            map.setOptions({
                streetViewControl: false
            });
        } else {
            map.setOptions({
                streetViewControl: true
            });
        }
    };

    /**
     * Sets the Map Type
     *
     * @description Changes the Google Map type and resets theme to none
     * @since 1.0
     */
    gmb.set_map_type = function (reset) {
        if (reset === true) {
            $('#gmb_theme').val('none');
            $('#gmb_theme_json').val(' ');
        }

        var map_type = $('#gmb_type').val().toUpperCase();
        map.setOptions({
            mapTypeId: google.maps.MapTypeId[map_type],
            styles: false
        });
    };

    /**
     * JS for Marker Icon Modal
     */
    gmb.set_map_marker_icon = function () {

        var marker_containers = $('.marker-icon-row');
        var marker_modal = $('.marker-icon-modal');
        var marker_modal_save_container = marker_modal.find('.save-marker-icon');
        var marker_modal_save_btn = marker_modal.find('.save-marker-button');

        //Marker Item Click
        $('.marker-item').on('click', function () {

            var marker_data = $(this).data('marker');
            var marker_toggle = $(this).data('toggle');

            $('.marker-item').removeClass('marker-item-selected');
            $(this).addClass('marker-item-selected');
            marker_modal_save_btn.attr('data-marker', marker_data); //Set marker data attribute on save bt

            //Slide up all panels
            marker_containers.hide();

            //Slide down specific div
            $('.' + marker_toggle).show();

        });

        //Old school icon click action
        $('.maps-icon').on('click', function () {
            $('.maps-icon').removeClass('marker-item-selected');
            marker_modal_save_container.slideDown();
            $(this).addClass('marker-item-selected');
            marker_modal_save_btn.data('marker-image', $(this).find('img').attr('src'));
        });

        //SVG/Font icon Click
        $('.icon').on('click', function () {
            $('.icon').removeClass('marker-item-selected');
            $(this).addClass('marker-item-selected');
            $('.save-marker-icon, .marker-label-color-wrap').show(); //slide down save button
            marker_modal_save_btn.attr('data-label', $(this).find('span').attr('class')); //Set marker data attribute on save btn
        });

        //Setup colorpickers
        var color_picker_options = {
            // you can declare a default color here, or in the data-default-color attribute on the input
            // a callback to fire whenever the color changes to a valid color
            change: function (event, ui) {

                var this_color = ui.color.toString();

                //Marker Color
                if ($(this).hasClass('marker-color') === true) {

                    $('.save-marker-button').attr('data-marker-color', this_color);
                    $('.marker-svg polygon, .marker-svg path').attr('fill', this_color);

                } else if ($(this).hasClass('label-color') === true) {

                    $('.save-marker-button').attr('data-label-color', this_color);
                    $('.icon-inner span').css('color', this_color);

                }


            },
            // a callback to fire when the input is emptied or an invalid color
            clear: function () {
            },
            // hide the color picker controls on load
            hide: true,
            // show a group of common colors beneath the square
            // or, supply an array of colors to customize further
            palettes: true
        };

        $('.color-picker').wpColorPicker(color_picker_options);

    };

    /**
     * Set Marker Edit Icons
     *
     * @since 2.0
     * @param marker_index This markers index
     * @returns {string}
     */
    gmb.set_marker_edit_icons = function (marker_index) {
        return '<div class="infowindow-toolbar"><ul id="edit-toolbar">' +
            '<li class="edit-info" data-index="' + marker_index + '" data-tooltip="' + gmb_data.i18n.btn_edit_marker + '"></li>' +
            '<li class="trash-marker" data-index="' + marker_index + '" data-tooltip="' + gmb_data.i18n.btn_delete_marker + '"></li>' +
            '</ul>' +
            '</div>';
    };

    /**
     * Refresh Tooltips
     *
     * Helper function to refresh tooltips when elements added dynamically to DOM
     */
    gmb.initialize_tooltips = function () {
        $('[data-tooltip!=""]').qtip({ // Grab all elements with a non-blank data-tooltip attr.
            content: {
                attr: 'data-tooltip' // Tell qTip2 to look inside this attr for its content
            },
            hide: {
                fixed: true,
                delay: 100,
                event: 'mouseleave click'
            },
            position: {
                my: 'top center',
                at: 'bottom center'
            },
            style: {
                classes: 'qtip-tipsy'
            },
            show: {
                when: {
                    event: 'focus'
                },
                effect: function () {
                    $(this).fadeIn(200);
                }
            }
        });

    };

    gmb.set_map_theme = function () {
    };

    /**
     * Clear Marker Values
     *
     * Sets all marker meta field data to emtpy
     * @since 2.1
     *
     * @param index
     */
    gmb.clear_marker_values = function (index) {

        //Clear marker data
        $('#gmb_markers_group_' + index + '_marker').val('');
        $('#gmb_markers_group_' + index + '_label').val('');
        $('#gmb_markers_group_' + index + '_marker_img').val('');
        $('#gmb_markers_group_' + index + '_marker_included_img').val('');

    }

    /**
     * Detect Google Maps API Authentication Error
     *
     *   Google Authentication Callback in case there was an error
     *
     * @see: https://developers.google.com/maps/documentation/javascript/events#auth-errors
     * @see: https://developers.google.com/maps/documentation/javascript/events#auth-errors
     */

    window.gm_authFailure = function () {

        $('#poststuff').before('<div class="notice gmc-notice-error error"><p>' + gmb_data.i18n.api_key_required + '</p></div>');

    };


}(jQuery, window.MapsBuilderAdmin || ( window.MapsBuilderAdmin = {} )) );


jQuery(window).load(function () {
    MapsBuilderAdmin.init();

    /**
     * Event for after the MapsBuilder admin JS loads
     *
     * @since 2.1.0
     *
     * @type {CustomEvent}
     */
    var gmb_init = new CustomEvent('MapBuilderAdminInit');
    window.dispatchEvent(gmb_init);
});