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

function GMB_InfoWindow() {
    // Inherit from OverlayView
    this.extend(GMB_InfoWindow, google.maps.OverlayView);

    this.container = jQuery('<div class="gmb-infobuble"></div>');
    this.layer = null;
    this.marker = null;
    this.position = null;
}

// Attach our function to the window
window['GMB_InfoWindow'] = GMB_InfoWindow;

// Extension method from InfoBubble
InfoBubble.prototype.extend = function(obj1, obj2) {
    return (function(object) {
        for (var property in object.prototype) {
            this.prototype[property] = object.prototype[property];
        }
        return this;
    }).apply(obj1, [obj2]);
};

/**
 * Called when window is added to map
 */
GMB_InfoWindow.prototype.onAdd = function() {
    this.layer = jQuery(this.getPanes().floatPlane);
    this.layer.append(this.container);
    this.container.find('.map-info-close').on('click', _.bind(function() {
        // Close info window on click
        this.close();
    }, this));
};
GMB_InfoWindow.prototype['onAdd'] = GMB_InfoWindow.prototype.onAdd;

/**
 * Redraws the window any time something happens to affect its position
 */
GMB_InfoWindow.prototype.draw = function() {
    var markerIcon = this.marker.getIcon(),
        cHeight = this.container.outerHeight() + markerIcon.scaledSize.height + 10,
        cWidth = this.container.width() / 2 + markerIcon.scaledSize.width / 2;

    this.position = this.getProjection().fromLatLngToDivPixel(this.marker.getPosition());

    this.container.css({
        'top': this.position.y - cHeight,
        'left': this.position.x - cWidth
    });
};
GMB_InfoWindow.prototype['draw'] = GMB_InfoWindow.prototype.draw;

/**
 * Cleanup function when the window is removed from map
 */
GMB_InfoWindow.prototype.onRemove = function() {
    this.container.remove();
};
GMB_InfoWindow.prototype['onRemove'] = GMB_InfoWindow.prototype.onRemove;

/**
 * Set the contents of the overlay container
 */
GMB_InfoWindow.prototype.setContent = function( html ) {
    this.container.html(html);
};
GMB_InfoWindow.prototype['setContent'] = GMB_InfoWindow.prototype.setContent;

/**
 * Add the window to a specific map marker (thus displaying it)
 */
GMB_InfoWindow.prototype.open = function( map, marker ) {
    this.marker = marker;
    this.setMap(map);
};
GMB_InfoWindow.prototype['open'] = GMB_InfoWindow.prototype.open;

/**
 * Remove the window from any specific map (thus hiding it)
 */
GMB_InfoWindow.prototype.close = function() {
    this.setMap(null);
};
GMB_InfoWindow.prototype['close'] = GMB_InfoWindow.prototype.close;
