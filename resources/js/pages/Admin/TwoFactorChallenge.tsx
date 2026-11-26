import React from 'react';
import { Head, useForm } from '@inertiajs/react';

export default function TwoFactorChallenge() {
    const form = useForm({ code: '' });
    return <><Head title="Verify sign in · Sahu Admin"/><main className="admin-auth-simple"><form className="admin-auth-simple-card" onSubmit={event => { event.preventDefault(); form.post('/admin/security/two-factor/challenge'); }}><span className="admin-login-eyebrow">SECURE SIGN IN</span><h1>Verify it’s you</h1><p>Enter a 6-digit authenticator code or one unused recovery code.</p>{form.errors.code && <div className="admin-error">{form.errors.code}</div>}<label>Authentication or recovery code<input autoComplete="one-time-code" maxLength={32} autoFocus value={form.data.code} onChange={event => form.setData('code', event.target.value.trim().slice(0, 32))}/></label><button className="admin-primary-button" disabled={form.processing}>Verify and sign in <span>→</span></button></form></main></>;
}
