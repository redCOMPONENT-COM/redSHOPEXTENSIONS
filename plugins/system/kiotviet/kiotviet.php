<?php
/**
 * @package     RedSHOP
 * @subpackage  Plugin
 *
 * @copyright   Copyright (C) 2008 - 2017 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
defined('_JEXEC') or die;
require_once __DIR__ . '/libraries/vendor/autoload.php';
use Kiotviet\ConnectApi;
use Kiotviet\Categories\SyncCategoriesRedshop;
use Kiotviet\Categories\SyncCategoriesKiotviet;
use Kiotviet\Products\SyncProductRedshop;
use Kiotviet\Orders\CreateOrderKiotviet;

JLoader::import('redshop.library');

class plgSystemKiotviet extends JPlugin
{
	use \Kiotviet\Traits\Helper;
	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 * @since  3.1
	 */
	protected $autoloadLanguage = true;

	/**
	 * Application object
	 *
	 * @var    JApplicationCms
	 * @since  3.2
	 */
	protected $app;

	/**
	 * Application object
	 *
	 * @var    JDatabaseDriver
	 * @since  __DEPLOY_VERSION__
	 */
	public $db;

	public function __construct(&$subject, $config = array())
	{
		parent::__construct($subject, $config);

		$this->db = JFactory::getDbo();
	}

	public function onRedshopAdminBeforeRender($view)
	{
		$app       = JFactory::getApplication();
		$allowView = array('categories', 'product', 'order');
		$viewInput = $app->input->getString('view', '');

		if (!$app->isClient('administrator')
			|| $app->input->get('option', '') !== 'com_redshop' || !in_array($viewInput, $allowView)) {
			return;
		}

		JHtml::script(Juri::root() . 'plugins/system/kiotviet/js/kiotviet.js');

		$class = 'btn-default';

		$title = JText::sprintf('PLG_SYSTEM_KIOTVIET_SYNC_TITLE', $viewInput);


		$html = '<a type="button" '
			. 'id="sync_' . $viewInput . '" class="btn btn-small ' . $class . '">'
			. JText::_($title) . '</a>';


		$bar = JToolbar::getInstance('toolbar');
		$bar->appendButton('Custom', $html, $title);

		if ($viewInput == 'product') {
			$html = '<a type="button" '
				. 'id="registration_webhook_' . $viewInput . '" class="btn btn-small ' . $class . '">'
				. JText::_('PLG_SYSTEM_KIOTVIET_REGISTRATION_WEBHOOK_TITLE') . '</a>';

			$bar->appendButton('Custom', $html, $title);

			$html = '<a type="button" '
				. 'id="remove_webhook_' . $viewInput . '" class="btn btn-small ' . $class . '">'
				. JText::_('PLG_SYSTEM_KIOTVIET_REMOVE_WEBHOOK_TITLE') . '</a>';

			$bar->appendButton('Custom', $html, $title);
		}
	}

	public function getAccessToken()
	{
		$kiotviet = new ConnectApi;
		$config   = array(
			'client_id' => $this->params->get('client_id'),
			'secret_id' => $this->params->get('secret_id')
		);

		$kiotviet->setConfig($config);

		return $kiotviet->getAccessToken();
	}

	public function onAjaxSyncCategories()
	{
		$accessToken = $this->getAccessToken();
		if ($this->params->get('update_redshop_category')) {
			$categoryRedshop = new SyncCategoriesRedshop($accessToken, $this->params->get('retailer'), $this->params);
			$categoryRedshop->execute();
		}
	}

	public function onAjaxSyncProducts()
	{
		$app        = \JFactory::getApplication();
		$input      = $app->input;
		$productIds = $input->get('productIds');

		$accessToken = $this->getAccessToken();

		$productRedshop = new SyncProductRedshop($accessToken, $this->params->get('retailer'), $this->params);

		if (!empty($productIds)) {
			$productRedshop->syncProductByIds($productIds);
		} elseif ($input->get('startLimit') || $input->get('limit')) {
			$productRedshop->limit      = $input->get('limit');
			$productRedshop->startLimit = $input->get('startLimit');

			$productRedshop->execute();
		} else {
			$product = $productRedshop->getProudcts();

			$data = array(
				'total'    => $product->total,
				'pageSize' => $product->pageSize
			);

			echo json_encode($data);
			$app->close();
		}
	}


	public function onCreateOrderKiotviet($orderId, $orderRef)
	{
		$orderDetail = RedshopEntityOrder::getInstance($orderId)->getItem();
		$accessToken = $this->getAccessToken();


		$order = new CreateOrderKiotviet($accessToken, $this->params->get('retailer'), $this->params);

		$kvOrders = $order->create($orderDetail, $orderRef);

		$this->storeOrderKiotviet($orderId, $kvOrders);
	}

	public function sendOrderShipping($orderId, $orderRef)
	{
		$orderKvData = RedshopEntityField_Data::getInstance()->loadItemByArray(
			array(
				'fieldid' => RedshopHelperExtrafields::getField('rs_kiotviet_orders')->id,
				'itemid'  => $orderId,
				'section' => RedshopHelperExtrafields::SECTION_ORDER
			)
		)->data_txt;

		$orderKvCode = json_decode($orderKvData)->code;

		if ($this->params->get('use_lalamove') || !empty($orderKvCode)) {
			return false;
		}

		$orderDetail = RedshopEntityOrder::getInstance($orderId)->getItem();
		$accessToken = $this->getAccessToken();


		$order = new CreateOrderKiotviet($accessToken, $this->params->get('retailer'), $this->params);

		$kvOrders = $order->create($orderDetail, $orderRef);

		$this->storeOrderKiotviet($orderId, $kvOrders);
	}

	public function onAjaxRegistrationWebhook()
	{
		$accessToken = $this->getAccessToken();
		$webhook     = new \Kiotviet\Webhook($accessToken, $this->params->get('retailer'));

		$types = array(
			'product.update' => 'webhook.php',
			'product.delete' => 'webhook.php',
			'stock.update'   => 'webhook.php'
		);

		foreach ($types as $type => $url) {
			$webhook->create($type, 'http://' . JUri::getInstance()->getHost() . '/' . $url);
		}
	}

	public function onAjaxRemoveWebhook()
	{
		$accessToken = $this->getAccessToken();
		$webhook     = new \Kiotviet\Webhook($accessToken, $this->params->get('retailer'));
		$webhook->delete();
	}

	public function onAjaxCheckStockExists()
	{
		$app   = JFactory::getApplication();
		$input = $app->input;

		$stateCode       = $input->get('stateCode');
		$usersInfoId     = $input->get('usersInfoId');
		$productIds      = $input->get('productIds');
		$result          = array('outstock' => false);
		$productOutStock = array();

		if ($usersInfoId) {
			$stateCode = RedshopEntityUser::getInstance($usersInfoId)->getItem()->state_code;
		}

		if (!empty($stateCode)) {
			$stockroomId = self::getStockroomIdByStateCode($stateCode);

			foreach ($productIds as $productId) {
				if (!RedshopHelperStockroom::isStockExists($productId, 'product', $stockroomId)) {
					$product = RedshopHelperProduct::getProductById($productId);

					$productOutStock[] = $product->product_name;
				}
			}
		}

		if (!empty($productOutStock)) {
			$result['outstock'] = true;
		}

		$result['product_out_stock'] = $productOutStock;
		echo json_encode($result);
		$app->close();
	}

	public function storeOrderKiotviet($orderId, $kvOrder)
	{
		$customField = RedshopEntityField::getInstanceByField('name', 'rs_kiotviet_orders');

		$db = JFactory::getDbo();

		$columns = array('fieldid', 'data_txt', 'itemid', 'section');

		$values = array(
			$db->q((int)$customField->get('id')),
			$db->q($kvOrder),
			$db->q((int)$orderId),
			$db->q((int)RedshopHelperExtrafields::SECTION_ORDER)
		);

		$query = $db->getQuery(true)
			->insert($db->qn('#__redshop_fields_data'))
			->columns($db->qn($columns))
			->values(implode(',', $values));

		return $db->setQuery($query)->execute();
	}

	public function onAfterDeleteCategory($data, $pk, $children)
	{
		if (empty($pk))
		{
			return false;
		}

		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
			->delete('#__kiotviet_category_mapping')
			->where($db->qn('rs_category_id') . ' = ' . $db->q($pk));

		$db->setQuery($query)->execute();
	}

	public function onAfterProductFullSave($row)
	{
		$post = $this->app->input->post->getArray();
		$kiotvietSettings = $post['kiotviet'];
		$productId = $row->product_id;

		foreach ($kiotvietSettings as $key => $value)
		{
			if ($this->isExistRecord($productId, $key))
			{
				// update record
				$db = $this->db;
				$query = $db->getQuery(true);

				$fields = array(
					$db->qn('value_setting') . ' = ' . $db->q($value)
				);

				$conditions = array(
					$db->qn('product_id') . ' = ' . $db->q($productId),
					$db->qn('key_setting') . ' = ' . $db->q($key)
				);

				$query->update($db->qn('#__kiotviet_setting_product'))->set($fields)->where($conditions);
				$db->setQuery($query)->execute();
			}
			else
			{
				// insert record
				if ($value == '0')
				{
					continue;
				}

				$record = new stdClass();
				$record->product_id = $productId;
				$record->key_setting= $key;
				$record->value_setting= $value;

				$this->db->insertObject('#__kiotviet_setting_product', $record);
			}
		}
	}

	public function isExistRecord($productId, $keySetting)
	{
		$db = $this->db;
		$query = $db->getQuery(true)
			->select('product_id')
			->from($db->qn('#__kiotviet_setting_product'))
			->where($db->qn('product_id') . ' = ' . $db->q($productId))
			->where($db->qn('key_setting') . ' = ' . $db->q($keySetting));

		return $db->setQuery($query)->loadResult();

	}

	public function onDisplayTabMenu($tabMenu, $selectedTabPosition)
	{
		$tabMenu->addItem(
			'#kiotviet_settings',
			'Kiotviet setting product',
			$selectedTabPosition === 'kiotviet_settings',
			'kiotviet_settings'
		);
	}
}
