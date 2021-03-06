<?php
/**
 * @package     RedSHOP
 * @subpackage  Plugin
 *
 * @copyright   Copyright (C) 2008 - 2015 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');

/**
 * Joomla! System Logging Plugin
 *
 * @package        Joomla
 * @subpackage     System
 */
JLoader::import('redshop.library');

class plgredshop_shippingself_pickup extends JPlugin
{
	var $payment_code = "self_pickup";
	var $classname = "self_pickup";

	function onShowconfig()
	{
		return true;
	}

	function onWriteconfig($values)
	{
		return true;
	}

	function onListRates(&$d)
	{
		$shippinghelper = shipping::getInstance();
		$shipping = $shippinghelper->getShippingMethodByClass($this->classname);
		$shipping_location = $shippinghelper->getShippingRates($shipping->element);

		$shippingrate = [];
		$rate = 0;

		if (count($shipping_location) > 0)
		{
			for ($i = 0, $in = count($shipping_location); $i < $in; $i++)
			{
				$rs = $shipping_location[$i];
				$shipping_rate_id = \RedshopShippingRate::encrypt(
									array(
										__CLASS__,
										$shipping->name,
										$rs->shipping_rate_name,
										number_format(0, 2, '.', ''),
										$rs->shipping_rate_id,
										'single',
										'0',
									)
								);

				$shippingrate[$rate] = new stdClass;
				$shippingrate[$rate]->text = JText::_($rs->shipping_rate_name);
				$shippingrate[$rate]->value = $shipping_rate_id;
				$shippingrate[$rate]->rate = 0;
				$shippingrate[$rate]->vat = 0;
				$shippingrate[$rate]->shipping_rate_country = $rs->shipping_rate_country;
				$shippingrate[$rate]->shipping_rate_state = $rs->shipping_rate_state;

				$rate++;
			}
		}
		else
		{
			$shipping_rate_id = \RedshopShippingRate::encrypt(
									array(
										__CLASS__,
										$shipping->name,
										$shipping->name,
										number_format(0, 2, '.', ''),
										$shipping->name,
										'single',
										'0'
									)
								);

			$shippingrate[$rate] = new stdClass;
			$shippingrate[$rate]->text = JText::_($shipping->name);
			$shippingrate[$rate]->value = $shipping_rate_id;
			$shippingrate[$rate]->rate = 0;
			$shippingrate[$rate]->vat = 0;
			$rate++;
		}

		return $shippingrate;
	}
}
