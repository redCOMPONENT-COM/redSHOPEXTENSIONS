<?php

namespace Kiotviet\Products;

use Kiotviet\ConnectApi;
use Kiotviet\Categories\SyncCategoriesRedshop as Category;

class Product extends ConnectApi
{
	public function __construct($accessToken, $retailerName, $options = array())
	{
		$this->_headers = $this->getHeaders($accessToken, $retailerName);
		$this->_client  = $this->getClient();
		$this->_options = $options;
	}
}