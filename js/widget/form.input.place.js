/**
 * @class $.sv.placeinput
 */
var widget = {};
widget.options = {
    keys: ['id', 'country', 'city', 'name', 'latitude', 'longitude'],
    mapOptions: {
        zoom: 14
    },
    map: '',
    autoCompleteOptions: {}
};

widget._create = function () {
    this._setUpHiddenFields();

    this.geocoder = null;
    this.map = null;
    this.marker = null;

    this.service = null;
    this.autocomplete = null;
    this._place = null;
    this._latLng = null;

    this.mapWrapper = (this.options.map || this.element.find('.map')).parent();
    this.btn = this.element.find('.btn');
    this.input = this.element.find('input');

    this.mapWrapper.collapse({toggle: false});
    this._on(this.input, {
        'click': this._click,
        'focus': this._focus
    });
    this._on(this.btn, {
        'click': this._btnClick
    });
};
widget._setUpHiddenFields = function () {
    var key;
    for (var i = 0; i < this.options.keys.length; i++) {
        key = 'place_' + this.options.keys[i];
        if (this.element.find('[name=' + key + ']').length) {
            this.element.find('[name=' + key + ']').val(this.options[key] ? this.options[key] : null);
        } else {
            this.element.append($('<input/>', {
                type: 'hidden',
                name: key,
                value: this.options[key] ? this.options[key] : null
            }));
        }
    }
};
widget._click = function () {
    this.input.select();
//        this.select();
};
widget._btnClick = function (ev) {
    this._off(this.btn, 'click');
    this._off(this.input, 'focus');
    ev.preventDefault();
    ev.stopImmediatePropagation();
    this._initGoogle($.proxy(function () {
        this._initComponents();
        this._on(this.btn, {
            'click': function () {
                this.mapWrapper.collapse('toggle');
            }
        });
        this.btn.trigger('click');
    }, this));
};
widget._initGoogle = function (fn) {
    (new sovpalo.models.Google()).load('places', fn);
};
widget._focus = function () {
    this._off(this.input, 'focus');
    this._initGoogle($.proxy(function () {
        this._initComponents();
    }, this));
};
widget._change = function (place) {
    var getAddressPart = function (o, k) {
        o = o || [];
        for (var i = 0; i < o.length; i++) {
            if (o[i].types[0] == k) {
                return o[i];
            }
        }
        return {
            long_name: '',
            short_name: ''
        };
    };
    this.element.find('[name=place_id]').val(place['place_id']);
    this.element.find('[name=place_city]').val(getAddressPart(place['address_components'], 'locality').long_name);
    this.element.find('[name=place_country]').val(getAddressPart(place['address_components'], 'country').short_name.toLowerCase());
    this.element.find('[name=place_latitude]').val(place['geometry']['location'].lat());
    this.element.find('[name=place_longitude]').val(place['geometry']['location'].lng());
};
widget._initComponents = function () {

    this.geocoder = new google.maps.Geocoder();

    this._initAutoComplete();
    this._initMap();

    if (!this.input.val()) {
        var lat = this.options.latitude || $('[name=place_latitude]').prop('value');
        var lng = this.options.longitude || $('[name=place_longitude]').prop('value');
        if (lat && lng) {
            this.setLocation(lat, lng);
        }
    } else {
        this._codePlace(this.input.val());
    }

    this._on($(window), {
        'resize': this._resizeHandler
    });
    this._on(this.input, {
        'keypress': function (e) {
            if (e.keyCode === 13) {
                e.preventDefault();
                e.stopImmediatePropagation();
            }
        }
    });
};
widget._initAutoComplete = function () {
    this.autocomplete = new google.maps.places.Autocomplete(this.input.get(0), this.options.autoCompleteOptions);
    google.maps.event.addListener(this.autocomplete, 'place_changed', $.proxy(function () {
        var place = this.autocomplete.getPlace();
        if (place.geometry) {
            this._setPlace(place);
        }
    }, this));
};
widget._initMap = function () {
    this.map = new google.maps.Map(this.mapWrapper.find('.map').get(0), this.options.mapOptions);

    this.autocomplete.bindTo("bounds", this.map);

    google.maps.event.addListener(this.map, 'click', $.proxy(function (e) {
        var pos = e.latLng;
        this.marker.setPosition(pos);
        this.map.panTo(pos);
        this.input.blur();
        this._codeLatLng(pos);
    }, this));

    this.marker = new google.maps.Marker({
        map: this.map
    });

    this.service = new google.maps.places.PlacesService(this.map);

    this.mapWrapper.on('show.bs.collapse', $.proxy(function () {
        this.mapWrapper.css('display', 'block');
        if (this.input.val()) {
            this.resize();
        } else {
            this.geoLocation();
        }
        this.mapWrapper.css('display', '');
    }, this));
};

widget.setValue = function (value) {
    this.input.val(value);
    this._codePlace(value);
};
widget.getValue = function () {
    return this.input.val();
};
widget.setLocation = function (latitude, longitude) {
    this.setLatLng(new google.maps.LatLng(latitude, longitude));
};
widget.getLocation = function () {
    var latLng = this.getLatLng();
    if (latLng) {
        return {
            latitude: latLng.lat(),
            longitude: latLng.lng()
        };
    }
};
widget.setLatLng = function (latLng) {
    this._latLng = latLng;
    this._codeLatLng(this._latLng);
};
widget.getLatLng = function () {
    if (this._place && this._place.geometry) {
        return this._place.geometry.location;
    }
    return this._latLng;
};
widget.getMap = function () {
    return this.map;
};
widget.reload = function () {
    if (this.map) {
        this._codePlace(this.input.val());
    }
};
widget.resize = function () {
    if (this.map) {
        var center = this.map.getCenter();
        google.maps.event.trigger(this.map, 'resize');
        this.map.setCenter(center);
    }
};
widget.geoLocation = function (callback) {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition($.proxy(function (position) {
            var pos = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);
            this._updatePosition(pos);
            this._codeLatLng(pos);
            if (callback) {
                callback(pos);
            }
        }, this), function () {
            // error
            if (callback) {
                callback(null);
            }
        });
    } else {
        if (callback) {
            callback(null);
        }
    }
};

widget._codePlace = function (query) {
    if (!query) {
        return;
    }
    if (this.service) {
        this.service.textSearch({query: query}, $.proxy(function (results, status) {
            if (status === google.maps.places.PlacesServiceStatus.OK) {
                if (results[0]) {
                    this._setPlace(results[0]);
                } else {
                    // alert("No results found");
                }
            } else {
                // alert("Textsearch failed due to: " + status);
            }
        }, this));
    }
};
widget._codeLatLng = function (latlng) {
    this.geocoder.geocode({latLng: latlng}, $.proxy(function (results, status) {
        if (status === google.maps.GeocoderStatus.OK) {
            if (results[0]) {
//                    console.log(results[0]);
                this._setPlace(results[0], false);
            } else {
                // alert("No results found");
            }
        } else {
            // alert("Geocoder failed due to: " + status);
        }
    }, this));
};
widget._resizeHandler = function () {
    this.resize();
};
widget._setPlace = function (place, updateMap) {
    updateMap = typeof updateMap === 'undefined';
    this._place = place;

    this.resize();

    var pos = place.geometry.location;

    if (updateMap) {
        this._updatePosition(pos);
    }

    $('[name=place_latitude]').prop('value', pos.lat());
    $('[name=place_longitude]').prop('value', pos.lng());

    // update inputs
    if (!updateMap) {
        this.input.val(place['formatted_address']);
    }

    this._change(place);
};
widget._updatePosition = function (pos) {
    if (!this.map) {
        return;
    }
    this.map.setCenter(pos);

    if (this.options.icon) {
        this.marker.setIcon({
            url: this.options.icon,
            size: new google.maps.Size(71, 71),
            origin: new google.maps.Point(0, 0),
            anchor: new google.maps.Point(17, 34),
            scaledSize: new google.maps.Size(35, 35)
        });
    }

    this.marker.setPosition(pos);
    this.marker.setVisible(true);
};

widget.focus = function () {
    this.input.focus();
};

sovpalo.registerWidget('FormInputPlace', widget);