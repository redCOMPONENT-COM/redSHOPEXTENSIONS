<?php

/**
 * @package     RedSHOP
 * @subpackage  Plugin
 *
 * @copyright   Copyright (C) 2008 - 2017 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
defined('_JEXEC') or die;
define('DS', str_replace('\\', '/', DIRECTORY_SEPARATOR));
define('ROOT_PATH', dirname(__FILE__));

require_once 'library/Crypt/RSA.php';


class plgRedshop_PaymentAlepay extends JPlugin
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
	 * @param string $element Name of the payment plugin
	 * @param array  $data    Cart Information
	 *
	 * @return  object  Authorize or Charge success or failed message and transaction id
	 */
	public function onPrePayment($element, $data)
	{
		if ($element != 'alepay') {
			return;
		}

		$app         = JFactory::getApplication();
		$itemId      = $app->input->getInt('Itemid', 0);
		$orderInfo   = $data['order'];
		$billingInfo = $data['billinginfo'];
		$dataPost = [];

		$dataPost['orderCode']        = $orderInfo->order_id;
		$dataPost['amount']           = $orderInfo->order_total;
		$dataPost['orderDescription'] = "Thanh toan đơn hàng " . $data['order_id'];
		$dataPost['currency']         = $this->params->get('currency');
		$dataPost['totalItem']        = $data['order_quantity'];

		$dataPost['returnUrl'] = JURI::base(
			) . 'index.php?tmpl=component&option=com_redshop&view=order_detail&controller=order_detail&task=notify_payment&payment_plugin=Alepay&orderid=' . $data['order_id'] . '&accept=1&Itemid=' . $itemId;
		$dataPost['cancelUrl'] = JURI::base(
			) . "index.php?tmpl=component&option=com_redshop&view=order_detail&controller=order_detail&"
			. "task=notify_payment&payment_plugin=Alepay&Itemid=$itemId&orderid=" . $data['order_id'];

		$dataPost['buyerName']    = $billingInfo->lastname . $billingInfo->firstname;
		$dataPost['buyerEmail']   = !empty($billingInfo->user_email) ? $billingInfo->user_email : $billingInfo->email;
		$dataPost['buyerPhone']   = $billingInfo->phone;
		$dataPost['buyerAddress'] = $billingInfo->address;
		$dataPost['buyerCity']    = !empty($billingInfo->city) ? $billingInfo->city : 'Viet Nam';
		$dataPost['buyerCountry'] = $billingInfo->country_code;

		/*
			0: Cho phép thanh toán ngay với thẻ quốc tế và trả góp
			1: chỉ thanh toán ngay với thẻ quốc tế
			2: Chỉ thanh toán trả góp
			3: Thanh toán ngay với thẻ ATM, IB, QRCODE, thẻ quốc tế và thanh toán trả góp nếu thiết lập
			allowDomestic = true
		 * */
		$dataPost['checkoutType'] = 4;
		$dataPost['allowDomestic'] = $this->params->get('allow_domestic') == 1 ? true : false;

		$result = $this->sendRequestToAlepay($dataPost, 'checkout/v1/request-order');

		if ($result->errorCode === '000') {
			$dataDecrypted = $this->decryptData($result->data, $this->params->get('encrypt_key'));
			$dataDecrypted = json_decode($dataDecrypted);
			$linkCheckout = $dataDecrypted->checkoutUrl;
			$lang = JFactory::getLanguage();

			if ($lang->getTag() == 'en-GB')
			{
				$linkCheckout = str_replace('/vi', '/eng', $linkCheckout);
			}

			$this->app->redirect($linkCheckout);
		} else {
			$this->app->enqueueMessage($result->errorDescription, 'error');

			$link = JRoute::_(
				'index.php?tmpl=component&option=com_redshop&view=order_detail&controller=order_detail&task=notify_payment&payment_plugin=Alepay&accept=0&orderid=' . $data['order_id'] . '&Itemid=' . $itemId
			);

			$this->app->redirect($link);
		}
	}

	private function sendRequestToAlepay($data, $url)
	{
		$link = 'https://alepay.vn/';

		if ($this->params->get('isTest')) {
			$link = 'https://alepay-sandbox.nganluong.vn/';
		}

		$dataEncrypt = $this->encryptData(json_encode($data), $this->params->get('encrypt_key'));

		$checksum = md5($dataEncrypt . $this->params->get('checksum'));
		$post     = [
			'token'    => $this->params->get('token'),
			'data'     => $dataEncrypt,
			'checksum' => $checksum
		];

		$dataString = json_encode($post);

		$ch = curl_init($link . $url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt(
			$ch,
			CURLOPT_HTTPHEADER,
			array(
				'Content-Type: application/json',
				'Content-Length: ' . strlen($dataString)
			)
		);
		$result = curl_exec($ch);

		return json_decode($result);
	}

	private function decryptData($data, $publicKey)
	{
		$rsa = new Crypt_RSA();
		$rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1);
		$cipherText = base64_decode($data);
		$rsa->loadKey($publicKey); // public key

		return $rsa->decrypt($cipherText);

		// $outPut = $rsa->decrypt($data);
		return $outPut;
	}


	private function encryptData($data, $publicKey)
	{
		$rsa = new Crypt_RSA();
		$rsa->loadKey($publicKey); // public key
		$rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1);
		$output = $rsa->encrypt($data);

		return base64_encode($output);
	}

	/**
	 * Notify payment
	 *
	 * @param string $element Name of plugin
	 * @param array  $request HTTP request data
	 *
	 * @return  object  Contains the information of order success of falier in object
	 */
	public function onNotifyPaymentAlepay($element, $request)
	{
		if ($element != 'Alepay') {
			return;
		}

		$values         = new stdClass;
		$verify_status  = $this->params->get('verify_status', '');
		$invalid_status = $this->params->get('invalid_status', '');;

		if ($request['accept'] && isset($request['checksum'])) {
			$callbackData = json_decode(
				$this->decryptCallbackData($request['data'], $this->params->get('encrypt_key'))
			);
			//		$transactionInfo = $this->getTransactionInfo($callbackData->data);

			if (in_array($callbackData->errorCode, array('000', '150'))) {
				$values->order_status_code         = $verify_status;
				$values->order_payment_status_code = 'Paid';
				$values->log                       = JText::_('PLG_RS_PAYMENT_ALEPAY_ORDER_PLACED');
				$values->msg                       = JText::_('PLG_RS_PAYMENT_ALEPAY_ORDER_PLACED');
			} else {
				$values->order_status_code         = $invalid_status;
				$values->order_payment_status_code = 'Unpaid';
				$values->log                       = JText::_('PLG_RS_PAYMENT_ALEPAY_NOT_PLACED');
				$values->msg                       = JText::_('PLG_RS_PAYMENT_ALEPAY_NOT_PLACED');
				$values->type                      = 'error';
			}
		} else {
			$values->order_status_code         = $invalid_status;
			$values->order_payment_status_code = 'Unpaid';
			$values->log                       = JText::_('PLG_RS_PAYMENT_ALEPAY_NOT_PLACED');
			$values->msg                       = JText::_('PLG_RS_PAYMENT_ALEPAY_NOT_PLACED');
			$values->type                      = 'error';
		}

		$values->order_id = $request['orderid'];

		return $values;
	}

	private function decryptCallbackData($data, $publicKey)
	{
		$decoded = base64_decode($data);

		return $this->decryptData($decoded, $publicKey);
	}

	public function getTransactionInfo($transactionCode)
	{
		// demo data
		$data   = array('transactionCode' => $transactionCode);
		$result = $this->sendRequestToAlepay($data, 'checkout/v1/get-transaction-info');

		if ($result->errorCode == '000') {
			$dataDecrypted = $this->decryptData($result->data, $this->params->get('encrypt_key'));

			return json_decode($dataDecrypted);
		} else {
			return $result;
		}
	}
}
