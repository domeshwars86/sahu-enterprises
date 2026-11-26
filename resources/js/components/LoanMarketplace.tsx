import { useMemo, useState } from 'react';
import { Link } from '@inertiajs/react';

type Product = { title: string; slug: string; icon: string; desc: string; tag?: string };
type Category = { id: number; slug: string; name: string; description: string | null; icon: string; products: Product[] };

export default function LoanMarketplace({ categories, products, companyName = 'SAHU ENTERPRISES' }: { categories: Category[]; products: Product[]; companyName?: string }) {
    const [query, setQuery] = useState('');
    const [selectedCategory, setSelectedCategory] = useState('all');
    const searchTerm = query.trim().toLocaleLowerCase();
    const visibleCategories = useMemo(() => categories
        .filter(category => selectedCategory === 'all' || selectedCategory === category.slug)
        .map(category => ({ ...category, products: category.products.filter(product => !searchTerm || `${product.title} ${product.desc}`.toLocaleLowerCase().includes(searchTerm)) }))
        .filter(category => category.products.length > 0), [categories, searchTerm, selectedCategory]);
    const uncategorized = categories.length === 0 ? products.filter(product => !searchTerm || `${product.title} ${product.desc}`.toLocaleLowerCase().includes(searchTerm)) : [];
    const productCount = visibleCategories.reduce((count, category) => count + category.products.length, uncategorized.length);

    return <>
        <section className="page-hero loans-marketplace-hero">
            <div className="breadcrumb"><Link href="/">Home</Link> <span>/</span> All loans</div>
            <span className="eyebrow">Loan assistance marketplace</span>
            <h1>Explore options.<br/><em>Choose with clarity.</em></h1>
            <p>Browse the loan-assistance services currently available through {companyName}. Lenders decide eligibility, pricing and approval.</p>
            <Link className="button" href="/apply">Request a callback <span>↗</span></Link>
        </section>
        <section className="section loan-marketplace">
            <div className="marketplace-controls">
                <label className="marketplace-search"><span aria-hidden="true">⌕</span><input type="search" value={query} onChange={event => setQuery(event.target.value)} placeholder="Search loans and financing options" aria-label="Search loans"/></label>
                <div className="marketplace-categories" aria-label="Browse loan categories">
                    <button type="button" className={selectedCategory === 'all' ? 'active' : ''} onClick={() => setSelectedCategory('all')}>All available</button>
                    {categories.map(category => <button type="button" key={category.id} className={selectedCategory === category.slug ? 'active' : ''} onClick={() => setSelectedCategory(category.slug)}>{category.name}</button>)}
                </div>
            </div>
            <div className="marketplace-result-count">{productCount} available {productCount === 1 ? 'service' : 'services'}</div>
            {visibleCategories.map(category => <section className="marketplace-category" key={category.slug}>
                <div className="marketplace-category-heading"><span className="product-icon">{category.icon}</span><div><span className="eyebrow">Explore category</span><h2>{category.name}</h2><p>{category.description}</p></div></div>
                <div className="marketplace-product-grid">{category.products.map((product, index) => <Link className={`marketplace-product-card marketplace-tone-${index % 4}`} href={product.slug} key={`${category.slug}-${product.slug}`}>
                    <div className="marketplace-product-top"><span className="product-icon">{product.icon}</span>{product.tag && <span className="product-tag">{product.tag}</span>}</div>
                    <h3>{product.title}</h3><p>{product.desc}</p><b>View guidance <span>↗</span></b>
                </Link>)}</div>
            </section>)}
            {uncategorized.length > 0 && <div className="marketplace-product-grid">{uncategorized.map(product => <Link className="marketplace-product-card" href={product.slug} key={product.slug}><span className="product-icon">{product.icon}</span><h3>{product.title}</h3><p>{product.desc}</p><b>View guidance ↗</b></Link>)}</div>}
            {productCount === 0 && <div className="marketplace-empty"><b>No matching available service</b><p>Try a different search or ask us to confirm current availability.</p><Link className="text-link" href="/contact">Contact our team ↗</Link></div>}
            <p className="source-disclosure">Only listed services are currently enabled. Availability, eligibility, rates, fees and approval are determined by the applicable lending institution. Ask us to confirm the service and lender before you apply.</p>
        </section>
    </>;
}
