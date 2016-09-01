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

    // Inherit from OverlayView.
    this.extend(GMB_InfoWindow, google.maps.OverlayView);

    this.container = jQuery('<div class="gmb-infobubble"><div class="gmb-infobubble__header"><div class="gmb-infobubble__close">×</div></div><div class="gmb-infobubble__window"><div class="gmb-infobubble__content"></div></div><div class="gmb-infobubble__arrow"></div></div>');
    this.layer = null;
    this.marker = null;
    this.position = null;

}

// Attach our function to the window
window['GMB_InfoWindow'] = GMB_InfoWindow;

/**
 * Extend.
 *
 * Extension method from InfoBubble.
 *
 * @param obj1
 * @param obj2
 */
GMB_InfoWindow.prototype.extend = function (obj1, obj2) {
    return (function (object) {
        for (var property in object.prototype) {
            this.prototype[property] = object.prototype[property];
        }
        return this;
    }).apply(obj1, [obj2]);
};


/**
 * On add.
 *
 * Called when custom overlay window is added to map.
 */
GMB_InfoWindow.prototype.onAdd = function () {
    this.layer = jQuery(this.getPanes().floatPane);
    this.layer.append(this.container);

    this.container.find('.gmb-infobubble__close').on('click', _.bind(function () {
        // Close info window on click
        this.close();
    }, this));

    // Prevent various events from propagating to the map
    this.addEvents_();

    // With the window added to the map, trigger `domready` event
    google.maps.event.trigger(this, 'domready');
};
GMB_InfoWindow.prototype['onAdd'] = GMB_InfoWindow.prototype.onAdd;

/**
 * Draw GMB_InfoWindow.
 *
 * Redraws the window any time something happens to affect its position.
 */
GMB_InfoWindow.prototype.draw = function () {


};
GMB_InfoWindow.prototype['draw'] = GMB_InfoWindow.prototype.draw;

/**
 * Cleanup function when the window is removed from map
 */
GMB_InfoWindow.prototype.onRemove = function () {
    this.container.remove();
};
GMB_InfoWindow.prototype['onRemove'] = GMB_InfoWindow.prototype.onRemove;

/**
 * Set Content.
 *
 * Set the contents of the overlay container.
 */
GMB_InfoWindow.prototype.setContent = function (html) {
    this.container.find('.gmb-infobubble__content').html(html);
};
GMB_InfoWindow.prototype['setContent'] = GMB_InfoWindow.prototype.setContent;


/**
 * Set Anchor.
 *
 * @param anchor
 */
GMB_InfoWindow.prototype.setAnchor = function (anchor) {
    if (anchor) {
        this.set('anchor', anchor);
        this.bindTo('anchorPoint', anchor);
        this.bindTo('position', anchor);
    } else if (this.get('anchor')) {
        this.set('anchor', null);
        this.unbind('anchorPoint');
        this.unbind('position');
    }
};
GMB_InfoWindow.prototype['setAnchor'] = GMB_InfoWindow.prototype.setAnchor;

/**
 * Set the position of the InfoBubble.
 *
 * @param {google.maps.LatLng} position The position to set.
 */
GMB_InfoWindow.prototype.setPosition = function (position) {
    if (position) {
        this.setAnchor(null);
        this.set('position', position);
    }
};
GMB_InfoWindow.prototype['setPosition'] = GMB_InfoWindow.prototype.setPosition;

/**
 * Add the window to a specific map marker (thus displaying it).
 */
GMB_InfoWindow.prototype.open = function (map, marker) {
    this.marker = marker;
    this.setMap(map);
    GMB_InfoWindow.prototype.setPosition(123);
};
GMB_InfoWindow.prototype['open'] = GMB_InfoWindow.prototype.open;

/**
 * Remove the window from any specific map (thus hiding it).
 */
GMB_InfoWindow.prototype.close = function () {
    this.setMap(null);
};
GMB_InfoWindow.prototype['close'] = GMB_InfoWindow.prototype.close;

/**
 * Centre the marker in the window on click.
 *
 * Sourced from InfoBubble's panToView function.
 */
GMB_InfoWindow.prototype.panToView = function () {
    var projection = this.getProjection();

    if (!projection) {
        // The map projection is not ready yet so do nothing
        return;
    }

    if (!this.container) {
        // No Bubble yet so do nothing
        return;
    }

    var anchorHeight = this.marker.anchorPoint.y;
    var height = this.container.offsetHeight + anchorHeight;
    var map = this.get('map');
    var mapDiv = map.getDiv();
    var mapHeight = mapDiv.offsetHeight;

    var latLng = this.marker.getPosition();
    var centerPos = projection.fromLatLngToContainerPixel(map.getCenter());
    var pos = projection.fromLatLngToContainerPixel(latLng);

    // Find out how much space at the top is free
    var spaceTop = centerPos.y - height;

    // Fine out how much space at the bottom is free
    var spaceBottom = mapHeight - centerPos.y;

    var needsTop = spaceTop < 0;
    var deltaY = 0;

    if (needsTop) {
        spaceTop *= -1;
        deltaY = (spaceTop + spaceBottom) / 2;
    }

    pos.y -= deltaY;
    latLng = projection.fromContainerPixelToLatLng(pos);

    if (map.getCenter() != latLng) {
        map.panTo(latLng);
    }
};
//GMB_InfoWindow.prototype['panToView'] = GMB_InfoWindow.prototype.panToView;

/**
 * Prevent various events from propagating to the map layer
 *
 * This is a slightly modified version of the InfoBubble plugin's approach.
 */
GMB_InfoWindow.prototype.addEvents_ = function () {
    // We want to cancel all the events so they do not go to the map
    var events = ['mousedown', 'mousemove', 'mouseover', 'mouseout', 'mouseup',
        'mousewheel', 'DOMMouseScroll', 'touchstart', 'touchend', 'touchmove',
        'dblclick', 'contextmenu', 'click'];

    // Grab the DOM element represented by the container
    var window = this.container[0];
    this.listeners_ = [];
    for (var i = 0, event; event = events[i]; i++) {
        this.listeners_.push(
            google.maps.event.addDomListener(window, event, function (e) {
                e.cancelBubble = true;
                if (e.stopPropagation) {
                    e.stopPropagation();
                }
            })
        );
    }
};
GMB_InfoWindow.prototype['addEvents_'] = GMB_InfoWindow.prototype.addEvents_;