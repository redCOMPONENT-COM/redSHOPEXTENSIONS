<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.cache
 *
 * @copyright   Copyright (C) 2005 - 2020 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Uri\Uri;

/**
 * Joomla! Page Cache Plugin.
 *
 * @since  1.5
 */
class PlgSystemRedshop_Schema_Structured_Data extends JPlugin
{
    /**
     * After Display Product method
     *
     * Method is called by the product view
     *
     * @param string  &$template The Product Template Data
     * @param object $params The product params
     * @param object $data The product object Data
     *
     * @return  void
     */
    public function onAfterDisplayProduct(&$template, $params, $data)
    {
        $app              = \JFactory::getApplication();

        if (!$app->isClient('site')) {
            return;
        }

        $url              = Uri::getInstance()->toString();
        $option           = $app->input->get('option', '');
        $view             = $app->input->get('view', '');
        $vatPrice         = RedshopHelperProduct::getProductTax($data->product_id, $data->product_price);
        $currencySymbol   = Redshop::getConfig()->get('REDCURRENCY_SYMBOL');
        $discountPrice    = $data->discount_price + $vatPrice;
        $normalPrice      = $data->product_price + $vatPrice;
        $stockStatus      = $this->defineProductAvailability($data);

        $productDesc      = !empty($data->product_s_desc) ? $data->product_s_desc : $data->product_desc;

        $productDescClean = str_replace(array('"', "'"), "", strip_tags($productDesc));
        $productNumber    = strip_tags($data->product_number);
        $productImage     = (REDSHOP_FRONT_IMAGES_ABSPATH . 'product/') . $data->product_full_image;
        $price            = $normalPrice;
        $priceValidUntil  = '';
        $today            = time();
        
        if ($data->discount_enddate >= $today) {
            $price           = $discountPrice;
            $discountEnddate = date('Y-m-d',$data->discount_enddate);
            $priceValidUntil = '"priceValidUntil": "' . $discountEnddate . '",';
        }

        $productBrand = $data->manufacturer_name ?: '';

        if ($option == 'com_redshop' && $view == 'product') {
            $js = '
                {
                    "@context": "schema.org",
                    "@type": "Product",
                    "name": "' . $data->product_name . '",
                    "sku": "' . $productNumber . '",
                    "mpn": "' . $productNumber . '",
                    "image": "' . $productImage . '",
                    "description": "' . $productDescClean . '",
                    "brand": {
                        "@type": "Brand",
                        "name": "' . $productBrand . '"
                    },
                    "offers": {
                        "@type": "Offer",
                        "priceCurrency": "' . $currencySymbol . '",
                        "price": "' . $price . '",
                        "availability": "' . $stockStatus . '",
                        "itemCondition": "http://schema.org/NewCondition",
                        '.$priceValidUntil.'
                        "url": "' . $url . '"
                    }
                }
            ';

            JFactory::getDocument()->addScriptDeclaration($js, 'application/ld+json');
        }
    }
    
    /**
     * Define Product Availability
     *
     * @param object $product The product object Product
     *
     * @return string
     */
    public function defineProductAvailability($product)
    {
        if ($product->expired) {
            return 'https://schema.org/Discontinued';
        }

        if (!Redshop::getConfig()->get('USE_STOCKROOM')) {
            return 'https://schema.org/InStock';
        }

        $isInStock = RedshopHelperStockroom::isStockExists($product->product_id);
    
        if (Redshop::getConfig()->get('ALLOW_PRE_ORDER')) {
            return !$isInStock ? 'https://schema.org/PreOrder' : 'https://schema.org/InStock';
        } else {
            return $isInStock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock';
        }
    }
}
