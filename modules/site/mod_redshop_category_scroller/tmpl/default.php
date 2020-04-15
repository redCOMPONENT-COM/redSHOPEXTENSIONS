<?php
/**
 * @package     RedSHOP.Frontend
 * @subpackage  mod_redshop_category_scroller
 *
 * @copyright   Copyright (C) 2008 - 2015 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;
JHTML::_('behavior.tooltip');
JHTML::_('behavior.modal');

$uri = JURI::getInstance();
$url = $uri->root();

$itemId = JRequest::getInt('Itemid');
$user   = JFactory::getUser();

JHtml::_('redshopjquery.framework');
$document = JFactory::getDocument();
$document->addStyleSheet(JURI::base() . 'modules/mod_redshop_products/css/products.css');

// Light-box Java-script
JHtml::script('com_redshop/redbox.js', false, true);
JHtml::script('com_redshop/attribute.js', false, true);
JHtml::script('com_redshop/common.js', false, true);

$config = Redconfiguration::getInstance();

$view     = JRequest::getCmd('view', 'category');
$moduleId = "mod_" . $module->id;

$document = JFactory::getDocument();
$document->addStyleSheet("modules/mod_redshop_category_scroller/css/jquery.css");
$document->addStyleSheet("modules/mod_redshop_category_scroller/css/skin_002.css");
$document->addStyleSheet("modules/mod_redshop_category_scroller/css/slick.css");


if ($view == 'category') {
    if (empty($GLOBALS['product_price_slider'])) {
        JHtml::script('com_redshop/jquery.tools.min.js', false, true);
    }
} else {
    JHtml::script('com_redshop/jquery.tools.min.js', false, true);
}

$document->addScript('modules/mod_redshop_category_scroller/js/slick.min.js');

$document->addScriptDeclaration(
    "jQuery(document).ready(function () {
    jQuery('#rs_category_scroller_" . $module->id . "').slick({
		slidesToShow: 3,
		slidesToScroll: 1,
		autoplay: 0,
		autoplaySpeed: 2000,
		prevArrow: '<div class=\"red_product-prev red_product-prev-horizontal\"></div>',
		nextArrow: '<div class=\"red_product-next red_product-next-horizontal\"></div>'
		
    });
});"
);

echo $pretext;
echo "<div>";
echo "<div>
		<div class='red_product-skin-produkter'>
		<div style='display: block;' class='red_product-container red_product-container-horizontal'>
		<div class='red_product-clip red_product-clip-horizontal' style='width: " . $scrollerWidth . "px;'>
		<ul id='rs_category_scroller_" . $module->id . "' class='red_product-list red_product-list-horizontal'>";

for ($i = 0, $countRows = count($rows); $i < $countRows; $i++) {
    $row = $rows[$i];

    $itemData = \RedshopHelperProduct::getMenuInformation(0, 0, '', 'product&pid=' . $row->product_id);

    if (isset($itemData->id)) {
        $itemId = $itemData->id;
    } else {
        $itemId = \RedshopHelperRouter::getItemId($row->product_id);
    }

    $categoryAttach = '';

    if (isset($row->cat_in_sefurl)) {
        $categoryAttach = '&cid=' . $row->cat_in_sefurl;
    }

    $link = JRoute::_(
        'index.php?option=com_redshop&view=product&pid=' . $row->product_id . $categoryAttach . '&Itemid=' . $itemId
    );
    $url  = JURI::base();
    echo "<li red_productindex='" . $i . "' class='red_product-item red_product-item-horizontal'><div class='listing-item'><div class='product-shop'>";

    if ($isShowProductName) {
        $productName = $config->maxchar($row->product_name, $productTitleMaxChars, $productTitleEndSuffix);
        echo "<a href='" . $link . "' title='" . $row->product_name . "'>" . $productName . "</a>";
    }

    if (\Redshop::getConfig()->get('SHOW_PRICE') && !\Redshop::getConfig()->get(
            'USE_AS_CATALOG'
        ) && !\Redshop::getConfig()->get('DEFAULT_QUOTATION_MODE') && $isShowPrice && !$row->not_for_sale) {
        $productArr         = \RedshopHelperProductPrice::getNetPrice($row->product_id);
        $productPrice       = \RedshopHelperProductPrice::priceReplacement($productArr['product_price']);
        $productPriceSaving = \RedshopHelperProductPrice::priceReplacement($productArr['product_price_saving']);
        $productOldPrice    = \RedshopHelperProductPrice::priceReplacement($productArr['product_old_price']);

        if ($isShowDiscountPriceLayout) {
            echo "<div id='mod_redoldprice' class='mod_redoldprice'><span style='text-decoration:line-through;'>"
                . $productOldPrice . "</span></div>";
            echo "<div id='mod_redmainprice' class='mod_redmainprice'>" . $productPrice . "</div>";
            echo "<div id='mod_redsavedprice' class='mod_redsavedprice'>"
                . \JText::_('COM_REDSHOP_PRODCUT_PRICE_YOU_SAVED') . ' ' . $productPriceSaving . "</div>";
        } else {
            echo "<div class='mod_redproducts_price'>" . $productPrice . "</div>";
        }
    }

    if ($isShowReadMore) {
        echo "<div class='mod_redshop_category_scroller_readmore'><a href='" . $link . "'>"
            . JText::_('COM_REDSHOP_TXT_READ_MORE') . "</a></div>";
    }

    echo "</div>";

    if ($isShowImage) {
        $productImage = "";

        if (JFile::exists(REDSHOP_FRONT_IMAGES_RELPATH . "/product/" . $row->product_full_image)) {
            $productImage = \RedShopHelperImages::getImagePath(
                $row->product_full_image,
                '',
                'thumb',
                'product',
                $thumbWidth,
                $thumbHeight,
                \Redshop::getConfig()->get('USE_IMAGE_SIZE_SWAPPING')
            );
        } elseif (JFile::exists(REDSHOP_FRONT_IMAGES_RELPATH . "/product/" . $row->product_thumb_image)) {
            $productImage = RedShopHelperImages::getImagePath(
                $row->product_thumb_image,
                '',
                'thumb',
                'product',
                $thumbWidth,
                $thumbHeight,
                \Redshop::getConfig()->get('USE_IMAGE_SIZE_SWAPPING')
            );
        } else {
            $productImage = REDSHOP_FRONT_IMAGES_ABSPATH . "noimage.jpg";
        }

        $thumbImage = "<a href='" . $link . "'><img style='width:" . $thumbWidth . "px;height:" . $thumbHeight . "px;' src='" . $productImage . "'></a>";
        echo "<div class='product-image' style='width:" . $thumbWidth . "px;height:" . $thumbHeight . "px;'>" . $thumbImage . "</div>";
    }

    if ($isShowAddToCart) {
        // Product attribute  Start
        $setOfAttributes = [];

        if ($row->attribute_set_id > 0) {
            $setOfAttributes = \Redshop\Product\Attribute::getProductAttribute(0, $row->attribute_set_id, 0, 1);
        }

        $attributes      = \Redshop\Product\Attribute::getProductAttribute($row->product_id);
        $attributes      = array_merge($attributes, $setOfAttributes);
        $totalAttributes = is_array($attributes) ? count($attributes) : 0;

        // Product accessory Start
        $accessory      = \RedshopHelperAccessory::getProductAccessories(0, $row->product_id);
        $totalAccessory = is_array($accessory) ? count($accessory) : 0;

        $addToCartData = \Redshop\Cart\Render::replace(
            $row->product_id,
            0,
            0,
            0,
            "",
            false,
            [],
            $totalAttributes,
            $totalAccessory,
            0,
            $moduleId
        );
        echo "<div class='form-button'>" . $addToCartData . "<div>";
    }

    echo "</div></li>";
}

echo "</ul></div></div></div></div></div>";
