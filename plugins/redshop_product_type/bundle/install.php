<?php
/**
 * @package     \Redshop.Modules
 * @subpackage  plg_system_redgoogleanalytics
 *
 * @copyright   Copyright (C) 2012 - 2017 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later, see LICENSE.
 */
defined('_JEXEC') or die();

use Joomla\Registry\Registry;

/**
 * PlgRedshop_Product_TypeBundleInstallerScript installer class.
 *
 * @package  \Redshopb.Plugin
 * @since    2.0
 */
class PlgRedshop_Product_TypeBundleInstallerScript
{
	/**
	 * Method to run before an install/update/uninstall method
	 *
	 * @param   string $type   The type of change (install, update or discover_install)
	 *
	 * @return  void
	 */
	public function preflight($type)
	{
		$path = \JPath::clean(JPATH_ROOT . '/media/com_redshop/templates/bundle_template');

		$templateDesc = '
		<div class="attribute_listing table-responsive"><table class="table table-striped"><thead><tr><th>{property_number_lbl}</th><th>{property_name_lbl}</th><th></th><th> </th></tr></thead><tbody><tr><td>{bundle_number}</td><td>{bundle_name}</td><td align="center">{bundle_stock_amount_image}</td><td>{bundle_select}</td></tr><tr><td>{property_number}</td><td>{property_name}</td><td align="center">{property_stock_image}</td><td>{property_select}</td></tr></tbody></table></div>
		';

		if (!is_dir($path))
		{
			mkdir($path);
		}

		file_put_contents($path . '/bundle_template.php', $templateDesc);
	}

}
