<?php
namespace Kiotviet\Orders;
use Kiotviet\ConnectApi;

class Order extends ConnectApi
{
	public function __construct($accessToken, $retailerName, $options = array())
	{
		$this->_headers = $this->getHeaders($accessToken, $retailerName);
		$this->_client  = $this->getClient();
		$this->_options = $options;
	}
}