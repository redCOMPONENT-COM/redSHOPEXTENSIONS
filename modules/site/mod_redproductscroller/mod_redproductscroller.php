<?php
/**
 * @package     RedSHOP.Frontend
 * @subpackage  mod_redshop_redproductscroller
 *
 * @copyright   Copyright (C) 2008 - 2015 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

JLoader::import('redshop.library');

$document = JFactory::getDocument();
JHTML::script('com_redshop/redbox.js', false, true);
JHtml::script('com_redshop/attribute.js', false, true);
JHtml::script('com_redshop/common.js', false, true);
/**
 * This class sets all Parameters.
 * Must first call the MOS function, something like:
 * $params = mosParseParams( $module->params );
 * and send the $params variable to this class (productScroller)
 *
 * @param $params the results from mosParseParams( $module->params );
 *
 * @example $scroller = new productScroller($params);
 */

class redproductScroller
{
    /**
     * @var $NumberOfProducts
     */
    var $NumberOfProducts = 5;

    /**
     * @var $featuredProducts
     */
    var $featuredProducts = false;

    /**
     * // scroll, alternate, slide
     * @var $ScrollBehavior
     */
    var $ScrollBehavior = 'scroll';

    /**
     * @var $PS_DIRECTION
     */
    var $ScrollDirection = 'up';

    /**
     * @var $ScrollHeight
     */
    var $ScrollHeight = '125';

    /**
     * @var $ScrollWidth
     */
    var $ScrollWidth = '150';

    /**
     * @var $ScrollAmount
     */
    var $ScrollAmount = '2';

    /**
     * @var $ScrollDelay
     */
    var $ScrollDelay = '80';

    /**
     * @var $ScrollAlign
     */
    var $ScrollAlign = 'center';

    /**
     * // newest [asc], oldest [desc], random [rand]
     * @var $SortMethod
     */
    var $ScrollSortMethod = 'random';

    /**
     * @var $ScrollTitles
     */
    var $ScrollTitles = 'yes';

    /**
     * @var $ScrollSpaceChar
     */
    var $ScrollSpaceChar = '&nbsp;';

    /**
     * @var $ScrollSpaceCharTimes
     */
    var $ScrollSpaceCharTimes = 5;

    /**
     * @var $ScrollLineChar
     */
    var $ScrollLineChar = '<br />';

    /**
     * @var $ScrollLineCharTimes
     */
    var $ScrollLineCharTimes = 2;

    /**
     * @var $ScrollSection
     */
    var $ScrollSection = 0;

    // CSS override -----------------------

    /**
     * @var $ScrollCSSOverride
     */
    var $ScrollCSSOverride = 'no';

    /**
     * @var $ScrollTextAlign
     */
    var $ScrollTextAlign = 'left';

    /**
     * @var $ScrollTextWeight
     */
    var $ScrollTextWeight = 'normal';

    /**
     * @var $ScrollTextSize
     */
    var $ScrollTextSize = '10';

    /**
     * @var $ScrollTextColor
     */
    var $ScrollTextColor = '#000000';

    /**
     * @var $ScrollBGColor
     */
    var $ScrollBGColor = 'transparent';

    /**
     * @var $ScrollMargin
     */
    var $ScrollMargin = '2';

    var $isShowDiscountPriceLayout = 0;

    var $boxwidth = '100';

    var $params = null;

    /**
     * set mammeters
     */
    function __construct(&$params, $moduleId)
    {
        $this->params = $params;

        // Standard mammeters
        $this->show_product_name = $params->get('show_product_name', "yes");
        $this->show_addtocart    = $params->get('show_addtocart', "yes");
        $this->show_price        = $params->get('show_price', "yes");
        $this->category_id       = JRequest::getInt('cid', 0);

        $this->thumbwidth        = $params->get('thumbwidth', 100);
        $this->thumbheight       = $params->get('thumbheight', 100);

        // Limit by NoP
        $this->NumberOfProducts = $params->get('NumberOfProducts', $this->NumberOfProducts);
        $this->featuredProducts = $params->get('featuredProducts', $this->featuredProducts);

        $this->ScrollSection        = $params->get('ScrollSection', $this->ScrollSection);
        $this->ScrollBehavior       = $params->get('ScrollBehavior', $this->ScrollBehavior);
        $this->ScrollDirection      = $params->get('ScrollDirection', $this->ScrollDirection);
        $this->ScrollHeight         = $params->get('ScrollHeight', $this->ScrollHeight);
        $this->ScrollWidth          = $params->get('ScrollWidth', $this->ScrollWidth);
        $this->ScrollAmount         = $params->get('ScrollAmount', $this->ScrollAmount);
        $this->ScrollDelay          = $params->get('ScrollDelay', $this->ScrollDelay);
        $this->ScrollAlign          = $params->get('ScrollAlign', $this->ScrollAlign);
        $this->ScrollSortMethod     = $params->get('ScrollSortMethod', $this->ScrollSortMethod);
        $this->ScrollTitles         = $params->get('ScrollTitles', $this->ScrollTitles);
        $this->ScrollSpaceChar      = $params->get('ScrollSpaceChar', $this->ScrollSpaceChar);
        $this->ScrollSpaceCharTimes = $params->get('ScrollSpaceCharTimes', $this->ScrollSpaceCharTimes);
        $this->ScrollLineChar       = $params->get('ScrollLineChar', $this->ScrollLineChar);
        $this->ScrollLineCharTimes  = $params->get('ScrollLineCharTimes', $this->ScrollLineCharTimes);

        // Customization mammeters
        $this->ScrollCSSOverride        = $params->get('ScrollCSSOverride', $this->ScrollCSSOverride);
        $this->ScrollTextAlign          = $params->get('ScrollTextAlign', $this->ScrollTextAlign);
        $this->ScrollTextWeight         = $params->get('ScrollTextWeight', $this->ScrollTextWeight);
        $this->ScrollTextSize           = $params->get('ScrollTextSize', $this->ScrollTextSize);
        $this->ScrollTextColor          = $params->get('ScrollTextColor', $this->ScrollTextColor);
        $this->ScrollBGColor            = $params->get('ScrollBGColor', $this->ScrollBGColor);
        $this->ScrollMargin             = $params->get('ScrollMargin', $this->ScrollMargin);
        $this->show_discountpricelayout = $params->get('show_discountpricelayout', ($this->show_discountpricelayout ?? 0));
        $this->boxwidth                 = $params->get('boxwidth', $this->boxwidth);
        $this->module_id                = $moduleId;
    }

    /**
     * Display Product Data
     */
    function displayredScroller(&$rows)
    {
        if ($this->ScrollCSSOverride == 'yes')
        {
            $txt_size = $this->ScrollTextSize . 'px';
            $margin   = $this->ScrollMargin . 'px';
            echo $this->params->get('pretext', "");
            echo " <div style=\"text-align:" . $this->ScrollAlign . ";background-color: " . $this->ScrollBGColor . "; width:" . $this->ScrollWidth . "px;
                   margin-top: $margin; margin-right: $margin; margin-bottom: $margin; margin-left: $margin;\" >
           <marquee behavior=\"" . $this->ScrollBehavior . "\"
                    direction=\"" . $this->ScrollDirection . "\"
                    height=\"" . $this->ScrollHeight . "\"
                    width=\"" . $this->ScrollWidth . "\"
                    scrollamount=\"" . $this->ScrollAmount . "\"
                    scrolldelay=\"" . $this->ScrollDelay . "\"
                    truespeed=\"true\" onmouseover=\"this.stop()\" onmouseout=\"this.start()\"
                    style=\"text-align: " . $this->ScrollTextAlign . "; color: " . $this->ScrollTextColor . "; font-weight: " . $this->ScrollTextWeight . "; font-size: $txt_size;px\" >";
        }
        else
        {
            echo " <div style=\"width:" . $this->ScrollWidth . "px;text-align:" . $this->ScrollAlign . ";\">
           <marquee behavior=\"" . $this->ScrollBehavior . "\"
                    direction=\"" . $this->ScrollDirection . "\"
                    height=\"" . $this->ScrollHeight . "\"
                    width=\"" . $this->ScrollWidth . "\"
                    scrollamount=\"" . $this->ScrollAmount . "\"
                    scrolldelay=\"" . $this->ScrollDelay . "\"
                    truespeed=\"true\" onmouseover=\"this.stop()\" onmouseout=\"this.start()\">";
        }

        if (($this->ScrollDirection == 'left') || ($this->ScrollDirection == 'right'))
        {
            echo '<table><tr>';
        }

        $i = 0;

        foreach ($rows as $row)
        {
            if (($this->ScrollDirection == 'left') || ($this->ScrollDirection == 'right'))
            {
                echo '<td style="vertical-align:top;padding: 2px 5px 2px 5px;"><table width="' . $this->boxwidth . '">';
            }

            // Display Product
            $productdata = $this->ShowProducts($row, $i);
            echo $productdata;

            if (($this->ScrollDirection == 'left') || ($this->ScrollDirection == 'right'))
            {
                echo '</table></td>';

            }
            else
            {
                for ($i = 0; $i < $this->ScrollLineCharTimes; $i++)
                {
                    echo $this->ScrollLineChar;
                }
            }

            $i++;
        }

        if (($this->ScrollDirection == 'left') || ($this->ScrollDirection == 'right'))
        {
            echo '</tr></table>';
        }

        echo "    </marquee>
        </div>";
    }


    /**
     * Helper DB function
     */
    public function getredProductSKU($limit = 0, $how = null, $categoryId = 0)
    {
        $category_array = new product_category;

        $hierachy = $category_array->getCategoryListArray($categoryId, $categoryId);

        $cid = [];

        $count = count((array) $hierachy);
        for ($i = 0, $in = $count; $i < $in; $i++)
        {
            $cid[] = $hierachy[$i]->category_id;
        }

        $cid      = array_merge((array) $categoryId, $cid);
        $database = JFactory::getDbo();

        if ($limit > 0)
        {
            $limit = "LIMIT $limit";
        }
        else
        {
            $limit = "";
        }

        $query = "SELECT DISTINCT p.*  FROM #__redshop_product AS p";

        $query .= "\nJOIN #__redshop_product_category_xref as pc ON p.product_id=pc.product_id";

        if (count($cid))
        {
            $cids = 'pc.category_id=' . implode(' OR pc.category_id=', $cid);
            $query .= " AND (" . $cids . ")";
        }

        $query .= "\nJOIN #__redshop_category as c ON pc.category_id=c.id";

        $query .= "\n WHERE p.published = '1' AND c.published = '1' AND product_parent_id=0 ";


        switch ($how)
        {
            case 'random':
                $query .= "\n ORDER BY RAND() $limit";
                break;
            case 'newest':
                $query .= "\n ORDER BY p.publish_date DESC $limit";
                break;
            case 'oldest':
                $query .= "\n ORDER BY p.publish_date ASC $limit";
                break;
            default:
                $query .= "\n ORDER BY p.publish_date DESC $limit";
                break;
        }

        $database->setQuery($query);
        $rows = $database->loadObjectList();

        return $rows;
    }

    public function ShowProducts($row)
    {
        $categoryId   = $row->cat_in_sefurl;
        $itemData      = \RedshopHelperProduct::getMenuInformation(0, 0, '', 'product&pid=' . $row->product_id);

        if (isset($itemData->id))
        {
            $itemId = $itemData->id;
        }
        else
        {
            $itemId = \RedshopHelperRouter::getItemId($row->product_id);
        }

        $data_add   = '';
        $thumbImage = "";

        $productName = $row->product_name;

        $link = JRoute::_('index.php?option=com_redshop&view=product&pid=' . $row->product_id . '&cid=' . $categoryId . '&Itemid=' . $itemId);

        $productName = $row->product_name;

        if ($this->boxwidth > 0)
        {
            $pwidth = $this->boxwidth / 10;
            $productName  = wordwrap($productName, $pwidth, "<br>\n", true);
        }

        if ($row->product_full_image)
        {
            $thumbUrl = RedShopHelperImages::getImagePath(
                            $row->product_full_image,
                            '',
                            'thumb',
                            'product',
                            $this->thumbwidth,
                            $this->thumbheight,
                            \Redshop::getConfig()->get('USE_IMAGE_SIZE_SWAPPING')
                        );
            $thumbImage = "<div style='width:" . $this->thumbwidth . "px;height:" . $this->thumbheight . "px;'>
                                <a href='" . $link . "' title=''>
                                    <img src='" . $thumbUrl . "'>
                                </a>
                            </div>";

            $data_add .= $thumbImage;
        }

        if ($this->show_product_name == 'yes')
        {
            $productName = "<tr><td style='text-align:" . $this->ScrollTextAlign . ";font-weight:" . $this->ScrollTextWeight . ";font-size:" . $this->ScrollTextSize . "px;'><a href='" . $link . "' >" . $productName . "</a></td></tr>";
            $data_add .= $productName;
        }

        if (\Redshop::getConfig()->get('SHOW_PRICE') == 1 && !$row->not_for_sale && !\Redshop::getConfig()->get('USE_AS_CATALOG') && (!\Redshop::getConfig()->get('DEFAULT_QUOTATION_MODE') || (\Redshop::getConfig()->get('DEFAULT_QUOTATION_MODE') && \Redshop::getConfig()->get('SHOW_QUOTATION_PRICE'))))
        {
            if ($this->show_price == 'yes')
            {
                $productPrice = \Redshop\Product\Price::getPrice($row->product_id);

                $productArr             = \RedshopHelperProductPrice::getNetPrice($row->product_id);
                $productPrice_discount = $productArr['productPrice'] + $productArr['productVat'];

                if (!$productPrice)
                {
                    $productPrice_dis = \RedshopHelperProductPrice::priceReplacement($productPrice);
                }
                else
                {
                    $productPrice_dis = \RedshopHelperProductPrice::formattedPrice($productPrice);
                }

                $display_text = "<tr><td class='mod_redproducts_price' style='text-align:" . $this->ScrollTextAlign . ";font-weight:" . $this->ScrollTextWeight . ";font-size:" . $this->ScrollTextSize . "px;'>" . $productPrice_dis . "</td></tr>";

                if ($row->product_on_sale && $productPrice_discount > 0)
                {
                    if ($productPrice > $productPrice_discount)
                    {
                        $display_text = "";
                        $s_price      = $productPrice - $productPrice_discount;

                        if ($this->show_discountpricelayout)
                        {
                            $data_add .= "<tr><td id='mod_redoldprice' class='mod_redoldprice' style='text-align:" . $this->ScrollTextAlign . ";font-weight:" . $this->ScrollTextWeight . ";font-size:" . $this->ScrollTextSize . "px;'><span style='text-decoration:line-through;'>" . \RedshopHelperProductPrice::formattedPrice($productPrice) . "</span></td></tr>";
                            $productPrice = $productPrice_discount;
                            $data_add .= "<tr><td id='mod_redmainprice' class='mod_redmainprice' style='text-align:" . $this->ScrollTextAlign . ";font-weight:" . $this->ScrollTextWeight . ";font-size:" . $this->ScrollTextSize . "px;'>" . \RedshopHelperProductPrice::formattedPrice($productPrice_discount) . "</td></tr>";
                            $data_add .= "<tr><td id='mod_redsavedprice' class='mod_redsavedprice' style='text-align:" . $this->ScrollTextAlign . ";font-weight:" . $this->ScrollTextWeight . ";font-size:" . $this->ScrollTextSize . "px;'>" . JText::_('COM_REDSHOP_PRODCUT_PRICE_YOU_SAVED') . ' ' . \RedshopHelperProductPrice::formattedPrice($s_price) . "</td></tr>";
                        }
                        else
                        {
                            $productPrice = $productPrice_discount;
                            $data_add .= "<tr><td class='mod_redproducts_price' style='text-align:" . $this->ScrollTextAlign . ";font-weight:" . $this->ScrollTextWeight . ";font-size:" . $this->ScrollTextSize . "px;'>" . \RedshopHelperProductPrice::formattedPrice($productPrice) . "</td></tr>";
                        }
                    }
                }

                $data_add .= $display_text;
            }
        }

        // Start cart
        if ($this->show_addtocart == 'yes')
        {
            $addToCartTemplates = \RedshopHelperTemplate::getTemplate('add_to_cart');
            $templateName = $addToCartTemplates[0]->name ?? 'add_to_cart1';
            $addToCartTemplate = '{form_addtocart:' . $templateName . '}';

            $addToCartData = \Redshop\Cart\Render::replace($row->product_id, $categoryId, 0, 0, $addToCartTemplate, false, [], 0, 0, 0, $this->module_id);
            $data_add .= "<tr><td style='text-align:" . $this->ScrollTextAlign . ";font-weight:" . $this->ScrollTextWeight . ";font-size:" . $this->ScrollTextSize . "px;'>" . $addToCartData . "</td></tr>";
        }

        return $data_add;
    }
}

// End class productScroller
$moduleId = "mod_" . $module->id;

// Start of Product Scroller Script
$scroller = new redproductScroller($params, $module->id);

/**
 * Load Products
 **/
$rows = $scroller->getredProductSKU($scroller->NumberOfProducts, $scroller->ScrollSortMethod, $scroller->category_id, $scroller->featuredProducts);

/**
 * Display Product Scroller
 **/
$scroller->displayredScroller($rows);
