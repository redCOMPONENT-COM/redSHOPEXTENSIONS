<?php
/**
 * @package     RedSHOP
 * @subpackage  Plugin
 *
 * @copyright   Copyright (C) 2008 - 2015 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

/**
 * Class plgEconomicEconomic
 *
 * @since  2.0.0
 */
class PlgEconomicEconomic extends JPlugin
{
	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 * @since  3.1
	 */
	protected $autoloadLanguage = true;

	/**
	 * specific redform plugin parameters
	 *
	 * @var JRegistry object
	 */
	public $_conn = false;

	/**
	 * @var integer
	 */
	public $error = 0;

	/**
	 * @var null|string
	 */
	public $errorMsg = null;

	/**
	 * @var SoapClient
	 */
	public $client;

	/**
	 * @var null
	 */
	public $termOfPayment = null;

	/**
	 * @var integer
	 */
	public $contraAccount = 0;

	/**
	 * @var integer
	 */
	public $cashbook = 0;

	/**
	 * @var null
	 */
	public $LayoutHandle = null;

	/**
	 * @var null
	 */
	public $UnitHandle;

	/**
	 * @var null
	 */
	public $debtorGroupHandles = null;

	/**
	 * PlgEconomicEconomic constructor.
	 *
	 * @param   object $subject Subject
	 * @param   array  $config  Config
	 *
	 * @throws  Exception
	 */
	public function __construct(&$subject, $config = array())
	{
		parent::__construct($subject, $config);

		$input = JFactory::getApplication()->input;

		// If not store agreement token. Start init SOAP Client
		if ($input->getCmd('option') != 'com_ajax' || $input->getCmd('plugin') != 'economicStoreAgreementToken'
			|| $input->getCmd('group') != 'economic')
		{
			$this->onEconomicConnect();
		}

		JPlugin::loadLanguage('plg_economic_economic');
	}

	/**
	 * Method for store "Economic Agreement Token" into plugin params.
	 *
	 * @return  void
	 *
	 * @throws  Exception
	 */
	public function onAjaxEconomicStoreAgreementToken()
	{
		$app    = JFactory::getApplication();
		$token  = $app->input->get('token', '');
		$params = $this->params;
		$params->set('agreementGrantToken', $token);

		$db    = JFactory::getDbo();
		$query = $db->getQuery(true)
			->update($db->qn('#__extensions'))
			->set($db->qn('params') . ' = ' . $db->quote($params->toString()))
			->where($db->qn('type') . ' = ' . $db->quote('plugin'))
			->where($db->qn('element') . ' = ' . $db->quote($this->_name))
			->where($db->qn('folder') . ' = ' . $db->quote($this->_type));

		$db->setQuery($query)->execute();
		$app->enqueueMessage(JText::_('PLG_ECONOMIC_ECONOMIC_SUCCESS_GRANT_ACCESS'), 'success');
		$app->redirect(JUri::root());
	}

	/**
	 * Create e-conomic connection
	 *
	 * @return  void
	 */
	public function onEconomicConnect()
	{
		// Check whether plugin has been unpublished
		if (null === $this->params)
		{
			return;
		}

		$isEconomic = 'economic' === $this->params->get('accountType', 'economic');

		$soapUrl = $isEconomic ? 'https://api.e-conomic.com/secure/api1/EconomicWebService.asmx?wsdl'
			: 'https://soap.reviso.com/api1/EconomicWebService.asmx?wsdl';

		// Try to create SOAP client
		try
		{
			ini_set('soap.wsdl_cache_enabled', '0');

			$this->client = new SoapClient(
				$soapUrl,
				array(
					"trace"          => 1,
					"exceptions"     => 1,
					"stream_context" => stream_context_create(
						array(
							"http" => array(
								"header" => "X-EconomicAppIdentifier: " . self::getAppIdentifier()
							)
						)
					)
				)
			);
		}
		catch (Exception $exception)
		{
			$this->error    = 1;
			$this->errorMsg = "Unable to connect soap client - E-conomic Plugin Failure.";

			JError::raiseWarning(21, $exception->getMessage());
		}

		try
		{
			if ($isEconomic)
			{
				$this->_conn = $this->client->ConnectWithToken(
					array(
						'appToken' => 'HMyFsrGmE0SNYKscbninTynqGZy71uAjccarUzQ5ic81',
						'token'    => $this->params->get('agreementGrantToken', '')
					)
				);
			}
			else
			{
				$this->_conn = $this->client->Connect(
					array(
						'agreementNumber' => $this->params->get('economic_agreement_number', ''),
						'userName'        => $this->params->get('economic_username', ''),
						'password'        => $this->params->get('economic_password', '')
					)
				);
			}
		}
		catch (Exception $exception)
		{
			$this->error    = 1;
			$this->errorMsg = "e-conomic user is not authenticated. Access denied";

			if (Redshop::getConfig()->get('DETAIL_ERROR_MESSAGE_ON'))
			{
				$this->report(__METHOD__, $exception);
			}
			else
			{
				JError::raiseWarning(21, JText::_('COM_REDSHOP_DETAIL_ERROR_MESSAGE_LBL'));
			}
		}
	}

	/**
	 * Get unique app identifier for e-conomic plugin.
	 *
	 * @see http://techtalk.e-conomic.com/e-conomic-soap-api-now-requires-you-to-specify-a-custom-x-economicappidentifier-header/ X-EconomicAppIdentifier
	 *
	 * @return  string  Unique Identifier string
	 */
	protected static function getAppIdentifier()
	{
		// Getting plugin information
		$manifestFile = simplexml_load_file(__DIR__ . '/economic.xml');

		$appIdentifier = __CLASS__ . '/' . $manifestFile->version
			. ' redshop/' . $manifestFile->redshop
			. ' (http://redcomponent.com/redcomponent/redshop/plugins/economic-accounting; support@redcomponent.com)'
			. ' ' . JFactory::getConfig()->get('sitename');

		return $appIdentifier;
	}

	/**
	 * Method to find debtor number in economic
	 *
	 * @param   array $data Data
	 *
	 * @return  string
	 */
	public function Debtor_FindByNumber($data)
	{
		if ($this->error)
		{
			return $this->errorMsg;
		}

		try
		{
			return $this->client->Debtor_FindByNumber(array('number' => $data ['user_info_id']))->Debtor_FindByNumberResult;
		}
		catch (Exception $exception)
		{
			if (Redshop::getConfig()->get('DETAIL_ERROR_MESSAGE_ON'))
			{
				$this->report(__METHOD__, $exception, array('number' => $data['user_info_id']));
			}
			else
			{
				JError::raiseWarning(21, JText::_('COM_REDSHOP_DETAIL_ERROR_MESSAGE_LBL'));
			}
		}
	}

	/**
	 * Method to find debtor email in economic.
	 *
	 * @param   array $data Data
	 *
	 * @return  string
	 */
	public function Debtor_FindByEmail($data)
	{
		if ($this->error)
		{
			return $this->errorMsg;
		}

		try
		{
			return $this->client->__soapCall(
				'Debtor_FindByEmail',
				array(
					'Debtor_FindByEmail' => array(
						'email' => trim($data['email'])
					)
				)
			)->Debtor_FindByEmailResult;
		}
		catch (Exception $exception)
		{
			if (Redshop::getConfig()->get('DETAIL_ERROR_MESSAGE_ON'))
			{
				$this->report(__METHOD__, $exception, array('email' => trim($data['email'])));
			}
			else
			{
				JError::raiseWarning(21, JText::_('COM_REDSHOP_DETAIL_ERROR_MESSAGE_LBL'));
			}
		}
	}

	/**
	 * Method to find debtor by VAT number in economic.
	 *
	 * @access public
	 * @return array
	 */
	public function Debtor_FindByVAT($d)
	{
		if ($this->error)
		{
			return $this->errorMsg;
		}

		try
		{
			$Handle = $this->client->Debtor_FindByCINumber(array('ciNumber' => $d ['vatnumber']))->Debtor_FindByCINumberResult;

			return $Handle;
		}
		catch (Exception $exception)
		{
			print("<p><i>Debtor_FindByCINumber:" . $exception->getMessage() . "</i></p>");

			if (DETAIL_ERROR_MESSAGE_ON)
			{
				JError::raiseWarning(21, "Debtor_FindByCINumber:" . $exception->getMessage());
			}
			else
			{
				JError::raiseWarning(21, JText::_('DETAIL_ERROR_MESSAGE_LBL'));
			}
		}
	}

	/**
	 * Method to get debtor group in economic.
	 *
	 * @param   array $data Data
	 *
	 * @return  mixed
	 */
	public function Debtor_GetDebtorGroup($data)
	{
		if ($this->error)
		{
			return $this->errorMsg;
		}

		try
		{
			$debtorHandle         = new stdclass;
			$debtorHandle->Number = $data ['user_info_id'];

			return $this->client->Debtor_GetDebtorGroup(array('debtorHandle' => $debtorHandle))->Debtor_GetDebtorGroupResult;
		}
		catch (Exception $exception)
		{
			if (Redshop::getConfig()->get('DETAIL_ERROR_MESSAGE_ON'))
			{
				$this->report(__METHOD__, $exception, array('debtorHandle' => $debtorHandle));
			}
			else
			{
				JError::raiseWarning(21, JText::_('COM_REDSHOP_DETAIL_ERROR_MESSAGE_LBL'));
			}
		}
	}

	/**
	 * Method to get debtor group
	 *
	 * @return  mixed
	 */
	public function getDebtorGroup()
	{
		if ($this->error)
		{
			return $this->errorMsg;
		}

		$checkDebtorGroupId = $this->params->get('economic_debtor_group_id', 2);

		if ($this->debtorGroupHandles)
		{
			return $this->debtorGroupHandles;
		}

		$debtorGroupHandles = $this->client->debtorGroup_GetAll()->DebtorGroup_GetAllResult->DebtorGroupHandle;
		$deptorGroups       = array();

		if (is_object($debtorGroupHandles))
		{
			if (isset($debtorGroupHandles->Number))
			{
				$deptorGroups[] = $debtorGroupHandles->Number;
			}
		}
		else
		{
			foreach ($debtorGroupHandles as $item)
			{
				if ($item->Number)
				{
					$deptorGroups[] = $item->Number;
				}
			}
		}

		$debtorGroupHandle = new stdClass;

		if (count($deptorGroups) > 0)
		{
			if (in_array($checkDebtorGroupId, $deptorGroups))
			{
				$debtorGroupHandle->Number = $checkDebtorGroupId;
			}
			else
			{
				$debtorGroupHandle->Number = $deptorGroups[0];
			}

			$this->debtorGroupHandles = $debtorGroupHandle;
		}

		return $debtorGroupHandle;
	}

	/**
	 * Method to get term of payment
	 *
	 * @param   array $data Data
	 *
	 * @return  mixed
	 */
	public function getTermOfPayment($data)
	{
		if ($this->error)
		{
			return $this->errorMsg;
		}

		if (isset($data['economic_payment_terms_id']) && $data['economic_payment_terms_id'] != ""
			&& $data['economic_payment_terms_id'] != 0)
		{
			$checkPaymentId = intval($data['economic_payment_terms_id']);
		}
		else
		{
			$checkPaymentId = $this->params->get('economic_payment_terms', 2);
		}

		if ($this->termOfPayment && $this->termOfPayment == $checkPaymentId)
		{
			return $this->termOfPayment;
		}

		$terms           = array();
		$termOfResultAll = $this->client->TermOfPayment_GetAll()->TermOfPayment_GetAllResult;
		$termOfPayments  = $termOfResultAll->TermOfPaymentHandle;

		if (is_object($termOfPayments))
		{
			if (isset($termOfPayments->Id))
			{
				$terms[] = $termOfPayments->Id;
			}
		}
		else
		{
			foreach ($termOfPayments as $termOfPayment)
			{
				if ($termOfPayment->Id)
				{
					$terms[] = $termOfPayment->Id;
				}
			}
		}

		if (count($terms) > 0)
		{
			if (in_array($checkPaymentId, $terms))
			{
				$this->termOfPayment = $checkPaymentId;
			}
			else
			{
				$this->termOfPayment = $terms[0];
			}
		}

		return $this->termOfPayment;
	}

	public function getTermOfPaymentContraAccount($d)
	{
		if ($this->error)
		{
			return $this->errorMsg;
		}

		if (!$this->termOfPayment)
		{
			$this->termOfPayment = $this->getTermOfPayment($d);
		}

		try
		{
			$this->contraAccount = 0;

			$termOfPaymentHandle     = new stdclass;
			$termOfPaymentHandle->Id = $this->termOfPayment;

			$contractAccount = $this->client->TermOfPayment_GetContraAccount(
				array('termOfPaymentHandle' => $termOfPaymentHandle)
			)->TermOfPayment_GetContraAccountResult;

			if (isset($contractAccount->Number))
			{
				$this->contraAccount = $contractAccount->Number;
			}

			return $this->contraAccount;
		}
		catch (Exception $exception)
		{
			if (Redshop::getConfig()->get('DETAIL_ERROR_MESSAGE_ON'))
			{
				$this->report(__METHOD__, $exception, array('termOfPaymentHandle' => $termOfPaymentHandle));
			}
			else
			{
				JError::raiseWarning(21, JText::_('COM_REDSHOP_DETAIL_ERROR_MESSAGE_LBL'));
			}
		}
	}

	/**
	 * Method for get all Cash Book
	 *
	 * @return  mixed
	 */
	public function getCashBookAll()
	{
		if ($this->error)
		{
			return $this->errorMsg;
		}

		try
		{
			$this->cashbook  = 1;
			$cashBookNumbers = array();
			$cashBooks       = $this->client->CashBook_GetAll()->CashBook_GetAllResult;
			$cashBooks       = $cashBooks->CashBookHandle;

			if (is_object($cashBooks))
			{
				if (isset($cashBooks->Number))
				{
					$cashBookNumbers[] = $cashBooks->Number;
				}
			}
			else
			{
				foreach ($cashBooks as $cashBook)
				{
					if ($cashBook->Number)
					{
						$cashBookNumbers[] = $cashBook->Number;
					}
				}
			}

			if (count($cashBookNumbers) > 0)
			{
				$cashBookNumber = $this->params->get('economic_cashbook_number', 1);

				if (in_array($cashBookNumber, $cashBookNumbers))
				{
					$this->cashbook = $cashBookNumber;
				}
				else
				{
					$this->cashbook = $cashBookNumbers[0];
				}
			}

			return $this->cashbook;
		}
		catch (Exception $exception)
		{
			if (Redshop::getConfig()->get('DETAIL_ERROR_MESSAGE_ON'))
			{
				$this->report(__METHOD__, $exception);
			}
			else
			{
				JError::raiseWarning(21, JText::_('COM_REDSHOP_DETAIL_ERROR_MESSAGE_LBL'));
			}
		}
	}

	/**
	 * Method to get layout template
	 *
	 * @param   array $data Data
	 *
	 * @return  mixed
	 */
	public function getLayoutTemplate($data)
	{
		if ($this->error)
		{
			return $this->errorMsg;
		}

		if ($this->LayoutHandle)
		{
			return $this->LayoutHandle;
		}

		$layoutHandleId = 0;
		$arr            = array();
		$resultAll      = $this->client->TemplateCollection_GetAll()->TemplateCollection_GetAllResult;
		$termOfPayments = $resultAll->TemplateCollectionHandle;

		if (is_object($termOfPayments))
		{
			if (isset($termOfPayments->Id))
			{
				$arr[] = $termOfPayments->Id;
			}
		}
		else
		{
			for ($i = 0, $in = count($termOfPayments); $i < $in; $i++)
			{
				if ($termOfPayments[$i]->Id)
				{
					$arr[] = $termOfPayments[$i]->Id;
				}
			}
		}

		if (count($arr) > 0)
		{
			if (isset($data['economic_design_layout']) && $data['economic_design_layout'] != "" && $data['economic_design_layout'] != 0)
			{
				$checkId = intval($data['economic_design_layout']);
			}
			else
			{
				$checkId = $this->params->get('economic_layout_id', 19);
			}

			if (in_array($checkId, $arr))
			{
				$layoutHandleId = $checkId;
			}
			else
			{
				$layoutHandleId = $arr[0];
			}
		}

		$layoutHandle     = new stdclass;
		$layoutHandle->Id = $layoutHandleId;

		$this->LayoutHandle = $layoutHandle;

		return $layoutHandle;
	}

	/**
	 * Method to store debtor in economic
	 *
	 * @param   array $data Data
	 *
	 * @return  mixed
	 */
	public function storeDebtor($data)
	{
		if ($this->error)
		{
			return $this->errorMsg;
		}

		$deptorGroupHandle = $this->getDebtorGroup();

		if (isset($data['eco_user_number']) && $data['eco_user_number'] != "")
		{
			$tmpDebtorGroup = $this->Debtor_GetDebtorGroup($data);

			if (empty($tmpDebtorGroup->Number) || (int) $tmpDebtorGroup->Number !== (int) $deptorGroupHandle->Number)
			{
				$data['eco_user_number'] = '';
			}
		}

		$TermOfPaymentHandle     = new stdclass;
		$TermOfPaymentHandle->Id = $this->getTermOfPayment($data);

		$CurrencyHandle       = new stdclass;
		$CurrencyHandle->Code = $data ['currency_code'];

		// Changes for store debtor error
		$data['user_info_id'] = !empty($data['eco_user_number']) ? $data['eco_user_number'] : $data['user_info_id'];

		if ($data['newuserFlag'] === true)
		{
			$data['user_info_id'] = $this->getMaxDebtor() + 1;
		}

		$Handle         = new stdclass;
		$Handle->Number = $data ['user_info_id'];

		$LayoutHandle = $this->getLayoutTemplate($data);

		try
		{
			$debtorInfor = array
			(
				'Handle'                => $Handle,
				'Number'                => $data['user_info_id'],
				'DebtorGroupHandle'     => $deptorGroupHandle,
				'Name'                  => $data['name'],
				'VatZone'               => $data['vatzone'],
				'CINumber'              => $data['vatnumber'],
				'CurrencyHandle'        => $CurrencyHandle,
				'IsAccessible'          => 1,
				'Email'                 => $data ['email'],
				'TelephoneAndFaxNumber' => $data ['phone'],
				'Address'               => $data ['address'],
				'PostalCode'            => $data ['zipcode'],
				'City'                  => $data ['city'],
				'Country'               => $data ['country'],
				'TermOfPaymentHandle'   => $TermOfPaymentHandle,
				'LayoutHandle'          => $LayoutHandle
			);

			// Get Employee to set Our Reference Number
			if ($employeeHandle = $this->employeeFindByNumber($data))
			{
				$debtorInfor['OurReferenceHandle'] = $employeeHandle;
			}

			if (isset($data['ean_number']) && $data['ean_number'] != "")
			{
				$debtorInfor = array_merge($debtorInfor, array('Ean' => $data['ean_number']));
			}

			if (isset($data['maximumcredit']) && $data['maximumcredit'] != 0)
			{
				$debtorInfor = array_merge($debtorInfor, array('CreditMaximum' => $data['maximumcredit']));
			}

			if (!empty($data['eco_user_number']))
			{
				$newDebtorHandle = $this->client->Debtor_UpdateFromData(array('data' => $debtorInfor))->Debtor_UpdateFromDataResult;
			}
			else
			{
				$newDebtorHandle = $this->client->Debtor_CreateFromData(array('data' => $debtorInfor))->Debtor_CreateFromDataResult;
			}

			return $newDebtorHandle;
		}
		catch (Exception $exception)
		{
			if (Redshop::getConfig()->get('DETAIL_ERROR_MESSAGE_ON'))
			{
				$this->report(__METHOD__, $exception, $debtorInfor, $data);
			}
			else
			{
				JError::raiseWarning(21, JText::_('COM_REDSHOP_DETAIL_ERROR_MESSAGE_LBL'));
			}
		}
	}

	/**
	 * Get Extra field value for Debtor Reference
	 *
	 * @param   array $d User information array
	 *
	 * @return  mixed      User input if found else false
	 * @throws  Exception
	 */
	protected function getExtraFieldForDebtorRef($d)
	{
		// Get which fields are for employee reference from params
		$extraFieldForDebtorRef = (int) trim($this->params->get('extraFieldForDebtorRef', 0));

		$extraFieldForDebtorCompanyRef = (int) trim($this->params->get('extraFieldForDebtorCompanyRef', 0));

		if ($extraFieldForDebtorRef || $extraFieldForDebtorCompanyRef)
		{
			$usersInfo = JTable::getInstance('user_detail', 'table');
			$usersInfo->load($d['user_info_id']);

			$section = 7;
			$fieldId = $extraFieldForDebtorRef;

			if ($usersInfo->is_company)
			{
				$section = 8;
				$fieldId = $extraFieldForDebtorCompanyRef;
			}

			// Initialiase variables.
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);

			// Create the base select statement.
			$query->select('data_txt')
				->from($db->qn('#__redshop_fields_data'))
				->where($db->qn('fieldid') . ' = ' . $fieldId)
				->where($db->qn('itemid') . ' = ' . (int) $d['user_info_id'])
				->where($db->qn('section') . ' = ' . $db->q($section));

			// Set the query and load the result.
			$db->setQuery($query);

			try
			{
				return (int) $db->loadResult();
			}
			catch (RuntimeException $e)
			{
				throw new RuntimeException($e->getMessage(), $e->getCode());
			}
		}

		return false;
	}

	/**
	 * Get Employee By Number
	 *
	 * @param   array $data User information array
	 *
	 * @return  boolean|object  StdClass Object on success, false on fail.
	 * @throws  Exception
	 */
	protected function employeeFindByNumber($data)
	{
		$userInput = $this->getExtraFieldForDebtorRef($data);

		// Return false if there is no reference is set
		if (!$userInput)
		{
			return false;
		}

		try
		{
			$employee = $this->client
				->Employee_FindByNumber(
					array(
						"number" => (int) $userInput
					)
				)->Employee_FindByNumberResult;
		}
		catch (Exception $exception)
		{
			if (Redshop::getConfig()->get('DETAIL_ERROR_MESSAGE_ON'))
			{
				$this->report(__METHOD__, $exception, array('number' => (int) $userInput));
			}
			else
			{
				JError::raiseWarning(21, JText::_('COM_REDSHOP_DETAIL_ERROR_MESSAGE_LBL'));
			}
		}

		return $employee;
	}

	/**
	 * Method for find Product Group
	 *
	 * @param   array $data Data
	 *
	 * @return null|string
	 */
	public function ProductGroup_FindByNumber($data)
	{
		if ($this->error)
		{
			return $this->errorMsg;
		}

		try
		{
			$productGroup = $this->client->ProductGroup_FindByNumber(array('number' => $data['productgroup_id']))->ProductGroup_FindByNumberResult;

			return $productGroup;
		}
		catch (Exception $exception)
		{
			if (Redshop::getConfig()->get('DETAIL_ERROR_MESSAGE_ON'))
			{
				$this->report(__METHOD__, $exception, array('number' => $data['productgroup_id']));
			}
			else
			{
				JError::raiseWarning(21, JText::_('COM_REDSHOP_DETAIL_ERROR_MESSAGE_LBL'));
			}
		}
	}

	/**
	 * Method to find product number in economic
	 *
	 * @param   array $data Data
	 *
	 * @return  mixed
	 */
	public function Product_FindByNumber($data)
	{
		if ($this->error)
		{
			return $this->errorMsg;
		}

		try
		{
			return $this->client->Product_FindByNumber(array('number' => $data['product_number']))->Product_FindByNumberResult;
		}
		catch (Exception $exception)
		{
			if (Redshop::getConfig()->get('DETAIL_ERROR_MESSAGE_ON'))
			{
				$this->report(__METHOD__, $exception, array('number' => $data['product_number']));
			}
			else
			{
				JError::raiseWarning(21, JText::_('COM_REDSHOP_DETAIL_ERROR_MESSAGE_LBL'));
			}
		}
	}

	/**
	 * Method to get stock of product in economic
	 *
	 * @param   array $data Data
	 *
	 * @return  mixed
	 */
	public function getProductStock($data)
	{
		if ($this->error)
		{
			return $this->errorMsg;
		}

		try
		{
			$productHandle         = new stdClass;
			$productHandle->Number = $data['product_number'];
			$Handle                = $this->client->Product_GetInStock(array('productHandle' => $productHandle))->Product_GetInStockResult;

			return $Handle;
		}
		catch (Exception $exception)
		{
			if (Redshop::getConfig()->get('DETAIL_ERROR_MESSAGE_ON'))
			{
				$this->report(__METHOD__, $exception, array('productHandle' => $productHandle));
			}
			else
			{
				JError::raiseWarning(21, JText::_('COM_REDSHOP_DETAIL_ERROR_MESSAGE_LBL'));
			}
		}
	}

	/**
	 * Method to get product group
	 *
	 * @access public
	 * @return array
	 */
	public function getProductGroup($d)
	{
		if ($this->error)
		{
			return $this->errorMsg;
		}

		$productGroupHandles = $this->client->ProductGroup_GetAll()->ProductGroup_GetAllResult->ProductGroupHandle;

		for ($i = 0, $in = count($productGroupHandles); $i < $in; $i++)
		{
			if (!$productGroupHandles[$i]->Number)
			{
				$productGroupHandle         = new stdclass;
				$productGroupHandle->Number = $productGroupHandles[$i];

				return $productGroupHandle;
				break;
			}

			return $productGroupHandles[$i];
		}

		return $productGroupHandles;
	}

	public function getMaxDebtor()
	{
		if ($this->error)
		{
			return $this->errorMsg;
		}

		$dbt = array();

		try
		{
			$debtors = $this->client->Debtor_GetAll()->Debtor_GetAllResult;
			$debtors = $debtors->DebtorHandle;

			if ($debtors->Number)
			{
				return $debtors->Number;
			}

			for ($i = 0, $in = count($debtors); $i < $in; $i++)
			{
				$dbt[] = $debtors [$i]->Number;
			}

			return max($dbt);
		}
		catch (Exception $exception)
		{
			if (Redshop::getConfig()->get('DETAIL_ERROR_MESSAGE_ON'))
			{
				$this->report(__METHOD__, $exception);
			}
			else
			{
				JError::raiseWarning(21, JText::_('COM_REDSHOP_DETAIL_ERROR_MESSAGE_LBL'));
			}
		}
	}

	public function getMaxInvoiceNumber()
	{
		if ($this->error)
		{
			return $this->errorMsg;
		}

		try
		{
			$inv     = array();
			$invoice = $this->client->Invoice_GetAll()->Invoice_GetAllResult;
			$invoice = $invoice->InvoiceHandle;

			if (is_array($invoice))
			{
				for ($i = 0, $in = count($invoice); $i < $in; $i++)
				{
					$inv[] = $invoice[$i]->Number;
				}
			}
			elseif ($invoice->Number)
			{
				$inv[] = $invoice->Number;
			}
			else
			{
				$inv[] = 0;
			}

			$max = max($inv);

			return $max;
		}
		catch (Exception $exception)
		{
			if (Redshop::getConfig()->get('DETAIL_ERROR_MESSAGE_ON'))
			{
				$this->report(__METHOD__, $exception);
			}
			else
			{
				JError::raiseWarning(21, JText::_('COM_REDSHOP_DETAIL_ERROR_MESSAGE_LBL'));
			}
		}
	}

	public function getMaxDraftInvoiceNumber()
	{
		if ($this->error)
		{
			return $this->errorMsg;
		}

		try
		{
			$max             = 0;
			$cinv            = array();
			$current_invoice = $this->client->CurrentInvoice_GetAll()->CurrentInvoice_GetAllResult;
			$current_invoice = $current_invoice->CurrentInvoiceHandle;

			if (is_array($current_invoice))
			{
				for ($i = 0, $in = count($current_invoice); $i < $in; $i++)
				{
					$cinv[] = $current_invoice[$i]->Id;
				}
			}
			elseif ($current_invoice->Id)
			{
				$cinv[] = $current_invoice->Id;
			}
			else
			{
				$cinv[] = 0;
			}

			$cmax = max($cinv);

			if ($cmax)
			{
				$currentInvoiceHandle     = new stdclass;
				$currentInvoiceHandle->Id = $cmax;
				$invoiceData              = $this->client
					->CurrentInvoice_GetOtherReference(array('currentInvoiceHandle' => $currentInvoiceHandle))
					->CurrentInvoice_GetOtherReferenceResult;

				if ($invoiceData && is_numeric($invoiceData))
				{
					$max = intval($invoiceData);
				}
			}

			return $max;
		}
		catch (Exception $exception)
		{
			if (Redshop::getConfig()->get('DETAIL_ERROR_MESSAGE_ON'))
			{
				$this->report(__METHOD__, $exception);
			}
			else
			{
				JError::raiseWarning(21, JText::_('COM_REDSHOP_DETAIL_ERROR_MESSAGE_LBL'));
			}
		}
	}

	public function getUnitGroup()
	{
		if ($this->error)
		{
			return $this->errorMsg;
		}

		if ($this->UnitHandle)
		{
			return $this->UnitHandle;
		}

		try
		{
			$UnitHandle = new stdclass;

			$UnitHandleId = 1;
			$arr          = array();
			$unitall      = $this->client->Unit_GetAll()->Unit_GetAllResult->UnitHandle;

			if (is_array($unitall))
			{
				for ($i = 0, $in = count($unitall); $i < $in; $i++)
				{
					if ($unitall[$i]->Number)
					{
						$arr[] = $unitall[$i]->Number;
					}
				}
			}
			else
			{
				$arr[] = $unitall->Number;
			}

			if (count($arr) > 0)
			{
				$checkId = $this->params->get('economic_units_id', 1);

				if (in_array($checkId, $arr))
				{
					$UnitHandleId = $checkId;
				}
				else
				{
					$UnitHandleId = $arr[0];
				}
			}

			$UnitHandle->Number = $UnitHandleId;
			$this->UnitHandle   = $UnitHandle;

			return $UnitHandle;
		}
		catch (Exception $exception)
		{
			if (Redshop::getConfig()->get('DETAIL_ERROR_MESSAGE_ON'))
			{
				$this->report(__METHOD__, $exception);
			}
			else
			{
				JError::raiseWarning(21, JText::_('COM_REDSHOP_DETAIL_ERROR_MESSAGE_LBL'));
			}
		}
	}

	/**
	 * Method to store product in economic
	 *
	 * @access public
	 * @return array
	 */
	public function storeProduct($d)
	{
		if ($this->error)
		{
			return $this->errorMsg;
		}

		try
		{
			if (isset($d['product_group']))
			{
				$productGroupHandle         = new stdclass;
				$productGroupHandle->Number = $d['product_group'];
			}
			else
			{
				$productGroupHandle = $this->getProductGroup($d);

				if (!$productGroupHandle->Number)
				{
					$productGroupHandle         = new stdclass;
					$productGroupHandle->Number = 1;
				}
			}

			$UnitHandle = $this->getUnitGroup();

			$Handle         = new stdclass;
			$Handle->Number = $d ['product_number'];

			$prdinfo = array
			(
				'Handle'             => $Handle,
				'Number'             => $d ['product_number'],
				'ProductGroupHandle' => $productGroupHandle,
				'Name'               => $d['product_name'],
				'BarCode'            => '',
				'SalesPrice'         => (float) $d ['product_price'],
				'CostPrice'          => (float) $d ['product_price'],
				'RecommendedPrice'   => (float) $d ['product_price'],
				'Description'        => $d ['product_s_desc'],
				'UnitHandle'         => $UnitHandle,
				'Volume'             => $d ['product_volume'],
				'IsAccessible'       => 1,
				'InStock'            => $d['product_stock']
			);

			if ($d['eco_prd_number'] != '')
			{
				$prdinfo['BarCode'] = $this->productGetBarCode($Handle);

				return $this->client->Product_UpdateFromData(array('data' => $prdinfo))->Product_UpdateFromDataResult;
			}

			return $this->client->Product_CreateFromData(array('data' => $prdinfo))->Product_CreateFromDataResult;
		}
		catch (Exception $exception)
		{
			if (Redshop::getConfig()->get('DETAIL_ERROR_MESSAGE_ON'))
			{
				$this->report(__METHOD__, $exception, $prdinfo, $d);
			}
			else
			{
				JError::raiseWarning(21, JText::_('COM_REDSHOP_DETAIL_ERROR_MESSAGE_LBL'));
			}
		}
	}

	/**
	 * Get product barcode information from e-conomic product
	 *
	 * @param   object $productHandle Product Number Handle
	 *
	 * @return  string  Barcode
	 */
	protected function productGetBarCode($productHandle)
	{
		try
		{
			return $this->client->Product_GetBarCode(
				array('productHandle' => $productHandle)
			)->Product_GetBarCodeResult;
		}
		catch (Exception $exception)
		{
			if (Redshop::getConfig()->get('DETAIL_ERROR_MESSAGE_ON'))
			{
				$this->report(__METHOD__, $exception, array('productHandle' => $productHandle), $productHandle);
			}
			else
			{
				JError::raiseWarning(21, JText::_('COM_REDSHOP_DETAIL_ERROR_MESSAGE_LBL'));
			}
		}
	}

	/**
	 * Method to store product group in economic
	 *
	 * @access public
	 * @return array
	 */
	public function storeProductGroup($d)
	{
		if ($this->error)
		{
			return $this->errorMsg;
		}

		try
		{
			$accountHandle         = new stdclass;
			$accountHandle->Number = $d['vataccount'];

			$nonaccountHandle         = new stdclass;
			$nonaccountHandle->Number = $d['novataccount'];

			$Handle         = new stdclass;
			$Handle->Number = $d ['productgroup_id'];

			$prdgrpinfo = array
			(
				'Handle'                                         => $Handle,
				'Number'                                         => $d ['productgroup_id'],
				'Name'                                           => $d['productgroup_name'],
				'AccountForVatLiableDebtorInvoicesCurrentHandle' => $accountHandle,
				'AccountForVatExemptDebtorInvoicesCurrentHandle' => $nonaccountHandle
			);

			if ($d['eco_prdgro_number'] != '')
			{
				$newProductGroupNumber = $this->client->ProductGroup_UpdateFromData(array("data" => $prdgrpinfo))->ProductGroup_UpdateFromDataResult;
			}
			else
			{
				$newProductGroupNumber = $this->client->ProductGroup_CreateFromData(array("data" => $prdgrpinfo))->ProductGroup_CreateFromDataResult;
			}

			return $newProductGroupNumber;
		}
		catch (Exception $exception)
		{
			if (Redshop::getConfig()->get('DETAIL_ERROR_MESSAGE_ON'))
			{
				$this->report(__METHOD__, $exception, $prdgrpinfo, $d);
			}
			else
			{
				JError::raiseWarning(21, JText::_('COM_REDSHOP_DETAIL_ERROR_MESSAGE_LBL'));
			}
		}
	}

	/**
	 * Method to get debtor contact handle
	 *
	 * @access public
	 * @return array
	 */
	public function getDebtorContactHandle($d)
	{
		if ($this->error)
		{
			return $this->errorMsg;
		}

		try
		{
			$contacts = $this->client->DebtorContact_FindByExternalId(array('externalId' => $d ['user_info_id']))->DebtorContact_FindByExternalIdResult->DebtorContactHandle;

			if (count($contacts) > 0)
			{
				$contactHandle = new stdclass;

				if (is_array($contacts))
				{
					for ($i = 0, $in = count($contacts); $i < $in; $i++)
					{
						if ($contacts[$i]->Id)
						{
							$contactHandle->Id = $contacts[$i]->Id;
							break;
						}
					}
				}
				else
				{
					$contactHandle->Id = $contacts->Id;
				}

				$d['updateDebtorContact'] = $contactHandle->Id;
				$this->DebtorContact_GetData($d);
			}
			else
			{
				$contactHandle = $this->storeDebtorContact($d);
			}

			return $contactHandle;
		}
		catch (Exception $exception)
		{
			if (Redshop::getConfig()->get('DETAIL_ERROR_MESSAGE_ON'))
			{
				$this->report(__METHOD__, $exception);
			}
			else
			{
				JError::raiseWarning(21, JText::_('COM_REDSHOP_DETAIL_ERROR_MESSAGE_LBL'));
			}
		}
	}

	/**
	 * Method to get debtor contact handle
	 *
	 * @access public
	 * @return array
	 */
	public function DebtorContact_GetData($d)
	{
		if ($this->error)
		{
			return $this->errorMsg;
		}

		try
		{
			$entityHandle     = new stdclass;
			$entityHandle->Id = $d ['updateDebtorContact'];
			$contacts         = $this->client->DebtorContact_GetData(array('entityHandle' => $entityHandle))->DebtorContact_GetDataResult;
		}
		catch (Exception $exception)
		{
			if (Redshop::getConfig()->get('DETAIL_ERROR_MESSAGE_ON'))
			{
				$this->report(__METHOD__, $exception, array('entityHandle' => $entityHandle));
			}
			else
			{
				JError::raiseWarning(21, JText::_('COM_REDSHOP_DETAIL_ERROR_MESSAGE_LBL'));
			}
		}
	}

	/**
	 * Method to delete debtor contact handle
	 *
	 * @access public
	 * @return array
	 */
	public function DebtorContact_Delete($d)
	{
		if ($this->error)
		{
			return $this->errorMsg;
		}

		try
		{
			$debtorContactHandle     = new stdclass;
			$debtorContactHandle->Id = $d ['user_info_id'];
			$contacts                = $this->client->DebtorContact_Delete(array('debtorContactHandle' => $debtorContactHandle));
		}
		catch (Exception $exception)
		{
			if (Redshop::getConfig()->get('DETAIL_ERROR_MESSAGE_ON'))
			{
				$this->report(__METHOD__, $exception);
			}
			else
			{
				JError::raiseWarning(21, JText::_('COM_REDSHOP_DETAIL_ERROR_MESSAGE_LBL'));
			}
		}
	}

	/**
	 * Method to create debtor contact in economic
	 *
	 * @access public
	 * @return array
	 */
	public function storeDebtorContact($d)
	{
		if ($this->error)
		{
			return $this->errorMsg;
		}

		try
		{
			if (isset($d['updateDebtorContact']) && $d['updateDebtorContact'] != "")
			{
				$Id = $d ['updateDebtorContact'];
			}
			else
			{
				$Id = $d ['user_info_id'];
			}

			$Handle     = new stdclass;
			$Handle->Id = $Id;

			$debtorHandle         = new stdclass;
			$debtorHandle->Number = $d ['debtorHandle'];

			$info = array
			(
				'Handle'                        => $Handle,
				'DebtorHandle'                  => $debtorHandle,
				'Id'                            => $Id,
				'Number'                        => $Id,
				'Name'                          => $d['name'],
				'Email'                         => $d['email'],
				'TelephoneNumber'               => $d['phone'],
				'ExternalId'                    => $d['user_info_id'],
				'IsToReceiveEmailCopyOfOrder'   => 0,
				'IsToReceiveEmailCopyOfInvoice' => 1
			);

			if (isset($d['updateDebtorContact']) && $d['updateDebtorContact'] != "")
			{
				$contactHandle = $this->client->DebtorContact_UpdateFromData(array('data' => $info))->DebtorContact_UpdateFromDataResult;
			}
			else
			{
				$contactHandle = $this->client->DebtorContact_CreateFromData(array('data' => $info))->DebtorContact_CreateFromDataResult;
			}

			return $contactHandle;
		}
		catch (Exception $exception)
		{
			if (Redshop::getConfig()->get('DETAIL_ERROR_MESSAGE_ON'))
			{
				$this->report(__METHOD__, $exception);
			}
			else
			{
				JError::raiseWarning(21, JText::_('COM_REDSHOP_DETAIL_ERROR_MESSAGE_LBL'));
			}
		}
	}

	/**
	 * Method to create invoice in economic
	 *
	 * @access public
	 * @return array
	 */
	public function createInvoice($d)
	{
		if ($this->error)
		{
			return $this->errorMsg;
		}

		$CurrencyHandle       = new stdclass;
		$CurrencyHandle->Code = $d ['currency_code'];

		$TermOfPaymentHandle     = new stdclass;
		$TermOfPaymentHandle->Id = $this->getTermOfPayment($d);

		$debtorHandle         = new stdclass;
		$debtorHandle->Number = $d['debtorHandle'];

		try
		{
			if (isset($d['setAttname']) && $d['setAttname'] == 1)
			{
				$debtorContactHandle = $this->getDebtorContactHandle($d);

				$this->client->Debtor_SetAttention(array('debtorHandle' => $debtorHandle, 'valueHandle' => $debtorContactHandle));
			}

			$this->client->Debtor_SetVatZone(array('debtorHandle' => $debtorHandle, 'value' => $d['vatzone']));

			$invoiceHandle = $this->client->CurrentInvoice_Create(array('debtorHandle' => $debtorHandle))->CurrentInvoice_CreateResult;

			$this->client->CurrentInvoice_SetCurrency(array('currentInvoiceHandle' => $invoiceHandle, 'valueHandle' => $CurrencyHandle));

			$this->client->CurrentInvoice_SetTermOfPayment(array('currentInvoiceHandle' => $invoiceHandle, 'valueHandle' => $TermOfPaymentHandle));

			$this->client->CurrentInvoice_SetIsVatIncluded(array('currentInvoiceHandle' => $invoiceHandle, 'value' => $d['isvat']));

			$this->client->CurrentInvoice_SetOtherReference(array('currentInvoiceHandle' => $invoiceHandle, 'value' => $d['order_number']));

			// Get Employee to set Our Reference Number
			if ($employeeHandle = $this->employeeFindByNumber($d))
			{
				$this->client->CurrentInvoice_SetOurReference2(
					array(
						'currentInvoiceHandle' => $invoiceHandle,
						'valueHandle'          => $employeeHandle
					)
				);
			}

			$reference = '';

			if (isset($d['order_number']) && $d['order_number'] != "")
			{
				$reference .= JText::_('COM_REDSHOP_ORDER_NUMBER') . ': ' . $d['order_number'];
			}

			if (isset($d['requisition_number']) && $d['requisition_number'] != "")
			{
				$reference .= chr(13) . '' . JText::_('COM_REDSHOP_REQUISITION_NUMBER') . ': ' . $d['requisition_number'];
			}

			if (isset($d['customer_note']) && $d['customer_note'] != "")
			{
				$reference .= chr(13) . '' . JText::_('COM_REDSHOP_CUSTOMER_NOTE_LBL') . ': ' . $d['customer_note'];
			}

			$this->client->CurrentInvoice_SetTextLine1(array('currentInvoiceHandle' => $invoiceHandle, 'value' => $reference));

			return $invoiceHandle;
		}
		catch (Exception $exception)
		{
			if (Redshop::getConfig()->get('DETAIL_ERROR_MESSAGE_ON'))
			{
				$this->report(__METHOD__, $exception);
			}
			else
			{
				JError::raiseWarning(21, JText::_('COM_REDSHOP_DETAIL_ERROR_MESSAGE_LBL'));
			}
		}
	}

	public function deleteInvoice($d)
	{
		if ($this->error)
		{
			return $this->errorMsg;
		}

		$invoiceHandle     = new stdclass;
		$invoiceHandle->Id = $d ['invoiceHandle'];

		try
		{
			$this->client->CurrentInvoice_Delete(array('currentInvoiceHandle' => $invoiceHandle));
		}
		catch (Exception $exception)
		{
			if (Redshop::getConfig()->get('DETAIL_ERROR_MESSAGE_ON'))
			{
				$this->report(__METHOD__, $exception, $invoiceHandle);
			}
		}
	}

	/**
	 * Method to set Delivery Address in economic
	 *
	 * @access public
	 * @return array
	 */
	public function setDeliveryAddress($d)
	{
		if ($this->error)
		{
			return $this->errorMsg;
		}

		try
		{
			$invoiceHandle     = new stdclass;
			$invoiceHandle->Id = $d['invoiceHandle'];

			if ($d ['address_ST'] != '')
			{
				$this->client
					->CurrentInvoice_SetDeliveryAddress(array('currentInvoiceHandle' => $invoiceHandle, 'value' => $d ['address_ST']));
			}

			if ($d ['name_ST'] != '')
			{
			}

			if ($d ['city_ST'] != '')
			{
				$this->client
					->CurrentInvoice_SetDeliveryCity(array('currentInvoiceHandle' => $invoiceHandle, 'value' => $d ['city_ST']));
			}

			if ($d ['country_ST'] != '')
			{
				$this->client
					->CurrentInvoice_SetDeliveryCountry(array('currentInvoiceHandle' => $invoiceHandle, 'value' => $d ['country_ST']));
			}

			if ($d ['zipcode_ST'] != '')
			{
				$this->client
					->CurrentInvoice_SetDeliveryPostalCode(array('currentInvoiceHandle' => $invoiceHandle, 'value' => $d ['zipcode_ST']));
			}
		}
		catch (Exception $exception)
		{
			if (Redshop::getConfig()->get('DETAIL_ERROR_MESSAGE_ON'))
			{
				$this->report(__METHOD__, $exception);
			}
			else
			{
				JError::raiseWarning(21, JText::_('COM_REDSHOP_DETAIL_ERROR_MESSAGE_LBL'));
			}
		}
	}

	/**
	 * Method to create invoice line in economic
	 *
	 * @access public
	 * @return array
	 */
	public function createInvoiceLine($d)
	{
		if ($this->error)
		{
			return $this->errorMsg;
		}

		$order_item_id     = $d ['order_item_id'];
		$invoiceHandle     = new stdclass;
		$invoiceHandle->Id = $d['invoiceHandle'];

		$Handle         = new stdclass;
		$Handle->Id     = $d['invoiceHandle'];
		$Handle->Number = $order_item_id;

		$UnitHandle = $this->getUnitGroup();

		$ProductHandle         = new stdclass;
		$ProductHandle->Number = $d ['product_number'];

		try
		{
			$info = array
			(
				'Handle'            => $Handle,
				'InvoiceHandle'     => $invoiceHandle,
				'Number'            => $order_item_id,
				'Id'                => $d['invoiceHandle'],
				'Description'       => $d ['product_name'],
				'DeliveryDate'      => $d ['delivery_date'],
				'UnitHandle'        => $UnitHandle,
				'ProductHandle'     => $ProductHandle,
				'UnitNetPrice'      => $d['product_price'],
				'Quantity'          => $d['product_quantity'],
				'DiscountAsPercent' => 0,
				'UnitCostPrice'     => $d['product_price'],
				'TotalMargin'       => $d['product_price'],
				'TotalNetAmount'    => $d['product_price'],
				'MarginAsPercent'   => 1
			);

			if (isset($d['updateInvoice']) && $d['updateInvoice'] == 1)
			{
				$invoiceLineNumber = $this->client
					->CurrentInvoiceLine_UpdateFromData(array('data' => $info))->CurrentInvoiceLine_UpdateFromDataResult;
			}
			else
			{
				$invoiceLineNumber = $this->client
					->CurrentInvoiceLine_CreateFromData(array('data' => $info))->CurrentInvoiceLine_CreateFromDataResult;
			}

			return $invoiceLineNumber;
		}
		catch (Exception $exception)
		{
			if (Redshop::getConfig()->get('DETAIL_ERROR_MESSAGE_ON'))
			{
				$this->report(__METHOD__, $exception);
			}
			else
			{
				JError::raiseWarning(21, JText::_('COM_REDSHOP_DETAIL_ERROR_MESSAGE_LBL'));
			}
		}
	}

	/**
	 * Method to create invoice Array in economic.
	 *
	 * @param $d
	 * @param $darray
	 *
	 * @return null|string
	 */
	public function createInvoiceLineArray($d, $darray)
	{
		if ($this->error)
		{
			return $this->errorMsg;
		}

		$order_item_id     = $d ['order_item_id'];
		$invoiceHandle     = new stdclass;
		$invoiceHandle->Id = $d['invoiceHandle'];

		$Handle         = new stdclass;
		$Handle->Id     = $d['invoiceHandle'];
		$Handle->Number = $order_item_id;

		$UnitHandle = $this->getUnitGroup();

		try
		{
			for ($i = 0, $in = count($darray); $i < $in; $i++)
			{
				$ProductHandle         = new stdclass;
				$ProductHandle->Number = $darray[$i]['product_number'];
				$info[]                = array(
					'CurrentInvoiceLineData' => array
					(
						'Handle'            => $Handle,
						'InvoiceHandle'     => $invoiceHandle,
						'Number'            => $order_item_id,
						'Id'                => $d['invoiceHandle'],
						'Description'       => $darray[$i]['product_name'],
						'DeliveryDate'      => $darray[$i]['delivery_date'],
						'UnitHandle'        => $UnitHandle,
						'ProductHandle'     => $ProductHandle,
						'UnitNetPrice'      => $darray[$i]['product_price'],
						'Quantity'          => $darray[$i]['product_quantity'],
						'DiscountAsPercent' => 0,
						'UnitCostPrice'     => $darray[$i]['product_price'],
						'TotalMargin'       => $darray[$i]['product_price'],
						'TotalNetAmount'    => $darray[$i]['product_price'],
						'MarginAsPercent'   => 1
					)
				);
			}

			if (isset($d['updateInvoice']) && $d['updateInvoice'] == 1)
			{
				$invoiceLineNumber = $this->client
					->CurrentInvoiceLine_UpdateFromDataArray(array('dataArray' => $info))->CurrentInvoiceLine_UpdateFromDataArrayResult;
			}
			else
			{
				$invoiceLineNumber = $this->client
					->CurrentInvoiceLine_CreateFromDataArray(array('dataArray' => $info))->CurrentInvoiceLine_CreateFromDataArrayResult;
			}

			return $invoiceLineNumber;
		}
		catch (Exception $exception)
		{
			if (Redshop::getConfig()->get('DETAIL_ERROR_MESSAGE_ON'))
			{
				$this->report(__METHOD__, $exception);
			}
			else
			{
				JError::raiseWarning(21, JText::_('COM_REDSHOP_DETAIL_ERROR_MESSAGE_LBL'));
			}
		}
	}

	/**
	 * Method to check invoice draft in economic
	 *
	 * @access public
	 * @return array
	 */
	public function checkDraftInvoice($d)
	{
		if ($this->error)
		{
			return $this->errorMsg;
		}

		$invoiceHandle     = new stdclass;
		$invoiceHandle->Id = $d['invoiceHandle'];

		try
		{
			$invoiceData = $this->client->CurrentInvoice_GetData(array('entityHandle' => $invoiceHandle))->CurrentInvoice_GetDataResult;

			return $invoiceData;
		}
		catch (Exception $exception)
		{
			if (Redshop::getConfig()->get('DETAIL_ERROR_MESSAGE_ON'))
			{
				$this->report(__METHOD__, $exception);
			}
			else
			{
				JError::raiseWarning(21, JText::_('COM_REDSHOP_DETAIL_ERROR_MESSAGE_LBL'));
			}
		}
	}

	/**
	 * Method to check invoice booked in economic
	 *
	 * @access public
	 * @return array
	 */
	public function checkBookInvoice($d)
	{
		if ($this->error)
		{
			return $this->errorMsg;
		}

		try
		{
			$bookInvoiceData = $this->client->Invoice_FindByOtherReference(array('otherReference' => $d['order_number']))->Invoice_FindByOtherReferenceResult;

			return $bookInvoiceData;
		}
		catch (Exception $exception)
		{
			if (Redshop::getConfig()->get('DETAIL_ERROR_MESSAGE_ON'))
			{
				$this->report(__METHOD__, $exception);
			}
			else
			{
				JError::raiseWarning(21, JText::_('COM_REDSHOP_DETAIL_ERROR_MESSAGE_LBL'));
			}
		}
	}

	/**
	 * Method to update invoice in economic
	 *
	 * @access public
	 * @return array
	 */
	public function updateInvoiceDate($d)
	{
		if ($this->error)
		{
			return $this->errorMsg;
		}

		$invoiceHandle     = new stdclass;
		$invoiceHandle->Id = $d['invoiceHandle'];

		try
		{
			$invoiceNumber = $this->client
				->CurrentInvoice_SetDate(array('currentInvoiceHandle' => $invoiceHandle, 'value' => $d['invoiceDate']))
				->CurrentInvoice_SetDateResponse;

			return $invoiceNumber;
		}
		catch (Exception $exception)
		{
			if (Redshop::getConfig()->get('DETAIL_ERROR_MESSAGE_ON'))
			{
				$this->report(__METHOD__, $exception);
			}
			else
			{
				JError::raiseWarning(21, JText::_('COM_REDSHOP_DETAIL_ERROR_MESSAGE_LBL'));
			}
		}
	}

	/**
	 * Method to book invoice
	 *
	 * @access public
	 * @return array
	 */
	public function bookInvoice($d)
	{
		if ($this->error)
		{
			return $this->errorMsg;
		}

		// Send pdf from economic
		$pdf = '';

		$bookHandle         = new stdclass;
		$bookHandle->Number = $d['bookinvoice_number'];

		if ($bookHandle)
		{
			$pdf = $this->Invoice_GetPdf($bookHandle);

			// Cashbook entry
			$makeCashbook = (int) $this->params->get('economicUseCashbook', 1);

			if ($makeCashbook && $d['amount'] > 0)
			{
				$this->createCashbookEntry($d, $bookHandle);
			}

			// Cashbook Entry for Creditor Payment to Paypal
			if ($makeCashbook && isset($d['order_transfee']) && $this->params->get('economicCreditorNumber', false))
			{
				$this->createCashbookEntryCreditorPayment($d, $bookHandle);
			}
		}

		return $pdf;
	}

	/**
	 * Method to find current book invoice number in economic.
	 *
	 * @param $d
	 *
	 * @return null|string
	 */
	public function CurrentInvoice_BookWithNumber($d)
	{
		if ($this->error)
		{
			return $this->errorMsg;
		}

		$invoiceHandle     = new stdclass;
		$invoiceHandle->Id = $d['invoiceHandle'];

		try
		{
			$info = array(
				'currentInvoiceHandle' => $invoiceHandle,
				'number'               => $d['order_number']
			);

			$bookHandle = $this->client->CurrentInvoice_BookWithNumber($info)->CurrentInvoice_BookWithNumberResult;

			return $bookHandle;
		}
		catch (Exception $exception)
		{
			if (Redshop::getConfig()->get('DETAIL_ERROR_MESSAGE_ON'))
			{
				$this->report(__METHOD__, $exception);
			}
			else
			{
				JError::raiseWarning(21, JText::_('COM_REDSHOP_DETAIL_ERROR_MESSAGE_LBL'));
			}
		}
	}

	/**
	 * Method to find current book invoice in economic
	 *
	 * @access public
	 * @return array
	 */
	public function CurrentInvoice_Book($d)
	{
		if ($this->error)
		{
			return $this->errorMsg;
		}

		$invoiceHandle     = new stdclass;
		$invoiceHandle->Id = $d['invoiceHandle'];

		try
		{
			$info = array(
				'currentInvoiceHandle' => $invoiceHandle,
			);

			$bookHandle = $this->client->CurrentInvoice_Book($info)->CurrentInvoice_BookResult;

			return $bookHandle;
		}
		catch (Exception $exception)
		{
			if (Redshop::getConfig()->get('DETAIL_ERROR_MESSAGE_ON'))
			{
				$this->report(__METHOD__, $exception);
			}
			else
			{
				JError::raiseWarning(21, JText::_('COM_REDSHOP_DETAIL_ERROR_MESSAGE_LBL'));
			}
		}
	}

	/**
	 * Method to get pdf invoice
	 *
	 * @access public
	 * @return array
	 */
	public function Invoice_GetPdf($invoiceHandle)
	{
		if ($this->error)
		{
			return $this->errorMsg;
		}

		try
		{
			$pdf = $this->client->Invoice_GetPdf(array('invoiceHandle' => $invoiceHandle))->Invoice_GetPdfResult;

			return $pdf;
		}
		catch (Exception $exception)
		{
			if (Redshop::getConfig()->get('DETAIL_ERROR_MESSAGE_ON'))
			{
				$this->report(__METHOD__, $exception);
			}
			else
			{
				JError::raiseWarning(21, JText::_('COM_REDSHOP_DETAIL_ERROR_MESSAGE_LBL'));
			}
		}
	}

	/**
	 * Method to create cash book entry in economic
	 *
	 * @access public
	 * @return array
	 */
	public function createCashbookEntry($d, $bookHandle)
	{
		// Cashbook entry
		$makeCashbook = (int) $this->params->get('economicUseCashbook', 1);

		if (!$makeCashbook)
		{
			return;
		}

		if ($this->error)
		{
			return $this->errorMsg;
		}

		$cashbooknumber = intval($this->getCashBookAll());
		$contraaccount  = intval($this->getTermOfPaymentContraAccount($d));

		$cashBookHandle         = new stdclass;
		$cashBookHandle->Number = $cashbooknumber;

		$debtorHandle         = new stdclass;
		$debtorHandle->Number = $d['debtorHandle'];

		$contraAccountHandle         = new stdclass;
		$contraAccountHandle->Number = $contraaccount;

		$CurrencyHandle       = new stdclass;
		$CurrencyHandle->Code = $d ['currency_code'];

		try
		{
			if ($contraaccount)
			{
				$info = array(
					'cashBookHandle'      => $cashBookHandle,
					'debtorHandle'        => $debtorHandle,
					'contraAccountHandle' => $contraAccountHandle
				);
			}
			else
			{
				$info = array(
					'cashBookHandle' => $cashBookHandle,
					'debtorHandle'   => $debtorHandle
				);
			}

			$cashBookEntryHandle = $this->client->CashBookEntry_CreateDebtorPayment($info)->CashBookEntry_CreateDebtorPaymentResult;

			$this->client->CashBookEntry_SetAmount(array('cashBookEntryHandle' => $cashBookEntryHandle, 'value' => (0 - $d ['amount'])));

			$this->client->CashBookEntry_SetDebtorInvoiceNumber(array('cashBookEntryHandle' => $cashBookEntryHandle, 'value' => $bookHandle->Number));

			$this->client
				->CashBookEntry_SetText(
					array(
						'cashBookEntryHandle' => $cashBookEntryHandle,
						'value'               => 'INV (' . $bookHandle->Number . ') ORDERID (' . $d ['order_id'] . ') CUST (' . $d['name'] . ')'
					)
				);

			$this->client->CashBookEntry_SetCurrency(array('cashBookEntryHandle' => $cashBookEntryHandle, 'valueHandle' => $CurrencyHandle));

			$this->client->CashBook_Book(array('cashBookHandle' => $cashBookHandle));
		}
		catch (Exception $exception)
		{
			if (Redshop::getConfig()->get('DETAIL_ERROR_MESSAGE_ON'))
			{
				$this->report(__METHOD__, $exception);
			}
			else
			{
				JError::raiseWarning(21, JText::_('COM_REDSHOP_DETAIL_ERROR_MESSAGE_LBL'));
			}
		}
	}

	/**
	 * Method to create cash book entry in economic for Merchant Fees
	 *
	 * @param   array  $d          Information about booking invoice
	 * @param   Object $bookHandle SOAP Object of the e-conomic current book invoice
	 *
	 * @return  void
	 */
	public function createCashbookEntryCreditorPayment($d, $bookHandle)
	{
		if ($this->error)
		{
			return $this->errorMsg;
		}

		$cashBookHandle         = new stdclass;
		$cashBookHandle->Number = intval($this->getCashBookAll());

		$debtorHandle         = new stdclass;
		$debtorHandle->Number = $this->params->get('economicCreditorNumber');

		$contraaccount = intval($this->getTermOfPaymentContraAccount($d));

		$contraAccountHandle         = new stdclass;
		$contraAccountHandle->Number = $contraaccount;

		$CurrencyHandle       = new stdclass;
		$CurrencyHandle->Code = $d ['currency_code'];

		try
		{
			if ($contraaccount)
			{
				$info = array(
					'cashBookHandle'      => $cashBookHandle,
					'creditorHandle'      => $debtorHandle,
					'contraAccountHandle' => $contraAccountHandle
				);
			}
			else
			{
				$info = array(
					'cashBookHandle' => $cashBookHandle,
					'creditorHandle' => $debtorHandle
				);
			}

			$cashBookEntryHandle = $this->client->CashBookEntry_CreateCreditorPayment($info)
				->CashBookEntry_CreateCreditorPaymentResult;

			$this->client->CashBookEntry_SetAmount(
				array(
					'cashBookEntryHandle' => $cashBookEntryHandle,
					'value'               => $d['order_transfee']
				)
			);

			$this->client->CashBookEntry_SetCreditor(
				array(
					'cashBookEntryHandle' => $cashBookEntryHandle,
					'valueHandle'         => $debtorHandle
				)
			);

			$this->client->CashBookEntry_SetText(
				array(
					'cashBookEntryHandle' => $cashBookEntryHandle,
					'value'               => JText::_('COM_REDSHOP_ECONOMIC_CREDITOR_TEXT')
				)
			);

			$this->client->CashBookEntry_SetCurrency(
				array(
					'cashBookEntryHandle' => $cashBookEntryHandle,
					'valueHandle'         => $CurrencyHandle
				)
			);

			$this->client->CashBook_Book(
				array(
					'cashBookHandle' => $cashBookHandle
				)
			);
		}
		catch (Exception $exception)
		{
			if (Redshop::getConfig()->get('DETAIL_ERROR_MESSAGE_ON'))
			{
				$this->report(__METHOD__, $exception);
			}
			else
			{
				JError::raiseWarning(21, JText::_('COM_REDSHOP_DETAIL_ERROR_MESSAGE_LBL'));
			}
		}
	}

	/**
	 * Method for send report
	 *
	 * @param   Exception $exception Exception throw from E-conomic
	 * @param   array     $soapData  Soap prepared data
	 * @param   array     $data      List of data.
	 *
	 * @return  boolean                True on success. False otherwise
	 * @throws  Exception
	 */
	private function report($method, $exception = null, $soapData = array(), $data = array())
	{
		$app      = JFactory::getApplication();
		$from     = $app->get('mailfrom', '');
		$fromName = $app->get('fromname', '');
		$email    = Redshop::getConfig()->get('ADMINISTRATOR_EMAIL', $from);
		$subject  = 'E-conomic error';
		$data     = (array) $data;
		$soapData = (array) $soapData;

		$body = '<p>Hi ' . $fromName . '</p>'
			. '<p>There are an error from Economic: </p>'
			. '<blockquote>' . $exception->getMessage() . '</blockquote>'
			. '<p>On method: ' . $method . '</p>';

		if (!empty($data))
		{
			$body .= '<hr /><h4>Data</h4>'
				. '<table style="width: 100%;">'
				. '<thead><tr><th>Key</th><th>Value</th></tr></thead><tbody>';

			foreach ($data as $key => $value)
			{
				$body .= '<tr><td>' . $key . '</td>'
					. '<td>' . (is_array($value) || is_object($value) ? json_encode($value) : $value) . '</td></tr>';
			}

			$body .= '</tbody>'
				. '</table>';
		}

		if (!empty($soapData))
		{
			$body .= '<hr /><h4>SOAP data</h4>'
				. '<table style="width: 100%;">'
				. '<thead><tr><th>Key</th><th>Value</th></tr></thead><tbody>';

			foreach ($soapData as $key => $value)
			{
				$body .= '<tr><td>' . $key . '</td>'
					. '<td>' . (is_array($value) || is_object($value) ? json_encode($value) : $value) . '</td></tr>';
			}

			$body .= '</tbody></table>';
		}

		return RedshopHelperMail::sendEmail($from, $fromName, $email, $subject, $body, true, null, '', null, 'economic');
	}
}
