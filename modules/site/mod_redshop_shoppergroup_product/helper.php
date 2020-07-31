<?php
/**
 * @package     RedSHOP.Frontend
 * @subpackage  mod_redshop_shoppergroup_product
 *
 * @copyright   Copyright (C) 2008 - 2015 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

/**
 * Helper for mod_redshop_shoppergroup_product
 *
 * @since  1.5
 */
class ModRedshopShopperGroupProduct
{
    /**
     * @param $params
     *
     * @return null
     * @throws Exception
     * @since 1.5
     */
	public static function getList(&$params)
	{
		$user = JFactory::getUser();
		$shopperGroupId = \RedshopHelperUser::getShopperGroup($user->id);
		$shopperGroupData = \Redshop\Helper\ShopperGroup::generateList($shopperGroupId);
		$shopperGroupCategories = $shopperGroupData[0]->categories;

		return \RedshopHelperProduct::getProductCategory($shopperGroupCategories);
	}
}
