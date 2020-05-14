<?php

/**
 * @package     RedSHOP.Backend
 * @subpackage  Template
 *
 * @copyright   Copyright (C) 2008 - 2019 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
defined('_JEXEC') or die;

$db = JFactory::getDbo();
$query = $db->getQuery(true)
	->select('key_setting, value_setting')
	->from($db->qn('#__kiotviet_setting_product'))
	->where($db->qn('product_id') . ' = ' . $db->q($this->detail->product_id));

$kiotvietSelected = $db->setQuery($query)->loadAssocList('key_setting');

$kiotvietSetting = [
	'Sync price' => 'sync_price',
	'Sync stockroom' => 'sync_stockroom',
	'Sync template' => 'sync_template',
	'Sync product short desc' => 'sync_product_short_desc',
	'Sync image' => 'sync_image',
	'Sync accessory' => 'sync_accessory',
	'Unpublished product' => 'unpublished_product'
];

?>
<div class="row">
	<div class="col-sm-12">
		<div class="box box-primary">
			<div class="box-header with-border">
				<h3 class="box-title">Kiotviet setting product</h3>
			</div>
			<div class="box-body">
				<?php echo generateSelect($kiotvietSetting, $kiotvietSelected) ?>
			</div>
		</div>
	</div>
</div>

<?php

function generateSelect($kiotvietSetting, $kiotvietSelected = [])
{
	$options = [
		[
			'value' => 0,
			'text' => 'select'
		],
		[
			'value' => 'yes',
			'text' => 'yes'
		],
		[
			'value' => 'no',
			'text' => 'no'
		]
	];

	$html = '';

	foreach ($kiotvietSetting as $label => $key)
	{
		$selected = 0;

		if (!empty($kiotvietSelected[$key]))
		{
			$selected = $kiotvietSelected[$key]['value_setting'];
		}

		$html .= '
			<div class="form-group">
			<div class="col-sm-4">
				'. $label .'
			</div>
			<div class="col-sm-8">
				'. JHtml::_('select.genericlist', $options, "kiotviet[$key]",'class="inputbox" size="1" ', 'value', 'text', $selected) .'
			</div>
		</div>
		<hr/>
	';
	}


	return $html;
}
?>
