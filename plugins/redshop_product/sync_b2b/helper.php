<?php
/**
 * @package     RedSHOP
 * @subpackage  Plugin
 *
 * @copyright   Copyright (C) 2008 - 2017 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

use Joomla\Registry\Registry;

defined('_JEXEC') or die;
JLoader::import('redshop.library');

/**
 * Plugin will synchronize data to redSHOP B2b
 *
 * @since  1.0.0
 */
class PlgRedshop_ProductSync_B2bHelper
{
	const WS_PRO_IMG     = '/index.php?webserviceClient=site&webserviceVersion=1.2.0&option=redshopb&view=product_image&api=Hal';
	const WS_PRO_ATT     = '/index.php?webserviceClient=site&webserviceVersion=1.0.0&option=redshopb&view=product_attribute&api=Hal';
	const WS_PRO_ATT_VAL = '/index.php?webserviceClient=site&webserviceVersion=1.1.0&option=redshopb&view=product_attribute_value&api=Hal';
	const WS_PRO_DESC    = '/index.php?webserviceClient=site&webserviceVersion=1.0.0&option=redshopb&view=product_description&api=Hal';
	const WS_PRO         = '/index.php?webserviceClient=site&webserviceVersion=1.3.0&option=redshopb&view=product&api=Hal';

	const TAG_IMG_ADD   = 'product-additional-image';
	const TAG_IMG       = 'product-image';
	const TAG_ATTR      = 'product-attribute';
	const TAG_ATTR_PROP = 'product-attribute-property';
	const TAG_IMG_ATTR  = 'product-image-attribute';
	const TAG_DESC      = 'product-description';

	/**
	 * Sync product image
	 *
	 * @param   string  $url          Product data
	 * @param   string  $accessToken  Product data
	 * @param   string  $image        Product data
	 * @param   integer $productId    Product data
	 * @param   integer $productB2BId Product data
	 *
	 * @return  boolean
	 */
	public static function syncProductImage($url, $accessToken, $image, $productId, $productB2BId)
	{
		if (empty($url) || empty($accessToken) || !$productB2BId || !$productId)
		{
			return false;
		}

		if (empty($image) || !JFile::exists(JPATH_ROOT . '/components/com_redshop/assets/images/product/' . $image))
		{
			self::cleanupProductImage($url, $accessToken, $productId, $productB2BId);

			return true;
		}

		$data = array();
		$type = 'POST';

		$data['product_id']   = $productB2BId;
		$data['image']        = basename($image);
		$data['image_upload'] = file_get_contents(JPath::clean(JPATH_ROOT . '/components/com_redshop/assets/images/product/' . $image));
		$data['image_upload'] = base64_encode($data['image_upload']);

		$remoteId = self::getRemoteId(self::TAG_IMG, $productId);

		if ($remoteId)
		{
			$type       = 'PUT';
			$data['id'] = $remoteId;
		}

		$return = self::sendData($url . self::WS_PRO_IMG, $accessToken, $data, $type);

		// In case fail when update, try to insert new image instead.
		if (!$return->result)
		{
			// self::cleanupProductImage($url, $accessToken, $productId, $productB2BId);

			if (isset($data['id']))
			{
				unset($data['id']);
			}

			$type   = 'POST';
			$return = self::sendData($url . self::WS_PRO_IMG, $accessToken, $data, $type);
		}

		if ($type === 'POST')
		{
			self::storeSync(self::TAG_IMG, $productId, (int) $return->result);
		}

		return true;
	}

	/**
	 * Method for clean up duplicate images.
	 *
	 * @param   string  $url          Url
	 * @param   string  $accessToken  Access token
	 * @param   integer $productId    Product ID
	 * @param   integer $productB2BId B2B Product ID
	 *
	 * @return  void
	 */
	public static function cleanupProductImage($url, $accessToken, $productId, $productB2BId)
	{
		if (empty($url) || empty($accessToken) || !$productB2BId || !$productId)
		{
			return;
		}

		$remoteImages = self::sendData($url . self::WS_PRO_IMG . '&filter[product_id]=' . $productB2BId, $accessToken, array(), 'GET');
		$remoteImages = $remoteImages->totalItems > 0 ? $remoteImages->_embedded->item : array();

		if (empty($remoteImages))
		{
			return;
		}

		// Check product main image.
		$productImageRemoteId  = self::getRemoteId(self::TAG_IMG, $productId);

		$additionalImages    = self::getAdditionalImages($productId);
		$additionalRemoteIds = array();

		if (!empty($additionalImages))
		{
			foreach ($additionalImages as $additionalImage)
			{
				$additionalRemoteIds[] = $additionalImage->remote_id;
			}
		}

		foreach ($remoteImages as $remoteImage)
		{
			// Skip if this image if from attributes.
			if ($remoteImage->product_attribute_value_id)
			{
				continue;
			}

			// Do for additional images.
			if ($remoteImage->view == 0)
			{
				// Skip if this image is already synced with additional images.
				if (in_array($remoteImage->id, $additionalRemoteIds))
				{
					continue;
				}

				self::sendData($url . self::WS_PRO_IMG, $accessToken, array('id' => $remoteImage->id), 'DELETE');

				$additionalRemoteIds = array();

				// Try to delete old reference if not exist.
				foreach ($additionalImages as $index => $additionalImage)
				{
					if ($additionalImage->remote_id != $remoteImage->id)
					{
						$additionalRemoteIds[] = $additionalImage->media_id;

						continue;
					}

					unset($additionalImages[$index]);

					self::cleanRef(self::TAG_IMG_ADD, $additionalImage->media_id, $remoteImage->id);
				}
			}
			// Skip image if this is main image and same with product main image sync ref ID
			elseif ($productImageRemoteId == $remoteImage->id)
			{
				continue;
			}

			self::sendData($url . self::WS_PRO_IMG, $accessToken, array('id' => $remoteImage->id), 'DELETE');
		}

		// Clean up remote reference if needed
		self::cleanRef(self::TAG_IMG, $productId, $productImageRemoteId);
	}

	/**
	 * @param   string $url         Url of server
	 * @param   string $accessToken Access token
	 * @param   array  $data        Data
	 * @param   string $type        Type ("POST", "PUT", "DELETE")
	 *
	 * @return mixed
	 */
	public static function sendData($url, $accessToken, $data, $type = 'POST')
	{
		$headers   = array('Authorization' => 'Bearer ' . $accessToken);
		$transport = new JHttpTransportCurl(new Registry);
		$uri       = new JUri($url);

		if ($type == 'GET')
		{
			$data = null;
		}

		try
		{
			$return = $transport->request($type, $uri, $data, $headers, 10);
			$return = new Registry($return->body);

			return $return->toObject();
		}
		catch (Exception $e)
		{
			$return = array('result' => 0);

			return (object) $return;
		}
	}

	/**
	 * Method for get remote ID of specific data.
	 *
	 * @param   string  $object  Object type
	 * @param   integer $localId Local ID
	 *
	 * @return  integer  Remote ID
	 */
	public static function getRemoteId($object = '', $localId = 0)
	{
		$db = JFactory::getDbo();

		$query = $db->getQuery(true)
			->select($db->qn('remote_id'))
			->from($db->qn('#__redshop_syncb2b_remote'))
			->where($db->qn('object') . ' = ' . $db->quote($object))
			->where($db->qn('local_id') . ' = ' . (int) $localId)
			->order($db->qn('id') . ' DESC');

		return (int) $db->setQuery($query)->loadResult();
	}

	/**
	 * Method for get all additional media of product
	 *
	 * @param   integer $productId Local ID
	 *
	 * @return  array                List of media
	 */
	public static function getAdditionalImages($productId = 0)
	{
		$db = JFactory::getDbo();

		$subQuery = $db->getQuery(true)
			->select($db->qn('product_full_image'))
			->from($db->qn('#__redshop_product'))
			->where($db->qn('product_id') . ' = ' . (int) $productId);

		$query = $db->getQuery(true)
			->select($db->qn(array('m.media_id', 'm.media_name', 'r.remote_id')))
			->from($db->qn('#__redshop_media', 'm'))
			->leftJoin(
				$db->qn('#__redshop_syncb2b_remote', 'r') . ' ON ' . $db->qn('r.local_id') . ' = ' . $db->qn('m.media_id')
				. ' AND ' . $db->qn('r.object') . ' = ' . $db->quote(self::TAG_IMG_ADD)
			)
			->where($db->qn('m.section_id') . ' = ' . (int) $productId)
			->where($db->qn('m.media_section') . ' = ' . $db->quote('product'))
			->where($db->qn('m.media_type') . ' = ' . $db->quote('images'))
			->where($db->qn('m.media_name') . ' NOT IN (' . $subQuery . ')')
			->order($db->qn('m.media_id') . ' DESC');

		return $db->setQuery($query)->loadObjectList();
	}

	/**
	 * Method for clear sync references
	 *
	 * @param   string  $object   Sync key
	 * @param   integer $localId  Local id
	 * @param   integer $remoteId Remote id
	 *
	 * @return  void
	 */
	public static function cleanRef($object = '', $localId = 0, $remoteId = 0)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true)
			->delete($db->qn('#__redshop_syncb2b_remote'))
			->where($db->qn('object') . ' = ' . $db->quote($object))
			->where($db->qn('local_id') . ' = ' . $db->quote($localId))
			->where($db->qn('remote_id') . ' <> ' . $db->quote($remoteId));

		$db->setQuery($query)->execute();
	}

	/**
	 * @param string $object   Object
	 * @param int    $localId  Local ID
	 * @param int    $remoteId Remote ID
	 *
	 * @return mixed
	 */
	public static function storeSync($object = '', $localId = 0, $remoteId = 0)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		if (!$remoteId)
		{
			$query->clear()
				->delete($db->qn('#__redshop_syncb2b_remote'))
				->where($db->qn('object') . ' = ' . $db->quote($object))
				->where($db->qn('local_id') . ' = ' . (int) $localId);
			$db->setQuery($query)->execute();

			return true;
		}

		$query->clear()
			->insert($db->qn('#__redshop_syncb2b_remote'))
			->columns($db->qn(array('object', 'local_id', 'remote_id')))
			->values($db->quote($object) . ',' . (int) $localId . ',' . (int) $remoteId);

		return $db->setQuery($query)->execute();
	}

	/**
	 * Sync product additional images
	 *
	 * @param   string  $url          Product data
	 * @param   string  $accessToken  Product data
	 * @param   integer $productId    Product data
	 * @param   integer $productB2BId Product data
	 *
	 * @return  boolean
	 */
	public static function syncProductAdditionalImages($url, $accessToken, $productId, $productB2BId)
	{
		if (empty($url) || empty($accessToken) || !$productB2BId || !$productId)
		{
			return false;
		}

		$additionalImages = self::getAdditionalImages($productId);

		if (empty($additionalImages))
		{
			return true;
		}

		foreach ($additionalImages as $additionalImage)
		{
			$image = $additionalImage->media_name;

			if (empty($image) || !JFile::exists(JPATH_ROOT . '/components/com_redshop/assets/images/product/' . $image))
			{
				continue;
			}

			$data = array();
			$type = 'POST';

			$data['product_id']   = $productB2BId;
			$data['image']        = basename($image);
			$data['image_upload'] = file_get_contents(JPath::clean(JPATH_ROOT . '/components/com_redshop/assets/images/product/' . $image));
			$data['image_upload'] = base64_encode($data['image_upload']);
			$data['product_view'] = 0;

			$remoteId = self::getRemoteId(self::TAG_IMG_ADD, $additionalImage->media_id);

			if ($remoteId)
			{
				$type       = 'PUT';
				$data['id'] = $remoteId;
			}

			$return = self::sendData($url . self::WS_PRO_IMG, $accessToken, $data, $type);

			// In case fail when update, try to insert new image instead.
			if (!$return->result)
			{
				// self::cleanupProductImage($url, $accessToken, $productId, $productB2BId);

				if (isset($data['id']))
				{
					unset($data['id']);
				}

				$type   = 'POST';
				$return = self::sendData($url . self::WS_PRO_IMG, $accessToken, $data, $type);
			}

			if ($type === 'POST')
			{
				self::storeSync(self::TAG_IMG_ADD, $additionalImage->media_id, (int) $return->result);
			}
		}

		return true;
	}

	/**
	 * Sync product attributes
	 *
	 * @param   string  $url          Product data
	 * @param   string  $accessToken  Product data
	 * @param   array   $attributes   Product description
	 * @param   integer $productId    Product ID
	 * @param   integer $productB2BId Product data
	 *
	 * @return  boolean
	 */
	public static function syncProductAttributes($url, $accessToken, $attributes = array(), $productId = 0, $productB2BId = 0)
	{
		if (empty($url) || empty($accessToken) || empty($attributes) || !$productId || !$productB2BId)
		{
			return true;
		}

		foreach ($attributes as $attribute)
		{
			$type = 'POST';
			$data = array(
				'product_id' => $productB2BId,
				'name'       => $attribute->attribute_name,
			);

			if ($attribute->display_type == 'dropdown')
			{
				$data['type_id'] = !$attribute->allow_multiple_selection ? 5 : 6;
			}
			else
			{
				$data['type_id'] = !$attribute->allow_multiple_selection ? 8 : 7;
			}

			$remoteId = self::getRemoteId(self::TAG_ATTR, $attribute->attribute_id);

			if ($remoteId)
			{
				$type       = 'PUT';
				$data['id'] = $remoteId;
			}

			$return = self::sendData($url . self::WS_PRO_ATT, $accessToken, $data, $type);

			if (!$return->result)
			{
				self::cleanupProductAttributes($url, $accessToken, $productB2BId, $attributes);

				return self::syncProductAttributes($url, $accessToken, $attributes, $productId, $productB2BId);
			}

			$remoteAttributeId = $type === 'PUT' ? $remoteId : (int) $return->result;

			if ($type === 'POST' && $remoteAttributeId)
			{
				self::storeSync(self::TAG_ATTR, $attribute->attribute_id, $remoteAttributeId);
			}

			if (empty($attribute->properties))
			{
				continue;
			}

			// Sync properties.
			self::syncProductAttributeValues($url, $accessToken, $attribute->properties, $remoteAttributeId, $productB2BId);
		}

		return true;
	}

	/**
	 * @param $url
	 * @param $accessToken
	 * @param $attributeId
	 * @param $productB2BId
	 */
	public static function cleanupProductAttributes($url, $accessToken, $productB2BId, $attributes)
	{
		if (empty($url) || empty($accessToken) || !$productB2BId)
		{
			return;
		}

		$remoteUrl = $url . self::WS_PRO_ATT . '&filter[product_id]=' . $productB2BId;

		$remoteAttributes = self::sendData(
			$remoteUrl, $accessToken, array(), 'GET'
		);
		$remoteAttributes = !empty($remoteAttributes) && $remoteAttributes->totalItems > 0 ? $remoteAttributes->_embedded->item : array();

		if (!empty($remoteAttributes))
		{
			foreach ($remoteAttributes as $remoteAttribute)
			{
				self::sendData($url . self::WS_PRO_ATT, $accessToken, array('id' => $remoteAttribute->id), 'DELETE');
			}
		}

		if (empty($attributes))
		{
			return;
		}

		$attributeIds = array();

		foreach ($attributes as $attribute)
		{
			$attributeIds[] = $attribute->attribute_id;
		}

		$db    = JFactory::getDbo();
		$query = $db->getQuery(true)
			->delete($db->qn('#__redshop_syncb2b_remote'))
			->where($db->qn('object') . ' = ' . $db->quote(self::TAG_ATTR))
			->where($db->qn('local_id') . ' IN (' . implode(',', $attributeIds) . ')');
		$db->setQuery($query)->execute();
	}

	public static function syncProductAttributeValues($url, $accessToken, $properties, $remoteAttributeId, $productB2BId)
	{
		if (empty($url) || empty($accessToken) || empty($properties) || !$remoteAttributeId)
		{
			return;
		}

		foreach ($properties as $property)
		{
			$hasImage  = false;
			$type      = 'POST';
			$imagePath = '';

			$data = array(
				'product_attribute_id' => $remoteAttributeId,
				'value'                => $property->property_name,
				'sku'                  => JFilterOutput::stringURLSafe($property->property_name),
				'selected'             => $property->setdefault_selected,
			);

			if (!empty($property->property_main_image))
			{
				$imagePath = JPath::clean(JPATH_ROOT . '/components/com_redshop/assets/images/property/' . $property->property_main_image);

				if (JFile::exists($imagePath))
				{
					$hasImage = true;
				}
			}
			elseif (!empty($property->property_image))
			{
				$imagePath = JPath::clean(JPATH_ROOT . '/components/com_redshop/assets/images/product_attributes/' . $property->property_image);

				if (JFile::exists($imagePath))
				{
					$hasImage = true;
				}
			}

			$remoteId = self::getRemoteId(self::TAG_ATTR_PROP, $property->property_id);

			if ($remoteId)
			{
				$type       = 'PUT';
				$data['id'] = $remoteId;
			}

			$return = self::sendData($url . self::WS_PRO_ATT_VAL, $accessToken, $data, $type);

			if (empty($return) || !$return->result)
			{
				self::cleanUpProductAttributeValues($url, $accessToken, $properties, $remoteAttributeId);

				self::syncProductAttributeValues($url, $accessToken, $properties, $remoteAttributeId, $productB2BId);

				return;
			}

			$productAttributeValueId = $type === 'PUT' ? $remoteId : (int) $return->result;

			if (!$productAttributeValueId)
			{
				continue;
			}

			if ($type === 'POST')
			{
				self::storeSync(self::TAG_ATTR_PROP, $property->property_id, $productAttributeValueId);
			}

			if (!$hasImage)
			{
				continue;
			}

			// Store product attribute image
			$type = 'POST';
			$data = array(
				'product_id'                 => $productB2BId,
				'image'                      => basename($imagePath),
				'product_attribute_value_id' => $productAttributeValueId,
				'image_upload'               => base64_encode(file_get_contents($imagePath))
			);

			$remoteId = self::getRemoteId(self::TAG_IMG_ATTR, $property->property_id);

			if ($remoteId)
			{
				$type       = 'PUT';
				$data['id'] = $remoteId;
			}

			self::cleanUpProductAttributeImages(
				$url . self::WS_PRO_IMG, $accessToken, $property->property_id, $productAttributeValueId, $productB2BId
			);

			$return = self::sendData($url . self::WS_PRO_IMG, $accessToken, $data, $type);

			if (!$return->result)
			{
				if (isset($data['id']))
				{
					unset($data['id']);
				}

				$type = 'POST';
				self::sendData($url . self::WS_PRO_IMG, $accessToken, $data, $type);
			}

			if ($type === 'POST')
			{
				self::storeSync(self::TAG_IMG_ATTR, $property->property_id, (int) $return->result);
			}
		}
	}

	public static function cleanUpProductAttributeValues($url, $accessToken, $properties, $productAttributeValueId)
	{
		if (empty($url) || empty($accessToken) || !$productAttributeValueId)
		{
			return;
		}

		$remoteData = self::sendData(
			$url . self::WS_PRO_ATT_VAL . '&filter[product_attribute_id]=' . $productAttributeValueId, $accessToken, array(), 'GET'
		);

		$remoteData = !empty($remoteData) && $remoteData->totalItems > 0 ? $remoteData->_embedded->item : array();

		if (!empty($remoteData))
		{
			foreach ($remoteData as $remoteDatum)
			{
				self::sendData($url . self::WS_PRO_ATT_VAL, $accessToken, array('id' => $remoteDatum->id), 'DELETE');
			}
		}

		if (empty($properties))
		{
			return;
		}

		$propertyIds = array();

		foreach ($properties as $property)
		{
			$propertyIds[] = $property->property_id;
		}

		$db    = JFactory::getDbo();
		$query = $db->getQuery(true)
			->delete($db->qn('#__redshop_syncb2b_remote'))
			->where($db->qn('object') . ' = ' . $db->quote(self::TAG_ATTR_PROP))
			->where($db->qn('local_id') . ' IN (' . implode(',', $propertyIds) . ')');
		$db->setQuery($query)->execute();
	}

	/**
	 * Method for clean up duplicate images of product attributes.
	 *
	 * @param   string  $url                     Url
	 * @param   string  $accessToken             Access token
	 * @param   integer $propertyId              Property ID
	 * @param   integer $productAttributeValueId B2B Product ID
	 * @param   integer $productB2BId            B2B Product ID
	 *
	 * @return  void
	 */
	public static function cleanUpProductAttributeImages($url, $accessToken, $propertyId, $productAttributeValueId, $productB2BId)
	{
		if (empty($url) || empty($accessToken) || !$productB2BId || !$propertyId || !$productAttributeValueId)
		{
			return;
		}

		$remoteImages = self::sendData($url . self::WS_PRO_IMG . '&filter[product_id]=' . $productB2BId, $accessToken, array(), 'GET');
		$remoteId     = self::getRemoteId(self::TAG_IMG_ATTR, $propertyId);
		$remoteImages = $remoteImages->totalItems > 0 ? $remoteImages->_embedded->item : array();

		if (empty($remoteImages))
		{
			return;
		}

		foreach ($remoteImages as $remoteImage)
		{
			// Skip if this image is from attributes OR same as current Sync ref ID
			if ($remoteImage->product_attribute_value_id !== $productAttributeValueId)
			{
				continue;
			}

			self::sendData($url . self::WS_PRO_IMG, $accessToken, array('id' => $remoteImage->id), 'DELETE');
		}

		self::cleanRef(self::TAG_IMG_ATTR, $propertyId, $remoteId);
	}

	/**
	 * Sync product image
	 *
	 * @param   string  $url          Product data
	 * @param   string  $accessToken  Product data
	 * @param   object  $product      Product description
	 * @param   integer $productB2BId Product B2B ID
	 *
	 * @return  boolean
	 */
	public static function syncProductDescription($url, $accessToken, $product, $productB2BId)
	{
		if (empty($url) || empty($accessToken) || empty($product) || !$productB2BId)
		{
			return false;
		}

		$data = array(
			'product_id'        => $productB2BId,
			'description_intro' => '',
			'description'       => htmlspecialchars($product->product_desc),
			'isNew'             => 1
		);

		$type = 'POST';

		$remoteId = self::getRemoteId(self::TAG_DESC, $product->product_id);

		if ($remoteId)
		{
			$type       = 'PUT';
			$data['id'] = $remoteId;
			unset($data['isNew']);
		}

		$return = self::sendData($url . self::WS_PRO_DESC, $accessToken, $data, $type);

		if (!$return->result)
		{
			self::cleanupProductDescription($url, $accessToken, $product->product_id, $productB2BId);

			if (isset($data['id']))
			{
				unset($data['id']);
			}

			$data['isNew'] = 1;
			$type          = 'POST';

			$return = self::sendData($url . self::WS_PRO_DESC, $accessToken, $data, $type);
		}

		if ($type === 'POST')
		{
			self::storeSync(self::TAG_DESC, $product->product_id, (int) $return->result);
		}

		return true;
	}

	/**
	 * @param $url
	 * @param $accessToken
	 * @param $productId
	 * @param $productB2BId
	 */
	public static function cleanupProductDescription($url, $accessToken, $productId, $productB2BId)
	{
		if (empty($url) || empty($accessToken) || !$productB2BId || !$productId)
		{
			return;
		}

		$remoteDescriptions = self::sendData(
			$url . self::WS_PRO_DESC . '&filter[product_id]=' . $productB2BId, $accessToken, array(), 'GET'
		);
		$remoteId           = self::getRemoteId(self::TAG_DESC, $productId);
		$remoteDescriptions = $remoteDescriptions->totalItems > 0 ? $remoteDescriptions->_embedded->item : array();

		if (empty($remoteDescriptions))
		{
			return;
		}

		foreach ($remoteDescriptions as $remoteDescription)
		{
			// Skip if this description is for attributes
			if (!empty($remoteDescription->main_attribute_value_id) || $remoteId == $remoteDescription->id)
			{
				continue;
			}

			self::sendData($url . self::WS_PRO_DESC, $accessToken, array('id' => $remoteDescription->id), 'DELETE');
		}

		self::cleanRef(self::TAG_DESC, $productId, $remoteId);
	}

	/**
	 * Sync product
	 *
	 * @param   object $product Product data
	 *
	 * @return  mixed
	 */
	public static function syncProduct($url, $accessToken, $company, $product)
	{
		if (empty($product) || empty($url) || empty($accessToken))
		{
			return false;
		}

		$db     = JFactory::getDbo();
		$type   = 'POST';
		$insert = true;

		$data = array(
			'name'     => $product->product_name,
			'alias'    => JFilterOutput::stringURLSafe($product->product_name),
			'sku'      => $product->product_number,
			'date_new' => date('Y-m-d'),
			'price'    => $product->product_price,
			'company'  => $company
		);

		$redshopBId = self::getRedshopBId($product->product_id);

		if ($redshopBId)
		{
			$insert     = false;
			$data['id'] = $redshopBId;
			$type       = 'PUT';
		}

		$return = self::sendData($url . self::WS_PRO, $accessToken, $data, $type);

		if (!$return->result)
		{
			if (isset($data['id']))
			{
				unet($data['id']);
			}

			$type = 'POST';

			$return = self::sendData($url . self::WS_PRO, $accessToken, $data, $type);

			if (!$return->result)
			{
				return false;
			}

			$db    = JFactory::getDbo();
			$query = $db->getQuery(true)
				->delete($db->qn('#__redshop_redshopb_xref'))
				->where($db->qn('redshop_product_id') . ' = ' . $product->product_id);
			$db->setQuery($query)->execute();

			$insert = true;
		}

		if ($insert)
		{
			$columns = array('redshop_product_id', 'redshopb_product_id');
			$values  = array($db->q((int) $product->product_id), $db->q((int) $return->result));

			$query = $db->getQuery(true)
				->insert($db->quoteName('#__redshop_redshopb_xref'))
				->columns($db->quoteName($columns))
				->values(implode(',', $values));

			$db->setQuery($query)->execute();
		}

		return $type === 'PUT' ? $redshopBId : $return->result;
	}

	/**
	 * Method to get redSHOPB Product ID
	 *
	 * @param   int $productId Product id
	 *
	 * @return  integer
	 */
	public static function getRedshopBId($productId)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select($db->qn('redshopb_product_id'))
			->from($db->qn('#__redshop_redshopb_xref'))
			->where($db->qn('redshop_product_id') . ' = ' . $db->q((int) $productId));

		return $db->setQuery($query)->loadResult();
	}
}
