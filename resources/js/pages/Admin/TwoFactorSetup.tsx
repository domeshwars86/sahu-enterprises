import React from 'react';
import { Head, useForm } from '@inertiajs/react';

type Props = { secret: string; otpauthUri: string };
export default function TwoFactorSetup({ secret, otpauthUri }: Props) {
    const form = useForm({ code: '' });
    return <><Head title="Secure your account · Sahu Admin"/><main className="admin-auth-simple"><form className="admin-auth-simple-card" onSubmit={event => { event.preventDefault(); form.post('/admin/security/two-factor/setup'); }}><span className="admin-login-eyebrow">ACCOUNT SECURITY</span><h1>Set up two-factor authentication</h1><p>This privileged account needs an authenticator app. Add this account manually in your authenticator, then enter the 6-digit code to verify it.</p><label>Setup key<input readOnly value={secret}/></label><label>Authenticator setup URI<input readOnly value={otpauthUri}/></label>{form.errors.code && <div className="admin-error">{form.errors.code}</div>}<label>6-digit verification code<input inputMode="numeric" autoComplete="one-time-code" maxLength={6} value={form.data.code} onChange={event => form.setData('code', event.target.value.replace(/\D/g, '').slice(0, 6))}/></label><button className="admin-primary-button" disabled={form.processing}>Enable and continue <span>→</span></button><p className="admin-security-note">Keep your setup key private. It will not be shown again after setup.</p></form></main></>;
}
