<?php
/**
 * @package    Joomla.Cli
 *
 * @copyright  Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

/**
 * This is a CRON script which should be called from the command-line, not the
 * web. For example something like:
 * /usr/bin/php /path/to/site/cli/update_cron.php
 */

// Set flag that this is a parent file.
const _JEXEC = 1;

error_reporting(E_ALL | E_NOTICE);
ini_set('display_errors', 1);

// Load system defines
if (file_exists(dirname(__DIR__) . '/defines.php'))
{
	require_once dirname(__DIR__) . '/defines.php';
}

if (!defined('_JDEFINES'))
{
	define('JPATH_BASE', dirname(__DIR__));
	require_once JPATH_BASE . '/includes/defines.php';
}

require_once JPATH_LIBRARIES . '/import.legacy.php';
require_once JPATH_LIBRARIES . '/cms.php';

// Load the configuration
require_once JPATH_CONFIGURATION . '/configuration.php';

///var/www/html/beer/plugins/system/kiotviet/kiotviet.php

require_once JPATH_PLUGINS . '/system/kiotviet/libraries/vendor/autoload.php';
/**
 * This script will fetch the update information for all extensions and store
 * them in the database, speeding up your administrator.
 *
 * @since  2.5
 */
use Kiotviet\Products\SyncProductRedshop;
use Joomla\Registry\Registry;

require_once JPATH_LIBRARIES . '/redshop/vendor/autoload.php';
JLoader::import('redshop.redshop');
JLoader::registerPrefix('Redshop', JPATH_LIBRARIES . '/redshop');
JLoader::discover('', JPATH_SITE . '/components/com_redshop/helpers', true, true);
define('JPATH_REDSHOP_MEDIA', JPATH_SITE . '/media/com_redshop');
define('JPATH_REDSHOP_TEMPLATE', JPATH_REDSHOP_MEDIA . '/templates');
define('REDSHOP_FRONT_IMAGES_RELPATH', JPATH_ROOT . '/components/com_redshop/assets/images/');

// Product
define('REDSHOP_FRONT_IMAGES_RELPATH_PRODUCT', REDSHOP_FRONT_IMAGES_RELPATH . 'product/');
define('REDSHOP_FRONT_DOCUMENT_RELPATH', JPATH_ROOT . '/components/com_redshop/assets/document/');

define('REDSHOP_MEDIA_IMAGE_RELPATH', JPATH_ROOT . '/media/com_redshop/images/');

class syncdatakiotviet extends JApplicationCli
{
	public function doExecute()
	{
		error_reporting(0);
		@ini_set('display_errors', 0);
		$kiotviet = new Kiotviet\ConnectApi();
		$params = json_decode(JPluginHelper::getPlugin('system', 'kiotviet')->params);
		$params     = new Registry($params);

		$config = array(
			'client_id' => $params->get('client_id'),
			'secret_id' => $params->get('secret_id')
		);

		$kiotviet->setConfig($config);

		$accessToken = $kiotviet->getAccessToken();
		$headers = $kiotviet->getHeaders($accessToken, $params->get('retailer'));

		$client = $kiotviet->getClient();

		$response = $client->request('GET', 'products', $headers);

		$productRedshop = new SyncProductRedshop($accessToken, $params->get('retailer'), $params);
		$data = $productRedshop->getProudcts();

		$total = $data->total;
		$limit = 100;
		$totalPage = ceil($total / $limit);

		for ($i = 0; $i < $totalPage; $i++)
		{
			$productRedshop->limit = $limit;
			$productRedshop->startLimit = $limit * $i;

			$productRedshop->execute(true);

//			$productRedshop->syncProductByIds(array(260));
		}
	}
}

// Instantiate the application object, passing the class name to JCli::getInstance
// and use chaining to execute the application.
JApplicationCli::getInstance('syncdatakiotviet')->execute();