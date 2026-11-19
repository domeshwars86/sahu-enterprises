import React from 'react';
import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';
import '../css/app.css';

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
