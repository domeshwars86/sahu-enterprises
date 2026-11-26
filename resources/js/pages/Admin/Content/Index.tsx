import React, { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AdminLayout from '../../../components/AdminLayout';

type Article = { id: number; title: string; slug: string; body: string; status: string; author: string | null; publishedAt: string | null };
type Video = { id: number; title: string; url: string; publisher: string | null; description: string | null; category: string | null; displayOrder: number; isVisible: boolean; isFeatured: boolean };
type Faq = { id: number; question: string; answer: string; displayOrder: number; isVisible: boolean };
type Props = { articles: { data: Article[]; links: Array<{ url: string | null; label: string; active: boolean }> } | Article[]; faqs: Faq[]; videos: Video[]; canManageArticles: boolean; canManageVideos: boolean };

export default function Index({ articles, faqs, videos, canManageArticles, canManageVideos }: Props) {
    const articleRows = Array.isArray(articles) ? articles : articles.data;
    const [editingArticleId, setEditingArticleId] = useState<number | null>(null);
    const article = useForm({ title: '', slug: '', body: '', status: 'draft' });
    const articleEdit = useForm({ title: '', body: '', status: 'draft' });
    const video = useForm({ title: '', url: '', publisher: '', description: '', category: '', display_order: 0, is_visible: false, is_featured: false });
    const [editingVideoId, setEditingVideoId] = useState<number | null>(null);
    const videoEdit = useForm({ title: '', url: '', publisher: '', description: '', category: '', display_order: 0, is_visible: false, is_featured: false });
    const faq = useForm({ question: '', answer: '', display_order: 0, is_visible: false });
    const [editingFaqId, setEditingFaqId] = useState<number | null>(null);
    const faqEdit = useForm({ question: '', answer: '', display_order: 0, is_visible: false });

    return <>
        <Head title="Content management · Admin"/>
        <AdminLayout title="Content management">
            <div className="admin-welcome-row"><p className="admin-muted">Publish educational articles and approved video links to the public learning pages.</p></div>
            <div className="admin-content-grid">
                {canManageArticles && <section className="admin-panel admin-content-panel">
                    <h2>Educational articles</h2>
                    <form onSubmit={event => { event.preventDefault(); article.post('/admin/content/articles', { onSuccess: () => article.reset() }); }}>
                        <label>Title<input required maxLength={180} value={article.data.title} onChange={event => article.setData('title', event.target.value)}/></label>
                        <label>URL slug<input maxLength={180} placeholder="Generated from the title if blank" value={article.data.slug} onChange={event => article.setData('slug', event.target.value)}/></label>
                        <label>Article text<textarea required rows={6} maxLength={50000} value={article.data.body} onChange={event => article.setData('body', event.target.value)}/></label>
                        <label>Visibility<select value={article.data.status} onChange={event => article.setData('status', event.target.value)}><option value="draft">Draft</option><option value="published">Publish now</option></select></label>
                        <button className="admin-primary-button" disabled={article.processing}>Save article</button>
                    </form>
                    {editingArticleId !== null && <form className="admin-content-edit" onSubmit={event => { event.preventDefault(); articleEdit.patch(`/admin/content/articles/${editingArticleId}`, { onSuccess: () => setEditingArticleId(null) }); }}>
                        <h3>Edit article</h3>
                        <label>Title<input required maxLength={180} value={articleEdit.data.title} onChange={event => articleEdit.setData('title', event.target.value)}/></label>
                        <label>Article text<textarea required rows={6} maxLength={50000} value={articleEdit.data.body} onChange={event => articleEdit.setData('body', event.target.value)}/></label>
                        <label>Visibility<select value={articleEdit.data.status} onChange={event => articleEdit.setData('status', event.target.value)}><option value="draft">Draft</option><option value="published">Published</option></select></label>
                        <button className="admin-primary-button" disabled={articleEdit.processing}>Update article</button>
                        <button type="button" className="admin-secondary-button" onClick={() => setEditingArticleId(null)}>Cancel</button>
                    </form>}
                    <div className="admin-content-list">{articleRows.map(item => <article key={item.id}>
                        <div><b>{item.title}</b><small>{item.author ?? 'Staff'} · {item.status} · /education/{item.slug}</small></div>
                        <button className="admin-secondary-button" onClick={() => { setEditingArticleId(item.id); articleEdit.setData({ title: item.title, body: item.body, status: item.status }); }}>Edit</button>
                        <select aria-label={`Set ${item.title} visibility`} value={item.status} onChange={event => router.patch(`/admin/content/articles/${item.id}`, { status: event.target.value }, { preserveScroll: true })}><option value="draft">Draft</option><option value="published">Published</option></select>
                        {item.status === 'published' && <Link href={`/education/${item.slug}`} target="_blank">View</Link>}
                    </article>)}</div>
                </section>}
                {canManageArticles && <section className="admin-panel admin-content-panel">
                    <h2>Frequently asked questions</h2>
                    <form onSubmit={event => { event.preventDefault(); faq.post('/admin/content/faqs', { onSuccess: () => faq.reset() }); }}>
                        <label>Question<input required maxLength={240} value={faq.data.question} onChange={event => faq.setData('question', event.target.value)}/></label>
                        <label>Answer<textarea required rows={4} maxLength={5000} value={faq.data.answer} onChange={event => faq.setData('answer', event.target.value)}/></label>
                        <label>Display order<input type="number" min={0} max={10000} value={faq.data.display_order} onChange={event => faq.setData('display_order', Number(event.target.value))}/></label>
                        <label className="admin-content-check"><input type="checkbox" checked={faq.data.is_visible} onChange={event => faq.setData('is_visible', event.target.checked)}/> Show publicly</label>
                        <button className="admin-primary-button" disabled={faq.processing}>Save FAQ</button>
                    </form>
                    {editingFaqId !== null && <form className="admin-content-edit" onSubmit={event => { event.preventDefault(); faqEdit.patch(`/admin/content/faqs/${editingFaqId}`, { onSuccess: () => setEditingFaqId(null) }); }}>
                        <h3>Edit FAQ</h3>
                        <label>Question<input required maxLength={240} value={faqEdit.data.question} onChange={event => faqEdit.setData('question', event.target.value)}/></label>
                        <label>Answer<textarea required rows={4} maxLength={5000} value={faqEdit.data.answer} onChange={event => faqEdit.setData('answer', event.target.value)}/></label>
                        <label>Display order<input type="number" min={0} max={10000} value={faqEdit.data.display_order} onChange={event => faqEdit.setData('display_order', Number(event.target.value))}/></label>
                        <label className="admin-content-check"><input type="checkbox" checked={faqEdit.data.is_visible} onChange={event => faqEdit.setData('is_visible', event.target.checked)}/> Show publicly</label>
                        <button className="admin-primary-button" disabled={faqEdit.processing}>Update FAQ</button>
                        <button type="button" className="admin-secondary-button" onClick={() => setEditingFaqId(null)}>Cancel</button>
                    </form>}
                    <div className="admin-content-list">{faqs.map(item => <article key={item.id}>
                        <div><b>{item.question}</b><small>Order {item.displayOrder} · {item.isVisible ? 'public' : 'hidden'}</small></div>
                        <button className="admin-secondary-button" onClick={() => { setEditingFaqId(item.id); faqEdit.setData({ question: item.question, answer: item.answer, display_order: item.displayOrder, is_visible: item.isVisible }); }}>Edit</button>
                    </article>)}</div>
                </section>}
                {canManageVideos && <section className="admin-panel admin-content-panel">
                    <h2>Educational videos</h2>
                    <form onSubmit={event => { event.preventDefault(); video.post('/admin/content/videos', { onSuccess: () => video.reset() }); }}>
                        <label>Title<input required maxLength={180} value={video.data.title} onChange={event => video.setData('title', event.target.value)}/></label>
                        <label>YouTube URL<input required type="url" maxLength={2048} placeholder="https://www.youtube.com/watch?v=…" value={video.data.url} onChange={event => video.setData('url', event.target.value)}/></label>
                        <label>Publisher<input maxLength={160} value={video.data.publisher} onChange={event => video.setData('publisher', event.target.value)}/></label>
                        <label>Category<input maxLength={80} value={video.data.category} onChange={event => video.setData('category', event.target.value)}/></label>
                        <label>Description<textarea rows={4} maxLength={5000} value={video.data.description} onChange={event => video.setData('description', event.target.value)}/></label>
                        <label>Display order<input type="number" min={0} max={10000} value={video.data.display_order} onChange={event => video.setData('display_order', Number(event.target.value))}/></label>
                        <label className="admin-content-check"><input type="checkbox" checked={video.data.is_visible} onChange={event => video.setData('is_visible', event.target.checked)}/> Show publicly</label>
                        <label className="admin-content-check"><input type="checkbox" checked={video.data.is_featured} onChange={event => video.setData('is_featured', event.target.checked)}/> Feature on learning page</label>
                        <button className="admin-primary-button" disabled={video.processing}>Save video</button>
                    </form>
                    {editingVideoId !== null && <form className="admin-content-edit" onSubmit={event => { event.preventDefault(); videoEdit.patch(`/admin/content/videos/${editingVideoId}`, { onSuccess: () => setEditingVideoId(null) }); }}>
                        <h3>Edit video</h3>
                        <label>Title<input required maxLength={180} value={videoEdit.data.title} onChange={event => videoEdit.setData('title', event.target.value)}/></label>
                        <label>YouTube URL<input required type="url" maxLength={2048} value={videoEdit.data.url} onChange={event => videoEdit.setData('url', event.target.value)}/></label>
                        <label>Publisher<input maxLength={160} value={videoEdit.data.publisher} onChange={event => videoEdit.setData('publisher', event.target.value)}/></label>
                        <label>Category<input maxLength={80} value={videoEdit.data.category} onChange={event => videoEdit.setData('category', event.target.value)}/></label>
                        <label>Description<textarea rows={4} maxLength={5000} value={videoEdit.data.description} onChange={event => videoEdit.setData('description', event.target.value)}/></label>
                        <label>Display order<input type="number" min={0} max={10000} value={videoEdit.data.display_order} onChange={event => videoEdit.setData('display_order', Number(event.target.value))}/></label>
                        <label className="admin-content-check"><input type="checkbox" checked={videoEdit.data.is_visible} onChange={event => videoEdit.setData('is_visible', event.target.checked)}/> Show publicly</label>
                        <label className="admin-content-check"><input type="checkbox" checked={videoEdit.data.is_featured} onChange={event => videoEdit.setData('is_featured', event.target.checked)}/> Feature on learning page</label>
                        <button className="admin-primary-button" disabled={videoEdit.processing}>Update video</button>
                        <button type="button" className="admin-secondary-button" onClick={() => setEditingVideoId(null)}>Cancel</button>
                    </form>}
                    <div className="admin-content-list">{videos.map(item => <article key={item.id}>
                        <div><b>{item.title}</b><small>{item.publisher ?? 'Publisher not specified'} · {item.category ?? 'Uncategorized'}</small></div>
                        <button className="admin-secondary-button" onClick={() => { setEditingVideoId(item.id); videoEdit.setData({ title: item.title, url: item.url, publisher: item.publisher ?? '', description: item.description ?? '', category: item.category ?? '', display_order: item.displayOrder, is_visible: item.isVisible, is_featured: item.isFeatured }); }}>Edit</button>
                        <label className="admin-content-check"><input type="checkbox" checked={item.isVisible} onChange={event => router.patch(`/admin/content/videos/${item.id}`, { is_visible: event.target.checked }, { preserveScroll: true })}/> Public</label>
                        <a href={item.url} target="_blank" rel="noreferrer">Source ↗</a>
                    </article>)}</div>
                </section>}
            </div>
            <p className="admin-filter-note">Videos are embedded only after a visitor selects one. Publishing content does not imply endorsement by the named publisher.</p>
        </AdminLayout>
    </>;
}
