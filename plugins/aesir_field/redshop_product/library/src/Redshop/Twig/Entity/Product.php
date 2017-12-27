<?php
/**
 * @package     Redshop.Plugin
 * @subpackage  Entity.Twig
 *
 * @copyright   Copyright (C) 2012 - 2017 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later, see LICENSE.
 */

namespace Redshop\Twig\Entity;

use Aesir\Entity\Twig\AbstractTwigEntity;
use function array_values;
use function var_dump;

defined('_JEXEC') or die;

/**
 * Category Twig Entity.
 *
 * @since  1.1.0
 */
class Product extends AbstractTwigEntity
{
	/**
	 * Constructor.
	 *
	 * @param   \RedshopEntityProduct $entity The entity
	 */
	public function __construct(\RedshopEntityProduct $entity)
	{
		$this->entity = $entity;
	}

	/**
	 * Method for get/generate thumbnail of Product Full Image
	 *
	 * @param   integer  $width   Width of thumbnail
	 * @param   integer  $height  Height of thumbnail
	 *
	 * @return string
	 */
	public function thumbFullImage($width = 100, $height = 100)
	{
		return \RedshopHelperMedia::getImagePath(
			$this->entity->get('product_full_image'),
			'',
			'thumb',
			'product',
			(int) $width,
			(int) $height,
			\Redshop::getConfig()->get('USE_IMAGE_SIZE_SWAPPING')
		);
	}

	/**
	 * Method for render add to cart form.
	 *
	 * @param   string  $templateName           Template add to cart name
	 * @param   string  $attributeTemplate      Attribute template. Use in Add to cart item per product.
	 * @param   string  $attributeCartTemplate  Attribute template. Use in Add to cart item per product attributes.
	 *
	 * @return  string
	 *
	 * @throws  \Exception
	 */
	public function addToCartForm($templateName = 'add_to_cart1', $attributeTemplate = 'attributes', $attributeCartTemplate = 'attributes_listing1')
	{
		\JHtml::script('system/mootools-core.js', false, true);
		\JHtml::script('system/mootools-more.js', false, true);
		\JHtml::script('system/validate.js', true, false);

		\JHtml::_('redshopjquery.framework');
		\JHtml::script('com_redshop/jquery.validate.js', false, true);
		\JHtml::script('com_redshop/common.js', false, true);
		\JHtml::script('com_redshop/registration.js', false, true);
		\JHtml::script('com_redshop/redbox.js', false, true);
		\JHtml::script('com_redshop/attribute.js', false, true);

		$attributes = (array) $this->entity->get('attributes', array());
		$content    = '{form_addtocart:' . $templateName . '}';

		// Has attribute
		if (!empty($attributes))
		{
			$individualAttribute = \Redshop::getConfig()->getBool('INDIVIDUAL_ADD_TO_CART_ENABLE', false);

			$section  = $individualAttribute ? 'attributewithcart_template' : 'attribute_template';
			$template = $individualAttribute ? $attributeCartTemplate : $attributeTemplate;
			$content  = $individualAttribute ? '' : $content;

			$content = \productHelper::getInstance()->getAttributeTemplate('{' . $section . ':' . $template . '}');
			$content = \RedshopHelperAttribute::replaceAttributeData(
				$this->entity->getId(), 0, 0, array_values($attributes), '{' . $section . ':' . $template . '}', $content
			);
		}

		return \productHelper::getInstance()->replaceCartTemplate($this->entity->getId(), 0, 0, 0, $content);
	}
}
