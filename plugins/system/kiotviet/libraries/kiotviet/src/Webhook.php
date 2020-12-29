<?php
namespace Kiotviet;

use LinkedIn\Exception;

class Webhook extends ConnectApi
{
	public function __construct($accessToken, $retailerName, $options = array())
	{
		$this->_headers = $this->getHeaders($accessToken, $retailerName);
		$this->_client  = $this->getClient();
	}

	public function create($typeWebhook, $url)
	{
		$body = array(
			'Webhook' => array(
				'Type' => $typeWebhook,
				'Url' => $url . '?noecho',
				'IsActive' => true
			)
		);

		$this->_headers['headers']['content-type'] = 'application/json';
		$request = array_merge($this->_headers, array('body' => json_encode($body)));
		try {
			$response = $this->getClient()->request('POST', 'webhooks', $request);
		} catch (Exception $e)
		{

		}

		return json_decode($response->getBody()->getContents());
	}


	public function delete()
	{
		$response = $this->getClient()->request('GET', 'webhooks', $this->_headers);

		$listWebhook = json_decode($response->getBody()->getContents());

		foreach ($listWebhook->data as $webhook)
		{
			$this->getClient()->request('DELETE', 'webhooks/' . $webhook->id, $this->_headers);
		}
	}
}