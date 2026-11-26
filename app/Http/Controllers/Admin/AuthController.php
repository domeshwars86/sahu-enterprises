<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Admin\AuditLogger;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function create(): Response|RedirectResponse
    {
        return Auth::check() ? redirect()->route('admin.dashboard') : Inertia::render('Admin/Login');
    }

    public function store(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ]);
        $key = Str::lower($credentials['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['email' => 'Too many sign-in attempts. Please try again in a few minutes.'])->onlyInput('email');
        }

        if (! Auth::attempt(['email' => Str::lower($credentials['email']), 'password' => $credentials['password'], 'is_active' => true], (bool) ($credentials['remember'] ?? false))) {
            RateLimiter::hit($key, 900);
            $auditLogger->record('admin.login_failed', null, metadata: ['email_fingerprint' => hash_hmac('sha256', Str::lower($credentials['email']), (string) config('app.key'))]);

            return back()->withErrors(['email' => 'The email or password is incorrect.'])->onlyInput('email');
        }

        $user = $request->user();

        if (! $user instanceof User || ! $user->adminRole) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            RateLimiter::hit($key, 900);

            return back()->withErrors(['email' => 'This account is not enabled for admin access.'])->onlyInput('email');
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();
        $auditLogger->record('admin.login_succeeded', $user);

        if (in_array($user->adminRole->slug, ['super-admin', 'admin'], true)) {
            return redirect()->route($user->two_factor_enabled ? 'admin.security.challenge' : 'admin.security.setup');
        }

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $user = $request->user();
        $auditLogger->record('admin.logout', $user);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    public function forgotPassword(): Response
    {
        return Inertia::render('Admin/ForgotPassword');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email', 'max:255']]);
        Password::sendResetLink(['email' => Str::lower($request->string('email')->toString())]);

        return back()->with('status', 'If the staff account exists and email is configured, a password reset link will be sent.');
    }

    public function resetPassword(string $token): Response
    {
        return Inertia::render('Admin/ResetPassword', [
            'token' => $token,
            'email' => request()->query('email', ''),
        ]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(14)->mixedCase()->numbers()],
        ]);

        $status = Password::reset($credentials, function (User $user, string $password): void {
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($user));
        });

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('admin.login')->with('status', __($status))
            : back()->withErrors(['email' => [__($status)]])->onlyInput('email');
    }
}
