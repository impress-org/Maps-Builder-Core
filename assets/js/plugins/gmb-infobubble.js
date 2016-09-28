/**
 * Custom InfoBubble for Google Maps Builder
 *
 * Forked from: https://github.com/googlemaps/js-info-bubble/
 */


/**
 * A CSS3 GMB_InfoBubble v0.8
 * @param {Object.<string, *>=} opt_options Optional properties to set.
 * @extends {google.maps.OverlayView}
 * @constructor
 */
function GMB_InfoBubble(opt_options) {
    this.extend(GMB_InfoBubble, google.maps.OverlayView);
    this.tabs_ = [];
    this.activeTab_ = null;
    this.baseZIndex_ = 100;
    this.isOpen_ = false;

    var options = opt_options || {};

    if (options['backgroundColor'] == undefined) {
        options['backgroundColor'] = this.BACKGROUND_COLOR_;
    }

    if (options['borderColor'] == undefined) {
        options['borderColor'] = this.BORDER_COLOR_;
    }

    if (options['borderRadius'] == undefined) {
        options['borderRadius'] = this.BORDER_RADIUS_;
    }

    if (options['borderWidth'] == undefined) {
        options['borderWidth'] = this.BORDER_WIDTH_;
    }

    if (options['padding'] == undefined) {
        options['padding'] = this.PADDING_;
    }

    if (options['arrowPosition'] == undefined) {
        options['arrowPosition'] = this.ARROW_POSITION_;
    }

    if (options['disableAutoPan'] == undefined) {
        options['disableAutoPan'] = false;
    }

    if (options['disableAnimation'] == undefined) {
        options['disableAnimation'] = false;
    }

    if (options['minWidth'] == undefined) {
        options['minWidth'] = this.MIN_WIDTH_;
    }

    if (options['shadowStyle'] == undefined) {
        options['shadowStyle'] = this.SHADOW_STYLE_;
    }

    if (options['arrowSize'] == undefined) {
        options['arrowSize'] = this.ARROW_SIZE_;
    }

    if (options['arrowStyle'] == undefined) {
        options['arrowStyle'] = this.ARROW_STYLE_;
    }

    if (options['closeSrc'] == undefined) {
        options['closeSrc'] = this.CLOSE_SRC_;
    }

    this.buildDom_();
    this.setValues(options);
}
window['GMB_InfoBubble'] = GMB_InfoBubble;


/**
 * Default arrow size
 * @const
 * @private
 */
GMB_InfoBubble.prototype.ARROW_SIZE_ = 15;


/**
 * Default arrow style
 * @const
 * @private
 */
GMB_InfoBubble.prototype.ARROW_STYLE_ = 0;


/**
 * Default shadow style
 * @const
 * @private
 */
GMB_InfoBubble.prototype.SHADOW_STYLE_ = 1;


/**
 * Default min width
 * @const
 * @private
 */
GMB_InfoBubble.prototype.MIN_WIDTH_ = 50;


/**
 * Default arrow position
 * @const
 * @private
 */
GMB_InfoBubble.prototype.ARROW_POSITION_ = 50;


/**
 * Default padding
 * @const
 * @private
 */
GMB_InfoBubble.prototype.PADDING_ = 10;


/**
 * Default border width
 * @const
 * @private
 */
GMB_InfoBubble.prototype.BORDER_WIDTH_ = 1;


/**
 * Default border color
 * @const
 * @private
 */
GMB_InfoBubble.prototype.BORDER_COLOR_ = '#ccc';


/**
 * Default border radius
 * @const
 * @private
 */
GMB_InfoBubble.prototype.BORDER_RADIUS_ = 10;


/**
 * Default background color
 * @const
 * @private
 */
GMB_InfoBubble.prototype.BACKGROUND_COLOR_ = '#fff';

/**
 * Default close image source
 * @const
 * @private
 */
GMB_InfoBubble.prototype.CLOSE_SRC_ = 'https://maps.gstatic.com/intl/en_us/mapfiles/iw_close.gif';

/**
 * Extends a objects prototype by anothers.
 *
 * @param {Object} obj1 The object to be extended.
 * @param {Object} obj2 The object to extend with.
 * @return {Object} The new extended object.
 * @ignore
 */
GMB_InfoBubble.prototype.extend = function (obj1, obj2) {
    return (function (object) {
        for (var property in object.prototype) {
            this.prototype[property] = object.prototype[property];
        }
        return this;
    }).apply(obj1, [obj2]);
};


/**
 * Builds the GMB_InfoBubble dom
 * @private
 */
GMB_InfoBubble.prototype.buildDom_ = function () {
    var bubble = this.bubble_ = document.createElement('DIV');
    bubble.style['position'] = 'absolute';
    bubble.style['zIndex'] = this.baseZIndex_;

    var tabsContainer = this.tabsContainer_ = document.createElement('DIV');
    tabsContainer.style['position'] = 'relative';

    // Close button
    var close = this.close_ = document.createElement('IMG');
    close.style['position'] = 'absolute';
    close.style['border'] = 0;
    close.style['zIndex'] = this.baseZIndex_ + 1;
    close.style['cursor'] = 'pointer';
    close.src = this.get('closeSrc');

    var that = this;
    google.maps.event.addDomListener(close, 'click', function () {
        that.close();
        google.maps.event.trigger(that, 'closeclick');
    });

    // Content area
    var contentContainer = this.contentContainer_ = document.createElement('DIV');
    contentContainer.className = 'gmb-infobubble-container';
    contentContainer.style['overflowX'] = 'auto';
    contentContainer.style['overflowY'] = 'auto';
    contentContainer.style['cursor'] = 'default';
    contentContainer.style['clear'] = 'both';
    contentContainer.style['position'] = 'relative';

    var content = this.content_ = document.createElement('DIV');

    contentContainer.appendChild(content);

    // Arrow
    var arrow = this.arrow_ = document.createElement('DIV');
    arrow.style['position'] = 'relative';

    var arrowOuter = this.arrowOuter_ = document.createElement('DIV');
    var arrowInner = this.arrowInner_ = document.createElement('DIV');

    var arrowSize = this.getArrowSize_();

    arrowOuter.style['position'] = arrowInner.style['position'] = 'absolute';
    arrowOuter.style['left'] = arrowInner.style['left'] = '50%';
    arrowOuter.style['height'] = arrowInner.style['height'] = '0';
    arrowOuter.style['width'] = arrowInner.style['width'] = '0';
    arrowOuter.style['marginLeft'] = this.px(-arrowSize);
    arrowOuter.style['borderWidth'] = this.px(arrowSize);
    arrowOuter.style['borderBottomWidth'] = 0;

    // Shadow
    var bubbleShadow = this.bubbleShadow_ = document.createElement('DIV');
    bubbleShadow.style['position'] = 'absolute';

    // Hide the GMB_InfoBubble by default
    bubble.style['display'] = bubbleShadow.style['display'] = 'none';

    bubble.appendChild(this.tabsContainer_);
    bubble.appendChild(close);
    bubble.appendChild(contentContainer);
    arrow.appendChild(arrowOuter);
    arrow.appendChild(arrowInner);
    bubble.appendChild(arrow);

    var stylesheet = document.createElement('style');
    stylesheet.setAttribute('type', 'text/css');

    /**
     * The animation for the GMB_InfoBubble
     * @type {string}
     */
    this.animationName_ = '_ibani_' + Math.round(Math.random() * 10000);

    var css = '.' + this.animationName_ + '{-webkit-animation-name:' +
        this.animationName_ + ';-webkit-animation-duration:0.5s;' +
        '-webkit-animation-iteration-count:1;}' +
        '@-webkit-keyframes ' + this.animationName_ + ' {from {' +
        '-webkit-transform: scale(0)}50% {-webkit-transform: scale(1.2)}90% ' +
        '{-webkit-transform: scale(0.95)}to {-webkit-transform: scale(1)}}';

    stylesheet.textContent = css;
    document.getElementsByTagName('head')[0].appendChild(stylesheet);
};


/**
 * Sets the background class name
 *
 * @param {string} className The class name to set.
 */
GMB_InfoBubble.prototype.setBackgroundClassName = function (className) {
    this.set('backgroundClassName', className);
};
GMB_InfoBubble.prototype['setBackgroundClassName'] = GMB_InfoBubble.prototype.setBackgroundClassName;


/**
 * changed MVC callback
 */
GMB_InfoBubble.prototype.backgroundClassName_changed = function () {
    this.content_.className = this.get('backgroundClassName');
};
GMB_InfoBubble.prototype['backgroundClassName_changed'] = GMB_InfoBubble.prototype.backgroundClassName_changed;


/**
 * Sets the class of the tab
 *
 * @param {string} className the class name to set.
 */
GMB_InfoBubble.prototype.setTabClassName = function (className) {
    this.set('tabClassName', className);
};
GMB_InfoBubble.prototype['setTabClassName'] = GMB_InfoBubble.prototype.setTabClassName;


/**
 * tabClassName changed MVC callback
 */
GMB_InfoBubble.prototype.tabClassName_changed = function () {
    this.updateTabStyles_();
};
GMB_InfoBubble.prototype['tabClassName_changed'] = GMB_InfoBubble.prototype.tabClassName_changed;


/**
 * Gets the style of the arrow
 *
 * @private
 * @return {number} The style of the arrow.
 */
GMB_InfoBubble.prototype.getArrowStyle_ = function () {
    return parseInt(this.get('arrowStyle'), 10) || 0;
};


/**
 * Sets the style of the arrow
 *
 * @param {number} style The style of the arrow.
 */
GMB_InfoBubble.prototype.setArrowStyle = function (style) {
    this.set('arrowStyle', style);
};
GMB_InfoBubble.prototype['setArrowStyle'] = GMB_InfoBubble.prototype.setArrowStyle;


/**
 * Arrow style changed MVC callback
 */
GMB_InfoBubble.prototype.arrowStyle_changed = function () {
    this.arrowSize_changed();
};
GMB_InfoBubble.prototype['arrowStyle_changed'] = GMB_InfoBubble.prototype.arrowStyle_changed;


/**
 * Gets the size of the arrow
 *
 * @private
 * @return {number} The size of the arrow.
 */
GMB_InfoBubble.prototype.getArrowSize_ = function () {
    return parseInt(this.get('arrowSize'), 10) || 0;
};


/**
 * Sets the size of the arrow
 *
 * @param {number} size The size of the arrow.
 */
GMB_InfoBubble.prototype.setArrowSize = function (size) {
    this.set('arrowSize', size);
};
GMB_InfoBubble.prototype['setArrowSize'] = GMB_InfoBubble.prototype.setArrowSize;


/**
 * Arrow size changed MVC callback
 */
GMB_InfoBubble.prototype.arrowSize_changed = function () {
    this.borderWidth_changed();
};
GMB_InfoBubble.prototype['arrowSize_changed'] = GMB_InfoBubble.prototype.arrowSize_changed;


/**
 * Set the position of the GMB_InfoBubble arrow
 *
 * @param {number} pos The position to set.
 */
GMB_InfoBubble.prototype.setArrowPosition = function (pos) {
    this.set('arrowPosition', pos);
};
GMB_InfoBubble.prototype['setArrowPosition'] = GMB_InfoBubble.prototype.setArrowPosition;


/**
 * Get the position of the GMB_InfoBubble arrow
 *
 * @private
 * @return {number} The position..
 */
GMB_InfoBubble.prototype.getArrowPosition_ = function () {
    return parseInt(this.get('arrowPosition'), 10) || 0;
};


/**
 * arrowPosition changed MVC callback
 */
GMB_InfoBubble.prototype.arrowPosition_changed = function () {
    var pos = this.getArrowPosition_();
    this.arrowOuter_.style['left'] = this.arrowInner_.style['left'] = pos + '%';

    this.redraw_();
};
GMB_InfoBubble.prototype['arrowPosition_changed'] = GMB_InfoBubble.prototype.arrowPosition_changed;


/**
 * Set the zIndex of the GMB_InfoBubble
 *
 * @param {number} zIndex The zIndex to set.
 */
GMB_InfoBubble.prototype.setZIndex = function (zIndex) {
    this.set('zIndex', zIndex);
};
GMB_InfoBubble.prototype['setZIndex'] = GMB_InfoBubble.prototype.setZIndex;


/**
 * Get the zIndex of the GMB_InfoBubble
 *
 * @return {number} The zIndex to set.
 */
GMB_InfoBubble.prototype.getZIndex = function () {
    return parseInt(this.get('zIndex'), 10) || this.baseZIndex_;
};


/**
 * zIndex changed MVC callback
 */
GMB_InfoBubble.prototype.zIndex_changed = function () {
    var zIndex = this.getZIndex();

    this.bubble_.style['zIndex'] = this.baseZIndex_ = zIndex;
    this.close_.style['zIndex'] = zIndex + 1;
};
GMB_InfoBubble.prototype['zIndex_changed'] = GMB_InfoBubble.prototype.zIndex_changed;


/**
 * Set the style of the shadow
 *
 * @param {number} shadowStyle The style of the shadow.
 */
GMB_InfoBubble.prototype.setShadowStyle = function (shadowStyle) {
    this.set('shadowStyle', shadowStyle);
};
GMB_InfoBubble.prototype['setShadowStyle'] = GMB_InfoBubble.prototype.setShadowStyle;


/**
 * Get the style of the shadow
 *
 * @private
 * @return {number} The style of the shadow.
 */
GMB_InfoBubble.prototype.getShadowStyle_ = function () {
    return parseInt(this.get('shadowStyle'), 10) || 0;
};


/**
 * shadowStyle changed MVC callback
 */
GMB_InfoBubble.prototype.shadowStyle_changed = function () {
    var shadowStyle = this.getShadowStyle_();

    var display = '';
    var shadow = '';
    var backgroundColor = '';
    switch (shadowStyle) {
        case 0:
            display = 'none';
            break;
        case 1:
            shadow = '0 0 3px rgba(0,0,0,0.2)';
            backgroundColor = 'transparent';
            break;
        case 2:
            shadow = '0 0 3px rgba(0,0,0,0.2)';
            backgroundColor = 'transparent';
            break;
    }
    this.bubbleShadow_.style['boxShadow'] =
        this.bubbleShadow_.style['webkitBoxShadow'] =
            this.bubbleShadow_.style['MozBoxShadow'] = shadow;
    this.bubbleShadow_.style['backgroundColor'] = backgroundColor;
    if (this.isOpen_) {
        this.bubbleShadow_.style['display'] = display;
        this.draw();
    }
};
GMB_InfoBubble.prototype['shadowStyle_changed'] = GMB_InfoBubble.prototype.shadowStyle_changed;


/**
 * Show the close button
 */
GMB_InfoBubble.prototype.showCloseButton = function () {
    this.set('hideCloseButton', false);
};
GMB_InfoBubble.prototype['showCloseButton'] = GMB_InfoBubble.prototype.showCloseButton;


/**
 * Hide the close button
 */
GMB_InfoBubble.prototype.hideCloseButton = function () {
    this.set('hideCloseButton', true);
};
GMB_InfoBubble.prototype['hideCloseButton'] = GMB_InfoBubble.prototype.hideCloseButton;


/**
 * hideCloseButton changed MVC callback
 */
GMB_InfoBubble.prototype.hideCloseButton_changed = function () {
    this.close_.style['display'] = this.get('hideCloseButton') ? 'none' : '';
};
GMB_InfoBubble.prototype['hideCloseButton_changed'] = GMB_InfoBubble.prototype.hideCloseButton_changed;


/**
 * Set the background color
 *
 * @param {string} color The color to set.
 */
GMB_InfoBubble.prototype.setBackgroundColor = function (color) {
    if (color) {
        this.set('backgroundColor', color);
    }
};
GMB_InfoBubble.prototype['setBackgroundColor'] = GMB_InfoBubble.prototype.setBackgroundColor;


/**
 * backgroundColor changed MVC callback
 */
GMB_InfoBubble.prototype.backgroundColor_changed = function () {
    var backgroundColor = this.get('backgroundColor');
    this.contentContainer_.style['backgroundColor'] = backgroundColor;

    this.arrowInner_.style['borderColor'] = backgroundColor +
        ' transparent transparent';
    this.updateTabStyles_();
};
GMB_InfoBubble.prototype['backgroundColor_changed'] = GMB_InfoBubble.prototype.backgroundColor_changed;


/**
 * Set the border color
 *
 * @param {string} color The border color.
 */
GMB_InfoBubble.prototype.setBorderColor = function (color) {
    if (color) {
        this.set('borderColor', color);
    }
};
GMB_InfoBubble.prototype['setBorderColor'] = GMB_InfoBubble.prototype.setBorderColor;


/**
 * borderColor changed MVC callback
 */
GMB_InfoBubble.prototype.borderColor_changed = function () {
    var borderColor = this.get('borderColor');

    var contentContainer = this.contentContainer_;
    var arrowOuter = this.arrowOuter_;
    contentContainer.style['borderColor'] = borderColor;

    arrowOuter.style['borderColor'] = borderColor +
        ' transparent transparent';

    contentContainer.style['borderStyle'] =
        arrowOuter.style['borderStyle'] =
            this.arrowInner_.style['borderStyle'] = 'solid';

    this.updateTabStyles_();
};
GMB_InfoBubble.prototype['borderColor_changed'] = GMB_InfoBubble.prototype.borderColor_changed;


/**
 * Set the radius of the border
 *
 * @param {number} radius The radius of the border.
 */
GMB_InfoBubble.prototype.setBorderRadius = function (radius) {
    this.set('borderRadius', radius);
};
GMB_InfoBubble.prototype['setBorderRadius'] = GMB_InfoBubble.prototype.setBorderRadius;


/**
 * Get the radius of the border
 *
 * @private
 * @return {number} The radius of the border.
 */
GMB_InfoBubble.prototype.getBorderRadius_ = function () {
    return parseInt(this.get('borderRadius'), 10) || 0;
};


/**
 * borderRadius changed MVC callback
 */
GMB_InfoBubble.prototype.borderRadius_changed = function () {
    var borderRadius = this.getBorderRadius_();
    var borderWidth = this.getBorderWidth_();

    this.contentContainer_.style['borderRadius'] =
        this.contentContainer_.style['MozBorderRadius'] =
            this.contentContainer_.style['webkitBorderRadius'] =
                this.bubbleShadow_.style['borderRadius'] =
                    this.bubbleShadow_.style['MozBorderRadius'] =
                        this.bubbleShadow_.style['webkitBorderRadius'] = this.px(borderRadius);

    this.tabsContainer_.style['paddingLeft'] =
        this.tabsContainer_.style['paddingRight'] =
            this.px(borderRadius + borderWidth);

    this.redraw_();
};
GMB_InfoBubble.prototype['borderRadius_changed'] = GMB_InfoBubble.prototype.borderRadius_changed;


/**
 * Get the width of the border
 *
 * @private
 * @return {number} width The width of the border.
 */
GMB_InfoBubble.prototype.getBorderWidth_ = function () {
    return parseInt(this.get('borderWidth'), 10) || 0;
};


/**
 * Set the width of the border
 *
 * @param {number} width The width of the border.
 */
GMB_InfoBubble.prototype.setBorderWidth = function (width) {
    this.set('borderWidth', width);
};
GMB_InfoBubble.prototype['setBorderWidth'] = GMB_InfoBubble.prototype.setBorderWidth;


/**
 * borderWidth change MVC callback
 */
GMB_InfoBubble.prototype.borderWidth_changed = function () {
    var borderWidth = this.getBorderWidth_();

    this.contentContainer_.style['borderWidth'] = this.px(borderWidth);
    this.tabsContainer_.style['top'] = this.px(borderWidth);

    this.updateArrowStyle_();
    this.updateTabStyles_();
    this.borderRadius_changed();
    this.redraw_();
};
GMB_InfoBubble.prototype['borderWidth_changed'] = GMB_InfoBubble.prototype.borderWidth_changed;


/**
 * Update the arrow style
 * @private
 */
GMB_InfoBubble.prototype.updateArrowStyle_ = function () {
    var borderWidth = this.getBorderWidth_();
    var arrowSize = this.getArrowSize_();
    var arrowStyle = this.getArrowStyle_();
    var arrowOuterSizePx = this.px(arrowSize);
    var arrowInnerSizePx = this.px(Math.max(0, arrowSize - borderWidth));

    var outer = this.arrowOuter_;
    var inner = this.arrowInner_;

    this.arrow_.style['marginTop'] = this.px(-borderWidth);
    outer.style['borderTopWidth'] = arrowOuterSizePx;
    inner.style['borderTopWidth'] = arrowInnerSizePx;

    // Full arrow or arrow pointing to the left
    if (arrowStyle == 0 || arrowStyle == 1) {
        outer.style['borderLeftWidth'] = arrowOuterSizePx;
        inner.style['borderLeftWidth'] = arrowInnerSizePx;
    } else {
        outer.style['borderLeftWidth'] = inner.style['borderLeftWidth'] = 0;
    }

    // Full arrow or arrow pointing to the right
    if (arrowStyle == 0 || arrowStyle == 2) {
        outer.style['borderRightWidth'] = arrowOuterSizePx;
        inner.style['borderRightWidth'] = arrowInnerSizePx;
    } else {
        outer.style['borderRightWidth'] = inner.style['borderRightWidth'] = 0;
    }

    if (arrowStyle < 2) {
        outer.style['marginLeft'] = this.px(-(arrowSize));
        inner.style['marginLeft'] = this.px(-(arrowSize - borderWidth));
    } else {
        outer.style['marginLeft'] = inner.style['marginLeft'] = 0;
    }

    // If there is no border then don't show thw outer arrow
    if (borderWidth == 0) {
        outer.style['display'] = 'none';
    } else {
        outer.style['display'] = '';
    }
};


/**
 * Set the padding of the GMB_InfoBubble
 *
 * @param {number} padding The padding to apply.
 */
GMB_InfoBubble.prototype.setPadding = function (padding) {
    this.set('padding', padding);
};
GMB_InfoBubble.prototype['setPadding'] = GMB_InfoBubble.prototype.setPadding;


/**
 * Set the close image url
 *
 * @param {string} src The url of the image used as a close icon
 */
GMB_InfoBubble.prototype.setCloseSrc = function (src) {
    if (src && this.close_) {
        this.close_.src = src;
    }
};
GMB_InfoBubble.prototype['setCloseSrc'] = GMB_InfoBubble.prototype.setCloseSrc;


/**
 * Set the padding of the GMB_InfoBubble
 *
 * @private
 * @return {number} padding The padding to apply.
 */
GMB_InfoBubble.prototype.getPadding_ = function () {
    return parseInt(this.get('padding'), 10) || 0;
};


/**
 * padding changed MVC callback
 */
GMB_InfoBubble.prototype.padding_changed = function () {
    var padding = this.getPadding_();
    this.contentContainer_.style['padding'] = this.px(padding);
    this.updateTabStyles_();

    this.redraw_();
};
GMB_InfoBubble.prototype['padding_changed'] = GMB_InfoBubble.prototype.padding_changed;


/**
 * Add px extention to the number
 *
 * @param {number} num The number to wrap.
 * @return {string|number} A wrapped number.
 */
GMB_InfoBubble.prototype.px = function (num) {
    if (num) {
        // 0 doesn't need to be wrapped
        return num + 'px';
    }
    return num;
};


/**
 * Add events to stop propagation
 * @private
 */
GMB_InfoBubble.prototype.addEvents_ = function () {
    // We want to cancel all the events so they do not go to the map
    var events = ['mousedown', 'mousemove', 'mouseover', 'mouseout', 'mouseup',
        'mousewheel', 'DOMMouseScroll', 'touchstart', 'touchend', 'touchmove',
        'dblclick', 'contextmenu', 'click'];

    var bubble = this.bubble_;
    this.listeners_ = [];
    for (var i = 0, event; event = events[i]; i++) {
        this.listeners_.push(
            google.maps.event.addDomListener(bubble, event, function (e) {
                e.cancelBubble = true;
                if (e.stopPropagation) {
                    e.stopPropagation();
                }
            })
        );
    }
};


/**
 * On Adding the GMB_InfoBubble to a map
 * Implementing the OverlayView interface
 */
GMB_InfoBubble.prototype.onAdd = function () {
    if (!this.bubble_) {
        this.buildDom_();
    }

    this.addEvents_();

    var panes = this.getPanes();
    if (panes) {
        panes.floatPane.appendChild(this.bubble_);
        panes.floatShadow.appendChild(this.bubbleShadow_);
    }

    /* once the GMB_InfoBubble has been added to the DOM, fire 'domready' event */
    google.maps.event.trigger(this, 'domready');
};
GMB_InfoBubble.prototype['onAdd'] = GMB_InfoBubble.prototype.onAdd;


/**
 * Draw the GMB_InfoBubble
 * Implementing the OverlayView interface
 */
GMB_InfoBubble.prototype.draw = function () {
    var projection = this.getProjection();

    if (!projection) {
        // The map projection is not ready yet so do nothing
        return;
    }

    var latLng = /** @type {google.maps.LatLng} */ (this.get('position'));

    if (!latLng) {
        this.close();
        return;
    }

    var tabHeight = 0;

    if (this.activeTab_) {
        tabHeight = this.activeTab_.offsetHeight;
    }

    var anchorHeight = this.getAnchorHeight_();
    var arrowSize = this.getArrowSize_();
    var arrowPosition = this.getArrowPosition_();

    arrowPosition = arrowPosition / 100;

    var pos = projection.fromLatLngToDivPixel(latLng);
    var width = this.contentContainer_.offsetWidth;
    var height = this.bubble_.offsetHeight;

    if (!width) {
        return;
    }

    // Adjust for the height of the info bubble
    var top = pos.y - (height + arrowSize);

    if (anchorHeight) {
        // If there is an anchor then include the height
        top -= anchorHeight;
    }

    var left = pos.x - (width * arrowPosition);

    this.bubble_.style['top'] = this.px(top);
    this.bubble_.style['left'] = this.px(left);

    var shadowStyle = parseInt(this.get('shadowStyle'), 10);

    switch (shadowStyle) {
        case 1:
            // Shadow is behind
            this.bubbleShadow_.style['top'] = this.px(top + tabHeight - 1);
            this.bubbleShadow_.style['left'] = this.px(left);
            this.bubbleShadow_.style['width'] = this.px(width);
            this.bubbleShadow_.style['height'] =
                this.px(this.contentContainer_.offsetHeight - arrowSize);
            break;
        case 2:
            // Shadow is below
            width = width * 0.8;
            if (anchorHeight) {
                this.bubbleShadow_.style['top'] = this.px(pos.y);
            } else {
                this.bubbleShadow_.style['top'] = this.px(pos.y + arrowSize);
            }
            this.bubbleShadow_.style['left'] = this.px(pos.x - width * arrowPosition);

            this.bubbleShadow_.style['width'] = this.px(width);
            this.bubbleShadow_.style['height'] = this.px(2);
            break;
    }
};
GMB_InfoBubble.prototype['draw'] = GMB_InfoBubble.prototype.draw;


/**
 * Removing the GMB_InfoBubble from a map
 */
GMB_InfoBubble.prototype.onRemove = function () {
    if (this.bubble_ && this.bubble_.parentNode) {
        this.bubble_.parentNode.removeChild(this.bubble_);
    }
    if (this.bubbleShadow_ && this.bubbleShadow_.parentNode) {
        this.bubbleShadow_.parentNode.removeChild(this.bubbleShadow_);
    }

    for (var i = 0, listener; listener = this.listeners_[i]; i++) {
        google.maps.event.removeListener(listener);
    }
};
GMB_InfoBubble.prototype['onRemove'] = GMB_InfoBubble.prototype.onRemove;


/**
 * Is the GMB_InfoBubble open
 *
 * @return {boolean} If the GMB_InfoBubble is open.
 */
GMB_InfoBubble.prototype.isOpen = function () {
    return this.isOpen_;
};
GMB_InfoBubble.prototype['isOpen'] = GMB_InfoBubble.prototype.isOpen;


/**
 * Close the GMB_InfoBubble
 */
GMB_InfoBubble.prototype.close = function () {
    if (this.bubble_) {
        this.bubble_.style['display'] = 'none';
        // Remove the animation so we next time it opens it will animate again
        this.bubble_.className =
            this.bubble_.className.replace(this.animationName_, '');
    }

    if (this.bubbleShadow_) {
        this.bubbleShadow_.style['display'] = 'none';
        this.bubbleShadow_.className =
            this.bubbleShadow_.className.replace(this.animationName_, '');
    }
    this.isOpen_ = false;
};
GMB_InfoBubble.prototype['close'] = GMB_InfoBubble.prototype.close;


/**
 * Open the GMB_InfoBubble (asynchronous).
 *
 * @param {google.maps.Map=} opt_map Optional map to open on.
 * @param {google.maps.MVCObject=} opt_anchor Optional anchor to position at.
 * @param map_data Data containing all the Maps Builder options.
 */
GMB_InfoBubble.prototype.open = function (opt_map, opt_anchor, map_data) {
    var that = this;
    window.setTimeout(function () {
        that.open_(opt_map, opt_anchor);
    }, 230); //Adjusting the timeout here appears to calculate height more efficiently.
};


/**
 * Open the GMB_InfoBubble
 * @private
 * @param {google.maps.Map=} opt_map Optional map to open on.
 * @param {google.maps.MVCObject=} opt_anchor Optional anchor to position at.
 */
GMB_InfoBubble.prototype.open_ = function (opt_map, opt_anchor) {
    this.updateContent_();

    if (opt_map) {
        this.setMap(opt_map);
    }

    if (opt_anchor) {
        this.set('anchor', opt_anchor);
        this.bindTo('anchorPoint', opt_anchor);
        this.bindTo('position', opt_anchor);
    }

    // Show the bubble and the show
    this.bubble_.style['display'] = this.bubbleShadow_.style['display'] = '';
    var animation = !this.get('disableAnimation');

    if (animation) {
        // Add the animation
        this.bubble_.className += ' ' + this.animationName_;
        this.bubbleShadow_.className += ' ' + this.animationName_;
    }

    this.redraw_();
    this.isOpen_ = true;

    var pan = !this.get('disableAutoPan');
    if (pan) {
        var that = this;
        window.setTimeout(function () {
            // Pan into view, done in a time out to make it feel nicer :)
            that.panToView();
        }, 200);
    }
};
GMB_InfoBubble.prototype['open'] = GMB_InfoBubble.prototype.open;


/**
 * Set the position of the GMB_InfoBubble
 *
 * @param {google.maps.LatLng} position The position to set.
 */
GMB_InfoBubble.prototype.setPosition = function (position) {
    if (position) {
        this.set('position', position);
    }
};
GMB_InfoBubble.prototype['setPosition'] = GMB_InfoBubble.prototype.setPosition;


/**
 * Returns the position of the GMB_InfoBubble
 *
 * @return {google.maps.LatLng} the position.
 */
GMB_InfoBubble.prototype.getPosition = function () {
    return /** @type {google.maps.LatLng} */ (this.get('position'));
};
GMB_InfoBubble.prototype['getPosition'] = GMB_InfoBubble.prototype.getPosition;


/**
 * position changed MVC callback
 */
GMB_InfoBubble.prototype.position_changed = function () {
    this.draw();
};
GMB_InfoBubble.prototype['position_changed'] = GMB_InfoBubble.prototype.position_changed;


/**
 * Pan the GMB_InfoBubble into view
 */
GMB_InfoBubble.prototype.panToView = function () {
    var projection = this.getProjection();

    if (!projection) {
        // The map projection is not ready yet so do nothing
        return;
    }

    if (!this.bubble_) {
        // No Bubble yet so do nothing
        return;
    }

    var anchorHeight = this.getAnchorHeight_();
    var height = this.bubble_.offsetHeight + anchorHeight;
    var map = this.get('map');
    var mapDiv = map.getDiv();
    var mapHeight = mapDiv.offsetHeight;


    var latLng = this.getPosition();
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
GMB_InfoBubble.prototype['panToView'] = GMB_InfoBubble.prototype.panToView;


/**
 * Converts a HTML string to a document fragment.
 *
 * @param {string} htmlString The HTML string to convert.
 * @return {Node} A HTML document fragment.
 * @private
 */
GMB_InfoBubble.prototype.htmlToDocumentFragment_ = function (htmlString) {
    htmlString = htmlString.replace(/^\s*([\S\s]*)\b\s*$/, '$1');
    var tempDiv = document.createElement('DIV');
    tempDiv.innerHTML = htmlString;
    if (tempDiv.childNodes.length == 1) {
        return /** @type {!Node} */ (tempDiv.removeChild(tempDiv.firstChild));
    } else {
        var fragment = document.createDocumentFragment();
        while (tempDiv.firstChild) {
            fragment.appendChild(tempDiv.firstChild);
        }
        return fragment;
    }
};


/**
 * Removes all children from the node.
 *
 * @param {Node} node The node to remove all children from.
 * @private
 */
GMB_InfoBubble.prototype.removeChildren_ = function (node) {
    if (!node) {
        return;
    }

    var child;
    while (child = node.firstChild) {
        node.removeChild(child);
    }
};


/**
 * Sets the content of the GMB_InfoBubble.
 *
 * @param {string|Node} content The content to set.
 */
GMB_InfoBubble.prototype.setContent = function (content) {
    this.set('content', content);
};
GMB_InfoBubble.prototype['setContent'] = GMB_InfoBubble.prototype.setContent;


/**
 * Get the content of the GMB_InfoBubble.
 *
 * @return {string|Node} The marker content.
 */
GMB_InfoBubble.prototype.getContent = function () {
    return /** @type {Node|string} */ (this.get('content'));
};
GMB_InfoBubble.prototype['getContent'] = GMB_InfoBubble.prototype.getContent;


/**
 * Sets the marker content and adds loading events to images
 */
GMB_InfoBubble.prototype.updateContent_ = function () {
    if (!this.content_) {
        // The Content area doesn't exist.
        return;
    }

    this.removeChildren_(this.content_);
    var content = this.getContent();
    if (content) {
        if (typeof content == 'string') {
            content = this.htmlToDocumentFragment_(content);
        }
        this.content_.appendChild(content);

        var that = this;
        var images = this.content_.getElementsByTagName('IMG');
        for (var i = 0, image; image = images[i]; i++) {
            // Because we don't know the size of an image till it loads, add a
            // listener to the image load so the marker can resize and reposition
            // itself to be the correct height.
            google.maps.event.addDomListener(image, 'load', function () {
                that.imageLoaded_();
            });
        }
    }
    this.redraw_();
};


/**
 * Image loaded
 * @private
 */
GMB_InfoBubble.prototype.imageLoaded_ = function () {
    var pan = !this.get('disableAutoPan');
    this.redraw_();
    if (pan && (this.tabs_.length == 0 || this.activeTab_.index == 0)) {
        this.panToView();
    }
};


/**
 * Updates the styles of the tabs
 * @private
 */
GMB_InfoBubble.prototype.updateTabStyles_ = function () {
    if (this.tabs_ && this.tabs_.length) {
        for (var i = 0, tab; tab = this.tabs_[i]; i++) {
            this.setTabStyle_(tab.tab);
        }
        this.activeTab_.style['zIndex'] = this.baseZIndex_;
        var borderWidth = this.getBorderWidth_();
        var padding = this.getPadding_() / 2;
        this.activeTab_.style['borderBottomWidth'] = 0;
        this.activeTab_.style['paddingBottom'] = this.px(padding + borderWidth);
    }
};


/**
 * Sets the style of a tab
 * @private
 * @param {Element} tab The tab to style.
 */
GMB_InfoBubble.prototype.setTabStyle_ = function (tab) {
    var backgroundColor = this.get('backgroundColor');
    var borderColor = this.get('borderColor');
    var borderRadius = this.getBorderRadius_();
    var borderWidth = this.getBorderWidth_();
    var padding = this.getPadding_();

    var marginRight = this.px(-(Math.max(padding, borderRadius)));
    var borderRadiusPx = this.px(borderRadius);

    var index = this.baseZIndex_;
    if (tab.index) {
        index -= tab.index;
    }

    // The styles for the tab
    var styles = {
        'cssFloat': 'left',
        'position': 'relative',
        'cursor': 'pointer',
        'backgroundColor': backgroundColor,
        'border': this.px(borderWidth) + ' solid ' + borderColor,
        'padding': this.px(padding / 2) + ' ' + this.px(padding),
        'marginRight': marginRight,
        'whiteSpace': 'nowrap',
        'borderRadiusTopLeft': borderRadiusPx,
        'MozBorderRadiusTopleft': borderRadiusPx,
        'webkitBorderTopLeftRadius': borderRadiusPx,
        'borderRadiusTopRight': borderRadiusPx,
        'MozBorderRadiusTopright': borderRadiusPx,
        'webkitBorderTopRightRadius': borderRadiusPx,
        'zIndex': index,
        'display': 'inline'
    };

    for (var style in styles) {
        tab.style[style] = styles[style];
    }

    var className = this.get('tabClassName');
    if (className != undefined) {
        tab.className += ' ' + className;
    }
};


/**
 * Add user actions to a tab
 * @private
 * @param {Object} tab The tab to add the actions to.
 */
GMB_InfoBubble.prototype.addTabActions_ = function (tab) {
    var that = this;
    tab.listener_ = google.maps.event.addDomListener(tab, 'click', function () {
        that.setTabActive_(this);
    });
};


/**
 * Set a tab at a index to be active
 *
 * @param {number} index The index of the tab.
 */
GMB_InfoBubble.prototype.setTabActive = function (index) {
    var tab = this.tabs_[index - 1];

    if (tab) {
        this.setTabActive_(tab.tab);
    }
};
GMB_InfoBubble.prototype['setTabActive'] = GMB_InfoBubble.prototype.setTabActive;


/**
 * Set a tab to be active
 * @private
 * @param {Object} tab The tab to set active.
 */
GMB_InfoBubble.prototype.setTabActive_ = function (tab) {
    if (!tab) {
        this.setContent('');
        this.updateContent_();
        return;
    }

    var padding = this.getPadding_() / 2;
    var borderWidth = this.getBorderWidth_();

    if (this.activeTab_) {
        var activeTab = this.activeTab_;
        activeTab.style['zIndex'] = this.baseZIndex_ - activeTab.index;
        activeTab.style['paddingBottom'] = this.px(padding);
        activeTab.style['borderBottomWidth'] = this.px(borderWidth);
    }

    tab.style['zIndex'] = this.baseZIndex_;
    tab.style['borderBottomWidth'] = 0;
    tab.style['marginBottomWidth'] = '-10px';
    tab.style['paddingBottom'] = this.px(padding + borderWidth);

    this.setContent(this.tabs_[tab.index].content);
    this.updateContent_();

    this.activeTab_ = tab;

    this.redraw_();
};


/**
 * Set the max width of the GMB_InfoBubble
 *
 * @param {number} width The max width.
 */
GMB_InfoBubble.prototype.setMaxWidth = function (width) {
    this.set('maxWidth', width);
};
GMB_InfoBubble.prototype['setMaxWidth'] = GMB_InfoBubble.prototype.setMaxWidth;


/**
 * maxWidth changed MVC callback
 */
GMB_InfoBubble.prototype.maxWidth_changed = function () {
    this.redraw_();
};
GMB_InfoBubble.prototype['maxWidth_changed'] = GMB_InfoBubble.prototype.maxWidth_changed;


/**
 * Set the max height of the GMB_InfoBubble
 *
 * @param {number} height The max height.
 */
GMB_InfoBubble.prototype.setMaxHeight = function (height) {
    this.set('maxHeight', height);
};
GMB_InfoBubble.prototype['setMaxHeight'] = GMB_InfoBubble.prototype.setMaxHeight;


/**
 * maxHeight changed MVC callback
 */
GMB_InfoBubble.prototype.maxHeight_changed = function () {
    this.redraw_();
};
GMB_InfoBubble.prototype['maxHeight_changed'] = GMB_InfoBubble.prototype.maxHeight_changed;


/**
 * Set the min width of the GMB_InfoBubble
 *
 * @param {number} width The min width.
 */
GMB_InfoBubble.prototype.setMinWidth = function (width) {
    this.set('minWidth', width);
};
GMB_InfoBubble.prototype['setMinWidth'] = GMB_InfoBubble.prototype.setMinWidth;


/**
 * minWidth changed MVC callback
 */
GMB_InfoBubble.prototype.minWidth_changed = function () {
    this.redraw_();
};
GMB_InfoBubble.prototype['minWidth_changed'] = GMB_InfoBubble.prototype.minWidth_changed;


/**
 * Set the min height of the GMB_InfoBubble
 *
 * @param {number} height The min height.
 */
GMB_InfoBubble.prototype.setMinHeight = function (height) {
    this.set('minHeight', height);
};
GMB_InfoBubble.prototype['setMinHeight'] = GMB_InfoBubble.prototype.setMinHeight;


/**
 * minHeight changed MVC callback
 */
GMB_InfoBubble.prototype.minHeight_changed = function () {
    this.redraw_();
};
GMB_InfoBubble.prototype['minHeight_changed'] = GMB_InfoBubble.prototype.minHeight_changed;


/**
 * Add a tab
 *
 * @param {string} label The label of the tab.
 * @param {string|Element} content The content of the tab.
 */
GMB_InfoBubble.prototype.addTab = function (label, content) {
    var tab = document.createElement('DIV');
    tab.innerHTML = label;

    this.setTabStyle_(tab);
    this.addTabActions_(tab);

    this.tabsContainer_.appendChild(tab);

    this.tabs_.push({
        label: label,
        content: content,
        tab: tab
    });

    tab.index = this.tabs_.length - 1;
    tab.style['zIndex'] = this.baseZIndex_ - tab.index;

    if (!this.activeTab_) {
        this.setTabActive_(tab);
    }

    tab.className = tab.className + ' ' + this.animationName_;

    this.redraw_();
};
GMB_InfoBubble.prototype['addTab'] = GMB_InfoBubble.prototype.addTab;


/**
 * Update a tab at a speicifc index
 *
 * @param {number} index The index of the tab.
 * @param {?string} opt_label The label to change to.
 * @param {?string} opt_content The content to update to.
 */
GMB_InfoBubble.prototype.updateTab = function (index, opt_label, opt_content) {
    if (!this.tabs_.length || index < 0 || index >= this.tabs_.length) {
        return;
    }

    var tab = this.tabs_[index];
    if (opt_label != undefined) {
        tab.tab.innerHTML = tab.label = opt_label;
    }

    if (opt_content != undefined) {
        tab.content = opt_content;
    }

    if (this.activeTab_ == tab.tab) {
        this.setContent(tab.content);
        this.updateContent_();
    }
    this.redraw_();
};
GMB_InfoBubble.prototype['updateTab'] = GMB_InfoBubble.prototype.updateTab;


/**
 * Remove a tab at a specific index
 *
 * @param {number} index The index of the tab to remove.
 */
GMB_InfoBubble.prototype.removeTab = function (index) {
    if (!this.tabs_.length || index < 0 || index >= this.tabs_.length) {
        return;
    }

    var tab = this.tabs_[index];
    tab.tab.parentNode.removeChild(tab.tab);

    google.maps.event.removeListener(tab.tab.listener_);

    this.tabs_.splice(index, 1);

    delete tab;

    for (var i = 0, t; t = this.tabs_[i]; i++) {
        t.tab.index = i;
    }

    if (tab.tab == this.activeTab_) {
        // Removing the current active tab
        if (this.tabs_[index]) {
            // Show the tab to the right
            this.activeTab_ = this.tabs_[index].tab;
        } else if (this.tabs_[index - 1]) {
            // Show a tab to the left
            this.activeTab_ = this.tabs_[index - 1].tab;
        } else {
            // No tabs left to sho
            this.activeTab_ = undefined;
        }

        this.setTabActive_(this.activeTab_);
    }

    this.redraw_();
};
GMB_InfoBubble.prototype['removeTab'] = GMB_InfoBubble.prototype.removeTab;


/**
 * Get the size of an element
 * @private
 * @param {Node|string} element The element to size.
 * @param {number=} opt_maxWidth Optional max width of the element.
 * @param {number=} opt_maxHeight Optional max height of the element.
 * @return {google.maps.Size} The size of the element.
 */
GMB_InfoBubble.prototype.getElementSize_ = function (element, opt_maxWidth, opt_maxHeight) {

    var inner = document.createElement('DIV');
    inner.className = 'gmb-infobubble';
    inner.style.display = 'inline';
    inner.style.position = 'absolute';
    inner.style.padding = this.padding + 'px';

    if (typeof element == 'string') {
        inner.innerHTML = element;
    } else {
        inner.appendChild(element.cloneNode(true));
    }

    //The info_window's map element
    var map_el = jQuery('#google-maps-builder-' + this.map_data.id);
    map_el.append(inner);

    //Original size.
    var size = new google.maps.Size(inner.offsetWidth, inner.offsetHeight);

    //Now test size within a container (preventing scrollbars)
    //@see http://stackoverflow.com/questions/13382516/getting-scroll-bar-width-using-javascript
    var outer = document.createElement('div');
    outer.className = 'gmb-infobubble-container';
    outer.style.position = 'relative';
    outer.style.overflowY = 'auto';
    outer.style.overflowX = 'auto';
    outer.style.width = inner.offsetWidth + 2 + 'px';
    outer.style.height = inner.offsetHeight + 2 + 'px';
    outer.style.visibility = 'hidden';
    outer.style.msOverflowStyle = 'scrollbar'; // needed for WinJS apps.
    map_el.append(outer);

    //Add inner div into outer inner.
    jQuery(inner).appendTo(outer);

    //Calculate scrollbar width.
    var scroll_width = outer.offsetWidth - inner.offsetWidth;

    //If there's a vertical scrollbar
    if (scroll_width > 0) {
        outer.style.width = inner.offsetWidth + scroll_width + 4 + 'px';
        size = new google.maps.Size(outer.offsetWidth, outer.offsetHeight);
    }


    // If the width is bigger than the max width then set the width and size again.
    if (opt_maxWidth && size.width > opt_maxWidth) {
        inner.style.width = this.px(opt_maxWidth);
        size = new google.maps.Size(inner.offsetWidth, inner.offsetHeight);
    }

    // If the height is bigger than the max height then set the height and size again.
    if (opt_maxHeight && size.height > opt_maxHeight) {
        inner.style.height = this.px(opt_maxHeight);
        size = new google.maps.Size(inner.offsetWidth, inner.offsetHeight);
    }

    jQuery(outer).remove();
    return size;

};

/**
 * Redraw the GMB_InfoBubble
 * @private
 */
GMB_InfoBubble.prototype.redraw_ = function () {
    this.figureOutSize_();
    this.positionCloseButton_();
    this.draw();
};


/**
 * Figure out the optimum size of the GMB_InfoBubble
 * @private
 */
GMB_InfoBubble.prototype.figureOutSize_ = function () {
    var map = this.get('map');

    if (!map) {
        return;
    }

    var padding = this.getPadding_();
    var borderWidth = this.getBorderWidth_();
    var borderRadius = this.getBorderRadius_();
    var arrowSize = this.getArrowSize_();
    var mapDiv = map.getDiv();
    var gutter = arrowSize * 2;
    var mapWidth = mapDiv.offsetWidth - gutter;
    var mapHeight = mapDiv.offsetHeight - gutter - this.getAnchorHeight_();
    var tabHeight = 0;
    var width = /** @type {number} */ (this.get('minWidth') || 0);
    var height = /** @type {number} */ (this.get('minHeight') || 0);
    var maxWidth = /** @type {number} */ (this.get('maxWidth') || 0);
    var maxHeight = /** @type {number} */ (this.get('maxHeight') || 0);

    maxWidth = Math.min(mapWidth, maxWidth);
    maxHeight = Math.min(mapHeight, maxHeight);

    var tabWidth = 0;
    if (this.tabs_.length) {
        // If there are tabs then you need to check the size of each tab's content.
        for (var i = 0, tab; tab = this.tabs_[i]; i++) {
            var tabSize = this.getElementSize_(tab.tab, maxWidth, maxHeight);
            var contentSize = this.getElementSize_(tab.content, maxWidth, maxHeight);

            if (width < tabSize.width) {
                width = tabSize.width;
            }

            // Add up all the tab widths because they might end up being wider than the content.
            tabWidth += tabSize.width;

            if (height < tabSize.height) {
                height = tabSize.height;
            }

            if (tabSize.height > tabHeight) {
                tabHeight = tabSize.height;
            }

            if (width < contentSize.width) {
                width = contentSize.width;
            }

            if (height < contentSize.height) {
                height = contentSize.height;
            }
        }
    } else {
        var content = /** @type {string|Node} */ (this.get('content'));
        if (typeof content == 'string') {
            content = this.htmlToDocumentFragment_(content);
        }
        if (content) {

            var contentSize = this.getElementSize_(content, maxWidth, maxHeight);

            //If content width is l
            if (width < contentSize.width) {
                width = contentSize.width;
            }

            if (height < contentSize.height) {
                height = contentSize.height;
            }
        }
    }


    if (maxWidth) {
        width = Math.min(width, maxWidth);
    }

    if (maxHeight) {
        height = Math.min(height, maxHeight);
    }


    //Account for the info_window's padding.
    // if (padding) {
    //     height = height + (padding * 2);
    //     width = width + (padding * 2);
    // }

    width = Math.max(width, tabWidth);

    if (width == tabWidth) {
        width = width + 2 * padding;
    }

    arrowSize = arrowSize * 2;
    width = Math.max(width, arrowSize);

    if (this.tabsContainer_) {
        this.tabHeight_ = tabHeight;
        this.tabsContainer_.style['width'] = this.px(tabWidth);
    }

    this.contentContainer_.style['width'] = this.px(width);
    this.contentContainer_.style['height'] = this.px(height);

};


/**
 *  Get the height of the anchor
 *
 *  This function is a hack for now and doesn't really work that good, need to
 *  wait for pixelBounds to be correctly exposed.
 *  @private
 *  @return {number} The height of the anchor.
 */
GMB_InfoBubble.prototype.getAnchorHeight_ = function () {
    var anchor = this.get('anchor');
    if (anchor) {
        var anchorPoint = /** @type google.maps.Point */(this.get('anchorPoint'));

        if (anchorPoint) {
            return -1 * anchorPoint.y;
        }
    }
    return 0;
};

GMB_InfoBubble.prototype.anchorPoint_changed = function () {
    this.draw();
};
GMB_InfoBubble.prototype['anchorPoint_changed'] = GMB_InfoBubble.prototype.anchorPoint_changed;

/**
 * Position the close button in the right spot.
 * @private
 */
GMB_InfoBubble.prototype.positionCloseButton_ = function () {
    var br = this.getBorderRadius_();
    var bw = this.getBorderWidth_();

    var right = 2;
    var top = 2;

    if (this.tabs_.length && this.tabHeight_) {
        top += this.tabHeight_;
    }

    top += bw;
    right += bw;

    var c = this.contentContainer_;
    if (c && c.clientHeight < c.scrollHeight) {
        // If there are scrollbars then move the cross in so it is not over
        // scrollbar
        right += 15;
    }

    this.close_.style['right'] = this.px(right);
    this.close_.style['top'] = this.px(top);
};