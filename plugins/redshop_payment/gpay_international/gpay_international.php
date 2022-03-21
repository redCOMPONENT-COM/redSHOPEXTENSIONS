<?php

/**
 * @package     RedSHOP
 * @subpackage  Plugin
 *
 * @copyright   Copyright (C) 2008 - 2022 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
defined('_JEXEC') or die;

class PlgRedshop_PaymentGpay_international extends \RedshopPayment
{
	private $accessToken = "";


	/**
	 * This method will be triggered on before placing order to authorize or charge credit card
	 *
	 * @param   string  $element  Name of the payment plugin
	 * @param   array   $data     Cart Information
	 *
	 * @return  void  Authorize or Charge success or failed message and transaction id
	 * @throws  Exception
	 * @since   1.0
	 */
	public function onPrePayment($element, $data)
	{
		if ($element != 'gpay_international')
		{
			return;
		}

		$this->getToken();

		$jsonResult = $this->createTransaction($data['carttotal'], $data['order_id']);

		JFactory::getApplication()->redirect($jsonResult->response->order_url);
	}

	private function getSignature($data)
	{
		$private_key_pem = openssl_pkey_get_private($this->params->get('privateKey')
		);

		openssl_sign($data, $binary_signature, $private_key_pem, OPENSSL_ALGO_SHA256);

		return base64_encode($binary_signature);
	}

	private function getToken()
	{
		$merchantCode = $this->params->get('merchantCode');
		$password     = $this->params->get('password');

		$data = array(
			'merchant_code' => $merchantCode,
			'password'      => $password
		);

		$result = $this->execPostRequest("authentication/token/create", $data);

		$this->accessToken = $result->response->token;
	}

	private function createTransaction($orderTotal, $orderId)
	{
		$amount = number_format($orderTotal, 0, "", "");

		$merchantCode = $this->params->get('merchantCode');
		$orderTime    = time();
		$gOrderId     = base64_encode($orderId . '#' . $orderTime);

		$rawHash     = "merchant_code=" . $merchantCode . "&order_id=" . $gOrderId . "&order_amt=" . $amount;
		$signature   = $this->getSignature($rawHash);
		$callbackUrl = $this->getNotifyUrl($gOrderId);

		$data = array(
			'merchant_code'        => $merchantCode,
			'order_id'             => $gOrderId,
			'order_amt'            => (int) $amount,
			'order_currency'       => Redshop::getConfig()->get('CURRENCY_CODE'),
			'order_description'    => "Order: " . $orderId,
			'order_time'           => $orderTime,
			'callback_url'         => $callbackUrl,
			'webhook_url'          => $callbackUrl,
			'source_of_funds_type' => 'INTERNATIONAL_CARD',
			'language'             => $this->getLang(),
			'service_code'         => 'PAYMENTGATEWAY',
			'signature'            => $signature
		);

		return $this->execPostRequest("intlpayment/order/create", $data);
	}

	private function execPostRequest($url, $data, $method = "POST")
	{
		$endpoint = 'https://mpa.g-pay.vn/api/v3/';

		if ($this->params->get('isTest'))
		{
			$endpoint = 'https://sandbox-mpa.g-pay.vn/api/v3/';
		}

		$ch = curl_init($endpoint . $url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

		if ($method === "POST")
		{
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
		}

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt(
			$ch,
			CURLOPT_HTTPHEADER,
			array(
				'Signature ' . $this->getSignature($data),
				'Accept: application/json',
				'Content-Type: application/json',
				'Authorization: Bearer ' . $this->accessToken
			)
		);


		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		$result = curl_exec($ch);

		curl_close($ch);

		return json_decode($result);
	}


	/**
	 * Notify payment
	 *
	 * @param   string  $element  Name of plugin
	 * @param   array   $request  HTTP request data
	 *
	 * @return  object  Contains the information of order success of falier in object
	 * @since   1.0
	 */
	public function onNotifyPaymentGpay_international($element, $request)
	{
		if ($element != 'gpay_international')
		{
			return false;
		}

		$merchantCode = $this->params->get('merchantCode');
		$gpayTransId  = $request['gpay_trans_id'];

		$rawHash     = "merchant_code=" . $merchantCode . "&gpay_trans_id=" . $gpayTransId;
		$signature = $this->getSignature($rawHash);

		$data = array(
			'merchant_code' => $merchantCode,
			'gpay_trans_id' => $gpayTransId,
			'signature'     => $signature
		);

		$this->getToken();

		$result = $this->execPostRequest("intlpayment/order/query", $data);

		$redshopOrderId = explode('#', base64_decode($result->response->order_id));

		if ($result->response->order_status == "ORDER_SUCCESS")
		{
			$values = $this->setStatus(
				$redshopOrderId[0],
				$result->response->gpay_trans_id,
				$this->params->get('verify_status', ''),
				'Paid',
				JText::_('PLG_REDSHOP_PAYMENT_GPAY_INTERNATIONAL_PAYMENT_SUCCESS'),
				json_encode($result)
			);

			if (isset($request['ipn']) && $request['ipn'])
			{
				$values->log = $values->log . ' IPN';
				//Change order status
				RedshopHelperOrder::changeOrderStatus($values);
			}

			return $values;
		}

		return $this->setStatus(
			$redshopOrderId[0],
			$result->response->gpay_trans_id,
			$this->params->get('invalid_status', ''),
			'Unpaid',
			JText::_('PLG_REDSHOP_PAYMENT_GPAY_INTERNATIONAL_PAYMENT_REJECTED'),
			json_encode($result)
		);
	}

	protected function preparePaymentInput($orderInfo)
	{
	}

}
