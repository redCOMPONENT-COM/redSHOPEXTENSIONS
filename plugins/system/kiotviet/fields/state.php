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
class JFormFieldState extends JFormFieldList
{
	/**
	 * @access private
	 */
	protected $name = 'state';

	/**
	 * Method to get the field input markup for a generic list.
	 *
	 * @return  string  The field input markup.
	 */
	public function getInput()
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select($db->qn(array('s.state_2_code', 's.state_name')))
			->from($db->qn('#__redshop_country', 'c'))
			->leftJoin($db->qn('#__redshop_state', 's') . 'ON s.country_id = c.id')
			->where($db->qn('c.country_name') . ' = ' . $db->q('Viet Nam'));

		$items = $db->setQuery($query)->loadObjectList();

		$options = array();

		if (!$this->element['multiple'])
		{
			$options[] = JHTML::_('select.option', '', JText::_('PLG_SYSTEM_KIOTVIET_SELECT_BRANCH'), 'value', 'text');
		}

		if (count($items) > 0)
		{
			foreach ($items as $item)
			{
				$option = JHTML::_('select.option', $item->state_2_code, $item->state_name);
				$options[] = $option;
			}
		}


		$options = array_merge(parent::getOptions(), $options);

		$attr = '';

		// Initialize some field attributes.
		$attr .= $this->element['class'] ? ' class="' . (string) $this->element['class'] . '"' : '';
		$attr .= $this->element['size'] ? ' size="' . (int) $this->element['size'] . '"' : '';
		$attr .= $this->element['multiple'] ? ' multiple' : '';

		// Initialize JavaScript field attributes.
		$attr .= $this->element['onchange'] ? ' onchange="' . (string) $this->element['onchange'] . '"' : '';

		return JHTML::_('select.genericlist', $options, $this->name, trim($attr), 'value', 'text', $this->value, $this->id);
	}
}