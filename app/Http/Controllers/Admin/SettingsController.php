<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use App\Models\LoanCategory;
use App\Services\Admin\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    private const KEYS = ['name', 'tagline', 'proprietor', 'phone', 'office_phone', 'whatsapp', 'email', 'address', 'city', 'state', 'postal_code', 'service_areas', 'business_hours', 'grievance_email', 'website_url', 'facebook_url', 'instagram_url', 'linkedin_url', 'new_enquiry_notifications_enabled', 'contact_notifications_enabled', 'news_sync_enabled', 'crm_reminder_email_enabled'];

    public function index(Request $request): Response
    {
        abort_unless($request->user()->hasAdminPermission('settings.manage'), 403);
        $settings = BusinessSetting::query()->whereIn('key', self::KEYS)->pluck('value', 'key')->all();
        foreach (self::KEYS as $key) {
            $settings[$key] ??= match ($key) {
                'service_areas' => implode(', ', config('sahu.service_areas', [])),
                'new_enquiry_notifications_enabled', 'contact_notifications_enabled' => '1',
                'news_sync_enabled' => config('news.pib.enabled') ? '1' : '0',
                'crm_reminder_email_enabled' => '0',
                default => config('sahu.'.$key),
            };
        }

        $supportedCategories = LoanCategory::query()
            ->where('is_enabled', true)
            ->whereHas('products', fn ($query) => $query->where('loan_products.is_enabled', true))
            ->orderBy('display_order')
            ->pluck('name')
            ->all();

        return Inertia::render('Admin/Settings/Index', [
            'settings' => $settings,
            'supportedCategories' => $supportedCategories,
        ]);
    }

    public function update(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        abort_unless($request->user()->hasAdminPermission('settings.manage'), 403);
        $data = $request->validate(['name' => ['required', 'string', 'max:160'], 'tagline' => ['nullable', 'string', 'max:240'], 'proprietor' => ['nullable', 'string', 'max:160'], 'phone' => ['nullable', 'string', 'max:40'], 'office_phone' => ['nullable', 'string', 'max:40'], 'whatsapp' => ['nullable', 'string', 'max:40'], 'email' => ['nullable', 'email', 'max:160'], 'address' => ['nullable', 'string', 'max:500'], 'city' => ['nullable', 'string', 'max:120'], 'state' => ['nullable', 'string', 'max:120'], 'postal_code' => ['nullable', 'string', 'max:20'], 'service_areas' => ['nullable', 'string', 'max:1000'], 'business_hours' => ['nullable', 'string', 'max:180'], 'grievance_email' => ['nullable', 'email', 'max:160'], 'website_url' => ['nullable', 'url', 'starts_with:https://', 'max:500'], 'facebook_url' => ['nullable', 'url', 'starts_with:https://', 'max:500'], 'instagram_url' => ['nullable', 'url', 'starts_with:https://', 'max:500'], 'linkedin_url' => ['nullable', 'url', 'starts_with:https://', 'max:500'], 'new_enquiry_notifications_enabled' => ['required', 'boolean'], 'contact_notifications_enabled' => ['required', 'boolean'], 'news_sync_enabled' => ['required', 'boolean'], 'crm_reminder_email_enabled' => ['required', 'boolean']]);
        foreach (['new_enquiry_notifications_enabled', 'contact_notifications_enabled', 'news_sync_enabled', 'crm_reminder_email_enabled'] as $key) {
            $data[$key] = $data[$key] ? '1' : '0';
        }
        foreach ($data as $key => $value) {
            BusinessSetting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }
        $auditLogger->record('settings.business_updated', $request->user(), 'business_settings', null, ['keys' => array_keys($data)]);

        return back()->with('success', 'Business details updated.');
    }
}
