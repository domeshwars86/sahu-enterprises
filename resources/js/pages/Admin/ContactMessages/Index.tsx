import React from 'react';
import { Head } from '@inertiajs/react';
import AdminLayout from '../../../components/AdminLayout';

type ContactMessage = { id: number; reference: string; subject: string; name: string; email: string | null; mobile: string; message: string | null; createdAt: string };
type Props = { messages: { data: ContactMessage[]; links: Array<{ url: string | null; label: string; active: boolean }> } };

export default function Index({ messages }: Props) {
    return <>
        <Head title="Contact messages · Admin"/>
        <AdminLayout title="Contact messages">
            <div className="admin-welcome-row"><p className="admin-muted">Public contact form requests. Contact data is encrypted at rest and shown only to staff with the sensitive-contact permission.</p></div>
            <section className="admin-panel admin-table-panel"><div className="admin-table-scroll"><table className="admin-table"><thead><tr><th>Reference / received</th><th>Subject</th><th>Customer</th><th>Phone / email</th><th>Message</th></tr></thead><tbody>{messages.data.map(message => <tr key={message.id}>
                <td><b>{message.reference}</b><small>{new Date(message.createdAt).toLocaleString('en-IN', { dateStyle: 'medium', timeStyle: 'short' })}</small></td>
                <td>{message.subject}</td>
                <td>{message.name}</td>
                <td>{message.mobile}<small>{message.email ?? 'No email provided'}</small></td>
                <td>{message.message ? <details><summary>Read message</summary><p className="admin-contact-message-body">{message.message}</p></details> : <span className="admin-muted">Restricted</span>}</td>
            </tr>)}</tbody></table>{messages.data.length === 0 && <div className="admin-empty"><b>No contact requests</b><span>New public contact messages will be listed here.</span></div>}</div>
                <div className="admin-pagination">{messages.links.map((link, index) => <button key={`${link.label}-${index}`} disabled={!link.url} className={link.active ? 'active' : ''} onClick={() => link.url && window.location.assign(link.url)} dangerouslySetInnerHTML={{ __html: link.label }}/>)}</div>
            </section>
        </AdminLayout>
    </>;
}
