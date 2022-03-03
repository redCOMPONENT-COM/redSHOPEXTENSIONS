<?php
namespace Ahamove\Api;

class Request
{
	public $method = "GET";
	public $body = array();
	public $host = '';
	public $path = '';
	public $header = array();
	
	/**
	 * Build and return the header require for calling Ahamove API
	 * @return {Object} an associative aray of Ahamove header
	 */
	function buildHeader()
	{
		return [
			'Accept' => 'no-cache',
			"cache-control"=> "no-cache"
		];
	}
	
	/**
	 * Send out the request via guzzleHttp
	 * @return return the result after requesting through guzzleHttp
	 */
	public function send()
	{
		$client = new \GuzzleHttp\Client();
		
		$content = [
			'headers' => $this->buildHeader(),
			'http_errors' => false,
		];
		
		$content['query'] = $this->body;

		return $client->request($this->method, $this->host.$this->path, $content);
	}
}

class AhamoveAPI
{
	public $host = '';
	public $token = '';
	
	/**
	 * Constructor for Ahamove API
	 *
	 * @param $host - domain with http / https
	 * @patam $token - token created from apikey
	 * @param $apikey - apikey Ahamove provide
	 *
	 */
	public function __construct($host = "", $token = "")
	{
		$this->host = $host;
		$this->token = 'token=' . $token . '&';
	}
	

	
	/**
	 * Make a http Request to get a quotation from Ahamove API via guzzlehttp/guzzle
	 *
	 * @param $data{String}
	 * @return the http response from guzzlehttp/guzzle, an exception will not be thrown
	 *   2xx - http request is successful
	 *   4xx - unsuccessful request, see body for error message and documentation for matching
	 *   5xx - server error, please contact Ahamove
	 */
	public function estimateOrderFee($data)
	{
		$request = new Request();
		$request->path = "/v1/order/estimated_fee";
		$request->body = $this->token . $data;
		$request->host = $this->host;
		return $request->send();
	}
	
	/**
	 * Make a http request to create new order at  Ahamove API via guzzlehttp/guzzle
	 *
	 * @param $data{String}
	 * @return the http response from guzzlehttp/guzzle, an exception will not be thrown
	 *   2xx - http request is successful
	 *   4xx - unsuccessful request, see body for error message and documentation for matching
	 *   5xx - server error, please contact Ahamove
	 */
	public function createOrder($data)
	{
		$request = new Request();
		$request->path = "/v1/order/create";
		$request->body = $this->token . $data;
		$request->host = $this->host;
		return $request->send();
	}
	
	/**
	 * This API is used to get detail information about an order
	 *
	 * @param $orderId(String), the OrderId of Ahamove
	 * @return the http response from guzzlehttp/guzzle, an exception will not be thrown
	 *   2xx - http request is successful
	 *   4xx - unsuccessful request, see body for error message and documentation for matching
	 *   5xx - server error, please contact Ahamove
	 */
	public function getOrderDetail($orderId)
	{
		$request = new Request();
		$request->path = "/v1/order/detail";
		$request->body = $this->token . 'order_id=' . $orderId;
		$request->host = $this->host;
		return $request->send();
	}
	
	/**
	 * This API is used to cancel an order
	 *
	 * @param $orderId(String)
	 * @return the http response from guzzlehttp/guzzle, an exception will not be thrown
	 *   2xx - http request is successful
	 *   4xx - unsuccessful request, see body for error message and documentation for matching
	 *   5xx - server error, please contact Ahamove
	 */
	public function cancelOrder($orderId)
	{
		$request = new Request();
		$request->path = "/v1/order/cancel";
		$request->body = $this->token . 'order_id=' . $orderId;
		$request->host = $this->host;
		return $request->send();
	}

	public function getTrackingUrl($orderId)
	{
		$request = new Request();
		$request->path = "/v1/order/shared_link";
		$request->body = $this->token . 'order_id=' . $orderId;
		$request->host = $this->host;
		return $request->send();
	}
	
	public function getServiceTypeAndCityId($lat, $lng)
	{
		$request = new Request();
		$request->path = "/v1/order/service_types";
		$request->body = 'lat=' . $lat . '&lng=' . $lng;
		$request->host = $this->host;
		return $request->send();
	}
}
