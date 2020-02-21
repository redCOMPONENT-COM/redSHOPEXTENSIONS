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
class Plg\Redshop_Paymentrs_Payment_Onepay_Domestic extends JPlugin
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
		if ($element != 'rs_payment_onepay_domestic')
		{
			return;
		}

		$debugMode = $this->params->get("debug_mode");

		if ($debugMode ==  1)
		{
			$virtualPaymentClientURL = 'https://mtf.onepay.vn/onecomm-pay/vpc.op';
		}
		else
		{
			$virtualPaymentClientURL = 'https://onepay.vn/onecomm-pay/vpc.op';
		}

		$secureSecret     = $this->params->get("secure_secret_key");
		$app              = JFactory::getApplication();
		$itemId           = $app->input->getInt('Itemid', 0);
		$vpcURL           = $virtualPaymentClientURL . "?";
		$stringHashData   = "";
		$phoneShip        = $data['shippinginfo']->phone;
		$userEmailShip    = $data['shippinginfo']->user_email;
		$userIdShip       = $data['shippinginfo']->user_id;

		$arrayData = [
			'Title'              => $this->params->get("portal_title"),
			'vpc_Merchant'       => $this->params->get("merchant_id"),
			'vpc_AccessCode'     => $this->params->get("merchant_access_code"),
			'vpc_MerchTxnRef'    => date ( 'YmdHis' ) . rand (),
			'vpc_OrderInfo'      => $data['order_id'],
			'vpc_Amount'         => (int) round($data['carttotal'] * 100, 2),
			'vpc_ReturnURL'      => JURI::base() . 'index.php?tmpl=component&option=com_redshop&view=order_detail&controller=order_detail&task=notify_payment&payment_plugin=rs_payment_onepay_domestic&accept=1&Itemid=' . $itemId,
			'vpc_Version'        => $this->params->get("portal_version"),
			'vpc_Command'        => $this->params->get("command_type"),
			'vpc_Locale'         => $this->params->get("language"),
			'vpc_Currency'       => $this->params->get("currency_code"),
			'vpc_TicketNo'       => $_SERVER ['REMOTE_ADDR'],
			'vpc_Customer_Phone' => $this->convertUnicodeToNonUnicode(substr($phoneShip, 0, 50)),
			'vpc_Customer_Email' => $this->convertUnicodeToNonUnicode(substr($userEmailShip, 0, 50)),
			'vpc_Customer_Id'    => $this->convertUnicodeToNonUnicode(substr($userIdShip, 0, 50))
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
	public function onNotifyPaymentrs_payment_onepay_domestic($element, $request)
	{
		if ($element != 'rs_payment_onepay_domestic')
		{
			return false;
		}

		$ipnStatus = $request["ipn"];

		if (!empty($ipnStatus)) {
			JFactory::getSession()->set('ipn_status', $ipnStatus);
		}

		$values         = new stdClass;
		$secureSecret   = $this->params->get("secure_secret_key");
		$txnSecureHash  = $request["vpc_SecureHash"];
		unset ( $request["vpc_SecureHash"] );
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
				$values->type                      = 'error';
			}
		}
		else
		{
			$values->order_status_code         = $invalid_status;
			$values->order_payment_status_code = 'Unpaid';
			$values->log                       = JText::_('COM_REDSHOP_ORDER_NOT_PLACED');
			$values->msg                       = JText::_('COM_REDSHOP_ORDER_NOT_PLACED');
			$values->type                      = 'error';
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

	function convertUnicodeToNonUnicode ($str){
		$unicode = array(
			'a'=>'á|à|ả|ã|ạ|ă|ắ|ặ|ằ|ẳ|ẵ|â|ấ|ầ|ẩ|ẫ|ậ',
			'd'=>'đ',
			'e'=>'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',
			'i'=>'í|ì|ỉ|ĩ|ị',
			'o'=>'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',
			'u'=>'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',
			'y'=>'ý|ỳ|ỷ|ỹ|ỵ',
			'A'=>'Á|À|Ả|Ã|Ạ|Ă|Ắ|Ặ|Ằ|Ẳ|Ẵ|Â|Ấ|Ầ|Ẩ|Ẫ|Ậ',
			'D'=>'Đ',
			'E'=>'É|È|Ẻ|Ẽ|Ẹ|Ê|Ế|Ề|Ể|Ễ|Ệ',
			'I'=>'Í|Ì|Ỉ|Ĩ|Ị',
			'O'=>'Ó|Ò|Ỏ|Õ|Ọ|Ô|Ố|Ồ|Ổ|Ỗ|Ộ|Ơ|Ớ|Ờ|Ở|Ỡ|Ợ',
			'U'=>'Ú|Ù|Ủ|Ũ|Ụ|Ư|Ứ|Ừ|Ử|Ữ|Ự',
			'Y'=>'Ý|Ỳ|Ỷ|Ỹ|Ỵ',
		);

		foreach($unicode as $nonUnicode => $uni){

			$str = preg_replace("/($uni)/i", $nonUnicode, $str);

		}

		return $str;
	}

	/**
	 * IPN Update API (Instant Payment Notification)
	 *
	 * @param   string  $element  Plugin Name
	 * @param   integer  $orderId Order Id
	 *
	 * @return  string  $res response
	 */
	public function onAfterNotifyPaymentrs_payment_onepay_domestic($element, $orderId)
	{
		if ($element != 'rs_payment_onepay_domestic')
		{
			return false;
		}

		$ipnStatus = JFactory::getSession()->get('ipn_status');

		if ($ipnStatus != 1) {
			return false;
		}

		$res = 'responsecode=1&desc=confirm-success';

		// Initialiase variables.
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		// Create the base select statement.
		$query->select('count(*)')
			->from($db->qn('#__redshop_orders'))
			->where($db->qn('order_id') . ' = ' . (int) $orderId)
			->where($db->qn('order_status') . ' = "C"' )
			->where($db->qn('order_payment_status') . ' = "Paid"' );

		// Set the query and load the result.
		$db->setQuery($query);

		try
		{
			$order = $db->loadResult();
		}
		catch (RuntimeException $e)
		{
			$res = 'responsecode=0&desc=confirm-fail';
		}

		if ($order == 0)
		{
			$res = 'responsecode=0&desc=confirm-fail';
		}

		JFactory::getSession()->set('ipn_status', null);

		echo $res; exit;
	}
}
