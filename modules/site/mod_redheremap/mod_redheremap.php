<?php
/**
 * @package     redWEB.Frontend
 * @subpackage  mod_redheremap
 *
 * @copyright   Copyright (C) 2008 - 2015 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

$moduleclass_sfx     = htmlspecialchars($params->get('moduleclass_sfx'));
$appId = $params->get('api', '');
$appCode = $params->get('code', '');
$zoom = $params->get('zoom', '14');
$lat = $params->get('lat', '');
$lng = $params->get('lng', '');
$height = $params->get('height', '300');
$width = $params->get('width', '300');
$tiletype = $params->get('tiletype', 'normal.day');
$scheme = $params->get('scheme', 'maptile');
$disablemousewheel = $params->get('disablemousewheel', '0');
$info = $params->get('info', '');
$info = trim(preg_replace('/\s+/', ' ', $info));

$icon = $params->get('icon', '');

if (!empty($icon))
{
	$icon = JUri::root() . $icon;
}

$document = JFactory::getDocument();
$document->addScript('https://js.api.here.com/v3/3.0/mapsjs-core.js');
$document->addScript('https://js.api.here.com/v3/3.0/mapsjs-service.js');
$document->addScript('https://js.api.here.com/v3/3.0/mapsjs-ui.js');
$document->addScript('https://js.api.here.com/v3/3.0/mapsjs-mapevents.js');
$document->addScript(JUri::root() . 'modules/mod_redheremap/assets/redheremap.js');
$document->addStyleSheet('https://js.api.here.com/v3/3.0/mapsjs-ui.css');

require JModuleHelper::getLayoutPath('mod_redheremap', $params->get('layout', 'default'));
