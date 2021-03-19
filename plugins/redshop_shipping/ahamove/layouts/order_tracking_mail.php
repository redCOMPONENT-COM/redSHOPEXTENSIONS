<?php
/**
 * @package     RedSHOP.Backend
 * @subpackage  Template
 *
 * @copyright   Copyright (C) 2008 - 2021 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
defined('_JEXEC') or die;
JHTML::_('behavior.tooltip');

extract($displayData);

$translations = array_merge(
	$statusTranslations,
	array(
		'order_id' => JText::_('PLG_REDSHOP_SHIPPING_AHAMOVE_ORDER_TRACKING_REFERENCE_NUMBER'),
		'status'   => JText::_('PLG_REDSHOP_SHIPPING_AHAMOVE_ORDER_STATUS'),
		'shared_link' => JText::_('PLG_REDSHOP_SHIPPING_AHAMOVE_SHARING'),
		'deliverToAddress' => JText::_('PLG_REDSHOP_SHIPPING_AHAMOVE_ORDER_TRACKING_DELIVERY_ADDRESS'),
		'deliverySchedule' => JText::_('PLG_REDSHOP_SHIPPING_AHAMOVE_DELIVERY_SCHEDULE_TIME'),
		'deliveryNote' => JText::_('PLG_REDSHOP_SHIPPING_AHAMOVE_ORDER_TRACKING_DELIVERY_NOTE'),
		'requestBody' => JText::_('PLG_REDSHOP_SHIPPING_AHAMOVE_ORDER_TRACKING_REQUEST_BODY'),
		'fee_ship'  => JText::_('PLG_REDSHOP_SHIPPING_AHAMOVE_PRICE_LBL')
	)
);
?>
<tr>
	<td colspan="2"
	    style="font-size: 14px; font-family: verdana; color: #666666; text-align: justify; margin-bottom: 5px !important;">
		<strong><?= JText::_('PLG_REDSHOP_SHIPPING_AHAMOVE_ORDER_TRACKING_LBL') ?></strong>
	</td>
</tr>

<?php foreach($orderData['generalInfo'] as $key => $value): ?>
<?php if (!in_array($key, array('requestBody', 'order_id', 'deliveryNote'))): ?>
<tr>
	<td><?= $translations[$key] ?></td>
	<td><?= $key!='deliveryNote'?$value:str_replace("\r\n", "</br>", $value) ?></td>
</tr>
<?php endif ?>
<?php endforeach; ?>

<tr>
	<td><?= JText::_('PLG_REDSHOP_SHIPPING_AHAMOVE_ORDER_STATUS_LBL') ?></td>
	<td><?= $translations[$orderData['status']] ?></td>
</tr>
