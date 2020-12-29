<?php
/**
 * @package     RedSHOP
 * @subpackage  Plugin
 *
 * @copyright   Copyright (C) 2008 - 2015 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

$uri  = JURI::getInstance();
$url  = $uri->root();
$user = JFactory::getUser();
$db   = JFactory::getDbo();

JLoader::import('redshop.library');

$sql           = "SELECT op.*,o.order_total,o.user_id,o.order_tax,o.order_shipping FROM #__redshop_order_payment AS op LEFT JOIN #__redshop_orders AS o ON op.order_id = o.order_id  WHERE o.order_id='" . $data['order_id'] . "'";
$db->setQuery($sql);
$order_details = $db->loadObjectList();
$request       = JRequest::get('REQUEST');
$task          = $request['task'];

$db = JFactory::getDbo();

$q = "SELECT * FROM #__redshop_order_item WHERE order_id=" . $data['order_id'];
$db->setQuery($q);
$rs = $db->loadObjectlist();

// Authenticate vars to send
$dataForm = array(
	'merchant'          => $this->params->get("seller_id"),
	'orderid'           => $data['order_id'],
	'currency'          => $this->params->get("dibs_currency"),
	'ip'                => $_SERVER['REMOTE_ADDR'],

	// Customer Shipping Address

	'delivery1.Name'    => $data['shippinginfo']->firstname . " " . $data['shippinginfo']->lastname,
	'delivery2.Address' => $data['shippinginfo']->address . "," . $data['shippinginfo']->city . "," . $data['shippinginfo']->state_code . "," . $data['shippinginfo']->country_code,

	// Order detail
	'ordline0-1'        => 'Product Id',
	'ordline0-2'        => 'Product Name',
	'ordline0-3'        => 'Quantity',
	'ordline0-4'        => 'Price',

	// Extra parameters
	'flexlang'          => $this->params->get("dibs_languages"),
	'pay_type'          => $this->params->get("dibs_pay_type"),
	'flexwin_color'     => $this->params->get("dibs_color"),
	'flexwin_decorator' => $this->params->get("dibs_flexwin_decorator"),
	'md5key1'           => $this->params->get("dibs_md5key1"),
	'md5key2'           => $this->params->get("dibs_md5key2"),
	'dibs_uniqueid'     => $this->params->get("dibs_uniqueid"),
	'forcecurrency'     => $this->params->get("dibs_forcecurrency")
);

for ($p = 0, $pn = count($rs); $p < $pn; $p++)
{
	$dataForm['ordline' . ($p + 1) . '-1'] = $rs[$p]->product_id;
	$dataForm['ordline' . ($p + 1) . '-2'] = $rs[$p]->order_item_name;
	$dataForm['ordline' . ($p + 1) . '-3'] = $rs[$p]->product_quantity;
	$dataForm['ordline' . ($p + 1) . '-4'] = $rs[$p]->product_item_price;
}

if ($this->params->get("is_test") == "1")
{
	$dataForm['test'] = "yes";
}

$version            = "2";
$dibsurl            = "https://payment.architrade.com/paymentweb/start.action";
$dataForm['amount'] = \RedshopHelperCurrency::convert($order_details[0]->order_total, '', $this->params->get("dibs_currency"));
$dataForm['amount'] = number_format($dataForm['amount'], 2, '.', '') * 100;

if ($dataForm['flexlang'] == "Auto")
{
	$dibs_lang_arr = array(
		'Denmark'       => 'da',
		'Sweden'        => 'sv',
		'Norway'        => 'no',
		'Finland'       => 'fi',
		'Germany'       => 'de',
		'Netherlands'   => 'nl',
		'France'        => 'fr',
		'Spain'         => 'es',
		'Italy'         => 'it',
		'Faroe Islands' => 'fo'
	);

	if (isset($lang) && $lang != '')
	{
		$dataForm["lang"] = $lang;
	}
	else
	{
		$lang = 'en';
		$dataForm["lang"] = $lang;
	}
}

if ($dataForm['flexlang'] != "Auto")
{
	$dataForm["lang"] = $dataForm['flexlang'];
}

if ($dataForm["flexwin_decorator"] != "Own Decorator")
{
	$dataForm["decorator"] = $dataForm["flexwin_decorator"];
	$dataForm["color"]     = $dataForm["flexwin_color"];
}

if ($dataForm["md5key1"] != "" && $dataForm["md5key2"] != "")
{
	$md5key                    = md5($dataForm["md5key2"] . md5($dataForm["md5key1"] . 'merchant=' . $dataForm["merchant"] . '&orderid=' . $data['order_id'] . '&currency=' . $dataForm['currency'] . '&amount=' . $dataForm['amount']));
	$dataForm["md5key"]        = $md5key;
	$dataForm["dibs_uniqueid"] = 'yes';
}

// Build the post string
$postString = '';
?>
<form action="<?php echo $dibsurl ?>" id='dibscheckout' name="dibscheckout" target="myNewWin" method="post">
	<?php foreach ($dataForm as $name => $value): ?>
		<input type="hidden" name="<?php echo $name ?>" value="<?php echo urlencode($value) ?>"/>
	<?php endforeach; ?>
	<?php echo $postString; ?>
	<?php
	$accepturl = JURI::base() . "index.php?option=com_redshop&view=order_detail&controller=order_detail&task=notify_payment&payment_plugin=rs_payment_dibspaymentmethod&orderid=" . $data['order_id'];
	$cancelurl = JURI::base() . "index.php?option=com_redshop&view=order_detail&controller=order_detail&task=notify_payment&payment_plugin=rs_payment_dibspaymentmethod&orderid=" . $data['order_id'];

	?>
	<input type="hidden" name="accepturl" value="<?php echo $accepturl; ?>"/>
	<input type="hidden" name="cancelurl" value="<?php echo $cancelurl; ?>"/>
</form>
<script>
	function redirectOutput() {
		var w = window.open('', 'Popup_Window', "width=700,height=500,toolbar=1");
		document.dibscheckout.target = 'Popup_Window';
		document.dibscheckout.submit();
		return true;
	}
</script>
<script type="text/javascript">
	window.onload = redirectOutput;
</script>
