<?php
/**
 * @package     RedSHOP.Frontend
 * @subpackage  mod_redshop_shoppergroup_product
 *
 * @copyright   Copyright (C) 2008 - 2015 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;
JHTML::_('behavior.tooltip');
JHTML::_('behavior.modal');

$uri = JURI::getInstance();
$url = $uri->root();

$itemId    = JRequest::getInt('Itemid');
$user      = JFactory::getUser();
$view      = JRequest::getCmd('view');
$getoption = JRequest::getCmd('option');

$document = JFactory::getDocument();
JHTML::stylesheet('modules/mod_redshop_shoppergroup_product/css/products.css');

JHtml::script('com_redshop/attribute.js', false, true);
JHtml::script('com_redshop/common.js', false, true);
JHTML::script('com_redshop/redbox.js', false, true);

$redTemplate   = Redtemplate::getInstance();

echo "<div class='mod_redshop_shoppergroup_product_wrapper'>";

$module_id = "mod_" . $module->id;

foreach ($rows as $row)
{
	$setOfAttributes = [];

	if ($row->attribute_set_id > 0)
	{
		$setOfAttributes = \Redshop\Product\Attribute::getProductAttribute(0, $row->attribute_set_id, 0, 1);
	}

	$attributes = \Redshop\Product\Attribute::getProductAttribute($row->product_id);
	$attributes = array_merge($attributes, $setOfAttributes);
	$totalAttributes   = count($attributes);

	/*
	 * collecting extra fields
	 */
	$countUserFields = 0;
	$hiddenUserField    = "";
	$userFields        = [];

	if (\Redshop::getConfig()->get('AJAX_CART_BOX'))
	{
		$ajaxDetailTemplate_desc = "";
		$ajaxDetailTemplate      = \Redshop\Template\Helper::getAjaxDetailBox($row);

		if (count($ajaxDetailTemplate) > 0)
		{
			$ajaxDetailTemplate_desc = $ajaxDetailTemplate->template_desc;
		}

		$returns          = \RedshopHelperProduct::getProductUserfieldFromTemplate($ajaxDetailTemplate_desc);
		$templateUserField = $returns[0];
		$userFields       = $returns[1];

		if ($templateUserField != "")
		{
			$uField = "";

			for ($ui = 0; $ui < count($userFields); $ui++)
			{
				$productUserFields = \RedshopHelperExtrafields::listAllUserFields($userFields[$ui], 12, '', '', 0, $row->product_id);
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
				$hiddenUserField = "<div style='display:none;'><form method='post' action='' id='user_fields_form_" . $row->product_id . "' name='user_fields_form_" . $row->product_id . "'>" . $templateUserField . "</form></div>";
			}
		}
	}

	$ItemData = \RedshopHelperProduct::getMenuInformation(0, 0, '', 'product&pid=' . $row->product_id);

	if (count($ItemData) > 0)
	{
		$itemId = $ItemData->id;
	}
	else
	{
		$itemId = \RedshopHelperRouter::getItemId($row->product_id);
	}

	$link       = JRoute::_('index.php?option=com_redshop&view=product&pid=' . $row->product_id . '&Itemid=' . $itemId);

	echo "<div class='mod_redshop_shoppergroup_product'>";

	if ($image)
	{
		$thum_image = \Redshop\Product\Image\Image::getImage($row->product_id, $link, $thumbWidth, $thumbHeight);
		echo "<div class='mod_redshop_shoppergroup_product_image'>" . $thum_image . "</div>";
	}

	echo "<div class='mod_redshop_shoppergroup_product_title'><a href='" . $link . "' title=''>";
	echo ($params->get('crop_title_length') == 0) ? $row->product_name : trim(substr($row->product_name, 0, $params->get('crop_title_length'))) . $params->get('post_text');
	echo "</a></div>";

	if ($show_short_description)
	{
		echo "<div class='mod_redshop_shoppergroup_product_desc'>" . $row->product_s_desc . "</div>";
	}

	$product_price = \Redshop\Product\Price::getPrice($row->product_id, $show_vat);
	$productArr             = \RedshopHelperProductPrice::getNetPrice($row->product_id);
	$product_price_discount = $productArr['productPrice'] + $productArr['productVat'];

	if (!$row->not_for_sale && $show_price)
	{
		if (\Redshop::getConfig()->get('SHOW_PRICE') && (!\Redshop::getConfig()->get('DEFAULT_QUOTATION_MODE') || (\Redshop::getConfig()->get('DEFAULT_QUOTATION_MODE') && SHOW_QUOTATION_PRICE)))
		{
			if (!$product_price)
			{
				$product_price_dis = \RedshopHelperProductPrice::priceReplacement($product_price);
			}
			else
			{
				$product_price_dis = \RedshopHelperProductPrice::formattedPrice($product_price);
			}

			$disply_text = "<div class='mod_redshop_shoppergroup_product_price'>" . $product_price_dis . "</div>";

			if ($row->product_on_sale && $product_price_discount > 0)
			{
				if ($product_price > $product_price_discount)
				{
					$disply_text = "";
					$s_price     = $product_price - $product_price_discount;

					if ($show_discountpricelayout)
					{
						echo "<div id='mod_redoldprice' class='mod_redoldprice'><span style='text-decoration:line-through;'>" . \RedshopHelperProductPrice::formattedPrice($product_price) . "</span></div>";
						$product_price = $product_price_discount;
						echo "<div id='mod_redmainprice' class='mod_redmainprice'>" . \RedshopHelperProductPrice::formattedPrice($product_price_discount) . "</div>";
						echo "<div id='mod_redsavedprice' class='mod_redsavedprice'>" . JText::_('MOD_REDSHOP_SHOPPERGROUP_PRODUCT_PRODCUT_PRICE_YOU_SAVED') . ' ' . \RedshopHelperProductPrice::formattedPrice($s_price) . "</div>";
					}
					else
					{
						$product_price = $product_price_discount;
						echo "<div class='mod_redshop_shoppergroup_product_price'>" . \RedshopHelperProductPrice::formattedPrice($product_price) . "</div>";
					}
				}
			}

			echo $disply_text;
		}
	}

	if ($show_readmore)
	{
		echo "<div class='mod_redshop_shoppergroup_product_readmore'><a href='" . $link . "'>" . JText::_('MOD_REDSHOP_SHOPPERGROUP_PRODUCT_TXT_READ_MORE') . "</a>&nbsp;</div>";
	}

	if ($show_addtocart)
	{
		$addToCart = \Redshop\Cart\Render::replace($row->product_id, $row->category_id, 0, 0, "", false, $userFields, $totalAttributes, $row->total_accessories, $countUserFields, $module_id);
		echo "<div class='mod_redshop_shoppergroup_product_addtocart'>" . $addToCart . $hiddenUserField . "</div>";
	}

	echo "</div>";
}

echo "</div>";
