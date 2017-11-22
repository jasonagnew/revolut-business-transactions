<?php
namespace App\Services;

use GuzzleHttp\Client;

use App\Services\Currency;

class Revolut {

	private $key;
	private $user;
	private $password;
	private $domain;


	/**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->key = config('revolut.key');
        $this->domain = config('revolut.domain');
        
        $this->settings = [
            'headers' => [
            	'Authorization' => 'Bearer ' . $this->key,
            	'Content-Type' => 'application/json'
            ]
        ];

        $this->client = new Client([
            'base_uri' => "{$this->domain}/api/1.0/",
        ]);
    }

    public function getTransactions($form = false)
    {	
    	$query = [];

    	if ( $form ) {
    		$query['form'] = $form;
    	}

        $settings = array_merge($this->settings, [ 'query' => $query ]);

        $response = $this->client->request('GET', "transactions", $settings);

        return json_decode($response->getBody());
    }

    public function getTotalBalance($base = 'GBP') {
        $accounts = $this->getAccounts();
    
        $total = 0;
        $curreny = new Currency();

        foreach ( $accounts as $account ) {
            $total += $curreny->convert( $account->balance, $account->currency , $base);
        }
        return $total;
    }

    public function getAccounts($pocket = true) {
        $query = [];

        $response = $this->client->request('GET', "accounts", array_merge($this->settings,$query));

        $accounts = json_decode($response->getBody());

        if ( $pocket == false ) {
            return $accounts;
        }

        $pocketAccounts = [];

        foreach ( $accounts as $account) {
            if ( $account->type == 'pocket' ) {
                 $pocketAccounts[] = $account;
            }
        }

        return $pocketAccounts;
    }


    public function getAccountsSummary() {
        $accounts = $this->getAccounts();
        $summary = '';
        foreach ( $accounts as $account ) {
            $balance = $this->getSymbol($account->currency) . $this->getAmount($account->balance);
            $summary .= "*{$account->currency}:* {$balance}\n";
        }
        return $summary;
    }

    public function getTransactionSummary($tranactions) {
        switch ( $tranactions->type ) {
            case 'transfer':
                return $this->getTransactionSummaryTransfer($tranactions);
                break;
            case 'topup':
                return $this->getTransactionSummaryTopup($tranactions);
                break;
            case 'exchange':
                return $this->getTransactionSummaryExchange($tranactions);
                break;
            case 'card_payment':
                return $this->getTransactionSummaryCardPayment($tranactions);
                break;
            case 'fee':
                return $this->getTransactionSummaryFee($tranactions);
                break;
        }
    }

    public function getTransactionSummaryTransfer($tranactions) {
        $legs = $tranactions->getLegs();
        $description = $legs[0]->description;
        $amount = $this->getAmountString($legs[0]);
        return "Paid {$amount} {$description} ({$tranactions->state})";
    }
    
    public function getTransactionSummaryTopup($tranactions) {
        $legs = $tranactions->getLegs();
        $description = str_replace('Payment ', '', $legs[0]->description);
        $amount = $this->getAmountString($legs[0]);
        return "Payment {$amount} {$description} ({$tranactions->state})";
    }

    public function getTransactionSummaryExchange($tranactions) {
        $legs = $tranactions->getLegs();
        $amountFrom = $this->getAmountString($legs[0]);
        $amountTo = $this->getAmountString($legs[1]);
        return "Exchanged {$amountFrom} to {$amountTo} ({$tranactions->state})";
    }

    public function getTransactionSummaryCardPayment($tranactions) {
        $legs = $tranactions->getLegs();
        $description = $legs[0]->description;
        $amount = $this->getAmountString($legs[0]);
        return "Paid {$amount} to {$description} ({$tranactions->state})";
    }

    public function getTransactionSummaryFee($tranactions) {
        $legs = $tranactions->getLegs();
        $amount = $this->getAmountString($legs[0]);
        return "Bank Fee Paid: {$amount} ({$tranactions->state})";
    }

    public function getAmountString($leg) {
        return $this->getSymbol($leg->currency) . $this->getAmount($leg->amount);
    }

    public function getSymbol($reference) {
        switch ( $reference ) {
            case 'GBP':
                return '£';
                break;
            case 'EUR':
                return '€';
                break;
            case 'USD':
                return '$';
                break;
        }
    }

    public function getAmount($amount) {
        return number_format( abs( (float) $amount ), 2 );
    }

} 