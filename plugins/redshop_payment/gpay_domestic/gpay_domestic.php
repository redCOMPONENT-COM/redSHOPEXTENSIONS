<?php

/**
 * @package     RedSHOP
 * @subpackage  Plugin
 *
 * @copyright   Copyright (C) 2008 - 2022 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
defined('_JEXEC') or die;

class PlgRedshop_PaymentGpay_domestic extends \RedshopPayment
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
        if ($element != 'gpay_domestic') {
            return;
        }

        $jsonResult = $this->createTransaction($data['carttotal'], $data['order_id']);

        JFactory::getApplication()->redirect($jsonResult['order_url']);
    }

    protected function preparePaymentInput($orderInfo)
    {
    }

    private function createTransaction($orderTotal, $orderId)
    {
        $clientId     = $this->params->get('clientId');
        $clientSecret = $this->params->get('clientSecret');
        $amount       = number_format($orderTotal, 0, "", "");
        $description  = "Order: " . $orderId;
        $customerId   = $this->params->get('customerId');
        $callbackUrl  = $this->getNotifyUrl($orderId);

        //before sign HMAC SHA256 signature
        $rawHash   = "client_id=" . $clientId . "&order_id=" . $orderId . "&amount=" . $amount . "&customer_id=" . $customerId;
        $signature = hash_hmac("sha256", $rawHash, $clientSecret);
        $data      = array(
            'client_id'    => $clientId,
            'order_id'     => $orderId,
            'amount'       => (int) $amount,
            'description'  => $description,
            'customer_id'  => $customerId,
            'callback_url' => $callbackUrl,
            'hmac'         => $signature
        );

        $result = $this->execPostRequest("merchant/orders", json_encode($data));

        return json_decode($result, true);  // decode jsonson
    }

    private function execPostRequest($url, $data, $method = "POST")
    {
        $endpoint = 'https://payment.g-pay.vn/api/v2/';

        if ($this->params->get('isTest')) {
            $endpoint = 'https://sandbox-payment.g-pay.vn/api/v2/';
        }

        $ch = curl_init($endpoint . $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if ($method === "POST") {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            array(
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
    public function onNotifyPaymentGpay_domestic($element, $request)
    {
        if ($element != 'gpay_domestic') {
            return false;
        }

	    $data = json_decode(base64_decode(($request['data'])), true);

	    $clientId     = $this->params->get('clientId');
	    $clientSecret = $this->params->get('clientSecret');
	    $orderId      = $data["order_id"];
	    $signature    = hash_hmac("sha256", "client_id=" . $clientId . "&order_id=" . $orderId, $clientSecret);

	    $response = $this->execPostRequest(
		    "merchant/orders/" . $orderId . "?client_id=" . $clientId . "&hmac=" . $signature,
		    array(),
		    "GET"
	    );

	    $result = json_decode($response, true);

        if ($result['return_code'] == 0 && $result['status'] == "ORDER_SUCCESS") {
	        $values =  $this->setStatus(
		        $result['order_id'],
		        $result['gpay_order_id'],
		        $this->params->get('verify_status', ''),
		        'Paid',
		        JText::_('PLG_REDSHOP_PAYMENT_GPAY_DOMESTIC_PAYMENT_SUCCESS'),
		        $response
	        );

	        if (isset($request['ipn']) && $request['ipn']) {
		        $values->log = $values->log . ' IPN';
		        //Change order status
		        RedshopHelperOrder::changeOrderStatus($values);
	        }

	        return  $values;
        }

        return $this->setStatus(
            $result['order_id'],
            $result['gpay_order_id'],
            $this->params->get('invalid_status', ''),
            'Unpaid',
            JText::_('PLG_REDSHOP_PAYMENT_GPAY_DOMESTIC_PAYMENT_REJECTED'),
            $response
        );
    }
}
