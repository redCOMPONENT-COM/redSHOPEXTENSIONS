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

<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title"><?= JText::_('PLG_REDSHOP_SHIPPING_AHAMOVE_ORDER_TRACKING_LBL') ?></h3>
    </div>

    <div class="panel-body">
        <div class="redshop-ahamove-order-tracking">
            <?php foreach($orderData['generalInfo'] as $key => $value): ?>
                <div class="row">
                    <label class="col-xs-5" ><?= $translations[$key] ?></label>
                    <div class="col-xs-7"><?= $key!='deliveryNote'?$value:str_replace("\r\n", "</br>", $value) ?></div>
                </div><hr>
            <?php endforeach; ?>

            <div class="row">
                <label class="col-xs-5"><?= JText::_('PLG_REDSHOP_SHIPPING_AHAMOVE_ORDER_TRACKING_URL') ?></label>
                <div class="col-xs-7 tracking-url"><a target="_blank" href="<?= $orderData['trackingUrl'] ?>"><?= $orderData['trackingUrl'] ?></a></div>
            </div>
            <hr>
            <div class="row">
                <label class="col-xs-5"><?= JText::_('PLG_REDSHOP_SHIPPING_AHAMOVE_ORDER_STATUS_LBL') ?></label>
                <div class="col-xs-7"><?= $translations[$orderData['status']] ?></div>
            </div>
            <hr>
            <div class="row">
                <label class="col-xs-5"><?= JText::_('PLG_REDSHOP_SHIPPING_AHAMOVE_PRICE_LBL') ?></label>
                <div class="col-xs-7"><?= RedshopHelperProductPrice::formattedPrice($orderData['fee_ship']) ?></div>
            </div>
        </div>
    </div>
</div>
