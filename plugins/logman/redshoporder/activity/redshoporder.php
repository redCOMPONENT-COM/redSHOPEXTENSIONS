<?php
/**
 * @package    LOGman
 * @copyright  Copyright (C) 2008 - 2015 redCOMPONENT.com. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

/**
 * redSHOP LOGman plugin.
 *
 * @package  Joomlatools\Plugin\LOGman\Activity
 *
 * @since    1.0.0
 */
class PlgLogman\RedshoporderActivity\Redshoporder extends ComLogmanModelEntityActivity
{
	/**
	 * Init
	 *
	 * @param   KObjectConfig  $config  Config
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	protected function _initialize(KObjectConfig $config)
	{
		if (is_string($config->data['metadata']))
		{
			$type = json_decode($config->data['metadata'])->type;
			if ($type == 'billingAddress')
			{
				$config->append(
					array('format' => '{actor} {action} billing address with order id {object}')
				);
			}
			elseif ($type == 'shippingRate')
			{
				$config->append(
					array('format' => '{actor} {action} shipping rate with order id {object}')
				);
			}
			elseif ($type == 'updateOrderStatus')
			{
				$config->append(
					array('format' => '{actor} {action} order status with order id {object}')
				);
			}
			elseif ($type == 'updateShippingAdd')
			{
				$config->append(
					array('format' => '{actor} {action} shipping address with order id {object}')
				);
			}
			elseif ($type == 'updateOrderItem')
			{
				$config->append(
					array('format' => '{actor} {action} order item with order id {object}')
				);
			}
			elseif ($type == 'updateOrderDiscount')
			{
				$config->append(
					array('format' => '{actor} {action} order discount with order id {object}')
				);
			}
			elseif ($type == 'updateSpecialDiscount')
			{
				$config->append(
					array('format' => '{actor} {action} order special discount with order id {object}')
				);
			}
			elseif ($type == 'updateAddNewOrderItem')
			{
				$config->append(
					array('format' => '{actor} {action} new order item with order id {object}')
				);
			}
		}

		parent::_initialize($config);
	}
}