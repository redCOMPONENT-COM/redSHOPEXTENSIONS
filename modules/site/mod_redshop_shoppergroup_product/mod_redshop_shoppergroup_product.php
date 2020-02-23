<?php
/**
 * @package     RedSHOP.Frontend
 * @subpackage  mod_redshop_shoppergroup_product
 *
 * @copyright   Copyright (C) 2008 - 2015 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

JLoader::import('redshop.library');
$image                    = trim($params->get('image', 0));
$isShowPrice               = trim($params->get('show_price', 0));
$thumbWidth               = trim($params->get('thumbwidth', 100));
$thumbHeight              = trim($params->get('thumbheight', 100));
$show_short_description   = trim($params->get('show_short_description', 1));
$isShowReadMore            = trim($params->get('show_readmore', 1));
$isShowAddToCart           = trim($params->get('show_addtocart', 1));
$isShowDiscountPriceLayout = trim($params->get('show_discountpricelayout', 1));
$show_desc                = trim($params->get('show_desc', 1));
$isShowVat                 = trim($params->get('show_vat', 1));

// Include the syndicate functions only once
require_once __DIR__ . '/helper.php';

$rows = ModRedshopShopperGroupProduct::getList($params);

if ($rows)
{
	require JModuleHelper::getLayoutPath('mod_redshop_shoppergroup_product', $params->get('layout', 'default'));
}
