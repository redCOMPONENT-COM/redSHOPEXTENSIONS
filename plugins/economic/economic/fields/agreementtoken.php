<?php
/**
 * @package     RedSHOP.Backend
 * @subpackage  Element
 *
 * @copyright   Copyright (C) 2008 - 2015 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

// Load redSHOP Library
JLoader::import('redshop.library');

/**
 * Generate "Agreement Grant Token" field
 *
 * @package        RedSHOP.Backend
 * @subpackage     Element
 * @since          1.5
 */
class JFormFieldAgreementtoken extends JFormFieldText
{
	/**
	 * @var  string
	 */
	protected $type = 'agreementtoken';

	/**
	 * @var string
	 */
	public $appPublicToken = 'wNh27S22igfDbuuxBJEhKPVCLqeTUAUgLiJkzI2KPL41';

	/**
	 * Method to get the field input mark up.
	 *
	 * @return  string  The field input mark up.
	 *
	 * @since   2.0.0
	 */
	protected function getInput()
	{
		$plugin = JPluginHelper::getPlugin('economic', 'economic');

		if (empty($plugin))
		{
			return '<div class="alert alert-error"><i class="icon icon-warning"></i>'
				. JText::_('PLG_ECONOMIC_ECONOMIC_ENABLE_PLUGIN') . '</div>';
		}

		if (empty($this->value))
		{
			return '<div class="alert alert-warning"><i class="icon icon-warning"></i>'
				. JText::_('PLG_ECONOMIC_ECONOMIC_MISSING_TOKEN') . '</div>'
				. '<a class="btn btn-primary" href="' . $this->getGenerateTokenLink() . '" target="_blank" rel="noopener">'
				. JText::_('PLG_ECONOMIC_ECONOMIC_GRANT_ACCESS') . '</a>';
		}

		return '<div class="alert alert-success"><i class="icon icon-save"></i>' . JText::_('PLG_ECONOMIC_ECONOMIC_HAS_TOKEN') . '</div>'
			. '<div><a class="btn btn-primary" href="' . $this->getGenerateTokenLink() . '" target="_blank" rel="noopener">'
			. JText::_('PLG_ECONOMIC_ECONOMIC_RENEW_ACCESS') . '</a></div>'
			. '<input type="hidden" name="' . $this->name . '" value="' . $this->value . '" />';
	}

	/**
	 * Method for return generate Token Link
	 *
	 * @return string
	 */
	protected function getGenerateTokenLink()
	{
		$link = JUri::root() . 'index.php?option=com_ajax&plugin=economicStoreAgreementToken&group=economic&format=raw';
		$link = 'https://secure.e-conomic.com/secure/api1/requestaccess.aspx?appPublicToken='
			. $this->appPublicToken . '&redirectUrl=' . urlencode($link);

		return $link;
	}
}
