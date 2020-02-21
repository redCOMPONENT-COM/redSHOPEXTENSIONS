<?php
/**
 * @package     RedSHOP.Frontend
 * @subpackage  mod_redshop_currencies
 *
 * @copyright   Copyright (C) 2008 - 2017 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;
JLoader::register('Mod\RedshopCurrenciesHelper', __DIR__ . '/helper.php');

$session        = JFactory::getSession();
$textBefore     = $params->get('text_before', '');
$currencies     = Mod\RedshopCurrenciesHelper::getList($params);
$activeCurrency = Mod\RedshopCurrenciesHelper::getActive();

require JModuleHelper::getLayoutPath('mod_redshop_currencies', $params->get('layout', 'default'));
