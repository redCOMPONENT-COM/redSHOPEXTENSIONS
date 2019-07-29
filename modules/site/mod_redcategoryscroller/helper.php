<?php
/**
 * @package     RedSHOP.Frontend
 * @subpackage  mod_redcategoryscroller
 *
 * @copyright   Copyright (C) 2008 - 2017 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

use Joomla\Registry\Registry;

JLoader::import('redshop.library');

/**
 * Helper for category scroll module
 *
 * @since  2.0.0
 */
class ModRedCategoryScrollerHelper
{
	/**
	 * Method for get necessary data
	 *
	 * @param   Registry  $params  Module params
	 *
	 * @return  array
	 *
	 * @since   2.0.0
	 */
	public static function getList(&$params)
	{
		$limit      = (int) $params->get('NumberOfCategory', 5);
		$sortMethod = $params->get('ScrollSortMethod', 'random');
		$isFeatured = (boolean) $params->get('featuredCategory', false);
		$categoryId = JFactory::getApplication()->input->getInt('cid', 0);
		$hierarchyTree = RedshopHelperCategory::getCategoryListArray($categoryId, $categoryId);

		$cid = array();

		for ($i = 0, $in = count($hierarchyTree); $i < $in; $i++)
		{
			$cid[] = $hierarchyTree[$i]->id;
		}

		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select('*')
			->from($db->qn('#__redshop_category'))
			->where($db->qn('published') . ' = 1');

		switch ($sortMethod)
		{
			case 'random':
				$query->order('RAND()');
				break;
			case 'oldest':
				$query->order($db->qn('category_pdate') . ' ASC');
				break;
			default:
				$query->order($db->qn('category_pdate') . ' DESC');
				break;
		}


		if ($limit)
		{
			$db->setQuery($query, 0, $limit);
		}
		else
		{
			$db->setQuery($query);
		}
		
		$categories = $db->loadObjectList();
		
		foreach ($categories as $category)
		{
			$temp=self::getPathThumbnail($category);
			$category->rel = $temp->rel;
			$category->abs = $temp->abs;
			$category->link_category = $temp->link_category;
		}
		
		return $categories;
	}
	
	public function getPathThumbnail($category)
	{
		$input          = JFactory::getApplication()->input;
		$model          = JModelLegacy::getInstance('Category', 'RedshopModel');
		$manufacturerId = $model->getState('manufacturer_id');
		
		// Default with JPATH_ROOT . '/components/com_redshop/assets/images/'
		$middlePath        = REDSHOP_FRONT_IMAGES_RELPATH . 'category/';
		$title             = " title='" . $category->name . "' ";
		$alt               = " alt='" . $category->name . "' ";
		$productImg        = REDSHOP_FRONT_IMAGES_ABSPATH . "noimage.jpg";
		$categoryFullImage = REDSHOP_FRONT_IMAGES_ABSPATH . "noimage.jpg";
		
		$resul =  (object)[];
		$resul->rel= REDSHOP_MEDIA_IMAGE_RELPATH . "noimage.jpg";
		$resul->abs= REDSHOP_FRONT_IMAGES_ABSPATH . "noimage.jpg";
		
		
		$height = Redshop::getConfig()->get('THUMB_HEIGHT');
		$width = Redshop::getConfig()->get('THUMB_WIDTH');
		
		if(is_null($height) || is_null($width) || !$height || !$width )
		{
			$height = 500;
			$width = 500;
		}
		
		// Try to get category Itemid
		$categoryItemId = (int) RedshopHelperRouter::getCategoryItemid($category->id);
		$mainItemId     = !$categoryItemId ? $input->getInt('Itemid', null) : $categoryItemId;
		
		// Generate category link
		$link = JRoute::_(
			'index.php?option=' . $input->get('option', 'com_redshop') .
			'&view=category&cid=' . $category->id .
			'&manufacturer_id=' . $manufacturerId .
			'&layout=detail&Itemid=' . $mainItemId
		);
		
		$resul->link_category =$link;
		$medias = RedshopEntityCategory::getInstance($category->id)->getMedia();
		
		/** @var RedshopEntityMediaImage $fullImage */
		$fullImage = null;
		
		foreach ($medias->getAll() as $media)
		{
			/** @var RedshopEntityMedia $media */
			if ($media->get('scope') == 'full')
			{
				$fullImage = RedshopEntityMediaImage::getInstance($media->getId());
				
				break;
			}
		}
		
		if ($fullImage !== null)
		{
			$categoryFullImage = $fullImage->getAbsImagePath();
			
			// Generate thumb with watermark if needed.
			if (Redshop::getConfig()->getBool('WATERMARK_CATEGORY_THUMB_IMAGE'))
			{
				$productImg = RedshopHelperMedia::watermark(
					'category',
					$category->category_full_image,
					$width,
					$height,
					Redshop::getConfig()->get('WATERMARK_CATEGORY_THUMB_IMAGE')
				);
				if(!empty($productImg['rel']) && !empty($productImg['abs']))
				{
					$resul->rel=$productImg['rel'];
					$resul->abs=$productImg['abs'];
				}
			}
			else
			{
				$productImg = $fullImage->generateThumb($width, $height);
				if(!empty($productImg['rel']) && !empty($productImg['abs']))
				{
					$resul->rel=$productImg['rel'];
					$resul->abs=$productImg['abs'];
				}
				$productImg = $productImg['abs'];
			}
		}
		elseif (Redshop::getConfig()->get('CATEGORY_DEFAULT_IMAGE')
			&& JFile::exists($middlePath . Redshop::getConfig()->get('CATEGORY_DEFAULT_IMAGE')))
		{
			// Use default image
			$categoryFullImage = Redshop::getConfig()->get('CATEGORY_DEFAULT_IMAGE');
			$productImg        = RedshopHelperMedia::watermark(
				'category',
				Redshop::getConfig()->get('CATEGORY_DEFAULT_IMAGE'),
				$width,
				$height,
				Redshop::getConfig()->get('WATERMARK_CATEGORY_THUMB_IMAGE')
			);
			
			$productImg = $fullImage->generateThumb($width, $height);
			if(!empty($productImg['rel']) && !empty($productImg['abs']))
			{
				$resul->rel=$productImg['rel'];
				$resul->abs=$productImg['abs'];
			}
		}
		
		return $resul;
	}
}
