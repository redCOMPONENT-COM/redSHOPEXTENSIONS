<?php
/**
 * @package     RedSHOP.Frontend
 * @subpackage  mod_redshop_cart
 *
 * @copyright   Copyright (C) 2008 - 2017 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;
extract($displayData)
?>

<div class="fb-page"
     data-href="<?php echo $params->get('facebook_page_url', 'https://www.facebook.com/facebook/'); ?>"
     data-width="<?php echo $params->get('facebook_width', '340'); ?>"
     data-height="<?php echo $params->get('facebook_height', '500'); ?>"
     data-tabs="<?php echo $params->get('facebook_page_tabs', 'timeline'); ?>"
     data-hide-cover="<?php echo $params->get('facebook_page_hide_cover', 'false'); ?>"
     data-show-facepile="<?php echo $params->get('facebook_page_show_facepile', 'true'); ?>"
     data-hide-cta="<?php echo $params->get('facebook_page_hide_cta', 'false'); ?>"
     data-small-header="<?php echo $params->get('facebook_page_small_header', 'false'); ?>"
     data-adapt-container-width="<?php echo $params->get('facebook_page_adap_container_width', 'true'); ?>"
>
</div>