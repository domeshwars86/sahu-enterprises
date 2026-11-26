import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AdminLayout from '../../components/AdminLayout';

type ChartItem = { label: string; total: number };
type Props = {
    metrics: Record<string, number>;
    charts: { enquiryTrends: ChartItem[]; categories: ChartItem[]; statuses: ChartItem[]; sources: ChartItem[]; applicationProgress: ChartItem[]; agentWorkload: ChartItem[] };
    recentEnquiries: Array<{ id: number; reference: string; customer: string; product: string; amount: string | null; status: string; assignee: string | null; createdAt: string }>;
    upcomingFollowUps: Array<{ id: number; purpose: string; scheduledAt: string; reference: string | null }>;
};
const money = (value: string | null) => value ? new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR', maximumFractionDigits: 0 }).format(Number(value)) : '—';
const date = (value: string) => new Date(value).toLocaleDateString('en-IN', { day: 'numeric', month: 'short' });

function ChartCard({ title, items, dateLabels = false }: { title: string; items: ChartItem[]; dateLabels?: boolean }) {
    const maximum = Math.max(1, ...items.map(item => item.total));

    return <section className="admin-panel admin-chart-card">
        <div className="admin-panel-heading"><div><h2>{title}</h2></div></div>
        {items.length ? <div className="admin-chart-rows">{items.map(item => <div className="admin-chart-row" key={item.label}>
            <span title={item.label}>{dateLabels ? new Date(`${item.label}T00:00:00`).toLocaleDateString('en-IN', { day: 'numeric', month: 'short' }) : item.label.replaceAll('_', ' ')}</span>
            <div className="admin-chart-track"><i style={{ width: `${Math.max(4, (item.total / maximum) * 100)}%` }}/></div>
            <b>{item.total.toLocaleString('en-IN')}</b>
        </div>)}</div> : <div className="admin-empty admin-empty-small">No recorded activity yet.</div>}
    </section>;
}

export default function Dashboard({ metrics, charts, recentEnquiries, upcomingFollowUps }: Props) {
    const cards = [['Total enquiries', metrics.totalEnquiries], ['New enquiries', metrics.newEnquiries], ['Pending follow-ups', metrics.pendingFollowUps], ['Active applications', metrics.activeApplications], ['Approved', metrics.approvedApplications], ['Disbursed', metrics.disbursedApplications], ['Overdue follow-ups', metrics.overdueFollowUps]] as const;

    return <>
        <Head title="Dashboard · Admin"/>
        <AdminLayout title="Operations overview">
            <div className="admin-welcome-row"><div><p className="admin-muted">A live view of recorded enquiry and application activity.</p></div><Link className="admin-primary-button admin-inline-button" href="/admin/enquiries">Review enquiries <span>→</span></Link></div>
            <div className="admin-metric-grid">{cards.map(([label, value], index) => <div key={label} className={`admin-metric-card ${index === 1 ? 'is-accent' : ''}`}><div className="admin-metric-top"><span>{label}</span><i>{['◫', '✳', '◷', '▧', '✓', '↗', '⚑'][index]}</i></div><strong>{value.toLocaleString('en-IN')}</strong><small>Current records</small></div>)}</div>
            <div className="admin-chart-grid">
                <ChartCard title="Enquiry trends · last 14 days" items={charts.enquiryTrends} dateLabels/>
                <ChartCard title="Loan category distribution" items={charts.categories}/>
                <ChartCard title="Lead status distribution" items={charts.statuses}/>
                <ChartCard title="Enquiry sources" items={charts.sources}/>
                <ChartCard title="Application progress" items={charts.applicationProgress}/>
                {charts.agentWorkload.length > 0 && <ChartCard title="Assigned lead workload" items={charts.agentWorkload}/>}
            </div>
            <div className="admin-dashboard-grid">
                <section className="admin-panel admin-table-panel"><div className="admin-panel-heading"><div><h2>Recent enquiries</h2><p>Latest customer requests received</p></div><Link href="/admin/enquiries">View all <span>→</span></Link></div>{recentEnquiries.length ? <div className="admin-table-scroll"><table className="admin-table"><thead><tr><th>Reference / customer</th><th>Product</th><th>Requested</th><th>Status</th><th>Owner</th><th>Date</th></tr></thead><tbody>{recentEnquiries.map(row => <tr key={row.id}><td><Link className="admin-ref" href={`/admin/enquiries/${row.id}`}>{row.reference}</Link><small>{row.customer}</small></td><td>{row.product}</td><td>{money(row.amount)}</td><td><span className={`admin-status status-${row.status}`}>{row.status.replaceAll('_', ' ')}</span></td><td>{row.assignee ?? 'Unassigned'}</td><td>{date(row.createdAt)}</td></tr>)}</tbody></table></div> : <div className="admin-empty"><b>No enquiries recorded yet</b><span>New public enquiries will appear here.</span></div>}</section>
                <section className="admin-panel admin-followup-panel"><div className="admin-panel-heading"><div><h2>Upcoming follow-ups</h2><p>Scheduled callbacks and customer actions</p></div><span className="admin-heading-icon">◷</span></div>{upcomingFollowUps.length ? <ul className="admin-followup-list">{upcomingFollowUps.map(item => <li key={item.id}><div className="admin-followup-date">{date(item.scheduledAt)}</div><div><b>{item.purpose}</b><small>{item.reference ?? 'Customer follow-up'}</small></div></li>)}</ul> : <div className="admin-empty admin-empty-small">No upcoming follow-ups.</div>}<Link className="admin-followup-link" href="/admin/follow-ups">Open follow-ups <span>→</span></Link></section>
            </div>
        </AdminLayout>
    </>;
}
