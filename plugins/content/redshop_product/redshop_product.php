<?php
/**
 * @package     RedSHOP
 * @subpackage  Plugin
 *
 * @copyright   Copyright (C) 2008 - 2017 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');

/**
 * Replaces textstring with link
 */
JLoader::import('redshop.library');

class plgContentredshop_product extends JPlugin
{
	public function onContentPrepare($context, &$row, &$params, $page = 0)
	{
		if (preg_match_all("/{redshop:.+?}/", $row->text, $matches, PREG_PATTERN_ORDER) > 0)
		{
			JHTML::_('behavior.tooltip');
			JHTML::_('behavior.modal');
			JHtml::_('redshopjquery.framework');

			JPluginHelper::importPlugin('redshop_product');
			$dispatcher = \RedshopHelperUtility::getDispatcher();

			$session = JFactory::getSession();
			$post    = JRequest::get('POST');

			if (isset($post['product_currency']))
			{
				$session->set('product_currency', $post['product_currency']);
			}

			$moduleId     = "plg_";
			$lang          = JFactory::getLanguage();

			// Or JPATH_ADMINISTRATOR if the template language file is only
			$lang->load('plg_content_redshop_product', JPATH_ADMINISTRATOR);
			$lang->load('com_redshop', JPATH_SITE);

			$plugin = JPluginHelper::getPlugin('content', 'redshop_product');
			$red_params = new JRegistry($plugin->params);

			// Get show price yes/no option
			$isShowPrice = trim($red_params->get('show_price', 0));
			$isShowPrice_with_vat = trim($red_params->get('show_price_with_vat', 1));
			$isShowDiscountPriceLayout = trim($red_params->get('show_discountpricelayout', 1));
			$redTemplate = Redtemplate::getInstance();
			$prtemplate_id = trim($red_params->get('product_template', 1));
			$prtemplate1 = $redTemplate->getTemplate('product_content_template', $prtemplate_id);
			$prtemplate_default = $prtemplate1[0]->template_desc;

			if ($prtemplate_default == "")
			{
				$prtemplate_default = '<div class="mod_redshop_products"><table border="0"><tbody><tr><td><div class="mod_redshop_products_image">{product_thumb_image}</div></td></tr><tr><td><div class="mod_redshop_products_title">{product_name}</div></td></tr><tr><td><div class="mod_redshop_products_price">{product_price}</div></td></tr><tr><td><div class="mod_redshop_products_readmore">{read_more}</div></td></tr><tr><td><div>{attribute_template:attributes}</div></td></tr><tr><td><div class="mod_redshop_product_addtocart">{form_addtocart:add_to_cart1}</div></td></tr></tbody></table></div>';
			}

			$matches = $matches[0];

			for ($i = 0, $countMatches = count($matches); $i < $countMatches; $i++)
			{
				$prtemplate = $prtemplate_default;
				$match = explode(":", $matches[$i]);
				$product_id = (int) (trim($match[1], '}'));
				$product = \Redshop\Product\Product::getProductById($product_id);
				$url = JURI::root();

				if (!$product->product_id)
				{
					$row->text = str_replace($matches[$i], '', $row->text);
					continue;
				}

				$dispatcher->trigger('onPrepareProduct', array(&$prtemplate, &$red_params, $product));

				// Changes for sh404sef duplicating url
				$catid = $row->cat_in_sefurl;
				$itemData = \RedshopHelperProduct::getMenuInformation(0, 0, '', 'product&pid=' . $product->product_id);

				if (count($itemData) > 0)
				{
					$productItemId = $itemData->id;
				}
				else
				{
					$productItemId = \RedshopHelperRouter::getItemId($product->product_id, $catid);
				}

				$defaultLink = 'index.php?option=com_redshop&view=product&pid=' . $product->product_id . '&cid=' . $catid . '&Itemid=' . $productItemId;
				$link = ($page == 1) ? $url . $defaultLink : JRoute::_($defaultLink);

				// End changes for sh404sef duplicating url

				if (strstr($prtemplate, "{product_thumb_image_3}"))
				{
					$pimg_tag = '{product_thumb_image_3}';
					$ph_thumb = \Redshop::getConfig()->get('PRODUCT_MAIN_IMAGE_HEIGHT_3');
					$pw_thumb = \Redshop::getConfig()->get('PRODUCT_MAIN_IMAGE_3');
				}
				elseif (strstr($prtemplate, "{product_thumb_image_2}"))
				{
					$pimg_tag = '{product_thumb_image_2}';
					$ph_thumb = \Redshop::getConfig()->get('PRODUCT_MAIN_IMAGE_HEIGHT_2');
					$pw_thumb = \Redshop::getConfig()->get('PRODUCT_MAIN_IMAGE_2');
				}
				elseif (strstr($prtemplate, "{product_thumb_image_1}"))
				{
					$pimg_tag = '{product_thumb_image_1}';
					$ph_thumb = \Redshop::getConfig()->get('PRODUCT_MAIN_IMAGE_HEIGHT');
					$pw_thumb = \Redshop::getConfig()->get('PRODUCT_MAIN_IMAGE');
				}
				else
				{
					$pimg_tag = '{product_thumb_image}';
					$ph_thumb = \Redshop::getConfig()->get('PRODUCT_MAIN_IMAGE_HEIGHT');
					$pw_thumb = \Redshop::getConfig()->get('PRODUCT_MAIN_IMAGE');
				}

				$hidden_thumb_image = "<input type='hidden' name='prd_main_imgwidth' id='prd_main_imgwidth' value='"
						. $pw_thumb . "'><input type='hidden' name='prd_main_imgheight' id='prd_main_imgheight' value='"
						. $ph_thumb . "'>";
				$thumbImage = \Redshop\Product\Image\Image::getImage($product_id, $link, $pw_thumb, $ph_thumb, 2, 1);
				$prtemplate = str_replace($pimg_tag, $thumbImage . $hidden_thumb_image, $prtemplate);
				$product_name = "<a href='" . $link . "' title=''>" . $product->product_name . "</a>";
				$prtemplate = str_replace("{product_name}", $product_name, $prtemplate);

				if (strstr($prtemplate, "{product_desc}"))
				{
					$prtemplate = str_replace("{product_desc}", $product->product_s_desc, $prtemplate);
				}

				if (strstr($prtemplate, "{product_price}"))
				{
					$pr_price = '';

					if ($isShowPrice && \Redshop::getConfig()->get('SHOW_PRICE'))
					{
						$productPrice = \Redshop\Product\Price::getPrice($product->product_id, $isShowPrice_with_vat);
						$productArr = \RedshopHelperProductPrice::getNetPrice($product->product_id, 0, 1);
						$productPrice_discount = $productArr['productPrice'];
						$productPrice_discountVat = $productArr['productVat'];

						if ($isShowPrice_with_vat)
						{
							$productPrice_discount += $productPrice_discountVat;
						}

						if ($product->product_on_sale && $productPrice_discount > 0)
						{
							if ($productPrice > $productPrice_discount)
							{
								$s_price = $productPrice - $productPrice_discount;

								if ($isShowDiscountPriceLayout)
								{
									$pr_price = "<div id='mod_redsavedprice' class='mod_redsavedprice'>"
										. JText::_('COM_REDSHOP_PRODCUT_PRICE_YOU_SAVED') . ' '
										. \RedshopHelperProductPrice::formattedPrice($s_price) . "</div>";
								}
								else
								{
									$productPrice = $productPrice_discount;
									$pr_price = \RedshopHelperProductPrice::formattedPrice($productPrice);
								}
							}
							else
							{
								$pr_price = \RedshopHelperProductPrice::formattedPrice($productPrice);
							}
						}
						else
						{
							$pr_price = \RedshopHelperProductPrice::formattedPrice($productPrice);
						}
					}

					$prtemplate = str_replace("{product_price}", $pr_price, $prtemplate);
				}

				if (strstr($prtemplate, "{read_more}"))
				{
					$read_more = "<a href='" . $link . "'>" . JText::_('PLG_CONTENT_REDSHOP_PRODUCT_TXT_READ_MORE') . "</a>";
					$prtemplate = str_replace("{read_more}", $read_more, $prtemplate);
				}

				/*
				 * Product attribute  Start
				 */
				$setOfAttributes = [];

				if ($product->attribute_set_id > 0)
				{
					$setOfAttributes = \Redshop\Product\Attribute::getProductAttribute(0, $product->attribute_set_id, 0, 1);
				}

				$attributes = \Redshop\Product\Attribute::getProductAttribute($product->product_id);
				$attributes = array_merge($attributes, $setOfAttributes);
				$totalAttributes = count($attributes);

				/*
				 * Product accessory Start
				 */
				$accessory = \RedshopHelperAccessory::getProductAccessories(0, $product->product_id);
				$totalAccessory = count($accessory);

				// Product User Field Start
				$countUserFields = 0;
				$returns = \Redshop\Product\Product::getProductUserfieldFromTemplate($prtemplate);
				$templateUserField = $returns[0];
				$userFields = $returns[1];

				if (strstr($prtemplate, "{if product_userfield}") && strstr($prtemplate, "{product_userfield end if}") && $templateUserField != "")
				{
					$uField = "";
					$cart = $session->get('cart');
					$idx = 0;

					if (isset($cart['idx']))
					{
						$idx = (int) ($cart['idx']);
					}

					$cart_id = '';

					for ($j = 0; $j < $idx; $j++)
					{
						if ($cart[$j]['product_id'] == $this->data->product_id)
						{
							$cart_id = $j;
						}
					}

					for ($ui = 0, $countUserfield = count($userFields); $ui < $countUserfield; $ui++)
					{
						if (!$idx)
						{
							$cart_id = "";
						}

						$productUserFields = \RedshopHelperExtrafields::listAllUserFields($userFields[$ui], 12, '', $cart_id, 0, $this->data->product_id);

						$uField .= $productUserFields[1];

						if ($productUserFields[1] != "")
						{
							$countUserFields++;
						}

						$prtemplate = str_replace('{' . $userFields[$ui] . '_lbl}', $productUserFields[0], $prtemplate);
						$prtemplate = str_replace('{' . $userFields[$ui] . '}', $productUserFields[1], $prtemplate);
					}

					$productUserFieldsForm = "<form method='post' action='' id='user_fields_form' name='user_fields_form'>";

					if ($uField != "")
					{
						$prtemplate = str_replace("{if product_userfield}", $productUserFieldsForm, $prtemplate);
						$prtemplate = str_replace("{product_userfield end if}", "</form>", $prtemplate);
					}
					else
					{
						$prtemplate = str_replace("{if product_userfield}", "", $prtemplate);
						$prtemplate = str_replace("{product_userfield end if}", "", $prtemplate);
					}
				}

				// Product User Field End

				$childproduct = \RedshopHelperProduct::getChildProduct($product->product_id);

				if (count($childproduct) > 0)
				{
					$isChilds = true;
					$attributes = [];
				}
				else
				{
					$isChilds = false;

					// Get attributes
					$setOfAttributes = [];

					if ($product->attribute_set_id > 0)
					{
						$setOfAttributes = \Redshop\Product\Attribute::getProductAttribute(0, $product->attribute_set_id, 0, 1);
					}

					$attributes = \Redshop\Product\Attribute::getProductAttribute($product->product_id);
					$attributes = array_merge($attributes, $setOfAttributes);
				}

				$prtemplate = \Redshop\Cart\Render::replace(
					$product->product_id,
					0,
					0,
					0,
					$prtemplate,
					false,
					$userFields,
					$totalAttributes,
					$totalAccessory,
					$countUserFields,
					$moduleId
				);

				$attribute_template = \Redshop\Template\Helper::getAttribute($prtemplate);
				$prtemplate = \RedshopHelperAttribute::replaceAttributeData($product->product_id, 0, 0, $attributes, $prtemplate, $attribute_template, $isChilds);

				$dispatcher->trigger('onAfterDisplayProduct', array(&$prtemplate, &$red_params, $product));

				$row->text = str_replace($matches[$i], $prtemplate, $row->text);
			}
		}

		return true;
	}
}
