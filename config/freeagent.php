<?php

return [

	'sandbox' => env('FREEAGENT_SANDBOX', true),
	'client_id' => env('FREEAGENT_CLIENT_ID', ''),
	'client_secret' => env('FREEAGENT_CLIENT_SECRET', ''),
	'response_type' => 'json',
	'redirect_uri' =>  env('FREEAGENT_REDIRECT_URI', ''),

];
