<?php
/**
 * @package     RedSHOP.Frontend
 * @subpackage  mod_redshop_products
 *
 * @copyright   Copyright (C) 2008 - 2015 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

JHtml::_('behavior.tooltip');
JHtml::_('behavior.modal');

$url         = JUri::root();
$fullUrl     = JUri::getInstance()->toString();
$loadmoreUrl = $fullUrl . (strpos($fullUrl, '?') === false ? '?' : '&') . 'loadmore=1';

$Itemid = JFactory::getApplication()->input->getInt('Itemid');
$user   = JFactory::getUser();

$document = JFactory::getDocument();
$document->addStyleSheet('modules/mod_redshop_products/css/products.css');

// Lightbox Javascript
JHtml::script('com_redshop/redshop.attribute.min.js', false, true);
JHtml::script('com_redshop/redshop.common.min.js', false, true);
JHtml::script('com_redshop/redshop.redbox.min.js', false, true);

$producthelper   = productHelper::getInstance();
$extraField      = extraField::getInstance();
$stockroomhelper = rsstockroomhelper::getInstance();


echo "<div class=\"mod_redshop_products_wrapper\">";

$moduleId = "mod_" . $module->id;

for ($i = 0, $in = count($rows); $i < $in; $i++)
{
	$row = $rows[$i];

	if ($showStockroomStatus == 1)
	{
		$isStockExists = $stockroomhelper->isStockExists($row->product_id);

		if (!$isStockExists)
		{
			$isPreorderStockExists = $stockroomhelper->isPreorderStockExists($row->product_id);
		}

		if (!$isStockExists)
		{
			$productPreorder = $row->preorder;

			if (($productPreorder == "global" && ALLOW_PRE_ORDER) || ($productPreorder == "yes") || ($productPreorder == "" && ALLOW_PRE_ORDER))
			{
				if (!$isPreorderStockExists)
				{
					$stockStatus = "<div class=\"modProductStockStatus mod_product_outstock\"><span></span>" . JText::_('COM_REDSHOP_OUT_OF_STOCK') . "</div>";
				}
				else
				{
					$stockStatus = "<div class=\"modProductStockStatus mod_product_preorder\"><span></span>" . JText::_('COM_REDSHOP_PRE_ORDER') . "</div>";
				}
			}
			else
			{
				$stockStatus = "<div class=\"modProductStockStatus mod_product_outstock\"><span></span>" . JText::_('COM_REDSHOP_OUT_OF_STOCK') . "</div>";
			}
		}
		else
		{
			$stockStatus = "<div class=\"modProductStockStatus mod_product_instock\"><span></span>" . JText::_('COM_REDSHOP_AVAILABLE_STOCK') . "</div>";
		}
	}

			$categoryId = $producthelper->getCategoryProduct($row->product_id);
			$ItemData   = $producthelper->getMenuInformation(0, 0, '', 'product&pid=' . $row->product_id);

			if (count($ItemData) > 0)
			{
				$Itemid = $ItemData->id;
			}
			else
			{
				$Itemid = RedshopHelperRouter::getItemId($row->product_id, $categoryId);
			}

			$link         = JRoute::_('index.php?option=com_redshop&view=product&pid=' . $row->product_id . '&cid=' . $categoryId . '&Itemid=' . $Itemid);
			$wrapperClass = isset($verticalProduct) && $verticalProduct ? 'mod_redshop_products' : 'mod_redshop_products_horizontal';
			?>
            <div class="<?php echo $wrapperClass ?>">
				<?php $productInfo = $producthelper->getProductById($row->product_id); ?>
				<?php if ($image): ?>
					<?php $thumb = $productInfo->product_full_image; ?>
					<?php if (Redshop::getConfig()->get('WATERMARK_PRODUCT_IMAGE')): ?>
						<?php $thumImage = RedshopHelperMedia::watermark('product', $thumb, $thumbWidth, $thumbHeight, Redshop::getConfig()->get('WATERMARK_PRODUCT_THUMB_IMAGE'), '0'); ?>
                        <div class="mod_redshop_products_image"><img src="<?php echo $thumImage ?>"></div>
					<?php else: ?>
						<?php $thumImage = RedShopHelperImages::getImagePath(
							$thumb,
							'',
							'thumb',
							'product',
							$thumbWidth,
							$thumbHeight,
							Redshop::getConfig()->get('USE_IMAGE_SIZE_SWAPPING')
						); ?>
                        <div class="mod_redshop_products_image">
                            <a href="<?php echo $link ?>" title="<?php echo $row->product_name ?>">
                                <img src="<?php echo $thumImage ?>">
                            </a>
                        </div>
					<?php endif; ?>
				<?php endif; ?>
				<?php if (!empty($stockStatus)): ?>
					<?php echo $stockStatus ?>
				<?php endif; ?>
                <div class="mod_redshop_products_title">
                    <a href="<?php echo $link ?>" title=""><?php echo $row->product_name ?></a>
                </div>
				<?php if ($showShortDescription): ?>
                    <div class="mod_redshop_products_desc"><?php echo $row->product_s_desc ?></div>
				<?php endif; ?>
				<?php
				if (!$row->not_for_sale && $showPrice)
				{
					$productArr = $producthelper->getProductNetPrice($row->product_id);

					if ($showVat != '0')
					{
						$productPrice         = $productArr['product_main_price'];
						$productPriceDiscount = $productArr['productPrice'] + $productArr['productVat'];
						$productOldPrice      = $productArr['product_old_price'];
					}
					else
					{
						$productPrice         = $productArr['product_price_novat'];
						$productPriceDiscount = $productArr['productPrice'];
						$productOldPrice      = $productArr['product_old_price_excl_vat'];
					}

					if (Redshop::getConfig()->getBool('SHOW_PRICE') && (!Redshop::getConfig()->get('DEFAULT_QUOTATION_MODE') || (Redshop::getConfig()->get('DEFAULT_QUOTATION_MODE') && Redshop::getConfig()->get('SHOW_QUOTATION_PRICE'))))
					{
						if (!$productPrice)
						{
							$productDiscountPrice = $producthelper->getPriceReplacement($productPrice);
						}
						else
						{
							$productDiscountPrice = $producthelper->getProductFormattedPrice($productPrice);
						}

						$displyText = '<div class=\"mod_redshop_products_price\">' . $productDiscountPrice . '</div>';

						if ($row->product_on_sale && $productPriceDiscount > 0)
						{
							if ($productOldPrice > $productPriceDiscount)
							{
								$displyText  = '';
								$savingPrice = $productOldPrice - $productPriceDiscount;
								?>
								<?php if ($showDiscountPriceLayout): ?>
								<?php $productPrice = $productPriceDiscount; ?>
                                <div id="mod_redoldprice"
                                     class="mod_redoldprice"><?php echo $producthelper->getProductFormattedPrice($productOldPrice) ?></div>
                                <div id="mod_redmainprice"
                                     class="mod_redmainprice"><?php echo $producthelper->getProductFormattedPrice($productPriceDiscount) ?></div>
                                <div id="mod_redsavedprice"
                                     class="mod_redsavedprice"><?php echo JText::_('COM_REDSHOP_PRODCUT_PRICE_YOU_SAVED') . ' ' . $producthelper->getProductFormattedPrice($savingPrice) ?></div>
							<?php else: ?>
								<?php $productPrice = $productPriceDiscount; ?>
                                <div class="mod_redshop_products_price"><?php echo $producthelper->getProductFormattedPrice($productPrice) ?></div>
							<?php endif; ?>
								<?php
							}
						}

						echo $displyText;
					}
				}
				?>
				<?php if ($showWishlist): ?>
                    <div class="wishlist"><?php echo $producthelper->replaceWishlistButton($row->product_id, '{wishlist_link}') ?></div>
				<?php endif; ?>
				<?php if ($showReadmore): ?>
                    <div class="mod_redshop_products_readmore">
                        <a href="<?php echo $link ?>"><?php echo JText::_('COM_REDSHOP_TXT_READ_MORE') ?></a>&nbsp;
                    </div>
				<?php endif; ?>
				<?php if (isset($showAddToCart) && $showAddToCart): ?>
					<?php
					// Product attribute  Start
					$attributesSet = array();

					if ($row->attribute_set_id > 0)
					{
						$attributesSet = $producthelper->getProductAttribute(0, $row->attribute_set_id, 0, 1);
					}

					$attributes = $producthelper->getProductAttribute($row->product_id);
					$attributes = array_merge($attributes, $attributesSet);
					$totalatt   = count($attributes);
					// Product attribute  End

					// Product accessory Start
					$accessory      = $producthelper->getProductAccessory(0, $row->product_id);
					$totalAccessory = count($accessory);
					// Product accessory End

					/*
					 * collecting extra fields
					 */
					$countNoUserField = 0;
					$hiddenUserField  = '';
					$userfieldArr     = array();

					if (Redshop::getConfig()->getBool('AJAX_CART_BOX'))
					{
						$ajaxDetailTemplateDesc = "";
						$ajaxDetailTemplate     = $producthelper->getAjaxDetailboxTemplate($row);

						if (count($ajaxDetailTemplate) > 0)
						{
							$ajaxDetailTemplateDesc = $ajaxDetailTemplate->template_desc;
						}

						$returnArr         = $producthelper->getProductUserfieldFromTemplate($ajaxDetailTemplateDesc);
						$templateUserfield = $returnArr[0];
						$userfieldArr      = $returnArr[1];

						if (!empty($templateUserfield))
						{
							$ufield = '';

							foreach ($userfieldArr as $item)
							{
								$productUserfields = $extraField->list_all_user_fields($item, 12, '', '', 0, $row->product_id);
								$ufield            .= $productUserfields[1];

								if (!empty($productUserfields[1]))
								{
									$countNoUserField++;
								}

								$templateUserfield = str_replace('{' . $item . '_lbl}', $productUserfields[0], $templateUserfield);
								$templateUserfield = str_replace('{' . $item . '}', $productUserfields[1], $templateUserfield);
							}

							if ($ufield != "")
							{
								$hiddenUserField = '<div class=\"hiddenFields\">'
									. '<form method=\"post\" action=\"\" id=\"user_fields_form_' . $row->product_id . '\" name=\"user_fields_form_' . $row->product_id . '\">'
									. $templateUserfield . '</form></div>';
							}
						}
					}
					// End
					$addtocart = $producthelper->replaceCartTemplate(
						$row->product_id, $categoryId, 0, 0, "", false,
						$userfieldArr, $totalatt, $totalAccessory, $countNoUserField, $moduleId
					);
					?>
                    <div class="mod_redshop_products_addtocart"><?php echo $addtocart . $hiddenUserField ?></div>
				<?php endif; ?>
            </div>
		<?php endforeach; ?>
    </div>
	<?php if ($showLoadmore): ?>
        <div id="<?php echo $moduleId ?>_loadmorewrapper" class="text-center">
            <div id="<?php echo $moduleId ?>_loadmorebtn"
                 class="btn btn-primary"><?php echo $loadmoreBtnText ?></div>
            <img id="<?php echo $moduleId ?>_loadmoreloading"
                 src="<?php echo $url ?>/components/com_redshop/assets/images/loading.gif"/>
        </div>
	<?php endif; ?>
</div>
<script type="text/javascript">
    (function ($) {
        $(document).ready(function () {
            $('#<?php echo $moduleId ?>_loadmoreloading').hide();

            $('#<?php echo $moduleId ?>_loadmorebtn').on('click', function () {
                $(this).hide();
                $('#<?php echo $moduleId ?>_loadmoreloading').show();

                $.ajax({
                    url: '<?php echo $loadmoreUrl ?>',
                    success: function (html) {
                        var productsHtml = $(html).find('#<?php echo $moduleId ?>_products_wrapper');

                        productsHtml.insertBefore('#<?php echo $moduleId ?>_loadmorewrapper');

                        if (productsHtml.find('.<?php echo $wrapperClass ?>').length == <?php echo $loadmoreCount ?>) {
                            $('#<?php echo $moduleId ?>_loadmorebtn').show();
                        }

                        $('#<?php echo $moduleId ?>_loadmoreloading').hide();
                    }
                });
            });
        });
    })(jQuery);
</script>
