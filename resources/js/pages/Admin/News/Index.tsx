import React from 'react';
import { Head, router } from '@inertiajs/react';
import AdminLayout from '../../../components/AdminLayout';

type Article = { id: number; title: string; slug: string; sourceName: string; sourceUrl: string; category: string; publishedAt: string | null; isPublished: boolean; isFeatured: boolean };
type Props = {
    articles: { data: Article[]; links: Array<{ url: string | null; label: string; active: boolean }>; total: number };
    categories: string[]; categoryFilter: string;
    sync: { enabled: boolean; status: string | null; lastAttemptAt: string | null; lastSuccessAt: string | null; lastCount: number };
};

export default function Index({ articles, categories, categoryFilter, sync }: Props) {
    const setCategory = (category: string) => router.get('/admin/news', { category }, { preserveState: true, replace: true });
    const toggle = (article: Article, isPublished: boolean, isFeatured: boolean) => router.patch(`/admin/news/${article.id}`, { is_published: isPublished, is_featured: isFeatured }, { preserveScroll: true });
    return <>
        <Head title="News management · Admin"/>
        <AdminLayout title="News management">
            <div className="admin-welcome-row"><p className="admin-muted">Manage source-attributed headlines from the configured PIB RSS feed.</p><span className="admin-total-pill">{articles.total} headlines</span></div>
            <section className="admin-panel admin-news-sync"><div><h2>News source status</h2><p>{sync.enabled ? 'PIB sync is enabled by both environment and business settings.' : 'PIB sync is disabled by configuration or business settings.'}</p><small>Last attempt: {sync.lastAttemptAt ? new Date(sync.lastAttemptAt).toLocaleString('en-IN') : 'Never'} · Last success: {sync.lastSuccessAt ? new Date(sync.lastSuccessAt).toLocaleString('en-IN') : 'Never'} · Last import: {sync.lastCount} records</small></div><div className="admin-news-sync-actions"><span className={`admin-status ${sync.status === 'failed' ? 'status-rejected' : sync.status === 'succeeded' ? 'status-approved' : 'status-on_hold'}`}>{sync.status ?? 'not run'}</span><button className="admin-primary-button" disabled={!sync.enabled} onClick={() => router.post('/admin/news/sync')}>Sync now</button></div></section>
            <div className="admin-filter-panel"><label className="admin-news-filter">Category<select value={categoryFilter} onChange={event => setCategory(event.target.value)}><option value="">All categories</option>{categories.map(category => <option key={category} value={category}>{category.replaceAll('_', ' ')}</option>)}</select></label><p className="admin-news-disclosure">The portal stores source-linked headlines, not copied article bodies.</p></div>
            <section className="admin-panel admin-news-list"><div className="admin-table-scroll"><table className="admin-table"><thead><tr><th>Headline / source</th><th>Category</th><th>Published</th><th>Website visibility</th><th>Featured</th></tr></thead><tbody>{articles.data.map(article => <tr key={article.id}><td className="admin-news-title"><b>{article.title}</b><small><a href={article.sourceUrl} target="_blank" rel="noreferrer">{article.sourceName} ↗</a></small></td><td>{article.category.replaceAll('_', ' ')}</td><td>{article.publishedAt ? new Date(article.publishedAt).toLocaleDateString('en-IN') : '—'}</td><td><label className="admin-toggle"><input type="checkbox" checked={article.isPublished} onChange={event => toggle(article, event.target.checked, article.isFeatured)}/><span>{article.isPublished ? 'Visible' : 'Hidden'}</span></label></td><td><label className="admin-toggle"><input type="checkbox" checked={article.isFeatured} onChange={event => toggle(article, article.isPublished, event.target.checked)}/><span>{article.isFeatured ? 'Featured' : 'Standard'}</span></label></td></tr>)}</tbody></table>{articles.data.length === 0 && <div className="admin-empty"><b>No sourced headlines</b><span>Run an approved sync to refresh records.</span></div>}</div><div className="admin-pagination">{articles.links.map((link, index) => <button key={`${link.label}-${index}`} disabled={!link.url} className={link.active ? 'active' : ''} onClick={() => link.url && router.get(link.url, {}, { preserveState: true, preserveScroll: true })} dangerouslySetInnerHTML={{ __html: link.label }}/>)}</div></section>
            <p className="admin-filter-note">Keep source attribution intact. This editor does not publish copied full-text articles or bypass feed permissions.</p>
        </AdminLayout>
    </>;
}
