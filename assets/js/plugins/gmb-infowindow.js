/**
 * GMB Custom OverlayView Implementation
 *
 * Similar to an InfoWindow/InfoBubble/InfoBox, this is our version of an OverlayView
 * to provide information attached to a marker.
 *
 * Resources used in developing this (to whose authors we are very grateful):
 *     - http://artandlogic.com/2014/02/custom-google-maps-info-windows/
 *     - https://codepen.io/emgerold/pen/kjivC
 *     - https://developers.google.com/maps/documentation/javascript/customoverlays
 */

google.maps.GMB_InfoWindow = (function() {
   var InfoWindow = function() {
       this.container = $('<div class="gmb-infobuble"></div>');
       this.layer = null;
       this.marker = null;
       this.position = null;
   };

    /**
     * Inherit from OverlayView
     */
    InfoWindow.prototype = new google.maps.OverlayView();

    /**
     * Called when window is added to map
     */
    InfoWindow.prototype.onAdd = function() {
        this.layer = $(this.getPanes().floatPlane);
        this.layer.append(this.container);
        this.container.find('.map-info-close').on('click', _.bind(function() {
            // Close info window on click
            this.close();
        }, this));
    };

    /**
     * Redraws the window any time something happens to affect its position
     */
    InfoWindow.prototype.draw = function() {
        var markerIcon = this.marker.getIcon(),
            cHeight = this.container.outerHeight() + markerIcon.scaledSize.height + 10,
            cWidth = this.container.width() / 2 + markerIcon.scaledSize.width / 2;

        this.position = this.getProjection().fromLatLngToDivPixel(this.marker.getPosition());

        this.container.css({
            'top': this.position.y - cHeight,
            'left': this.position.x - cWidth
        });
    };

    /**
     * Cleanup function when the window is removed from map
     */
    InfoWindow.prototype.onRemove = function() {
        this.container.remove();
    };

    /**
     * Set the contents of the overlay container
     */
    InfoWindow.prototype.setContent = function( html ) {
        this.container.html(html);
    };

    /**
     * Add the window to a specific map marker (thus displaying it)
     */
    InfoWindow.prototype.open = function( map, marker ) {
        this.marker = marker;
        this.setMap(map);
    };

    /**
     * Remove the window from any specific map (thus hiding it)
     */
    InfoWindow.prototype.close = function() {
        this.setMap(null);
    };
});