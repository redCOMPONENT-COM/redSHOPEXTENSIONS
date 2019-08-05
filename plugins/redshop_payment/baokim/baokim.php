<?php
/**
 * @package     RedSHOP
 * @subpackage  Plugin
 *
 * @copyright   Copyright (C) 2008 - 2017 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
defined('_JEXEC') or die;

class plgRedshop_PaymentBaokim extends JPlugin
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
	 * Setting Api Baokim
	 *
	 * @return  void
	 */
	public function settingApi()
	{
		$this->loadLibrary();
		BaoKimAPI::setApiKey($this->params->get('api_key'));
		BaoKimAPI::setSecretKey($this->params->get('secret_key'));
	}

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
		if ($element != 'baokim')
		{
			return;
		}

		if (empty($plugin))
		{
			$plugin = $element;
		}


		$producthelper  = productHelper::getInstance();
		$uri            = JURI::getInstance();
		$url            = $uri->root();
		$user           = JFactory::getUser();
		$app            = JFactory::getApplication();
		$itemId         = $app->input->getInt('Itemid', 0);
		$orderInfo      = $data['order'];
		$billingInfo    = $data['billinginfo'];

		$this->settingApi();

		$client = new GuzzleHttp\Client(['timeout' => 20.0]);;
		$options['query']['jwt'] = BaoKimAPI::getToken();

		$payload['mrc_order_id'] = $orderInfo->order_id;
		$payload['total_amount'] = ($orderInfo->order_total > 10000) ? $orderInfo->order_total : '10000';
		$payload['description'] = "Thanh toan đơn hàng " . $data['order_id'];
		$payload['url_success'] = JURI::base() . 'index.php?tmpl=component&option=com_redshop&view=order_detail&controller=order_detail&task=notify_payment&payment_plugin=Baokim&accept=1&Itemid=' . $itemId;
		$payload['url_detail'] = JURI::base() . 'index.php?tmpl=component&option=com_redshop&view=order_detail&controller=order_detail&task=notify_payment&payment_plugin=Baokim&accept=0&mrc_order_id='. $orderInfo->order_id .'&Itemid=' . $itemId;
		$payload['customer_email'] = !empty($billingInfo->user_email) ? $billingInfo->user_email : $billingInfo->email;
		$payload['customer_phone'] = $billingInfo->phone;
		$payload['customer_name'] = $billingInfo->lastname .  $billingInfo->firstname;
		$payload['customer_address'] = $billingInfo->address;

		$options['form_params'] = $payload;

		$link = 'https://api.baokim.vn/payment/api/v4/';

		if ($this->params->get('isTest'))
		{
			$link = 'https://sandbox-api.baokim.vn/payment/api/v4/';
		}


		$response = $client->request("POST", $link . "order/send", $options);
		$result = json_decode($response->getBody()->getContents())->data;

		if (!empty($result))
		{
			$this->app->redirect($result->payment_url);
		}
	}

	/**
	 * Notify payment
	 *
	 * @param   string  $element  Name of plugin
	 * @param   array   $request  HTTP request data
	 *
	 * @return  object  Contains the information of order success of falier in object
	 */
	public function onNotifyPaymentBaokim($element, $request)
	{
		if ($element != 'Baokim')
		{
			return;
		}

		$app              = JFactory::getApplication();
		$input            = $app->input;
		$orderId          = $input->getInt('mrc_order_id');
		$values           = new stdClass;
		$values->order_id = $orderId;

		if (isset($request['stat']) && $request['stat'] == 'c' && $request['accept'] == 1)
		{
			$values->order_status_code         = $this->params->get('verify_status', 'C');
			$values->order_payment_status_code = 'Paid';
			$values->log                       = JText::_('PLG_REDSHOP_PAYMENT_BAOKIM_ORDER_PLACED');
			$values->msg                       = JText::_('PLG_REDSHOP_PAYMENT_BAOKIM_ORDER_PLACED');
		}
		else
		{
			$values->order_status_code         = $this->params->get('invalid_status', 'P');
			$values->order_payment_status_code = 'Unpaid';
			$values->log                       = JText::_('PLG_REDSHOP_PAYMENT_BAOKIM_ORDER_NOT_PLACED');
			$values->msg                       = JText::_('PLG_REDSHOP_PAYMENT_BAOKIM_ORDER_NOT_PLACED');
			$values->type                      = 'error';
		}

		return $values;
	}

	/**
	 * Load library payment Baokim
	 *
	 * @return  void
	 */
	public function loadLibrary()
	{
		require_once dirname(__DIR__) . '/baokim/library/baokimapi.php';
	}
}
