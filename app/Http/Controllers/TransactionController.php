<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Transaction;
use App\Services\Revolut;
use App\Services\Slack;
use App\Services\Currency;
use App\Services\Freeagent;

use Carbon\Carbon;

class TransactionController extends Controller
{
     /**
     * Sync transactions from Revolut
     */
    public function sync() 
    {    	
    	$revolut = new Revolut;
    	$slack = new Slack;
    	$freeagent = new Freeagent;

    	$accounts = env('REVOLUT_TO_FREEAGENT', '');
    	$accounts = explode(',',$accounts);

    	$accountLookup = [];

    	foreach ($accounts as $key => $account) {
    		$details = explode(':',$account);
    		$accountLookup[$details[0]] = $details[1];
    	}

    	$last = Transaction::orderBy('updated_at', 'desc')->first();
    	$from = false;
    	$alerts = [];

    	if ( $last ) {
    		$from = Carbon::now()->subDays(1)->toDateString();
    	}

    	$transactions = $revolut->getTransactions($from);

    	foreach ($transactions as $transaction) {
    		$transaction->created_at = Carbon::parse($transaction->created_at);
    		$transaction->updated_at = Carbon::parse($transaction->updated_at);
    		if ( isset( $transaction->completed_at) ) {
    			$transaction->completed_at = Carbon::parse($transaction->completed_at);	
    		}
    		$transaction->legs = json_encode( $transaction->legs );
    		$transaction->transaction_id = $transaction->id;
    		unset( $transaction->id );

    		$current = Transaction::where('transaction_id', $transaction->transaction_id)->first();

    		if ( $current ) {
    			if ( $current->state !== $transaction->state ) {
    				$current->fill((array)$transaction)->save();
    				$current->update = true;
    				$alerts[] = $current;
    			}
    		} else {
    			$current = Transaction::create((array)$transaction);
    			$current->update = false;
    			$alerts[] = $current;
    		}
    	}

    	$freeagentTransactions = [];

    	$response = [];
    	foreach ( $alerts as $alert ) {
    		$message = $revolut->getTransactionSummary($alert);
    		$total = '£' . number_format($revolut->getTotalBalance(), 2);
    		$accounts = $revolut->getAccountsSummary();
    		$response[] = $message;
    		$slack->transactionWithBalance($message, $total, $accounts);

    		$legs = $alert->getLegs();

    		if ( $alert->state !== 'completed' ) {
    			continue;
    		}

    		if ( $alert->type == 'exchange' ) {
    			$currentAccount = $accountLookup[$legs[0]->account_id];

    			$freeagentTransactions[$currentAccount][] = [ 
    				$alert->updated_at->format('d/m/Y'),
    				number_format( (float) $legs[0]->amount, 2, '.', ''),
    				$message
    			];

    			$currentAccount = $accountLookup[$legs[1]->account_id];

    			$freeagentTransactions[$currentAccount][] = [ 
    				$alert->updated_at->format('d/m/Y'),
    				number_format( (float) $legs[1]->amount, 2, '.', ''),
    				$message
    			];
    		} else {
                if ( isset( $accountLookup[$legs[0]->account_id] ) ) {
        			$currentAccount = $accountLookup[$legs[0]->account_id];

        			$freeagentTransactions[$currentAccount][] = [ 
        				$alert->updated_at->format('d/m/Y'),
        				number_format( (float) $legs[0]->amount, 2, '.', ''),
        				$message
        			];
                }
    		}
    	}

    	foreach ( $freeagentTransactions as $id => $statement) {
    		$csv = $this->generateCsv($statement);
    		$freeagent->updateBankStatement($id, $csv);
    	}
    	return response()->json($response);
    }

    public function freeagentSetup() {
    	$freeagent = new Freeagent(); 
  
    	return redirect()->to(
		    $freeagent->getAuthorizationUrl()
		);
    }

    public function freeagentOauth(Request $request) {
   		$freeagent = new Freeagent();

		$freeagent->getTokenFromCode( $request->input('code') );

		return response()->json('Success');
    }

	function generateCsv($data, $delimiter = ',', $enclosure = '"') {
		$contents = '';
		$handle = fopen('php://temp', 'r+');
		foreach ($data as $line) {
		   fputcsv($handle, $line, $delimiter, $enclosure);
		}
		rewind($handle);
		while (!feof($handle)) {
		   $contents .= fread($handle, 8192);
		}
		fclose($handle);
		return $contents;
	}
}
