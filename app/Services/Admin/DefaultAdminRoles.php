<?php

namespace App\Services\Admin;

use App\Models\AdminRole;

class DefaultAdminRoles
{
    /** @return array<string, array{name: string, permissions: list<string>}> */
    public function definitions(): array
    {
        return [
            'super-admin' => ['name' => 'Super Admin', 'permissions' => ['*']],
            'admin' => ['name' => 'Admin', 'permissions' => ['dashboard.view', 'enquiries.view', 'enquiries.view_sensitive', 'enquiries.edit', 'enquiries.assign', 'enquiries.status', 'enquiries.note', 'followups.manage', 'tasks.manage', 'applications.manage', 'customers.view', 'customers.manage', 'reports.view', 'reports.export', 'products.manage', 'partners.manage', 'content.manage', 'news.manage', 'videos.manage', 'users.manage', 'audit.view', 'settings.manage', 'notifications.view', 'contact_messages.view', 'contact_messages.view_sensitive']],
            'sales-manager' => ['name' => 'Sales Manager', 'permissions' => ['dashboard.view', 'enquiries.view', 'enquiries.view_sensitive', 'enquiries.edit', 'enquiries.assign', 'enquiries.status', 'enquiries.note', 'followups.manage', 'tasks.manage', 'applications.manage', 'customers.view', 'customers.manage', 'reports.view', 'reports.export', 'notifications.view', 'contact_messages.view']],
            'dsa-agent' => ['name' => 'DSA Agent', 'permissions' => ['dashboard.view', 'enquiries.view', 'enquiries.edit_assigned', 'enquiries.status_assigned', 'enquiries.note_assigned', 'followups.assigned', 'tasks.assigned', 'applications.assigned', 'notifications.view']],
            'customer-support' => ['name' => 'Customer Support', 'permissions' => ['dashboard.view', 'enquiries.view_assigned', 'enquiries.note_assigned', 'followups.assigned', 'tasks.assigned', 'notifications.view', 'contact_messages.view', 'contact_messages.view_sensitive']],
            'content-manager' => ['name' => 'Content Manager', 'permissions' => ['dashboard.view', 'content.manage', 'news.manage', 'videos.manage', 'notifications.view']],
            'read-only-viewer' => ['name' => 'Read-only Viewer', 'permissions' => ['dashboard.view', 'enquiries.view', 'reports.view']],
        ];
    }

    public function sync(): AdminRole
    {
        $superAdmin = null;

        foreach ($this->definitions() as $slug => $definition) {
            $role = AdminRole::query()->updateOrCreate(
                ['slug' => $slug],
                ['name' => $definition['name'], 'permissions' => $definition['permissions']],
            );

            if ($slug === 'super-admin') {
                $superAdmin = $role;
            }
        }

        return $superAdmin;
    }
}
