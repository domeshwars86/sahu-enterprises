import React from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AdminLayout from '../../../components/AdminLayout';

type Task = { id: number; title: string; dueAt: string | null; priority: string; status: string; reference: string | null; enquiryId: number | null; assignee: string | null; overdue: boolean };
type Props = { tasks: { data: Task[]; links: Array<{ url: string | null; label: string; active: boolean }>; total: number }; status: string; canManage: boolean; enquiries: Array<{ id: number; reference_number: string }>; staff: Array<{ id: number; name: string }> };

export default function Index({ tasks, status, canManage, enquiries, staff }: Props) {
    const create = useForm({ loan_enquiry_id: '', title: '', description: '', due_at: '', priority: 'normal', assigned_user_id: '' });
    const setStatus = (next: string) => router.get('/admin/tasks', { status: next }, { preserveState: true, replace: true });

    return <>
        <Head title="Tasks · Admin"/>
        <AdminLayout title="Tasks">
            <div className="admin-welcome-row"><p className="admin-muted">Work assigned to you and your team.</p><span className="admin-total-pill">{tasks.total} tasks</span></div>
            <div className="admin-panel admin-create-task">
                <h2>Create a task</h2>
                <form onSubmit={event => { event.preventDefault(); create.post('/admin/tasks', { onSuccess: () => create.reset() }); }}>
                    <label>Related enquiry<select required value={create.data.loan_enquiry_id} onChange={event => create.setData('loan_enquiry_id', event.target.value)}><option value="">Choose enquiry</option>{enquiries.map(item => <option key={item.id} value={item.id}>{item.reference_number}</option>)}</select></label>
                    <label>Task title<input required maxLength={160} value={create.data.title} onChange={event => create.setData('title', event.target.value)}/></label>
                    <label>Due date<input type="datetime-local" value={create.data.due_at} onChange={event => create.setData('due_at', event.target.value)}/></label>
                    <label>Priority<select value={create.data.priority} onChange={event => create.setData('priority', event.target.value)}><option value="low">Low</option><option value="normal">Normal</option><option value="high">High</option></select></label>
                    {canManage && <label>Assign to<select value={create.data.assigned_user_id} onChange={event => create.setData('assigned_user_id', event.target.value)}><option value="">Me</option>{staff.map(person => <option key={person.id} value={person.id}>{person.name}</option>)}</select></label>}
                    <button className="admin-primary-button">Create task</button>
                </form>
            </div>
            <div className="admin-followup-tabs">{['open', 'pending', 'in_progress', 'completed', 'cancelled', 'all'].map(tab => <button key={tab} className={status === tab ? 'active' : ''} onClick={() => setStatus(tab)}>{tab.replaceAll('_', ' ').replace(/^./, char => char.toUpperCase())}</button>)}</div>
            <section className="admin-panel admin-followups-table"><div className="admin-table-scroll"><table className="admin-table"><thead><tr><th>Due</th><th>Task</th><th>Enquiry</th><th>Assigned to</th><th>Priority</th><th>Status</th><th>Update</th></tr></thead><tbody>{tasks.data.map(task => <tr key={task.id}><td className={task.overdue ? 'admin-overdue' : ''}>{task.dueAt ? new Date(task.dueAt).toLocaleString('en-IN', { dateStyle: 'medium', timeStyle: 'short' }) : 'No due date'}{task.overdue && <small>Overdue</small>}</td><td><b>{task.title}</b></td><td>{task.reference && task.enquiryId ? <Link className="admin-ref" href={`/admin/enquiries/${task.enquiryId}`}>{task.reference}</Link> : '—'}</td><td>{task.assignee ?? 'Unassigned'}</td><td><span className="admin-priority">{task.priority}</span></td><td><span className={`admin-status status-${task.status}`}>{task.status.replaceAll('_', ' ')}</span></td><td>{!['completed', 'cancelled'].includes(task.status) && <select className="admin-task-select" aria-label={`Update ${task.title}`} value={task.status} onChange={event => router.patch(`/admin/tasks/${task.id}`, { status: event.target.value }, { preserveScroll: true })}><option value="pending">Pending</option><option value="in_progress">In progress</option><option value="completed">Completed</option>{canManage && <option value="cancelled">Cancelled</option>}</select>}</td></tr>)}</tbody></table>{tasks.data.length === 0 && <div className="admin-empty"><b>No tasks in this view</b><span>Create a task from this page or an enquiry record.</span></div>}</div><div className="admin-pagination">{tasks.links.map((link, index) => <button key={`${link.label}-${index}`} disabled={!link.url} className={link.active ? 'active' : ''} onClick={() => link.url && router.get(link.url, {}, { preserveState: true, preserveScroll: true })} dangerouslySetInnerHTML={{ __html: link.label }}/>)}</div></section>
        </AdminLayout>
    </>;
}
