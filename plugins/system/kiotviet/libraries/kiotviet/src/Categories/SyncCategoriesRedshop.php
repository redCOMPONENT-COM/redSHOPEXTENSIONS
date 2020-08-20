<?php

namespace Kiotviet\Categories;

class SyncCategoriesRedshop extends Category
{
	protected $primaryKey = 'id';

	protected $nameKey = 'name';

	public function execute()
	{
		$result     = $this->getCategory();
		$categories = $result->data;

		for ($i = 0; $i < $result->total; $i++) {
			$this->storeCategory($categories[$i]);
		}
	}

	public function storeCategory($category)
	{
		$catRootId = \RedshopHelperCategory::getRootId();

		$data              = array();
		$catId             = self::getRedshopCategoryByIdKv($category->categoryId);
		$table             = $this->getTable();
		$data['parent_id'] = $catRootId;
		$data['level']     = '';
		$data['id']        = $catId;
		$data['published'] = 1;
		$data['name']      = $category->categoryName;

		if ($this->_options->get('update_category_per_page')) {
			$data['products_per_page'] = $this->_options->get('category_products_per_page');
		}

		if ($this->_options->get('update_category_template')) {
			$data['template'] = $this->_options->get('category_template');
		}

		if (!empty($category->parentId)) {
			$kvCategory      = $this->getCategoryKiotvietById($category->parentId);
			$categoryRedshopID = self::getRedshopCategoryByIdKv($kvCategory->categoryId);

			if (empty($categoryRedshop)) {
				$categoryRedshopID = self::storeCategory($kvCategory);
			}

			$data['parent_id'] = $categoryRedshopID;
		}

		$table->setLocation($data['parent_id'], 'last-child');

		try {
			if (!$table->bind($data) || !$table->check() || !$table->store()) {
				return false;
			}

			if (!$catId) {
				$this->storeCategoryMapping($category->categoryId, $table->{$this->primaryKey});
			}
		} catch (\Exception $e) {
			return false;
		}

		return $table->{$this->primaryKey};
	}

	public function getCategory()
	{
		$response = $this->_client->request('GET', 'categories', $this->_headers);

		return json_decode($response->getBody()->getContents());
	}

	public function getTable()
	{
		\JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_redshop/tables');

		return \RedshopTable::getInstance('Category', 'RedshopTable');
	}

	public function storeCategoryMapping($kvCategoryId, $rsCategoryId)
	{
		$data                 = new \stdClass;
		$data->kv_category_id = $kvCategoryId;
		$data->rs_category_id = $rsCategoryId;


		\JFactory::getDbo()->insertObject('#__kiotviet_category_mapping', $data);
	}

	public function getCategoryKiotvietById($id)
	{
		$response = $this->_client->request('GET', 'categories/' . $id, $this->_headers);

		return json_decode($response->getBody()->getContents());
	}
}