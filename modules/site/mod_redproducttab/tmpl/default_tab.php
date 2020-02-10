<?php
/**
 * @package     RedSHOP.Module
 * @subpackage  mod_redshop_producttab
 *
 * @copyright   Copyright (C) 2008 - 2017 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

$itemClass      = '';
$containerClass = '';

if (version_compare(JVERSION, '3.0', '<'))
{
	$itemClass      = 'left1';
	$containerClass = 'clearfix';
}

$nbRow = $productPerRow;
$j     = 0;
?>
<?php foreach ($rows as $row): ?>
<?php $j++; ?>
<?php if (($j%$nbRow == 0) || ($j%$nbRow == 1) ): ?>
<div class="row <?php echo $containerClass; ?>">
<?php  endif; ?>
	<div class="col-md-<?php echo (12/$productPerRow); ?> <?php echo $itemClass; ?>">
	<?php
		$category_id = $row->category_id;
		$ItemData = RedshopHelperProduct::getMenuInformation(0, 0, '', 'product&pid=' . $row->product_id);

		if (count($ItemData) > 0)
		{
			$Itemid = $ItemData->id;
		}
		else
		{
			$Itemid = RedshopHelperRouter::getItemId($row->product_id);
		}

		$link = JRoute::_('index.php?option=com_redshop&view=product&pid=' . $row->product_id . '&cid=' . $category_id . '&Itemid=' . $Itemid);

		?>
		<?php if ($image) : ?>
			<div>
				<?php echo Redshop\Product\Image\Image::getImage($row->product_id, $link, $thumbwidth, $thumbheight); ?>
			</div>
		<?php endif; ?>

		<p>
			<a href="<?php echo $link; ?>"><?php echo $row->product_name; ?></a>
		</p>

		<?php
		if (!$row->not_for_sale && $show_price && !Redshop::getConfig()->get('USE_AS_CATALOG'))
		{
			$product_price          = Redshop\Product\Price::getPrice($row->product_id);
			$productArr             = RedshopHelperProductPrice::getNetPrice($row->product_id);
			$product_price_discount = $productArr['productPrice'] + $productArr['productVat'];

			if (Redshop::getConfig()->get('SHOW_PRICE') && (!Redshop::getConfig()->get('DEFAULT_QUOTATION_MODE') || (Redshop::getConfig()->get('DEFAULT_QUOTATION_MODE') && SHOW_QUOTATION_PRICE)))
			{
				if (!$product_price)
				{
					$product_price_dis = RedshopHelperProductPrice::priceReplacement($product_price);
				}
				else
				{
					$product_price_dis = RedshopHelperProductPrice::formattedPrice($product_price);
				}

				if ($row->product_on_sale
					&& ($product_price_discount > 0 && $product_price > $product_price_discount))
				{
					if ($show_discountpricelayout)
					{
						echo '<div id="mod_redoldprice" class="mod_redoldprice">'
								. '<span style="text-decoration:line-through">'
									. RedshopHelperProductPrice::formattedPrice($product_price)
								. '</span>'
							. '</div>';

						echo '<div id="mod_redmainprice" class="mod_redmainprice">'
								. RedshopHelperProductPrice::formattedPrice($product_price_discount)
							. '</div>';

						echo '<div id="mod_redsavedprice" class="mod_redsavedprice">'
								. JText::_('COM_REDSHOP_PRODCUT_PRICE_YOU_SAVED')
								. ' '
								. RedshopHelperProductPrice::formattedPrice($product_price - $product_price_discount)
							. '</div>';
					}
					else
					{
						echo '<div class="mod_redshop_products_price">'
								. RedshopHelperProductPrice::formattedPrice($product_price_discount)
							. '</div>';
					}
				}
				else
				{
					echo '<div class="mod_redshop_products_price">' . $product_price_dis . '</div>';
				}
			}
		}

		if ($show_readmore)
		{
			echo "<br><a href=\"" . $link . "\">" . JText::_('MOD_REDPRODUCTTAB_SHOW_READ_MORE') . "</a>&nbsp;";
		}

		if ($show_addtocart)
		{
			$attributes_set = array();

			if ($row->attribute_set_id > 0)
			{
				$attributes_set = RedshopHelperProduct_Attribute::getProductAttribute(0, $row->attribute_set_id, 0, 1);
			}

			$attributes = RedshopHelperProduct_Attribute::getProductAttribute($row->product_id);
			$attributes = array_merge($attributes, $attributes_set);
			$totalatt   = count($attributes);

			$accessory      = RedshopHelperAccessory::getProductAccessories(0, $row->product_id);
			$totalAccessory = count($accessory);

			$count_no_user_field = 0;
			$hidden_userfield    = "";
			$userfieldArr        = array();

			if (Redshop::getConfig()->get('AJAX_CART_BOX'))
			{
				$ajax_detail_template_desc = "";
				$ajax_detail_template      = \Redshop\Template\Helper::getAjaxDetailBox($row);

				if (count($ajax_detail_template) > 0)
				{
					$ajax_detail_template_desc = $ajax_detail_template->template_desc;
				}

				$returnArr          = RedshopHelperProduct::getProductUserfieldFromTemplate($ajax_detail_template_desc);
				$template_userfield = $returnArr[0];
				$userfieldArr       = $returnArr[1];

				if ($template_userfield != "")
				{
					$ufield = "";

					for ($ui = 0, $countUserFieldArr = count($userfieldArr); $ui < $countUserFieldArr; $ui++)
					{
						$productUserFields = RedshopHelperExtrafields::listAllUserFields($userfieldArr[$ui], 12, '', '', 0, $row->product_id);
						$ufield .= $productUserFields[1];

						if ($productUserFields[1] != "")
						{
							$count_no_user_field++;
						}

						$template_userfield = str_replace('{' . $userfieldArr[$ui] . '_lbl}', $productUserFields[0], $template_userfield);
						$template_userfield = str_replace('{' . $userfieldArr[$ui] . '}', $productUserFields[1], $template_userfield);
					}

					if ($ufield != "")
					{
						$hidden_userfield = "<div style=\"display:none;\"><form method=\"post\" action=\"\" id=\"user_fields_form_" . $row->product_id . "\" name=\"user_fields_form_" . $row->product_id . "\">" . $template_userfield . "</form></div>";
					}
				}
			}

			$addtocart = Redshop\Cart\Render::replace($row->product_id, $category_id, 0, 0, "", false, $userfieldArr, $totalatt, $totalAccessory, $count_no_user_field, $moduleId);
			echo "<div class=\"mod_redshop_products_addtocart\">" . $addtocart . $hidden_userfield . "</div>";
		}

		?>
	</div>
<?php if(($j%$nbRow == 0) || ($j == count($rows))) : ?>
</div>
<?php endif; ?>
<?php endforeach; ?>
