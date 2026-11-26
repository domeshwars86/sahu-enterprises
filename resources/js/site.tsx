import React from 'react';
import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';
import '../css/app.css';
import '../css/admin.css';
import '../css/admin-auth.css';
import '../css/admin-followups.css';
import '../css/admin-staff.css';
import '../css/admin-products.css';
import '../css/admin-catalogue.css';
import '../css/admin-tasks.css';
import '../css/admin-applications.css';
import '../css/admin-customers.css';
import '../css/admin-news.css';
import '../css/admin-reports.css';
import '../css/admin-audit.css';
import '../css/admin-partners.css';
import '../css/admin-content.css';

createInertiaApp({
    resolve: async (name) => {
        const pages = import.meta.glob<React.ComponentType>('./pages/**/*.tsx', { import: 'default' });
        const page = pages[`./pages/${name}.tsx`];
        if (!page) throw new Error(`Unknown Inertia page: ${name}`);
        return page();
    },
    setup({ el, App, props }) {
        if (!el) return;
        createRoot(el).render(<App {...props} />);
    },
});
