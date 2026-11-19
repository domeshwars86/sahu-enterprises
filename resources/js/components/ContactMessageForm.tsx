import { useState, type FormEvent } from 'react';
import { Link, useHttp } from '@inertiajs/react';

type ContactData = {
    idempotency_key: string;
    name: string;
    mobile_number: string;
    email: string;
    subject: string;
    message: string;
    privacy_consent: boolean;
    website: string;
};

type ContactResponse = { message: string; reference_number: string };

function initialData(): ContactData {
    return {
        idempotency_key: crypto.randomUUID(),
        name: '',
        mobile_number: '',
        email: '',
        subject: 'loan_assistance',
        message: '',
        privacy_consent: false,
        website: '',
    };
}

export default function ContactMessageForm() {
    const [referenceNumber, setReferenceNumber] = useState('');
    const [status, setStatus] = useState('');
    const { data, setData, post, processing, errors } = useHttp<ContactData, ContactResponse>('post', '/contact', initialData());

    const update = <K extends keyof ContactData>(field: K, value: ContactData[K]) => setData(previous => ({ ...previous, [field]: value }));

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        setStatus('');

        post('/contact', {
            onSuccess: response => {
                setReferenceNumber(response.reference_number);
                setStatus(response.message);
            },
            onError: () => setStatus('Please check the highlighted details and try again.'),
            onNetworkError: () => setStatus('We could not send your message. Please try again when your connection is available.'),
        });
    }

    if (referenceNumber) {
        return <div className="contact-success" role="status">
            <span className="eyebrow">Message received</span>
            <h3>Thank you for reaching out.</h3>
            <p>{status}</p>
            <p className="reference-number">Reference <strong>{referenceNumber}</strong></p>
        </div>;
    }

    return <form className="contact-form" onSubmit={submit}>
        <div className="input-grid">
            <label htmlFor="contact-name">Your name
                <input id="contact-name" autoComplete="name" maxLength={120} required value={data.name} onChange={event => update('name', event.target.value)} />
                {errors.name && <small role="alert">{errors.name}</small>}
            </label>
            <label htmlFor="contact-mobile">Mobile number
                <input id="contact-mobile" autoComplete="tel-national" inputMode="numeric" maxLength={10} pattern="[6-9][0-9]{9}" placeholder="10-digit Indian mobile number" required value={data.mobile_number} onChange={event => update('mobile_number', event.target.value.replace(/\D/g, '').slice(0, 10))} />
                {errors.mobile_number && <small role="alert">{errors.mobile_number}</small>}
            </label>
            <label htmlFor="contact-email">Email <small>Optional</small>
                <input id="contact-email" type="email" autoComplete="email" maxLength={254} value={data.email} onChange={event => update('email', event.target.value)} />
                {errors.email && <small role="alert">{errors.email}</small>}
            </label>
            <label htmlFor="contact-subject">What can we help with?
                <select id="contact-subject" required value={data.subject} onChange={event => update('subject', event.target.value)}>
                    <option value="loan_assistance">Loan assistance</option>
                    <option value="callback">Request a callback</option>
                    <option value="other">Something else</option>
                </select>
                {errors.subject && <small role="alert">{errors.subject}</small>}
            </label>
            <label className="contact-message-field" htmlFor="contact-message">Your message
                <textarea id="contact-message" maxLength={3000} rows={5} required value={data.message} onChange={event => update('message', event.target.value)} />
                {errors.message && <small role="alert">{errors.message}</small>}
            </label>
        </div>
        <label className="consent"><input type="checkbox" required checked={data.privacy_consent} onChange={event => update('privacy_consent', event.target.checked)} /> <span>I agree that Sahu Enterprises may use these details to respond to my message under its <Link href="/privacy-policy">privacy notice</Link>.</span></label>
        {errors.privacy_consent && <p className="form-error" role="alert">{errors.privacy_consent}</p>}
        <div className="honeypot" aria-hidden="true"><label htmlFor="contact-website">Leave this field empty<input id="contact-website" autoComplete="off" tabIndex={-1} value={data.website} onChange={event => update('website', event.target.value)} /></label></div>
        {status && <p className="form-error" role="status">{status}</p>}
        <button className="button" type="submit" disabled={processing}>{processing ? 'Sending…' : 'Send message'} <span>↗</span></button>
    </form>;
}
