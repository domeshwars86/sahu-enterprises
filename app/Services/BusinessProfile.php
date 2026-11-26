<?php

namespace App\Services;

use App\Models\BusinessSetting;
use Illuminate\Support\Facades\Schema;

class BusinessProfile
{
    /** @var array<string, mixed>|null */
    private ?array $settings = null;

    public function get(string $key): mixed
    {
        $this->settings ??= Schema::hasTable('business_settings')
            ? BusinessSetting::query()->pluck('value', 'key')->all()
            : [];

        return $this->settings[$key] ?? config('sahu.'.$key);
    }

    /** @return array<string, mixed> */
    public function publicDetails(): array
    {
        $address = trim(implode(', ', array_filter([
            preg_replace('/\s+/', ' ', (string) $this->get('address')),
            $this->get('city'),
            $this->get('state'),
            $this->get('postal_code'),
            'India',
        ])));
        $mapQuery = http_build_query(['api' => 1, 'query' => $address]);
        $directionsQuery = http_build_query(['api' => 1, 'destination' => $address]);

        return [
            'name' => $this->get('name'),
            'tagline' => $this->get('tagline'),
            'proprietor' => $this->get('proprietor'),
            'phone' => $this->get('phone'),
            'officePhone' => $this->get('office_phone'),
            'whatsapp' => $this->get('whatsapp'),
            'email' => $this->get('email'),
            'address' => $this->get('address'),
            'city' => $this->get('city'),
            'state' => $this->get('state'),
            'postalCode' => $this->get('postal_code'),
            'mapUrl' => 'https://www.google.com/maps/search/?'.$mapQuery,
            'directionsUrl' => 'https://www.google.com/maps/dir/?'.$directionsQuery,
            'serviceAreas' => $this->serviceAreas(),
            'businessHours' => $this->get('business_hours'),
            'grievanceEmail' => $this->get('grievance_email'),
            'socialLinks' => array_filter([
                'Website' => $this->get('website_url'),
                'Facebook' => $this->get('facebook_url'),
                'Instagram' => $this->get('instagram_url'),
                'LinkedIn' => $this->get('linkedin_url'),
            ]),
        ];
    }

    public function notificationEmail(): ?string
    {
        return config('services.sahu.enquiry_to') ?: $this->get('email');
    }

    /** @return array<int, string> */
    private function serviceAreas(): array
    {
        $areas = $this->get('service_areas');

        return is_array($areas)
            ? array_values($areas)
            : array_values(array_filter(array_map(trim(...), explode(',', (string) $areas))));
    }
}
