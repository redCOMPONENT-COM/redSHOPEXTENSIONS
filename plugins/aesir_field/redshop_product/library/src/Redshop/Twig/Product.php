<?php
/**
 * @package     Aesir.Library
 * @subpackage  Twig.Extension
 *
 * @copyright   Copyright (C) 2012 - 2016 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later, see LICENSE.
 */

namespace Redshop\Twig;

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
	 * @param   integer  $id  User identifier
	 *
	 * @return  \RedshopProduct
	 */
	public function getProduct($id)
	{
		return \RedshopProduct::getInstance($id);
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
