<?php

/**
 * @package     RedSHOP
 * @subpackage  Plugin
 *
 * @copyright   Copyright (C) 2008 - 2020 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
defined('_JEXEC') or die;

/**
 * This method will be triggered on before placing order to authorize or charge credit card
 *
 * @param   string  $element  Name of the payment plugin
 * @param   array   $data     Cart Information
 *
 * @return  mixed  Authorize or Charge success or failed message and transaction id
 */
class plgRedshop_PaymentBambora extends JPlugin
{
    const BAMBORA_SUCCESS = '20000';
    const BAMBORA_CHECKOUT_URL = "https://api.v1.checkout.bambora.com/sessions";
    const BAMBORA_PAYMENT_INVALID_CURRENCY = 'paymenttypenotfound';
    const BAMBORA_INVALID_API_USER = 'apiusernotfound';
    
    /**
     * Application object
     *
     * @var    JApplicationCms
     * @since  3.2
     */
    protected $app;
    
    /**
     * Load the language file on instantiation.
     *
     * @var    boolean
     * @since  3.1
     */
    protected $autoloadLanguage = true;
    
    /**
     * This method will be triggered on before placing order to authorize or charge credit card
     *
     * @param   string  $element  Name of the payment plugin
     * @param   array   $data     Cart Information
     *
     * @return  mixed  Authorize or Charge success or failed message and transaction id
     */
    public function onPrePayment($element, $data)
    {
        if ($element != 'bambora') {
            return;
        }
        
        $app         = JFactory::getApplication();
        $itemId      = $app->input->getInt('Itemid', 0);
        $orderInfo   = $data['order'];
        $orderId     = $orderInfo->order_id;
        $billingInfo = $data['billinginfo'];
        $dataOrder   = array();
        
        $dataOrder['order']['id']               = $orderId;
        $paymentCurrency                        = $this->params->get(
            "currency",
            Redshop::getConfig()->get('CURRENCY_CODE')
        );
        $orderTotal                             = RedshopHelperCurrency::convert(
            $orderInfo->order_total,
            '',
            $paymentCurrency
        );
        $dataOrder['order']["amount"]           = round($orderTotal * 100);
        $dataOrder['order']['currency']         = $this->params->get('currency');
        $dataOrder['url']['accept']             = $this->getNotifyUrl($orderId);
        $dataOrder['url']['cancel']             = $this->getReturnUrl($orderId);
        $dataOrder['customer']['email']         = $billingInfo->user_email;
        $dataOrder['paymentwindow']['language'] = JFactory::getLanguage()->getTag();
        
        $result       = $this->sendRequestToBambora($dataOrder);
        $responseCode = $result->meta->action->code;
        $responseType = $result->meta->action->type;
        
        if ($responseCode == self::BAMBORA_SUCCESS) {
            $this->app->redirect($result->url);
            
            return true;
        } else {
            $link = JUri::root(
                ) . 'index.php?tmpl=component&option=com_redshop&view=order_detail&controller=order_detail&task=notify_payment&payment_plugin=' . $this->_name
                . '&orderid=' . $orderId . '&Itemid=' . $itemId
                . '&responseCode=' . $responseCode
                . '&responseType=' . $responseType;
            
            $this->app->redirect($link);
        }
    }
    
    private function sendRequestToBambora($data)
    {
        $accessToken    = $this->params->get('access_token');
        $merchantNumber = $this->params->get('merchant_number');
        $secretToken    = $this->params->get('secret_token');
        
        $apiKey = base64_encode(
            $accessToken . "@" . $merchantNumber . ":" . $secretToken
        );
        
        $checkoutUrl = self::BAMBORA_CHECKOUT_URL;
        
        $dataString = json_encode($data);
        
        $contentLength = isset($dataString) ? strlen($dataString) : 0;
        
        $headers = array(
            'Content-Type: application/json',
            'Content-Length: ' . $contentLength,
            'Accept: application/json',
            'Authorization: Basic ' . $apiKey
        );
        
        $curl = curl_init();
        
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_POSTFIELDS, $dataString);
        curl_setopt($curl, CURLOPT_URL, $checkoutUrl);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        
        $result = curl_exec($curl);
        
        return json_decode($result);
    }
    
    /**
     * Notify payment
     *
     * @param   string  $element  Name of plugin
     * @param   array   $request  HTTP request data
     *
     * @return  object  Contains the information of order success of falier in object
     */
    public function onNotifyPaymentBambora($element, $request)
    {
        if ($element != 'bambora') {
            return;
        }
        
        $values         = new stdClass;
        $verify_status  = $this->params->get('verify_status', '');
        $invalid_status = $this->params->get('invalid_status', '');
        
        if (($request['paymenttype']) && isset($request['hash'])) {
            $values->order_status_code         = $verify_status;
            $values->order_payment_status_code = 'Paid';
            $values->log                       = JText::_('PLG_REDSHOP_PAYMENT_BAMBORA_ORDER_PLACED');
            $values->msg                       = JText::_('PLG_REDSHOP_PAYMENT_BAMBORA_ORDER_PLACED');
        } else {
            $values->order_status_code         = $invalid_status;
            $values->order_payment_status_code = 'Unpaid';
            $values->log                       = JText::_('PLG_REDSHOP_PAYMENT_BAMBORA_ORDER_NOT_PLACED');
            $values->msg                       = JText::_('PLG_REDSHOP_PAYMENT_BAMBORA_ORDER_NOT_PLACED');
            $values->type                      = 'error';
        }
        
        $msg = '';
        if (isset($request['responseType'])) {
            switch ($request['responseType']) {
                case self::BAMBORA_INVALID_API_USER:
                    //API user not found
                    $msg = JText::_('PLG_REDSHOP_PAYMENT_BAMBORA_INVALID_API_USER');
                    break;
                case self::BAMBORA_PAYMENT_INVALID_CURRENCY:
                    //Payment type or currency is not supported.
                    $msg = JText::_(
                        'PLG_REDSHOP_PAYMENT_BAMBORA_INVALID_CURRENCY'
                    );
                    break;
            }
            
            JFactory::getApplication()->enqueueMessage($msg, 'error');
            
            $values->order_status_code         = $invalid_status;
            $values->order_payment_status_code = 'Unpaid';
            $values->log                       = JText::_('PLG_REDSHOP_PAYMENT_BAMBORA_ORDER_NOT_PLACED');
            $values->msg                       = JText::_('PLG_REDSHOP_PAYMENT_BAMBORA_ORDER_NOT_PLACED');
            $values->type                      = 'error';
        }
        
        $values->order_id = $request['orderid'];
        
        return $values;
    }
    
    /**
     * Get notify url for payment status update.
     *
     * @param   integer  $orderId  Order Id
     *
     * @return  string             Notify url
     */
    protected function getNotifyUrl($orderId)
    {
        return JUri::base()
            . 'index.php?option=com_redshop&view=order_detail&task=notify_payment&payment_plugin=' . $this->_name
            . '&orderid=' . $orderId
            . '&Itemid=' . JFactory::getApplication()->input->getInt('Itemid');
    }
    
    /**
     * Get return url of for the payment.
     *
     * @param   integer  $orderId  Order Id
     *
     * @return  string   Return Url
     */
    protected function getReturnUrl($orderId)
    {
        return JUri::base()
            . 'index.php?option=com_redshop&view=order_detail&layout=receipt&oid=' . $orderId
            . '&Itemid=' . JFactory::getApplication()->input->getInt('Itemid');
    }
}
