<?php
/**
 * @package     RedSHOP.Frontend
 * @subpackage  mod_redshop_producttab
 *
 * @copyright   Copyright (C) 2008 - 2017 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

$newProduct   = trim($params->get('show_newprd', 1));
$listProduct  = trim($params->get('show_ltsprd', 1));
$soldProduct = trim($params->get('show_soldprd', 1));
$specialProduct       = trim($params->get('show_splprd', 1));

$image           = trim($params->get('image', 0));
$isShowPrice     = trim($params->get('show_price', 0));
$isShowReadMore  = trim($params->get('show_readmore', 1));
$isShowAddToCart = trim($params->get('show_addtocart', 1));
$isShowDesc      = trim($params->get('show_desc', 1));
$thumbWidth      = trim($params->get('thumbwidth', 100));
$thumbHeight     = trim($params->get('thumbheight', 100));
$layout          = $params->get('layout', 'default');
$productPerRow   = $params->get('number_of_row');

\JFactory::getDocument()->addStyleSheet("modules/mod_redproducttab/css/style.css");
JFactory::getDocument()->addStyleSheet("modules/mod_redproducttab/css/bootstrap.min.css");
JFactory::getDocument()->addStyleSheet("modules/mod_redproducttab/js/bootstrap.min.js");
JFactory::getDocument()->addStyleSheet("modules/mod_redproducttab/js/jquery.min.js");

\JLoader::import('redshop.library');

require_once __DIR__ . '/helper.php';

include JModuleHelper::getLayoutPath('mod_redproducttab', $layout);
