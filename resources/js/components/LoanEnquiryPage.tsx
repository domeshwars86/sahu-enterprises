import { useRef, useState } from 'react';
import { Link, useHttp } from '@inertiajs/react';

type EnquiryData = {
    idempotency_key: string;
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
    customer_notes: string;
    preferred_callback_time: string;
    contact_consent: boolean;
    marketing_consent: boolean;
    website: string;
};

type EnquiryResponse = { message: string; reference_number: string };

const loanOptions = [
    { label: 'Car loan', category: 'new_car', vehicle: 'car', condition: 'new' },
    { label: 'Used vehicle loan', category: 'used_car', vehicle: 'car', condition: 'used' },
    { label: 'Two-wheeler loan', category: 'two_wheeler', vehicle: 'two_wheeler', condition: 'new' },
    { label: 'Commercial vehicle', category: 'commercial_vehicle', vehicle: 'commercial_vehicle', condition: 'new' },
    { label: 'Vehicle refinance', category: 'vehicle_refinance', vehicle: 'car', condition: 'used' },
];

function initialData(): EnquiryData {
    return {
        idempotency_key: crypto.randomUUID(),
        customer_name: '',
        mobile_number: '',
        email: '',
        city: '',
        loan_category: 'new_car',
        requested_amount: '',
        preferred_tenure: '',
        employment_category: '',
        vehicle_type: 'car',
        vehicle_condition: 'new',
        vehicle_price: '',
        down_payment: '',
        customer_notes: '',
        preferred_callback_time: '',
        contact_consent: false,
        marketing_consent: false,
        website: '',
    };
}

export default function LoanEnquiryPage() {
    const formRef = useRef<HTMLFormElement>(null);
    const [step, setStep] = useState(1);
    const [loan, setLoan] = useState(loanOptions[0]);
    const [referenceNumber, setReferenceNumber] = useState('');
    const [message, setMessage] = useState('');
    const { data, setData, post, processing, errors, clearErrors } = useHttp<EnquiryData, EnquiryResponse>('post', '/apply', initialData());

    const update = <K extends keyof EnquiryData>(field: K, value: EnquiryData[K]) => setData(previous => ({ ...previous, [field]: value }));

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
        return <section className="section form-section"><div className="form-card form-success" role="status"><span className="preview-icon">✓</span><span className="eyebrow">Enquiry received</span><h1>Thank you for contacting <em>Sahu Enterprises.</em></h1><p>{message}</p><div className="reference-number"><span>Your reference number</span><strong>{referenceNumber}</strong></div><p className="source-disclosure">A lending institution assesses every application and determines any offer and terms. Loan approval is not guaranteed.</p><Link className="button" href="/">Back to home</Link></div></section>;
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
                        {loanOptions.map(option => <button key={option.category} type="button" className={loan.category === option.category ? 'selected' : ''} aria-pressed={loan.category === option.category} onClick={() => { setLoan(option); update('loan_category', option.category); update('vehicle_type', option.vehicle); update('vehicle_condition', option.condition); }}><span>{loan.category === option.category ? '✓' : '↗'}</span>{option.label}</button>)}
                    </div>
                </>}
                {step === 2 && <>
                    <h2>Tell us a little<br/><em>about your plans.</em></h2>
                    <div className="input-grid">
                        <label>Approximate loan amount<input type="number" min="1" inputMode="numeric" placeholder="e.g. ₹8,00,000" value={data.requested_amount} onChange={event => update('requested_amount', event.target.value)} required/>{errors.requested_amount && <small className="form-error">{errors.requested_amount}</small>}</label>
                        <label>City<input placeholder="Your city" value={data.city} onChange={event => update('city', event.target.value)} required/>{errors.city && <small className="form-error">{errors.city}</small>}</label>
                        <label>Vehicle type<select value={data.vehicle_type} onChange={event => update('vehicle_type', event.target.value)} required><option value="car">Car</option><option value="two_wheeler">Two-wheeler</option><option value="commercial_vehicle">Commercial vehicle</option></select></label>
                        <label>Vehicle condition<select value={data.vehicle_condition} onChange={event => update('vehicle_condition', event.target.value)} required><option value="new">New</option><option value="used">Pre-owned</option></select></label>
                        <label>Vehicle price <small>Optional</small><input type="number" min="1" inputMode="numeric" placeholder="Approximate price" value={data.vehicle_price} onChange={event => update('vehicle_price', event.target.value)}/></label>
                        <label>Down payment <small>Optional</small><input type="number" min="0" inputMode="numeric" placeholder="Approximate down payment" value={data.down_payment} onChange={event => update('down_payment', event.target.value)}/></label>
                        <label>Preferred tenure<select value={data.preferred_tenure} onChange={event => update('preferred_tenure', event.target.value)} required><option value="">Choose one</option><option>1–3 years</option><option>3–5 years</option><option>5+ years</option></select></label>
                        <label>Employment category <small>Optional</small><select value={data.employment_category} onChange={event => update('employment_category', event.target.value)}><option value="">Choose one</option><option value="salaried">Salaried</option><option value="self_employed">Self-employed</option><option value="business_owner">Business owner</option><option value="other">Other</option></select></label>
                    </div>
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
                    <div className="review-summary"><p><span>Loan type</span><b>{loan.label}</b></p><p><span>Requested amount</span><b>{data.requested_amount ? `₹${Number(data.requested_amount).toLocaleString('en-IN')}` : 'Not provided'}</b></p><p><span>City</span><b>{data.city}</b></p><p><span>Preferred tenure</span><b>{data.preferred_tenure}</b></p><p><span>Contact</span><b>{data.customer_name} · {data.mobile_number}</b></p></div>
                    <label className="consent"><input type="checkbox" checked={data.contact_consent} onChange={event => update('contact_consent', event.target.checked)} required/> I consent to Sahu Enterprises using these details to respond to my enquiry. See the <Link href="/privacy-policy">Privacy Policy</Link>.</label>
                    <label className="consent"><input type="checkbox" checked={data.marketing_consent} onChange={event => update('marketing_consent', event.target.checked)}/> I would like to receive optional updates. (Optional)</label>
                    {errors.contact_consent && <small className="form-error">Consent is required so we can respond to this enquiry.</small>}
                    <div className="preview-banner">Your enquiry will be stored securely and used to respond to your request. You can choose separately whether to receive optional updates.</div>
                </>}
                <input className="honeypot" type="text" name="website" tabIndex={-1} autoComplete="off" aria-hidden="true" value={data.website} onChange={event => update('website', event.target.value)}/>
                {message && <div className="form-alert" role="alert">{message}</div>}
                <div className="form-actions">
                    {step > 1 && <button type="button" className="button button-outline" onClick={() => { clearErrors(); setMessage(''); setStep(step - 1); }}>← Back</button>}
                    <button type="button" className="button" onClick={continueStep} disabled={processing}>{processing ? 'Sending…' : step === 4 ? 'Send enquiry' : 'Continue'} <span>→</span></button>
                </div>
            </form>
        </section>
    </>;
}
