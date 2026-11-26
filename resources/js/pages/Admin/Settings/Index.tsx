import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AdminLayout from '../../../components/AdminLayout';

type Settings = Record<string, string | null>;
type TextKey = 'name' | 'tagline' | 'proprietor' | 'phone' | 'office_phone' | 'whatsapp' | 'email' | 'address' | 'city' | 'state' | 'postal_code' | 'service_areas' | 'business_hours' | 'grievance_email' | 'website_url' | 'facebook_url' | 'instagram_url' | 'linkedin_url';

export default function Index({ settings, supportedCategories }: { settings: Settings; supportedCategories: string[] }) {
    const form = useForm({
        name: settings.name ?? '', tagline: settings.tagline ?? '', proprietor: settings.proprietor ?? '', phone: settings.phone ?? '', office_phone: settings.office_phone ?? '', whatsapp: settings.whatsapp ?? '',
        email: settings.email ?? '', address: settings.address ?? '', city: settings.city ?? '', state: settings.state ?? '', postal_code: settings.postal_code ?? '',
        service_areas: settings.service_areas ?? '', business_hours: settings.business_hours ?? '', grievance_email: settings.grievance_email ?? '',
        website_url: settings.website_url ?? '', facebook_url: settings.facebook_url ?? '', instagram_url: settings.instagram_url ?? '', linkedin_url: settings.linkedin_url ?? '',
        new_enquiry_notifications_enabled: settings.new_enquiry_notifications_enabled !== '0',
        contact_notifications_enabled: settings.contact_notifications_enabled !== '0',
        news_sync_enabled: settings.news_sync_enabled === '1',
        crm_reminder_email_enabled: settings.crm_reminder_email_enabled === '1',
    });
    const field = (key: TextKey, label: string, type = 'text') => <label>{label}<input type={type} value={form.data[key]} onChange={event => form.setData(key, event.target.value)}/>{form.errors[key] && <small className="admin-form-error">{form.errors[key]}</small>}</label>;
    const toggle = (key: 'new_enquiry_notifications_enabled' | 'contact_notifications_enabled' | 'news_sync_enabled' | 'crm_reminder_email_enabled', label: string) => <label className="admin-content-check"><input type="checkbox" checked={form.data[key]} onChange={event => form.setData(key, event.target.checked)}/>{label}</label>;

    return <>
        <Head title="Business settings · Admin"/>
        <AdminLayout title="Business settings">
            <p className="admin-muted">Manage public business details and operational toggles. Sensitive environment secrets are not shown or editable here.</p>
            <section className="admin-panel admin-content-panel admin-settings-panel"><form onSubmit={event => { event.preventDefault(); form.put('/admin/settings'); }}>
                <h2>Company settings</h2>
                {field('name', 'Business name')}{field('tagline', 'Business category / subtitle')}{field('proprietor', 'Proprietor name')}{field('phone', 'Primary mobile number')}{field('office_phone', 'Office contact number')}{field('whatsapp', 'Official WhatsApp number')}{field('email', 'Business email', 'email')}
                <p className="admin-muted">WhatsApp is customer initiated. The number is used only to build click-to-chat links; messages are never sent automatically.</p>
                <label>Business address<textarea rows={3} value={form.data.address} onChange={event => form.setData('address', event.target.value)}/></label>
                {field('city', 'City')}{field('state', 'State')}{field('postal_code', 'Postal code')}
                <label>Service areas (comma separated)<input value={form.data.service_areas} onChange={event => form.setData('service_areas', event.target.value)}/></label>
                {field('business_hours', 'Business hours')}{field('grievance_email', 'Grievance email', 'email')}
                <h2>Publicly supported loan categories</h2>
                <p className="admin-muted">This list reflects enabled categories with at least one enabled product. Manage verified availability in the existing category and product catalogue.</p>
                <div className="supported-category-list">{supportedCategories.length ? supportedCategories.map(category => <span key={category}>{category}</span>) : <span>No loan categories are currently enabled.</span>}</div>
                <Link className="text-link" href="/admin/loan-categories">Manage loan categories →</Link>
                <h2>Social links</h2>
                {field('website_url', 'Website URL', 'url')}{field('facebook_url', 'Facebook URL', 'url')}{field('instagram_url', 'Instagram URL', 'url')}{field('linkedin_url', 'LinkedIn URL', 'url')}
                <h2>Notifications and news</h2>
                {toggle('new_enquiry_notifications_enabled', 'In-app alerts for new loan enquiries')}
                {toggle('contact_notifications_enabled', 'In-app alerts for new contact messages')}
                {toggle('news_sync_enabled', 'Allow the configured PIB headline sync')}
                {toggle('crm_reminder_email_enabled', 'Email assigned staff when CRM work is due (requires production mail configuration)')}
                <button className="admin-primary-button" disabled={form.processing}>Save settings</button>
            </form></section>
        </AdminLayout>
    </>;
}
