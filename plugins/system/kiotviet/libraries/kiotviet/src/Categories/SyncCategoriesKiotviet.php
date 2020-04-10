<?php
namespace Kiotviet\Categories;

class SyncCategoriesKiotviet extends Category
{
	protected $baseUrl = 'https://public.kiotapi.com/';

	public function execute()
	{
		$Categories = \RedshopHelperCategory::getCategoryTree(1);
		$field      = \RedshopHelperExtrafields::getField('rs_kiotviet_category_id');

		foreach ($Categories as $category)
		{
			$formData = array();
			$formData['categoryName'] = $category->name;
			$formData['parentId'] = Category::getCategoryByFieldData($field->id, 0, $category->id);
			$formData['categoryName'] = $category->name;

			$formParam = array('form_params' => $formData);
			$post = array_merge($this->_headers, $formParam);
			echo "<pre>";
			print_r($post);
			echo "</pre>";
			die();
			$response = $this->_client->request('POST', 'categories', $post);
		}
	}
}