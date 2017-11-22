<?php

namespace App\Services;

use GuzzleHttp\Client;

class Slack {

    public function __construct()
    {
        $this->client = new Client();
    }

    public function send($message)
    {
        $webhook = config('slack.webhook');

        $payload = json_encode([
            'text' => $message,
        ]);

        $response = $this->client->post($webhook,['body' => $payload]);

        return $response;   
    }

    public function transactionWithBalance($summary, $balance, $accounts) 
    {
        
        $webhook = config('slack.webhook');

        $payload = '{
            "attachments": [
                {
                    "text": "*Transaction:* '.$summary.'",
                    "callback_id": "revolut_transaction",
                    "color": "#3AA3E3",
                    "attachment_type": "default",
                    "mrkdwn_in": ["text", "pretext"],
                    "actions": [ ]
                },
                {
                    "text": "*Balance:* '.$balance.'",
                    "callback_id": "revolut_balance",
                    "color": "#36a64f",
                    "attachment_type": "default",
                    "mrkdwn_in": ["text", "pretext"],
                    "actions": [
                    ]
                },
                {
                    "text": "'.$accounts.'",
                    "callback_id": "revolut_accounts",
                    "color": "#ff8f02",
                    "attachment_type": "default",
                    "mrkdwn_in": ["text", "pretext"],
                    "actions": [
                    ]
                }
            ]
        }';

        $response = $this->client->post($webhook,['body' => $payload]);

        return $response;
    }

}