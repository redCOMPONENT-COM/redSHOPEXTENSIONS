<?php
/**
 * @package     ItunesReviews.Plugin
 * @subpackage  ItunesReviews
 *
 * @copyright   Copyright (C) 2012 - 2017 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later, see LICENSE.
 */

defined('_JEXEC') or die();

/**
 * PlgSystemItunesreviewsInstallerScript installer class.
 *
 * @package  ItunesReviews.Plugin
 * @since    1.0.0
 */
class PlgSystemItunesreviewsInstallerScript
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
		$target = JPATH_ROOT . '/cli/itunesreviews.php';

		if (JFile::exists($target))
		{
			JFile::delete($target);
		}

		if ($type === 'uninstall')
		{
			return;
		}

		// Move entry point file
		JFile::move(__DIR__ . '/cli/itunesreviews.php', $target);
	}
}
