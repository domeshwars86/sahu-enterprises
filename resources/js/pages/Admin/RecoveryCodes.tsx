import React from 'react';
import { Head, Link } from '@inertiajs/react';

export default function RecoveryCodes({ codes }: { codes: string[] }) {
    return <><Head title="Save recovery codes · Sahu Admin"/><main className="admin-auth-simple"><section className="admin-auth-simple-card"><span className="admin-login-eyebrow">ACCOUNT RECOVERY</span><h1>Save your recovery codes</h1><p>Store these codes somewhere private and secure. Each code works once if you lose access to your authenticator. They will not be shown again.</p><ol className="admin-recovery-codes">{codes.map(code => <li key={code}><code>{code}</code></li>)}</ol><Link className="admin-primary-button admin-inline-button" href="/admin">Continue to dashboard <span>→</span></Link></section></main></>;
}
