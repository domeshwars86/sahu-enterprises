import React, { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AdminLayout from '../../../components/AdminLayout';

type Product = { id: number; name: string; slug: string; is_enabled: boolean };
type Mapping = { loan_product_id: number; name?: string; partnership_status: 'pending' | 'active' | 'ended'; is_available: boolean; internal_sourcing_info: string | null };
type Partner = { id: number; name: string; category: string | null; authorizedContact: string | null; products: string[]; productMappings: Mapping[]; notes: string | null; verified: boolean; active: boolean };
type Props = { partners: { data: Partner[]; links: Array<{ url: string | null; label: string; active: boolean }>; total: number }; products: Product[] };
type PartnerForm = { name: string; institution_category: string; authorized_contact: string; supported_products: string; internal_notes: string; is_verified: boolean; is_active: boolean; product_mappings: Mapping[] };

export default function Index({ partners, products }: Props) {
    const createForm = useForm<PartnerForm>({ name: '', institution_category: '', authorized_contact: '', supported_products: '', internal_notes: '', is_verified: false, is_active: false, product_mappings: [] });
    const [selectedProduct, setSelectedProduct] = useState('');
    const addMapping = () => {
        const productId = Number(selectedProduct);
        if (!productId || createForm.data.product_mappings.some(mapping => mapping.loan_product_id === productId)) return;
        const product = products.find(item => item.id === productId);
        createForm.setData('product_mappings', [...createForm.data.product_mappings, { loan_product_id: productId, name: product?.name, partnership_status: 'pending', is_available: false, internal_sourcing_info: '' }]);
        setSelectedProduct('');
    };
    const create = (event: React.FormEvent) => {
        event.preventDefault();
        createForm.transform(data => ({ ...data, supported_products: data.supported_products.split(',').map(value => value.trim()).filter(Boolean) }));
        createForm.post('/admin/lending-partners', { onSuccess: () => createForm.reset() });
    };

    return <><Head title="Lending partners · Admin"/><AdminLayout title="Lending partners">
        <div className="admin-welcome-row"><p className="admin-muted">Record verified lender authorization and map it to canonical loan products.</p><span className="admin-total-pill">{partners.total} records</span></div>
        <div className="admin-partner-disclosure">Public product pages show lender names only when the partner is verified and active, the product mapping is active and available, and an internal sourcing authorization note is recorded.</div>
        <section className="admin-panel admin-partner-create"><h2>Add partner record</h2><form onSubmit={create}>
            <label>Institution name<input required maxLength={160} value={createForm.data.name} onChange={event => createForm.setData('name', event.target.value)}/></label>
            <label>Institution category<input maxLength={80} placeholder="Bank, NBFC…" value={createForm.data.institution_category} onChange={event => createForm.setData('institution_category', event.target.value)}/></label>
            <label>Authorized contact<input value={createForm.data.authorized_contact} onChange={event => createForm.setData('authorized_contact', event.target.value)}/></label>
            <label>Legacy product tags<input placeholder="Optional internal tags" value={createForm.data.supported_products} onChange={event => createForm.setData('supported_products', event.target.value)}/></label>
            <label>Internal authorization note<textarea required rows={2} value={createForm.data.internal_notes} onChange={event => createForm.setData('internal_notes', event.target.value)}/></label>
            <label className="admin-checkbox"><input type="checkbox" checked={createForm.data.is_verified} onChange={event => createForm.setData('is_verified', event.target.checked)}/> Verified</label>
            <label className="admin-checkbox"><input type="checkbox" checked={createForm.data.is_active} onChange={event => createForm.setData('is_active', event.target.checked)}/> Active</label>
            <ProductMappingEditor products={products} mappings={createForm.data.product_mappings} setMappings={mappings => createForm.setData('product_mappings', mappings)} selectedProduct={selectedProduct} setSelectedProduct={setSelectedProduct} onAdd={addMapping}/>
            <button className="admin-primary-button" disabled={createForm.processing}>Save partner record</button>
            {Object.values(createForm.errors).map((error, index) => <div className="admin-error" key={index}>{error}</div>)}
        </form></section>
        <section className="admin-panel admin-partner-list"><div className="admin-panel-heading"><div><h2>Partner directory</h2><p>Contacts and internal sourcing details are staff-only.</p></div></div>
            <div className="admin-partner-record-list">{partners.data.map(partner => <PartnerEditor key={partner.id} partner={partner} products={products}/>)}</div>
            {partners.data.length === 0 && <div className="admin-empty"><b>No partners recorded</b><span>Add only relationships with verified business authorization.</span></div>}
            <div className="admin-pagination">{partners.links.map((link, index) => <button key={`${link.label}-${index}`} disabled={!link.url} className={link.active ? 'active' : ''} onClick={() => link.url && router.get(link.url, {}, { preserveScroll: true })} dangerouslySetInnerHTML={{ __html: link.label }}/>)}</div>
        </section>
    </AdminLayout></>;
}

function ProductMappingEditor({ products, mappings, setMappings, selectedProduct, setSelectedProduct, onAdd }: { products: Product[]; mappings: Mapping[]; setMappings: (mappings: Mapping[]) => void; selectedProduct: string; setSelectedProduct: (value: string) => void; onAdd: () => void }) {
    const updateMapping = (index: number, patch: Partial<Mapping>) => setMappings(mappings.map((mapping, current) => current === index ? { ...mapping, ...patch } : mapping));
    return <div className="partner-product-mappings"><div className="admin-partner-mapping-heading"><div><b>Product authorization map</b><small>Map exact products; do not infer support from institution name.</small></div><div><select value={selectedProduct} onChange={event => setSelectedProduct(event.target.value)}><option value="">Choose a canonical product</option>{products.filter(product => !mappings.some(mapping => mapping.loan_product_id === product.id)).map(product => <option key={product.id} value={product.id}>{product.name}</option>)}</select><button type="button" className="admin-secondary-button" onClick={onAdd} disabled={!selectedProduct}>Add mapping</button></div></div>
        {mappings.map((mapping, index) => <div className="admin-partner-mapping-row" key={mapping.loan_product_id}><b>{mapping.name ?? products.find(product => product.id === mapping.loan_product_id)?.name ?? `Product ${mapping.loan_product_id}`}</b><label>Status<select value={mapping.partnership_status} onChange={event => updateMapping(index, { partnership_status: event.target.value as Mapping['partnership_status'] })}><option value="pending">Pending verification</option><option value="active">Active authorization</option><option value="ended">Ended</option></select></label><label className="admin-checkbox"><input type="checkbox" checked={mapping.is_available} onChange={event => updateMapping(index, { is_available: event.target.checked })}/> Available for this product</label><label className="partner-sourcing-note">Internal sourcing authorization note<textarea required={mapping.is_available} rows={2} maxLength={1000} value={mapping.internal_sourcing_info ?? ''} onChange={event => updateMapping(index, { internal_sourcing_info: event.target.value })}/></label><button type="button" className="admin-text-button" onClick={() => setMappings(mappings.filter((_, current) => current !== index))}>Remove map</button></div>)}
    </div>;
}

function PartnerEditor({ partner, products }: { partner: Partner; products: Product[] }) {
    const form = useForm<PartnerForm>({ name: partner.name, institution_category: partner.category ?? '', authorized_contact: partner.authorizedContact ?? '', supported_products: partner.products.join(', '), internal_notes: partner.notes ?? '', is_verified: partner.verified, is_active: partner.active, product_mappings: partner.productMappings });
    const [selectedProduct, setSelectedProduct] = useState('');
    const addMapping = () => {
        const productId = Number(selectedProduct);
        if (!productId || form.data.product_mappings.some(mapping => mapping.loan_product_id === productId)) return;
        const product = products.find(item => item.id === productId);
        form.setData('product_mappings', [...form.data.product_mappings, { loan_product_id: productId, name: product?.name, partnership_status: 'pending', is_available: false, internal_sourcing_info: '' }]);
        setSelectedProduct('');
    };
    const save = (event: React.FormEvent) => { event.preventDefault(); form.transform(data => ({ ...data, supported_products: data.supported_products.split(',').map(value => value.trim()).filter(Boolean) })); form.patch(`/admin/lending-partners/${partner.id}`, { preserveScroll: true }); };

    return <details className="admin-partner-record"><summary><span><b>{partner.name}</b><small>{partner.category ?? 'Institution category not set'} · {partner.productMappings.length} product mappings</small></span><span>{partner.verified ? 'Verified' : 'Unverified'} · {partner.active ? 'Active' : 'Inactive'}</span></summary>
        <form onSubmit={save} className="admin-partner-edit-form"><div className="admin-partner-edit-grid">
            <label>Institution name<input required maxLength={160} value={form.data.name} onChange={event => form.setData('name', event.target.value)}/></label>
            <label>Institution category<input maxLength={80} value={form.data.institution_category} onChange={event => form.setData('institution_category', event.target.value)}/></label>
            <label>Authorized contact<input value={form.data.authorized_contact} onChange={event => form.setData('authorized_contact', event.target.value)}/></label>
            <label>Legacy product tags<input value={form.data.supported_products} onChange={event => form.setData('supported_products', event.target.value)}/></label>
            <label className="partner-sourcing-note">Internal authorization note<textarea required rows={2} value={form.data.internal_notes} onChange={event => form.setData('internal_notes', event.target.value)}/></label>
            <label className="admin-checkbox"><input type="checkbox" checked={form.data.is_verified} onChange={event => form.setData('is_verified', event.target.checked)}/> Verified</label>
            <label className="admin-checkbox"><input type="checkbox" checked={form.data.is_active} onChange={event => form.setData('is_active', event.target.checked)}/> Active</label>
        </div><ProductMappingEditor products={products} mappings={form.data.product_mappings} setMappings={mappings => form.setData('product_mappings', mappings)} selectedProduct={selectedProduct} setSelectedProduct={setSelectedProduct} onAdd={addMapping}/>
            <button className="admin-primary-button" disabled={form.processing}>Save partner & product maps</button>{Object.values(form.errors).map((error, index) => <div className="admin-error" key={index}>{error}</div>)}
        </form>
    </details>;
}
