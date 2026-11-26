import React, { useMemo, useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AdminLayout from '../../../components/AdminLayout';

type Category = { id: number; name: string; slug: string; is_enabled: boolean };
type Faq = { question: string; answer: string };
type Product = { id: number; slug: string; name: string; category: string; description: string | null; eligibility: string | null; documentation: string[]; seoTitle: string | null; seoDescription: string | null; is_enabled: boolean; is_featured: boolean; categories: number[]; categoryNames: string[]; faqs: Faq[]; relatedProductIds: number[] };
type ProductFormData = { name: string; slug: string; category_ids: number[]; description: string; eligibility: string; documentation: string; seo_title: string; seo_description: string; is_enabled: boolean; is_featured: boolean; faqs: Faq[]; related_product_ids: number[] };

const defaultFaqs: Faq[] = [
    { question: 'Does Sahu Enterprises approve this loan?', answer: 'No. The applicable lending institution independently decides eligibility, terms, approval and disbursement.' },
    { question: 'What are the eligibility criteria and documents?', answer: 'Requirements vary by lender, product and applicant. Confirm the current requirements directly with the applicable institution.' },
];

export default function Index({ products, categories }: { products: Product[]; categories: Category[] }) {
    const [search, setSearch] = useState('');
    const visibleProducts = useMemo(() => products.filter(product => `${product.name} ${product.categoryNames.join(' ')}`.toLowerCase().includes(search.toLowerCase())), [products, search]);

    return <><Head title="Loan products · Admin"/><AdminLayout title="Loan products">
        <div className="admin-welcome-row"><p className="admin-muted">Manage canonical products, public guidance, FAQs, search details and recommendations.</p><span className="admin-total-pill">{products.length} products · {products.filter(product => product.is_enabled).length} public</span></div>
        <div className="admin-product-notice">New products are created hidden. To publish, first record an active, verified lender-product relationship in Lending partners. This prevents unsupported products from appearing publicly.</div>
        <ProductEditor categories={categories} allProducts={products} />
        <div className="admin-catalogue-toolbar"><label>Search catalogue<input type="search" value={search} onChange={event => setSearch(event.target.value)} placeholder="Product or category"/></label><span>{visibleProducts.length} results</span></div>
        <section className="admin-product-edit-list">{visibleProducts.map(product => <ProductEditor key={product.id} product={product} categories={categories} allProducts={products}/>)}</section>
    </AdminLayout></>;
}

function ProductEditor({ product, categories, allProducts }: { product?: Product; categories: Category[]; allProducts: Product[] }) {
    const form = useForm<ProductFormData>({
        name: product?.name ?? '',
        slug: product?.slug ?? '',
        category_ids: product?.categories ?? [],
        description: product?.description ?? '',
        eligibility: product?.eligibility ?? '',
        documentation: product?.documentation.join('\n') ?? '',
        seo_title: product?.seoTitle ?? '',
        seo_description: product?.seoDescription ?? '',
        is_enabled: product?.is_enabled ?? false,
        is_featured: product?.is_featured ?? false,
        faqs: product?.faqs.length ? product.faqs : defaultFaqs,
        related_product_ids: product?.relatedProductIds ?? [],
    });
    const onSubmit = (event: React.FormEvent) => {
        event.preventDefault();
        form.transform(data => ({ ...data, documentation: data.documentation.split('\n').map(item => item.trim()).filter(Boolean) }));
        if (product) {
            form.patch(`/admin/products/${product.id}`, { preserveScroll: true });
        } else {
            form.post('/admin/products', { onSuccess: () => form.reset() });
        }
    };
    const setFaq = (index: number, field: keyof Faq, value: string) => form.setData('faqs', form.data.faqs.map((faq, faqIndex) => faqIndex === index ? { ...faq, [field]: value } : faq));
    const toggleCategory = (categoryId: number, checked: boolean) => form.setData('category_ids', checked ? [...form.data.category_ids, categoryId] : form.data.category_ids.filter(id => id !== categoryId));
    const selectableRelated = allProducts.filter(item => item.id !== product?.id);

    return <details className={product ? 'admin-panel admin-product-editor' : 'admin-panel admin-product-create'} open={!product}>
        {product ? <summary className="admin-product-summary"><span className="admin-product-summary-icon">◈</span><span><b>{product.name}</b><small>/{product.slug} · {product.categoryNames.join(', ') || product.category}</small></span><span className={product.is_enabled ? 'admin-status public' : 'admin-status'}>{product.is_enabled ? 'Public' : 'Hidden'}</span><span className="admin-summary-arrow">⌄</span></summary> : <div className="admin-product-create-heading"><h2>Create a product</h2><p>Product titles and descriptions are informational. Avoid rates, guarantees or unverified lender claims.</p></div>}
        <form onSubmit={onSubmit} className="admin-product-form">
            <div className="catalogue-form-grid">
                <label>Product name<input required maxLength={160} value={form.data.name} onChange={event => form.setData('name', event.target.value)}/>{form.errors.name && <small className="admin-error">{form.errors.name}</small>}</label>
                <label>Route slug <small>Use lowercase URL text; generated if blank</small><input maxLength={100} placeholder="loans/product-name" value={form.data.slug} onChange={event => form.setData('slug', event.target.value)}/>{form.errors.slug && <small className="admin-error">{form.errors.slug}</small>}</label>
                <fieldset className="catalogue-fieldset"><legend>Categories</legend><div className="catalogue-category-options">{categories.map(category => <label key={category.id}><input type="checkbox" checked={form.data.category_ids.includes(category.id)} onChange={event => toggleCategory(category.id, event.target.checked)}/>{category.name}</label>)}</div>{form.errors.category_ids && <small className="admin-error">{form.errors.category_ids}</small>}</fieldset>
                <label className="catalogue-description">Public description<textarea rows={3} maxLength={1200} value={form.data.description} onChange={event => form.setData('description', event.target.value)}/>{form.errors.description && <small className="admin-error">{form.errors.description}</small>}</label>
                <label className="catalogue-description">Eligibility guidance<textarea rows={3} maxLength={4000} placeholder="Only publish information verified for this product and lender." value={form.data.eligibility} onChange={event => form.setData('eligibility', event.target.value)}/></label>
                <label className="catalogue-description">Documentation guidance <small>One general document per line; never ask for sensitive uploads here.</small><textarea rows={3} value={form.data.documentation} onChange={event => form.setData('documentation', event.target.value)}/></label>
                <label>SEO title<input maxLength={180} value={form.data.seo_title} onChange={event => form.setData('seo_title', event.target.value)}/></label>
                <label>SEO description<textarea rows={2} maxLength={320} value={form.data.seo_description} onChange={event => form.setData('seo_description', event.target.value)}/></label>
                <label>Related products <small>Choose up to 12</small><select multiple size={Math.min(6, Math.max(3, selectableRelated.length))} value={form.data.related_product_ids.map(String)} onChange={event => form.setData('related_product_ids', Array.from(event.target.selectedOptions, option => Number(option.value)))}>{selectableRelated.map(item => <option value={item.id} key={item.id}>{item.name}</option>)}</select></label>
            </div>
            <div className="admin-product-faq-editor"><div className="admin-panel-heading"><div><h3>Product FAQs</h3><p>Answers are displayed on the public product page.</p></div><button type="button" className="admin-secondary-button" onClick={() => form.setData('faqs', [...form.data.faqs, { question: '', answer: '' }])}>Add FAQ</button></div>
                {form.data.faqs.map((faq, index) => <div className="admin-product-faq-row" key={index}><label>Question<input maxLength={240} value={faq.question} onChange={event => setFaq(index, 'question', event.target.value)}/></label><label>Answer<textarea rows={2} maxLength={3000} value={faq.answer} onChange={event => setFaq(index, 'answer', event.target.value)}/></label><button type="button" className="admin-text-button" onClick={() => form.setData('faqs', form.data.faqs.filter((_, faqIndex) => faqIndex !== index))}>Remove</button></div>)}
            </div>
            <div className="admin-product-form-footer"><label className="admin-toggle"><input type="checkbox" checked={form.data.is_featured} onChange={event => form.setData('is_featured', event.target.checked)}/><span>Feature on homepage</span></label><label className="admin-toggle"><input type="checkbox" checked={form.data.is_enabled} onChange={event => form.setData('is_enabled', event.target.checked)}/><span>Publicly available</span></label><button className="admin-primary-button" disabled={form.processing || categories.length === 0}>{form.processing ? 'Saving…' : product ? 'Save product' : 'Create hidden product'}</button></div>
            {Object.values(form.errors).map((error, index) => <div className="admin-error" key={index}>{error}</div>)}
        </form>
    </details>;
}
