<?php
/**
 * @package     RedSHOP
 * @subpackage  Plugin
 *
 * @copyright   Copyright (C) 2008 - 2017 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');
JLoader::import('redshop.library');

/**
 * redSHOP Shipping GLS
 *
 * @since 1.0.0
 */
class PlgRedshop_ShippingDefault_Shipping_Gls extends JPlugin
{
	/**
	 * Shipping name
	 *
	 * @var  string
	 *
	 * @since  1.0.0
	 */
	const SHIPPING_NAME = "default_shipping_gls";

	/**
	 * Client
	 *
	 * @var  string
	 *
	 * @since  1.0.0
	 */
	public $client = '';

	/**
	 * Error Message
	 *
	 * @var  string
	 *
	 * @since  1.0.0
	 */
	public $errorMsg = '';

	/**
	 * Error
	 *
	 * @var  integer
	 *
	 * @since  1.0.0
	 */
	public $error = 0;

	/**
	 * Constructor
	 *
	 * @param   object  $subject  The object to observe
	 * @param   array   $config   An optional associative array of configuration settings
	 *
	 * @since   1.0.0
	 */
	public function __construct(&$subject, $config = [])
	{
		parent::__construct($subject, $config);
	}

	/**
	 * Get GLS Location
	 *
	 * @param   int     $usersInfoId  redSHOP user info id
	 * @param   string  $className    Shipping class name
	 * @param   int     $shopId       GLS Shop ID
	 *
	 * @return  string
	 */
	public function getGLSLocation($usersInfoId, $className, $shopId = 0)
	{
		$shippingGLS    = \RedshopHelperOrder::getParameters('default_shipping_gls');
		$selectedShopId = null;

		if (empty($shippingGLS) || !$shippingGLS[0]->enabled || $className != $this->_name)
		{
			return '';
		}

		$values = \RedshopHelperUser::getUserInformation(0, '', $usersInfoId, false);

		if ($shopId)
		{
			$shopOrderDetail = explode("###", $shopId);

			// Zipcode
			if (isset($shopOrderDetail[2]) && !empty($shopOrderDetail[2]))
			{
				$values->zipcode = $shopOrderDetail[2];
			}

			// Phone
			if (isset($shopOrderDetail[1]) && !empty($shopOrderDetail[1]))
			{
				$values->phone = $shopOrderDetail[1];
			}
		}

		$shopList      = [];
		$shopResponses = $this->GetNearstParcelShops($values);

		if (!empty($shopResponses) && is_array($shopResponses))
		{
			foreach ($shopResponses as $shopResponse)
			{
				$shopList[] = JHTML::_(
					'select.option',
					$shopResponse->shop_id,
					$shopResponse->CompanyName . ', ' . $shopResponse->Streetname . ', ' . $shopResponse->ZipCode . ', ' . $shopResponse->CityName
				);

				if (!$shopId)
				{
					continue;
				}

				$shopDetail = explode("|", $shopId);

				if ($shopDetail[0] == $shopResponse->Number)
				{
					$selectedShopId = $shopResponse->shop_id;
				}
			}
		}

		return \RedshopLayoutHelper::render(
			'glslocation',
			array(
				'values'         => $values,
				'selectedShopId' => $selectedShopId,
				'shopList'       => $shopList
			),
			JPATH_PLUGINS . '/redshop_shipping/default_shipping_gls/layouts'
		);
	}

	/**
	 * GLS Connection
	 *
	 * @return  void
	 */
	public function onLabelsGLSConnection()
	{
		$url = 'http://www.gls.dk/webservices_v4/wsShopFinder.asmx?WSDL';

		try
		{
			$this->client = new SoapClient($url, array("trace" => 1, "exceptions" => 1));
		}
		catch (Exception $exception)
		{
			$this->error = true;

			echo $this->errorMsg = "Unable to connect soap client";

			JError::raiseWarning(21, $exception->getMessage());
		}
	}

	/**
	 * Get GLS Nearst Parcel Shop
	 *
	 * @param   object  $values  redSHOP Shipping data
	 *
	 * @return  mixed
	 */
	public function getNearstParcelShops($values)
	{
		if ($this->error)
		{
			return $this->errorMsg;
		}

		try
		{
			$this->onLabelsGLSConnection();

			$countryCode = isset($values->country_code) ? $values->country_code : '';

			if (!$countryCode)
			{
				$countryCode = \Redshop::getConfig()->get('DEFAULT_VAT_COUNTRY');
			}

			$handle = $this->client->SearchNearestParcelShops(
				array(
					'street'           => (string) isset($values->address) ? $values->address : '',
					'zipcode'          => (string) isset($values->zipcode) ? $values->zipcode : '',
					'countryIso3166A2' => \RedshopHelperWorld::getCountryCode2($countryCode),
					'Amount'           => $this->params->get('amount_shop', 10)
				)
			)->SearchNearestParcelShopsResult;

			return $this->shopArray($handle->parcelshops->PakkeshopData);
		}
		catch (Exception $exception)
		{
			if ($exception->getMessage())
			{
				return "<p><i>" . $exception->getMessage() . "</i></p>
						<p><i>Not support your country code: ". $countryCode ."</i></p>";
			}

			return false;
		}
	}

	/**
	 * get Pacsoft array
	 *
	 * @param   array $pakkeshopData Pacsoft data
	 *
	 * @return  array
	 */
	public function shopArray($pakkeshopData)
	{
		if (empty($pakkeshopData))
		{
			return [];
		}

		$i         = 0;
		$returns = [];

		foreach ($pakkeshopData as $key => $data)
		{
			$shopNumber           = $data->Number;
			$companyName          = $data->CompanyName;
			$streetName           = $data->Streetname;
			$zipCode              = $data->ZipCode;
			$telephone            = $data->Telephone;
			$countryCodeISO3166A2 = $data->CountryCodeISO3166A2;
			$cityName             = $data->CityName;
			$stropeningTime       = $this->weekdaysTime($data->OpeningHours->Weekday);
			$shopId               = $shopNumber . "|" . $companyName
				. "|" . $streetName . "|" . $zipCode
				. "|" . $countryCodeISO3166A2
				. "|" . $telephone . "|" . $stropeningTime
				. "|" . $cityName;

			$returns[$i]                       = new stdClass;
			$returns[$i]->shop_id              = $shopId;
			$returns[$i]->Number               = $shopNumber;
			$returns[$i]->CompanyName          = $companyName;
			$returns[$i]->Streetname           = $streetName;
			$returns[$i]->ZipCode              = $zipCode;
			$returns[$i]->Telephone            = $telephone;
			$returns[$i]->openingTime          = $stropeningTime;
			$returns[$i]->CityName             = $cityName;
			$returns[$i]->CountryCodeISO3166A2 = $countryCodeISO3166A2;

			$i++;
		}

		return $returns;
	}

	/**
	 * get Pacsoft weekday
	 *
	 * @param   array $weekDay Pacsoft data
	 *
	 * @return  string
	 */
	public function weekdaysTime($weekDay)
	{
		$opningTime = Array();

		for ($i = 0, $in = count($weekDay); $i < $in; $i++)
		{
			if ($weekDay[$i]->day == 'Monday')
			{
				$day = JText::_('MON');
			}
			elseif ($weekDay[$i]->day == 'Tuesday')
			{
				$day = JText::_('TUE');
			}
			elseif ($weekDay[$i]->day == 'Wednesday')
			{
				$day = JText::_('WED');
			}
			elseif ($weekDay[$i]->day == 'Thursday')
			{
				$day = JText::_('THU');
			}
			elseif ($weekDay[$i]->day == 'Friday')
			{
				$day = JText::_('FRI');
			}
			elseif ($weekDay[$i]->day == 'Saturday')
			{
				$day = JText::_('SAT');
			}
			elseif ($weekDay[$i]->day == 'Sunday')
			{
				$day = JText::_('SUN');
			}
			else
			{
				$day = $weekDay[$i]->day;
			}

			$opningTime[] = "<b>" . $day . '</b> '
				. $weekDay[$i]->openAt->From . '-' . $weekDay[$i]->openAt->To;
		}

		$stropeningTime = implode('  ', $opningTime);

		return $stropeningTime;
	}

	/**
	 * get List Shipping rate
	 *
	 * @param   array $data redSHOP Shipping data
	 *
	 * @return  array
	 */
	public function onListRates(&$data)
	{
		$shippingRate = [];
		$rate         = 0;
		$shipping     = \RedshopHelperShipping::getShippingMethodByClass($this->_name);
		$rates        = \RedshopHelperShipping::listShippingRates($shipping->element, $data['users_info_id'], $data);

		// fix listshipping rate show one item #task: 5879
		//$countRate    = count($rates) >= 1 ? 1 : 0;

		for ($i = 0; $i < count($rates); $i++)
		{
			$rs                      = $rates[$i];
			$shippingRateValue       = $rs->shipping_rate_value;
			$rs->shipping_rate_value = \RedshopHelperShipping::applyVatOnShippingRate($rs, $data);
			$shippingVatRate         = $rs->shipping_rate_value - $shippingRateValue;
			$economicDisplayNumber   = $rs->economic_displaynumber;
			$shippingRateId          = \RedshopShippingRate::encrypt(
				array(
					__CLASS__,
					$shipping->name,
					$rs->shipping_rate_name,
					number_format($rs->shipping_rate_value, 2, '.', ''),
					$rs->shipping_rate_id,
					'single',
					$shippingVatRate,
					$economicDisplayNumber
				)
			);

			$shippingRate[$rate]        = new stdClass;
			$shippingRate[$rate]->text  = $rs->shipping_rate_name;
			$shippingRate[$rate]->value = $shippingRateId;
			$shippingRate[$rate]->rate  = $rs->shipping_rate_value;
			$shippingRate[$rate]->vat   = $shippingVatRate;

			$rate++;
		}

		return $shippingRate;
	}

	/**
	 * get List Shipping rate
	 *
	 * @param   array  $data      redSHOP Shipping data
	 * @param   string $template  redSHOP Shipping template
	 * @param   string $className Shipping class name
	 * @param   string $checked   Is checked
	 *
	 * @return  void
	 */
	public function onReplaceShippingTemplate($data, &$template, $className, $checked)
	{
		if ($className != "default_shipping_gls")
		{
			return;
		}

		$glsLocation = $this->getGLSLocation($data['users_info_id'], $className);
		$style       = $checked != "checked" ? "style='display:none;'" : "style='display:block;'";

		if ($glsLocation)
		{
			$glsLocation = "<div " . $style . " id='rs_glslocationId'>" . $glsLocation . "</div>";
		}

		$template = str_replace("{gls_shipping_location}", $glsLocation, $template);
	}

	public function onBeforeUserShippingStore($orderUser, $orderResult)
	{
		if (empty($orderResult->shop_id))
		{
			return;
		}

		$orderShippingInfo = \RedshopShippingRate::decrypt($orderResult->ship_method_id);

		if ('plgredshop_shippingdefault_shipping_gls' != strtolower($orderShippingInfo[0]))
		{
			return;
		}

		$locationInfo = explode("|", trim($orderResult->shop_id));

		if (count($locationInfo) <= 0)
		{
			return;
		}

		$companyName = 'ServicePointID:' . $locationInfo[0] . ':PostDanmark';
		$city = explode('###', $locationInfo[7]);
		$city = trim($city[0]);

		$data = [
			'company_name' => $companyName,
			'firstname'    => $locationInfo[1],
			'lastname'     => '',
			'address'      => $locationInfo[2],
			'city'         => $city,
			'zipcode'      => $locationInfo[3]
		];

		$orderUser->bind($data);
	}
}
