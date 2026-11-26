import React from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AdminLayout from '../../../components/AdminLayout';

type EnquiryField = { key: string; label: string; type: 'text' | 'number' | 'select'; required: boolean; options?: Record<string, string> };
type Category = { id: number; slug: string; name: string; description: string | null; icon: string; enquiry_fields: EnquiryField[]; is_enabled: boolean; display_order: number; products_count: number };

export default function Index({ categories }: { categories: Category[] }) {
    const createForm = useForm({ name: '', slug: '', description: '', icon: '↗', enquiry_fields: '[]', is_enabled: false, display_order: 0 });
    const create = (event: React.FormEvent) => { event.preventDefault(); createForm.post('/admin/loan-categories', { onSuccess: () => createForm.reset() }); };
    const update = (category: Category, patch: Partial<Category>) => router.patch(`/admin/loan-categories/${category.id}`, {
        name: patch.name ?? category.name,
        slug: patch.slug ?? category.slug,
        description: patch.description ?? category.description,
        icon: patch.icon ?? category.icon,
        enquiry_fields: category.enquiry_fields ?? [],
        is_enabled: patch.is_enabled ?? category.is_enabled,
        display_order: patch.display_order ?? category.display_order,
    }, { preserveScroll: true });

    return <><Head title="Loan categories · Admin"/><AdminLayout title="Loan categories">
        <div className="admin-welcome-row"><p className="admin-muted">Organize canonical products into searchable public categories.</p><span className="admin-total-pill">{categories.length} categories</span></div>
        <section className="admin-panel catalogue-create"><h2>Create a category</h2><p>New categories are private until they contain an available product and are enabled.</p>
            <form onSubmit={create} className="catalogue-form-grid">
                <label>Category name<input required maxLength={160} value={createForm.data.name} onChange={event => createForm.setData('name', event.target.value)}/></label>
                <label>URL slug <small>Optional; generated from the name</small><input maxLength={100} value={createForm.data.slug} onChange={event => createForm.setData('slug', event.target.value)}/></label>
                <label>Icon<input maxLength={24} value={createForm.data.icon} onChange={event => createForm.setData('icon', event.target.value)}/></label>
                <label>Display order<input type="number" min={0} value={createForm.data.display_order} onChange={event => createForm.setData('display_order', Number(event.target.value))}/></label>
                <label className="catalogue-description">Description<textarea maxLength={1000} rows={2} value={createForm.data.description} onChange={event => createForm.setData('description', event.target.value)}/></label>
                <label className="catalogue-description">Dynamic enquiry fields <small>JSON array: key, label, type (text/number/select), required, and options for selects.</small><textarea rows={6} value={createForm.data.enquiry_fields} onChange={event => createForm.setData('enquiry_fields', event.target.value)}/>{createForm.errors.enquiry_fields && <small className="admin-error">{createForm.errors.enquiry_fields}</small>}</label>
                <button className="admin-primary-button" disabled={createForm.processing}>{createForm.processing ? 'Saving…' : 'Create category'}</button>
                {Object.values(createForm.errors).map((error, index) => <div className="admin-error" key={index}>{error}</div>)}
            </form>
        </section>
        <section className="catalogue-category-list">{categories.map(category => <CategoryCard category={category} key={category.id} onSave={update}/>)}</section>
    </AdminLayout></>;
}

function CategoryCard({ category, onSave }: { category: Category; onSave: (category: Category, patch: Partial<Category>) => void }) {
    const form = useForm({ name: category.name, slug: category.slug, description: category.description ?? '', icon: category.icon, enquiry_fields: JSON.stringify(category.enquiry_fields ?? [], null, 2), is_enabled: category.is_enabled, display_order: category.display_order });
    const submit = (event: React.FormEvent) => { event.preventDefault(); form.patch(`/admin/loan-categories/${category.id}`, { preserveScroll: true }); };

    return <form className="admin-panel catalogue-category-card" onSubmit={submit}>
        <div className="catalogue-category-status"><span className="product-icon">{form.data.icon}</span><div><b>{category.name}</b><small>{category.products_count} linked products · {category.slug}</small></div>
            <label className="admin-toggle"><input type="checkbox" checked={form.data.is_enabled} onChange={event => { form.setData('is_enabled', event.target.checked); onSave(category, { is_enabled: event.target.checked }); }}/><span>{form.data.is_enabled ? 'Public' : 'Hidden'}</span></label>
        </div>
        <div className="catalogue-form-grid">
            <label>Name<input required maxLength={160} value={form.data.name} onChange={event => form.setData('name', event.target.value)}/></label>
            <label>URL slug<input required maxLength={100} value={form.data.slug} onChange={event => form.setData('slug', event.target.value)}/></label>
            <label>Icon<input maxLength={24} value={form.data.icon} onChange={event => form.setData('icon', event.target.value)}/></label>
            <label>Display order<input type="number" min={0} value={form.data.display_order} onChange={event => form.setData('display_order', Number(event.target.value))}/></label>
            <label className="catalogue-description">Description<textarea maxLength={1000} rows={2} value={form.data.description} onChange={event => form.setData('description', event.target.value)}/></label>
            <label className="catalogue-description">Dynamic enquiry fields <small>JSON array; only listed keys are accepted by the enquiry endpoint.</small><textarea rows={6} value={form.data.enquiry_fields} onChange={event => form.setData('enquiry_fields', event.target.value)}/>{form.errors.enquiry_fields && <small className="admin-error">{form.errors.enquiry_fields}</small>}</label>
        </div>
        <div className="catalogue-category-actions"><button className="admin-primary-button" disabled={form.processing}>Save category</button>{Object.values(form.errors).map((error, index) => <span className="admin-error" key={index}>{error}</span>)}</div>
    </form>;
}
