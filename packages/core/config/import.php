<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Address data import (lunar:import:address-data)
    |--------------------------------------------------------------------------
    |
    | The JSON can be ~1.5 MB; slow links or Sail/Docker NAT may need a higher
    | timeout than the HTTP client default (~30s).
    |
    */
    'address_data' => [
        'url' => env('LUNAR_IMPORT_ADDRESS_DATA_URL', 'http://data.lunarphp.io/countries+states.json'),
        'timeout' => (int) env('LUNAR_IMPORT_ADDRESS_DATA_TIMEOUT', 180),
        'connect_timeout' => (int) env('LUNAR_IMPORT_ADDRESS_DATA_CONNECT_TIMEOUT', 60),
        'retries' => (int) env('LUNAR_IMPORT_ADDRESS_DATA_RETRIES', 3),
        'retry_sleep_ms' => (int) env('LUNAR_IMPORT_ADDRESS_DATA_RETRY_SLEEP_MS', 2000),
    ],

];
