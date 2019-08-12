<?php
/**
 * @package     RedSHOP.Frontend
 * @subpackage  mod_redshop_filter
 *
 * @copyright   Copyright (C) 2008 - 2015 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

$group = array();


?>

<div class="category-product-filter <?php echo $moduleClassSfx; ?>">
    <form novalidate action="<?php echo $action; ?>" method="post" name="adminForm-<?php echo $module->id; ?>"
          id="category-product-filter-form-<?php echo $module->id; ?>" class="form-validate">
        <div class="form-horizontal">
            <div class='taglist row' id="panelfiler<?php echo $module->id; ?>">
				<?php if (empty($mid) && $enableManufacturer == 1 && count($manufacturers) > 0): ?>
                    <div class="filterbox  groupbox panel panel-default">
                        <strong class="attrtitle collapsed" data-toggle="collapse"
                                data-parent="#panelfiler<?php echo $module->id; ?>"
                                data-target="#box_manu"><?php echo JText::_("COM_REDSHOP_MANUFACTURER"); ?></strong>
                        <div id="box_manu" class="hoverbox collapse">
                            <ul class='taglist' id="manufacture-list">
								<?php foreach ($manufacturers as $manufacturer) : ?>
                                    <li style="list-style: none">
                                        <label>
                                <span class='taginput' data-aliases='manu-<?php echo $manufacturer->id; ?>'>
                                <input type="checkbox" name="filterform[manufacturer][]"
	                                <?php echo in_array($manufacturer->id, $filterInput['manufacturer']) ? " checked " : ""; ?>
                                       value="<?php echo $manufacturer->id ?>">
                                </span>
                                            <span class='tagname'><?php echo $manufacturer->name; ?></span>
                                        </label>
                                    </li>
								<?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
				<?php endif; ?>

				<?php if ($enableCategory == 1 && !empty($categories)): ?>
                    <div class="filterbox  groupbox panel panel-default">
                        <strong class="attrtitle collapsed" data-toggle="collapse"
                                data-parent="#panelfiler<?php echo $module->id; ?>"
                                data-target="#box_categories"><?php echo JText::_("COM_REDSHOP_CATEGORY"); ?></strong>
                        <div id="box_categories" class="hoverbox collapse">
                            <ul class='taglist' id="categories-list">
								<?php foreach ($categories as $key => $cat) : ?>
                                    <li style="list-style: none">
                                        <label>
                                <span class='taginput' data-aliases='cat-<?php echo $cat->id; ?>'>
                                <input type="checkbox" <?php echo in_array($cat->id, $filterInput['category']) ? "checked " : ""; ?>
                                       name="filterform[category][]" value="<?php echo $cat->id ?>">
                                </span>
                                            <span class='tagname'><?php echo $cat->name; ?></span>
                                        </label>
                                    </li>
								<?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
				<?php endif; ?>

				<?php if ($enableCustomField == 1 && !empty($customFields)): ?>
					<?php foreach ($customFields as $fieldId => $field) : ?>
						<?php if (count($field['value']) <= 0) continue; ?>
                        <div class="filterbox  groupbox panel panel-default">
                            <strong class="attrtitle collapsed" data-toggle="collapse"
                                    data-parent="#panelfiler<?php echo $module->id; ?>"
                                    data-target="#<?php echo 'filter_customfield_' . $fieldId; ?>"><?php echo $field['title']; ?></strong>
                            <div id="<?php echo 'filter_customfield_' . $fieldId; ?>" class="hoverbox collapse">
                                <ul class='taglist' id="categories-list">
									<?php foreach ($field['value'] as $value => $name) : ?>
										<?php
										$checked         = "";
										$selectedOptions = $filterInput['custom_field'][$fieldId];

										if (in_array($value, $selectedOptions))
										{
											$checked = " checked ";
										}
										?>
                                        <li style="list-style: none">
                                            <label>
                                        <span class='taginput' data-aliases='cus-<?php echo $value; ?>'>
                                        <input type="checkbox"
                                               name="filterform[custom_field][<?php echo $fieldId; ?>][]"
                                               value="<?php echo urlencode($value); ?>" <?php echo $checked; ?>>
                                        </span>
                                                <span class='tagname'><?php echo $name; ?></span>
                                            </label>
                                        </li>
									<?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
					<?php endforeach; ?>
				<?php endif; ?>

				<?php if (!empty($attributesGroup)) : ?>
					<?php foreach ($attributesGroup as $key => $attribute) : ?>
                        <div class="filterbox  groupbox_<?php echo $key; ?> panel panel-default">
                            <strong class="attrtitle collapsed" data-parent="#panelfiler<?php echo $module->id; ?>"
                                    data-toggle="collapse" data-target="#box_<?php echo $key; ?>">
                                <input type="checkbox" name="filterform[attribute_name][]"
                                       value="<?php echo $attribute['attribute_name']; ?>"
                                       id="attribute_<?php echo $attribute['attribute_name'] ?>">
								<?php echo $attribute['attribute_name']; ?>
                            </strong>
							<?php if (!empty($attribute['properties'])) : ?>
								<?php
								$properties = explode("||", $attribute['properties']);
								?>
                                <div id="box_<?php echo $key; ?>" class="hoverbox collapse">
                                    <ul>
										<?php foreach ($properties as $property) : ?>
											<?php
											$checked         = "";
											$selectedOptions = $filterInput['attribute_name'][$attribute['attribute_name']]['property'];

											if (in_array($property, $selectedOptions))
											{
												$checked = " checked ";
											}
											?>
                                            <li>
                                                <label>
                                                    <input type="checkbox"
                                                           name="filterform[attribute_name][<?php echo $attribute['attribute_name'] ?>][property][]"
                                                           value="<?php echo $property; ?>" <?php echo $checked; ?>>
                                                    <span class='tagname'><?php echo $property; ?></span>
                                                </label>
                                            </li>
										<?php endforeach; ?>
                                    </ul>
                                </div>
							<?php endif; ?>
                        </div>
					<?php endforeach; ?>
				<?php endif; ?>

				<?php if ($enablePrice == 1) : ?>
                    <div class="filterbox  groupbox_price panel panel-default">
                        <strong class="attrtitle collapsed" data-parent="#panelfiler<?php echo $module->id; ?>"
                                data-toggle="collapse" data-target="#box_price">
							<?php echo JText::_("COM_REDSHOP_PRODUCT_PRICE"); ?>
                        </strong>
                        <div id="box_price" class="hoverbox collapse">
                            <div id="slider-range"></div>
                            <div id="filter-price">
								<?php
								$priceInput    = $filterInput['filterprice'];
								$minPriceInput = !empty($priceInput['min']) ? $priceInput['min'] : $rangeMin;
								$maxPriceInput = !empty($priceInput['max']) ? $priceInput['max'] : $rangeMax;

								?>
                                <div id="amount-min">
                                    <div><?php echo Redshop::getConfig()->get('CURRENCY_CODE') ?></div>
                                    <input type="text" pattern="^\d*(\.\d{2}$)?" class="span12"
                                           name="filterform[filterprice][min]" value="<?php echo $minPriceInput; ?>"
                                           min="0"
                                           max="<?php echo $rangeMax; ?>"/>
                                </div>
                                <div id="amount-max">
                                    <div><?php echo Redshop::getConfig()->get('CURRENCY_CODE') ?></div>
                                    <input type="text" pattern="^\d*(\.\d{2}$)?" class="span12"
                                           name="filterform[filterprice][max]" value="<?php echo $maxPriceInput; ?>"
                                           min="0"
                                           max="<?php echo $rangeMax; ?>"/>
                                </div>
                            </div>
                        </div>
                    </div>
				<?php endif; ?>

                <div class="filterbox  groupbox_control">
                    <div class="control-btn">
                        <span class="btn btn-danger"
                              onclick="clearAll();"><?php echo JText::_("COM_REDSHOP_RESET"); ?></span>
                    </div>
                    <div class="control-btn">
                        <input type="button" class="btn btn-success" onclick="submitFilter();"
                               value="<?php echo JText::_("COM_REDSHOP_FILTER"); ?>">
                    </div>
                </div>
            </div>
        </div>
        <input type="hidden" name="filterform[cid]" value="<?php echo !empty($cid) ? $cid : 0; ?>"/>
        <input type="hidden" name="filterform[mid]" value="<?php echo !empty($mid) ? $mid : 0; ?>"/>
        <input type="hidden" name="limitstart" value="0"/>
    </form>
</div>

<link rel="stylesheet" type="text/css"
      href="<?php echo JUri::root() . 'modules/mod_redshop_category_product_filters/lib/css/jqui.css'; ?>">
<link rel="stylesheet" type="text/css"
      href="<?php echo JUri::root() . 'modules/mod_redshop_category_product_filters/lib/css/product_filters.css'; ?>">
<link rel="stylesheet" type="text/css"
      href="<?php echo JUri::root() . 'modules/mod_redshop_category_product_filters/lib/fontawesome/fontawesome.min.css'; ?>">
<script type="text/javascript"
        src="<?php echo JUri::root() . 'modules/mod_redshop_category_product_filters/lib/js/jquery-ui.min.js'; ?>"></script>
<script type="text/javascript"
        src="<?php echo JUri::root() . 'modules/mod_redshop_category_product_filters/lib/fontawesome/fontawesome.js'; ?>"></script>
<script type="text/javascript"
        src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui-touch-punch/0.2.3/jquery.ui.touch-punch.min.js"></script>

<script type="text/javascript">
    function range_slide(min_range, max_range, cur_min, cur_max) {
        jQuery.ui.slider.prototype.widgetEventPrefix = 'slider';

        jQuery("#slider-range").slider({
            range: true,
            min: min_range,
            max: max_range,
            step: 10,
            values: [cur_min, cur_max],
            slide: function (event, ui) {
                jQuery('[name="filterform[filterprice][min]"]').attr('value', ui.values[0]);
                jQuery('[name="filterform[filterprice][max]"]').attr('value', ui.values[1]);
            }, change: function (event, ui) {
                jQuery('input[name="limitstart"]').val(0);
            }
        });
    }

   /* function order(select) {
        var value = jQuery(select).val();
        jQuery('input[name="order_by"]').val(value);
        submitpriceform();
    }

    function pagination(start) {
        jQuery('input[name="limitstart"]').val(start);
        submitpriceform();
    }*/

    function clearAll() {
        var $filterForm = jQuery("#category-product-filter-form-<?php echo $module->id; ?>");
        $filterForm.find('input[type="checkbox"]').each(function () {
            jQuery(this).prop('checked', false);
        });

		<?php if ($enablePrice == 1) : ?>
        $filterForm.find('[name="filterform[filterprice][min]"]').val("<?php echo $rangeMin;?>");
        $filterForm.find('[name="filterform[filterprice][max]"]').val("<?php echo $rangeMax;?>");
        range_slide(<?php echo $rangeMin;?>, <?php echo $rangeMax;?>, <?php echo $rangeMin;?>, <?php echo $rangeMax;?>);
		<?php endif; ?>

        $filterForm.find('[name="limitstart"]').val(0);

        $filterForm.submit();
    }

    function submitFilter() {
        var $filterForm = jQuery("#category-product-filter-form-<?php echo $module->id; ?>");
        $filterForm.find('[name="limitstart"]').val(0);
        $filterForm.submit();
    }


    jQuery(document).ready(function () {
        jQuery('#category-product-filter-form-<?php echo $module->id; ?>').closest('.moduletable').css('overflow', 'inherit');

        <?php if ($enablePrice == 1) : ?>
        range_slide(<?php echo $rangeMin;?>, <?php echo $rangeMax;?>, <?php echo $minPriceInput;?>, <?php echo $maxPriceInput;?>);
		<?php endif; ?>
    });
</script>