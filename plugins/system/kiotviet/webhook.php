<?php
//$test = file_get_contents("test.txt");
//$test .= file_get_contents('php://input');
//$file = fopen("test.txt","w");
//fwrite($file,$test);
//fclose($file);
use Kiotviet\Products\SyncProductRedshop;
use Kiotviet\Traits\Helper;
use Joomla\Registry\Registry;
const _JEXEC = 1;

error_reporting(E_ALL | E_NOTICE);
ini_set('display_errors', 1);

//error_reporting(0);
//@ini_set('display_errors', 0);

// Load system defines
if (file_exists(__DIR__ . '/defines.php'))
{
	require_once __DIR__ . '/defines.php';
}

if (!defined('_JDEFINES'))
{
	define('JPATH_BASE', __DIR__);
	require_once JPATH_BASE . '/includes/defines.php';
}

require_once JPATH_LIBRARIES . '/import.legacy.php';
require_once JPATH_LIBRARIES . '/cms.php';

// Load the configuration
require_once JPATH_CONFIGURATION . '/configuration.php';
JLoader::import('redshop.library');
require_once JPATH_PLUGINS . '/system/kiotviet/libraries/vendor/autoload.php';

$response = json_decode(file_get_contents('php://input'));

$test = file_get_contents("administrator/logs/kiotviet.txt");
$test .= file_get_contents('php://input');
$file = fopen("administrator/logs/kiotviet.txt","w");
fwrite($file,$test);
fclose($file);

$notications = $response->Notifications;

$kiotviet = new Kiotviet\ConnectApi();
$params = json_decode(JPluginHelper::getPlugin('system', 'kiotviet')->params);
$params     = new Registry($params);

$config = array(
	'client_id' => $params->get('client_id'),
	'secret_id' => $params->get('secret_id')
);

$kiotviet->setConfig($config);

$accessToken = $kiotviet->getAccessToken();
$syncProduct = new SyncProductRedshop($accessToken, $params->get('retailer'), $params);

foreach ($notications as $notication)
{
	$action = $notication->Action;

	if (strpos($action, 'product.update') !== false)
	{
		$items = $notication->Data;

		foreach ($items as $item)
		{
			$kvProduct = Helper::lcfirstObject($item);

			$rsProduct = \Redshop\Repositories\Product::getProductByNumber($kvProduct->code);

			if (empty($rsProduct) && !empty($kvProduct->masterProductId))
			{
				continue;
			}

			$productId = 0;

			if (!empty($rsProduct))
			{
				$productId = $rsProduct->product_id;
			}

			if ($params->get('update_redshop_product'))
			{
				$syncProduct->storeProduct($kvProduct, $productId);
			}

			if ($params->get('update_redshop_image') && $productId)
			{
				$syncProduct->storeAdditionalImages($productId, $kvProduct->images);
			}

			if ($params->get('update_redshop_stockroom') && $productId)
			{
				$syncProduct->storeStockRoom($productId, $kvProduct);
			}

			$syncProduct->storeUnitsKiotviet($kvProduct, $productId);

			$syncProduct->storeAttributeKiotviet($kvProduct, $productId);

			$syncProduct->storeAccessories($kvProduct, $productId);
		}
	}
	elseif (strpos($action, 'stock.update') !== false)
	{
		$stockDatas = $notication->Data;

		foreach ($stockDatas as $stockData)
		{
			$rsProduct = \Redshop\Repositories\Product::getProductByNumber($stockData->ProductCode);

			if (empty($rsProduct))
			{
				continue;
			}

			$productId = $rsProduct->product_id;

			$db = JFactory::getDbo();

			foreach ($params->get('mapping_stock') as $key => $value)
			{
				if ($stockData->BranchId == $value->branch)
				{
					$query = $db->getQuery(true)->clear()
						->delete($db->qn('#__redshop_product_stockroom_xref'))
						->where($db->qn('product_id') . ' = ' . $db->q($productId))
						->where($db->qn('stockroom_id') . ' = ' . $db->q($value->stock));

					$db->setQuery($query)->execute();

					$stockSave = new \stdClass;
					$stockSave->product_id = $productId;
					$stockSave->stockroom_id = $value->stock;
					$stockSave->quantity = $stockData->OnHand;
					$stockSave->preorder_stock = 0;
					$stockSave->ordered_preorder = 0;

					$db->insertObject('#__redshop_product_stockroom_xref', $stockSave, 'stockroom_id');

					break;
				}
			}
		}
	}
	elseif (strpos($action, 'product.delete') !== false)
	{
		$data = $notication->Data;
		$productNumbers = array();
		$db = JFactory::getDbo();

		if (!empty($data))
		{
			foreach ($data as $kvProductId)
			{
				$productNumbers[] = $syncProduct->getProductRedshopByIdKioviet($kvProductId)->product_number;
			}
		}

		if (!empty($productNumbers))
		{
			$query = $db->getQuery(true)
				->delete('#__redshop_product')
				->where($db->qn('product_number') . ' IN (' . implode(',', $db->q($productNumbers)) . ')' );

			$db->setQuery($query)->execute();
		}
	}
}

die('done');












