redSHOP = window.redSHOP || {};
jQuery(document).ready(function($) {
	let updateShippingFeeOnScheduletimeChange = false,
		isOnestepcheckout = redSHOP.RSConfig._('ONESTEP_CHECKOUT_ENABLE') == 1 ? 1 : '' ,
		billisshipCheckboxOnclick,
		paymentMethodOnclick;

	setTimeout(customizeFormValidationRules, 500);
	google.maps.event.addDomListener(window, 'load', initializing);

	$('body').on('change', '[name="delivery_date"], [name="delivery_time"]', function (e) {
		if (updateShippingFeeOnScheduletimeChange) {
			var bill_is_shipp = $("#billisship").is(":checked");
			var $addressObj = bill_is_shipp ? $("[name=address]") : $("[name=address_ST]");
			var address = $addressObj.val();
			var geocoding_location = bill_is_shipp ? $('#rs_geocoding_location').val() : $('#rs_geocoding_location_ST').val();

			if (geocoding_location !== '' && $addressObj.attr('data-address') !== 'N/A') {
				calculateShippingFee(0, geocoding_location.split(',')[0], geocoding_location.split(',')[1], address);
			}
		}
	}).on('click', '#billisship', function (e) {
		clearTimeout(billisshipCheckboxOnclick);

		billisshipCheckboxOnclick = setTimeout(function () {
			var bill_is_shipp = $("#billisship").is(":checked");
			var $addressObj = bill_is_shipp ? $("[name=address]") : $("[name=address_ST]");
			var address = $addressObj.val();
			var geocoding_location = bill_is_shipp ? $('#rs_geocoding_location').val() : $('#rs_geocoding_location_ST').val();

			if (geocoding_location !== '' && $addressObj.attr('data-address') !== 'N/A') {
				calculateShippingFee(0, geocoding_location.split(',')[0], geocoding_location.split(',')[1], address);
			}
		}, 600);
	}).on('click', '[name="payment_method_id"]', function (e) {
		clearTimeout(paymentMethodOnclick);
		var payment_method = $(this).val();

		paymentMethodOnclick = setTimeout(function () {
			if ($('input[name="shipping_rate_id"]:checked').hasClass('ahamove')) {
				var bill_is_shipp = $("#billisship").is(":checked");
				var $addressObj = bill_is_shipp ? $("[name=address]") : $("[name=address_ST]");
				var address = $addressObj.val();
				var geocoding_location = bill_is_shipp ? $('#rs_geocoding_location').val() : $('#rs_geocoding_location_ST').val();

				if (geocoding_location !== '' && $addressObj.attr('data-address') !== 'N/A') {
					calculateShippingFee(0, geocoding_location.split(',')[0], geocoding_location.split(',')[1], address);
				}
			}
		}, 600);
	});

	function initializing() {
		setTimeout(function () {
			AHAMOVE = window.AHAMOVE || {};
			var requester = AHAMOVE.requester_contact,
				bound,lat,lng,radius;

			jQuery('.redSHOPSiteViewCheckout input[name^="address"]').focus(function (index, value) {

				//Check condition shipping Ahamove checked and have onestep checkout
				if (!jQuery('[name="shipping_rate_id"]:checked').hasClass('ahamove') && isOnestepcheckout) {
					return;
				}

				var $addressElement = jQuery(this);

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
				$stateElement = this.name === 'address' ? jQuery('[name="state_code"]') : jQuery('[name="state_code_ST"]');
				$stateElementValue = $stateElement.val();
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
					types: ['geocode', 'establishment'],
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

					if (typeof state == 'undefined'|| state.location_bound.center_point.split(',').length <= 1) {
						console.log('Cannot get state element');
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
		if (!jQuery('[name="shipping_rate_id"]:checked').hasClass('ahamove') && isOnestepcheckout) {
			return;
		}
		console.log('Ahamove selected');
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

		var bill_is_shipp = jQuery("#billisship").is(":checked");

		var cityName = [];

		for (var i=0; i < place.address_components.length; i++) {
			if (place.address_components[i].types[0] == "locality") {
				cityName.push(place.address_components[i].long_name);
			}

			if (place.address_components[i].types[0] == "administrative_area_level_1") {
				cityName.push(place.address_components[i].long_name);
			}
		}

		cityName.join(', ');

		if (bill_is_shipp) {
			jQuery('[name="city"]').val(cityName);
		} else {
			jQuery('[name="city_ST"]').val(cityName);
		}
		jQuery(document).trigger('onSelectShippingAddress', [this, dataAddress]);

		//Check coditions billing and onestep checkout
		if (isOnestepcheckout && (bill_is_shipp && this.inputName === 'address') || (!bill_is_shipp && this.inputName === 'address_ST')) {
			calculateShippingFee(0, lat, lng, dataAddress);
		}
	}

	function showShippingRates(rates, isSuccess = true) {
		var $shippingContainer = jQuery('input.ahamove:first').closest('div');
		$shippingContainer.empty();

		if (isSuccess) {
			for (let i = 0; i < rates.length; i++) {
				let rateElement = rates[i];
				// let checked = (i === 0) ? ' checked ' : ' ';
				let checked = window.AHAMOVE.config.isChecked == 1 ? 'checked' : '';
				let rateId = "ahamove-rate-" + i;
				let rate = jQuery('<div class="ahamove-shipping-rate"> ' +
					'<label class="radio inline" for="' + rateId + '">' +
					'<input type="radio" class="ahamove verified" id="' + rateId + '" name="shipping_rate_id" data-rate-value="'
					+ rateElement.rate + '" value="' + rateElement.value + '" ' + checked
					+ ' onclick="javascript:onestepCheckoutProcess(this.name,\'ahamove\');">' +
					'<span>AHAMOVE (' + rateElement.text + ')</span>' +
					'<span class="rate-value"> ' +
					number_format(rateElement.rate, redSHOP.RSConfig._('PRICE_DECIMAL'), redSHOP.RSConfig._('PRICE_SEPERATOR'), redSHOP.RSConfig._('THOUSAND_SEPERATOR')) +
					'</span></label></div>');

				rate.appendTo($shippingContainer);
			}
		} else if (!jQuery('input.ahamove:checked').hasClass('unverified')) {
			let rateElement = rates[0];
			let rate = jQuery('<label class="radio inline" for="ahamove-rate-0">' +
				'<input type="radio" class="ahamove unverified" id="ahamove-rate-0" name="shipping_rate_id" data-rate-value="'
				+ rateElement.rate + '" value="' + rateElement.value + '" '
				+ ' checked onclick="javascript:onestepCheckoutProcess(this.name,\'ahamove\');">' +
				'<span>' + rateElement.text + '</span></label>');

			rate.appendTo($shippingContainer);
		}

		jQuery('#ahamove-rate-0').trigger('onclick');
	}

	function calculateShippingFee(userInfoId, lat, lng, address) {
		var customerInfo = {
			location: {
				lat: lat,
				lng: lng,
				address: address,
				address_remark: ''
			},
			address: address,
			name: "Nguyen Van A",
			phone: "0985507863"
		};

		var requestParams = {
			customer: customerInfo,
			delivery_schedule: {
				date: jQuery('[name="delivery_date"]').val(),
				time: jQuery('[name=delivery_time]').val()
			},
			stateSelected: jQuery('#rs_state_state_code').val(),
			users_info_id: userInfoId,
			payment_method: jQuery('[name="payment_method_id"]:checked').val()
		};

		requestParams[redSHOP.RSConfig._('AJAX_TOKEN')] = "1";

		sendEstimateOrderFee(requestParams);
	}

	function sendEstimateOrderFee(requestParams) {
		AHAMOVE = window.AHAMOVE || {};

		jQuery.ajax({
			type: 'POST',
			dataType: 'json',
			cache: false,
			url: redSHOP.RSConfig._('SITE_URL')
				+ "index.php?option=com_ajax&plugin=AhamoveGetShippingFee&group=redshop_shipping&format=raw",
			data: requestParams,
			beforeSend: function() {
				addLoadingAnimation();
			},
			success: function(data) {
				if (data.success === 1) {
					showShippingRates(data.ratesList);
				}
				else {
					showShippingRates(AHAMOVE.rate_unverified, false);
					alert(data['service_error']['error_msg']);

					var formValidatorSettings = jQuery('#adminForm').validate().settings;

					jQuery.extend(true, formValidatorSettings, {
						messages: {
							shipping_rate_id: {
								ahamove: data['service_error']['debug_msg']
							}
						}
					});
				}
			},
			error:function (xhr, ajaxOptions, thrownError){
				console.log(thrownError);
				jQuery('input[name="geocoding_location"]').val('');
			},
			complete: function(){
				jQuery('.ahamove-spinner').remove();
				jQuery('#__msg_overlay').remove();
			}
		});
	}

	function customizeFormValidationRules () {
		var $ = jQuery.noConflict();

		$.validator.addMethod("location_valid_ahamove", function (location, element) {
			return this.optional(element) || $(element).attr('address_verified') === 'yes';
		}, '');

		$.validator.addMethod("phone", function (phone_number, element) {
			return this.optional(element) || phone_number.match(/^0?(2|[35789])[0-9]{8}$|^02[48][0-9]{8}$/);
		}, '');

		$.validator.addMethod("ahamove", function (rate, element) {
			return this.optional(element) || $('.ahamove[name="shipping_rate_id"]:checked').hasClass('verified');
		}, '');

		var settings = $('#adminForm').validate().settings;

		$.extend(true, settings, {
			rules: {
				address: {
					required: true,
					location_valid_ahamove: {
						depends: function (element) {
							return $('[name="shipping_rate_id"]:checked').hasClass('ahamove');
						}
					}
				},
				address_ST: {
					location_valid_ahamove: {
						depends: function (element) {
							return $('[name="shipping_rate_id"]:checked').hasClass('ahamove');
						}
					}
				},
				phone: {
					phone: true
				},
				phone_ST: {
					phone: true
				},
				shipping_rate_id: {
					ahamove: {
						depends: function (element) {
							var addressIsNotEmpty = $("#billisship").is(":checked") ? $("[name='address']").is(":filled") : $("[name='address_ST']").is(":filled");
							return $('[name="shipping_rate_id"]:checked').hasClass('ahamove') && addressIsNotEmpty;
						}
					}
				}
			},
			messages: {
				address: {
					required: Joomla.JText._('COM_REDSHOP_YOUR_MUST_PROVIDE_A_ADDRESS'),
					location_valid_ahamove: Joomla.JText._('PLG_REDSHOP_SHIPPING_CAN_NOT_VERIFY_YOUR_ADDRESS')
				},
				address_ST: {
					billingRequired: Joomla.JText._('COM_REDSHOP_YOUR_MUST_PROVIDE_A_ADDRESS'),
					location_valid_ahamove: Joomla.JText._('PLG_REDSHOP_SHIPPING_CAN_NOT_VERIFY_YOUR_ADDRESS')
				},
				firstname_ST: {
					billingRequired: Joomla.JText._('COM_REDSHOP_YOUR_MUST_PROVIDE_A_FIRSTNAME')
				},
				lastname_ST: {
					billingRequired: Joomla.JText._('COM_REDSHOP_YOUR_MUST_PROVIDE_A_LASTNAME')
				},
				phone: {
					required: Joomla.JText._('COM_REDSHOP_YOUR_MUST_PROVIDE_A_VALID_PHONE'),
					phone: Joomla.JText._('PLG_REDSHOP_SHIPPING_GETQUOTATION_ERROR_INVALID_PHONE_NUMBER')
				},
				phone_ST: {
					required: Joomla.JText._('COM_REDSHOP_YOUR_MUST_PROVIDE_A_VALID_PHONE'),
					billingRequired: Joomla.JText._('PLG_REDSHOP_SHIPPING_GETQUOTATION_ERROR_INVALID_PHONE_NUMBER'),
					phone: Joomla.JText._('PLG_REDSHOP_SHIPPING_GETQUOTATION_ERROR_INVALID_PHONE_NUMBER')
				},
				shipping_rate_id: {
					ahamove: Joomla.JText._('PLG_REDSHOP_SHIPPING_INVALID_SHIPPING_RATE')
				}
			},
			errorPlacement: function(error, element) {
				if ((element.is(":radio") && element.attr('name') == "payment_method_id")) {
					error.appendTo('#divPaymentMethod .panel-heading:first');
				} else if (element.is(":checkbox") && element.attr('name') == "termscondition") {
					error.insertAfter(element.parent());
				}
				else if (element.is(":radio") && element.attr('name') == "shipping_rate_id" && element.hasClass('ahamove')) {
					error.insertAfter(element.parent());
				}
				else { // This is the default behavior
					error.insertAfter(element);
				}
			}
		});
	}

	function addLoadingAnimation() {
		jQuery('<div class="ahamove-spinner" style="position: fixed; z-index: 1031; top: 50%; left: 50%; transform: translate(-50%, -50%)"><img src="'
			+ redSHOP.RSConfig._('SITE_URL') + 'plugins/redshop_shipping/ahamove/assets/spinner.gif'
			+ '"></div>').appendTo('body');

		jQuery('<div id="__msg_overlay">').css({
			"width" : "100%"
			, "height" : "100%"
			, "background" : "#000"
			, "position" : "fixed"
			, "top" : "0"
			, "left" : "0"
			, "zIndex" : "50"
			, "opacity" : 0.2
		}).appendTo('body');
	}
	
});
