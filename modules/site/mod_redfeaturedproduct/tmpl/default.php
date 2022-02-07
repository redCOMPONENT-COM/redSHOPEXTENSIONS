<?php
/**
 * @package     RedSHOP.Module
 * @subpackage  mod_redfeaturedproduct
 *
 * @copyright   Copyright (C) 2008 - 2015 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

$redConfig = Redconfiguration::getInstance();
$uri = JURI::getInstance();
$url = $uri->root();
$user = JFactory::getUser();
$app = JFactory::getApplication();
$itemId = $app->input->getInt('Itemid', 0);
$view = $app->input->getCmd('view', 'category');
$cid = $app->input->getInt('cid');

$document = JFactory::getDocument();
JHTML::stylesheet('modules/mod_redfeaturedproduct/css/jquery.css');
JHTML::stylesheet('modules/mod_redfeaturedproduct/css/skin_002.css');
JHtml::_('redshopjquery.framework');

if ($view == 'category') {
    if (!isset($GLOBALS['product_price_slider'])) {
        JHtml::script('com_redshop/jquery.tools.min.js', false, true);
    }
} else {
    JHTML::script('com_redshop/redbox.js', false, true);
    JHtml::script('com_redshop/attribute.js', false, true);
    JHtml::script('com_redshop/common.js', false, true);
    JHtml::script('com_redshop/jquery.tools.min.js', false, true);
}

JHTML::script('com_redshop/carousel.js', false, true);
$document->addScriptDeclaration("jQuery(document).ready(function() {
	jQuery('#produkt_carousel_mod_" . $module->id . "').red_product({
		wrap: 'last',
		scroll: 1,
		auto: 6,
		animation: 'slow',
		easing: 'swing',
		itemLoadCallback: jQuery.noConflict()
	});
});");

echo $params->get('pretext', "");

if (count($list) > 0) {
    $rightArrow = $scrollerWidth + 20; ?>
    <div class="red_product-skin-produkter">
        <div class="red_product-container red_product-container-horizontal">
            <div class="red_product-prev red_product-prev-horizontal"></div>
            <div style="left:<?php echo $rightArrow; ?>px;"
                 class="red_product-next red_product-next-horizontal"></div>
            <div style="width:<?php echo $scrollerWidth; ?>px;height:<?php echo $scrollerHeight; ?>px;" class="red_product-clip red_product-clip-horizontal">
                <ul id="produkt_carousel_mod_<?php echo $module->id; ?>"
                    class="red_product-list red_product-list-horizontal">
                    <?php $i = 0;

                    foreach ($list as $row) {
                        $itemData = \RedshopHelperProduct::getMenuInformation(0, 0, '',
                            'product&pid=' . $row->product_id);

                        if (isset($itemData->id)) {
                            $itemId = $itemData->id;
                        } else {
                            $itemId = \RedshopHelperRouter::getItemId($row->product_id);
                        }

                        if (!$cid) {
                            $cid = \RedshopHelperProduct::getCategoryProduct($row->product_id);
                        }

                        $link = JRoute::_('index.php?option=com_redshop&view=product&pid=' . $row->product_id . '&cid=' . $cid . '&Itemid=' . $itemId);
                        $productImage = "";

                        if (JFile::exists(REDSHOP_FRONT_IMAGES_RELPATH . "product/" . $row->product_full_image)) {
                            $productImage = RedShopHelperImages::getImagePath(
                                $row->product_full_image,
                                '',
                                'thumb',
                                'product',
                                $thumbWidth,
                                $thumbHeight,
                                \Redshop::getConfig()->get('USE_IMAGE_SIZE_SWAPPING')
                            );
                        } elseif (JFile::exists(REDSHOP_FRONT_IMAGES_RELPATH . "product/" . $row->product_thumb_image)) {
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
                            $productImage = REDSHOP_FRONT_IMAGES_ABSPATH . 'noimage.jpg';
                        }

                        $productThumbImage = "<a href=\"" . $link . "\" title=\"\" ><img src=\"" . $productImage . "\"></a>";
                        ?>

                        <li red_productindex="<?php echo $i; ?>" class="red_product-item red_product-item-horizontal">
                            <div class="listing-item">
                                <div class="product-shop">
                                    <?php
                                    if ($params->get('show_product_name', 1)) {
                                        echo "<div class=\"mod_redproducts_title\"><a href=\"" . $link . "\" title=\"" .
                                            $row->product_name . "\">" . $row->product_name . "</a></div>";
                                    }

                                    if (!$row->not_for_sale && $params->get('show_price', 1)) {
                                        $products = \RedshopHelperProductPrice::getNetPrice($row->product_id);

                                        if ($params->get('show_vatprice', "0")) {
                                            $productPrice = $products['product_main_price'];
                                            $productPriceDiscount = $products['productPrice'] + $products['productVat'];
                                        } else {
                                            $productPrice = $products['product_price_novat'];
                                            $productPriceDiscount = $products['productPrice'];
                                        }

                                        if (\Redshop::getConfig()->get('SHOW_PRICE') && !\Redshop::getConfig()->get('USE_AS_CATALOG') && (!\Redshop::getConfig()->get('DEFAULT_QUOTATION_MODE') || (\Redshop::getConfig()->get('DEFAULT_QUOTATION_MODE') && \Redshop::getConfig()->get('SHOW_QUOTATION_PRICE')))) {
                                            if (!$productPrice) {
                                                $productPriceDis = \RedshopHelperProductPrice::priceReplacement($productPrice);
                                            } else {
                                                $productPriceDis = \RedshopHelperProductPrice::formattedPrice($productPrice);
                                            }

                                            $displayText = "<div class=\"mod_redproducts_price\">" . $productPriceDis . "</div>";

                                            if ($row->product_on_sale && $productPriceDiscount > 0) {
                                                if ($productPrice > $productPriceDiscount) {
                                                    $displayText = "";
                                                    $s_price = $productPrice - $productPriceDiscount;

                                                    if ($params->get('show_discountpricelayout', "100")) {
                                                        echo "<div id=\"mod_redoldprice\" class=\"mod_redoldprice\"><span>" . \RedshopHelperProductPrice::formattedPrice($productPrice) . "</span></div>";
                                                        echo "<div id=\"mod_redmainprice\" class=\"mod_redmainprice\">" . \RedshopHelperProductPrice::formattedPrice($productPriceDiscount) . "</div>";
                                                        echo "<div id=\"mod_redsavedprice\" class=\"mod_redsavedprice\">" . JText::_('MOD_REDFEATUREDPRODUCT_PRODUCT_PRICE_YOU_SAVED') . ' ' . \RedshopHelperProductPrice::formattedPrice($s_price) . "</div>";
                                                    } else {
                                                        echo "<div class=\"mod_redproducts_price\">" . \RedshopHelperProductPrice::formattedPrice($productPriceDiscount) . "</div>";
                                                    }
                                                }
                                            }

                                            echo $displayText;
                                        }
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="product-image"
                                 style="width:<?php echo $thumbWidth; ?>px;height:<?php echo $thumbHeight; ?>px;">
                                <?php echo $productThumbImage; ?>
                            </div>
                            <?php
                            if ($params->get('show_addtocart', 1)) {
                                $attributes = \Redshop\Product\Attribute::getProductAttribute($row->product_id);
                                $totalAttributes = count($attributes);

                                $addToCartTemplates = \RedshopHelperTemplate::getTemplate('add_to_cart');
                                $templateName = $addToCartTemplates[0]->name ?? 'add_to_cart1';
                                $addToCartTemplate = '{form_addtocart:' . $templateName . '}';

                                $addToCartData = \Redshop\Cart\Render::replace($row->product_id, 0, 0, 0,
                                    $addToCartTemplate, false, [],
                                    $totalAttributes, 0, 0, $module->id);
                                echo "<div class=\"form-button\">" . $addToCartData . "</div>";
                            }
                            ?>
                        </li>
                        <?php $i++;
                    }
                    ?>
                </ul>
            </div>
        </div>
    </div>
    <?php
} else {
    echo "<div>" . JText::_("MOD_REDFEATUREDPRODUCT_NO_FEATURED_PRODUCTS_TO_DISPLAY") . "</div>";
}
