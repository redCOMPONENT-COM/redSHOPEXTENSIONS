<?php
/**
 * @package     RedSHOP.Frontend
 * @subpackage  mod_redshop_currencies
 *
 * @copyright   Copyright (C) 2008 - 2017 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

JLoader::import('redshop.library');

/**
 * Helper for currencies module
 *
 * @since  1.7.0
 */
class ModRedshopCurrenciesHelper
{
	/**
	 * @var  array
	 * @since  1.7.0
	 */
	protected static $cache;

	/**
	 * Retrieve a list of article
	 *
	 * @param   \Joomla\Registry\Registry $params Module parameters
	 *
	 * @return  mixed
	 */
	public static function getList(&$params)
	{
		// Prepare available currency list.
		$availableCurrencies = $params->get('product_currency', []);

		if (empty($availableCurrencies))
		{
			return [];
		}

		$key = md5(implode(',', $availableCurrencies));

		if (!isset(self::$cache[$key]))
		{
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true)
				->select($db->qn('id', 'value'))
				->select($db->qn('name', 'text'))
				->from($db->qn('#__redshop_currency'))
				->where($db->qn('code') . ' IN (' . implode(',', \RedshopHelperUtility::quote($availableCurrencies)) . ')')
				->order($db->qn('name'));

			self::$cache[$key] = $db->setQuery($query)->loadObjectList();
		}

		return self::$cache[$key];
	}

	/**
	 * Method for get currency active
	 *
	 * @return  integer  ID of currency
	 *
	 * @since   1.7.0
	 */
	public static function getActive()
	{
		$session        = JFactory::getSession();
		$activeCurrency = $session->get(
			'product_currency',
			\RedshopEntityCurrency::getInstance()->loadFromCode(\Redshop::getConfig()->get('CURRENCY_CODE'))->getId()
		);

		return $activeCurrency;
	}
}
