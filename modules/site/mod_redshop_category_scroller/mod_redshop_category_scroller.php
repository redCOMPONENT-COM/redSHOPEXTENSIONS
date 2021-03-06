<?php
/**
 * @package     RedSHOP.Frontend
 * @subpackage  mod_redshop_category_scroller
 *
 * @copyright   Copyright (C) 2008 - 2015 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

JLoader::import('redshop.library');
$cid = JFactory::getApplication()->input->getInt('cid', 0);

$NumberOfProducts  = trim($params->get('NumberOfProducts', 5));
$ScrollSortMethod  = trim($params->get('ScrollSortMethod', 'random'));
$isShowProductName = trim($params->get('show_product_name', 1));
$isShowAddToCart   = trim($params->get('show_addtocart', 1));
$isShowPrice       = trim($params->get('show_price', 1));
$isShowImage       = trim($params->get('show_image', 1));
$isShowReadMore    = trim($params->get('show_readmore', 1));
$isShowVat         = trim($params->get('show_vat', 1));

$scrollerWidth             = trim($params->get('scrollerwidth', 500));
$scrollerHeight            = trim($params->get('scrollerheight', 200));
$thumbWidth                = trim($params->get('thumbwidth', 100));
$thumbHeight               = trim($params->get('thumbheight', 100));
$productTitleEndSuffix     = trim($params->get('product_title_end_suffix', '...'));
$productTitleMaxChars      = trim($params->get('product_title_max_chars', 10));
$isShowDiscountPriceLayout = trim($params->get('show_discountpricelayout', 1));
$pretext                   = trim($params->get('pretext', ''));
$user = JFactory::getUser();

$db = JFactory::getDbo();
$query = $db->getQuery(true)
	->select('p.product_id')
	->from($db->qn('#__redshop_product', 'p'))
	->where('p.published = 1')
	->group('p.product_id');

switch ($ScrollSortMethod)
{
	case 'random':
		$orderBy = "RAND()";
		break;
	case 'newest':
		$orderBy = "p.publish_date DESC";
		break;
	case 'oldest':
		$orderBy = "p.publish_date ASC";
		break;
	case 'mostsold':
		$orderBy = "orderItems.qty DESC";
		$subQuery = $db->getQuery(true)
			->select('SUM(' . $db->qn('oi.product_quantity') . ') AS qty, oi.product_id')
			->from($db->qn('#__redshop_order_item', 'oi'))
			->group('oi.product_id');
		$query->select('orderItems.qty')
			->leftJoin('(' . $subQuery . ') orderItems ON orderItems.product_id = p.product_id');
		break;
	default:
		$orderBy = "p.product_id";
}

$query->order($orderBy);

if ($cid)
{
	$query->leftJoin($db->qn('#__redshop_product_category_xref', 'cx') . ' ON cx.product_id = p.product_id')
		->where('cx.category_id = ' . (int) $cid);
}

$rows = [];

if ($productIds = $db->setQuery($query, 0, (int) $NumberOfProducts)->loadColumn())
{
	// Third steep get all product relate info
	$query->clear()
		->where('p.product_id IN (' . implode(',', $productIds) . ')')
		->order('FIELD(p.product_id, ' . implode(',', $productIds) . ')');

	$query = \Redshop\Product\Product::getMainProductQuery($query, $user->id)
		->select('CONCAT_WS(' . $db->q('.') . ', p.product_id, ' . (int) $user->id . ') AS concat_id');

	if ($rows = $db->setQuery($query)->loadObjectList('concat_id'))
	{
		\Redshop\Product\Product::setProduct($rows);
		$rows = array_values($rows);
	}
}

require JModuleHelper::getLayoutPath('mod_redshop_category_scroller');
