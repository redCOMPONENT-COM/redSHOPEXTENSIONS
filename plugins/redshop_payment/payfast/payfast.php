<?php
/**
 * @package     RedSHOP
 * @subpackage  Plugin
 *
 * @copyright   Copyright (C) 2008 - 2015 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

class plgRedshop_PaymentPayfast extends JPlugin
{
    /**
     * Load the language file on instantiation.
     *
     * @var    boolean
     * @since  1.0
     */
    protected $autoloadLanguage = true;

    /**
     * @var string
     * @since 1.0
     */
    protected $secretKey = '';

    /**
     * @param $element
     * @param $data
     *
     * @throws Exception
     * @since  1.0
     */
    public function onPrePayment($element, $data)
    {
        if ($element != 'payfast') {
            return;
        }

        $secretKey = md5(uniqid(rand(), true));
        \JFactory::getSession()->set('payfast_secret_key', $secretKey);

        $url       = \JRoute::_(
            JUri::base()
            . 'index.php?option=com_redshop&view=order_detail&layout=receipt&Itemid=0&oid='
            . (int)$data['order_id']
        );

        $notifyUrl = \JRoute::_(
            JUri::base()
            . 'index.php?option=com_redshop&view=order_detail&controller=order_detail&task=notify_payment&payment_plugin=Payfast&payment_status=COMPLETE&secret_key=' . $secretKey
            .  '&Itemid=0&oid=' . $data['order_id']
        );

        $checksumSource = array(
            'merchant_id'   => $this->params->get('merchantId'),
            'merchant_key'  => $this->params->get('merchantKey'),
            'return_url'    => $notifyUrl,
            'cancel_url'    => $url,
            'notify_url'    => $url,
            'name_first'    => $data['billinginfo']->firstname,
            'name_last'     => $data['billinginfo']->lastname,
            'email_address' => $data['billinginfo']->user_email,
            'm_payment_id'  => $data['order_id'],
            'amount'        => $data['order']->order_total,
            'item_name'     => 'Order #' . $data['order_id']
        );

        $pfOutput = '';
        foreach ($checksumSource as $key => $val) {
            if (!empty($val)) {
                $pfOutput .= $key . '=' . urlencode(trim($val)) . '&';
            }
        }

        $getString = substr($pfOutput, 0, -1);

        if (isset($passPhrase)) {
            $getString .= '&passphrase=' . urlencode(trim($passPhrase));
        }
        $data['signature'] = md5($getString);

        $testingMode = (bool)$this->params->get('sandbox', 1);
        $pfHost      = $testingMode ? 'sandbox.payfast.co.za' : 'www.payfast.co.za';
        $htmlForm    = '<form  id="payfastform" action="https://' . $pfHost . '/eng/process" method="post">';

        foreach ($checksumSource as $name => $value) {
            $htmlForm .= '<input name="' . $name . '" type="hidden" value="' . $value . '" />';
        }

        $htmlForm .= '<input type="submit" value="Pay Now" /></form>';
        echo $htmlForm;

        JFactory::getDocument()->addScriptDeclaration(
            '
			jQuery(document).ready(function($) {
				jQuery("#payfastform").submit();
			});
		'
        );
    }

    /**
     * @param $element
     * @param $request
     *
     * @return stdClass|void
     * @throws Exception
     * @since 1.0
     */
    public function onNotifyPaymentPayfast($element, $request)
    {
        if (strtolower($element) != 'payfast') {
            return;
        }
        // Notify PayFast that information has been received
        header('HTTP/1.0 200 OK');
        flush();

        $orderId       = $request['m_payment_id'] ?? $request['oid'];
        $paymentStatus = $request['payment_status'];

        $validHosts = array(
            'www.payfast.co.za',
            'sandbox.payfast.co.za',
            'w1w.payfast.co.za',
            'w2w.payfast.co.za',
        );

        $secretKey = \JFactory::getSession()->get('payfast_secret_key', '');
        $result = ($secretKey == $request['secret_key']);

        $values           = new stdClass;
        $values->order_id = $orderId;

        if ($result
            && ($paymentStatus == 'COMPLETE')) {
            $values->order_status_code         = $this->params->get('payment_status', 'C');
            $values->order_payment_status_code = 'Paid';
            $values->log                       = \JText::_('PLG_REDSHOP_PAYMENT_PAYFAST_ORDER_PLACED');
            $values->msg                       = \JText::_('PLG_REDSHOP_PAYMENT_PAYFAST_ORDER_PLACED');
        } else {
            $values->order_status_code         = $this->params->get('invalid_status', 'P');
            $values->order_payment_status_code = 'Unpaid';
            $values->log                       = \JText::_('PLG_REDSHOP_PAYMENT_PAYFAST_ORDER_NOT_PLACED');
            $values->msg                       = \JText::_('PLG_REDSHOP_PAYMENT_PAYFAST_ORDER_NOT_PLACED');
            $values->type                      = 'error';
        }

        return $values;
    }
}