<?php
/**
 * @package     RedSHOP
 * @subpackage  Plugin
 *
 * @copyright   Copyright (C) 2008 - 2017 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;
JLoader::import('redshop.library');

/**
 * Plugin will synchronize data to redSHOP B2b
 *
 * @since  1.0.0
 */
class PlgRedshop_ProductSync_B2b extends JPlugin
{
	/**
	 * @var integer
	 */
	public $maxExecutionTime = 0;

	/**
	 * @var integer
	 */
	public $startTime = 0;

	/**
	 * @var string
	 */
	public $accessToken = null;

	/**
	 * @var null
	 */
	protected $syncCategories = null;

	/**
	 * @var  boolean
	 */
	protected $autoloadLanguage = true;

	/**
	 * Constructor
	 *
	 * @param   object $subject The object to observe
	 * @param   array  $config  An optional associative array of configuration settings
	 *
	 * @since   1.0.0
	 */
	public function __construct(&$subject, $config = array())
	{
		$this->maxExecutionTime = ini_get("max_execution_time");
		$this->maxExecutionTime = empty($this->maxExecutionTime) ? 9999999 : $this->maxExecutionTime;
		$this->startTime        = microtime(1);

		parent::__construct($subject, $config);
	}

	/**
	 *
	 * Method is called by the product view
	 *
	 * @param   object  $data  Product data
	 * @param   boolean $isNew Product is new
	 *
	 * @return  boolean
	 */
	public function onAfterProductSave(&$data, $isNew)
	{
		$db          = JFactory::getDbo();
		$url         = $this->params->get('url', '');
		$accessToken = $this->getAccessToken();
		$params      = '/index.php?webserviceClient=site&webserviceVersion=1.2.0&option=redshopb&view=product&api=hal';
		$result      = array();
		$type        = 'POST';
		$insert      = true;

		if (empty($url))
		{
			return true;
		}

		if (empty($accessToken))
		{
			return true;
		}

		if (!$isNew)
		{
			$redshopBId = $this->getRedshopBId($data->product_id);

			if ($redshopBId)
			{
				$insert       = false;
				$result['id'] = $redshopBId;
				$type         = 'PUT';
			}
		}

		$result['name']             = $data->product_name;
		$result['alias']            = JFilterOutput::stringURLUnicodeSlug($data->product_name);
		$result['sku']              = $data->product_number;
		$result['manufacturer_sku'] = $data->manufacturer_id;
		$result['date_new']         = date('Y-m-d');
		$result['price']            = $data->product_price;
		$result['company']          = $this->params->get('company', 2);

		if ($data->manufacturer_id > 0)
		{
			$result['manufacturer_id'] = $data->manufacturer_id;
		}

		$ch = curl_init($url . $params);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($result));
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $accessToken));
		$return = curl_exec($ch);
		$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$error  = curl_error($ch);
		curl_close($ch);
		$return    = json_decode($return);
		$productId = $return->result;

		if ($productId > 0 && $insert)
		{
			$query   = $db->getQuery(true);
			$columns = array('redshop_product_id', 'redshopb_product_id');
			$values  = array($db->q((int) $data->product_id), $db->q((int) $productId));

			$query
				->insert($db->quoteName('#__redshop_redshopb_xref'))
				->columns($db->quoteName($columns))
				->values(implode(',', $values));

			$db->setQuery($query)->execute();
		}

		return true;
	}

	/**
	 * Method to get Access Token
	 *
	 * @return  string
	 */
	public function getAccessToken()
	{
		if ($this->accessToken === null)
		{
			$url          = $this->params->get('url', '') . '/index.php?option=token&api=oauth2';
			$clientId     = $this->params->get('client_id', '');
			$clientSecret = $this->params->get('client_secret', '');
			$params       = array(
				'grant_type'    => 'client_credentials',
				'client_id'     => $clientId,
				'client_secret' => $clientSecret
			);

			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
			$result = curl_exec($ch);
			curl_close($ch);

			$this->accessToken = json_decode($result)->access_token;
		}

		return $this->accessToken;
	}

	/**
	 * Method to get redSHOPB Product ID
	 *
	 * @param   int $productId Product id
	 *
	 * @return  interger
	 */
	public function getRedshopBId($productId)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select($db->qn('redshopb_product_id'))
			->from($db->qn('#__redshop_redshopb_xref'))
			->where($db->qn('redshop_product_id') . ' = ' . $db->q((int) $productId));

		return $db->setQuery($query)->loadResult();
	}

	/**
	 *
	 * Method is called after product is deleted
	 *
	 * @param   array $ids Product id list
	 *
	 * @return  boolean
	 */
	public function onAfterProductDelete($ids)
	{
		$url         = $this->params->get('url', '');
		$params      = '/index.php?webserviceClient=site&webserviceVersion=1.2.0&option=redshopb&view=product&api=hal';
		$accessToken = $this->getAccessToken();

		foreach ($ids as $id)
		{
			$redshopBId = $this->getRedshopBId($id);

			if (!$redshopBId)
			{
				continue;
			}

			$ch = curl_init($url . $params);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('id' => $redshopBId)));
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $accessToken));
			$result = curl_exec($ch);
			$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$error  = curl_error($ch);
			curl_close($ch);

			if (json_decode($result)->result == 1)
			{
				$db    = JFactory::getDbo();
				$query = $db->getQuery(true)
					->delete($db->qn('#__redshop_redshopb_xref'))
					->where(array($db->qn('redshopb_product_id') . ' = ' . $db->q((int) $redshopBId)));

				$db->setQuery($query)->execute();
			}
		}

		return true;
	}

	/**
	 *
	 * Method is called by the Manufacturer view
	 *
	 * @param   object  $data  Manufacturer data
	 * @param   boolean $isNew Manufacturer is new
	 *
	 * @return  boolean
	 */
	public function onAfterManufacturerSave(&$data, $isNew)
	{
		$db          = JFactory::getDbo();
		$url         = $this->params->get('url', '');
		$accessToken = $this->getAccessToken();
		$params      = '/index.php?webserviceClient=site&webserviceVersion=1.1.0&option=redshopb&view=manufacturer&api=hal';
		$result      = array();
		$type        = 'POST';
		$insert      = true;

		if (empty($url))
		{
			return true;
		}

		if (empty($accessToken))
		{
			return true;
		}

		if (!$isNew)
		{
			$redshopBId = $this->getRedshopBManufacturerId($data->manufacturer_id);

			if ($redshopBId)
			{
				$insert       = false;
				$result['id'] = $redshopBId;
				$type         = 'PUT';
			}
		}

		$result['name']  = $data->manufacturer_name;
		$result['alias'] = JFilterOutput::stringURLUnicodeSlug($data->manufacturer_name);

		$ch = curl_init($url . $params);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($result));
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $accessToken));
		$return = curl_exec($ch);
		$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$error  = curl_error($ch);
		curl_close($ch);
		$return         = json_decode($return);
		$manufacturerId = $return->result;

		if ($manufacturerId > 0 && $insert)
		{
			$query   = $db->getQuery(true);
			$columns = array('redshop_manufacturer_id', 'redshopb_manufacturer_id');
			$values  = array($db->q((int) $data->manufacturer_id), $db->q((int) $manufacturerId));

			$query
				->insert($db->quoteName('#__redshop_redshopb_manufacturer_xref'))
				->columns($db->quoteName($columns))
				->values(implode(',', $values));

			$db->setQuery($query)->execute();
		}

		return true;
	}

	/**
	 * Method to get redSHOPB Manufacturer ID
	 *
	 * @param   int $manufacturerId Manufacturer id
	 *
	 * @return  integer
	 */
	public function getRedshopBManufacturerId($manufacturerId)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select($db->qn('redshopb_manufacturer_id'))
			->from($db->qn('#__redshop_redshopb_manufacturer_xref'))
			->where($db->qn('redshop_manufacturer_id') . ' = ' . $db->q((int) $manufacturerId));

		return $db->setQuery($query)->loadResult();
	}

	/**
	 *
	 * Method is called after Manufacturer is deleted
	 *
	 * @param   array $ids Manufacturer id list
	 *
	 * @return  boolean
	 */
	public function onAfterManufacturerDelete($ids)
	{
		$url         = $this->params->get('url', '');
		$params      = '/index.php?webserviceClient=site&webserviceVersion=1.1.0&option=redshopb&view=manufacturer&api=hal';
		$accessToken = $this->getAccessToken();

		foreach ($ids as $id)
		{
			$redshopBId = $this->getRedshopBManufacturerId($id);

			if (!$redshopBId)
			{
				continue;
			}

			$ch = curl_init($url . $params);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('id' => $redshopBId)));
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $accessToken));
			$result = curl_exec($ch);
			$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$error  = curl_error($ch);
			curl_close($ch);

			if (json_decode($result)->result == 1)
			{
				$db    = JFactory::getDbo();
				$query = $db->getQuery(true)
					->delete($db->qn('#__redshop_redshopb_manufacturer_xref'))
					->where(array($db->qn('redshopb_manufacturer_id') . ' = ' . $db->q((int) $redshopBId)));

				$db->setQuery($query)->execute();
			}
		}

		return true;
	}

	/**
	 * Ajax execution
	 *
	 * @return  array  Result of syncing.
	 *
	 * @since   1.1
	 * @throws  Exception
	 */
	public function onAjaxSync_B2b_Products()
	{
		JSession::checkToken('get') or die(JText::_('JINVALID_TOKEN'));

		$categories = $this->getAvailableCategoriesIds();

		$app = JFactory::getApplication();
		$db  = JFactory::getDbo();

		$query = $db->getQuery(true)
			->select('*')
			->from($db->qn('#__redshop_product', 'p'))
			->leftJoin($db->qn('#__redshop_product_category_xref', 'ref') . ' ON ' . $db->qn('ref.product_id') . ' = ' . $db->qn('p.product_id'))
			->where($db->qn('ref.category_id') . ' IN (' . implode(',', $categories) . ')')
			->order($db->qn('p.product_id') . ' ASC');

		$returnResult  = array('goToNextStep' => 0);
		$lastProductId = (int) $this->params->get('lastSyncProductId', 0);

		if ($lastProductId)
		{
			$query->where($db->qn('product_id') . ' > ' . $lastProductId);
		}

		$products = $db->setQuery($query)
			->loadObjectList();

		if (empty($products))
		{
			return $returnResult;
		}

		$returnResult['totalProductsAmount']  = count($products);
		$returnResult['passedProductsAmount'] = $returnResult['totalProductsAmount'] - count($products);

		foreach ($products as $product)
		{
			if ($this->isExecutionTimeExceeded())
			{
				$returnResult['goToNextStep'] = 1;

				break;
			}

			$b2bProductId = $this->syncProduct($product);

			if ($b2bProductId === false)
			{
				continue;
			}

			if (!empty($product->product_full_image))
			{
				$this->syncProductImage($product->product_full_image, $b2bProductId);
			}

			if (!empty($product->product_desc))
			{
				$this->syncProductDescription($product->product_desc, $b2bProductId);
			}

			$lastProductId = $product->product_id;
			$returnResult['passedProductsAmount']++;
		}

		if ($returnResult['goToNextStep'] == 1)
		{
			$app->enqueueMessage(
				JText::sprintf(
					'PLG_REDSHOP_PRODUCT_SYNC_B2B_SYNCTOOL_PRODUCTS_START_SYNCHRONIZED_LBL',
					$returnResult['passedProductsAmount'],
					$returnResult['totalProductsAmount']
				)
			);

			$this->params->set('lastSyncProductId', $lastProductId);
		}
		else
		{
			$app->enqueueMessage(
				JText::sprintf('PLG_REDSHOP_PRODUCT_SYNC_B2B_SYNCTOOL_PRODUCTS_START_SYNCHRONIZE_COMPLETE_LBL', $returnResult['totalProductsAmount'])
			);

			$this->params->set('lastSyncProductId', 0);
		}

		$query = $db->getQuery(true)
			->update($db->qn('#__extensions'))
			->where($db->qn('type') . ' = ' . $db->quote('plugin'))
			->where($db->qn('element') . ' = ' . $db->quote($this->_name))
			->where($db->qn('folder') . ' = ' . $db->quote($this->_type))
			->set($db->qn('params') . ' = ' . $db->quote($this->params->toString()));

		if (!$db->setQuery($query)->execute())
		{
			throw new Exception($db->getErrorMsg());
		}

		return $returnResult;
	}

	/**
	 * Method for get available sync categories
	 *
	 * @return  array
	 */
	protected function getAvailableCategoriesIds()
	{
		if ($this->syncCategories === null)
		{
			$categories = $this->params->get('categories', null);
			$recursive  = (boolean) $this->params->get('category_recursive');

			if (empty($categories))
			{
				if (!$recursive)
				{
					$this->syncCategories = array();
				}
				else
				{
					$this->syncCategories = RedshopEntityCategory::getInstance(RedshopHelperCategory::getRootId())->getChildCategories()->ids();
				}
			}
			else
			{
				$ids = $categories;

				if ($recursive)
				{
					foreach ($categories as $categoryId)
					{
						$ids = array_merge($ids, RedshopEntityCategory::getInstance($categoryId)->getChildCategories()->ids());
					}
				}

				$this->syncCategories = $ids;
			}
		}

		return $this->syncCategories;
	}

	/**
	 * Deletes rows both from Sync table and from original table
	 *
	 * @param   int $maxTime If not defined then PHP maximum execution time will be used -10 sec
	 *
	 * @return  boolean
	 */
	public function isExecutionTimeExceeded($maxTime = 0)
	{
		if (empty($maxTime))
		{
			$maxTime = $this->maxExecutionTime - ($this->maxExecutionTime / 3);
		}

		return ((microtime(1) - $this->startTime) >= $maxTime);
	}

	/**
	 * Sync product
	 *
	 * @param   object $product Product data
	 *
	 * @return  boolean
	 */
	protected function syncProduct($product)
	{
		if (empty($product))
		{
			return false;
		}

		$db          = JFactory::getDbo();
		$url         = $this->params->get('url', '');
		$accessToken = $this->getAccessToken();
		$params      = '/index.php?webserviceClient=site&webserviceVersion=1.3.0&option=redshopb&view=product&api=hal';
		$result      = array();
		$type        = 'POST';
		$insert      = true;

		if (empty($url))
		{
			return true;
		}

		if (empty($accessToken))
		{
			return true;
		}

		$redshopBId = $this->getRedshopBId($product->product_id);

		if ($redshopBId)
		{
			$insert       = false;
			$result['id'] = $redshopBId;
			$type         = 'PUT';
		}

		$result['name']     = $product->product_name;
		$result['alias']    = JFilterOutput::stringURLUnicodeSlug($product->product_name);
		$result['sku']      = $product->product_number;
		$result['date_new'] = date('Y-m-d');
		$result['price']    = $product->product_price;
		$result['company']  = $this->params->get('company', 2);

		$ch = curl_init($url . $params);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($result));
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $accessToken));
		$return = curl_exec($ch);
		$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$error  = curl_error($ch);
		curl_close($ch);
		$return = json_decode($return);

		$productId = $return->result;

		if (!$productId)
		{
			return false;
		}

		if ($insert)
		{
			$columns = array('redshop_product_id', 'redshopb_product_id');
			$values  = array($db->q((int) $product->product_id), $db->q((int) $productId));

			$query = $db->getQuery(true)
				->insert($db->quoteName('#__redshop_redshopb_xref'))
				->columns($db->quoteName($columns))
				->values(implode(',', $values));

			$db->setQuery($query)->execute();
		}

		return $productId;
	}

	/**
	 * Sync product image
	 *
	 * @param   string  $image        Product data
	 * @param   integer $productB2BId Product data
	 *
	 * @return  boolean
	 */
	protected function syncProductImage($image, $productB2BId)
	{
		if (!JFile::exists(REDSHOP_FRONT_IMAGES_RELPATH_PRODUCT . $image) || !$productB2BId)
		{
			return false;
		}

		$url         = $this->params->get('url', '');
		$accessToken = $this->getAccessToken();
		$params      = '/index.php?webserviceClient=site&webserviceVersion=1.2.0&option=redshopb&view=product_image&api=Hal';
		$result      = array();
		$type        = 'POST';

		if (empty($url))
		{
			return true;
		}

		if (empty($accessToken))
		{
			return true;
		}

		$result['product_id']   = $productB2BId;
		$result['image']        = basename($image);
		$result['image_upload'] = base64_encode(file_get_contents(JPath::clean(REDSHOP_FRONT_IMAGES_RELPATH_PRODUCT . $image)));

		$ch = curl_init($url . $params);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($result));
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $accessToken));
		$return = curl_exec($ch);
		$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$error  = curl_error($ch);
		curl_close($ch);
		$return = json_decode($return);

		return true;
	}

	/**
	 * Sync product image
	 *
	 * @param   string  $desc         Product description
	 * @param   integer $productB2BId Product data
	 *
	 * @return  boolean
	 */
	protected function syncProductDescription($desc, $productB2BId)
	{
		if (empty($desc) || !$productB2BId)
		{
			return false;
		}

		$url         = $this->params->get('url', '');
		$accessToken = $this->getAccessToken();
		$params      = '/index.php?webserviceClient=site&webserviceVersion=1.0.0&option=redshopb&view=product_description&api=Hal';
		$result      = array();
		$type        = 'POST';

		if (empty($url))
		{
			return true;
		}

		if (empty($accessToken))
		{
			return true;
		}

		$result['product_id']  = $productB2BId;
		$result['description'] = $desc;

		$ch = curl_init($url . $params);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($result));
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $accessToken));
		$return = curl_exec($ch);
		$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$error  = curl_error($ch);
		curl_close($ch);
		$return = json_decode($return);

		return true;
	}
}
