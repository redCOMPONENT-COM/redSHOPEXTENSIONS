<?php
/**
 * @package     Redshop.Plugin
 * @subpackage  Twig.Extension
 *
 * @copyright   Copyright (C) 2012 - 2017 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later, see LICENSE.
 */

namespace Redshop\Twig;

use function is_object;
use Redshop\Twig\Entity\Product as TwigProduct;

defined('_JEXEC') or die;

/**
 * Type Twig extension.
 *
 * @since  1.1.0
 */
class Product extends \Twig_Extension
{
	/**
	 * Inject functions.
	 *
	 * @return  array
	 */
	public function getFunctions()
	{
		return array(
			new \Twig_SimpleFunction('redshop_product', array($this, 'getProduct'))
		);
	}

	/**
	 * Get an product.
	 *
	 * @param   mixed  $product  User identifier
	 *
	 * @return  TwigProduct
	 */
	public function getProduct($product)
	{
		if (is_object($product))
		{
			$product = \RedshopEntityProduct::getInstance($product->product_id)->bind($product);

			return new TwigProduct($product);
		}

		return new TwigProduct(\RedshopEntityProduct::getInstance((int) $product));
	}

	/**
	 * Get the name of this extension.
	 *
	 * @return  string
	 */
	public function getName()
	{
		return 'redshop_product';
	}
}
