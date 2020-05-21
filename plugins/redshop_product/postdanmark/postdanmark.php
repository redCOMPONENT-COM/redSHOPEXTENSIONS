<?php
/**
 * @package     RedSHOP
 * @subpackage  Plugin
 *
 * @copyright   Copyright (C) 2008 - 2015 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

/**
 * Update Shipping information for PostDanmark Shipping Plugin
 *
 * @since  1.3.3.1
 */
class PlgRedshop_ProductPostDanmark extends JPlugin
{
	public function onBeforeUserShippingStore($orderUser, $orderResult)
	{
		if (null == $orderResult->shop_id) {
			return;
		}

		$orderShippingInfo = Redshop\Shipping\Rate::decrypt($orderResult->ship_method_id);

		if ('plgredshop_shippingpostdanmark' != strtolower($orderShippingInfo[0])) {
			return;
		}

		$locationInfo = explode("|", trim($orderResult->shop_id));

		if (count($locationInfo) <= 0) {
			return;
		}
		$companyName = 'ServicePointID:' . $locationInfo[0] . ':PostDanmark';

		$data = [
			'company_name' => $companyName,
			'firstname'    => $locationInfo[1],
			'lastname'     => '',
			'address'      => $locationInfo[2],
			'city'         => $locationInfo[4],
			'zipcode'      => $locationInfo[3]
		];

		$orderUser->bind($data);
	}
}
