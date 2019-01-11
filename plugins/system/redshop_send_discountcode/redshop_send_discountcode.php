<?php
/**
 * @package     RedSHOP.Plugin
 * @subpackage  System.RedSHOP
 *
 * @copyright   Copyright (C) 2008 - 2017 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

/**
 * PlgSystemRedSHOP class.
 *
 * @extends JPlugin
 * @since   1.0.0
 */
class PlgSystemRedSHOP_Send_Discountcode extends JPlugin
{
	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 */
	protected $autoloadLanguage = true;

	/**
	 * Add Send Discount Button for Menu Bar on voucher and coupon view
	 *
	 * @return  boolean
	 */
	public function onAfterRoute()
	{
		$app = JFactory::getApplication();

		if (!$app->isAdmin())
		{
			return true;
		}

		$input = $app->input;

		if ($input->get('option', '') != 'com_redshop')
		{
			return true;
		}

		if ($input->get('view', '') != 'voucher' && $input->get('view', '') != 'coupon')
		{
			return true;
		}

		JToolBarHelper::modal('popupSendDiscountCode', 'icon-send', JText::_('PLG_SYSTEM_REDSHOP_SEND_EMAIL_BUTTON'));
	}

	/**
	 * This function for send discount code by email - Call from AJAX interface of Joomla
	 *
	 * @return  bool  Send mail
	 */
	public function onAjaxRedShop_SendDiscountCodeByMail()
	{
		$config = JFactory::getConfig();

		if (!$config->get('mailonline'))
		{
			return false;
		}

		$mailBcc  = null;
		$mailInfo = RedshopHelperMail::getMailTemplate(0, "send_discount_code_mail", '');

		if (empty($mailInfo))
		{
			return false;
		}

		$mailBody = $mailInfo[0]->mail_body;
		$subject  = $mailInfo[0]->mail_subject;

		$from     = $config->get('mailfrom');
		$fromName = $config->get('fromname');

		if (trim($mailInfo[0]->mail_bcc) != "")
		{
			$mailBcc = explode(",", $mailInfo[0]->mail_bcc);
		}

		$mailBody = RedshopHelperMail::imgInMail($mailBody);

		$app   = JFactory::getApplication();
		$input = $app->input;

		$email      = $input->getString('email', '');
		$view       = $input->getString('view', '');
		$discountId = $input->getString('discountId', '');

		// Get Code
		$discountDetail = $this->getDiscountCode($discountId, $view);

		if (empty($discountDetail))
		{
			return true;
		}

		$value = RedshopHelperProductPrice::formattedPrice($discountDetail->value);

		if ($discountDetail->type == '1' || $discountDetail->type == 'Percentage')
		{
			$value = number_format($discountDetail->value) . "%";
		}

		$mailBody = str_replace('{discount_code}', $discountDetail->code, $mailBody);
		$mailBody = str_replace('{discount_value}', $value, $mailBody);

		if (!RedshopHelperMail::sendEmail($from, $fromName, $email, $subject, $mailBody, true, null, $mailBcc))
		{
			JError::raiseWarning(21, JText::_('COM_REDSHOP_ERROR_SENDING_CONFIRMATION_MAIL'));

			return false;
		}

		return true;
	}

	/**
	 * This function add more Mail Sections on redSHOP
	 *
	 * @param   array &$options Mail Sections
	 *
	 * @return  void
	 */
	public function onMailSections(&$options)
	{
		$options['send_discount_code_mail'] = JText::_('PLG_SYSTEM_REDSHOP_SEND_DISCOUNT_CODE_EMAIL_SECTION');
	}

	/**
	 * Load a layout for modal button on onAfterRoute function
	 *
	 * @param   string &$render Render of layout
	 *
	 * @return  void
	 *
	 * @throws  Exception
	 */
	public function onRedshopAdminRender(&$render)
	{
		$app = JFactory::getApplication();

		if (!$app->isAdmin())
		{
			return;
		}

		$input = $app->input;

		if ($input->get('option', '') != 'com_redshop')
		{
			return;
		}

		if ($input->get('view', '') != 'voucher' && $input->get('view', '') != 'coupon')
		{
			return;
		}

		$render .= RedshopLayoutHelper::render(
			'form',
			array(
				'view' => $input->get('view', '')
			),
			JPATH_SITE . '/plugins/' . $this->_type . '/' . $this->_name . '/layouts'
		);
	}

	/**
	 * [getDiscountCode description]
	 *
	 * @param   int    $id   Discount ID
	 * @param   string $type Voucher or Coupon
	 *
	 * @return  string         Discount Code
	 */
	private function getDiscountCode($code, $type = "voucher")
	{
		$db = JFactory::getDbo();

		$query = $db->getQuery(true);
        if ($type == 'voucher')
        {
            $table = '#__redshop_voucher';

            $query->select(
                array
                (
                    $db->qn('code'),
                    $db->qn('amount'),
                    $db->qn('type'),
                )
            )
                ->from($db->qn($table))
                ->where($db->qn('code') . ' = ' . $db->q($code));

        }
        else {
            $table = '#__redshop_coupons';

            $query->select(
                array
                (
                    $db->qn('code'),
                    $db->qn('value'),
                    $db->qn('type'),
                )
            )
                ->from($db->qn($table))
                ->where($db->qn('code') . ' = ' . $db->q($code));
        }

		return $db->setQuery($query)->loadObject();
	}
}

