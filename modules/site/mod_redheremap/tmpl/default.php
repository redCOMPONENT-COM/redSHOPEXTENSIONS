<?php
/**
 * @package     Xxx.Frontend
 * @subpackage  mod_xxx_vvv
 *
 * @copyright   Copyright (C) 2012 - 2018 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;
?>
<div class="mod_redheremap<?php echo $moduleclass_sfx ?>" style="width: <?php echo $width?>; height: <?php echo $height?>" id="mod_redheremap<?php echo $module->id ?>">
</div>

<script type="text/javascript">
	var mod_redheremap<?php echo $module->id ?> = new redHEREMAP('mod_redheremap<?php echo $module->id ?>', {
		appId: '<?php echo $appId ?>',
		appCode: '<?php echo $appCode ?>',
		site: 1,
		zoomLevel: '<?php echo$zoom ?>',
		lat: '<?php echo $lat ?>',
		lng: '<?php echo $lng ?>',
		tiletype: '<?php echo $tiletype ?>',
		scheme: '<?php echo $scheme ?>',
		disablemousewheel: '<?php echo $disablemousewheel ?>',
		info: '<?php echo $info ?>',
		icon: '<?php echo $icon ?>'
	});
</script>