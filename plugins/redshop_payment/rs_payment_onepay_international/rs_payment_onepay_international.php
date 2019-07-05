<?php
/**
 * @package     RedSHOP
 * @subpackage  Plugin
 *
 * @copyright   Copyright (C) 2008 - 2019 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

JLoader::import('redshop.library');

/**
 * Onepay Payment gateway for redSHOP Payments
 */
class PlgRedshop_Paymentrs_Payment_Onepay_International extends JPlugin
{

	/**
	 * Prepare payment data to send Onepay
	 *
	 * @param   string  $element  Plugin Name
	 * @param   array   $data     Order Information
	 *
	 */
	public function onPrePayment($element, $data)
	{
		if ($element != 'rs_payment_onepay_international')
		{
			return;
		}

		$debugMode = $this->params->get("debug_mode");

		if ($debugMode ==  1)
		{
			$virtualPaymentClientURL = 'https://mtf.onepay.vn/vpcpay/vpcpay.op';
		}
		else
		{
			$virtualPaymentClientURL = 'https://onepay.vn/vpcpay/vpcpay.op';
		}

		$secureSecret      = $this->params->get("secure_secret_key");
		$app               = JFactory::getApplication();
		$itemId            = $app->input->getInt('Itemid', 0);
		$vpcURL            = $virtualPaymentClientURL . "?";
		$stringHashData    = "";
		$addressShip       = $data['shippinginfo']->address;
		$cityShip          = $data['shippinginfo']->city;
		$countryCodeShip   = $data['shippinginfo']->country_code;
		$phoneShip         = $data['shippinginfo']->phone;
		$userEmailShip     = $data['shippinginfo']->user_email;
		$userIdShip        = $data['shippinginfo']->user_id;
		$addressBill       = $data['billinginfo']->address;
		$cityBill          = $data['billinginfo']->city;
		$zipCodeBill       = $data['billinginfo']->zipcode;
		$country2CodeBill  = $data['billinginfo']->country_2_code;

		$arrayData = [
			'Title'              => $this->params->get("portal_title"),
			'vpc_Merchant'       => $this->params->get("merchant_id"),
			'vpc_AccessCode'     => $this->params->get("merchant_access_code"),
			'vpc_MerchTxnRef'    => date ( 'YmdHis' ) . rand (),
			'vpc_OrderInfo'      => $data['order_id'],
			'vpc_Amount'         => (int) round($data['carttotal'] * 100, 2),
			'vpc_ReturnURL'      => JURI::base() . 'index.php?tmpl=component&option=com_redshop&view=order_detail&controller=order_detail&task=notify_payment&payment_plugin=rs_payment_onepay_international&accept=1&Itemid=' . $itemId,
			'vpc_Version'        => $this->params->get("portal_version"),
			'vpc_Command'        => $this->params->get("command_type"),
			'vpc_Locale'         => $this->params->get("language"),
			'vpc_TicketNo'       => $_SERVER ['REMOTE_ADDR'],
			'vpc_SHIP_Street01'  => substr($addressShip, 0, 500),
			'vpc_SHIP_Provice'   => '',
			'vpc_SHIP_City'      => substr($cityShip, 0, 50),
			'vpc_SHIP_Country'   => substr($countryCodeShip, 0, 50),
			'vpc_Customer_Phone' => substr($phoneShip, 0, 50),
			'vpc_Customer_Email' => substr($userEmailShip, 0, 50),
			'vpc_Customer_Id'    => substr($userIdShip, 0, 50),
			'AVS_Street01'       => substr($addressBill, 0, 500),
			'AVS_City'           => substr($cityBill, 0, 50),
			'AVS_StateProv'      => '',
			'AVS_PostCode'       => substr($zipCodeBill, 0, 50),
			'AVS_Country'        => substr($country2CodeBill, 0, 50),
			'display'            => '',
			'AgainLink'          => urlencode($_SERVER['HTTP_REFERER'])
		];

		ksort ($arrayData);
		$appendAmp = 0;

		foreach($arrayData as $key => $value)
		{

			if (strlen($value) > 0)
			{

				if ($appendAmp == 0)
				{
					$vpcURL .= urlencode($key) . '=' . urlencode($value);
					$appendAmp = 1;
				}
				else
				{
					$vpcURL .= '&' . urlencode($key) . "=" . urlencode($value);
				}

				if ((strlen($value) > 0) && ((substr($key, 0,4)=="vpc_") || (substr($key,0,5) =="user_")))
				{
					$stringHashData .= $key . "=" . $value . "&";
				}
			}
		}

		$stringHashData = rtrim($stringHashData, "&");

		if (strlen($secureSecret) > 0)
		{
			$vpcURL .= "&vpc_SecureHash=" . strtoupper(hash_hmac('SHA256', $stringHashData, pack('H*',$secureSecret)));
		}

		header("Location: " . $vpcURL);
	}

	/**
	 * Handle Payment notification from Onepay
	 *
	 * @param   string  $element  Plugin Name
	 * @param   array   $request  Request data sent from Onepay
	 *
	 * @return  object  Status Object
	 */
	public function onNotifyPaymentrs_payment_onepay_international($element, $request)
	{
		if ($element != 'rs_payment_onepay_international')
		{
			return false;
		}

		$values         = new stdClass;
		$secureSecret   = $this->params->get("secure_secret_key");
		$txnSecureHash  = $request["vpc_SecureHash"];
		unset ( $request["vpc_SecureHash"] );
		$request["vpc_VerToken"] = str_replace(' ','+', $request["vpc_VerToken"] );
		ksort ($request);

		if (strlen ( $secureSecret ) > 0 && $request["vpc_TxnResponseCode"] != "7" && $request["vpc_TxnResponseCode"] != "No Value Returned")
		{

			$stringHashData = "";

			foreach ( $request as $key => $value )
			{
				if ($key != "vpc_SecureHash" && (strlen($value) > 0) && ((substr($key, 0,4)=="vpc_") || (substr($key,0,5) =="user_")))
				{
					$stringHashData .= $key . "=" . $value . "&";
				}
			}

			$stringHashData = rtrim($stringHashData, "&");

			if (strtoupper ( $txnSecureHash ) == strtoupper(hash_hmac('SHA256', $stringHashData, pack('H*',$secureSecret))))
			{
				$hashValidated = "CORRECT";
			}
			else
			{
				$hashValidated = "INVALID HASH";
			}
		}
		else
		{
			$hashValidated = "INVALID HASH";
		}

		$accept         = $request["accept"];
		$txnId            = $request["vpc_TransactionNo"];
		$orderId       = $request["vpc_OrderInfo"];
		$txnResponseCode       = $request["vpc_TxnResponseCode"];
		JPlugin::loadLanguage('com_redshop');
		$verify_status   = $this->params->get('verify_status', '');
		$invalid_status  = $this->params->get('invalid_status', '');

		if ($hashValidated=="CORRECT" && $txnResponseCode=="0")
		{

			if ($accept == "1" || $accept == "2")
			{
				if ($this->orderPaymentNotYetUpdated($orderId, $txnId))
				{
					$values->order_status_code         = $verify_status;
					$values->order_payment_status_code = 'Paid';
					$values->log                       = JText::_('COM_REDSHOP_ORDER_PLACED');
					$values->msg                       = JText::_('COM_REDSHOP_ORDER_PLACED');
				}
			}
			else
			{
				$values->order_status_code         = $invalid_status;
				$values->order_payment_status_code = 'Unpaid';
				$values->log                       = JText::_('COM_REDSHOP_ORDER_NOT_PLACED.');
				$values->msg                       = JText::_('COM_REDSHOP_ORDER_NOT_PLACED');
			}
		}
		else
		{
			$values->order_status_code         = $invalid_status;
			$values->order_payment_status_code = 'Unpaid';
			$values->log                       = JText::_('COM_REDSHOP_ORDER_NOT_PLACED');
			$values->msg                       = JText::_('COM_REDSHOP_ORDER_NOT_PLACED');
		}

		$values->transaction_id = $txnId;
		$values->order_id       = $orderId;

		return $values;
	}

	/**
	 * Check Order payment is set for specific transaction Id
	 *
	 * @param   integer  $orderId  Order Id
	 * @param   string   $txnId      Payment Transaction Id from payment gateway
	 *
	 * @return  boolean  True is not found any order with passed transaction id.
	 */
	public function orderPaymentNotYetUpdated($orderId, $txnId)
	{
		// Initialiase variables.
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		// Create the base select statement.
		$query->select('count(*)')
			->from($db->qn('#__redshop_order_payment'))
			->where($db->qn('order_id') . ' = ' . (int) $orderId)
			->where($db->qn('order_payment_trans_id') . ' = ' . $db->q($txnId));

		// Set the query and load the result.
		$db->setQuery($query);

		try
		{
			$orderPayment = $db->loadResult();
		}
		catch (RuntimeException $e)
		{
			throw new RuntimeException($e->getMessage(), $e->getCode());
		}

		$res = false;

		if ($orderPayment == 0)
		{
			$res = true;
		}

		return $res;
	}
}
