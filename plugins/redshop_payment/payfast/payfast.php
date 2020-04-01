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

        $url       = \JRoute::_(
            JUri::base()
            . 'index.php?option=com_redshop&view=order_detail&layout=receipt&Itemid=0&oid='
            . (int)$data['order_id']
        );
        $notifyUrl = \JRoute::_(
            JUri::base()
            . 'index.php?option=com_redshop&view=order_detail&controller=order_detail&task=notify_payment&payment_plugin=payfast&Itemid=0&oid='
            . (int)$data['order_id']
        );

        $checksumSource = array(
            'merchant_id'   => $this->params->get('merchantId'),
            'merchant_key'  => $this->params->get('merchantKey'),
            'return_url'    => $url,
            'cancel_url'    => $url,
            'notify_url'    => $notifyUrl,
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
        if ($element != 'payfast') {
            return;
        }

        $app   = JFactory::getApplication();

        define('SANDBOX_MODE', (int)$this->params->get('sandbox', 1));
        $pfHost = SANDBOX_MODE ? 'sandbox.payfast.co.za' : 'www.payfast.co.za';

        // Posted variables from ITN
        $pfData = $request;

        // Strip any slashes in data
        foreach ($pfData as $key => $val) {
            $pfData[$key] = stripslashes($val);
        }

        $pfParamString = '';

        // Construct variables
        foreach ($pfData as $key => $val) {
            if ($key != 'signature') {
                $pfParamString .= $key . '=' . urlencode($val) . '&';
            }
        }

        $pfParamString     = substr($pfParamString, 0, -1);
        $pfTempParamString = $pfParamString;

        $passPhrase = '';

        if (!empty($passPhrase)) {
            $pfTempParamString .= '&passphrase=' . urlencode($passPhrase);
        }
        $signature = md5($pfTempParamString);

        if ($signature != $pfData['signature']) {
            die('Invalid Signature');
        }

        $validHosts = array(
            'www.payfast.co.za',
            'sandbox.payfast.co.za',
            'w1w.payfast.co.za',
            'w2w.payfast.co.za',
        );

        // Variable initialization
        $url = 'https://' . $pfHost . '/eng/query/validate';

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $pfParamString);

        $response = curl_exec($ch);
        curl_close($ch);

        $lines        = explode("\r\n", $response);
        $verifyResult = trim($lines[0]);

        if (strcasecmp($verifyResult, 'VALID') != 0) {
            die('Data not valid');
        }

        $pfPaymentId = $pfData['pf_payment_id'];
        $orderId     = $pfData['m_payment_id'];
        $values      = new stdClass;

        if ($pfData ['payment_status'] == 'COMPLETE') {
            $values->order_status_code         = $this->params->get('verify_status', '');
            $values->order_payment_status_code = 'Paid';
            $values->log                       = \JText::_(
                'PLG_REDSHOP_PAYMENT_PAYGATE_PAYMENT_SUCCESS_LOG',
                $pfPaymentId
            );
            $values->msg                       = \JText::_('PLG_REDSHOP_PAYMENT_PAYFAST_PAYMENT_SUCCESS');
            $values->type                      = 'Success';
        } else {
            // If unknown status, do nothing (which is the safest course of action)
            $values->order_status_code         = $this->params->get('invalid_status', '');
            $values->order_payment_status_code = 'Unpaid';
            $values->log                       = \JText::_(
                'PLG_REDSHOP_PAYMENT_PAYFAST_PAYMENT_FAIL_LOG',
                'FAIL NHA CUNG'
            );
            $values->msg                       = '';

            $app->enqueueMessage($values->log, 'Warning');
        }

        $values->transaction_id = $pfPaymentId;
        $values->order_id       = $orderId;

        return $values;

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