<?php

/**
 * @package     RedSHOP
 * @subpackage  Plugin
 *
 * @copyright   Copyright (C) 2008 - 2021 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
require_once __DIR__.'/library/api/ahamoveAPI.php';

use Ahamove\Api\Request;
use Joomla\CMS\Log\Log;

class PlgRedshop_shippingAhamove extends JPlugin

{
	const PRODUCTION_URL = "https://api.ahamove.com";
	const SANDBOX_URL = "https://apistg.ahamove.com";
	const PAYMENT_METHOD = "CASH";
	
	/**
	 * Application object
	 *
	 * @var    JApplicationCms
	 * @since  3.2
	 */
	protected $app;
	
	/**
	 * Database object
	 *
	 * @var    JDatabaseDriver
	 * @since  3.2
	 */
	protected $db;
	
	/**
	 * Affects constructor behavior. If true, language files will be loaded automatically.
	 *
	 * @var    boolean
	 * @since  3.2
	 */
	protected $autoloadLanguage = true;
	
	/**
	 * @var string Gmap API key
	 */
	private $_gmapApiKey;
	
	/**
	 * @var string API key
	 */
	private $_apiKey;
	
	/**
	 * @var string The url endpoint (depending on the usage environment: Production/ Sandbox)
	 */
	private $_endpoint;
	
	/**
	 * @var string The country code must match with X-LLM-Country in the request headers
	 */
	private $_region;
	
	/**
	 * Constructor
	 *
	 * @param   object  $subject  The object to observe
	 * @param   array   $config   An array that holds the plugin configuration
	 */
	public function __construct(&$subject, $config)
	{
		if (JFactory::getApplication()->isClient('admin'))
		{
			return;
		}
		
		parent::__construct($subject, $config);
		
		$this->_gmapApiKey = $this->params->get('gmap_api_key', '');
		$this->_apiKey     = $this->params->get('api_key', '');
		$this->_endpoint   = $this->params->get('environment') == 'sandbox' ? self::SANDBOX_URL : self::PRODUCTION_URL;
		$requester         = $this->getRequesterContactInfo();
		$this->_region     = $requester['region'];
		$oneStepCheckout   = Redshop::getConfig()->get('ONESTEP_CHECKOUT_ENABLE');

		if (empty($this->_apiKey) || empty($this->_gmapApiKey)
			|| $this->app->input->getString('option') !== 'com_redshop')
		{
			return;
		}
		
		$this->addTranslatedStringsToJS();
		
		$document = JFactory::getDocument();
		
		$document->addScriptDeclaration(
			"var AHAMOVE = {}; AHAMOVE.requester_contact = ".
			json_encode($this->params->get('requesters_store_owner')).";
		AHAMOVE.config={isChecked : 1};
		redSHOP.RSConfig.load({ONESTEP_CHECKOUT_ENABLE: '" . $oneStepCheckout . "'});
		"
		);
		
		if ($this->app->input->getString('view') !== 'order_detail')
		{
			$document->addScript('https://maps.googleapis.com/maps/api/js?key='.$this->_gmapApiKey.'&libraries=places');
		}
		
		$document->addScript(JUri::root().'plugins/redshop_shipping/ahamove/assets/jquery.colorbox.js');
		
		$session        = JFactory::getSession();
		$rsuser_session = $session->get('rs_user');
		$auth           = $session->get('auth');
		
		if ($this->app->input->getString('view') === 'checkout')
		{
			if ((int) $rsuser_session['rs_user_info_id'] === 0 && empty($auth['users_info_id']))
			{
				$document->addScript(JUri::root().'plugins/redshop_shipping/ahamove/assets/checkout_newuser.js');
			}
			
			elseif (!empty($auth['users_info_id']) || $rsuser_session['rs_user_info_id'])
			{
				$document->addScript(JUri::root().'plugins/redshop_shipping/ahamove/assets/checkout_existinguser.js');
			}
		}
		elseif (in_array($this->app->input->getString('view'), array('account_billto', 'account_shipto')))
		{
			$document->addScript(JUri::root().'plugins/redshop_shipping/ahamove/assets/account_edit.js');
		}
	}
	
	public function getAccessToken($data)
	{
		$data          = 'mobile='.$data['phone'].'&name='.$data['name'].'&api_key='.$this->_apiKey.'&address='.$data['address'].'&lat='.$data['location']['lat'].'&lng='.$data['location']['lng'];
		$request       = new Request();
		$request->path = "/v1/partner/register_account";
		$request->body = $data;
		$request->host = $this->_endpoint;
		
		return json_decode($request->send()->getBody(), true);
	}
	
	public function getOrderItems($orderItems)
	{
		$data = array();
		
		foreach ($orderItems as $i => $item)
		{
			$data[$i]->name  = $item->order_item_name;
			$data[$i]->num   = (float) $item->product_quantity;
			$data[$i]->price = (float) $item->product_final_price;
		}
		
		return json_encode($data);
	}
	
	public function getCustomFieldsMapping()
	{
		return array(
			'rs_geocoding_location'    => RedshopHelperExtrafields::getField('rs_geocoding_location')->id,
			'rs_geocoding_location_ST' => RedshopHelperExtrafields::getField('rs_geocoding_location_ST')->id,
			'rs_address_remark'        => RedshopHelperExtrafields::getField('rs_address_remark')->id,
			'rs_address_remark_ST'     => RedshopHelperExtrafields::getField('rs_address_remark_ST')->id
		);
	}
	
	public function addTranslatedStringsToJS()
	{
		JText::script('COM_REDSHOP_ADDRESS_NOTE_PLACEHOLDER');
		JText::script('PLG_REDSHOP_SHIPPING_GETQUOTATION_ERROR_GENERAL_MESSAGE');
		JText::script('PLG_REDSHOP_SHIPPING_CAN_NOT_VERIFY_YOUR_ADDRESS');
		JText::script('PLG_REDSHOP_SHIPPING_INVALID_SHIPPING_RATE');
		JText::script('PLG_REDSHOP_SHIPPING_GETQUOTATION_ERROR_INVALID_PHONE_NUMBER');
	}
	
	public function onAjaxShowMap()
	{
		\Redshop\Helper\Ajax::validateAjaxRequest('get');
		
		$getParams   = $this->app->input->get->getArray();
		$usersInfoId = (int) $getParams['users_info_id'];
		
		$address                 = $this->getAddressExistingCustomer($usersInfoId);
		$address['full_address'] = $address['address'];
		
		$address_note_field_name    = ($address['address_type'] == 'BT') ? 'rs_address_remark' : 'rs_address_remark_ST';
		$address_note_field_section = ($address['address_type'] == 'BT') ? RedshopHelperExtrafields::SECTION_PRIVATE_BILLING_ADDRESS
			: RedshopHelperExtrafields::SECTION_PRIVATE_SHIPPING_ADDRESS;
		$address_note_data          = RedshopHelperExtrafields::getDataByName($address_note_field_name,
			$address_note_field_section, $address['users_info_id']);
		$address['address_note']    = $address_note_data ? $address_note_data->data_txt : "";

		echo RedshopLayoutHelper::render(
			'mapButton',
			array(
				'usersInfoId'      => $usersInfoId,
				'address'          => $address,
				'requesterContact' => $this->getRequesterContactInfo($address['state_code']),
				'gmapApiKey'       => $this->_gmapApiKey
			),
			JPATH_PLUGINS.'/redshop_shipping/ahamove/layouts'
		);
	}
	
	public function getAddressExistingCustomer($usersInfoId)
	{
		$customfields = $this->getCustomFieldsMapping();
		
		$query = $this->db->getQuery(true)
			->select('ru.*, CONCAT(ru.firstname, " ", ru.lastname) AS full_name, fd1.data_txt as geocoding_location,
		fd2.data_txt AS address_note')
			->from($this->db->qn('#__redshop_users_info', 'ru'))
			->leftjoin($this->db->qn('#__redshop_fields_data', 'fd1')
				.' ON ru.users_info_id = fd1.itemid AND fd1.fieldid IN('.$customfields["rs_geocoding_location"].','.
				$customfields["rs_geocoding_location_ST"].')')
			->leftjoin($this->db->qn('#__redshop_fields_data', 'fd2')
				.' ON ru.users_info_id = fd2.itemid AND fd2.fieldid IN('.$customfields["rs_address_remark"].','.
				$customfields["rs_address_remark_ST"].')')
			->where($this->db->qn('ru.users_info_id').' = '.$this->db->q($usersInfoId));
		
		$address = $this->db->setQuery($query)->loadAssoc();
		
		return $address;
	}
	
	public function makeDeliveryInfoRemark($cart, $customer, $orderId = 0)
	{
		$remark = "ORDER #".($orderId > 0 ? $orderId : '');
		$no     = 1;
		
		foreach ($cart as $i => $item)
		{
			if (!is_numeric($i))
			{
				continue;
			}
			
			$productId = is_object($item) ? $item->product_id : $item['product_Out of service - Lalamove can not deliver products to your provided addressid'];
			$quantity  = is_object($item) ? $item->product_quantity : $item['quantity'];
			
			$product = \Redshop\Product\Product::getProductById($productId);
			$remark  .= "\n\r $no. $product->product_name (x $quantity)";
			$no++;
		}
		
		if (!empty($customer['location']['address_remark']))
		{
			$remark .= "\n\r Address note: ".$customer['location']['address_remark'];
		}
		
		return $remark;
	}
	
	public function onAjaxAhamoveUpdateShippingAddress()
	{
		\Redshop\Helper\Ajax::validateAjaxRequest();
		
		$db                = $this->db;
		$redshopUserInfoId = $this->app->input->post->getInt('users_info_id', 0);
		$newAddress        = $this->app->input->post->getString('saving_address', '');
		$location          = $this->app->input->post->getString('saving_location', '');
		$addressType       = $this->app->input->post->getString('address_type', 'BT');
		
		$query1 = $db->getQuery(true)
			->update($db->qn('#__redshop_users_info'))
			->set($db->qn('address').' = '.$db->quote($newAddress))
			->where($db->qn('users_info_id').' = '.$db->quote($redshopUserInfoId));
		
		$locationField        = RedshopEntityField::getInstanceByField('name',
			($addressType == 'BT' ? 'rs_geocoding_location' : 'rs_geocoding_location_ST'));
		$locationFieldSection = $addressType == 'BT' ? RedshopHelperExtrafields::SECTION_PRIVATE_BILLING_ADDRESS :
			RedshopHelperExtrafields::SECTION_PRIVATE_SHIPPING_ADDRESS;
		
		/** @var RedshopTableField_Data $subPropertyTable */
		JTable::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_redshop/tables');
		
		$tableFieldData = JTable::getInstance('Field_Data', 'RedshopTable');
		
		if (!$tableFieldData->load(array(
			'fieldid' => $locationField->get('id'),
			'itemid'  => $redshopUserInfoId,
			'section' => $locationFieldSection
		)))
		{
			$tableFieldData->set('id', null);
			$tableFieldData->set('fieldid', $locationField->get('id'));
			$tableFieldData->set('itemid', $redshopUserInfoId);
			$tableFieldData->set('section', $locationFieldSection);
		}
		
		$tableFieldData->set('data_txt', $location);
		
		$result = array(
			'redshop_users_info_id'   => $redshopUserInfoId,
			'update_address_success'  => false,
			'update_location_success' => false
		);
		
		if ($db->setQuery($query1)->execute())
		{
			$result['update_address_success'] = true;
		}
		
		if ($tableFieldData->store())
		{
			$result['update_location_success'] = true;
		}
		
		return json_encode($result);
	}
	
	public function getRequesterContactInfo($state2Code = 'SG')
	{
		$requesterFiltered = null;
		foreach ((array) $this->params->get('requesters_store_owner') as $index => $requester)
		{
			if ((string) $requester->state === $state2Code)
			{
				$requesterFiltered = $requester;
				break;
			}
		}
		if (!is_object($requesterFiltered))
		{
			return false;
		}
		
		$latAndlngOfLocation = explode(',', $requesterFiltered->location_store);
		if (!$latAndlngOfLocation)
		{
			return false;
		}
		return array(
			'name'        => $requesterFiltered->name,
			'phone'       => $requesterFiltered->phone,
			'location'    => array(
				'lat' => $latAndlngOfLocation[0],
				'lng' => $latAndlngOfLocation[1],
                'radius' => $requesterFiltered->location_bound->radius
			),
			'address'     => $requesterFiltered->address,
			'state'       => $requesterFiltered->state,
			'region'      => $requesterFiltered->regions,
			'serviceType' => $requesterFiltered->service_types
		);
	}
	
	public function onAjaxAhamoveGetShippingFee()
	{
		\Redshop\Helper\Ajax::validateAjaxRequest();
		
		$cart             = RedshopHelperCartSession::getCart();
		$customer         = $this->app->input->post->get('customer', array(), 'array');
		$deliverySchedule = $this->app->input->post->get('delivery_schedule', array(), 'array');
		$usersInfoId      = $this->app->input->post->getInt('users_info_id', 0);
		$remarks          = $this->makeDeliveryInfoRemark($cart, $customer);
		$state2Code       = $this->app->input->post->getString('stateSelected', 'SG');
		$requester        = $this->getRequesterContactInfo($state2Code);
		$serviceType      = $requester['serviceType'];
		$accessToken      = $this->getAccessToken($requester)['token'];
		if ($usersInfoId > 0)
		{
			$address             = $this->getAddressExistingCustomer($usersInfoId);
			$customer['address'] = $address['address'];
			$customer['name']    = $address['full_name'];
			$customer['phone']   = $address['phone'];
		}
		
		$deliverySchedule = $this->convertScheduleTimeToUnix($deliverySchedule);
		
		
		$shippingRates = array();
		
		$body = $this->prepareRequestBodyForEstimateOrderFeeAndCreateOrder($serviceType, $requester, $customer,
			$remarks, $deliverySchedule);
		
		$request  = new \Ahamove\Api\AhamoveAPI($this->_endpoint, $accessToken);
		$response = $request->estimateOrderFee($body);
		$shipping = RedshopHelperShipping::getShippingMethodByClass('ahamove');
		
		$result = array(
			'content'     => json_decode($response->getBody()->getContents(), true),
			'status_code' => (int) $response->getStatusCode()
		);
		
		if ($result['status_code'] === 200)
		{
			$shippingRateId = Redshop\Shipping\Rate::encrypt(
				array(
					__CLASS__,
					$shipping->name,
					$serviceType,
					number_format($result['content']['total_price'], 2, '.', ''),
					0,
					'single',
					0,
					0,
					0
				)
			);
			
			$shippingRate        = new stdClass;
			$shippingRate->text  = $serviceType;
			$shippingRate->value = $shippingRateId;
			$shippingRate->rate  = $result['content']['total_price'];
			$shippingRate->vat   = 0;
			
			$shippingRates['ratesList'][] = $shippingRate;
		}
		else
		{
			$shippingRates['service_error']['error_msg'] = $result['content']['description'];
		}
		
		if (isset($shippingRates['ratesList']))
		{
			$shippingRates['success'] = 1;
		}
		else
		{
			$shippingRates['success'] = 0;
		}
		
		ob_clean();
		echo json_encode($shippingRates);
		
		$this->app->close();
	}
	
	public function prepareRequestBodyForEstimateOrderFeeAndCreateOrder(
		$serviceType,
		$requester,
		$customer,
		$remarks,
		$deliverySchedule
	) {
		$deliverySchedule = $deliverySchedule ?: 0;
		
		$path = array(
			//Store
			array(
				"lat"     => (float) $requester['location']['lat'],
				"lng"     => (float) $requester['location']['lng'],
				"address" => $requester['address'],
				"name"    => $requester['name'],
				"remarks" => 'Den lay hang vui long goi '.$requester['phone'],
				"mobile"  => $requester['phone']
			),
			//Customer
			array(
				"lat"         => (float) $customer['location']['lat'],
				"lng"         => (float) $customer['location']['lng'],
				"address"     => $customer['address'],
				"name"        => $customer['name'],
				"remarks"     => $remarks,
				"require_pod" => (boolean) true,
				"mobile"      => $customer['phone']
			)
		);
		
		return 'order_time='.$deliverySchedule.'&path='.json_encode($path,
				JSON_UNESCAPED_UNICODE).'&service_id='.$serviceType.'&payment_method='.self::PAYMENT_METHOD;
	}
	
	public function getTranslationListForOrderStatusAhamove()
	{
		return array(
			'IDLE'       => JText::_("PLG_REDSHOP_SHIPPING_AHAMOVE_ORDER_STATUS_CONFIRMED"),
			'ASSIGNING'  => JText::_("PLG_REDSHOP_SHIPPING_AHAMOVE_ORDER_STATUS_ASSIGNING_DRIVER"),
			'ACCEPTED'   => JText::_("PLG_REDSHOP_SHIPPING_AHAMOVE_ORDER_STATUS_ACCEPTED_DRIVER"),
			'IN PROCESS' => JText::_("PLG_REDSHOP_SHIPPING_AHAMOVE_ORDER_STATUS_IN_PROCESS"),
			'CANCELLED'  => JText::_("PLG_REDSHOP_SHIPPING_AHAMOVE_ORDER_STATUS_CANCELLED"),
			'COMPLETED'  => JText::_("PLG_REDSHOP_SHIPPING_AHAMOVE_ORDER_STATUS_COMPLETED"),
			'FAILED'     => JText::_("PLG_REDSHOP_SHIPPING_AHAMOVE_ORDER_STATUS_FAILED")
		);
	}
	
	public function afterOrderPlace($cart, $order)
	{
		$post = JFactory::getApplication()->input->post->getArray();
		
		$shippingDetail    = Redshop\Shipping\Rate::decrypt($post['shipping_rate_id']);
		$isShippingAhamove = strpos(strtolower($shippingDetail[0]), 'ahamove') !== false;
		
		if (!$isShippingAhamove)
		{
			return;
		}
		
		$deliveryDateTime = array(
			'date' => $post['delivery_date'],
			'time' => $post['delivery_time']
		);
		
		$dateTimeStore = $this->convertScheduleTimeToUnix($deliveryDateTime);
		
		$orderDeliveryField = RedshopEntityField::getInstanceByField('name', 'rs_ahamove_order_delivery_schedule');
		$itemSaved          = RedshopEntityField_Data::getInstance();
		$itemSaved->set('data_id', null)
			->set('fieldid', $orderDeliveryField->get('id'))
			->set('itemid', $order->order_id)
			->set('section', RedshopHelperExtrafields::SECTION_ORDER)
			->set('data_txt', (string) $dateTimeStore)
			->save();
	}
	
	public function sendOrderShipping($orderId, $paymentStatusCode, $orderStatusCode)
	{
		$orderEntity          = RedshopEntityOrder::getInstance($orderId);
		$orderData            = $orderEntity->getItem();
		$orderShippingAddress = $orderEntity->getShipping()->getItem();
		$orderPaymentMethod   = $orderEntity->getPayment()->getItem();
		$orderItems           = RedshopHelperOrder::getOrderItemDetail($orderId);
		$orderShippingMethod  = Redshop\Shipping\Rate::decrypt($orderData->ship_method_id);
		$isShippingAhamove    = strpos(strtolower($orderShippingMethod[0]), 'ahamove') !== false;
		$isPaymentCOD         = strpos(strtolower($orderPaymentMethod->order_payment_name), 'cod') !== false;
		
		if ($orderStatusCode == 'C' && $paymentStatusCode == 'Paid')
		{
			if ($isShippingAhamove && empty($this->getAhamoveOrderInformation($orderId)))
			{
				$orderDeliveryTime = RedshopEntityField_Data::getInstance()->loadItemByArray(
					array(
						'fieldid' => RedshopHelperExtrafields::getField('rs_ahamove_order_delivery_schedule')->id,
						'itemid'  => $orderId,
						'section' => RedshopHelperExtrafields::SECTION_ORDER
					)
				);
				
				$orderDeliveryTime = ($orderDeliveryTime) ? $orderDeliveryTime->getItem()->data_txt : 0;
				$existingCustomer  = $this->getAddressExistingCustomer($orderShippingAddress->users_info_id);
				$geocodingLocation = explode(',', $existingCustomer['geocoding_location']);
				
				$requester = $this->getRequesterContactInfo($existingCustomer['state_code']);
				
				$customer = array(
					'name'     => $existingCustomer['full_name'],
					'phone'    => $existingCustomer['phone'],
					'address'  => $orderShippingAddress->address,
					'location' => array(
						'lat'            => (string) $geocodingLocation[0],
						'lng'            => (string) $geocodingLocation[1],
						'address_remark' => (string) $existingCustomer['address_note']
					)
				);
				
				$remarks = $this->makeDeliveryInfoRemark($orderItems, $customer, $orderId);
				
				
				$body = $this->prepareRequestBodyForEstimateOrderFeeAndCreateOrder($orderShippingMethod[2],
					$requester, $customer, $remarks, $orderDeliveryTime);
				
				$body .= '&items='.$this->getOrderItems($orderItems);
				
				if ($isPaymentCOD)
				{
					$body .= '&remarks='."PS: Thu ho ".strip_tags(RedshopHelperProductPrice::formattedPrice($orderData->order_total));
				}
				
				$accessToken = $this->getAccessToken($requester)['token'];
				
				$request        = new \Ahamove\Api\AhamoveAPI($this->_endpoint, $accessToken);
				$response       = $request->createOrder($body);
				$responseDetail = json_decode($response->getBody()->getContents(), true);
				$statusCode     = (int) $response->getStatusCode();
				
				if ($statusCode === 200)
				{
					$dt                                 = new \DateTime('now',
						new \DateTimeZone(\JFactory::getConfig()->get('offset')));
					$ahamoveOrderId                     = array();
					$ahamoveOrderId['order_id']         = $responseDetail['order_id'];
					$ahamoveOrderId['deliverySchedule'] = $dt->setTimestamp($orderDeliveryTime)->format('d/m/Y H:i');
					$ahamoveOrderId['deliverToAddress'] = $customer['address'];
					$ahamoveOrderId['deliveryNote']     = $remarks;
					$ahamoveOrderId['requestBody']      = json_encode($body, JSON_UNESCAPED_UNICODE);
					$customField                        = RedshopEntityField::getInstanceByField('name',
						'rs_ahamove_order_ref');
					$db                                 = $this->db;
					$columns                            = array('fieldid', 'data_txt', 'itemid', 'section');
					$values                             = array(
						$db->q((int) $customField->get('id')),
						$db->q(json_encode($ahamoveOrderId)),
						$db->q((int) $orderId),
						$db->q((int) RedshopHelperExtrafields::SECTION_ORDER)
					);
					
					$query = $db->getQuery(true)
						->insert($db->qn('#__redshop_fields_data'))
						->columns($db->qn($columns))
						->values(implode(',', $values));
					
					$db->setQuery($query)->execute();
					
					$orderEntity->set('track_no', $responseDetail['order_id'])
						->save();
				}
				else
				{
					$comment = 'Order id='.$orderId.' failed to book on ahamove webservice';
					$comment .= ' - Status code: '.$statusCode;
					
					if (!empty($responseDetail['description']))
					{
						$comment .= ' - Failure message: '.$responseDetail['description'];
					}
					
					$this->writeLogWhenPostOrderFail($comment);
				}
			}
			
			$ahamoveOrderId = '';
			
			if ((int) $statusCode === 200)
			{
				$ahamoveOrderId = $responseDetail['order_id'];
			}
			
			JPluginHelper::importPlugin('system', 'kiotviet');
			$dispatcher = JDispatcher::getInstance();
			$dispatcher->trigger('onCreateOrderKiotviet', array($orderId, $ahamoveOrderId));
		}
	}
	
	public function writeLogWhenPostOrderFail($comment)
	{
		Log::addLogger(
			array('text_file' => 'ahamove.log'),
			Log::ALL,
			'com_redshop'
		);
		
		Log::add($comment, Log::ERROR, 'com_redshop');
	}
	
	public function convertScheduleTimeToUnix($datetime)
	{
		// $datetime is in below format:
		/*$datetime = array(
			'date' => 'd/m/Y',
			'time' => '17:25'  // or '03:17'  (hour:minute)
		);*/
		
		$timezone = new \DateTimeZone(date_default_timezone_get());
		
		if (!empty(\JFactory::getConfig()->get('offset')))
		{
			$timezone = new \DateTimeZone(\JFactory::getConfig()->get('offset'));
		}

		$deliveryTime = (empty($datetime['date']) || empty($datetime['time'])) ? 0 : \DateTime::createFromFormat('d/m/Y H:i',
			$datetime['date'].' '.$datetime['time'], $timezone)->getTimestamp(); // note: H is 24h format
		
		return $deliveryTime;
	}
	
	public function onReplaceShippingTemplate($d, &$data, $classname, $checked)
	{
		if ($classname == 'ahamove')
		{
			if (empty($d['ahamoveShippingRates']))
			{
				$data = str_replace("javascript:onestepCheckoutProcess(this.name,'ahamove');",
					'javascript:void(0);', $data);
				$data = str_replace('type="radio"', 'type="radio" class="ahamove unverified"', $data);
			}
			else
			{
				$data = str_replace('type="radio"', 'type="radio" class="ahamove verified"', $data);
			}
		}
	}
	
	/**
	 * onListRates
	 *
	 * @param   array  $data  Array values
	 *
	 * @return array
	 */
	public function onListRates(&$data)
	{
		$cart = RedshopHelperCartSession::getCart();
		
		if ((int) $data['users_info_id'] > 0 && JFactory::getApplication()->isClient('site'))
		{
			$user_info_id = $data['users_info_id'];
			$userData     = $this->getAddressExistingCustomer($user_info_id);
			
			if (!empty($userData['geocoding_location']))
			{
				$geocoding_location = explode(',', $userData['geocoding_location']);
				
				$timezone = new \DateTimeZone(date_default_timezone_get());
				
				if (!empty(\JFactory::getConfig()->get('offset')))
				{
					$timezone = new \DateTimeZone(\JFactory::getConfig()->get('offset'));
				}
				
				$now          = new \DateTime('now', $timezone);
				$deliveryTime = $now->modify('+30 minutes');
				
				$requestParams = array(
					'customer'          => array(
						'location' => array(
							'lat'            => $geocoding_location[0],
							'lng'            => $geocoding_location[1],
							'address'        => $userData['address'],
							'address_remark' => (string) $userData['address_note']
						),
						'address'  => $userData['address'],
						'name'     => $userData['full_name'],
						'phone'    => $userData['phone']
					),
					'delivery_schedule' => array(
						'date' => $deliveryTime->format('d/m/Y'),
						'time' => $deliveryTime->format('H:i')
					),
					'users_info_id'     => $user_info_id
				);
				
				
				$remarks          = $this->makeDeliveryInfoRemark($cart, $requestParams['customer']);
				$requester        = $this->getRequesterContactInfo($userData['state_code']);
				$deliverySchedule = $this->convertScheduleTimeToUnix($requestParams['delivery_schedule']);
				$accessToken      = $this->getAccessToken($requester)['token'];
				$shippingRates    = array();
				$serviceType      = $requester['serviceType'];
				
				$body = $this->prepareRequestBodyForEstimateOrderFeeAndCreateOrder($serviceType, $requester,
					$requestParams['customer'], $remarks, $deliverySchedule);
				
				$request  = new \Ahamove\Api\AhamoveAPI($this->_endpoint, $accessToken);
				$response = $request->estimateOrderFee($body);
				
				$shipping = RedshopHelperShipping::getShippingMethodByClass('ahamove');
				
				$result = array(
					'content'     => json_decode($response->getBody()->getContents(), true),
					'status_code' => (int) $response->getStatusCode()
				);
				
				if ($result['status_code'] === 200)
				{
					$shippingRateId = Redshop\Shipping\Rate::encrypt(
						array(
							__CLASS__,
							$shipping->name,
							$serviceType,
							number_format($result['content']['total_price'], 2, '.', ''),
							0,
							'single',
							0,
							0,
							0
						)
					);
					
					$shippingRate        = new stdClass;
					$shippingRate->text  = $serviceType;
					$shippingRate->value = $shippingRateId;
					$shippingRate->rate  = $result['content']['total_price'];
					$shippingRate->vat   = 0;
					
					$shippingRates['ratesList'][] = $shippingRate;
				}
				
				if (!empty($shippingRates['ratesList']))
				{
					$data['ahamoveShippingRates'] = $shippingRates['ratesList'];
					
					return $shippingRates['ratesList'];
				}
			}
		}
		
		$shippingRate = array();
		
		$shipping = RedshopHelperShipping::getShippingMethodByClass('ahamove');
		$rateList = RedshopHelperShipping::listShippingRates($shipping->element, $data['users_info_id'], $data);
		
		if (!empty($rateList))
		{
			foreach ($rateList as $key => $rate)
			{
				$shippingRateValue         = $rate->shipping_rate_value;
				$rate->shipping_rate_value = RedshopHelperShipping::applyVatOnShippingRate($rate, $data);
				$shippingVatRate           = $rate->shipping_rate_value - $shippingRateValue;
				
				$shippingRateId = Redshop\Shipping\Rate::encrypt(
					array(
						__CLASS__,
						$shipping->name,
						$rate->shipping_rate_name,
						number_format($rate->shipping_rate_value, 2, '.', ''),
						$rate->shipping_rate_id,
						'single',
						$shippingVatRate,
						$rate->economic_displaynumber,
						$rate->deliver_type
					)
				);
				
				$shippingRate[$key]        = new stdClass;
				$shippingRate[$key]->text  = $rate->shipping_rate_name;
				$shippingRate[$key]->value = $shippingRateId;
				$shippingRate[$key]->rate  = $rate->shipping_rate_value;
				$shippingRate[$key]->vat   = $shippingVatRate;
			}
		}
		
		$document = JFactory::getDocument();
		
		$document->addScriptDeclaration(
			"AHAMOVE = window.AHAMOVE || {}; AHAMOVE.rate_unverified = ".
			json_encode($shippingRate).";"
		);
		
		return $shippingRate;
	}
	
	public function getAhamoveOrderInformation($redshopOrderId)
	{
		$orderField    = RedshopEntityField::getInstanceByField('name', 'rs_ahamove_order_ref');
		$queryUserInfo = RedshopHelperOrder::getOrderUserQuery($redshopOrderId);
		$state2Code    = $this->db->setQuery($queryUserInfo)->loadObject()->state_code;
		$requester     = $this->getRequesterContactInfo($state2Code);
		$accessToken   = $this->getAccessToken($requester)['token'];
		
		$query = $this->db->getQuery(true)
			->select('data_txt')
			->from($this->db->qn('#__redshop_fields_data'))
			->where($this->db->qn('fieldid').' = '.$this->db->q($orderField->get('id')))
			->where($this->db->qn('itemid').' = '.$this->db->q($redshopOrderId));
		
		$orderJson = $this->db->setQuery($query)->loadResult();
		
		if (!$orderJson || !$accessToken)
		{
			return array();
		}
		
		$ahamoveOrderId = json_decode($orderJson, true)['order_id'];
		
		if (empty($ahamoveOrderId))
		{
			return array();
		}
		
		$request       = new \Ahamove\Api\AhamoveAPI($this->_endpoint, $accessToken);
		$orderResponse = $request->getOrderDetail($ahamoveOrderId);
		if ($orderResponse->getStatusCode() !== 200)
		{
			return array();
		}
		
		$order                = array();
		$order['generalInfo'] = json_decode($orderJson, true);
		$order['status']      = json_decode($orderResponse->getBody()->getContents(), true)['status'];
		$order['trackingUrl'] = json_decode($request->getTrackingUrl($ahamoveOrderId)->getBody()->getContents(),
			true)['shared_link'];
		$order['fee_ship']    = json_decode($orderResponse->getBody(), true)['total_price'];
		
		return $order;
	}
	
	public function onRenderReceipt(&$template, $orderId)
	{
		$template = $this->replaceOrderTrackingTemplate($template, $orderId);
	}
	
	public function onAfterRenderOrderDetail(&$template, $orderId)
	{
		$template = $this->replaceOrderTrackingTemplate($template, $orderId);
	}
	
	public function onReplaceTagsInOrderMail(&$template, $orderId)
	{
		$orderData = $this->getAhamoveOrderInformation($orderId);
		
		if (empty($orderData))
		{
			$template = str_replace('{order_tracking_ahamove}', "", $template);
			$template = str_replace('{expected_delivery_time}', "", $template);
		}
		
		$layout      = RedshopLayoutHelper::render(
			'order_tracking_mail',
			array(
				'orderData'          => $orderData,
				'statusTranslations' => $this->getTranslationListForOrderStatusAhamove()
			),
			JPATH_PLUGINS.'/redshop_shipping/ahamove/layouts'
		);
		$trackingUrl = $orderData['trackingUrl'];
		
		$template = str_replace("{expected_delivery_time}", $orderData['generalInfo']['deliverySchedule'],
			$template);
		
		if ($orderData['trackingUrl'])
		{
			$template = str_replace("{tracking_url}", $orderData['trackingUrl'], $template);
			$template = str_replace("{tracking_url_lbl}",
				\JText::_('PLG_REDSHOP_SHIPPING_AHAMOVE_ORDER_TRACKING_URL').' : ', $template);
		}
		else
		{
			$template = str_replace("{tracking_url}", "", $template);
			$template = str_replace("{tracking_url_lbl}", "", $template);
		}
		
		$template = str_replace("{tracking_url}", $trackingUrl, $template);
		$template = str_replace('{order_tracking_ahamove}', $layout, $template);
	}
	
	public function onAfterAdminDisplayOrderDetail($orderId, $orderDetail)
	{
		$orderData = $this->getAhamoveOrderInformation($orderId);
		
		if (empty($orderData))
		{
			return;
		}
		
		echo RedshopLayoutHelper::render(
			'order_tracking_admin',
			array(
				'gmapApiKey'         => $this->_gmapApiKey,
				'orderData'          => $orderData,
				'statusTranslations' => $this->getTranslationListForOrderStatusAhamove()
			),
			JPATH_PLUGINS.'/redshop_shipping/ahamove/layouts'
		);
	}
	
	public function replaceOrderTrackingTemplate($template, $orderId)
	{
		$orderData = $this->getAhamoveOrderInformation($orderId);
		
		if (empty($orderData))
		{
			$template = str_replace('{order_tracking_ahamove}', "", $template);
			
			return $template;
		}
		
		$layout = RedshopLayoutHelper::render(
			'order_tracking',
			array(
				'gmapApiKey'         => $this->_gmapApiKey,
				'orderData'          => $orderData,
				'statusTranslations' => $this->getTranslationListForOrderStatusAhamove()
			),
			JPATH_PLUGINS.'/redshop_shipping/ahamove/layouts'
		);
		
		$template = str_replace('{order_tracking_ahamove}', $layout, $template);
		
		return $template;
	}
	
	public function onReplaceTrackingUrl($orderId, &$orderTrackURL)
	{
		if (empty($orderId) || $ahamoveDataOrder = $this->getAhamoveOrderInformation($orderId))
		{
			return;
		}
		
		$orderData           = RedshopEntityOrder::getInstance($orderId)->getItem();
		$orderShippingMethod = Redshop\Shipping\Rate::decrypt($orderData->ship_method_id);
		$isShippingAhamove   = strpos(strtolower($orderShippingMethod[0]), 'ahamove') !== false;
		
		if ($isShippingAhamove)
		{
			$orderTrackURL = $ahamoveDataOrder['trackingUrl'];
		}
	}
}