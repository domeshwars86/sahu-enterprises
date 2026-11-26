import { Link } from '@inertiajs/react';

type ProductDetail = {
    id: number;
    title: string;
    slug: string;
    description: string | null;
    eligibility: string | null;
    documentation: string[];
    category: string;
    categorySlug: string;
    faqs: Array<{ question: string; answer: string }>;
    relatedProducts: Array<{ title: string; slug: string }>;
    lendingPartners: string[];
};
type RelatedArticle = { slug: string; title: string; source_name: string; category: string; published_at: string | null };
type Video = { title: string; url: string; publisher: string | null; category: string | null; description: string | null };

export default function LoanProductDetailPage({ product, news, videos, whatsappUrl }: { product: ProductDetail; news: RelatedArticle[]; videos: Video[]; whatsappUrl: string | null }) {
    const categoryVideos = videos.filter(video => !video.category || video.category.toLowerCase().includes(product.categorySlug.replaceAll('_', ' ')));
    const visibleVideos = (categoryVideos.length ? categoryVideos : videos).slice(0, 3);

    return <>
        <section className="page-hero loan-product-hero">
            <div className="breadcrumb"><Link href="/">Home</Link> <span>/</span> <Link href="/loans">All loans</Link> <span>/</span> {product.category}</div>
            <span className="eyebrow">{product.category} · Loan guidance</span>
            <h1>{product.title}<em>.</em></h1>
            <p>{product.description || 'Availability depends on current service arrangements and the applicable lender. We can help you understand common questions and next steps.'}</p>
            <div className="loan-detail-actions"><Link className="button" href={`/apply?product=${encodeURIComponent(product.slug.replace(/^\//, ''))}`}>Ask about this service <span>↗</span></Link>{whatsappUrl && <a className="button whatsapp-contact-button" href={whatsappUrl} target="_blank" rel="noopener noreferrer">Enquire on WhatsApp ↗</a>}<Link className="button button-outline" href="/emi-calculator">Estimate an EMI</Link></div>
        </section>
        <section className="section loan-product-overview">
            <div className="loan-product-copy"><span className="eyebrow">What to understand</span><h2>Start with your<br/><em>financing question.</em></h2><p>Share only broad estimates in an enquiry. The applicable lender will confirm product availability, eligibility, documentation, rates and terms.</p>
                <div className="loan-product-information">
                    <article><span className="eyebrow">Eligibility</span><h3>Criteria depend on the lender</h3><p>{product.eligibility || 'The lender assesses each applicant under its current criteria. No minimum income, credit score or approval outcome is promised here.'}</p></article>
                    <article><span className="eyebrow">Documentation</span><h3>Ask what is needed</h3>{product.documentation.length ? <ul>{product.documentation.map(item => <li key={item}>{item}</li>)}</ul> : <p>Requirements vary by product and lender. Confirm the current list through a verified lender channel before sharing any records.</p>}</article>
                </div>
            </div>
            <aside className="loan-product-aside"><span className="aside-number">SAFE NEXT STEPS</span><h3>Keep your choices in view.</h3><ol><li>Discuss your broad requirement.</li><li>Confirm the available lender and product.</li><li>Review written eligibility, charges and terms with the lender.</li></ol><p>Never share an OTP, PIN, password or unnecessary sensitive documents.</p>{product.lendingPartners.length > 0 && <div className="verified-lenders"><b>Verified partner availability recorded</b><p>{product.lendingPartners.join(' · ')}</p><small>Availability can change. Confirm directly with the institution.</small></div>}</aside>
        </section>
        <section className="section loan-product-faq-section"><div className="section-intro"><div><span className="eyebrow">Product questions</span><h2>Clear answers,<br/><em>before next steps.</em></h2></div></div><div className="faq-list">{product.faqs.map(faq => <details key={faq.question}><summary>{faq.question}<span>＋</span></summary><p>{faq.answer}</p></details>)}</div></section>
        {product.relatedProducts.length > 0 && <section className="section loan-related-products"><div className="section-intro"><div><span className="eyebrow">You may also explore</span><h2>Related loan<br/><em>assistance.</em></h2></div><Link href="/loans" className="text-link">Browse all available loans →</Link></div><div className="marketplace-product-grid">{product.relatedProducts.map(item => <Link className="marketplace-product-card" href={item.slug} key={item.slug}><span className="product-icon">↗</span><h3>{item.title}</h3><p>Explore related financing considerations and ask about current availability.</p><b>View guidance ↗</b></Link>)}</div></section>}
        {news.length > 0 && <section className="section loan-related-news"><div className="section-intro"><div><span className="eyebrow">Finance and lending updates</span><h2>Related news,<br/><em>from publishers.</em></h2></div><Link href="/news" className="text-link">All news →</Link></div><div className="home-news-grid">{news.map(article => <Link className="home-news-card" href={`/news/${article.slug}`} key={article.slug}><span className="eyebrow">{article.category}</span><h3>{article.title}</h3><span>{article.source_name}</span></Link>)}</div></section>}
        {visibleVideos.length > 0 && <section className="section loan-related-videos"><div className="section-intro"><div><span className="eyebrow">Financial learning</span><h2>Learn before<br/><em>you decide.</em></h2></div><Link href="/videos" className="text-link">Video library →</Link></div><div className="resource-link-grid">{visibleVideos.map(video => <article className="resource-link-card" key={video.url}><span className="eyebrow">{video.publisher || 'Financial education'}</span><h2>{video.title}</h2><p>{video.description}</p><a href={video.url} target="_blank" rel="noopener noreferrer">View video ↗</a></article>)}</div></section>}
        <section className="section loan-product-cta"><span className="eyebrow">Talk with our team</span><h2>Start with a question.<br/><em>Decide at your pace.</em></h2><p>We can confirm whether this service is currently available and explain the general next steps.</p><Link className="button" href={`/apply?product=${encodeURIComponent(product.slug.replace(/^\//, ''))}`}>Request a callback <span>↗</span></Link>{whatsappUrl && <a className="button whatsapp-contact-button" href={whatsappUrl} target="_blank" rel="noopener noreferrer">Ask about {product.title} on WhatsApp ↗</a>}</section>
    </>;
}
