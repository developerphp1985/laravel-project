<?php

return [
	/*
    |--------------------------------------------------------------------------
    | 
    |--------------------------------------------------------------------------
    |
    |
    */

    'merchant_id' => env('COINPAYMENTS_MERCHANT_ID', null),

    /*
    |--------------------------------------------------------------------------
    | 
    |--------------------------------------------------------------------------
    |
    | Your API public key associated with your coinpayments account
    |
    */
    
    'public_key' => env('COINPAYMENTS_PUBLIC_KEY', null),

    /*
    |--------------------------------------------------------------------------
    | 
    |--------------------------------------------------------------------------
    |
    | Your API private key associated with your coinpayments account
    |
    */

    'private_key' => env('COINPAYMENTS_PRIVATE_KEY', null),

    /*
    |--------------------------------------------------------------------------
    | 
    |--------------------------------------------------------------------------
    |
    | This is used to verify that an IPN is from us, use a good random string nobody can guess.
    |
    */

    'ipn_secret' => env('COINPAYMENTS_IPN_SECRET', null),

    /*
    |--------------------------------------------------------------------------
    | 
    |--------------------------------------------------------------------------
    |
    | URL for your IPN callbacks. If not set it will use the IPN URL in your Edit Settings page if you have one set.
    |
    */

    'ipn_url' => env('COINPAYMENTS_IPN_URL', null),

    /*
    |--------------------------------------------------------------------------
    | 
    |--------------------------------------------------------------------------
    |
    | The format of response to return, json or xml. (default: json)
    |
    */

    'format' => env('COINPAYMENTS_API_FORMAT', 'json'),
	

];
