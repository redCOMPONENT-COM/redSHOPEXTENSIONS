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
<div class="panel panel-default ahamove-order-tracking">
	<div class="panel-heading">
		<h3 class="panel-title"><?= JText::_('PLG_REDSHOP_SHIPPING_AHAMOVE_ORDER_TRACKING_LBL') ?></h3>
		<div>
			<button class="btn" id="lalamove-get-status" onclick="window.location.reload(false);">Get Latest Status</button>
		</div>
	</div>
	<div class="panel-boy">

		<?php foreach ($orderData['generalInfo'] as $key => $value): ?>
			<?php $hidden = in_array($key, array('requestBody', 'order_id')) ? ' hidden ' : ''; ?>
			<div class="row">
				<label class="col-sm-4 <?= $hidden ?>"><?= $translations[$key] ?></label>
				<div class="col-sm-8 <?= $hidden ?>"><?= $key != 'deliveryNote' ? $value : str_replace("\r\n", "</br>", $value) ?></div>
			</div>
		<?php endforeach; ?>

		<div class="row">
			<label class="col-sm-4"><?= JText::_('PLG_REDSHOP_SHIPPING_AHAMOVE_ORDER_TRACKING_URL') ?></label>
			<div class="col-sm-8 tracking-url"><a target="_blank" href="<?= $orderData['trackingUrl'] ?>"><?= $orderData['trackingUrl'] ?></a></div>
		</div>

		<div class="row">
			<label class="col-sm-4"><?= JText::_('PLG_REDSHOP_SHIPPING_AHAMOVE_ORDER_STATUS_LBL') ?></label>
			<div class="col-sm-8 lalamove-order-status"><?= $translations[$orderData['status']] ?></div>
		</div>
	</div>
</div>
