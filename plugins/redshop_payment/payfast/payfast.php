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

        $app = \JFactory::getApplication();


        $checksumSource = array(
            'merchant_id'   => $this->params->get('merchantId'),
            'merchant_key'  => $this->params->get('merchantKey'),
            'return_url'    => 'http://www.yourdomain.co.za/thank-you.html',
            'cancel_url'    => 'http://www.yourdomain.co.za/cancelled-transction.html',
            'notify_url'    => 'http://www.yourdomain.co.za/itn.php',
            'name_first'    => 'First Name',
            'name_last'     => 'Last Name',
            'email_address' => 'sbtu01@payfast.co.za',
            'm_payment_id'  => '',
            'amount'        => '100.00',
            'item_name'     => 'Test Item',

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

        $testingMode = true;
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

    public function onNotifyPaymentPayfast($element, $request)
    {
        if ($element != 'payfast') {
            return;
        }

        $app   = JFactory::getApplication();
        $input = $app->input;

        $status     = $input->getInt('TRANSACTION_STATUS');
        $tid        = $input->getInt('TRANSACTION_ID');
        $resultCode = $input->getInt('RESULT_CODE');
        $resultDesc = $input->getString('RESULT_DESC');

        $checksumSource = array(
            'PAYGATE_ID'         => $this->params->get('paygateId'),
            'REFERENCE'          => $input->getInt('REFERENCE'),
            'TRANSACTION_STATUS' => $status,
            'RESULT_CODE'        => $resultCode,
            'AUTH_CODE'          => $input->getString('AUTH_CODE'),
            'AMOUNT'             => $input->getFloat('AMOUNT'),
            'RESULT_DESC'        => $resultDesc,
            'TRANSACTION_ID'     => $tid
        );

        if ($riskIndicator = $input->getString('RISK_INDICATOR')) {
            $checksumSource['RISK_INDICATOR'] = $riskIndicator;
        }

        // Local secret key
        $checksumSource['CHECKSUM'] = $this->params->get('encryptionKey');

        $testChecksum = md5(implode("|", $checksumSource));

        $values = new stdClass;

        // Invalid trasaction
        if ($testChecksum != $input->getString('CHECKSUM')) {
            $values->order_status_code         = $this->params->get('invalid_status', '');
            $values->order_payment_status_code = 'Unpaid';
            $values->log                       = JText::_('PLG_REDSHOP_PAYMENT_PAYGATE_PAYMENT_INVALID_LOG');
            $values->msg                       = '';

            $app->enqueueMessage($values->log, 'Error');
        } // Transaction is valid and success
        else {
            if ($status == 1 && $resultCode == 990017) {
                $values->order_status_code         = $this->params->get('verify_status', '');
                $values->order_payment_status_code = 'Paid';
                $values->log                       = JText::sprintf(
                    'PLG_REDSHOP_PAYMENT_PAYGATE_PAYMENT_SUCCESS_LOG',
                    $tid
                );
                $values->msg                       = JText::_('PLG_REDSHOP_PAYMENT_PAYGATE_PAYMENT_SUCCESS');
                $values->type                      = 'Success';
            } // Transaction is valid but payment fail
            else {
                $values->order_status_code         = $this->params->get('invalid_status', '');
                $values->order_payment_status_code = 'Unpaid';
                $values->log                       = JText::sprintf(
                    'PLG_REDSHOP_PAYMENT_PAYGATE_PAYMENT_FAIL_LOG',
                    $resultDesc
                );
                $values->msg                       = '';

                $app->enqueueMessage($values->log, 'Warning');
            }
        }

        $values->transaction_id = $tid;
        $values->order_id       = $input->getInt('orderid');

        return $values;
    }
}