<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminRole;
use App\Services\Admin\AdminPermissionCatalog;
use App\Services\Admin\AuditLogger;
use App\Services\Admin\DefaultAdminRoles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    public function index(Request $request, DefaultAdminRoles $defaults): Response
    {
        abort_unless($request->user()->adminRole?->slug === 'super-admin', 403);
        $defaultSlugs = array_keys($defaults->definitions());

        return Inertia::render('Admin/Roles/Index', [
            'roles' => AdminRole::query()->withCount('users')->orderBy('id')->get()->map(fn (AdminRole $role): array => [
                'id' => $role->id,
                'name' => $role->name,
                'slug' => $role->slug,
                'permissions' => $role->permissions ?? [],
                'isDefault' => in_array($role->slug, $defaultSlugs, true),
                'usersCount' => $role->users_count,
            ])->all(),
            'permissionOptions' => AdminPermissionCatalog::permissions(),
        ]);
    }

    public function store(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        abort_unless($request->user()->adminRole?->slug === 'super-admin', 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', 'unique:admin_roles,name'],
            'slug' => ['required', 'string', 'max:80', 'alpha_dash', 'unique:admin_roles,slug'],
            'permissions' => ['present', 'array', 'max:40'],
            'permissions.*' => ['required', 'string', Rule::in(AdminPermissionCatalog::permissions())],
        ]);
        $role = AdminRole::query()->create([...$data, 'permissions' => array_values(array_unique($data['permissions']))]);
        $auditLogger->record('role.created', $request->user(), 'admin_role', $role->id, ['slug' => $role->slug, 'permission_count' => count($role->permissions)]);

        return back()->with('success', 'Role created.');
    }

    public function update(Request $request, AdminRole $role, AuditLogger $auditLogger): RedirectResponse
    {
        abort_unless($request->user()->adminRole?->slug === 'super-admin', 403);
        abort_if($role->slug === 'super-admin', 422, 'The Super Admin role cannot be edited.');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('admin_roles', 'name')->ignore($role->id)],
            'permissions' => ['present', 'array', 'max:40'],
            'permissions.*' => ['required', 'string', Rule::in(AdminPermissionCatalog::permissions())],
        ]);
        $data['permissions'] = array_values(array_unique($data['permissions']));
        $role->update($data);
        $auditLogger->record('role.updated', $request->user(), 'admin_role', $role->id, ['permission_count' => count($data['permissions'])]);

        return back()->with('success', 'Role permissions updated.');
    }

    public function destroy(Request $request, AdminRole $role, DefaultAdminRoles $defaults, AuditLogger $auditLogger): RedirectResponse
    {
        abort_unless($request->user()->adminRole?->slug === 'super-admin', 403);
        abort_if(in_array($role->slug, array_keys($defaults->definitions()), true), 422, 'Built-in roles cannot be deleted.');
        abort_if($role->users()->exists(), 422, 'Reassign staff before deleting a role.');
        $roleId = $role->id;
        $roleSlug = $role->slug;
        $role->delete();
        $auditLogger->record('role.deleted', $request->user(), 'admin_role', $roleId, ['slug' => $roleSlug]);

        return back()->with('success', 'Role deleted.');
    }
}
