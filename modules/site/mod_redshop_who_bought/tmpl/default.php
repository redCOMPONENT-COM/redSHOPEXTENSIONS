<?php
/**
 * @package     RedSHOP.Frontend
 * @subpackage  mod_redshop_who_bought
 *
 * @copyright   Copyright (C) 2008 - 2020 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

\JHtml::_('behavior.tooltip');
\JHtml::_('behavior.modal');

$itemId = JRequest::getInt('Itemid');
$user = JFactory::getUser();

$document = JFactory::getDocument();
JHtml::stylesheet("modules/mod_redshop_who_bought/assets/css/skin.css");
$document->addStyleDeclaration('
	.jcarousel-skin-tango .jcarousel-container-horizontal {
		width:' . $sliderWidth . 'px;
	}
	.jcarousel-skin-tango .jcarousel-item {
		width:' . ($sliderWidth / $scroll - 8) . 'px;
	}
');

JHtml::_('redshopjquery.framework');
JHtml::script('modules/mod_redshop_who_bought/assets/js/jquery.jcarousel.min.js');

$redTemplate = Redtemplate::getInstance();
$moduleId = "mod_" . $module->id;

JHtml::script('com_redshop/redshop.attribute.min.js', false, true);
JHtml::script('com_redshop/redshop.common.min.js', false, true);
JHtml::script('com_redshop/redshop.redbox.min.js', false, true);

JFactory::getDocument()->addScriptDeclaration('
	jQuery(document).ready(function () {
		jQuery(\'#mycarousel_' . $module->id . '\').jcarousel({scroll:'.$scroll.'});
	});');

echo '<ul id="mycarousel_' . $module->id . '" class="jcarousel-skin-tango">';

if (count($rows))
{
	foreach ($rows as $product)
	{
		$categoryId = \RedshopHelperProduct::getCategoryProduct($product->product_id);

		$setOfAttributes = [];

		if ($product->attribute_set_id > 0)
		{
			$setOfAttributes = \Redshop\Product\Attribute::getProductAttribute(0, $product->attribute_set_id,
                0, 1);
		}

		$attributes = \Redshop\Product\Attribute::getProductAttribute($product->product_id);
		$attributes = array_merge($attributes, $setOfAttributes);
		$totalAttributes   = count($attributes);

		$accessory      = \RedshopHelperAccessory::getProductAccessories(0, $product->product_id);
		$totalAccessory = count($accessory);

		/*
		 * collecting extra fields
		 */
		$countUserFields = 0;
		$hiddenUserField    = "";
		$userFields        = [];

		if (\Redshop::getConfig()->get('AJAX_CART_BOX'))
		{
			$ajaxDetailTemplate_desc = "";
			$ajaxDetailTemplate      = \Redshop\Template\Helper::getAjaxDetailBox($product);

			if (count($ajaxDetailTemplate) > 0)
			{
				$ajaxDetailTemplate_desc = $ajaxDetailTemplate->template_desc;
			}

			$returns           = \RedshopHelperProduct::getProductUserfieldFromTemplate($ajaxDetailTemplate_desc);
			$templateUserField = $returns[0];
			$userFields        = $returns[1];

			if ($templateUserField != "")
			{
				$uField = "";

				for ($ui = 0; $ui < count($userFields); $ui++)
				{
					$productUserFields = \RedshopHelperExtrafields::listAllUserFields($userFields[$ui], 12,
                        '', '', 0, $product->product_id);
					$uField .= $productUserFields[1];

					if ($productUserFields[1] != "")
					{
						$countUserFields++;
					}

					$templateUserField = str_replace('{' . $userFields[$ui] . '_lbl}', $productUserFields[0], $templateUserField);
					$templateUserField = str_replace('{' . $userFields[$ui] . '}', $productUserFields[1], $templateUserField);
				}

				if ($uField != "")
				{
					$hiddenUserField = "<div style='display:none;'><form method='post' action='' id='user_fields_form_"
                        . $product->product_id . "' name='user_fields_form_" . $product->product_id . "'>" . $templateUserField . "</form></div>";
				}
			}
		}


		echo " <li>";

		if ($isShowProductImage)
		{
			if (!\JFile::exists(REDSHOP_FRONT_IMAGES_RELPATH . 'product/' . $product->product_full_image))
			{
				$file_path = JPATH_SITE . '/components/com_redshop/assets/images/noimage.jpg';
				$filename = \RedshopHelperMedia::generateImages(
					$file_path, '', $thumbWidth, $thumbHeight, 'thumb',
                    \Redshop::getConfig()->get('USE_IMAGE_SIZE_SWAPPING')
				);
				$filename_path_info = pathinfo($filename);
				$thumbImage = REDSHOP_FRONT_IMAGES_ABSPATH . 'thumb/' . $filename_path_info['basename'];
			}
			elseif (\Redshop::getConfig()->get('WATERMARK_PRODUCT_IMAGE'))
			{
				$thumbImage = \RedshopHelperMedia::watermark('product', $product->product_full_image,
                    $thumbWidth, $thumbHeight, \Redshop::getConfig()->get('WATERMARK_PRODUCT_THUMB_IMAGE'), '0');
			}
			else
			{
				$thumbImage = \RedshopHelperMedia::getImagePath(
					$product->product_full_image,
					'',
					'thumb',
					'product',
					$thumbWidth,
					$thumbHeight,
					\Redshop::getConfig()->get('USE_IMAGE_SIZE_SWAPPING')
				);
			}

			echo "<div class=\"imageWhoBought\" style=\"min-height:" . $thumbHeight . "px\"><img src='" . $thumbImage . "' /></div>";
		}

		if ($isShowAddToCartButton)
		{
            $addToCartTemplates = \RedshopHelperTemplate::getTemplate('add_to_cart');
            $templateName = $addToCartTemplates[0]->name ?? 'add_to_cart1';
            $addToCartTemplate = '{form_addtocart:' . $templateName . '}';

			echo "<div>&nbsp;</div>";
			$addToCart = \Redshop\Cart\Render::replace($product->product_id, $categoryId, 0, 0,
                $addToCartTemplate, false, $userFields, $totalAttributes, $totalAccessory, $countUserFields, $moduleId);
			echo "<div class='mod_redshop_products_addtocart addToCartWhoBought'>" . $addToCart . $hiddenUserField . "</div>";
		}

		if ($isShowProductName)
		{
			$productItemId = \RedshopHelperRouter::getItemId($product->product_id);
			$link = \JRoute::_(
					'index.php?option=com_redshop&view=product&pid=' . $product->product_id . '&Itemid=' . $productItemId
			);

			echo "<div>&nbsp;</div>";

			if ($productTitleLinkable)
			{
				echo "<div style='text-align:center;'>";
					echo "<a href='" . $link . "'>";
						echo $product->product_name;
					echo "</a>";
				echo "</div>";
			}
			else
			{
				echo "<div style='text-align:center;'>" . $product->product_name . "</div>";
			}
		}

		if ($isShowProductPrice && $product->product_price)
		{
			if (\Redshop::getConfig()->get('SHOW_PRICE')
                && (!\Redshop::getConfig()->get('DEFAULT_QUOTATION_MODE')
                    || (\Redshop::getConfig()->get('DEFAULT_QUOTATION_MODE') && SHOW_QUOTATION_PRICE)))
			{
				echo "<div class=\"priceWhoBought\">" . \RedshopHelperProductPrice::formattedPrice($product->product_price) . "</div>";
			}
		}
	}

	echo "</li>";
}

echo "</ul>";
