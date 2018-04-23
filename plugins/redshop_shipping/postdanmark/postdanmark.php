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
 * Handle Post Denmark Shipping Locations
 *
 * @since  1.4
 */
class PlgRedshop_ShippingPostdanmark extends JPlugin
{
	/**
	 * @var  boolean
	 */
	protected $autoloadLanguage = true;

	/**
	 * Constructor
	 *
	 * @param   object $subject   The object to observe
	 * @param   array  $config    An optional associative array of configuration settings.
	 *                            Recognized key values include 'name', 'group', 'params', 'language'
	 *                            (this list is not meant to be comprehensive).
	 *
	 * @since   1.5
	 */
	public function __construct(&$subject, $config = array())
	{
		JPlugin::loadLanguage('plg_redshop_shipping_postdanmark');

		parent::__construct($subject, $config);
	}

	/**
	 * Shipping Method unique name
	 *
	 * @var  string
	 */
	public $className = 'postdanmark';

	/**
	 * Method will trigger on shipping rate listing.
	 *
	 * @param   array $data Users information
	 *
	 * @return  array         Shipping Rates
	 *
	 * @throws  Exception
	 */
	public function onListRates(&$data)
	{
		$shippingRates = array();
		$rate          = 0;
		$shipping      = RedshopHelperShipping::getShippingMethodByClass($this->className);
		$shippingArr   = RedshopHelperShipping::getShopperGroupDefaultShipping();

		if (!empty($shippingArr))
		{
			$shopperShipping   = $shippingArr['shipping_rate'];
			$shippingVatRate   = $shippingArr['shipping_vat'];
			$defaultShipping   = JText::_('COM_REDSHOP_DEFAULT_SHOPPER_GROUP_SHIPPING');
			$shopperShippingId = Redshop\Shipping\Rate::encrypt(
				array(
					__CLASS__,
					JText::_($shipping->name),
					$defaultShipping,
					number_format($shopperShipping, 2, '.', ''),
					$defaultShipping,
					'single',
					$shippingVatRate,
					'0',
					'1'
				)
			);

			$shippingRates[$rate]        = new stdClass;
			$shippingRates[$rate]->text  = $defaultShipping;
			$shippingRates[$rate]->value = $shopperShippingId;
			$shippingRates[$rate]->rate  = $shopperShipping;

			$rate++;
		}

		$rateList = RedshopHelperShipping::listShippingRates($shipping->element, $data['users_info_id'], $data);

		foreach ($rateList as $rateItem)
		{
			$shippingRate                  = $rateItem->shipping_rate_value;
			$rateItem->shipping_rate_value = Redshop\Shipping\Rate::applyVat($rateItem, $data);
			$shippingVatRate               = $rateItem->shipping_rate_value - $shippingRate;
			$economicDisplay               = $rateItem->economic_displaynumber;
			$shippingRateId                = Redshop\Shipping\Rate::encrypt(
				array(
					__CLASS__,
					JText::_($shipping->name),
					$rateItem->shipping_rate_name,
					number_format($rateItem->shipping_rate_value, 2, '.', ''),
					$rateItem->shipping_rate_id,
					'single',
					$shippingVatRate,
					$economicDisplay,
					$rateItem->deliver_type
				)
			);

			$shippingRates[$rate]        = new stdClass;
			$shippingRates[$rate]->text  = $rateItem->shipping_rate_name;
			$shippingRates[$rate]->value = $shippingRateId;
			$shippingRates[$rate]->rate  = $rateItem->shipping_rate_value;
			$shippingRates[$rate]->vat   = $shippingVatRate;

			$rate++;
		}

		if (!empty($shippingRates))
		{
			JHtml::_('redshopjquery.framework');

			// Load select2 for locations
			JHtml::_('redshopjquery.select2', '#mapMobileSeachBox');

			// Load redSHOP script
			JHtml::script('com_redshop/redshop.js', false, true);
			JText::script('PLG_REDSHOP_SHIPPING_POSTDANMARK_CHOOSE_DELIVERY_POINT');
			JText::script('PLG_REDSHOP_SHIPPING_POSTDANMARK_ENTER_VALUD_ZIP_CODE');
			JText::script('PLG_REDSHOP_SHIPPING_POSTDANMARK_CANCEL');
			JText::script('PLG_REDSHOP_SHIPPING_POSTDANMARK_OK');
			JText::script('PLG_REDSHOP_SHIPPING_POSTDANMARK_ENTER_POSTAL_CODE');
			JText::script('PLG_REDSHOP_SHIPPING_POSTDANMARK_ENTER_VALID_ZIP');
			JText::script('PLG_REDSHOP_SHIPPING_POSTDANMARK_PRESS_POINT_TO_DELIVERY');
			JText::script('PLG_REDSHOP_SHIPPING_POSTDANMARK_SELECT_ONE_OPTION');

			$useMap = $this->params->get('useMap', 1);
			RedshopHelperConfig::script('useMap', $useMap);

			$document = JFactory::getDocument();
			$document->addStyleSheet('plugins/redshop_shipping/postdanmark/includes/css/postdanmark_style.css');
			$document->addScript('plugins/redshop_shipping/postdanmark/includes/js/functions.js');

			if ($useMap)
			{
				$document->addStyleSheet('plugins/redshop_shipping/postdanmark/includes/js/magnific-popup/magnific-popup.css');
				$document->addScript('//maps.googleapis.com/maps/api/js?libraries=places&key=' . $this->params->get('mapKey'));
				$document->addScript('plugins/redshop_shipping/postdanmark/includes/js/map_functions.js');
				$document->addScript('plugins/redshop_shipping/postdanmark/includes/js/magnific-popup/jquery.magnific-popup.min.js');
			}
		}

		return $shippingRates;
	}

	/**
	 * Fetch data from postdanmark on ajax request
	 *
	 * @return  void
	 *
	 * @throws  Exception
	 */
	public function onPostDanmarkAjaxRequest()
	{
		$app = JFactory::getApplication();

		$zipCode     = $app->input->getInt('zipcode', '');
		$countryCode = $app->input->getCmd('countryCode', '');

		if (strlen($zipCode) === 4)
		{
			$url = 'https://api2.postnord.com/rest/businesslocation/v1/servicepoint/findNearestByAddress.json?'
				. '&apikey=' . $this->params->get('consumerId')
				. '&countryCode=' . trim($countryCode)
				. '&postalCode=' . trim($zipCode)
				. '&numberOfServicePoints=12';

			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_HEADER, 0);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			$jsonFile = curl_exec($curl);
			curl_close($curl);

			$data = json_decode($jsonFile);

			if ($data)
			{
				$points     = (array) $data->servicePointInformationResponse->servicePoints;
				$addresses  = array();
				$name       = array();
				$number     = array();
				$generate   = array();
				$opening    = array();
				$close      = array();
				$openingSat = array();
				$closeSat   = array();
				$lat        = array();
				$lng        = array();
				$city       = array();
				$postalCode = array();
				$key        = 1;

				// Unique shops location based on their servicePointId
				$uniqueShops = array();

				if (!empty($points))
				{
					// Loop through shops to make it unique
					foreach ($points as $shop)
					{
						$uniqueShops[$shop->servicePointId] = $shop;
					}

					// Loop through to prepare for map markers
					foreach ($uniqueShops as $point)
					{
						if ($point->visitingAddress->streetName)
						{
							$pointAddress = $point->visitingAddress->streetName;

							if (isset($point->visitingAddress->streetNumber))
							{
								$pointAddress .= ' ' . $point->visitingAddress->streetNumber;
							}

							$addresses[] = $pointAddress;
							$name[]      = $point->name;

							if (isset($point->openingHours) && count($point->openingHours) > 0)
							{
								$opening[] = $point->openingHours[0]->from1;
								$close[]   = $point->openingHours[0]->to1;

								if (count($point->openingHours) > 5)
								{
									$openingSat[] = $point->openingHours[5]->from1;
									$closeSat[]   = $point->openingHours[5]->to1;
								}
							}

							$lat[]            = $point->coordinate->northing;
							$lng[]            = $point->coordinate->easting;
							$number[]         = $point->deliveryAddress->postalCode . ' ' . $point->deliveryAddress->city;
							$city[]           = $point->deliveryAddress->city;
							$postalCode[]     = $point->deliveryAddress->postalCode;
							$servicePointId[] = $point->servicePointId;
						}
					}
				}

				$shopLocations['radio_html']  = $this->getPickupLocationsResult($uniqueShops);
				$shopLocations['addresses']   = $addresses;
				$shopLocations['name']        = $name;
				$shopLocations['number']      = $number;
				$shopLocations['generate']    = $generate;
				$shopLocations['opening']     = $opening;
				$shopLocations['close']       = $close;
				$shopLocations['opening_sat'] = $openingSat;
				$shopLocations['close_sat']   = $closeSat;
				$shopLocations['lat']         = $lat;
				$shopLocations['lng']         = $lng;
				$shopLocations['city']        = $city;
				$shopLocations['postalCode']  = $postalCode;

				if (isset($servicePointId))
				{
					$shopLocations['servicePointId'] = $servicePointId;
				}

				ob_clean();
				echo json_encode($shopLocations);
			}
			else
			{
				$shopLocations['error'] = JText::_('PLG_REDSHOP_SHIPPING_POSTDANMARK_NOT_ANSWER_FOR_CURRENT_ZIP');
				ob_clean();
				echo json_encode($shopLocations);
			}
		}

		$app->close();
	}

	/**
	 * Get shipping location result based on give data
	 *
	 * @param   array $shops Shipping locations of shops
	 *
	 * @return  string
	 */
	protected function getPickupLocationsResult($shops = array())
	{
		if (empty($shops))
		{
			return '<span class="postdanmark-error" id="postdanmark-error">'
				. JText::_('PLG_REDSHOP_SHIPPING_POSTDANMARK_NOT_CORRECT_ZIP') . '</span><br/>'
				. '<input type="hidden" name="postdanmark_pickupLocation" id="location" class="postdanmark_location" />';
		}

		return  RedshopLayoutHelper::render(
			'locations',
			array('shops' => $shops),
			JPATH_PLUGINS . '/redshop_shipping/postdanmark/layouts'
		);
	}
}
