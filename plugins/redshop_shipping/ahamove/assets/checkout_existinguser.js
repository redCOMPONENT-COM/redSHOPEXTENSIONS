redSHOP = window.redSHOP || {};
var updateShippingFeeOnScheduletimeChange = false;

jQuery(document).ready(function() {
	setTimeout(customizeFormValidationRules, 500);
	var $user_element = jQuery('[name=users_info_id]:checked');
	var paymentMethodOnclick;

	if (updateShippingFeeOnScheduletimeChange) {
		jQuery('body').on('change', '[name="delivery_date"], [name="delivery_time"]', function(e) {
			var $userChecked = jQuery('[name="users_info_id"]:checked');
			// TODO if need in future
		});
	}

	if (typeof $user_element != 'undefined') {
		var $ahamoveRateSelected = jQuery('input.ahamove:checked');

		if ($ahamoveRateSelected.hasClass('unverified')) {
			showAddressesPredictionsPopup($user_element);
		}
		else if ($ahamoveRateSelected.hasClass('verified')) {
			$user_element.trigger('onclick');
		}

		jQuery('body').on('click', '[name="payment_method_id"]', function (e) {
			clearTimeout(paymentMethodOnclick);

			paymentMethodOnclick = setTimeout(function () {
				if (jQuery('input[name="shipping_rate_id"]:checked').hasClass('ahamove')) {
					$user_element.trigger('onclick');
				}
			}, 500);
		});
	}
	function customizeFormValidationRules () {
		var $ = jQuery.noConflict();

		$.validator.addMethod("ahamove", function (rate, element) {
			return this.optional(element) || $('.ahamove[name="shipping_rate_id"]:checked').hasClass('verified');
		}, '');

		var settings = $('#adminForm').validate().settings;

		$.extend(true, settings, {
			rules: {
				shipping_rate_id: {
					ahamove: {
						depends: function (element) {
							return $('[name="shipping_rate_id"]:checked').hasClass('ahamove');
						}
					}
				}
			},
			messages: {
				shipping_rate_id: {
					ahamove: Joomla.JText._('PLG_REDSHOP_SHIPPING_AHAMOVE_INVALID_SHIPPING_RATE')
				}
			}
		});
	}

	function showAddressesPredictionsPopup($user) {
		if ($user.length === 0) return;

		var users_info_id = $user.val();
		var mapURL = redSHOP.RSConfig._('SITE_URL')
			+ "index.php?option=com_ajax&plugin=ShowMap&group=redshop_shipping&format=raw&"
			+ "&users_info_id=" + users_info_id + "&"
			+ redSHOP.RSConfig._('AJAX_TOKEN') + "=1";

		jQuery.colorbox({
			escKey: false, //escape key will not close
			overlayClose: false, //clicking background will not close
			closeButton: false, // hide the close button
			scrolling: false,
			className: "ahamove-address-confirm-box",
			overflow: "visible",
			width: "80%",
			href: function(){ return mapURL; },
			onOpen: function() {
				jQuery('<div id="__msg_overlay">').css({
					"width" : "100%"
					, "height" : "100%"
					, "background" : "#000"
					, "position" : "fixed"
					, "top" : "0"
					, "left" : "0"
					, "zIndex" : "50"
					, "opacity" : 0.6
				}).appendTo(document.body);
			},
			onComplete: function(){
				jQuery('#colorbox').css({"opacity": 1, "z-index": 100, "background-color": "#fff"});

				setTimeout(function(){
					jQuery(this).colorbox.resize();
				}, 1000);
			},
			onClosed: function() {
				// window.location.reload(false);
				jQuery('#__msg_overlay').remove();

				if ($user.attr('data-address-type') == 'BT') {
					var $billingAddress_addressText = jQuery('.redshop-billingaddresses .row .col-xs-7').eq(2);

					if ($billingAddress_addressText) {
						$billingAddress_addressText.text($user.attr('data-map-address'));
					}
				}

				$user.trigger('onclick');
			}
		});
	}

});