<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminRole;
use App\Models\User;
use App\Services\Admin\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Inertia\Inertia;
use Inertia\Response;

class StaffController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->hasAdminPermission('users.manage'), 403);
        $isSuperAdmin = $request->user()->adminRole->slug === 'super-admin';

        return Inertia::render('Admin/Staff/Index', [
            'staff' => User::query()->with('adminRole:id,name,slug')->orderBy('name')->paginate(25)->withQueryString()->through(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roleId' => $user->admin_role_id,
                'role' => $user->adminRole?->name,
                'roleSlug' => $user->adminRole?->slug,
                'teamManagerId' => $user->team_manager_id,
                'active' => $user->is_active,
                'lastLoginAt' => $user->last_login_at?->toIso8601String(),
            ]),
            'roles' => AdminRole::query()->when(! $isSuperAdmin, fn ($query) => $query->where('slug', '!=', 'super-admin'))->orderBy('id')->get(['id', 'name', 'slug']),
            'teamManagers' => User::query()->where('is_active', true)->whereHas('adminRole', fn ($query) => $query->where('slug', 'sales-manager'))->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor->hasAdminPermission('users.manage'), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', PasswordRule::min(14)->mixedCase()->numbers()],
            'admin_role_id' => ['required', 'integer', 'exists:admin_roles,id'],
            'team_manager_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);
        $role = AdminRole::query()->findOrFail($data['admin_role_id']);
        $this->validateTeamAssignment($data['team_manager_id'] ?? null, $role);
        abort_if($actor->adminRole->slug !== 'super-admin' && in_array($role->slug, ['admin', 'super-admin'], true), 403);
        abort_if($role->slug === 'super-admin' && $actor->adminRole->slug !== 'super-admin', 403);
        $staff = User::query()->create([
            'name' => $data['name'],
            'email' => mb_strtolower($data['email']),
            'password' => Hash::make($data['password']),
            'admin_role_id' => $role->id,
            'team_manager_id' => $data['team_manager_id'] ?? null,
            'is_active' => true,
        ]);
        $auditLogger->record('staff.created', $actor, 'user', $staff->id, ['role' => $role->slug]);

        return back()->with('success', 'Staff account created.');
    }

    public function update(Request $request, User $staff, AuditLogger $auditLogger): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor->hasAdminPermission('users.manage'), 403);
        $data = $request->validate(['admin_role_id' => ['required', 'integer', 'exists:admin_roles,id'], 'is_active' => ['required', 'boolean'], 'team_manager_id' => ['nullable', 'integer', 'exists:users,id']]);
        $role = AdminRole::query()->findOrFail($data['admin_role_id']);
        $this->validateTeamAssignment($data['team_manager_id'] ?? null, $role);
        $isSelf = $actor->is($staff);
        abort_if($isSelf && (! $data['is_active'] || $role->id !== $staff->admin_role_id), 422, 'You cannot deactivate yourself or change your own role.');
        abort_if($actor->adminRole->slug !== 'super-admin' && (in_array($role->slug, ['admin', 'super-admin'], true) || in_array($staff->adminRole?->slug, ['admin', 'super-admin'], true)), 403);

        if ($staff->adminRole?->slug === 'super-admin' && (! $data['is_active'] || $role->slug !== 'super-admin')) {
            $activeSuperAdmins = User::query()->where('is_active', true)->whereHas('adminRole', fn ($query) => $query->where('slug', 'super-admin'))->count();
            abort_if($activeSuperAdmins <= 1, 422, 'The last active Super Admin cannot be disabled or demoted.');
        }
        abort_if($staff->adminRole?->slug === 'sales-manager' && ($role->slug !== 'sales-manager' || ! $data['is_active']) && $staff->teamMembers()->exists(), 422, 'Reassign this manager’s team before changing their role or access.');

        $staff->update(['admin_role_id' => $role->id, 'team_manager_id' => $data['team_manager_id'] ?? null, 'is_active' => (bool) $data['is_active']]);
        $auditLogger->record('staff.updated', $actor, 'user', $staff->id, ['role' => $role->slug, 'is_active' => (bool) $data['is_active'], 'team_manager_id' => $data['team_manager_id'] ?? null]);

        return back()->with('success', 'Staff account updated.');
    }

    public function sendPasswordReset(Request $request, User $staff, AuditLogger $auditLogger): RedirectResponse
    {
        abort_unless($request->user()->hasAdminPermission('users.manage'), 403);
        $status = Password::sendResetLink(['email' => $staff->email]);
        $auditLogger->record('staff.password_reset_requested', $request->user(), 'user', $staff->id);

        return back()->with('success', $status === Password::RESET_LINK_SENT ? 'Password reset email queued.' : 'Password reset could not be sent. Check the mail configuration.');
    }

    private function validateTeamAssignment(?int $managerId, AdminRole $role): void
    {
        if (! $managerId) {
            return;
        }

        abort_unless(in_array($role->slug, ['dsa-agent', 'customer-support'], true), 422, 'Only sales and support staff can be assigned to a team manager.');
        abort_unless(User::query()->whereKey($managerId)->where('is_active', true)->whereHas('adminRole', fn ($query) => $query->where('slug', 'sales-manager'))->exists(), 422, 'Choose an active Sales Manager.');
    }
}
