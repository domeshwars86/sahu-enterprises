import React from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AdminLayout from '../../../components/AdminLayout';

type StaffMember = { id: number; name: string; email: string; roleId: number; role: string; roleSlug: string; teamManagerId: number | null; active: boolean; lastLoginAt: string | null };
type Role = { id: number; name: string; slug: string };
type Manager = { id: number; name: string };
type Props = { staff: { data: StaffMember[]; links: Array<{ url: string | null; label: string; active: boolean }>; total: number }; roles: Role[]; teamManagers: Manager[] };

export default function Index({ staff, roles, teamManagers }: Props) {
    const create = useForm({ name: '', email: '', password: '', password_confirmation: '', admin_role_id: '', team_manager_id: '' });
    const submit = (event: React.FormEvent) => { event.preventDefault(); create.post('/admin/users', { onSuccess: () => create.reset() }); };
    const update = (member: StaffMember, field: 'admin_role_id' | 'is_active' | 'team_manager_id', value: string | boolean) => router.patch(`/admin/users/${member.id}`, {
        admin_role_id: field === 'admin_role_id' ? value : member.roleId,
        is_active: field === 'is_active' ? value : member.active,
        team_manager_id: field === 'team_manager_id' ? (value || null) : member.teamManagerId,
    }, { preserveScroll: true });

    return <>
        <Head title="Team management · Admin"/>
        <AdminLayout title="Team management">
            <div className="admin-welcome-row"><p className="admin-muted">Provision staff accounts, assign roles, and place sales/support staff on a manager’s team.</p><span className="admin-total-pill">{staff.total} accounts</span></div>
            <div className="admin-staff-grid">
                <section className="admin-panel admin-staff-create"><h2>Add staff member</h2><p>Passwords must be at least 14 characters and include mixed case and numbers.</p><form onSubmit={submit}>
                    <label>Full name<input required maxLength={120} value={create.data.name} onChange={event => create.setData('name', event.target.value)}/></label>
                    <label>Email address<input required type="email" value={create.data.email} onChange={event => create.setData('email', event.target.value)}/></label>
                    <label>Role<select required value={create.data.admin_role_id} onChange={event => create.setData('admin_role_id', event.target.value)}><option value="">Choose role</option>{roles.map(role => <option key={role.id} value={role.id}>{role.name}</option>)}</select></label>
                    <label>Team manager<select value={create.data.team_manager_id} onChange={event => create.setData('team_manager_id', event.target.value)}><option value="">No team manager</option>{teamManagers.map(manager => <option key={manager.id} value={manager.id}>{manager.name}</option>)}</select></label>
                    <label>Temporary password<input required type="password" autoComplete="new-password" value={create.data.password} onChange={event => create.setData('password', event.target.value)}/></label>
                    <label>Confirm password<input required type="password" autoComplete="new-password" value={create.data.password_confirmation} onChange={event => create.setData('password_confirmation', event.target.value)}/></label>
                    {Object.values(create.errors).map((error, index) => <div className="admin-error" key={index}>{error}</div>)}
                    <button className="admin-primary-button" disabled={create.processing}>Create staff account <span>→</span></button>
                </form></section>
                <section className="admin-panel admin-staff-list"><div className="admin-panel-heading"><div><h2>Staff accounts</h2><p>Account status and team access</p></div></div><div className="admin-table-scroll"><table className="admin-table"><thead><tr><th>Name</th><th>Role</th><th>Team manager</th><th>Last sign in</th><th>Status</th><th>Account actions</th></tr></thead><tbody>{staff.data.map(member => <tr key={member.id}>
                    <td><b>{member.name}</b><small>{member.email}</small></td>
                    <td><select aria-label={`Role for ${member.name}`} value={member.roleId} onChange={event => update(member, 'admin_role_id', event.target.value)}>{roles.map(role => <option key={role.id} value={role.id}>{role.name}</option>)}</select></td>
                    <td><select aria-label={`Team manager for ${member.name}`} value={member.teamManagerId ?? ''} onChange={event => update(member, 'team_manager_id', event.target.value)}><option value="">None</option>{teamManagers.map(manager => <option key={manager.id} value={manager.id}>{manager.name}</option>)}</select></td>
                    <td>{member.lastLoginAt ? new Date(member.lastLoginAt).toLocaleDateString('en-IN') : 'Never'}</td>
                    <td><span className={`admin-status ${member.active ? 'status-approved' : 'status-closed'}`}>{member.active ? 'Active' : 'Inactive'}</span></td>
                    <td><div className="admin-row-actions"><button onClick={() => update(member, 'is_active', !member.active)}>{member.active ? 'Deactivate' : 'Activate'}</button><button onClick={() => router.post(`/admin/users/${member.id}/password-reset`)}>Send reset link</button></div></td>
                </tr>)}</tbody></table></div><div className="admin-pagination">{staff.links.map((link, index) => <button key={`${link.label}-${index}`} disabled={!link.url} className={link.active ? 'active' : ''} onClick={() => link.url && router.get(link.url, {}, { preserveScroll: true })} dangerouslySetInnerHTML={{ __html: link.label }}/>)}</div></section>
            </div>
        </AdminLayout>
    </>;
}
