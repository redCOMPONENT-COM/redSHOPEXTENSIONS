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
			$prtemplate_id = trim($red_params->get('product_template', 1));
			$prtemplate1 = RedshopHelperTemplate::getTemplate('product_content_template', $prtemplate_id);
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

				$prtemplate = \RedshopTagsReplacer::_(
					'product',
					$prtemplate,
					[
						'data' => $product
					]);

				if (strstr($prtemplate, "{read_more}"))
				{
					$catid = $product->cat_in_sefurl;
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

					$read_more = "<a href='" . $link . "'>" . JText::_('PLG_CONTENT_REDSHOP_PRODUCT_TXT_READ_MORE') . "</a>";
					$prtemplate = str_replace("{read_more}", $read_more, $prtemplate);
				}

				$dispatcher->trigger('onAfterDisplayProduct', array(&$prtemplate, &$red_params, $product));

				$row->text = str_replace($matches[$i], $prtemplate, $row->text);
			}
		}

		return true;
	}
}
