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

<div class="fb-share-button"
     data-href="<?php echo $params->get('facebook_page_url', 'https://developers.facebook.com/docs/plugins/comments');?>"
     data-layout="button_count"
     data-size="small"
     data-mobile-iframe="true">
	<a target="_blank"
	   href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $params->get('facebook_page_url', 'https://developers.facebook.com/docs/plugins/comments');?>%2F&amp;src=sdkpreparse" class="fb-xfbml-parse-ignore">Chia sẻ</a>
</div>