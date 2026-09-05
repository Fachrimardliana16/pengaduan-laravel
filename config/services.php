<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    | Nilai-nilai di bawah dibaca dari file .env. Jangan hard-code token/secret
    | langsung di sini.
    */

    'mailgun' => [
        'domain'   => env('MAILGUN_DOMAIN'),
        'secret'   => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme'   => 'https',
    ],

    // reCAPTCHA v2
    'recaptcha' => [
        'site_key' => env('RECAPTCHA_SITE_KEY'),
        'secret'   => env('RECAPTCHA_SECRET_KEY'),
    ],

    // Fonnte WhatsApp Gateway
    'fonnte' => [
        'token'          => env('FONNTE_TOKEN'),
        'group_targets'  => env('FONNTE_GROUP_TARGETS'),
    ],

    // PDAM SOAP Web Service
    'pdam' => [
        'wsdl' => env('PDAM_SOAP_WSDL', 'http://36.93.220.243/Pis.Billing.Web/pis.webservice.asmx?wsdl'),
    ],

    // Google Maps JavaScript API (tidak lagi digunakan — pakai Leaflet.js)
    // 'google_maps' => ['key' => env('GOOGLE_MAPS_API_KEY')],

];
