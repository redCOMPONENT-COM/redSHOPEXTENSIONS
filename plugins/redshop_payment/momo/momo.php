<?php

/**
 * @package     RedSHOP
 * @subpackage  Plugin
 *
 * @copyright   Copyright (C) 2008 - 2017 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
defined('_JEXEC') or die;

class PlgRedshop_PaymentMomo extends \RedshopPayment
{
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
		if ($element != 'momo')
		{
			return;
		}

		$jsonResult = $this->createTransaction($data['order']->order_total, $data['order_id']);

		JFactory::getApplication()->redirect($jsonResult['payUrl']);
	}

	protected function preparePaymentInput($orderInfo)
	{
	}

	private function createTransaction($orderTotal, $orderId)
	{
		$partnerCode = $this->params->get('partnerCode');
		$accessKey   = $this->params->get('accessKey');
		$secretKey   = $this->params->get('secretKey');

		$orderInfo   = "Order: " . $orderId;
		$amount      = number_format($orderTotal, 0, "", "");
		$ipnUrl      = $this->getNotifyUrl($orderId);
		$redirectUrl = $this->getNotifyUrl($orderId);
		$extraData   = "";

		$requestId   = time() . "";
		$requestType = "captureWallet";

		//before sign HMAC SHA256 signature
		$rawHash   = "accessKey=" . $accessKey . "&amount=" . $amount . "&extraData=" . $extraData . "&ipnUrl=" . $ipnUrl . "&orderId=" . $orderId . '-' . $requestId . "&orderInfo=" . $orderInfo . "&partnerCode=" . $partnerCode . "&redirectUrl=" . $redirectUrl . "&requestId=" . $requestId . "&requestType=" . $requestType;
		$signature = hash_hmac("sha256", $rawHash, $secretKey);
		$data      = array(
			'partnerCode' => $partnerCode,
			'requestId'   => $requestId,
			'amount'      => $amount,
			'orderId'     => $orderId . '-' . $requestId,
			'orderInfo'   => $orderInfo,
			'redirectUrl' => $redirectUrl,
			'ipnUrl'      => $ipnUrl,
			'lang'        => $this->getLang(),
			'extraData'   => $extraData,
			'requestType' => $requestType,
			'signature'   => $signature
		);

		$result = $this->execPostRequest("/v2/gateway/api/create", json_encode($data));

		return json_decode($result, true);  // decode jsonson
	}

	private function execPostRequest($url, $data)
	{
		$endpoint = 'https://payment.momo.vn';

		if ($this->params->get('isTest'))
		{
			$endpoint = 'https://test-payment.momo.vn';
		}

		$ch = curl_init($endpoint . $url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json',
				'Content-Length: ' . strlen($data)
			)
		);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		$result = curl_exec($ch);
		curl_close($ch);

		return $result;
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
	public function onNotifyPaymentMomo($element, $request)
	{
		if ($element != 'momo')
		{
			return false;
		}

		$accessKey = $this->params->get('accessKey');
		$secretKey = $this->params->get('secretKey');

		$partnerCode  = $request["partnerCode"];
		$orderId      = $request["orderId"];
		$requestId    = $request["requestId"];
		$amount       = $request["amount"];
		$orderInfo    = $request["orderInfo"];
		$orderType    = $request["orderType"];
		$transId      = $request["transId"];
		$resultCode   = $request["resultCode"];
		$message      = $request["message"];
		$payType      = $request["payType"];
		$responseTime = $request["responseTime"];
		$extraData    = $request["extraData"];
		$m2signature  = $request["signature"]; //MoMo signature
		$errorCode    = $request["errorCode"];

		//Checksum
		$rawHash = "accessKey=" . $accessKey . "&amount=" . $amount . "&extraData=" . $extraData . "&message=" . $message . "&orderId=" . $orderId . "&orderInfo=" . $orderInfo .
			"&orderType=" . $orderType . "&partnerCode=" . $partnerCode . "&payType=" . $payType . "&payType=" . $payType . "&requestId=" . $requestId . "&responseTime=" . $responseTime .
			"&resultCode=" . $resultCode . "&transId=" . $transId;

		$partnerSignature = hash_hmac("sha256", $rawHash, $secretKey);

		if ($m2signature == $partnerSignature)
		{
			if ($errorCode == '0')
			{
				return $this->setStatus(
					$orderId,
					$transId,
					$this->params->get('verify_status', ''),
					'Paid',
					JText::_('PLG_REDSHOP_PAYMENT_MOMO_PAYMENT_SUCCESS'),
					JText::_('PLG_REDSHOP_PAYMENT_MOMO_PAYMENT_SUCCESS_LOG')
				);
			}
		}

		return $this->setStatus(
			$orderId,
			$transId,
			$this->params->get('cancel_status', ''),
			'Unpaid',
			JText::_('PLG_REDSHOP_PAYMENT_MOMO_PAYMENT_REJECTED'),
			JText::_('PLG_REDSHOP_PAYMENT_MOMO_PAYMENT_REJECTED_LOG')
		);
	}
}
