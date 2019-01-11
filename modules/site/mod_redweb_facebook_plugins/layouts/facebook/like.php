<?php
/**
 * @package     RedSHOP.Frontend
 * @subpackage  mod_redshop_cart
 *
 * @copyright   Copyright (C) 2008 - 2017 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;
extract($displayData);
?>

<div class="fb-like"
     data-href="<?php echo $params->get('facebook_page_url', 'https://developers.facebook.com/docs/plugins/comments');?>"
     data-layout="button_count"
     data-action="like"
     data-show-faces="true"
    data-share="<?php echo $params->get('facebook_like_share');?>">
</div>