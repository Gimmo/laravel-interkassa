<?php

return [
    /**
     * Your merchant ID
     */
    'merchant_id' => env('INTERKASSA_MERCHANT_ID', ''),

    /**
     * Your secret key
     */
    'secret_key' => env('INTERKASSA_SECRET_KEY', ''),

    /**
     * Search order in the database and return order details
     */
    'searchOrder' => null, //  'App\Http\Controllers\InterkassaController@searchOrder',

    /**
     * If current status != paid then call PaidOrderFilter
     * update order into DB & other actions
     */
    'paidOrder' => null, //  'App\Http\Controllers\InterkassaController@paidOrder',

    /**
     * Default currency for payments
     */
    'currency' => 'USD',

    /**
     * Allowed IP's
     */
    'allowed_ips' => [ 
        '34.77.232.58', 
        '35.240.117.224', 
        '35.233.69.55'
    ],

    /**
     * Allow local?
     */
    'locale' => true,

    /**
     * Interkassa merchant URL
     */
    'url' => 'https://sci.interkassa.com/'
];