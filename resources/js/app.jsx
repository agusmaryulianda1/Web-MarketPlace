import '../css/app.css';

import AosProvider from './Components/AosProvider';
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';

createInertiaApp({
    title: (title) => (title ? `${title} - ${import.meta.env.VITE_APP_NAME ?? 'MarketPlace'}` : 'MarketPlace'),
    resolve: (name) => resolvePageComponent(`./Pages/${name}.jsx`, import.meta.glob('./Pages/**/*.jsx')),
    setup({ el, App, props }) {
        createRoot(el).render(<AosProvider><App {...props} /></AosProvider>);
    },
});