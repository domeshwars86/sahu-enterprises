import React from 'react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';

type Props = { flash: { status?: string } };
export default function ForgotPassword() {
    const { flash } = usePage<Props>().props;
    const form = useForm({ email: '' });
    return <><Head title="Reset password · Admin"/><main className="admin-auth-simple"><form className="admin-auth-simple-card" onSubmit={event => { event.preventDefault(); form.post('/admin/forgot-password'); }}><Link className="admin-brand" href="/admin/login"><span className="admin-brand-icon">S</span><span>SAHU ENTERPRISES<small>LOAN OPERATIONS</small></span></Link><span className="admin-login-eyebrow">ACCOUNT RECOVERY</span><h1>Reset your password</h1><p>Enter your staff email. If the account is eligible, you’ll receive a reset link.</p>{flash.status && <div className="admin-success">{flash.status}</div>}{form.errors.email && <div className="admin-error">{form.errors.email}</div>}<label>Email address<input type="email" autoComplete="username" required value={form.data.email} onChange={event => form.setData('email', event.target.value)}/></label><button className="admin-primary-button" disabled={form.processing}>Send reset link <span>→</span></button><Link className="admin-auth-back" href="/admin/login">← Return to sign in</Link></form></main></>;
}
