import React from 'react';
import type { PropsWithChildren } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import '../../css/admin.css';

type Shared = { auth: { user: null | { name: string; email: string; role: string; permissions: string[] } }; notificationCount?: number };

const navigation = [
    { href: '/admin', label: 'Overview', permission: 'dashboard.view', icon: '◫' },
    { href: '/admin/enquiries', label: 'Loan enquiries', permission: 'enquiries.view', icon: '▤' },
    { href: '/admin/contact-messages', label: 'Contact messages', permission: 'contact_messages.view', icon: '✉' },
    { href: '/admin/follow-ups', label: 'Follow-ups', permission: 'followups.manage', icon: '◷' },
    { href: '/admin/tasks', label: 'Tasks', permission: 'tasks.manage', icon: '☷' },
    { href: '/admin/applications', label: 'Loan applications', permission: 'applications.manage', icon: '▣' },
    { href: '/admin/customers', label: 'Customers', permission: 'customers.view', icon: '♧' },
    { href: '/admin/news', label: 'News management', permission: 'news.manage', icon: '▧' },
    { href: '/admin/content', label: 'Educational articles', permission: 'content.manage', icon: '▤' },
    { href: '/admin/content', label: 'Videos', permission: 'videos.manage', icon: '▷' },
    { href: '/admin/notifications', label: 'Notifications', permission: 'notifications.view', icon: '♢' },
    { href: '/admin/settings', label: 'Business settings', permission: 'settings.manage', icon: '⚙' },
    { href: '/admin/reports', label: 'Reports', permission: 'reports.view', icon: '▥' },
    { href: '/admin/audit-logs', label: 'Audit log', permission: 'audit.view', icon: '◉' },
    { href: '/admin/products', label: 'Loan products', permission: 'products.manage', icon: '◈' },
    { href: '/admin/loan-categories', label: 'Loan categories', permission: 'products.manage', icon: '▧' },
    { href: '/admin/lending-partners', label: 'Lending partners', permission: 'partners.manage', icon: '⌑' },
    { href: '/admin/users', label: 'Team management', permission: 'users.manage', icon: '♙' },
    { href: '/admin/roles', label: 'Roles & permissions', permission: 'roles.manage', icon: '⚿' },
];

export default function AdminLayout({ children, title }: PropsWithChildren<{ title: string }>) {
    const { auth, notificationCount = 0 } = usePage<Shared>().props;
    const user = auth.user;
    const permissions = user?.permissions ?? [];
    const allowed = (permission: string) => permissions.includes('*') || permissions.includes(permission) || (permission === 'enquiries.view' && permissions.includes('enquiries.view_assigned')) || (permission === 'followups.manage' && permissions.includes('followups.assigned')) || (permission === 'tasks.manage' && permissions.includes('tasks.assigned')) || (permission === 'applications.manage' && permissions.includes('applications.assigned'));

    return <><Head><meta name="robots" content="noindex,nofollow"/></Head><div className="admin-shell">
        <aside className="admin-sidebar">
            <Link href="/admin" className="admin-brand"><span className="admin-brand-icon">S</span><span>SAHU ENTERPRISES<small>LOAN OPERATIONS</small></span></Link>
            <div className="admin-nav-label">WORKSPACE</div>
            <nav className="admin-nav">{navigation.filter(item => allowed(item.permission)).map(item => <Link key={item.href} href={item.href} className="admin-nav-item"><span>{item.icon}</span>{item.label}</Link>)}</nav>
            <div className="admin-sidebar-foot"><span className="admin-live-dot"/> Secure workspace <small>Private staff access</small></div>
        </aside>
        <main className="admin-main">
            <header className="admin-header"><div><div className="admin-crumb">OPERATIONS / SAHU ENTERPRISES</div><h1>{title}</h1></div><div className="admin-profile"><Link className="admin-alert-link" href="/admin/notifications" aria-label={`${notificationCount} unread notifications`}>Alerts{notificationCount > 0 && <span>{notificationCount > 99 ? '99+' : notificationCount}</span>}</Link><Link className="admin-account-link" href="/admin/profile"><div className="admin-avatar">{user?.name?.slice(0, 1).toUpperCase() ?? 'S'}</div><div><b>{user?.name}</b><small>{user?.role}</small></div></Link><button className="admin-logout" onClick={() => router.post('/admin/logout')}>Sign out</button></div></header>
            <section className="admin-content">{children}</section>
        </main>
    </div></>;
}
