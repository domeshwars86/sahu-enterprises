# Frontend progress

## Complete

- Inertia React and strict TypeScript public site shell with responsive navigation, skip link, reduced-motion support, shared footer and lender disclosures.
- Home, loan catalog, five core vehicle finance services, calculator, scenario comparison, enquiry, finance news categories, news detail, resources/guides, about, contact, FAQs, and legal route experiences.
- Customer-focused vehicle finance showcase, six customer benefits, six-step financing journey, accessible loan mega menu, and real contact form UI.
- Refined the visual system with a blue-teal fintech palette, stronger responsive typography, elevated loan cards, clearer calculator results, and a homepage vehicle-type selector that routes to the selected loan experience.
- Interactive EMI estimates with editable values, month/year tenure, reset, principal/interest visualization, amortization schedule and down-payment/LTV tools; scenario comparison; client-side multi-step enquiry with required/optional consent and server-backed submission.
- PIB headline list and details wired to Laravel props. External headlines display publisher, category, date and direct source links only; local development fixtures are clearly labeled.
- Production Vite build, strict TypeScript check, and browser tests covering key flows and requested viewport widths.
- Added official financial resource directory and RBI education video library with click-to-load privacy-enhanced embeds.
- Added calculator-library routes for personal, home, business, car, two-wheeler and secured fixed-term scenarios, balance-transfer savings, down-payment, tenure comparison and amortization. The annual rate field is capped at 40%.
- Public loan cards and verified contact fields are driven by server-side catalogue/business config.

## Remaining before launch

- Verify actual company contact, service availability, geographic scope, grievance contact, registration information and approved legal wording with the business.
- Complete legal/privacy review and production content review.
- Provide or select a licensed commercial news API if broader publisher coverage is required. RBI requires written permission before this site caches or links to its internal pages.
- Configure and verify production SSR only if the chosen hosting environment will support the Node SSR runtime; client-rendered Inertia/Vite is currently used.
