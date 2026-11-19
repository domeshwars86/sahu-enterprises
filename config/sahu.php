<?php

return [
    'name' => env('SAHU_BUSINESS_NAME', 'Sahu Enterprises'),
    'domain' => env('SAHU_ENTERPRISES_DOMAIN'),
    'tagline' => env('SAHU_TAGLINE', 'Your Financial Goals. Our Guidance.'),
    'phone' => env('SAHU_BUSINESS_PHONE'),
    'whatsapp' => env('SAHU_WHATSAPP_NUMBER'),
    'email' => env('SAHU_BUSINESS_EMAIL'),
    'address' => env('SAHU_BUSINESS_ADDRESS'),
    'city' => env('SAHU_BUSINESS_CITY'),
    'state' => env('SAHU_BUSINESS_STATE'),
    'service_areas' => array_values(array_filter(array_map(
        trim(...),
        explode(',', (string) env('SAHU_SERVICE_AREAS', '')),
    ))),
    'business_hours' => env('SAHU_BUSINESS_HOURS'),
    'grievance_email' => env('SAHU_GRIEVANCE_EMAIL'),
    'social_profiles' => [],
    'verified_lending_partners' => [],
];
