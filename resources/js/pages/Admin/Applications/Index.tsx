import React from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AdminLayout from '../../../components/AdminLayout';

type History = { from: string | null; to: string; by: string; reason: string | null; at: string | null };
type Application = { id: number; reference: string; enquiryReference: string | null; enquiryId: number; product: string; productName: string | null; lenderName: string | null; lenderReference: string | null; amount: string | null; stage: string; submittedAt: string | null; verifiedDisbursementAmount: string | null; verifiedDisbursedAt: string | null; history: History[] };
type Enquiry = { id: number; reference_number: string; loan_category: string; requested_amount: string; loan_product_id: number | null };
type LenderMapping = { lending_partner_id: number; lender_name: string; loan_product_id: number; product_name: string };
type Props = { applications: { data: Application[]; links: Array<{ url: string | null; label: string; active: boolean }>; total: number }; eligibleEnquiries: Enquiry[]; stages: string[]; verifiedLenderMappings: LenderMapping[] };

export default function Index({ applications, eligibleEnquiries, stages, verifiedLenderMappings }: Props) {
    const form = useForm({ loan_enquiry_id: '', lending_partner_id: '', lender_name: '', lender_reference: '', staff_notes: '' });
    const selectedEnquiry = eligibleEnquiries.find(item => String(item.id) === form.data.loan_enquiry_id);
    const availableLenders = verifiedLenderMappings.filter(mapping => mapping.loan_product_id === selectedEnquiry?.loan_product_id);
    const moveStage = (application: Application, stage: string) => {
        const reason = ['submitted', 'approved', 'rejected', 'disbursed'].includes(stage) ? window.prompt(`Enter the recorded reason or evidence note for ${stage.replaceAll('_', ' ')}:`) : '';
        if (reason === null) return;
        router.patch(`/admin/applications/${application.id}/stage`, { stage, reason }, { preserveScroll: true });
    };
    return <><Head title="Loan applications · Admin"/><AdminLayout title="Loan applications">
        <div className="admin-welcome-row"><p className="admin-muted">Track lender applications created by staff from eligible enquiries. Enquiries remain distinct from applications.</p><span className="admin-total-pill">{applications.total} records</span></div>
        <section className="admin-panel admin-application-create"><h2>Record a genuine application</h2><p>An authorized staff member creates this record only after the enquiry reaches a submission workflow stage.</p>
            <form onSubmit={event => { event.preventDefault(); form.post('/admin/applications', { onSuccess: () => form.reset() }); }}>
                <label>Eligible enquiry<select required value={form.data.loan_enquiry_id} onChange={event => { form.setData('loan_enquiry_id', event.target.value); form.setData('lending_partner_id', ''); form.setData('lender_name', ''); }}><option value="">Choose enquiry</option>{eligibleEnquiries.map(item => <option key={item.id} value={item.id}>{item.reference_number} · {item.loan_category}</option>)}</select></label>
                <label>Verified product lender<select value={form.data.lending_partner_id} onChange={event => { const mapping = availableLenders.find(item => String(item.lending_partner_id) === event.target.value); form.setData('lending_partner_id', event.target.value); form.setData('lender_name', mapping?.lender_name ?? ''); }}><option value="">Select only if a verified mapping is recorded</option>{availableLenders.map((mapping, index) => <option key={`${mapping.lending_partner_id}-${index}`} value={mapping.lending_partner_id}>{mapping.lender_name} · {mapping.product_name}</option>)}</select><small>Only active, verified and available mappings appear here.</small></label>
                <label>Other lender name (staff-recorded, optional)<input maxLength={160} value={form.data.lender_name} onChange={event => form.setData('lender_name', event.target.value)}/></label>
                <label>Lender submission reference<input maxLength={160} value={form.data.lender_reference} onChange={event => form.setData('lender_reference', event.target.value)}/></label>
                <label>Internal note<input maxLength={4000} value={form.data.staff_notes} onChange={event => form.setData('staff_notes', event.target.value)}/></label>
                <button className="admin-primary-button" disabled={form.processing}>Create application record</button>
            </form>{Object.values(form.errors).map((error, index) => <div className="admin-error" key={index}>{error}</div>)}
        </section>
        <section className="admin-panel admin-application-list"><div className="admin-panel-heading"><div><h2>Application pipeline</h2><p>Application stages and verified disbursement reflect staff-recorded workflow and evidence.</p></div></div>
            <div className="admin-table-scroll"><table className="admin-table"><thead><tr><th>Application</th><th>Enquiry</th><th>Product</th><th>Amount</th><th>Lender record</th><th>Current stage</th><th>Update stage</th><th>Verified disbursement</th><th>History</th></tr></thead><tbody>{applications.data.map(application => <tr key={application.id}>
                <td><b>{application.reference}</b></td><td><Link className="admin-ref" href={`/admin/enquiries/${application.enquiryId}`}>{application.enquiryReference}</Link></td><td>{application.productName ?? application.product.replaceAll('_', ' ')}</td>
                <td>{application.amount ? new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR', maximumFractionDigits: 0 }).format(Number(application.amount)) : '—'}</td><td>{application.lenderName ?? 'Not recorded'}{application.lenderReference && <small>Ref: {application.lenderReference}</small>}</td>
                <td><span className={`admin-status status-${application.stage}`}>{application.stage.replaceAll('_', ' ')}</span></td><td><select className="admin-application-stage" aria-label={`Stage for ${application.reference}`} value={application.stage} onChange={event => moveStage(application, event.target.value)}>{stages.map(stage => <option key={stage} value={stage}>{stage.replaceAll('_', ' ')}</option>)}</select></td>
                <td>{application.verifiedDisbursementAmount ? <><b>₹{Number(application.verifiedDisbursementAmount).toLocaleString('en-IN')}</b><small>{application.verifiedDisbursedAt ? new Date(application.verifiedDisbursedAt).toLocaleDateString('en-IN') : ''}</small></> : application.stage === 'disbursed' ? <DisbursementEditor application={application}/> : 'Not recorded'}</td>
                <td><details className="admin-application-history"><summary>{application.history.length} changes</summary>{application.history.map((item, index) => <div key={`${item.at}-${index}`}><b>{item.from ?? 'Created'} → {item.to}</b><small>{item.by} · {item.at ? new Date(item.at).toLocaleString('en-IN') : ''}</small>{item.reason && <p>{item.reason}</p>}</div>)}</details></td>
            </tr>)}</tbody></table>{applications.data.length === 0 && <div className="admin-empty"><b>No application records yet</b><span>Only eligible enquiries appear in the application form.</span></div>}</div>
            <div className="admin-pagination">{applications.links.map((link, index) => <button key={`${link.label}-${index}`} disabled={!link.url} className={link.active ? 'active' : ''} onClick={() => link.url && router.get(link.url, {}, { preserveScroll: true })} dangerouslySetInnerHTML={{ __html: link.label }}/>)}</div>
        </section><p className="admin-filter-note">No lender API submission occurs. Actual disbursement totals are included in reports only after staff records an amount, date and evidence note.</p>
    </AdminLayout></>;
}

function DisbursementEditor({ application }: { application: Application }) {
    const form = useForm({ verified_disbursement_amount: '', verified_disbursed_at: '', evidence_note: '' });
    return <details className="admin-disbursement-editor"><summary>Record evidence</summary><form onSubmit={event => { event.preventDefault(); form.patch(`/admin/applications/${application.id}/disbursement`, { preserveScroll: true }); }}>
        <label>Actual amount<input required type="number" min="0.01" step="0.01" value={form.data.verified_disbursement_amount} onChange={event => form.setData('verified_disbursement_amount', event.target.value)}/></label>
        <label>Disbursement date<input required type="date" value={form.data.verified_disbursed_at} onChange={event => form.setData('verified_disbursed_at', event.target.value)}/></label>
        <label>Evidence note<textarea required maxLength={1000} rows={2} value={form.data.evidence_note} onChange={event => form.setData('evidence_note', event.target.value)}/></label>
        <button className="admin-secondary-button" disabled={form.processing}>Save actual disbursement</button>
        {Object.values(form.errors).map((error, index) => <small className="admin-error" key={index}>{error}</small>)}
    </form></details>;
}
