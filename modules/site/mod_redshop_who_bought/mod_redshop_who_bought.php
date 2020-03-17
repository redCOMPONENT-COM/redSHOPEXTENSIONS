<?php
/**
 * @package     RedSHOP.Frontend
 * @subpackage  mod_redshop_who_bought
 *
 * @copyright   Copyright (C) 2008 - 2015 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

JLoader::import('redshop.library');

$category = $params->get('category', '');

if (is_array($category)) {
    $category = implode(',', $category);
} else {
    $category = trim($category);
}

$countItems            = trim($params->get('number_of_items', 5));
$thumbWidth            = trim($params->get('thumbwidth', 100));
$thumbHeight           = trim($params->get('thumbheight', 100));
$sliderWidth           = trim($params->get('sliderwidth', 500));
$isShowProductImage    = trim($params->get('show_product_image', 1));
$isShowAddToCartButton = trim($params->get('show_addtocart_button', 1));
$isShowProductName     = trim($params->get('show_product_name', 1));
$productTitleLinkable  = trim($params->get('product_title_linkable', 1));
$isShowProductPrice    = trim($params->get('show_product_price', 1));
$scroll                = $params->get('number_of_products_one_scroll', 2);

$db    = JFactory::getDbo();
$query = $db->getQuery(true)
    ->select('p.*')
    ->from($db->qn('#__redshop_product', 'p'))
    ->where($db->qn('p.published') . ' = ' . $db->q(1));

if ($category != "") {
    $query->leftJoin(
        $db->qn('#__redshop_product_category_xref', 'pc') . ' ON ' .
        $db->qn('pc.product_id') . ' = ' . $db->qn('p.product_id')
    )
        ->where($db->qn('pc.category_id') . ' IN (' . $db->q($category) . ')');
}

$rows = [];
$db->setQuery($query, 0, $countItems);
$rows = $db->loadObjectList();

require JModuleHelper::getLayoutPath('mod_redshop_who_bought');
