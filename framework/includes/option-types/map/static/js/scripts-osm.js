'use strict';

/**
 * Free Leaflet + OpenStreetMap (Nominatim) location picker.
 *
 * Loaded by FW_Option_Type_Map only when NO Google Maps API key is configured,
 * as a drop-in replacement for the Google-based picker (static/js/scripts.js).
 * It wires the exact same markup/fields (.map-location, .map-venue, … ,
 * .map-coordinates, .map-googlemap) so the saved value shape is identical.
 *
 * Nominatim usage policy: at most ~1 request/second. We therefore geocode only
 * on Enter (location field) and on blur (detail fields), never per keystroke.
 */
(function($, _, fwe, localized) {

	// Point Leaflet's default marker icon at the CDN images (otherwise broken).
	if (typeof L !== 'undefined' && localized && localized.leaflet_images) {
		L.Icon.Default.mergeOptions({
			iconRetinaUrl: localized.leaflet_images + 'marker-icon-2x.png',
			iconUrl: localized.leaflet_images + 'marker-icon.png',
			shadowUrl: localized.leaflet_images + 'marker-shadow.png'
		});
	}

	function nominatim(endpoint, params, done) {
		params.format = 'jsonv2';
		params.addressdetails = 1;
		if (localized.language) {
			params['accept-language'] = localized.language;
		}
		$.ajax({ url: endpoint, data: params, dataType: 'json' })
			.done(function(res) { done(res); })
			.fail(function() { done(null); });
	}

	function fw_option_osm_initialize($data) {
		if (typeof L === 'undefined') {
			$data.find('.fw-option-map-inputs').attr('readonly', 'readonly');
			return;
		}

		var option = {
			fields: {
				location: $data.find('.map-location'),
				venue: $data.find('.map-venue'),
				address: $data.find('.map-address'),
				city: $data.find('.map-city'),
				state: $data.find('.map-state'),
				country: $data.find('.map-country'),
				zipCode: $data.find('.map-zip'),
				coordinates: $data.find('.map-coordinates')
			},
			toggles: {
				expand: $data.find('.fw-option-maps-expand'),
				reset: $data.find('.fw-option-maps-close')
			},
			tabs: {
				first: $data.find('.fw-option-maps-tab.first'),
				second: $data.find('.fw-option-maps-tab.second')
			},
			container: $data.find('.map-googlemap'),
			map: null,
			marker: null
		};

		function getCoords() {
			var raw = option.fields.coordinates.val();
			if (!raw) return null;
			try {
				var c = (typeof raw === 'object') ? raw : JSON.parse(raw);
				if (c && c.lat != null && c.lng != null) return c;
			} catch (e) {}
			return null;
		}

		function setField(name, value) {
			option.fields[name].val(value == null ? '' : value);
		}

		function setCoords(lat, lng) {
			option.fields.coordinates.val(JSON.stringify({ lat: lat, lng: lng }));
		}

		function getComputedLongAddress() {
			return _.reduce(
				[
					option.fields.venue.val(),
					option.fields.address.val(),
					option.fields.city.val(),
					option.fields.state.val(),
					option.fields.country.val(),
					option.fields.zipCode.val()
				],
				function(a, b) {
					b = (b || '').trim();
					return b ? (a ? a + ', ' + b : b) : a;
				},
				''
			);
		}

		// Fill the detail fields from a Nominatim result object.
		function fillFromResult(result) {
			if (!result) return;

			var addr = result.address || {};

			setField('address', addr.road || '');
			setField('city', addr.city || addr.town || addr.village || addr.hamlet || addr.county || '');
			setField('state', addr.state || '');
			setField('country', addr.country || '');
			setField('zipCode', addr.postcode || '');

			// Use the place name as the venue when it isn't just the street.
			if (result.name && result.name !== addr.road) {
				setField('venue', result.name);
			}

			var lat = parseFloat(result.lat), lng = parseFloat(result.lon);
			if (!isNaN(lat) && !isNaN(lng)) {
				setCoords(lat, lng);
				placeMarker(lat, lng, false);
			}

			setField('location', getComputedLongAddress());
		}

		function clearFields() {
			['location', 'venue', 'address', 'city', 'state', 'country', 'zipCode'].forEach(function(n) {
				setField(n, '');
			});
			option.fields.coordinates.val('');
		}

		function ensureMap(lat, lng) {
			if (option.map) return;

			option.map = L.map(option.container[0]).setView([lat, lng], 15);
			L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
				maxZoom: 19,
				attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
			}).addTo(option.map);

			option.marker = L.marker([lat, lng], { draggable: true }).addTo(option.map);

			// Drag the pin → reverse-geocode and refill the fields.
			option.marker.on('dragend', function() {
				var pos = option.marker.getLatLng();
				setCoords(pos.lat, pos.lng);
				nominatim(localized.nominatim_reverse, { lat: pos.lat, lon: pos.lng }, function(res) {
					if (res) {
						res.name = res.name || '';
						fillFromResult(res);
					}
				});
			});
		}

		function placeMarker(lat, lng, zoom) {
			ensureMap(lat, lng);
			option.marker.setLatLng([lat, lng]);
			if (zoom !== false) {
				option.map.setView([lat, lng], 15);
			} else {
				option.map.panTo([lat, lng]);
			}
		}

		// Forward-geocode a free-text address.
		function searchAddress(query, andExpand) {
			if (!query) return;
			nominatim(localized.nominatim_search, { q: query, limit: 1 }, function(res) {
				if (res && res.length) {
					fillFromResult(res[0]);
					if (andExpand) {
						option.toggles.expand.trigger('click');
					}
				} else {
					setCoords(0, 0);
				}
			});
		}

		// ---- events --------------------------------------------------------

		// Enter in the location box → geocode + reveal the map.
		option.fields.location.on('keydown', function(e) {
			if (e.keyCode === 13) {
				e.preventDefault();
				searchAddress($(this).val(), true);
				return false;
			}
		});

		// Editing the detail fields → re-geocode the composed address.
		$data.on('blur', '.map-venue, .map-address, .map-city, .map-state, .map-country, .map-zip', function() {
			searchAddress(getComputedLongAddress(), false);
		});

		option.toggles.expand.on('click', function(e) {
			e.preventDefault();
			option.tabs.first.hide().addClass('closed');
			option.tabs.second.show().removeClass('closed');

			var c = getCoords() || { lat: -34, lng: 150 };
			ensureMap(c.lat, c.lng);
			// Container was hidden until now; let Leaflet re-measure it.
			setTimeout(function() {
				option.map.invalidateSize();
				placeMarker(c.lat, c.lng, true);
			}, 50);
		});

		option.toggles.reset.on('click', function(e) {
			e.preventDefault();
			clearFields();
			option.tabs.second.hide().addClass('closed');
			option.tabs.first.show().removeClass('closed');
		});

		// If we already have a saved location, open straight to the map.
		if (option.fields.location.val() || getCoords()) {
			option.toggles.expand.trigger('click');
		}
	}

	$(document).ready(function() {
		fwe.on('fw:options:init', function(data) {
			var $obj = data.$elements.find('.fw-option-type-map:not(.initialized)');
			if (!$obj.length) {
				return;
			}
			$obj.each(function() {
				fw_option_osm_initialize($(this));
			});
			$obj.addClass('initialized');
		});
	});

	// Same value contract as the Google picker: coordinates come back as an object.
	fw.options.register('map', {
		getValue: function(optionDescriptor) {
			var promise = $.Deferred();

			fw.options
				.getContextValue(optionDescriptor.el)
				.then(function(result) {
					try {
						result.value.coordinates = JSON.parse(result.value.coordinates);
					} catch (e) {}

					promise.resolve({
						value: result.value,
						optionDescriptor: optionDescriptor
					});
				});

			return promise;
		}
	});

})(jQuery, _, fwEvents, _fw_option_type_map_osm);
