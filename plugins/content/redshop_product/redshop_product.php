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
			$redParams = new JRegistry($plugin->params);

			// Get show price yes/no option
			$prTemplateId = trim($redParams->get('product_template', 1));
			$prTemplate1 = RedshopHelperTemplate::getTemplate('product_content_template', $prTemplateId);
			$prTemplateDefault = $prTemplate1[0]->template_desc;

			if ($prTemplateDefault == "")
			{
				$prTemplateDefault = '<div class="mod_redshop_products"><table border="0"><tbody><tr><td><div class="mod_redshop_products_image">{product_thumb_image}</div></td></tr><tr><td><div class="mod_redshop_products_title">{product_name}</div></td></tr><tr><td><div class="mod_redshop_products_price">{product_price}</div></td></tr><tr><td><div class="mod_redshop_products_readmore">{read_more}</div></td></tr><tr><td><div>{attribute_template:attributes}</div></td></tr><tr><td><div class="mod_redshop_product_addtocart">{form_addtocart:add_to_cart1}</div></td></tr></tbody></table></div>';
			}

			$matches = $matches[0];

			for ($i = 0, $countMatches = count($matches); $i < $countMatches; $i++)
			{
				$prTemplate = $prTemplateDefault;
				$match = explode(":", $matches[$i]);
				$productId = (int) (trim($match[1], '}'));
				$product = \Redshop\Product\Product::getProductById($productId);
				$url = JURI::root();

				if (!$product->product_id)
				{
					$row->text = str_replace($matches[$i], '', $row->text);
					continue;
				}

				$dispatcher->trigger('onPrepareProduct', array(&$prTemplate, &$redParams, $product));

				$prTemplate = \RedshopTagsReplacer::_(
					'product',
					$prTemplate,
					[
						'data' => $product
					]);

				if (strstr($prTemplate, "{read_more}"))
				{
					$catId = $product->cat_in_sefurl;
					$itemData = \RedshopHelperProduct::getMenuInformation(0, 0, '', 'product&pid=' . $product->product_id);

					if (!empty($itemData->id))
					{
						$productItemId = $itemData->id;
					}
					else
					{
						$productItemId = \RedshopHelperRouter::getItemId($product->product_id, $catId);
					}

					$defaultLink = 'index.php?option=com_redshop&view=product&pid=' . $product->product_id . '&cid=' . $catId . '&Itemid=' . $productItemId;
					$link = ($page == 1) ? $url . $defaultLink : JRoute::_($defaultLink);

					$readMore = "<a href='" . $link . "'>" . JText::_('PLG_CONTENT_REDSHOP_PRODUCT_TXT_READ_MORE') . "</a>";
					$prTemplate = str_replace("{read_more}", $readMore, $prTemplate);
				}

				$dispatcher->trigger('onAfterDisplayProduct', array(&$prTemplate, &$redParams, $product));

				$row->text = str_replace($matches[$i], $prTemplate, $row->text);
			}
		}

		return true;
	}
}
