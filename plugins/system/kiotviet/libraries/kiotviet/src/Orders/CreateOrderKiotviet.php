<?php
namespace Kiotviet\Orders;

use LinkedIn\Exception;

class CreateOrderKiotviet extends Order
{
	public $alias = array(

	);

	public $branch = array();

	public function __construct($accessToken, $retailerName, $options = array())
	{
		$this->_options = $options;
		parent::__construct($accessToken, $retailerName, $options);
	}

	public function create($orderDetail, $orderRef)
	{
		$body = array(
			"CustomerId" => ''
		);

		$stateCode = $this->getUserInfo($orderDetail)->state_code;

		foreach ($this->_options->get('mapping_branch') as $key => $value)
		{
			if ($stateCode == $value->state)
			{
				$body['BranchId'] = $value->branch;
				break;
			}
		}

		$body['DeliveryDetail'] = $this->generateDeliveryDetail($orderDetail, $orderRef);
		$body['OrderDetails'] = $this->generateOrderDetail($orderDetail->order_id);

		$body['OrderDetails'][0]['Note'] = $this->getNoteProduct($orderDetail);
		$this->_headers['headers']['content-type'] = 'application/json';
		$request = array_merge($this->_headers, array('body' => json_encode($body)));

		try {
			$response = $this->_client->request('POST', 'orders', $request);

		} catch (Exception $e)
		{
			echo "<pre>";
			print_r($e->getMessage());
			echo "</pre>";
			die('fdsfds');
		}

		return $response->getBody()->getContents();
	}

	public function generateDeliveryDetail($orderDetail, $orderRef)
	{
		$return = array();
		$userInfo = $this->getUserInfo($orderDetail);
		$return['Receiver'] = $userInfo->firstname . ' ' . $userInfo->lastname;
		$return['ContactNumber'] = $userInfo->phone;
		$return['Address'] = $userInfo->address; //Địa chỉ nhận
		$return['LocationName'] = ''; // Tỉnh thành, quận huyện nhận
		$return['DeliveryCode'] = ''; // Mã vận đơn
		$return['ExpectedDelivery'] = $this->formatDate($orderDetail->cdate);

		if ($this->_options->get('use_lalamove'))
		{
			$strDate = $this->getFieldData($orderDetail->order_id, 'rs_lalamove_order_delivery_schedule');
			$return['DeliveryCode'] = $orderRef; // Mã vận đơn
			$return['ExpectedDelivery'] = date("c", $strDate);
		}

		$return['Status'] = 4; //Trạng thái GH(3: Chưa giao,4: Đang giao)
		$return['Price'] = $orderDetail->order_shipping;

		return $return;
	}

	public function formatDate($dateime)
	{
		$date = $dateime + (7 * 60 * 70);

		return gmdate("Y/m/d H:i:s",$date);
	}

	public function getUserInfo ($orderDetail)
	{
		$userId = $orderDetail->user_id;
		$usersInfoId = $orderDetail->user_info_id;

		if ($userId)
		{
			if ($usersInfoId)
			{
				$userInfo = \RedshopHelperShipping::getShippingAddress($usersInfoId);
			}
			else
			{
				$userInfo = \RedshopHelperOrder::getShippingAddress($userId);
			}
		}

		return $userInfo;
	}

	public function generateOrderDetail($orderId)
	{
		$orderItems = \RedshopEntityOrder::getInstance($orderId)->getOrderItems();
		$data = array();
		$i = 0;

		foreach ($orderItems as $orderItem)
		{
			$item = $orderItem->getItem();
			$data[$i]['ProductId'] = $this->getProductByCode($item->order_item_sku)->id;
			$data[$i]['Price'] = $item->product_item_price;
			$data[$i]['Quantity'] = $item->product_quantity;
			$data[$i]['Note'] = '';

			$i++;

			$accessories = \RedshopHelperOrder::getOrderItemAccessoryDetail($item->order_item_id);

			if (!empty($accessories))
			{
				foreach ($accessories as $key => $accessory)
				{
					$data[$i]['ProductId'] = $this->getProductByCode($accessory->order_acc_item_sku)->id;
					$data[$i]['Price'] = $accessory->product_acc_final_price;
					$data[$i]['Quantity'] = $accessory->product_quantity;
					$data[$i]['Note'] = 'Accessory';

					$i++;
				}

			}
		}

		return $data;
	}

	public function getNoteProduct($orderDetail)
	{
		$shippingDetail = \Redshop\Shipping\Rate::decrypt($orderDetail->ship_method_id);
		$typeShipping = $shippingDetail[0] == 'plgredshop_shippingself_pickup' ? 'Pickup' : 'Delivery';

		$payment = \RedshopEntityOrder_Payment::getInstance($orderDetail->order_id)->getItem();
		return \JText::_($payment->order_payment_name) . ' - ' . $orderDetail->order_payment_status . ' - ' . $typeShipping;
	}

	public function getProductByCode($code)
	{
		$response = $this->_client->request('GET', 'products/code/' . $code, $this->_headers);

		return json_decode($response->getBody()->getContents());
	}

	public function getFieldData($redshopOrderId, $fieldName)
	{
		$orderField = \RedshopEntityField::getInstanceByField('name', $fieldName);

		$db = \JFactory::getDbo();

		$query = $db->getQuery(true)
			->select('data_txt')
			->from($db->qn('#__redshop_fields_data'))
			->where($db->qn('fieldid') . ' = ' . $db->q($orderField->get('id')))
			->where($db->qn('itemid') . ' = ' . $db->q($redshopOrderId));

		$orderRef = $db->setQuery($query)->loadResult();

		return json_decode($orderRef);
	}
}