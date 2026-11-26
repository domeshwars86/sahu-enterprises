<?php

return [
    'name' => env('SAHU_BUSINESS_NAME') ?: 'SAHU ENTERPRISES',
    'proprietor' => env('SAHU_PROPRIETOR_NAME') ?: 'Khuleshwar Sahu',
    'domain' => env('SAHU_ENTERPRISES_DOMAIN'),
    'tagline' => env('SAHU_TAGLINE') ?: 'Financial Services',
    'phone' => env('SAHU_BUSINESS_PHONE') ?: '+91 9131840194',
    'office_phone' => env('SAHU_OFFICE_PHONE') ?: '+91 8982868291',
    'whatsapp' => env('SAHU_WHATSAPP_NUMBER') ?: '+91 9131840194',
    'email' => env('SAHU_BUSINESS_EMAIL') ?: 'sahuenterprises0707@gmail.com',
    'address' => env('SAHU_BUSINESS_ADDRESS') ?: '327, 3rd Floor, Orbit Tower, Sunder Nagar',
    'city' => env('SAHU_BUSINESS_CITY') ?: 'Raipur',
    'state' => env('SAHU_BUSINESS_STATE') ?: 'Chhattisgarh',
    'postal_code' => env('SAHU_BUSINESS_POSTAL_CODE') ?: '492001',
    'service_areas' => array_values(array_filter(array_map(
        trim(...),
        explode(',', (string) env('SAHU_SERVICE_AREAS', '')),
    ))),
    'business_hours' => env('SAHU_BUSINESS_HOURS'),
    'grievance_email' => env('SAHU_GRIEVANCE_EMAIL'),
    'social_profiles' => [],
    'verified_lending_partners' => [],
];
