<?php
/**
 * @package     RedSHOP.Backend
 * @subpackage  Template
 *
 * @copyright   Copyright (C) 2008 - 2021 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
defined('_JEXEC') or die;
JHTML::_('behavior.tooltip');

extract($displayData);

$address_note = $address['address_note'];
$cart         = RedshopHelperCartSession::getCart();
$token        = JSession::getFormToken();
?>

<div class="form-group ahamove delivery-address-container">
<!--	<input type="text" id="address-input">-->
	<div class="fieldline">
		<label for="address-input"><?php echo JText::_('COM_REDSHOP_ADDRESS'); ?> :</label>
		<input class="inputbox" type="text" id="address-input" autocomplete="off">
	</div>


	<div class="fieldline address_remark hidden">
		<label for="address_remark"><?php echo JText::_('COM_REDSHOP_ADDRESS_NOTE'); ?> :</label>
		<span id="address_remark" name="address_remark"></span>
	</div>
	<div class="delivery-address radio-group">
		<h4 class="title"></h4>
	</div>
	<button class="btn btn-success confirm-btn hidden" id="confirm-address-btn"><?php echo JText::_('COM_REDSHOP_CONFIRM'); ?></button>
</div>

<script type="text/javascript">
	var input_address = "<?php echo $address['full_address'] ?>",
		geocoder,
		service,
		address_remark = "<?php echo $address['address_note'] ?>";

	document.getElementById("address-input").value = input_address;

	function retrieveLatLong(element) {
		var $radio = jQuery(element);
		var place_id = $radio.attr('data-map-place-id');

		geocoder = new google.maps.Geocoder;

		geocoder.geocode( {'placeId': place_id}, function(results, status) {
			if (status === 'OK') {
				$radio.attr('data-map-lat', results[0].geometry.location.lat());
				$radio.attr('data-map-lng', results[0].geometry.location.lng());
				jQuery('#confirm-address-btn').removeClass('hidden');
			}
		});
	}

	jQuery(document).ready(function($) {
		showPredictions();
		var getPredictions;

		if (address_remark !== "") {
			$('#address_remark').text(address_remark);
			$('#address_remark').closest('.fieldline').removeClass('hidden');
		}

		$('.delivery-address-container')
		.on('keydown paste', '#address-input', function () {
			clearTimeout(getPredictions);
			getPredictions = setTimeout(function(){
				input_address = $('#address-input').val();
				showPredictions();
			}, 500);
		})
		.on('click', '#confirm-address-btn', function() {
			var $selectedAddress = $('[id^="address_ahamove"]:checked');
			var finalAddress = $selectedAddress.attr('data-map-address');
			var geocodingLocation = $selectedAddress.attr('data-map-lat') + ',' + $selectedAddress.attr('data-map-lng');

			if ($selectedAddress.length > 0) {
				var $users_info_id = window.parent.document.querySelector('input[name="users_info_id"]:checked');
				$users_info_id.setAttribute('data-map-address', finalAddress);
				$users_info_id.setAttribute('data-address-type', "<?php echo $address['address_type'] ?>");

				console.log(geocodingLocation);
				updateShippingAddress(finalAddress, geocodingLocation);
			}
		});

        function showPredictions() {
            if (input_address.trim() === '') {
                jQuery('.delivery-address .title').text("<?php echo JText::_('PLG_REDSHOP_SHIPPING_AHAMOVE_INPUT_ADDRESS_NOT_FOUND'); ?>");
                if (jQuery('.form-check.prediction')) jQuery('.form-check.prediction').remove();
                jQuery(window).colorbox.resize();
                return;
            }
            service = new google.maps.places.AutocompleteService();
            service.getPlacePredictions({
                input: input_address,
                componentRestrictions: {country: 'vn'},
                // bias search predictions to those places that are within a 40 km radius from Requester Contact location
                location: new google.maps.LatLng(<?php echo $requesterContact['location']['lat'] ?>, <?php echo $requesterContact['location']['lng'] ?>),
                radius: <?=$requesterContact['location']['radius']?>
            }, displaySuggestions);
        }

        function updateShippingAddress(fullAddress, geocodingLocation) {
            jQuery.ajax({
                type: 'POST',
                dataType: 'json',
                url: redSHOP.RSConfig._('SITE_URL')
                    + "index.php?option=com_ajax&plugin=AhamoveUpdateShippingAddress&group=redshop_shipping&format=raw",
                data: {
                    saving_address: fullAddress,
                    saving_location: geocodingLocation,
                    address_type: "<?php echo $address['address_type'] ?>",
                    users_info_id: <?php echo $usersInfoId ?>,
                    "<?php echo $token ?>": "1"
                },
                success: function(data) {
                    if (data.update_address_success && data.update_location_success) {
                        jQuery(window).colorbox.close();
                    }
                },
                error:function (xhr, ajaxOptions, thrownError){
                    console.log(thrownError);
                }
            });
        }
        function displaySuggestions(predictions, status) {
            jQuery('.delivery-address *').not('.title').remove();
            let i = 0;
console.log(google.maps.places.PlacesServiceStatus.OK);
            if (status != google.maps.places.PlacesServiceStatus.OK) {
                jQuery('.delivery-address .title').text("<?php echo JText::_('PLG_REDSHOP_SHIPPING_AHAMOVE_INPUT_ADDRESS_NOT_FOUND'); ?>");
                if (jQuery('.form-check.prediction')) jQuery('.form-check.prediction').remove();
                jQuery(window).colorbox.resize();
                return;
            }

            predictions.forEach(function(prediction) {
                $addr = jQuery('<div class="form-check prediction">' +
                    '<label class="form-check-label radio inline" for="address_ahamove'+ i + '">' +
                    '<input class="form-check-input" type="radio" name="address_ahamove" id="address_ahamove' + i + '" ' +
                    'value="" onclick="javascript:retrieveLatLong(this);" data-map-place-id="' + prediction.place_id +
                    '" data-map-address="' + prediction.description + '">' +
                    '<span>' + prediction.description + '</span>' +
                    '</label></div>');

                $addr.appendTo('.delivery-address');

                i++;
            });

            jQuery('#address_ahamove0').trigger('click');

            jQuery('.delivery-address .title').text("<?php echo JText::_('COM_REDSHOP_DELIVERY_CONFIRM_LABEL'); ?>");

            jQuery(window).colorbox.resize();
        }

    });
</script>
