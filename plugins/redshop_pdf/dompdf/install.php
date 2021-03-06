<?php
/**
 * @package     \Redshop.Plugin
 * @subpackage  domPDF
 *
 * @copyright   Copyright (C) 2012 - 2017 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later, see LICENSE.
 */
defined('_JEXEC') or die();

/**
 * PlgRedshop_PdfDomPDF installer class.
 *
 * @package  \Redshopb.Plugin
 * @since    2.0.0
 */
class PlgRedshop_PdfDomPDFInstallerScript
{
	/**
	 * Method to run before an install/update/uninstall method
	 *
	 * @param   string  $type  The type of change (install, update or discover_install)
	 *
	 * @return  void
	 */
	public function preflight($type)
	{
		if ($type == 'update' || $type == 'discover_install')
		{
			// Reads current (old) version from manifest
			$db = JFactory::getDbo();
			$version = $db->setQuery(
				$db->getQuery(true)
					->select($db->qn('manifest_cache'))
					->from($db->qn('#__extensions'))
					->where($db->qn('type') . ' = ' . $db->quote('plugin'))
					->where($db->qn('folder') . ' = ' . $db->quote('redshop_pdf'))
					->where($db->qn('element') . ' = ' . $db->quote('dompdf'))
			)
				->loadResult();

			if (!empty($version))
			{
				$version = new JRegistry($version);
				$version = $version->get('version');

				if (version_compare($version, '1.1.0', '<'))
				{
					$this->deleteOldLibrary();
				}
			}
		}
	}

	/**
	 * Method for delete old library files
	 *
	 * @return  void
	 */
	protected function deleteOldLibrary()
	{
		// Delete old languages files if necessary
		JLoader::import('joomla.filesystem.file');

		// Remove old library
		$oldFolder = JPATH_ROOT . '/plugins/redshop_pdf/dompdf/helper/dompdf';

		if (JFolder::exists($oldFolder))
		{
			JFolder::delete($oldFolder);
		}
	}
}
