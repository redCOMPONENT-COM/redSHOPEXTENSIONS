<?php
/**
 * @package     RedSHOP.Frontend
 * @subpackage  mod_redshop_cart
 *
 * @copyright   Copyright (C) 2008 - 2017 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

$facebookInit = new Joomla\CMS\Layout\FileLayout('facebook.init', JPATH_ROOT . '/modules/mod_redweb_facebook_plugins/layouts');
echo $facebookInit->render(array('params' => $params, 'moduleId' => $module->id));

$pluginType = $params->get('facebook_plugin', 'page');
$plugin = new Joomla\CMS\Layout\FileLayout('facebook.' . $pluginType, JPATH_ROOT . '/modules/mod_redweb_facebook_plugins/layouts');
?>

<div class="redweb-facebook-plugins">
	<div id="facebook-plugin-<?php echo $pluginType . '-' . $module->id; ?>"
	     class="facebook-plugin-<?php echo $pluginType; ?>"
	>
		<?php echo $plugin->render(array('params' => $params)); ?>
	</div>
</div>