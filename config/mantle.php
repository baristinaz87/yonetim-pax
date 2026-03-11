<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mantle Static Token
    |--------------------------------------------------------------------------
    |
    | Static bearer token used to authenticate incoming requests from the
    | Mantle integration partner. This must be set in the .env file.
    |
    */

    'static_token' => env('MANTLE_STATIC_TOKEN', ''),

];
