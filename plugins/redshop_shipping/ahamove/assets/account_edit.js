redSHOP = window.redSHOP || {};

function initializing() {
	setTimeout(function () {
		AHAMOVE = window.AHAMOVE || {};
		var requester = AHAMOVE.requester_contact,
			bound, lat, lng, radius;


		jQuery('input[name^="address"]').each(function (index, value) {
			let $addressElement = jQuery(this);

			// $addressElement.prop('readonly', true);
			$addressElement.attr('placeholder', '');
			$addressElement.attr('data-address', 'N/A');

			$addressElement.on('change', function () {
				var oldAddress = this.getAttribute('data-address');
				var newAddress = this.value.replace(/\s+/g,' ').trim();

				if (oldAddress === 'N/A' || newAddress !== oldAddress) {
					this.setAttribute('address_verified', 'no');
					this.setAttribute('data-address', 'N/A');
				}
				else {
					this.value = newAddress;
				}
			});
			let $stateElement = this.name === 'address' ? jQuery('[name="state_code"]') : jQuery('[name="state_code_ST"]');
			let $stateElementValue = $stateElement.val();
			$stateElementValue = Object.values(requester).find(function(requesters_store_owner) {
				return requesters_store_owner.state === $stateElementValue;
			});


			if (typeof $stateElementValue == 'undefined' || $stateElementValue.location_bound.center_point.split(',').length <= 1) {
				$stateElement.attr('address_verified', 'no');
				console.log('Cannot get state element');
				return;
			}

			let centerpoint = $stateElementValue.location_bound.center_point.split(',');

			lat = centerpoint[0];
			lng = centerpoint[1];
			radius = $stateElementValue.location_bound.radius;
			bound = getLocationLatLng(lat, lng, radius);
			var options = {
				types: ['address'],
				componentRestrictions: {country: 'vn'},
				bounds: bound,
				strictBounds: true // "true" means restrict, instead of just biasing search results to a region
			};

			// Create the autocomplete object, restricting the search predictions to geographical location types.
			var autocomplete = new google.maps.places.Autocomplete(
				$addressElement.get(0), options);

			autocomplete.inputId = this.id;
			autocomplete.inputName = this.name;

			// When the user selects an address from the drop-down, populate the address fields in the form.
			google.maps.event.addListener(autocomplete, 'place_changed', onSelectingAddress);

			$stateElement.change(function () {
				// reset address field on state (city) change
				$addressElement.val('');
				$addressElement.attr('address_verified', 'no');
				$addressElement.attr('data-address', 'N/A');

				let state = this.value;
				state = Object.values(requester).find(function(requesters_store_owner) {
					return requesters_store_owner.state === state;
				});

				if (typeof state == 'undefined') {
					$stateElement.attr('address_verified', 'no');
					return;
				}
			});
		});
	}, 1000);
}

function getLocationLatLng(lat, lng, radius) {
	if (typeof lat == 'undefined' || typeof lng == 'undefined' || typeof radius == 'undefined') {
		return;
	}

	return new google.maps.Circle({
		center: new google.maps.LatLng(lat, lng),
		radius: parseInt(radius)
	}).getBounds();
}

function onSelectingAddress() {
	var place = this.getPlace(); // Get the place details from the autocomplete object.
	var $input = jQuery('#' + this.inputId);

	if (typeof place.geometry == 'undefined') {
		$input.attr('address_verified', 'no');
		return;
	}

	jQuery('label#' + this.inputId + '-error').remove();

	var dataAddress = $input.val();

	$input.attr('address_verified', 'yes');
	$input.attr('data-address', dataAddress);

	var lat = place.geometry.location.lat();
	var lng = place.geometry.location.lng();

	if (this.inputName === 'address') {
		jQuery('#rs_geocoding_location').val(lat + ',' + lng);
	}

	if (this.inputName === 'address_ST') {
		jQuery('#rs_geocoding_location_ST').val(lat + ',' + lng);
	}
}

function customizeFormValidationRules () {
	var $ = jQuery.noConflict();

	$.validator.addMethod("location_valid", function (address, element) {
		return this.optional(element) || $(element).attr('address_verified') === 'yes';
	}, '');

	$.validator.addMethod("phone", function (phone_number, element) {
		return this.optional(element) || phone_number.match(/^0?(2|[35789])[0-9]{8}$|^02[48][0-9]{8}$/);
	}, '');

	var settings = $('#adminForm').validate().settings;

	$.extend(true, settings, {
		rules: {
			address: {
				required: true,
				location_valid: true
			},
			address_ST: {
				location_valid: true
			},
			phone: {
				phone: true
			},
			phone_ST: {
				phone: true
			}
		},
		messages: {
			address: {
				required: Joomla.JText._('COM_REDSHOP_YOUR_MUST_PROVIDE_A_ADDRESS'),
				location_valid: Joomla.JText._('PLG_REDSHOP_SHIPPING_AHAMOVE_CAN_NOT_VERIFY_YOUR_ADDRESS')
			},
			address_ST: {
				required: Joomla.JText._('COM_REDSHOP_YOUR_MUST_PROVIDE_A_ADDRESS'),
				location_valid: Joomla.JText._('PLG_REDSHOP_SHIPPING_AHAMOVE_CAN_NOT_VERIFY_YOUR_ADDRESS')
			},
			firstname_ST: {
				required: Joomla.JText._('COM_REDSHOP_YOUR_MUST_PROVIDE_A_FIRSTNAME')
			},
			lastname_ST: {
				required: Joomla.JText._('COM_REDSHOP_YOUR_MUST_PROVIDE_A_LASTNAME')
			},
			phone: {
				required: Joomla.JText._('COM_REDSHOP_YOUR_MUST_PROVIDE_A_VALID_PHONE'),
				phone: Joomla.JText._('PLG_REDSHOP_SHIPPING_AHAMOVE_GETQUOTATION_ERROR_INVALID_PHONE_NUMBER')
			},
			phone_ST: {
				required: Joomla.JText._('COM_REDSHOP_YOUR_MUST_PROVIDE_A_VALID_PHONE'),
				phone: Joomla.JText._('PLG_REDSHOP_SHIPPING_AHAMOVE_GETQUOTATION_ERROR_INVALID_PHONE_NUMBER')
			}
		}
	});
}

jQuery(document).ready(function($) {
	setTimeout(customizeFormValidationRules, 500);
	google.maps.event.addDomListener(window, 'load', initializing);

});