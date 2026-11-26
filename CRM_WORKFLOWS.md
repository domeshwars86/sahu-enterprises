# CRM workflows

## Enquiries

Public applications continue to write to `loan_enquiries`. CRM views and the existing `enquiries:list`, `enquiries:show`, and `enquiries:status` commands use those same rows. Customer name, phone, email, city, employment, and customer message remain encrypted at rest. HMAC fingerprints support exact-match search without storing plaintext search copies. Reference search supports partial matching.

New enquiries start at `new`. Supported manual statuses are `new`, `contact_attempted`, `contacted`, `qualified`, `documents_pending`, `ready_for_submission`, `submitted_to_lender`, `under_lender_review`, `approved`, `rejected`, `disbursed`, `on_hold`, `closed`, and `duplicate`. Each changed status records the old value, new value, actor, time, and optional reason. No lender submission or approval is performed automatically.

Staff with assignment rights can assign a lead and set its priority. Authorized staff can add encrypted internal notes and schedule callbacks. Follow-ups may be marked complete or cancelled by authorized staff. Agents and support staff only see their assigned records.

## Applications and other modules

Staff can explicitly create a loan application from an enquiry once it reaches an eligible workflow stage. Application stage changes are audited and require a reason for submitted, approved, rejected, and disbursed outcomes; no lender submission or automated approval occurs. Tasks can be assigned, linked to enquiries, and moved through their workflow. Customer profiles show scoped enquiry, note, status-change and follow-up history. Authorized staff can update contact preferences and record privacy request type and time; retention, response and fulfilment must follow the business-approved policy. Identities are not automatically merged.

Product visibility and featured state, partner records, and sourced news visibility/featured state can be managed in the admin workspace. The partner directory is internal, and there is no lender API or verified offer feed. Content staff can create/edit/publish plain-text education articles, FAQs, and approved YouTube links; published articles appear in `/resources`, FAQs on `/faq`, and videos on `/videos`. Business settings can update verified public contact details without exposing environment secrets. New-enquiry alerts and due follow-up/task reminders are in-app database notifications; the scheduler runs `crm:notify-due` once per minute and marks each item to prevent repeat alerts. Email/SMS reminder delivery is not implemented.

## Privacy and reporting

Roles without `enquiries.view_sensitive` receive masked contact values. Reports support date-filtered enquiry status/category and application-stage counts and trends. Authorized staff can export a CSV that excludes customer names and contact details, escapes spreadsheet formula content, and records an audit event. Keep access to the database and backups restricted and follow the business's approved retention and privacy process.
