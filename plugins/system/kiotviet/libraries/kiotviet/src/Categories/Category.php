<?php

namespace Kiotviet\Categories;

use Kiotviet\ConnectApi;
use Joomla\Registry\Registry;

class Category extends ConnectApi
{
	protected $_options;

	public function __construct($accessToken, $retailName, Registry $options)
	{
		$this->_headers = $this->getHeaders($accessToken, $retailName);
		$this->_client  = $this->getClient();
		$this->_options = $options;
	}

	public static function getCategoryByFieldData($fieldId, $kiotCatId = 0, $itemId = 0)
	{
		$db = \JFactory::getDbo();

		$query = $db->getQuery(true)
			->from($db->qn('#__redshop_fields_data'))
			->where($db->qn('fieldid') . ' = ' . $db->q($fieldId))
			->where($db->qn('section') . ' = ' . $db->q(2));

		if (empty($itemId))
		{
			$query->select('itemid')
				->where($db->qn('data_txt') . ' = ' . $db->q($kiotCatId));
		}
		else
		{
			$query->select('data_txt')
				->where($db->qn('itemid') . ' = ' . $db->q($itemId));
		}

		return $db->setQuery($query)->loadResult();
	}

	public static function getRedshopCategoryByIdKv($id)
	{
		$db = \JFactory::getDbo();

		$query = $db->getQuery(true)
			->select('rs_category_id')
			->from($db->qn('#__kiotviet_category_mapping'))
			->where($db->qn('kv_category_id') . ' = ' . $db->q($id));

		return $db->setQuery($query)->loadResult();
	}
}