<?php
/**
 * @package     RedSHOP
 * @subpackage  Plugin
 *
 * @copyright   Copyright (C) 2008 - 2017 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

JLoader::import('redshop.library');

$today = date('d/m/Y', strtotime('+2 day', time()));
$validity = date('YmdHis', strtotime('+1 day', time()));

$amount = \RedshopHelperCurrency::convert($data['order']->order_total, '', 'VND');

$name = $data['billinginfo']->lastname . ' ' . $data['billinginfo']->firstname;

$items = \RedshopHelperOrder::getItems($data['order_id']);
$description = "";

foreach ($items as $item)
{
	$description .= $item->order_item_name . ' - SL: ' . $item->product_quantity . ' - T: ' . $item->product_final_price . '<br>';
}

$description = urlencode($description);

$urlNotify = urlencode(JURI::base() . "index.php?option=com_redshop&view=order_detail&tmpl=component&controller=order_detail&task=notify_payment&payment_plugin=rs_payment_payoo&orderid=" . $data['order_id']);

$xml = "<shops><shop><shop_id>" . $this->params->get("shopid") . "</shop_id><username>" . $this->params->get("username") . "</username><session>" . md5($data['order_id']) . "</session><shop_title>" . $this->params->get("shoptitle") . "</shop_title><shop_domain>" . substr(JUri::base(), 0, -1) . "</shop_domain><shop_back_url>" . $urlNotify . "</shop_back_url><notify_url></notify_url><order_no>" . $data['order_id'] . "</order_no><order_cash_amount>" . $amount . "</order_cash_amount><order_ship_date>" . $today . "</order_ship_date><order_ship_days>2</order_ship_days><order_description>" . $description . "</order_description><validity_time>" . $validity . "</validity_time><customer><name>" . $name . "</name><phone>" . $data['billinginfo']->phone . "</phone><address>" . $data['billinginfo']->address . "</address><email>" . $data['billinginfo']->email . "</email></customer></shop></shops>";

$checkSum = hash('sha512', $this->params->get("checksumkey") . $xml);

$client = new \GuzzleHttp\Client();

$dataRequest = [
		'data' => $xml,
		'checksum' => $checkSum,
		'refer' => JURI::base()
];

if (1 == (int)$this->params->get("sandbox")) {
	$payooUrl = "https://newsandbox.payoo.com.vn/v2/paynow/order/create";
} else {
	$payooUrl = "https://www.payoo.vn/v2/paynow/order/create";
}

$response = $client->request('POST', $payooUrl, [GuzzleHttp\RequestOptions::JSON => $dataRequest]);

$response = json_decode($response->getBody()->getContents());

if ($response->result == 'success')
{
	\Joomla\CMS\Factory::getApplication()->redirect($response->order->payment_url);
}
else
{
	\Joomla\CMS\Factory::getApplication()->redirect($urlNotify);
}


