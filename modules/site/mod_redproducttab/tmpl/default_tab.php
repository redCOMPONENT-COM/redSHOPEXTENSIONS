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
		$itemData = RedshopHelperProduct::getMenuInformation(0, 0, '', 'product&pid=' . $row->product_id);

		if (is_array($itemData) && count($itemData) > 0)
		{
			$Itemid = $itemData->id;
		}
		else
		{
			$Itemid = RedshopHelperRouter::getItemId($row->product_id);
		}

		$link = JRoute::_('index.php?option=com_redshop&view=product&pid=' . $row->product_id . '&cid=' . $category_id . '&Itemid=' . $Itemid);

		?>
		<?php if ($image) : ?>
			<div>
				<?php echo Redshop\Product\Image\Image::getImage($row->product_id, $link, $thumbWidth, $thumbHeight); ?>
			</div>
		<?php endif; ?>

		<p>
			<a href="<?php echo $link; ?>"><?php echo $row->product_name; ?></a>
		</p>

		<?php
		if (!$row->not_for_sale && $isShowPrice && !Redshop::getConfig()->get('USE_AS_CATALOG'))
		{
			$productPrice          = \Redshop\Product\Price::getPrice($row->product_id);
			$products              = \RedshopHelperProductPrice::getNetPrice($row->product_id);
			$productPriceDiscountcount = $products['productPrice'] + $products['productVat'];

			if (Redshop::getConfig()->get('SHOW_PRICE') && (!Redshop::getConfig()->get('DEFAULT_QUOTATION_MODE') || (Redshop::getConfig()->get('DEFAULT_QUOTATION_MODE') && SHOW_QUOTATION_PRICE)))
			{
				if (!$productPrice)
				{
					$productPriceDiscount = RedshopHelperProductPrice::priceReplacement($productPrice);
				}
				else
				{
					$productPriceDiscount = RedshopHelperProductPrice::formattedPrice($productPrice);
				}

				if ($row->product_on_sale
					&& ($productPriceDiscountcount > 0 && $productPrice > $productPriceDiscountcount))
				{
					if ($show_discountpricelayout)
					{
						echo '<div id="mod_redoldprice" class="mod_redoldprice">'
								. '<span style="text-decoration:line-through">'
									. RedshopHelperProductPrice::formattedPrice($productPrice)
								. '</span>'
							. '</div>';

						echo '<div id="mod_redmainprice" class="mod_redmainprice">'
								. RedshopHelperProductPrice::formattedPrice($productPriceDiscountcount)
							. '</div>';

						echo '<div id="mod_redsavedprice" class="mod_redsavedprice">'
								. JText::_('COM_REDSHOP_PRODCUT_PRICE_YOU_SAVED')
								. ' '
								. RedshopHelperProductPrice::formattedPrice($productPrice - $productPriceDiscountcount)
							. '</div>';
					}
					else
					{
						echo '<div class="mod_redshop_products_price">'
								. RedshopHelperProductPrice::formattedPrice($productPriceDiscountcount)
							. '</div>';
					}
				}
				else
				{
					echo '<div class="mod_redshop_products_price">' . $productPriceDiscount . '</div>';
				}
			}
		}

        if ($isShowDesc)
        {
            echo '<div class="mod_redshop_products_desc">' . $row->product_s_desc . '</div>';
        }

		if ($isShowReadmore)
		{
			echo "<br><a href=\"" . $link . "\">" . JText::_('MOD_REDPRODUCTTAB_SHOW_READ_MORE') . "</a>&nbsp;";
		}

		if ($isShowAddToCart)
		{
			$setOfAttribute = [];

			if ($row->attribute_set_id > 0)
			{
				$setOfAttribute = \Redshop\Product\Attribute::getProductAttribute(0, $row->attribute_set_id, 0, 1);
			}

			$attributes = \Redshop\Product\Attribute::getProductAttribute($row->product_id);
			$attributes = array_merge($attributes, $setOfAttribute);
			$totalAttribute   = count($attributes);

			$accessory      = RedshopHelperAccessory::getProductAccessories(0, $row->product_id);
			$totalAccessory = count($accessory);

			$numberUserField = 0;
			$hiddenUserField    = "";
			$userFieldArr        = array();

			if (Redshop::getConfig()->get('AJAX_CART_BOX'))
			{
				$ajaxDetailTemplateDesc = "";
				$ajax_detail_template      = \Redshop\Template\Helper::getAjaxDetailBox($row);

				if (count($ajax_detail_template) > 0)
				{
					$ajaxDetailTemplateDesc = $ajax_detail_template->template_desc;
				}

				$returnArr          = \RedshopHelperProduct::getProductUserfieldFromTemplate($ajaxDetailTemplateDesc);
				$templateUserField  = $returnArr[0];
				$userFieldArr       = $returnArr[1];

				if ($templateUserField != "")
				{
					$userField = "";

					for ($ui = 0, $countUserFieldArr = count($userFieldArr); $ui < $countUserFieldArr; $ui++)
					{
						$productUserFields = \RedshopHelperExtrafields::listAllUserFields($userFieldArr[$ui], 12, '', '', 0, $row->product_id);
						$userField .= $productUserFields[1];

						if ($productUserFields[1] != "")
						{
							$numberUserField++;
						}

						$templateUserField = str_replace('{' . $userFieldArr[$ui] . '_lbl}', $productUserFields[0], $templateUserField);
						$templateUserField = str_replace('{' . $userFieldArr[$ui] . '}', $productUserFields[1], $templateUserField);
					}

					if ($userField != "")
					{
						$hiddenUserField = "<div style=\"display:none;\"><form method=\"post\" action=\"\" id=\"user_fields_form_" . $row->product_id . "\" name=\"user_fields_form_" . $row->product_id . "\">" . $templateUserField . "</form></div>";
					}
				}
			}

			$addToCart = \Redshop\Cart\Render::replace($row->product_id, $category_id, 0, 0, "", false, $userFieldArr, $totalAttribute, $totalAccessory, $numberUserField, $module->id);
			echo "<div class=\"mod_redshop_products_addtocart\">" . $addToCart . $hiddenUserField . "</div>";
		}

		?>
	</div>
<?php if(($j%$nbRow == 0) || ($j == count($rows))) : ?>
</div>
<?php endif; ?>
<?php endforeach; ?>
