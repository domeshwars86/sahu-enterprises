import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AdminLayout from '../../../components/AdminLayout';

type Note = { body: string; author: string | null; createdAt: string };
type Enquiry = {
    id: number; reference: string; customerName: string; mobile: string | null; email: string | null; city: string | null;
    category: string; amount: string | null; tenure: number | null; status: string; assignedTo: string | null; createdAt: string;
    notes: Note[]; statusHistory: Array<{ oldStatus: string | null; newStatus: string; reason: string | null; changedBy: string | null; createdAt: string }>;
};
type Customer = {
    id: number; communicationPreference: string | null; privacyRequestAt: string | null; enquiries: Enquiry[];
    followUps: Array<{ purpose: string; status: string; scheduledAt: string; assignedTo: string | null }>; canManage: boolean;
};
export default function Show({ customer }: { customer: Customer }) {
    const preference = useForm({ communication_preference: customer.communicationPreference ?? 'unspecified' });
    const privacy = useForm({ request_type: 'access' });
    const primary = customer.enquiries[0];

    return <><Head title={`Customer #${customer.id} · Admin`}/><AdminLayout title={`Customer #${customer.id}`}>
        <div className="admin-welcome-row"><Link className="admin-secondary-button" href="/admin/customers">← Customers</Link><p className="admin-muted">Linked records are shown only when you have access to their enquiries.</p></div>
        <section className="admin-panel admin-customer-list">
            <h2>{primary?.customerName ?? `Customer ${customer.id}`}</h2>
            <div className="admin-summary-grid"><div><small>Phone</small><b>{primary?.mobile ?? 'Not available'}</b></div><div><small>Email</small><b>{primary?.email ?? 'Not available'}</b></div><div><small>City</small><b>{primary?.city ?? 'Not available'}</b></div><div><small>Assigned staff</small><b>{primary?.assignedTo ?? 'Unassigned'}</b></div></div>
        </section>
        {customer.canManage && <section className="admin-panel admin-content-panel">
            <h2>Communication and privacy</h2>
            <form onSubmit={event => { event.preventDefault(); preference.patch(`/admin/customers/${customer.id}/communication-preference`); }}>
                <label>Preferred contact<select value={preference.data.communication_preference} onChange={event => preference.setData('communication_preference', event.target.value)}><option value="unspecified">Not specified</option><option value="phone">Phone</option><option value="email">Email</option><option value="whatsapp">WhatsApp</option><option value="no_marketing">No marketing contact</option></select></label>
                <button className="admin-primary-button" disabled={preference.processing}>Save preference</button>
            </form>
            <form className="admin-content-edit" onSubmit={event => { event.preventDefault(); privacy.post(`/admin/customers/${customer.id}/privacy-requests`, { onSuccess: () => privacy.reset() }); }}>
                <h3>Record a privacy request</h3>
                <label>Request type<select value={privacy.data.request_type} onChange={event => privacy.setData('request_type', event.target.value)}><option value="access">Access</option><option value="correction">Correction</option><option value="deletion">Deletion</option><option value="marketing_opt_out">Marketing opt-out</option></select></label>
                <button className="admin-primary-button" disabled={privacy.processing}>Record request</button>
                {customer.privacyRequestAt && <small>Most recent request recorded {new Date(customer.privacyRequestAt).toLocaleString('en-IN')}</small>}
            </form>
        </section>}
        <section className="admin-panel admin-customer-list"><h2>Enquiries and activity</h2>{customer.enquiries.map(enquiry => <article className="admin-content-list" key={enquiry.id}>
            <div><b><Link className="admin-ref" href={`/admin/enquiries/${enquiry.id}`}>{enquiry.reference}</Link> · {enquiry.category.replaceAll('_', ' ')}</b><small>{enquiry.status.replaceAll('_', ' ')} · ₹{enquiry.amount ?? '—'} · {enquiry.tenure ? `${enquiry.tenure} months` : 'Tenure not recorded'} · {new Date(enquiry.createdAt).toLocaleDateString('en-IN')}</small></div>
            <h3>Status history</h3>{enquiry.statusHistory.length === 0 ? <small>No status changes recorded.</small> : enquiry.statusHistory.map((entry, index) => <small key={`${entry.createdAt}-${index}`}>{entry.oldStatus ?? 'Created'} → {entry.newStatus}{entry.reason ? ` · ${entry.reason}` : ''} · {entry.changedBy ?? 'System'} · {new Date(entry.createdAt).toLocaleString('en-IN')}</small>)}
            <h3>Notes</h3>{enquiry.notes.length === 0 ? <small>No notes recorded.</small> : enquiry.notes.map((note, index) => <small key={`${note.createdAt}-${index}`}>{note.body} · {note.author ?? 'Staff'} · {new Date(note.createdAt).toLocaleString('en-IN')}</small>)}
        </article>)}</section>
        <section className="admin-panel admin-customer-list"><h2>Follow-up history</h2>{customer.followUps.length === 0 ? <p className="admin-muted">No follow-ups found for the records you can access.</p> : customer.followUps.map((item, index) => <article className="admin-content-list" key={`${item.scheduledAt}-${index}`}><b>{item.purpose}</b><small>{item.status} · {new Date(item.scheduledAt).toLocaleString('en-IN')} · {item.assignedTo ?? 'Unassigned'}</small></article>)}</section>
        <p className="admin-filter-note">Recording a request logs its category and timestamp; retention, deletion and response obligations follow the business’s approved privacy policy.</p>
    </AdminLayout></>;
}
