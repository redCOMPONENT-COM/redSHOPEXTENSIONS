<?php
/**
 * @package     Kiotviet.Plugin
 * @subpackage  Kiotviet
 *
 * @copyright   Copyright (C) 2012 - 2017 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later, see LICENSE.
 */

defined('_JEXEC') or die();

/**
 * PlgSystemKiotvietInstallerScript installer class.
 *
 * @package  Kiotviet.Plugin
 * @since    1.0.0
 */
class PlgSystemKiotvietInstallerScript
{
	/**
	 * Method to run before an install/update/uninstall method
	 *
	 * @param   string $type The type of change (install, update or discover_install)
	 *
	 * @return  void
	 */
	public function preflight($type)
	{
		JLoader::import('joomla.filesystem.file');

		// Entry point file
		$targetSync = JPATH_ROOT . '/cli/syncdatakiotviet.php';
		$targetWebhook = JPATH_ROOT . '/webhook.php';
		$targetLog = JPATH_ADMINISTRATOR . '/logs/kiotviet.txt';
		$targetTmplProduct = JPATH_ADMINISTRATOR . '/components/com_redshop/views/product_detail/tmpl/default_kiotviet_settings.php';

		if ($type === 'uninstall')
		{
			JFile::delete($targetSync);
			JFile::delete($targetWebhook);
			JFile::delete($targetLog);
			JFile::delete($targetTmplProduct);
			return;
		}

		if (JFile::exists($targetSync))
		{
			JFile::delete($targetSync);
		}

		if (JFile::exists($targetWebhook))
		{
			JFile::delete($targetWebhook);
		}

		if (JFile::exists($targetLog))
		{
			JFile::delete($targetLog);
		}

		if (JFile::exists($targetTmplProduct))
		{
			JFile::delete($targetTmplProduct);
		}

		if (!JFolder::exists(JPATH_ADMINISTRATOR . '/logs'))
		{
			mkdir(JPATH_ADMINISTRATOR . '/logs');
		}

		// Move entry point file
		JFile::move(__DIR__ . '/cli/syncdatakiotviet.php', $targetSync);
		JFile::move(__DIR__ . '/webhook.php', $targetWebhook);
		JFile::move(__DIR__ . '/admin/logs/kiotviet.txt', $targetLog);
		JFile::move(__DIR__ . '/admin/product/tmpl/default_kiotviet_settings.php', $targetTmplProduct);
	}
}
