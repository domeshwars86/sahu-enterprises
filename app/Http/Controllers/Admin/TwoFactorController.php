<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AuditLogger;
use App\Services\Admin\Totp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TwoFactorController extends Controller
{
    public function setup(Request $request, Totp $totp): Response
    {
        $user = $request->user();
        abort_unless(in_array($user->adminRole?->slug, ['super-admin', 'admin'], true), 403);
        $secret = $user->two_factor_secret;

        if (! $secret) {
            $secret = $totp->newSecret();
            $user->forceFill(['two_factor_secret' => $secret])->save();
        }

        $label = rawurlencode('Sahu Enterprises:'.$user->email);
        $issuer = rawurlencode('Sahu Enterprises');

        return Inertia::render('Admin/TwoFactorSetup', ['secret' => $secret, 'otpauthUri' => "otpauth://totp/{$label}?secret={$secret}&issuer={$issuer}&digits=6&period=30"]);
    }

    public function enable(Request $request, Totp $totp, AuditLogger $auditLogger): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'digits:6']]);
        $user = $request->user();
        abort_unless($user->two_factor_secret && $totp->verify($user->two_factor_secret, $data['code']), 422, 'The verification code is invalid.');
        $recoveryCodes = collect(range(1, 8))->map(fn (): string => Str::upper(Str::random(12)))->all();
        $user->forceFill(['two_factor_enabled' => true, 'two_factor_recovery_codes' => array_map(fn (string $code): string => Hash::make($code), $recoveryCodes)])->save();
        $request->session()->put('admin_mfa_verified_at', now()->timestamp);
        $auditLogger->record('admin.mfa_enabled', $user);

        $request->session()->put('admin_mfa_recovery_codes_once', $recoveryCodes);

        return redirect()->route('admin.security.recovery-codes')->with('success', 'Two-factor authentication is enabled. Save these one-time recovery codes securely.');
    }

    public function recoveryCodes(Request $request): Response
    {
        $codes = $request->session()->pull('admin_mfa_recovery_codes_once');
        abort_unless(is_array($codes), 404);

        return Inertia::render('Admin/RecoveryCodes', ['codes' => $codes]);
    }

    public function challenge(): Response
    {
        return Inertia::render('Admin/TwoFactorChallenge');
    }

    public function verify(Request $request, Totp $totp, AuditLogger $auditLogger): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:32']]);
        $user = $request->user();
        $recoveryCodes = $user->two_factor_enabled && $user->two_factor_secret ? ($user->two_factor_recovery_codes ?? []) : [];
        $normalizedCode = Str::upper(trim($data['code']));
        $recoveryCodeIndex = null;
        foreach ($recoveryCodes as $index => $recoveryCodeHash) {
            if (Hash::check($normalizedCode, $recoveryCodeHash)) {
                $recoveryCodeIndex = $index;
                break;
            }
        }
        $validAuthenticatorCode = preg_match('/^\d{6}$/', $normalizedCode) === 1
            && $user->two_factor_enabled
            && $user->two_factor_secret
            && $totp->verify($user->two_factor_secret, $normalizedCode);

        if (! $validAuthenticatorCode && $recoveryCodeIndex === null) {
            $auditLogger->record('admin.mfa_failed', $user);

            return back()->withErrors(['code' => 'The verification code is invalid.']);
        }

        if ($recoveryCodeIndex !== null) {
            unset($recoveryCodes[$recoveryCodeIndex]);
            $user->forceFill(['two_factor_recovery_codes' => array_values($recoveryCodes)])->save();
            $auditLogger->record('admin.mfa_recovery_code_used', $user, metadata: ['remaining_codes' => count($recoveryCodes)]);
        }

        $request->session()->put('admin_mfa_verified_at', now()->timestamp);
        $request->session()->regenerate();
        $auditLogger->record('admin.mfa_succeeded', $user);

        return redirect()->intended(route('admin.dashboard'));
    }
}
