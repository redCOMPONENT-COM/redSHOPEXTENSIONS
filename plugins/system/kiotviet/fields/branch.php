<?php
/**
 * @package     RedSHOP.Frontend
 * @subpackage  mod_redshop_categories
 *
 * @copyright   Copyright (C) 2008 - 2015 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

// No direct access
defined('_JEXEC') or die;

JLoader::import('redshop.library');
JFormHelper::loadFieldClass('list');

/**
 * Class JFormFieldRedshopCategoryRemove
 *
 * @since  1.5
 */
class JFormFieldBranch extends JFormFieldList
{
	/**
	 * @access private
	 */
	protected $name = 'branch';

	/**
	 * Method to get the field input markup for a generic list.
	 *
	 * @return  string  The field input markup.
	 */
	public function getInput()
	{
		$plugin = JPluginHelper::getPlugin('system', 'kiotviet');

		if (empty($plugin)) {
			return false;
		}

		$kiotviet = new Kiotviet\ConnectApi();
		$params   = json_decode($plugin->params);

		if (empty($params->product_number)) {
			return false;
		}

		$config = array(
			'client_id' => $params->client_id,
			'secret_id' => $params->secret_id
		);

		$kiotviet->setConfig($config);

		$accessToken = $kiotviet->getAccessToken();
		$headers     = $kiotviet->getHeaders($accessToken, $params->retailer);

		$client = $kiotviet->getClient();

		try {
			$response = $client->request('GET', 'products/code/' . $params->product_number, $headers);
		} catch (Exception $e) {
			return false;
		}

		$inventories = json_decode($response->getBody()->getContents())->inventories;

		$options   = array();
		$branchIds = array();

		if (!$this->element['multiple']) {
			$options[] = JHTML::_('select.option', '', JText::_('PLG_SYSTEM_KIOTVIET_SELECT_BRANCH'), 'value', 'text');
		}

		if (count($inventories) > 0) {
			foreach ($inventories as $item) {
				$option      = JHTML::_('select.option', $item->branchId, $item->branchName);
				$options[]   = $option;
				$branchIds[] = $item->branchName . '#' . $item->branchId;
			}
		}

		$options = array_merge(parent::getOptions(), $options);

		$attr = '';

		// Initialize some field attributes.
		$attr .= $this->element['class'] ? ' class="' . (string)$this->element['class'] . '"' : '';
		$attr .= $this->element['size'] ? ' size="' . (int)$this->element['size'] . '"' : '';
		$attr .= $this->element['multiple'] ? ' multiple' : '';

		// Initialize JavaScript field attributes.
		$attr .= $this->element['onchange'] ? ' onchange="' . (string)$this->element['onchange'] . '"' : '';

		return JHTML::_(
			'select.genericlist',
			$options,
			$this->name,
			trim($attr),
			'value',
			'text',
			$this->value,
			$this->id
		);
	}
}