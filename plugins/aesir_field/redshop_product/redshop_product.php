<?php
/**
 * @package     Aesir.Plugin
 * @subpackage  Aesir_Field.Redshop_Product
 *
 * @copyright   Copyright (C) 2008 - 2016 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

JLoader::import('reditem.library');
JLoader::registerPrefix('PlgAesir_FieldRedshop_Product', __DIR__);

use Aesir\Plugin\AbstractFieldPlugin;
use Aesir\Entity\FieldInterface;
use Aesir\Twig\Enviroment;

/**
 * Item related plugin
 *
 * @since  1.0.0
 */
final class PlgAesir_FieldRedshop_Product extends AbstractFieldPlugin
{
	/**
	 * Type for the form type="Redshop_Product" tag
	 *
	 * @var  string
	 */
	protected $formFieldType = 'PlgAesir_FieldRedshop_Product.Redshop_Product';

	/**
	 * Template section
	 *
	 * @var  string
	 */
	protected $templateSection = 'field_redshop_product';

	/**
	 * Get the attributes applicable to an item field.
	 *
	 * @param   FieldInterface  $field  Field being processed.
	 *
	 * @return  array
	 *
	 * @since   1.0.8
	 */
	protected function getFieldXmlAttributes(FieldInterface $field)
	{
		$attributes = parent::getFieldXmlAttributes($field);

		$attributes['default'] = $field->get('default');

		if ($field->getParams()->get('addSelectOption', 'true') === 'true')
		{
			$attributes['addSelectOption'] = 'true';
		}

		return $attributes;
	}

	/**
	 * Decode a field value from database.
	 *
	 * @param   FieldInterface  $field  Field where value comes from.
	 * @param   mixed           $value  Value to decode
	 *
	 * @return  mixed
	 */
	public function onReditemFieldDecodeDatabaseValue(FieldInterface $field, $value)
	{
		if ($field->type !== $this->_name)
		{
			return;
		}

		if (empty($value))
		{
			return;
		}

		$data = array_filter(array_values(json_decode($value, true)));
		$tmp  = array();

		foreach ($data AS $val)
		{
			$tmp[$val] = RedshopHelperProduct::getProductById($val);
		}

		return $tmp;
	}

	/**
	 * Encode a field value to store it in db, etc.
	 *
	 * @param   FieldInterface  $field  Field where value comes from.
	 * @param   mixed           $value  Value to encode
	 *
	 * @return  mixed
	 */
	public function onReditemFieldEncodeDatabaseValue(FieldInterface $field, $value)
	{
		if ($field->type !== $this->_name)
		{
			return;
		}

		$value = array_values(array_filter((array) $value, 'strlen'));

		return empty($value) ? '' : json_encode($value);
	}

	/**
	 * Event run after Aesir Twig Environment loaded
	 *
	 * @param   Enviroment                 $environment  Aesir Twig environment
	 * @param   Twig_LoaderInterface|null  $loader       Twig loader interface
	 * @param   array                      $options      Options list.
	 *
	 * @return  void
	 */
	public function onAesirAfterTwigLoad(Enviroment $environment, Twig_LoaderInterface $loader = null, $options = array())
	{
		// Require our Composer libraries
		if (!$this->loadLibrary())
		{
			return;
		}

		$environment->addExtension(new Redshop\Twig\Product);
	}

	/**
	 * Method for load library
	 *
	 * @return  boolean
	 */
	protected function loadLibrary()
	{
		if (class_exists('Redshop\\Twig\\Product'))
		{
			return true;
		}

		// Require our Composer libraries
		$composerAutoload = __DIR__ . '/library/vendor/autoload.php';

		if (!JFile::exists($composerAutoload))
		{
			return false;
		}

		$loader = require_once $composerAutoload;

		if (is_callable(array($loader, 'loadClass')))
		{
			\Doctrine\Common\Annotations\AnnotationRegistry::registerLoader(array($loader, 'loadClass'));
		}

		return true;
	}
}
