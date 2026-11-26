import React from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AdminLayout from '../../../components/AdminLayout';

type Role = { id: number; name: string; slug: string; permissions: string[]; isDefault: boolean; usersCount: number };
type Props = { roles: Role[]; permissionOptions: string[] };

export default function Index({ roles, permissionOptions }: Props) {
    const create = useForm({ name: '', slug: '', permissions: [] as string[] });
    const label = (permission: string) => permission.replaceAll('.', ' · ').replaceAll('_', ' ');
    const toggle = (role: Role, permission: string, checked: boolean) => {
        const permissions = checked ? [...role.permissions, permission] : role.permissions.filter(item => item !== permission);
        router.patch(`/admin/roles/${role.id}`, { name: role.name, permissions }, { preserveScroll: true });
    };
    const toggleNew = (permission: string, checked: boolean) => create.setData('permissions', checked ? [...create.data.permissions, permission] : create.data.permissions.filter(item => item !== permission));

    return <>
        <Head title="Roles & permissions · Admin"/>
        <AdminLayout title="Roles & permissions">
            <div className="admin-welcome-row"><p className="admin-muted">Only the Super Admin can change access rules. Review each permission before assigning a role to staff.</p></div>
            <section className="admin-panel admin-role-create"><h2>Create a custom role</h2><form onSubmit={event => { event.preventDefault(); create.post('/admin/roles', { onSuccess: () => create.reset() }); }}>
                <label>Role name<input required maxLength={80} value={create.data.name} onChange={event => create.setData('name', event.target.value)}/></label>
                <label>Role key<input required maxLength={80} pattern="[A-Za-z0-9_-]+" value={create.data.slug} onChange={event => create.setData('slug', event.target.value)}/></label>
                <div className="admin-role-permissions">{permissionOptions.map(permission => <label key={permission}><input type="checkbox" checked={create.data.permissions.includes(permission)} onChange={event => toggleNew(permission, event.target.checked)}/><span>{label(permission)}</span></label>)}</div>
                {Object.values(create.errors).map((error, index) => <div className="admin-error" key={index}>{error}</div>)}
                <button className="admin-primary-button" disabled={create.processing}>Create role</button>
            </form></section>
            <div className="admin-role-list">{roles.map(role => <section className="admin-panel admin-role-card" key={role.id}>
                <div className="admin-panel-heading"><div><h2>{role.name}</h2><p><code>{role.slug}</code> · {role.usersCount} staff account{role.usersCount === 1 ? '' : 's'} · {role.isDefault ? 'Built-in role' : 'Custom role'}</p></div>{!role.isDefault && role.usersCount === 0 && <button className="admin-secondary-button" onClick={() => { if (window.confirm(`Delete the unused ${role.name} role?`)) router.delete(`/admin/roles/${role.id}`); }}>Delete</button>}</div>
                {role.slug === 'super-admin' ? <p className="admin-muted">The Super Admin wildcard role is protected.</p> : <div className="admin-role-permissions">{permissionOptions.map(permission => <label key={permission}><input type="checkbox" checked={role.permissions.includes('*') || role.permissions.includes(permission)} disabled={role.permissions.includes('*')} onChange={event => toggle(role, permission, event.target.checked)}/><span>{label(permission)}</span></label>)}</div>}
            </section>)}</div>
        </AdminLayout>
    </>;
}
