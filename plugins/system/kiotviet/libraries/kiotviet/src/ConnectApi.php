<?php
namespace Kiotviet;

class ConnectApi
{
	private $_client_id;

	private $_secret_id;

	private $_retailer;

	protected $_headers;

	protected $_client;

	protected $_options;

	protected $scopes = 'PublicApi.Access.FNB';

	protected  $grantType = 'client_credentials';

	protected $baseUrl = 'https://publicfnb.kiotapi.com/';

	public $db;

	public function __construct()
	{
		$this->db = \JFactory::getDbo();
	}

	public function setConfig($config = array())
	{
		$this->_client_id = !empty($config['client_id']) ? $config['client_id'] : '';
		$this->_secret_id = !empty($config['secret_id']) ? $config['secret_id'] : '';
		$this->_retailer  = !empty($config['retailer']) ? $config['retailer'] : '';
	}

	public function getAccessToken()
	{
		$option = array(
			'form_params' => array (
				'client_id' => $this->_client_id,
				'client_secret' => $this->_secret_id,
				'scopes' => $this->scopes,
				'grant_type' => $this->grantType
			)
		);

		$client = new \GuzzleHttp\Client();

		try {
			$response = $client->request('POST', 'https://id.kiotviet.vn/connect/token', $option);

			return json_decode($response->getBody()->getContents())->access_token;
		}
		catch (Exception $exception)
		{
			echo "<pre>";
			print_r($exception->getMessage());
			echo "</pre>";
			die();
		}
	}

	public function getHeaders($accessToken, $retailerName)
	{
		return array(
			'headers' => array(
				'Retailer' => $retailerName,
				'Authorization' => 'Bearer ' . $accessToken)
		);
	}

	public function getClient()
	{
		return new \GuzzleHttp\Client(['base_uri' => $this->baseUrl]);
	}
}