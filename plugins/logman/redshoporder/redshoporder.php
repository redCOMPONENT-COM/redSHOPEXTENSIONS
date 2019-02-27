<?php
/**
 * @package    LOGman
 * @copyright  Copyright (C) 2008 - 2015 redCOMPONENT.com. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

/**
 * redSHOP LOGman plugin.
 *
 * @package  Joomlatools\Plugin\LOGman
 *
 * @since    1.0.0
 */
class PlgLogmanRedshoporder extends ComLogmanPluginJoomla
{
	/**
	 * Trigger after saved configuration
	 *
	 * @param   JRegistry  $config  Configuration
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function onAfterUpdateBillingAdd($data)
	{
		$name = JText::_('COM_REDSHOP_REDSHOP');
		$type = 'category';
		$url  = 'option=com_redshop&view=order_detail&task=edit&cid=' . $data->order_id;
		$megaData = array(
			'data' => $data,
			'type' => 'billingAddress',
		);

		$this->setLog($type, $data->order_id, $megaData, 'changed', $data->order_id, $url);
	}

	public function onAfterUpdateShippingRates($data)
	{
		$type = 'category';
		$url  = 'option=com_redshop&view=order_detail&task=edit&cid=' . $data->order_id;
		$megaData = array(
			'data' => $data,
			'type' => 'shippingRate',
		);

		$this->setLog($type, $data->order_id, $megaData, 'changed', $data->order_id, $url);
	}

	public function onAfterOrderStatusUpdate($data, $newstatus)
	{
		$type = 'category';
		$url  = 'option=com_redshop&view=order_detail&task=edit&cid=' . $data->order_id;
		$megaData = array(
			'data' => $data,
			'type' => 'updateOrderStatus',
		);

		$this->setLog($type, $data->order_id, $megaData, 'changed', $data->order_id, $url);
	}

	public function onAfterUpdateShippingAdd($data)
	{
		$type = 'category';
		$url  = 'option=com_redshop&view=order_detail&task=edit&cid=' . $data['order_id'];
		$megaData = array(
			'data' => $data,
			'type' => 'updateShippingAdd',
		);

		$this->setLog($type, $data['order_id'], $megaData, 'changed', $data['order_id'], $url);
	}

	public function onAfterUpdateOrderItem($data)
	{
		$type = 'category';
		$url  = 'option=com_redshop&view=order_detail&task=edit&cid=' . $data['order_item_id'];
		$megaData = array(
			'data' => $data,
			'type' => 'updateOrderItem',
		);

		$this->setLog($type, $data['order_item_id'], $megaData, 'changed', $data['order_item_id'], $url);
	}

	public function onAfterUpdateDiscount($data)
	{
		$type = 'category';
		$url  = 'option=com_redshop&view=order_detail&task=edit&cid=' . $data->order_id;
		$megaData = array(
			'data' => $data,
			'type' => 'updateOrderDiscount',
		);

		$this->setLog($type, $data->order_id, $megaData, 'changed', $data->order_id, $url);
	}

	public function  onAfterUpdateSpecialDiscount($data)
	{
		$type = 'category';
		$url  = 'option=com_redshop&view=order_detail&task=edit&cid=' . $data->order_id;
		$megaData = array(
			'data' => $data,
			'type' => 'updateSpecialDiscount',
		);

		$this->setLog($type, $data->order_id, $megaData, 'changed', $data->order_id, $url);
	}

	public function onAfterAddNewOrderItem($data)
	{
		$type = 'category';
		$url  = 'option=com_redshop&view=order_detail&task=edit&cid=' . $data->order_id;
		$megaData = array(
			'data' => $data,
			'type' => 'updateAddNewOrderItem',
		);

		$this->setLog($type, $data->order_id, $megaData, 'added', $data->order_id, $url);
	}

	/**
	 * Get set log
	 *
	 * @param   string  $type
	 * @param   string  $name
	 * @param   array   $data
	 * @param   string  $resultt
	 * @param   string  $uid
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	private function setLog($type, $name, $megaData, $result, $id, $url = '')
	{
		$user = JFactory::getUser();
		$this->getObject('com://admin/logman.controller.activity')
			->log(
				array(
					'object' => array(
						'package' => $this->_getPackage(),
						'type'    => $type,
						'id'      => $id,
						'name'    => $name,
						'url' => array(
							'admin' =>  $url,
						),
						'metadata'=> $megaData,

					),
					'verb'   => 'save',
					'actor'  => $user->id,
					'result' => $result
				)
			);
	}

	/**
	 * Get package
	 *
	 * @return  string
	 *
	 * @since  1.0.0
	 */
	private function _getPackage()
	{
		return 'redshoporder';
	}

	/**
	 * Get unique ID
	 *
	 * @param   mixed  $args  Args
	 *
	 * @return  string
	 *
	 * @since   1.0.0
	 */
	private function _getUniqueId ($args)
	{
		return md5(serialize($args) . serialize(JFactory::getUser()));
	}
}
