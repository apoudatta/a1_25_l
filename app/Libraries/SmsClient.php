<?php

namespace App\Libraries;

use GuzzleHttp\Client;

class SmsClient
{
	protected $client;
	protected $apiUrl; // Replace with actual API URL
	protected $auth;

	public function __construct()
	{
		$this->apiUrl = env('SMS_API');
		$this->client = new Client([
			// You can set any number of default request options
			'timeout'  => env('MAIL_SERVER_TIMEOUT', 10),
			// Disable SSL verification
			'verify'   => false,
		]);
		$this->auth = [
			'acode'   => env('ACODE'),
			'apiKey'  => env('APIKEY'),
		];
	}

	public function sendSms($msisdn, $message, $masking = '01552146174', $is_unicode = 0, $transactionType = 'T', $contentID = '')
	{
		// if(env('CI_ENVIRONMENT')!='production'){
		// 	$msisdn = env('TEST_MOBILE');
		// }
		if(env('SMS_CLIENT') == true){
			try {
				$payload = [
					'auth' => $this->auth,
					'smsInfo' => [
						'requestID' => uniqid(),
						'message'   => $message,
						'is_unicode' => $is_unicode,
						'masking'   => $masking??env('MASKING_NAME'),
						'msisdn'    => $msisdn,
						'transactionType' => $transactionType,
						'contentID' => $contentID,
					],
				];

				$response = $this->client->post($this->apiUrl, [
					'json' => $payload,
					'headers' => [
						'Content-Type' => 'application/json',
						'Accept'       => 'application/json',
					],
				]);

				return json_decode($response->getBody()->getContents(), true);
			} catch (\Exception $e) {
				return ['error' => $e->getMessage()];
			}
		}else{
			return 'SMS client false';
		}
	}
}
