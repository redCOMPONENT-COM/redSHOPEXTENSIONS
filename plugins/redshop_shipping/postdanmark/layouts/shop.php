<?php
/**
 * @package     RedSHOP.Backend
 * @subpackage  Template
 *
 * @copyright   Copyright (C) 2008 - 2017 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
defined('_JEXEC') or die;

/**
 * Layout variables
 * ===========================
 * @var   array   $displayData Layout variables
 * @var   object  $shop        Shop data
 * @var   integer $key         Key
 * @var   integer $count       Count of shops
 */
extract($displayData);

++$key;
?>
    <td class="radio_point_container" onclick="selectMarker(<?php echo trim($shop->servicePointId) ?>)">
        <table class="point_table">
            <tr>
                <td class="radio_point">
					<?php if ($count === $key): ?>
                        <input type="radio" id="<?php echo trim($shop->servicePointId) ?>"
                               value="<?php echo trim($shop->servicePointId) ?>"
                               name="postdanmark_pickupLocation" class="radio validate-one-required-by-name"
                        />
					<?php else: ?>
                        <input type="radio" id="<?php echo trim($shop->servicePointId) ?>"
                               value="<?php echo trim($shop->servicePointId) ?>" name="postdanmark_pickupLocation"
                               class="radio"/>
					<?php endif; ?>
                    <strong><?php echo $key ?><strong>
                </td>
                <td class="point_info">
                    <strong><?php echo trim($shop->name) ?></strong>
                    <div class="postdanmark_address">
                        <span class="street"><?php echo trim($shop->deliveryAddress->streetName) ?></span>
                        <br/>
                        <span class="city"><?php echo trim($shop->deliveryAddress->city) ?></span>
                        <input type="hidden" class="service_postcode"
                               value="<?php echo trim($shop->deliveryAddress->postalCode) ?>"/>
                    </div>
                </td>
            </tr>
        </table>
    </td>
<?php if ($key % 4 == 0 && $key < $count): ?>
    </tr>
    <tr>
<?php endif;
