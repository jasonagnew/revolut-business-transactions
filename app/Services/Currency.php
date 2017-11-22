<?php 
namespace App\Services;

use GuzzleHttp\Client;

class Currency {

	public $rates;

	public function __construct() {

		$client = new Client();

		$response = $client->request('GET', 'http://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml');

		$xml = $response->getBody()->getContents();

		$xml = simplexml_load_string($xml);
		$rates = [];

		foreach( $xml->Cube->Cube->children() as $rate ) {
			$currency = (string) $rate['currency'];
			$rates[$currency] = (float) $rate['rate'];
		}

		$rates['EUR'] = 1;

		$this->rates = $rates;
	}

	public function convert($amount, $current, $new) {
		$rates = $this->rates;
		$current_rate = $rates[$current];
		$adjustment = 1 / $current_rate;

		foreach ( $rates as $key => $rate ) {
			$rates[$key] = round($rate * $adjustment, 4);
		}

		$rate_adjustment = $rates[$new];

		return round($amount * $rate_adjustment, 2);
	}

}

