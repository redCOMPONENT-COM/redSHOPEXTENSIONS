<?php
/**
 * @package     RedSHOP
 * @subpackage  Plugin
 *
 * @copyright   Copyright (C) 2008 - 2017 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
defined('_JEXEC') or die;

class plgRedshop_PaymentVnpay extends JPlugin
{
	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 * @since  3.1
	 */
	protected $autoloadLanguage = true;

	/**
	 * Application object
	 *
	 * @var    JApplicationCms
	 * @since  3.2
	 */
	protected $app;

	/**
	 * This method will be triggered on before placing order to authorize or charge credit card
	 *
	 * @param   string  $element  Name of the payment plugin
	 * @param   array   $data     Cart Information
	 *
	 * @return  object  Authorize or Charge success or failed message and transaction id
	 */
	public function onPrePayment($element, $data)
	{
		if ($element != 'vnpay')
		{
			return;
		}

		$lang = JFactory::getLanguage()->getTag();

		switch ($lang)
		{
			case 'vi-VN': $vnp_Locale = 'vn';break;
			case 'en-GB': $vnp_Locale = 'en';break;
			default: $vnp_Locale = 'vn';
		}

		$app            = JFactory::getApplication();
		$itemId         = $app->input->getInt('Itemid', 0);
		$orderInfo      = $data['order'];
		$vnp_HashSecret = $this->params->get('secret_key');
		$vnp_Url        = "https://pay.vnpay.vn/vpcpay.html";

		if ($this->params->get('isTest'))
		{
			$vnp_Url = "http://sandbox.vnpayment.vn/paymentv2/vpcpay.html";
		}

		$inputData = array(
			"vnp_Version" => "2.0.0",
			"vnp_TmnCode" => $this->params->get('vnp_TmnCode'),
			"vnp_Amount" => $orderInfo->order_total * 100,
			"vnp_Command" => "pay",
			"vnp_CreateDate" => date('YmdHis'),
			"vnp_CurrCode" => "VND",
			"vnp_IpAddr" => $_SERVER['REMOTE_ADDR'],
			"vnp_Locale" => $vnp_Locale,
			"vnp_OrderInfo" => 'Thanh toan don hang: ' . $orderInfo->order_id,
			"vnp_ReturnUrl" => JURI::base() . 'index.php?tmpl=component&option=com_redshop&view=order_detail&controller=order_detail&task=notify_payment&payment_plugin=Vnpay&Itemid=' . $itemId,
			"vnp_TxnRef" => $orderInfo->order_id
		);

		ksort($inputData);
		$query    = "";
		$i        = 0;
		$hashdata = "";

		foreach ($inputData as $key => $value)
		{
			if ($i == 1)
			{
				$hashdata .= '&' . $key . "=" . $value;
			}
			else
			{
				$hashdata .= $key . "=" . $value;
				$i = 1;
			}

			$query .= urlencode($key) . "=" . urlencode($value) . '&';
		}

		$vnp_Url = $vnp_Url . "?" . $query;

		if (isset($vnp_HashSecret))
		{
			$vnpSecureHash = hash('sha256', $vnp_HashSecret . $hashdata);
			$vnp_Url .= 'vnp_SecureHashType=SHA256&vnp_SecureHash=' . $vnpSecureHash;
		}

		$this->app->redirect($vnp_Url);
	}

	/**
	 * Notify payment
	 *
	 * @param   string  $element  Name of plugin
	 * @param   array   $request  HTTP request data
	 *
	 * @return  object  Contains the information of order success of falier in object
	 */
	public function onNotifyPaymentVnpay($element, $request)
	{
		if ($element != 'Vnpay')
		{
			return;
		}

		if (isset($request['ipn']) && $request['ipn'])
		{
			echo $this->checkIPN($request);

			$this->app->close();
		}

		$orderId    = $request['vnp_TxnRef'];

		$values           = new stdClass;
		$values->order_id = $orderId;

		if ($this->checkSum($request))
		{
			if ($request['vnp_ResponseCode'] == '00')
			{
				$values->order_status_code         = $this->params->get('verify_status', 'C');
				$values->order_payment_status_code = 'Paid';
				$values->log                       = JText::_('PLG_REDSHOP_PAYMENT_VNPAY_ORDER_PLACED');
				$values->msg                       = JText::_('PLG_REDSHOP_PAYMENT_VNPAY_ORDER_PLACED');
			}
			else
			{
				$values->order_status_code         = $this->params->get('invalid_status', 'P');
				$values->order_payment_status_code = 'Unpaid';
				$values->log                       = JText::_('PLG_REDSHOP_PAYMENT_VNPAY_ORDER_NOT_PLACED');
				$values->msg                       = JText::_('PLG_REDSHOP_PAYMENT_VNPAY_ORDER_NOT_PLACED');
				$values->type                      = 'error';
			}
		}
		else
		{
			$values->order_status_code         = $this->params->get('invalid_status', 'P');
			$values->order_payment_status_code = 'Unpaid';
			$values->log                       = JText::_('PLG_REDSHOP_PAYMENT_VNPAY_ORDER_NOT_PLACED');
			$values->msg                       = JText::_('PLG_REDSHOP_PAYMENT_VNPAY_ORDER_NOT_PLACED');
			$values->type                      = 'error';
		}

		return $values;
	}

	public function checkSum($data)
	{
		$vnp_HashSecret = $this->params->get('secret_key');
		$vnp_SecureHash = $data['vnp_SecureHash'];
		$inputData = array();

		foreach ($data as $key => $value)
		{
			if (substr($key, 0, 4) == "vnp_") {
				$inputData[$key] = $value;
			}
		}

		unset($inputData['vnp_SecureHashType']);
		unset($inputData['vnp_SecureHash']);
		ksort($inputData);

		$i        = 0;
		$hashData = "";

		foreach ($inputData as $key => $value)
		{
			if ($i == 1)
			{
				$hashData = $hashData . '&' . $key . "=" . $value;
			}
			else
			{
				$hashData = $hashData . $key . "=" . $value;
				$i = 1;
			}
		}

		$secureHash = hash('sha256',$vnp_HashSecret . $hashData);

		if ($secureHash == $vnp_SecureHash)
		{
			return true;
		}

		return false;
	}

	public function checkIPN($data)
	{
		$orderId = $data['vnp_TxnRef'];
		$values           = new stdClass;
		$values->order_id = $orderId;

		try {
			if ($this->checkSum($data))
			{
				$order = RedshopEntityOrder::getInstance($orderId)->getItem();

				if (!empty($order))
				{
					if (!empty($order->order_status) && $order->order_status == 'P')
					{
						$returnData['RspCode'] = '00';
						$returnData['Message'] = 'Confirm Success';
					}
					else
					{
						$returnData['RspCode'] = '02';
						$returnData['Message'] = 'Order already confirmed';
					}
				}
				else
				{
					$returnData['RspCode'] = '01';
					$returnData['Message'] = 'Order not found';
				}
			}
			else
			{
				$returnData['RspCode'] = '97';
				$returnData['Message'] = 'Chu ky khong hop le';
			}
		} catch (Exception $e) {
			$returnData['RspCode'] = '99';
			$returnData['Message'] = 'Unknow error';
		}

		return json_encode($returnData);
	}
}
