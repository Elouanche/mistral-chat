<?php

// Configuration ShipEngine
define('SHIPENGINE_API_KEY', 'votre_clé_api_ici');

// Mapping des transporteurs ShipEngine
define('SHIPENGINE_CARRIERS', [
    'UPS' => [
        'carrier_id' => 'se-xxxxx',
        'service_code' => 'ups_ground'
    ],
    'FedEx' => [
        'carrier_id' => 'se-yyyyy',
        'service_code' => 'fedex_ground'
    ],
    'DHL' => [
        'carrier_id' => 'se-zzzzz',
        'service_code' => 'dhl_express'
    ]
]);

// Adresse d'expédition par défaut
define('DEFAULT_SHIP_FROM', [
    'company_name' => 'Votre Entreprise',
    'name' => 'Service Expédition',
    'phone' => '0123456789',
    'address_line1' => '123 Rue Commerce',
    'city_locality' => 'Paris',
    'state_province' => 'IDF',
    'postal_code' => '75000',
    'country_code' => 'FR'
]);
