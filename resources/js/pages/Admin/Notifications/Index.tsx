import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '../../../components/AdminLayout';

type Notification = { id: string; type: string; data: { title?: string; message?: string; url?: string }; readAt: string | null; createdAt: string | null };
type Props = { unreadCount: number; notifications: { data: Notification[]; links: Array<{ url: string | null; label: string; active: boolean }> } };
export default function Index({ unreadCount, notifications }: Props) {
    return <>
        <Head title="Notifications · Admin"/>
        <AdminLayout title="Notifications">
            <div className="admin-welcome-row"><p className="admin-muted">Operational alerts and assigned-work reminders.</p><button className="admin-secondary-button" disabled={!unreadCount} onClick={() => router.patch('/admin/notifications/read-all')}>Mark all read</button></div>
            <section className="admin-panel admin-notification-list">
                {notifications.data.map(item => <article key={item.id} className={item.readAt ? '' : 'unread'}>
                    <span className="admin-notification-dot" aria-hidden="true"/>
                    <div><b>{item.data.title ?? item.type.replace('Notification', '')}</b><p>{item.data.message ?? 'New operational update.'}</p><small>{item.createdAt ? new Date(item.createdAt).toLocaleString('en-IN', { dateStyle: 'medium', timeStyle: 'short' }) : ''}</small></div>
                    <div className="admin-notification-actions">{item.data.url && <Link href={item.data.url}>Open</Link>}{!item.readAt && <button className="admin-secondary-button" onClick={() => router.patch(`/admin/notifications/${item.id}/read`)}>Mark read</button>}</div>
                </article>)}
                {notifications.data.length === 0 && <div className="admin-empty"><b>You’re all caught up</b><span>New lead alerts and due work reminders will appear here.</span></div>}
            </section>
        </AdminLayout>
    </>;
}
