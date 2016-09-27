/**
 *  Maps Directions
 *
 *  Adds directions functionality to the maps builder
 *  @copyright: http://opensource.org/licenses/gpl-2.0.php GNU Public License
 *  @since: 2.0
 */

var gmb_data;
var gmb_upload_marker;
var trafficLayer = new google.maps.TrafficLayer();
var transitLayer = new google.maps.TransitLayer();
var bicycleLayer = new google.maps.BicyclingLayer();
var placeSearchAutocomplete;

(function ($, gmb) {

    "use strict";

    /**
     * Kick it off on Window Load
     */
    $(window).on('load', function () {

        //Layers
        $('.cmb2-id-gmb-layers input').on('change', function () {
            gmb.set_map_layers($(this));
        });

        //Loop through layers
        $('.cmb2-id-gmb-layers input:checkbox').each(function () {
            gmb.set_map_layers($(this));
        });

        //Places Search
        var places_search_control = $('.cmb2-id-gmb-places-search input');
        places_search_control.on('change', function () {
            gmb.toggle_map_places_search_field($(this));
        });

        gmb.toggle_map_places_search_field(places_search_control);

        //Autocomplete
        gmb.set_map_goto_location_autocomplete();

        //Edit Title
        gmb.set_map_edit_title();

        //Set lng and lat when map dragging
        google.maps.event.addListener(map, 'drag', function () {
            gmb.set_toolbar_lat_lng();
        });
        //Set lng and lat when map dragging
        google.maps.event.addListener(map, 'dragend', function () {
            gmb.set_toolbar_lat_lng();
        });

        //Set lng and lat when map dragging
        google.maps.event.addListener(map, 'zoom_changed', function () {
            gmb.set_toolbar_lat_lng();
        });

        //Initialize Magnific/Modal Functionality Too
        $('body').on('click', '.gmb-magnific-inline', function (e) {

            e.preventDefault();
            var target = '.' + $(this).data('target'); //target element class name
            var autofocus = $(this).data('auto-focus'); //autofocus option

            //Modal in modal?
            //We can't have a magnific inside magnific so CSS3 modal it is
            if ($.magnificPopup.instance.isOpen === true) {

                //Open CSS modal
                $(target).before('<div class="modal-placeholder"></div>') // Save a DOM "bookmark"
                    .removeClass('mfp-hide') //ensure it's visible
                    .appendTo('.magnific-builder #poststuff'); // Move the element to container

                //Check if wrapped properly
                var inner_wrap = $(target).find('.inner-modal-wrap');
                var inner_wrap_container = $(target).find('.inner-modal-container');

                //Not wrapped, wrap it
                if (inner_wrap.length == 0 && inner_wrap_container.length == 0) {

                    $(target).addClass('white-popup').wrapInner('<div class="inner-modal-wrap"><div class="inner-modal-container"><div class="inner-modal clear"></div></div></div>');
                    $('<button type="button" class="gmb-modal-close">&times;</button>').prependTo($(target).find('.inner-modal'));

                }

                //Add close functionality to outside overlay
                $(target).on('click', function (e) {
                    //only on overlay
                    if ($(e.target).hasClass('inner-modal-wrap') || $(e.target).hasClass('inner-modal-container')) {
                        // Move back out of container
                        gmb.close_modal_within_modal(target);
                    }
                });
                //Close button
                $('.gmb-modal-close').on('click', function () {
                    gmb.close_modal_within_modal(target);
                });
                //Autofocus
                if (autofocus == true) {
                    $(target).find('input[type="text"]').focus();
                }
            }
            //Normal modal open
            else {
                $.magnificPopup.open({
                    callbacks: {
                        beforeOpen: function () {
                            $(target).addClass('white-popup');
                        }
                    },
                    items: {
                        src: $(target),
                        type: 'inline'
                    },
                    midClick: true
                });
            }
        });

        //Custom marker modal uploader
        gmb_upload_marker = {

            // Call this from the upload button to initiate the upload frame.
            uploader: function () {

                //@TODO: i18n
                var frame = wp.media({
                    title: 'Set an Custom Marker Icon',
                    multiple: false,
                    library: {type: 'image'},
                    button: {text: 'Set Marker'}
                });

                // Handle results from media manager.
                frame.on('close', function () {
                    var attachments = frame.state().get('selection').toJSON();
                    gmb_upload_marker.render(attachments[0]);
                });

                frame.open();
                return false;
            },

            // Output Image preview
            render: function (attachment) {

                $('.gmb-image-preview').prepend(gmb_upload_marker.imgHTML(attachment));
                $('.gmb-image-preview').html(gmb_upload_marker.imgHTML(attachment));
                $('.gmb-image-preview').show();
                $('.save-marker-icon').slideDown(); //slide down save button
                $('.save-marker-button').data('marker-image', attachment.url); //slide down save button

            },

            // Render html for the image.
            imgHTML: function (attachment) {
                var img_html = '<img src="' + attachment.url + '" ';
                img_html += 'width="' + attachment.width + '" ';
                img_html += 'height="' + attachment.height + '" ';
                if (attachment.alt != '') {
                    img_html += 'alt="' + attachment.alt + '" ';
                }
                img_html += '/>';
                return img_html;
            },
            // User wants to remove the avatar
            removeImage: function (widget_id_string) {
                $("#" + widget_id_string + 'attachment_id').val('');
                $("#" + widget_id_string + 'imageurl').val('');
                $("#" + widget_id_string + 'preview img').remove();
                $("#" + widget_id_string + 'preview a').hide();
            }

        };


    });

    /**
     * Set Map Layers
     *
     * @description Toggles various layers on and off
     * @param layer obj
     */
    gmb.set_map_layers = function (layer) {

        if (layer) {
            var this_val = layer.val();
        } else {
            return false;
        }

        var checked = layer.prop('checked');


        switch (this_val) {

            case 'traffic':
                if (!checked) {
                    trafficLayer.setMap(null);
                } else {
                    trafficLayer.setMap(window.map);
                }
                break;
            case 'transit':
                if (!checked) {
                    transitLayer.setMap(null);
                } else {
                    transitLayer.setMap(window.map);
                }
                break;

            case 'bicycle':

                if (!checked) {
                    bicycleLayer.setMap(null);
                } else {
                    bicycleLayer.setMap(window.map);
                }
                break;

        }

    };

    /**
     * Toggle Places Search Field
     *
     * Adds and removes the places search field from the map preview
     * @param input
     */
    gmb.toggle_map_places_search_field = function (input) {

        //Setup search or Toggle show/hide?
        if (typeof placeSearchAutocomplete === 'undefined' && input.prop('checked') === true) {
            gmb.set_map_places_search_field(); //hasn't been setup yet, so set it up
            $('#places-search').show();
        } else if (input.prop('checked') === true && typeof placeSearchAutocomplete === 'object') {
            $('#places-search').show();
        } else {
            $('#places-search').hide();
        }

    };

    /**
     * Set up Places Search Field.
     *
     * Creates the Google Map custom control with autocomplete enabled.
     */
    gmb.set_map_places_search_field = function () {
        var input = /** @type {HTMLInputElement} */(
            document.getElementById('pac-input'));

        var types = document.getElementById('type-selector');
        map.controls[google.maps.ControlPosition.TOP_CENTER].push(document.getElementById('places-search'));

        placeSearchAutocomplete = new google.maps.places.Autocomplete(input);
        placeSearchAutocomplete.bindTo('bounds', map);

        var infowindow = new google.maps.InfoWindow();
        var marker = new google.maps.Marker({
            map: map,
            anchorPoint: new google.maps.Point(0, -29)
        });

        google.maps.event.addListener(placeSearchAutocomplete, 'place_changed', function () {
            infowindow.close();
            marker.setVisible(false);
            var place = placeSearchAutocomplete.getPlace();
            if (!place.geometry) {
                window.alert("Autocomplete's returned place contains no geometry");
                return;
            }

            // If the place has a geometry, then present it on a map.
            if (place.geometry.viewport) {
                map.fitBounds(place.geometry.viewport);
            } else {
                map.setCenter(place.geometry.location);
                map.setZoom(17);  // Why 17? Because it looks good.
            }
            marker.setIcon(/** @type {google.maps.Icon} */({
                url: place.icon,
                size: new google.maps.Size(71, 71),
                origin: new google.maps.Point(0, 0),
                anchor: new google.maps.Point(17, 34),
                scaledSize: new google.maps.Size(35, 35)
            }));
            marker.setPosition(place.geometry.location);
            marker.setVisible(true);

            var address = '';
            if (place.address_components) {
                address = [
                    (place.address_components[0] && place.address_components[0].short_name || ''),
                    (place.address_components[1] && place.address_components[1].short_name || ''),
                    (place.address_components[2] && place.address_components[2].short_name || '')
                ].join(' ');
            }

            infowindow.setContent('<div><strong>' + place.name + '</strong><br>' + address);
            infowindow.open(map, marker);
        });

        // Sets a listener on a radio button to change the filter type on Places
        // Autocomplete.
        function setupClickListener(id, types) {
            var radioButton = document.getElementById(id);
            google.maps.event.addDomListener(radioButton, 'click', function () {
                placeSearchAutocomplete.setTypes(types);
            });
        }

        setupClickListener('changetype-all', []);
        setupClickListener('changetype-address', ['address']);
        setupClickListener('changetype-establishment', ['establishment']);
        setupClickListener('changetype-geocode', ['geocode']);

        //Tame the enter key to not save the widget while using the autocomplete input
        google.maps.event.addDomListener(input, 'keydown', function (e) {
            if (e.keyCode == 13) {
                e.preventDefault();
            }
        });

    };

    /**
     * Goto Location Autocomplete
     */
    gmb.set_map_goto_location_autocomplete = function () {
        var modal = $('.map-autocomplete-wrap');
        var input = $('#map-location-autocomplete').get(0);
        var location_autocomplete = new google.maps.places.Autocomplete(input);
        location_autocomplete.bindTo('bounds', map);

        google.maps.event.addListener(location_autocomplete, 'place_changed', function () {

            var place = location_autocomplete.getPlace();
            if (!place.geometry) {
                window.alert("Autocomplete's returned place contains no geometry");
                return;
            }

            // If the place has a geometry, then present it on a map.
            if (place.geometry.viewport) {
                map.fitBounds(place.geometry.viewport);
            } else {
                map.setCenter(place.geometry.location);
                map.setZoom(17);  // Why 17? Because it looks good.
            }

            //Close modal
            $(modal).find('.mfp-close').trigger('click');
            gmb.close_modal_within_modal(modal);


        });

        //Tame the enter key to not save the widget while using the autocomplete input
        google.maps.event.addDomListener(input, 'keydown', function (e) {
            if (e.keyCode == 13) {
                e.preventDefault();
            }
        });

    };

    /**
     * Close a Modal within Modal
     *
     * @param modal
     */
    gmb.close_modal_within_modal = function (modal) {
        // Move back out of container
        $(modal)
            .addClass('mfp-hide') //ensure it's hidden
            .appendTo('.modal-placeholder')  // Move it back to it's proper location
            .unwrap(); // Remove the placeholder

    };

    /**
     * Edit Title within Modal.
     */
    gmb.set_map_edit_title = function () {

        //When edit title button is clicked insert title into feax input
        $('.edit-title').on('click', function () {
            $('#modal_title').val($('input#title').val()).focus();
        });

        //when feax title input is changed update default title field
        $('#modal_title').on('blur', function () {
            $('input#title').val($(this).val());
        });

    };

    /**
     * Update Toolbar Lat/Lng.
     */
    gmb.set_toolbar_lat_lng = function () {

        var lat_lng_sidebar_btn = $('.lat-lng-update-btn');
        var lat_lng_toolbar_btn = $('.update-lat-lng');

        var map_center = map.getCenter();
        $('.live-latitude').text(map_center.lat());
        $('.live-longitude').text(map_center.lng());
        lat_lng_toolbar_btn.attr('data-lat', map_center.lat());
        $('.lat-lng-change-message').slideDown();

        lat_lng_toolbar_btn.attr('data-lng', map_center.lng());
        lat_lng_sidebar_btn.attr('data-lat', map_center.lat());
        lat_lng_sidebar_btn.attr('data-lng', map_center.lng());

        lat_lng_sidebar_btn.removeAttr('disabled').addClass('button-primary');
        lat_lng_toolbar_btn.removeAttr('disabled').addClass('button-primary');

    };

    /**
     * Sets the Map Theme.
     *
     * Uses Snazzy Maps JSON arrow to set the colors for the map.
     *
     * @since 1.0
     */
    gmb.set_map_theme = function () {

        var preset_theme = $('#gmb_theme');
        var map_theme_input_val = parseInt(preset_theme.val());
        var map_type_select_field = $('#gmb_type');
        var custom_theme_json_wrap = $('.cmb2-id-gmb-theme-json');
        var custom_theme_json = $('#gmb_theme_json');

        //"Set a Custom Snazzy Map" button click
        $('.custom-snazzy-toggle').on('click', function (e) {
            e.preventDefault();
            preset_theme.val('custom');
            custom_theme_json_wrap.show();
            custom_theme_json.val('').focus();
            gmb.set_custom_snazzy_map();
        });

        //On Snazzy Map textfield value change
        custom_theme_json.on('change', function () {
            gmb.set_custom_snazzy_map();
        });

        //Sanity check to see if none
        if (preset_theme.val() !== 'none') {
            map_type_select_field.val('RoadMap');
        }
        //Snazzy maps select set to none
        if (preset_theme.val() === 'none') {
            custom_theme_json.val(''); //clear value from custom JSON field
        }
        //Custom snazzy map
        else if (preset_theme.val() === 'custom') {
            custom_theme_json_wrap.show();
            gmb.set_custom_snazzy_map();
        }
        //Preconfigured snazzy map
        else {
            custom_theme_json_wrap.hide();
            //AJAX to get JSON data for Snazzy
            $.getJSON(gmb_data.snazzy, function (data) {

                $.each(data, function (index) {

                    if (data[index].id === map_theme_input_val) {
                        map_theme_input_val = eval(data[index].json);
                        $('#gmb_theme_json').val(data[index].json);
                    }

                });

                map.setOptions({
                    mapTypeId: google.maps.MapTypeId.ROADMAP,
                    styles: map_theme_input_val
                });

            });

        }
    };


}(jQuery, window.MapsBuilderAdmin || ( window.MapsBuilderAdmin = {} )) );

