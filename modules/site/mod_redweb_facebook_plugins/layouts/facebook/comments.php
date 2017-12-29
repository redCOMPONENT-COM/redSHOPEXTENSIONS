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

<div class="fb-comments"
     data-href="<?php echo $params->get('facebook_comments_url', 'https://developers.facebook.com/docs/plugins/comments');?>"
     data-numposts="<?php echo $params->get('facebook_numposts', 5);?>"
     data-colorscheme="<?php echo $params->get('facebook_colorscheme', 'light');?>"
     data-mobile="<?php echo $params->get('facebook_mobile', true);?>"
     data-order-by="<?php echo $params->get('facebook_comments_order_by', 'social');?>"
     data-width="<?php echo $params->get('facebook_width', '550');?>"
>
</div>