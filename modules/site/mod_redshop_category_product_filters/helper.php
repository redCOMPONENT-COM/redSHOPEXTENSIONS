<?php
/**
 * @package     RedSHOP.Module
 * @subpackage  mod_redshop_category_product_filters
 *
 * @copyright   Copyright (C) 2008 - 2015 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

/**
 * Helper for mod_redshop_category_product_filters
 *
 * @since  2.1.2
 */
//class ProductFiltersHelper
class Mod\RedshopCategoryProductFiltersHelper
{
	/**
	 * This function will help get max and min value on list product price
	 *
	 * @param   array $pids default value is array
	 *
	 * @return array
	 */
	public static function getPriceRange($pids = [])
	{
		$max              = 0;
		$min              = 0;
		$allProductPrices = [];

		if (!empty($pids))
		{
			// Get product price
			foreach ($pids as $k => $id)
			{
				$productprices      = \RedshopHelperProductPrice::getNetPrice($id, JFactory::getUser()->id);
				$allProductPrices[] = $productprices['productPrice'];
			}

			// Get first value to make sure it won't zero value
			$max = $min = $allProductPrices[0];

			// Loop to check max min
			foreach ($allProductPrices as $k => $value)
			{
				// Check max
				if ($value >= $max)
				{
					$max = $value + 100;
				}

				// Check min
				if ($value <= $min)
				{
					$min = $value;
				}
			}
		}

		$arrays = array(
			"max" => $max,
			"min" => $min
		);

		return $arrays;
	}

	/**
	 * Retrieve a list of article
	 *
	 * @param   array $manuList manufacturer ids
	 *
	 * @return  mixed
	 */
	public static function getManufacturers($manuList = [])
	{
		if (empty($manuList))
		{
			return [];
		}

		$db    = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select($db->qn('name'))
			->select($db->qn('id'))
			->from($db->qn('#__redshop_manufacturer'))
			->where($db->qn('published') . ' = 1')
			->order($db->qn('name') . ' ASC');

		if (!empty($manuList))
		{
			$query->where($db->qn('id') . ' IN (' . implode(',', $manuList) . ')');
		}

		return $db->setQuery($query)->loadObjectList();
	}

	/**
	 * Retrieve a list of custom fields for filter
	 *
	 * @param   array $pids          product ids
	 * @param   array $productFields product custom fields (checkbox/ single select/ multi select)
	 *
	 * @return  array
	 */
	public static function getCustomFields($pids = [], $productFields = [])
	{
		if (empty($pids) || empty($productFields))
		{
			return [];
		}

		$manufacturerId = JRequest::getVar('manufacturer_id');

		$db    = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select($db->qn('fv.field_value'))
			->select($db->qn('fv.field_id'))
			->select($db->qn('fv.field_name'))
			->select($db->qn('f.title'))
			->select($db->qn('f.class'))
			->select($db->qn('f.ordering'))
			->from($db->qn('#__redshop_fields', 'f'))
			->leftJoin($db->qn('#__redshop_fields_value', 'fv') . ' ON ' . $db->qn('f.id') . ' = ' . $db->qn('fv.field_id'))
			->leftJoin($db->qn('#__redshop_fields_data', 'fd') . ' ON ' . $db->qn('f.id') . ' = ' . $db->qn('fd.fieldid'))
			->leftJoin($db->qn('#__redshop_product', 'p') . ' ON ' . $db->qn('p.product_id') . ' = ' . $db->qn('fd.itemid'))
			->where($db->qn('fd.itemid') . ' IN (' . implode(',', $pids) . ')')
			->where($db->qn('f.name') . ' IN (' . implode(',', $db->q($productFields)) . ')')
			->where($db->qn('fd.data_txt') . ' NOT LIKE "" AND ' . $db->qn('fd.data_txt') . ' IS NOT NULL ');

		if ($manufacturerId)
		{
			$query->where($db->qn('p.manufacturer_id') . ' = ' . $db->q($manufacturerId));
		}
		$query->group('fv.field_value, fv.field_id')
			->order('f.ordering, fv.field_value');

		$data   = $db->setQuery($query)->loadObjectList();
		$result = [];

		foreach ($data as $key => $value)
		{

			$result[$value->field_id]['title']                      = $value->title;
			$result[$value->field_id]['class']                      = $value->class;
			$result[$value->field_id]['value'][$value->field_value] = $value->field_name;
			$result[$value->field_id]['ordering']                   = $value->ordering;
		}

		return $result;
	}


	/**
	 * Retrieve a list of attributes for filter
	 *
	 * @param $attributeFilters
	 * @param $pids
	 *
	 * @return array|null The return list or null if the query failed.
	 *
	 * @throws  RuntimeException
	 */
	public static function getAttributeFiltersList($attributeFilters, $pids)
	{
		$db = \JFactory::getDbo();

		$query = "SELECT a.attribute_name, GROUP_CONCAT(DISTINCT ap.property_name
					ORDER BY  ap.property_name ASC SEPARATOR '||') as properties
					FROM #__redshop_product_attribute_property ap LEFT JOIN #__redshop_product_attribute a 
					ON a.attribute_id = ap.attribute_id WHERE a.attribute_published = 1 AND a.product_id > 0 ";

		$query .= " AND a.product_id IN (" . implode(',', $pids) . ") ";
		$query .= " AND a.attribute_name IN (" . "'" . implode("','", $attributeFilters) . "'" . ") ";
		$query .= " GROUP by a.attribute_name";

		return $db->setQuery($query)->loadAssocList();
	}
}
