<?php

namespace App\Services\Admin;

class AdminPermissionCatalog
{
    /** @return list<string> */
    public static function permissions(): array
    {
        return [
            'dashboard.view',
            'enquiries.view', 'enquiries.view_sensitive', 'enquiries.edit', 'enquiries.assign', 'enquiries.status', 'enquiries.note',
            'enquiries.view_assigned', 'enquiries.edit_assigned', 'enquiries.status_assigned', 'enquiries.note_assigned',
            'followups.manage', 'followups.assigned', 'tasks.manage', 'tasks.assigned', 'applications.manage', 'applications.assigned',
            'customers.view', 'customers.manage', 'reports.view', 'reports.export', 'products.manage', 'partners.manage', 'content.manage', 'news.manage',
            'videos.manage', 'users.manage', 'audit.view', 'settings.manage', 'notifications.view', 'contact_messages.view',
            'contact_messages.view_sensitive',
        ];
    }
}
