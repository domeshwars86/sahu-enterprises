import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AdminLayout from '../../../components/AdminLayout';

type Segment = { label: string; total: number };
type Filters = { from: string; to: string; category: string; status: string; application_stage: string; source: string; lender: string; assigned_user_id: string };
type Props = {
    summary: { enquiries: number; applications: number; verifiedDisbursements: number; verifiedDisbursementAmount: number; conversionRate: number; pendingFollowUps: number };
    byStatus: Segment[]; byCategory: Segment[]; bySource: Segment[]; daily: Array<{ day: string; total: number }>;
    applicationStages: Segment[]; followUpCompletion: Segment[]; lenderSubmissions: Segment[]; lenderSubmissionStages: Segment[]; disbursementByLender: Array<Segment & { amount: number }>; funnel: Segment[]; agentPerformance: Segment[]; weekly: Segment[]; monthly: Segment[];
    filters: Filters;
    options: { categories: string[]; sources: string[]; staff: Array<{ id: number; name: string }>; lenders: string[]; statuses: string[]; applicationStages: string[] };
    canExport: boolean;
};
function Bars({ title, rows }: { title: string; rows: Segment[] }) {
    const max = Math.max(1, ...rows.map(row => row.total));
    return <section className="admin-panel admin-report-card"><h2>{title}</h2>{rows.length ? <div className="admin-report-bars">{rows.map(row => <div key={row.label} className="admin-report-row"><span>{row.label.replaceAll('_', ' ')}</span><div><i style={{ width: `${Math.max(4, row.total / max * 100)}%` }}/></div><b>{row.total.toLocaleString('en-IN')}</b></div>)}</div> : <div className="admin-empty-small">No records in this period.</div>}</section>;
}
export default function Index({ summary, byStatus, byCategory, bySource, daily, weekly, monthly, applicationStages, followUpCompletion, lenderSubmissions, lenderSubmissionStages, disbursementByLender, funnel, agentPerformance, filters: initialFilters, options, canExport }: Props) {
    const [filters, setFilters] = useState(initialFilters);
    const apply = (event: React.FormEvent) => { event.preventDefault(); router.get('/admin/reports', filters, { preserveState: true, replace: true }); };
    const exportUrl = `/admin/reports/enquiries.csv?${new URLSearchParams(filters).toString()}`;
    const maxDaily = Math.max(1, ...daily.map(item => item.total));

    return <>
        <Head title="Reports · Admin"/>
        <AdminLayout title="Reports">
            <div className="admin-welcome-row"><p className="admin-muted">Counts reflect recorded requests, workflow history, staff activity, and lender names entered by staff.</p>{canExport && <a className="admin-primary-button admin-inline-button" href={exportUrl}>Download filtered enquiry CSV <span>↓</span></a>}</div>
            <form className="admin-filter-panel admin-report-filters" onSubmit={apply}>
                <label>From<input type="date" value={filters.from} onChange={event => setFilters({ ...filters, from: event.target.value })}/></label>
                <label>To<input type="date" value={filters.to} onChange={event => setFilters({ ...filters, to: event.target.value })}/></label>
                <label>Category<select value={filters.category} onChange={event => setFilters({ ...filters, category: event.target.value })}><option value="">All categories</option>{options.categories.map(category => <option key={category} value={category}>{category}</option>)}</select></label>
                <label>Lead status<select value={filters.status} onChange={event => setFilters({ ...filters, status: event.target.value })}><option value="">All lead statuses</option>{options.statuses.map(status => <option key={status} value={status}>{status.replaceAll('_', ' ')}</option>)}</select></label>
                <label>Application stage<select value={filters.application_stage} onChange={event => setFilters({ ...filters, application_stage: event.target.value })}><option value="">All application stages</option>{options.applicationStages.map(stage => <option key={stage} value={stage}>{stage.replaceAll('_', ' ')}</option>)}</select></label>
                <label>Source<select value={filters.source} onChange={event => setFilters({ ...filters, source: event.target.value })}><option value="">All sources</option>{options.sources.map(source => <option key={source} value={source}>{source}</option>)}</select></label>
                <label>Assigned staff<select value={filters.assigned_user_id} onChange={event => setFilters({ ...filters, assigned_user_id: event.target.value })}><option value="">All staff</option>{options.staff.map(member => <option key={member.id} value={member.id}>{member.name}</option>)}</select></label>
                <label>Lender<select value={filters.lender} onChange={event => setFilters({ ...filters, lender: event.target.value })}><option value="">All lenders</option>{options.lenders.map(lender => <option key={lender} value={lender}>{lender}</option>)}</select></label>
                <button className="admin-primary-button admin-filter-button">Apply filters</button>
            </form>
            <div className="admin-metric-grid admin-report-metrics"><div className="admin-metric-card"><div className="admin-metric-top"><span>Enquiries in range</span><i>▤</i></div><strong>{summary.enquiries.toLocaleString('en-IN')}</strong><small>Recorded requests</small></div><div className="admin-metric-card"><div className="admin-metric-top"><span>Application records</span><i>▣</i></div><strong>{summary.applications.toLocaleString('en-IN')}</strong><small>Staff-created applications</small></div></div>
            <div className="admin-metric-grid admin-report-metrics"><div className="admin-metric-card"><div className="admin-metric-top"><span>Verified disbursements</span><i>✓</i></div><strong>{summary.verifiedDisbursements.toLocaleString('en-IN')}</strong><small>Supported by recorded evidence</small></div><div className="admin-metric-card"><div className="admin-metric-top"><span>Verified disbursed amount</span><i>₹</i></div><strong>₹{summary.verifiedDisbursementAmount.toLocaleString('en-IN')}</strong><small>Actual amount recorded by staff</small></div><div className="admin-metric-card"><div className="admin-metric-top"><span>Enquiry to disbursement</span><i>↗</i></div><strong>{summary.conversionRate}%</strong><small>Based on verified disbursement records</small></div><div className="admin-metric-card"><div className="admin-metric-top"><span>Pending follow-ups</span><i>◷</i></div><strong>{summary.pendingFollowUps.toLocaleString('en-IN')}</strong><small>Within the selected date range</small></div></div>
            <div className="admin-report-grid">
                <Bars title="Daily enquiry report" rows={daily.map(item => ({ label: item.day, total: item.total }))}/>
                <Bars title="Weekly enquiry report" rows={weekly}/>
                <Bars title="Monthly enquiry report" rows={monthly}/>
                <Bars title="Lead status distribution" rows={byStatus}/>
                <Bars title="Loan category distribution" rows={byCategory}/>
                <Bars title="Lead source report" rows={bySource}/>
                <Bars title="Application status report" rows={applicationStages}/>
                <Bars title="Follow-up completion" rows={followUpCompletion}/>
                <Bars title="Lender submissions by recorded lender" rows={lenderSubmissions}/>
                <Bars title="Submitted application stage" rows={lenderSubmissionStages}/>
                <Bars title="Verified disbursements by lender" rows={disbursementByLender.map(item => ({ label: `${item.label} · ₹${item.amount.toLocaleString('en-IN')}`, total: item.total }))}/>
                <Bars title="Enquiries that reached each recorded stage" rows={funnel}/>
                {agentPerformance.length > 0 && <Bars title="Assigned enquiries by staff member" rows={agentPerformance}/>}
                <section className="admin-panel admin-report-card"><h2>Daily enquiry trend</h2>{daily.length ? <div className="admin-trend-chart">{daily.map(point => <div key={point.day} title={`${point.day}: ${point.total}`}><i style={{ height: `${Math.max(5, point.total / maxDaily * 100)}%` }}/><small>{new Date(point.day).toLocaleDateString('en-IN', { day: 'numeric', month: 'short' })}</small></div>)}</div> : <div className="admin-empty-small">No enquiries in this period.</div>}</section>
            </div>
            <p className="admin-filter-note">No revenue or commission values are estimated. Funnel counts use recorded status history. CSV export excludes customer names and contact details and records an audit event.</p>
        </AdminLayout>
    </>;
}
