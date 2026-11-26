import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AdminLayout from '../../../components/AdminLayout';

type Props = {
    profile: { name: string; email: string; role: string };
    mfaEnabled: boolean;
    recoveryCodesRemaining: number;
    sessions: { databaseManaged: boolean; otherCount: number | null };
};

export default function Index({ profile, mfaEnabled, recoveryCodesRemaining, sessions }: Props) {
    const details = useForm({ name: profile.name, email: profile.email });
    const password = useForm({ current_password: '', password: '', password_confirmation: '' });
    const sessionForm = useForm({ current_password: '' });

    return <>
        <Head title="Account security · Admin"/>
        <AdminLayout title="Account security">
            <p className="admin-muted">Manage your own profile, password, MFA status, and active database sessions.</p>
            <div className="admin-profile-settings-grid">
                <section className="admin-panel admin-content-panel"><h2>Profile</h2><p className="admin-muted">Role: {profile.role}</p><form onSubmit={event => { event.preventDefault(); details.put('/admin/profile'); }}>
                    <label>Name<input required maxLength={120} value={details.data.name} onChange={event => details.setData('name', event.target.value)}/></label>
                    <label>Email<input required type="email" maxLength={255} value={details.data.email} onChange={event => details.setData('email', event.target.value)}/></label>
                    {Object.values(details.errors).map((error, index) => <div className="admin-error" key={index}>{error}</div>)}
                    <button className="admin-primary-button" disabled={details.processing}>Save profile</button>
                </form></section>
                <section className="admin-panel admin-content-panel"><h2>Change password</h2><p className="admin-muted">Use at least 14 characters with mixed case and numbers. Other database-backed sessions are revoked after the change.</p><form onSubmit={event => { event.preventDefault(); password.put('/admin/profile/password', { onSuccess: () => password.reset() }); }}>
                    <label>Current password<input required type="password" autoComplete="current-password" value={password.data.current_password} onChange={event => password.setData('current_password', event.target.value)}/></label>
                    <label>New password<input required type="password" autoComplete="new-password" value={password.data.password} onChange={event => password.setData('password', event.target.value)}/></label>
                    <label>Confirm new password<input required type="password" autoComplete="new-password" value={password.data.password_confirmation} onChange={event => password.setData('password_confirmation', event.target.value)}/></label>
                    {Object.values(password.errors).map((error, index) => <div className="admin-error" key={index}>{error}</div>)}
                    <button className="admin-primary-button" disabled={password.processing}>Update password</button>
                </form></section>
                <section className="admin-panel admin-content-panel"><h2>Two-factor authentication</h2><p className="admin-muted">Status: <b>{mfaEnabled ? 'Enabled' : 'Required for Admin and Super Admin roles'}</b>. Recovery codes remaining: {recoveryCodesRemaining}.</p>{['Admin', 'Super Admin'].includes(profile.role) && <Link className="admin-primary-button admin-inline-button" href="/admin/security/two-factor/setup">Manage authenticator and refresh recovery codes</Link>}</section>
                <section className="admin-panel admin-content-panel"><h2>Active sessions</h2>{sessions.databaseManaged ? <><p className="admin-muted">Other active sessions on this account: {sessions.otherCount ?? 0}.</p><form onSubmit={event => { event.preventDefault(); if (window.confirm('Sign out all other sessions for this account?')) { sessionForm.delete('/admin/profile/sessions', { onSuccess: () => sessionForm.reset() }); } }}>
                    <label>Confirm current password<input required type="password" autoComplete="current-password" value={sessionForm.data.current_password} onChange={event => sessionForm.setData('current_password', event.target.value)}/></label>
                    {Object.values(sessionForm.errors).map((error, index) => <div className="admin-error" key={index}>{error}</div>)}
                    <button className="admin-secondary-button" disabled={sessionForm.processing}>Sign out other sessions</button>
                </form></> : <p className="admin-muted">Central session management is unavailable with the current session driver. Configure Laravel's database session driver to revoke sessions from this screen.</p>}</section>
            </div>
        </AdminLayout>
    </>;
}
