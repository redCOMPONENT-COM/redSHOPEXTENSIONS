<?php
/**
 * @package    LOGman
 * @copyright  Copyright (C) 2008 - 2015 redCOMPONENT.com. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

/**
 * redSHOP LOGman plugin.
 *
 * @package  Joomlatools\Plugin\LOGman
 *
 * @since    1.0.0
 */
class PlgLogmanRedshop extends ComLogmanPluginJoomla
{
	/**
	 * Trigger after saved configuration
	 *
	 * @param   JRegistry  $config  Configuration
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function onAfterAdminSaveConfiguration($config)
	{
		$name = JText::_('COM_REDSHOP_REDSHOP');
		$type = 'configuration';
		$id   = $this->_getUniqueId($config);

		$this->setLog($type, $name, '', 'changed', $id);
	}

	/**
	 * Trigger after saved product
	 *
	 * @param   array  $data
	 *
	 * @param   array  $product_id  product id
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function onAfterProductFullSave($data, $product_id)
	{
		$type   = 'product';
		$result = 'changed';

		if ($product_id == 0)
		{
			$result = 'addad';
		}

		$url = 'option=com_redshop&view=product_detail&task=edit&cid[]=' . $data->product_id;
		$this->setLog($type, $data->product_name, $data, $result, $data->product_id, $url);
	}

	/**
	 * Trigger after saved category
	 *
	 * @param   array  $data
	 *
	 * @param   array  $catID  category id
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function onAfterCategorySave($data, $catID)
	{
		$type   = 'category';
		$result = 'changed';

		if ($catID == 0)
		{
			$result = 'addad';
		}

		$url = 'option=com_redshop&view=category&layout=edit&id=' . $data->id;

		$this->setLog($type, $data->name, $data, $result, $data->id, $url);
	}

	/**
	 * Get package
	 *
	 * @return  string
	 *
	 * @since  1.0.0
	 */
	private function _getPackage()
	{
		return 'redshop';
	}

	/**
	 * Get unique ID
	 *
	 * @param   mixed  $args  Args
	 *
	 * @return  string
	 *
	 * @since   1.0.0
	 */
	private function _getUniqueId ($args)
	{
		return md5(serialize($args) . serialize(JFactory::getUser()));
	}

	/**
	 * Get set log
	 *
	 * @param   string  $type
	 * @param   string  $name
	 * @param   array   $data
	 * @param   string  $resultt
	 * @param   string  $uid
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	private function setLog($type, $name, $data, $result, $id, $url = '')
	{
		$user = JFactory::getUser();
		$this->getObject('com://admin/logman.controller.activity')
			->log(
				array(
					'object' => array(
						'package' => $this->_getPackage(),
						'type'    => $type,
						'id'      => $id,
						'name'    => $name,
						'url' => array(
							'admin' =>  $url,
						),
						'metadata'=> array('data' => $data),

					),
					'verb'   => 'save',
					'actor'  => $user->id,
					'result' => $result
				)
			);
	}
}
