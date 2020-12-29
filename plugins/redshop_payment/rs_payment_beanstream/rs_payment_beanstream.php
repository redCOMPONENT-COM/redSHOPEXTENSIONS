<?php
/**
 * @package     RedSHOP
 * @subpackage  Plugin
 *
 * @copyright   Copyright (C) 2008 - 2015 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

JLoader::import('redshop.library');

class plgRedshop_paymentrs_payment_beanstream extends JPlugin
{
    public $values;
	/**
	 * Plugin method with the same name as the event will be called automatically.
	 */
	public function onPrePayment_rs_payment_beanstream($element, $data)
	{
		if ($element != 'rs_payment_beanstream')
		{
			return;
		}

		$session = JFactory::getSession();
		$creditCardData  = $session->get('ccdata');
		$config  = \Redconfiguration::getInstance();

		// For total amount
		$calNo = 2;

		if (\Redshop::getConfig()->get('PRICE_DECIMAL') != '')
		{
			$calNo = \Redshop::getConfig()->get('PRICE_DECIMAL');
		}

		$orderTotal             = round($data['order_total'], $calNo);
		$orderPaymentExpireYear = substr($creditCardData['order_payment_expire_year'], -2);
		$orderPaymentName       = substr($creditCardData['order_payment_name'], 0, 50);
		$countryCode            = $config->getCountryCode2($data['billinginfo']->country_code);

		// Get params from plugin
		$merchantId       = $this->params->get("merchant_id");
		$apiUserName      = $this->params->get("api_username");
		$apiPassword      = $this->params->get("api_password");

		// Authenticate vars to send
		$dataForm = array(
			'requestType'     => 'BACKEND',
			'merchant_id'     => $merchantId,
			'username'        => $apiUserName,
			'password'        => $apiPassword,
			'trnCardOwner'    => $orderPaymentName,
			'trnCardNumber'   => $creditCardData['order_payment_number'],
			'trnExpMonth'     => $creditCardData['order_payment_expire_month'],
			'trnExpYear'      => $orderPaymentExpireYear,
			'trnCardCvd'      => $creditCardData['credit_card_code'],
			'trnOrderNumber'  => $data['order_number'],
			'trnAmount'       => $orderTotal,
			'ordEmailAddress' => $data['billinginfo']->user_email,
			'ordName'         => $data['billinginfo']->firstname . " " . $data['billinginfo']->lastname,
			'ordPhoneNumber'  => $data['billinginfo']->phone,
			'ordAddress1'     => $data['billinginfo']->address,
			'ordAddress2'     => "",
			'ordCity'         => $data['billinginfo']->city,
			'ordProvince'     => $data['billinginfo']->state_code,
			'ordPostalCode'   => $data['billinginfo']->zipcode,
			'ordCountry'      => $countryCode,
		);

		// Build the post string
		$postString = '';

		foreach ($dataForm AS $key => $val)
		{
			$postString .= urlencode($key) . "=" . $val . "&";
		}

		// Strip off trailing ampersand
		$postString = substr($postString, 0, -1);

		// Initialize curl
		$ch = curl_init();

		// Get curl to POST
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

		// Instruct curl to suppress the output from Beanstream, and to directly
		// return the transfer instead. (Output will be stored in $txResult.)
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		// This is the location of the Beanstream payment gateway
		curl_setopt($ch, CURLOPT_URL, "https://www.beanstream.com/scripts/process_transaction.asp");

		// These are the transaction parameters that we will POST
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postString);

		// Now POST the transaction. $txResult will contain Beanstream's response
		$txResult = curl_exec($ch);

		curl_close($ch);

		// Built array
		$arrResult = $this->explode_assoc("=", "&", $txResult);

		if ($arrResult['trnApproved'] == '1')
		{
			$this->values->responsestatus = 'Success';
			$message = $arrResult['messageText'];
		}
		else
		{
			// Catch Transaction ID
			$message = $arrResult['messageText'];
			$this->values->responsestatus = 'Fail';
		}

		$this->values->transaction_id = $arrResult['trnId'];
		$this->values->message = $message;

		return $this->values;
	}

	public function explode_assoc($glue1, $glue2, $array)
	{
		$array2 = explode($glue2, $array);

		foreach ($array2 as $val)
		{
			$pos = strpos($val, $glue1);
			$key = substr($val, 0, $pos);
			$array3[$key] = substr($val, $pos + 1, strlen($val));
		}

		return $array3;
	}
}
