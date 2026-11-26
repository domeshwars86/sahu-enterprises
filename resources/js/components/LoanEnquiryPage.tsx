import { useMemo, useRef, useState } from 'react';
import { Link, useHttp } from '@inertiajs/react';

type EnquiryField = {
    key: string;
    label: string;
    type: 'text' | 'number' | 'select';
    required: boolean;
    options?: Record<string, string>;
};

type LoanProduct = {
    id?: number | null;
    title: string;
    slug: string;
    desc: string;
    category?: string;
    categorySlug?: string;
    enquiryFields?: EnquiryField[];
};

type EnquiryData = {
    idempotency_key: string;
    product_slug: string;
    customer_name: string;
    mobile_number: string;
    email: string;
    city: string;
    loan_category: string;
    requested_amount: string;
    preferred_tenure: string;
    employment_category: string;
    vehicle_type: string;
    vehicle_condition: string;
    vehicle_price: string;
    down_payment: string;
    details: Record<string, string>;
    customer_notes: string;
    preferred_callback_time: string;
    contact_consent: boolean;
    marketing_consent: boolean;
    website: string;
};

type EnquiryResponse = { message: string; reference_number: string };

function initialData(product: LoanProduct | undefined): EnquiryData {
    return {
        idempotency_key: crypto.randomUUID(),
        product_slug: product?.slug.replace(/^\//, '') ?? '',
        customer_name: '',
        mobile_number: '',
        email: '',
        city: '',
        loan_category: product?.categorySlug ?? 'new_car',
        requested_amount: '',
        preferred_tenure: '',
        employment_category: '',
        vehicle_type: '',
        vehicle_condition: '',
        vehicle_price: '',
        down_payment: '',
        details: {},
        customer_notes: '',
        preferred_callback_time: '',
        contact_consent: false,
        marketing_consent: false,
        website: '',
    };
}

function inputValue(data: EnquiryData, key: string): string {
    return data.details[key] ?? '';
}

export default function LoanEnquiryPage({ loanProducts = [], companyName = 'SAHU ENTERPRISES' }: { loanProducts?: LoanProduct[]; companyName?: string }) {
    const params = new URLSearchParams(window.location.search);
    const requestedSlug = params.get('product')?.replace(/^\//, '') ?? '';
    const initialProduct = loanProducts.find(product => product.slug.replace(/^\//, '') === requestedSlug) ?? loanProducts[0];
    const formRef = useRef<HTMLFormElement>(null);
    const [step, setStep] = useState(1);
    const [product, setProduct] = useState<LoanProduct | undefined>(initialProduct);
    const [referenceNumber, setReferenceNumber] = useState('');
    const [message, setMessage] = useState('');
    const { data, setData, post, processing, errors, clearErrors } = useHttp<EnquiryData, EnquiryResponse>('post', '/apply', initialData(initialProduct));
    const fields = useMemo(() => product?.enquiryFields ?? [], [product]);

    const update = <K extends keyof EnquiryData>(field: K, value: EnquiryData[K]) => setData(previous => ({ ...previous, [field]: value }));
    const updateDetail = (key: string, value: string) => setData(previous => ({ ...previous, details: { ...previous.details, [key]: value } }));

    function selectProduct(next: LoanProduct) {
        setProduct(next);
        setData(previous => ({
            ...previous,
            product_slug: next.slug.replace(/^\//, ''),
            loan_category: next.categorySlug ?? 'vehicle_finance',
            details: {},
            vehicle_type: '',
            vehicle_condition: '',
        }));
    }

    function continueStep() {
        clearErrors();
        setMessage('');

        if (!formRef.current?.reportValidity()) {
            return;
        }

        if (step < 4) {
            setStep(step + 1);
            return;
        }

        post('/apply', {
            onSuccess: response => {
                setReferenceNumber(response.reference_number);
                setMessage(response.message);
            },
            onError: () => setMessage('Please check the highlighted details and try again.'),
            onNetworkError: () => setMessage('We could not send your enquiry. Please try again when your connection is available.'),
        });
    }

    if (referenceNumber) {
        return <section className="section form-section"><div className="form-card form-success" role="status"><span className="preview-icon">✓</span><span className="eyebrow">Enquiry received</span><h1>Thank you for contacting <em>{companyName}.</em></h1><p>{message}</p><div className="reference-number"><span>Your reference number</span><strong>{referenceNumber}</strong></div><p className="source-disclosure">A lending institution assesses every application and determines any offer and terms. Loan approval is not guaranteed.</p><Link className="button" href="/">Back to home</Link></div></section>;
    }

    return <>
        <section className="page-hero apply-hero">
            <div className="breadcrumb"><Link href="/">Home</Link> <span>/</span> Loan assistance</div>
            <span className="eyebrow">A conversation starts here</span>
            <h1>Tell us what’s<br/><em>on your mind.</em></h1>
            <p>Share a few details so our team can respond to your enquiry. We do not ask for OTPs, passwords or document uploads.</p>
        </section>
        <section className="section form-section">
            <div className="form-progress" aria-label={`Step ${step} of 4`}>
                {['Loan type', 'Your needs', 'Contact', 'Review'].map((label, index) => <div className={step === index + 1 ? 'current' : step > index + 1 ? 'done' : ''} key={label}><span>{step > index + 1 ? '✓' : `0${index + 1}`}</span><b>{label}</b></div>)}
            </div>
            <form ref={formRef} className="form-card" onSubmit={event => event.preventDefault()} noValidate>
                <div className="form-step-label">STEP 0{step} OF 04</div>
                {step === 1 && <>
                    <h2>What would you like<br/>help <em>exploring?</em></h2>
                    <div className="loan-choice-grid">
                        {loanProducts.map(option => <button key={option.slug} type="button" className={product?.slug === option.slug ? 'selected' : ''} aria-pressed={product?.slug === option.slug} onClick={() => selectProduct(option)}><span>{product?.slug === option.slug ? '✓' : '↗'}</span>{option.title}</button>)}
                    </div>
                    {!loanProducts.length && <p>Our team can confirm current service availability before you share personal details.</p>}
                </>}
                {step === 2 && <>
                    <h2>Tell us a little<br/><em>about your plans.</em></h2>
                    <div className="input-grid">
                        <label>Approximate loan amount<input type="number" min="1" inputMode="numeric" placeholder="e.g. ₹8,00,000" value={data.requested_amount} onChange={event => update('requested_amount', event.target.value)} required/>{errors.requested_amount && <small className="form-error">{errors.requested_amount}</small>}</label>
                        <label>City<input placeholder="Your city" value={data.city} onChange={event => update('city', event.target.value)} required/>{errors.city && <small className="form-error">{errors.city}</small>}</label>
                        {fields.map(field => <label key={field.key}>{field.label}{field.type === 'select' ? <select value={inputValue(data, field.key)} onChange={event => updateDetail(field.key, event.target.value)} required={field.required}><option value="">Choose one</option>{Object.entries(field.options ?? {}).map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select> : <input type={field.type === 'number' ? 'number' : 'text'} min={field.type === 'number' ? 0 : undefined} maxLength={field.type === 'text' ? 240 : undefined} placeholder={field.type === 'number' ? 'Approximate amount' : undefined} value={inputValue(data, field.key)} onChange={event => updateDetail(field.key, event.target.value)} required={field.required}/ >}{errors[`details.${field.key}`] && <small className="form-error">{errors[`details.${field.key}`]}</small>}</label>)}
                        <label>Preferred repayment tenure <small>Optional where the product permits flexible repayment</small><select value={data.preferred_tenure} onChange={event => update('preferred_tenure', event.target.value)}><option value="">Not sure yet</option><option>1–3 years</option><option>3–5 years</option><option>5+ years</option></select></label>
                    </div>
                    <p className="source-disclosure">Share broad estimates only. Do not include account numbers, identity numbers or document scans.</p>
                </>}
                {step === 3 && <>
                    <h2>How can we<br/><em>reach you?</em></h2>
                    <div className="input-grid">
                        <label>Full name<input autoComplete="name" placeholder="Your name" value={data.customer_name} onChange={event => update('customer_name', event.target.value)} required/>{errors.customer_name && <small className="form-error">{errors.customer_name}</small>}</label>
                        <label>Mobile number<input autoComplete="tel-national" placeholder="10-digit mobile number" inputMode="tel" maxLength={10} pattern="[6-9][0-9]{9}" title="Enter a valid 10-digit Indian mobile number" value={data.mobile_number} onChange={event => update('mobile_number', event.target.value)} required/>{errors.mobile_number && <small className="form-error">{errors.mobile_number}</small>}</label>
                        <label>Email <small>Optional</small><input type="email" autoComplete="email" placeholder="you@example.com" inputMode="email" value={data.email} onChange={event => update('email', event.target.value)}/>{errors.email && <small className="form-error">{errors.email}</small>}</label>
                        <label>Best time to call<select value={data.preferred_callback_time} onChange={event => update('preferred_callback_time', event.target.value)} required><option value="">Choose a time</option><option>Morning</option><option>Afternoon</option><option>Evening</option></select>{errors.preferred_callback_time && <small className="form-error">{errors.preferred_callback_time}</small>}</label>
                    </div>
                </>}
                {step === 4 && <>
                    <h2>Review your<br/><em>enquiry details.</em></h2>
                    <div className="review-summary"><p><span>Loan type</span><b>{product?.title ?? 'Loan assistance'}</b></p><p><span>Requested amount</span><b>{data.requested_amount ? `₹${Number(data.requested_amount).toLocaleString('en-IN')}` : 'Not provided'}</b></p><p><span>City</span><b>{data.city}</b></p><p><span>Preferred tenure</span><b>{data.preferred_tenure || 'Not selected'}</b></p><p><span>Contact</span><b>{data.customer_name} · {data.mobile_number}</b></p></div>
                    <label className="consent"><input type="checkbox" checked={data.contact_consent} onChange={event => update('contact_consent', event.target.checked)} required/> I consent to {companyName} using these details to respond to my enquiry. See the <Link href="/privacy-policy">Privacy Policy</Link>.</label>
                    <label className="consent"><input type="checkbox" checked={data.marketing_consent} onChange={event => update('marketing_consent', event.target.checked)}/> I would like to receive optional updates. (Optional)</label>
                    {errors.contact_consent && <small className="form-error">Consent is required so we can respond to this enquiry.</small>}
                    <div className="preview-banner">Your enquiry will be stored securely and used to respond to your request. You can choose separately whether to receive optional updates.</div>
                </>}
                <input className="honeypot" type="text" name="website" tabIndex={-1} autoComplete="off" aria-hidden="true" value={data.website} onChange={event => update('website', event.target.value)}/>
                {message && <div className="form-alert" role="alert">{message}</div>}
                <div className="form-actions">
                    {step > 1 && <button type="button" className="button button-outline" onClick={() => { clearErrors(); setMessage(''); setStep(step - 1); }}>← Back</button>}
                    <button type="button" className="button" onClick={continueStep} disabled={processing || (step === 1 && !product)}>{processing ? 'Sending…' : step === 4 ? 'Send enquiry' : 'Continue'} <span>→</span></button>
                </div>
            </form>
        </section>
    </>;
}
