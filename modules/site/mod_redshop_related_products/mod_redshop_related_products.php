<?php
/**
 * @package     RedSHOP.Frontend
 * @subpackage  mod_redshop_products
 *
 * @copyright   Copyright (C) 2008 - 2015 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

JLoader::import('redshop.library');

// Initialize variables.
/** @var JApplicationSite $app */
$app                     = JFactory::getApplication();
$db                      = JFactory::getDbo();
$session                 = JFactory::getSession();
$image                   = trim($params->get('image', 0));
$showPrice               = trim($params->get('show_price', 0));
$thumbWidth              = trim($params->get('thumbwidth', 100));
$thumbHeight             = trim($params->get('thumbheight', 100));
$showShortDescription    = trim($params->get('show_short_description', 1));
$showAddToCart           = trim($params->get('show_addtocart', 1));
$showDiscountPriceLayout = trim($params->get('show_discountpricelayout', 1));
$showDescription         = trim($params->get('show_desc', 1));
$showVat                 = trim($params->get('show_vat', 1));
$showWishlist            = trim($params->get('show_wishlist', 0));
$showStockroomStatus     = trim($params->get('show_stockroom_status', 1));
$showReadmore            = trim($params->get('show_readmore', 1));
$productId               = JFactory::getApplication()->input->get('pid');

$user = JFactory::getUser();


$stockrooms = $params->get('stockrooms', '');

if (is_array($stockrooms))
{
	$stockrooms = implode(',', $stockrooms);
}
else
{
	$stockrooms = trim($stockrooms);
}

if ($stockrooms && \Redshop::getConfig()->getBool('USE_STOCKROOM'))
{
	$query->leftJoin($db->qn('#__redshop_product_stockroom_xref', 'sx') . ' ON ' . $db->qn('p.product_id') . ' = ' . $db->qn('sx.product_id'))
		->where($db->qn('sx.stockroom_id') . ' IN (' . $stockrooms . ')')
		->where($db->qn('sx.quantity') . ' > 0');
}

$rows = [];

if ($productId)
{
	$rows = \RedshopHelperProduct::getRelatedProduct($productId);
}

require JModuleHelper::getLayoutPath('mod_redshop_related_products', $params->get('layout', 'default'));
