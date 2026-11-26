<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $hasDatabaseSessions = config('session.driver') === 'database' && Schema::hasTable('sessions');

        return Inertia::render('Admin/Profile/Index', [
            'profile' => ['name' => $user->name, 'email' => $user->email, 'role' => $user->adminRole?->name],
            'mfaEnabled' => $user->two_factor_enabled,
            'recoveryCodesRemaining' => count($user->two_factor_recovery_codes ?? []),
            'sessions' => [
                'databaseManaged' => $hasDatabaseSessions,
                'otherCount' => $hasDatabaseSessions ? DB::table('sessions')->where('user_id', $user->id)->where('id', '!=', $request->session()->getId())->count() : null,
            ],
        ]);
    }

    public function update(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
        ]);
        $user->forceFill(['name' => $data['name'], 'email' => mb_strtolower($data['email'])])->save();
        $auditLogger->record('profile.updated', $user, 'user', $user->id, ['fields' => ['name', 'email']]);

        return back()->with('success', 'Profile updated.');
    }

    public function updatePassword(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', PasswordRule::min(14)->mixedCase()->numbers()],
        ]);
        abort_unless(Hash::check($data['current_password'], $request->user()->password), 422, 'The current password is incorrect.');
        $request->user()->forceFill(['password' => Hash::make($data['password']), 'remember_token' => Str::random(60)])->save();
        $this->deleteOtherDatabaseSessions($request);
        $auditLogger->record('profile.password_changed', $request->user(), 'user', $request->user()->id);

        return back()->with('success', 'Password updated. Other sessions were revoked when database session storage is enabled.');
    }

    public function revokeOtherSessions(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $data = $request->validate(['current_password' => ['required', 'string']]);
        abort_unless(Hash::check($data['current_password'], $request->user()->password), 422, 'The current password is incorrect.');
        abort_unless($this->deleteOtherDatabaseSessions($request), 422, 'Central session revocation requires the database session driver.');
        $auditLogger->record('profile.other_sessions_revoked', $request->user(), 'user', $request->user()->id);

        return back()->with('success', 'Other sessions were signed out.');
    }

    private function deleteOtherDatabaseSessions(Request $request): bool
    {
        if (config('session.driver') !== 'database' || ! Schema::hasTable('sessions')) {
            return false;
        }

        DB::table('sessions')->where('user_id', $request->user()->id)->where('id', '!=', $request->session()->getId())->delete();

        return true;
    }
}
