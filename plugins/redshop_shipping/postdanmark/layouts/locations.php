<?php
/**
 * @package     RedSHOP.Backend
 * @subpackage  Template
 *
 * @copyright   Copyright (C) 2008 - 2017 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
defined('_JEXEC') or die;

/**
 * Layout variables
 * ===========================
 * @var   array  $displayData  Layout variables
 * @var   array  $shops        Shop data
 */
extract($displayData);
?>
<div class="postdanmark-choose">
	<strong><?php echo JText::_('PLG_REDSHOP_SHIPPING_POSTDANMARK_CHOOSE_DELIVERY_POINT') ?>:</strong>
</div>
<table id="mapAddress">
	<tr>
		<?php if (count($shops) === 1): ?>
			<?php
			echo \RedshopLayoutHelper::render(
				'shop',
				array('shop' => $shops[0], 'key' => 0, 'count' => 1),
				JPATH_PLUGINS . '/redshop_shipping/postdanmark/layouts'
			);
			?>
		<?php else: ?>
			<?php
			$cnt   = 0;
			$count = count($shops);
			foreach ($shops as $shop):
				echo \RedshopLayoutHelper::render(
					'shop',
					array('shop' => $shop, 'key' => $cnt, 'count' => $count),
					JPATH_PLUGINS . '/redshop_shipping/postdanmark/layouts'
				);
				$cnt++;
			endforeach;
			?>
		<?php endif; ?>
	</tr>
</table>
