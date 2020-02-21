<?php
/**
 * @package     RedSHOP.Frontend
 * @subpackage  mod_redshop_redproducts3d
 *
 * @copyright   Copyright (C) 2008 - 2015 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

$category = $params->get('category', []);

$count                  = trim($params->get('count', 2));
$stageWidth             = trim($params->get('stageWidth', 600));
$stageHeight            = trim($params->get('stageHeight', 400));
$thumbWidth             = trim($params->get('thumbwidth', 100));
$thumbHeight            = trim($params->get('thumbheight', 100));
$radius                 = trim($params->get('radius', 230));
$focalBlur              = trim($params->get('focalBlur', 5));
$elevation              = trim($params->get('elevation', -50));
$enableImageReflection  = trim($params->get('enableImageReflection', 'yes'));
$enableimageStroke      = trim($params->get('enableimageStroke', 'yes'));
$enableMouseOverToolTip = trim($params->get('enableMouseOverToolTip', 'yes'));
$enableMouseOverEffects = trim($params->get('enableMouseOverEffects', 'yes'));

$db = JFactory::getDbo();
$query = $db->getQuery(true);
JLoader::import('redshop.library');

$query->select('*')
    ->from($db->qn('#__redshop_product', 'p'))
	->where($db->qn('p.published') . ' = ' . $db->q('1'));

if (is_array($category) && count($category) > 0)
{
    JArrayHelper::toInteger($category);
    $query->leftJoin($db->qn('#__redshop_product_category_xref', 'cx')
        . ' ON ' . $db->qn('cx.product_id') . ' = ' . $db->qn('p.product_id'));
    $query->where($db->qn('cx.category_id') . ' IN (' . implode(',', $db->q($category)) . ') ');
}


$db->setQuery($query, 0, (int) count);
$rows = $db->loadObjectList();

require JModuleHelper::getLayoutPath('mod_redproducts3d');
