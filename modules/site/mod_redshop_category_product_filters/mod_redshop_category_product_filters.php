<?php
/**
 * @package     RedSHOP.Frontend
 * @subpackage  mod_redshop_filter
 *
 * @copyright   Copyright (C) 2008 - 2015 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

JLoader::register('Mod\RedshopCategoryProductFiltersHelper', __DIR__ . '/helper.php');
JLoader::import('redshop.library');

$input        = JFactory::getApplication()->input;
$cid          = $input->getInt('cid', 0);
$mainCategory = \RedshopEntityCategory::getInstance($cid)->getItem();

// retrieve product filter parameters from main category
$registry     = new JRegistry;
$filterParams = $registry->loadString($mainCategory->product_filter_params);
$flag         = $filterParams->get('enable') && ($input->getCmd('option') === 'com_redshop') && ($input->getCmd('view') === 'category') && !empty($cid);

if ($flag === false)
{
	return;
}

$mid                = $input->getInt('manufacturer_id', 0);
$moduleClassSfx     = $params->get('moduleclass_sfx');
$enableCategory     = $filterParams->get('category_enable');
$enableManufacturer = $filterParams->get('manufacturer_enable');
$enablePrice        = $filterParams->get('price_enable');
$template           = $filterParams->get('template_id');
$attributeFilters   = $filterParams->get('product_attributes');
$enableCustomField  = $filterParams->get('customfield_enable');
$productFields      = $filterParams->get('customfields');
$view               = $input->getString('view', '');
$layout             = $input->getString('layout', '');
$action             = JRoute::_("index.php?option=com_redshop&view=category&layout=detail&cid=" . $cid);

$categoryModel = JModelLegacy::getInstance('Category', '\RedshopModel');
$filterInput = $categoryModel->getState('filterform', []);


$productList = \RedshopHelperCategory::getCategoryProductList($cid, true);
$manuList    = [];
$pids        = [];

foreach ($productList as $k => $value)
{
	$pids[] = $value->product_id;

	if ($value->manufacturer_id && $value->manufacturer_id != $mid)
	{
		$manuList[] = $value->manufacturer_id;
	}
}

if (empty($pids))
{
	return;
}

$manufacturers   = Mod\RedshopCategoryProductFiltersHelper::getManufacturers(array_unique($manuList));
$categories      = \RedshopHelperCategory::getCategoryListArray($cid);
$customFields    = Mod\RedshopCategoryProductFiltersHelper::getCustomFields($pids, $productFields);
$rangePrice      = Mod\RedshopCategoryProductFiltersHelper::getPriceRange($pids);
$attributesGroup = Mod\RedshopCategoryProductFiltersHelper::getAttributeFiltersList($attributeFilters, $pids);

$rangeMin = $rangePrice['min'];
$rangeMax = $rangePrice['max'];

require JModuleHelper::getLayoutPath('mod_redshop_category_product_filters', $params->get('layout', 'default'));
