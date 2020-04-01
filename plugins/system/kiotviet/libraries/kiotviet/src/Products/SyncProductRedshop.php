<?php

namespace Kiotviet\Products;

use Kiotviet\Categories\Category;
use Kiotviet\Categories\SyncCategoriesRedshop;

class SyncProductRedshop extends Product
{
	protected $primaryKey = 'product_id';

	protected $nameKey = 'product_name';

	protected $syncCategory;

	public $limit;

	public $startLimit;

	public function __construct($accessToken, $retailerName, $options = array())
	{
		$this->syncCategory = new SyncCategoriesRedshop($accessToken, $retailerName, $options);

		parent::__construct($accessToken, $retailerName, $options);
	}

	public function execute($isCron = 0)
	{
		$dataProduct = $this->getProudcts($this->limit, $this->startLimit);

		for ($i = 0; $i < $this->limit; $i++) {
			$kvProduct = $dataProduct->data[$i];

			if (empty($kvProduct)) {
				return false;
			}

			// Get product redshop by product number
			$productRedshop = \Redshop\Repositories\Product::getProductByNumber($kvProduct->code);

			$isNew = empty($productRedshop) ? true : false;

			if ($this->_options->get('update_redshop_product')) {
				$productId = $this->storeProduct($kvProduct, $productRedshop->product_id);
			} else {
				$productId = $productRedshop->product_id;
			}


			if ($this->_options->get('update_redshop_image') && $productId) {
				$this->storeAdditionalImages($productId, $kvProduct->images);
			}

			if ($this->_options->get('update_redshop_stockroom') && $productId) {
				$this->storeStockRoom($productId, $kvProduct);
			}

			$this->storeUnitsKiotviet($kvProduct, $productId);

			$this->storeAttributeKiotviet($kvProduct, $productId);

			$this->storeAccessories($kvProduct, $productId);

			if ($isCron && $productId) {
				echo "----------" . $productId . '------------' . PHP_EOL;
			}
		}
	}

	public function syncProductByIds($productIds)
	{
		foreach ($productIds as $productId) {
//			$rsProduct = \RedshopHelperProduct::getProductById($productId);
			$rsProduct = \Redshop\Product\Product::getProductById($productId);
			$kvProduct = $this->getProductByCode($rsProduct->product_number);

			if ($this->_options->get('update_redshop_product')) {
				$this->storeProduct($kvProduct, $productId);
			}

			if ($this->_options->get('update_redshop_image') && $productId && isset($kvProduct->images)) {
				$this->storeAdditionalImages($productId, $kvProduct->images);
			}

			if ($this->_options->get('update_redshop_stockroom') && $productId) {
				$this->storeStockRoom($productId, $kvProduct);
			}

			$this->storeUnitsKiotviet($kvProduct, $productId);

			$this->storeAttributeKiotviet($kvProduct, $productId);

			$this->storeAccessories($kvProduct, $productId);
		}
	}

	public function getProudcts($limit = 20, $limitStart = 0)
	{
		$body = array(
			'pageSize'            => $limit,
			'currentItem'         => $limitStart,
			'orderBy'             => 'Name',
			'includeInventory'    => true,
			'includePricebook'    => true,
			'IncludeBatchExpires' => true
		);


		$request = array_merge($this->_headers, array('query' => $body));
		$response = $this->_client->request('GET', 'products?' . http_build_query($body), $request);

		return json_decode($response->getBody()->getContents());
	}

	public function getProductByCode($code)
	{
		$response = $this->_client->request('GET', 'products/code/' . $code, $this->_headers);

		return json_decode($response->getBody()->getContents());
	}

	public function storeProduct($kvProduct, $pid = 0)
	{
		if (!empty($kvProduct->masterProductId)) {
			return false;
		}

		$table           = $this->getTable('Product_Detail');
		$db              = \JFactory::getDbo();
		$catIdRedshop    = Category::getRedshopCategoryByIdKv($kvProduct->categoryId);

		// Store Price
		if (!empty($kvProduct->units)) {
			foreach ($kvProduct->units as $subProduct) {
				preg_match("/^2(0|4).[A-Z]{2,5}$/", $subProduct->unit, $matches);

				if (!empty($matches)) {
					$queryPrice = $db->getQuery(true)
						->delete($db->qn("#__redshop_product_price"))
						->where($db->qn('product_id') . ' = ' . $db->q($pid));
					$db->setQuery($queryPrice)->execute();

					$productPrice    = $this->getTable('prices_detail');
					$conversionValue = $subProduct->conversionValue;
					// Get product redshop
					$productRedshop = \Redshop\Repositories\Product::getProductByNumber($kvProduct->code);
					$quantity       = $subProduct->conversionValue;
					$price          = $subProduct->basePrice / $quantity;
					$quantityEnd    = $conversionValue == 20 ? 23 : 999;

					$productPrice->load(
						array(
							'product_id'           => $productRedshop->product_id,
							'price_quantity_start' => $quantity,
							'price_quantity_end'   => $quantityEnd
						)
					);

					$productPrice->product_id           = $productRedshop->product_id;
					$productPrice->product_price        = $price;
					$productPrice->product_currency     = \Redshop::getConfig()->get('CURRENCY_CODE');
					$productPrice->shopper_group_id     = 1;
					$productPrice->price_quantity_start = $quantity;
					$productPrice->price_quantity_end   = $quantityEnd;
					$productPrice->store();
				}
			}
		}

		if (!$catIdRedshop) {
			$kvCategory   = $this->syncCategory->getCategoryKiotvietById($kvProduct->categoryId);
			$catIdRedshop = $this->syncCategory->storeCategory($kvCategory);
		}

		$data                   = array();
		$data['product_id']     = $pid;
		$data['product_name']   = $kvProduct->name;
		$data['product_number'] = $kvProduct->code;
		$data['cat_in_sefurl']  = $catIdRedshop;
		$data['product_type']   = 'product';
		$data['published']      = 1;

		if ($this->_options->get('update_product_template'))
		{
			$data['product_template'] = $this->_options->get('product_template');
		}

		$data['product_s_desc'] = isset($kvProduct->description) ? $kvProduct->description : '';

		$data['not_for_sale'] = 0;

//		if ($this->checkStockIsExists($kvProduct) == 0)
//		{
//			$data['not_for_sale'] = 1;
//		}
//		else
//		{
//			$data['not_for_sale'] = 0;
//		}

		if (!empty($kvProduct->images) && $this->_options->get('update_redshop_image')) {
			$url        = $kvProduct->images[0];
			$binaryData = file_get_contents($url);
			$imageName  = basename($url);
			$fileName   = $imageName . '.png';
			$dest       = REDSHOP_FRONT_IMAGES_RELPATH . 'product/' . $fileName;

			\JFile::write($dest, $binaryData);

			$data['product_full_image'] = $fileName;
		}

		$isNew = false;

		// Try to load old data.
		if (array_key_exists($this->primaryKey, $data) && $data[$this->primaryKey]) {
			$isNew = $table->load($data[$this->primaryKey]);
		}

		if (!$isNew || ($isNew && $this->_options->get('update_redshop_price'))) {
			$data['product_price'] = $kvProduct->basePrice;
		}


		if (!$table->bind($data) && !$table->check()) {
			return false;
		}

		// Insert for new data or update exist data.
		if ((!$isNew && !$db->insertObject('#__redshop_product', $table, $this->primaryKey)) || !$table->store(false)) {
			return false;
		}

		$categoryXref              = new \stdClass;
		$categoryXref->category_id = $catIdRedshop;
		$categoryXref->product_id  = $table->{$this->primaryKey};
		$query                     = $db->getQuery(true);

		$this->storeCategoryXref($catIdRedshop, $table->{$this->primaryKey});

		if (!empty($data['product_full_image']) && $this->_options->get('update_redshop_image')) {
			$query->clear()
				->select("COUNT(*)")
				->from($db->qn('#__redshop_media'))
				->where($db->qn('media_name') . ' LIKE ' . $db->quote($data['product_full_image']))
				->where($db->qn('media_section') . ' LIKE ' . $db->quote('product'))
				->where($db->qn('section_id') . ' = ' . $db->quote($table->{$this->primaryKey}))
				->where($db->qn('media_type') . ' = ' . $db->quote('images'))
				->where($db->qn('published') . ' = ' . $db->quote('1'));

			$count = $db->setQuery($query)->loadResult();

			if (!$count) {
				$mediaTable                 = $this->getTable('Media_Detail');
				$mediaTable->media_id       = 0;
				$mediaTable->media_name     = $data['product_full_image'];
				$mediaTable->media_section  = 'product';
				$mediaTable->section_id     = $table->{$this->primaryKey};
				$mediaTable->media_type     = 'images';
				$mediaTable->media_mimetype = '';
				$mediaTable->published      = 1;
				$mediaTable->store();
				unset($mediaTable);
			}
		}

		return $table->{$this->primaryKey};
	}

	public function removeCategoryXref($pid)
	{
		$db = \JFactory::getDbo();
		$query = $db->getQuery(true)
			->delete($db->qn('#__redshop_product_category_xref'))
			->where(
				$db->qn('product_id') . ' = ' . $db->q($pid)
			);

		$db->setQuery($query)->execute();
	}

	public function storeCategoryXref($catId, $pid)
	{
		if ($this->_options->get('remove_category_maping'))
		{
			$this->removeCategoryXref($pid);
		}

		$db = \JFactory::getDbo();

		$query = $db->getQuery(true)
			->select('category_id')
			->from($db->qn('#__redshop_product_category_xref'))
			->where($db->qn('category_id') . ' = ' . $db->q($catId))
			->where($db->qn('product_id') . ' = ' . $db->q($pid));

		if (!$db->setQuery($query)->loadResult()) {
			$query->clear()
				->insert($db->qn('#__redshop_product_category_xref'))
				->columns(array('category_id', 'product_id'))
				->values($catId . ' , ' . $pid);

			$db->setQuery($query)->execute();
		}
	}

	public function storeAdditionalImages($productId = 0, $images = array())
	{
		if (empty($images) || !$productId) {
			return;
		}

		$db = \JFactory::getDbo();

		$query = $db->getQuery(true);

		foreach ($images as $index => $image) {
			$fileName = $this->copyAdditionalImages($productId, $image);

			$query->clear()
				->select('media_id')
				->from($db->qn('#__redshop_media'))
				->where($db->qn('media_name') . ' LIKE ' . $db->quote($fileName))
				->where($db->qn('media_section') . ' = ' . $db->quote('product'))
				->where($db->qn('section_id') . ' = ' . $db->quote($productId))
				->where($db->qn('media_type') . ' = ' . $db->quote('images'));

			$mediaId = $db->setQuery($query)->loadResult();

			if (\JFile::exists(REDSHOP_FRONT_IMAGES_RELPATH . 'product/' . basename($fileName))) {
				if (!$mediaId) {
					$rows                       = $this->getTable('Media_Detail');
					$rows->media_id             = 0;
					$rows->media_name           = $fileName;
					$rows->media_section        = 'product';
					$rows->section_id           = $productId;
					$rows->media_type           = 'images';
					$rows->media_mimetype       = '';
					$rows->published            = 1;
					$rows->media_alternate_text = '';

					$rows->store();
				}
			}
		}

		$this->deleteMediaNotFound($productId, $images);
	}

	public function deleteMediaNotFound($productId, $images)
	{
		$db    = \JFactory::getDbo();
		$query = $db->getQuery(true)
			->select('*')
			->from($db->qn('#__redshop_media'))
			->where($db->qn('media_section') . ' = ' . $db->quote('product'))
			->where($db->qn('section_id') . ' = ' . $db->quote($productId))
			->where($db->qn('media_type') . ' = ' . $db->quote('images'));

		$medias = $db->setQuery($query)->loadObjectList();

		$imagesUpload = array_column(array_map('pathinfo', $images), 'basename');

		foreach ($medias as $media) {
			$fileName = pathinfo($media->media_name, PATHINFO_FILENAME);

			if (!in_array($fileName, $imagesUpload)) {
				$dest = REDSHOP_FRONT_IMAGES_RELPATH . 'property/' . $media->media_name;
				$tsrc = REDSHOP_FRONT_IMAGES_RELPATH . 'property/thumb/' . $media->media_name;

				if (file_exists($dest)) {
					\JFile::delete($dest);
				}

				if (file_exists($tsrc)) {
					\JFile::delete($tsrc);
				}

				$query->clear()
					->delete($db->qn('#__redshop_media'))
					->where($db->qn('media_id') . ' = ' . $db->q($media->media_id));

				$db->setQuery($query)->execute();
			}
		}
	}

	public function getTable($name, $exten = 'Table')
	{
		\JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_redshop/tables');

		return \JTable::getInstance($name, $exten);
	}

	protected function renameToUniqueValue($fieldName, $fieldValue, $style = 'default')
	{
		$table = $this->getTable();

		while ($table->load(array($fieldName => $fieldValue))) {
			$fieldValue = JString::increment($fieldValue, $style);
		}

		return $fieldValue;
	}

	public function copyAdditionalImages(&$data, $image)
	{
		$url        = $image;
		$binaryData = file_get_contents($url);
		$imageName  = basename($url);
		$fileName   = $imageName . '.png';
		$dest       = REDSHOP_FRONT_IMAGES_RELPATH . 'product/' . $fileName;

		\JFile::write($dest, $binaryData);

		return $fileName;
	}

	public function storeStockRoom($rsProductId, $productDetail)
	{
		if ($productDetail->formulas) {
			return true;
		}

		$db = \JFactory::getDbo();

		$query = $db->getQuery(true)
			->delete($db->qn('#__redshop_product_stockroom_xref'))
			->where($db->qn('product_id') . ' = ' . $db->q($rsProductId));

		if (!$db->setQuery($query)->execute()) {
			return false;
		}

		foreach ($productDetail->inventories as $inventory) {
			foreach ($this->_options->get('mapping_stock') as $key => $value) {
				if ($inventory->branchId == $value->branch) {
					$stockSave                   = new \stdClass;
					$stockSave->product_id       = $rsProductId;
					$stockSave->stockroom_id     = $value->stock;
					$stockSave->quantity         = $inventory->onHand;
					$stockSave->preorder_stock   = 0;
					$stockSave->ordered_preorder = 0;

					$db->insertObject('#__redshop_product_stockroom_xref', $stockSave, 'stockroom_id');

					break;
				}
			}
		}
	}

	public function getProductRedshopByIdKioviet($id)
	{
		$response = $this->_client->request('GET', 'products/' . $id, $this->_headers);

		return \Redshop\Repositories\Product::getProductByNumber(
			json_decode($response->getBody()->getContents())->code
		);
	}

	public function checkStockIsExists($product)
	{
		$stock = 0;
		foreach ($product->inventories as $inventory) {
			$stock += $inventory->onHand;
		}

		return $stock;
	}

	public function storeUnitsKiotviet($kvProduct, $productId)
	{
		$conversionFieldId = $this->_options->get('field_conversion');
		$unitFieldId       = $this->_options->get('field_unit');


		// Store Field unit
		$this->storeField($unitFieldId, $kvProduct->unit, $productId, \RedshopHelperExtrafields::SECTION_PRODUCT);

		$conversionValue = array();

		if (!empty($kvProduct->units)) {
			foreach ($kvProduct->units as $key => $unit) {
				if (!in_array($unit->conversionValue, $conversionValue)) {
					$conversionValue[] = $unit->conversionValue;
				}
			}

			$this->storeField(
				$conversionFieldId,
				implode(',', $conversionValue),
				$productId,
				\RedshopHelperExtrafields::SECTION_PRODUCT
			);
		}
	}


	public function storeField($fieldId, $dataTxt, $itemId = 0, $section = 0)
	{
		$tableFieldData = $this->getTable('Field_Data', 'RedshopTable');

		if ($tableFieldData->load(array('fieldid' => $fieldId, 'itemid' => $itemId, 'section' => $section))) {
			$tableFieldData->set('data_txt', $dataTxt);
			$tableFieldData->store();
		} else {
			$FieldData           = new \stdClass;
			$FieldData->fieldid  = $fieldId;
			$FieldData->data_txt = $dataTxt;
			$FieldData->itemid   = $itemId;
			$FieldData->section  = $section;

			\JFactory::getDbo()->insertObject('#__redshop_fields_data', $FieldData);
		}
	}

	public function storeAttributeKiotviet($kvProduct, $productId)
	{
		if (empty($kvProduct->attributes)) {
			return false;
		}

		$kvAttributes = $kvProduct->attributes;

		foreach ($kvAttributes as $key => $kvAttribute) {
			$FieldName = 'rs_' . strtolower($kvAttribute->attributeName);

			$customField = \RedshopEntityField::getInstanceByField('name', $FieldName)->getItem();
			if (empty($customField)) {
				$table = $this->getTable('Field', 'RedshopTable');
				$table->reset();

				$data = array(
					'id'                  => 0,
					'title'               => $kvAttribute->attributeName,
					'name'                => $FieldName,
					'type'                => 1,
					'section'             => 1,
					'display_in_product'  => 0,
					'display_in_checkout' => 0,
					'required'            => 0,
					'is_searchable'       => 1,
					'published'           => 1,
					'show_in_front'       => 1
				);

				$table->save($data);

				$fieldId = $table->getPrimaryKey()['id'];
			} else {
				$fieldId = $customField->id;
			}

			$this->storeField(
				$fieldId,
				$kvAttribute->attributeValue,
				$productId,
				\RedshopHelperExtrafields::SECTION_PRODUCT
			);
		}
	}

	public function storeAccessories($kvProduct, $productId)
	{
		$this->removeAccessoryById($productId);

		if (!empty($kvProduct->formulas)) {
			foreach ($kvProduct->formulas as $accessory) {
				$productRedshop = \Redshop\Repositories\Product::getProductByNumber($accessory->code);
				$childProduct   = 0;

				if (!isset($productRedshop->product_id)) {
					$accessoryProductKv = $this->getProductByCode($accessory->code);
					$childProduct       = $this->storeProduct($accessoryProductKv);
				}

				$accDetail                   = $this->getTable('accessory_detail');
				$accDetail->child_product_id = isset($productRedshop->product_id) ? $productRedshop->product_id : $childProduct;
				$accDetail->product_id       = $productId;
				$accDetail->accessory_price  = 0;
				$accDetail->oprand           = '=';
				$accDetail->store();
			}
		}
	}

	public function removeAccessoryById($productId)
	{
		$db    = \JFactory::getDbo();
		$query = $db->getQuery(true)
			->delete($db->qn('#__redshop_product_accessory'))
			->where($db->qn('product_id') . ' = ' . $db->q($productId));

		$db->setQuery($query)->execute();
	}
}