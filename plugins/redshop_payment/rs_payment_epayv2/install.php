<?php
/**
 * @package     Epay.Plugin
 * @subpackage  Epay
 *
 * @copyright   Copyright (C) 2012 - 2017 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later, see LICENSE.
 */

defined('_JEXEC') or die();

/**
 * PlgSystemKiotvietInstallerScript installer class.
 *
 * @package  Eppay.Plugin
 * @since    1.0.0
 */
//class PlgRedshop_paymentRs_payment_epayv2InstallerScript
class PlgRedshop_paymentRs_payment_epayv2InstallerScript
{
	public function __construct()
	{
		$lang = JFactory::getLanguage();
		$lang->load('plg_redshop_payment_rs_payment_epayv2', JPATH_ADMINISTRATOR);
		$lang->load('com_redshop', JPATH_ADMINISTRATOR);
	}

	/**
	 * Method to run before an install/update/uninstall method
	 *
	 * @param   string $type The type of change (install, update or discover_install)
	 *
	 * @return  void
	 */
	public function postflight($type)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://www.howsmyssl.com/a/check");
		curl_setopt($ch, CURLOPT_SSLVERSION, 6);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		curl_close($ch);

		$tlsVer = json_decode($response, true);

		if (isset($tlsVer['tls_version']))
		{
			preg_match('!\d+\.?\d+!', $tlsVer['tls_version'], $matches);
			$version = $matches[0];

			if ($version < 1.2)
			{
				$msg = JText::sprintf('PLG_RS_PAYMENT_EPAYV2_CHECK_TLS_VERSION', $version);
				JFactory::getApplication()->enqueueMessage($msg, 'warning');
			}
		}
	}
}