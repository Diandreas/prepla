import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { route as routeFn } from 'ziggy-js';
import { initializeTheme } from './hooks/use-appearance';
import i18n from './lib/i18n/i18n';

declare global {
    const route: typeof routeFn;
}

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx')),
    setup({ el, App, props }) {
        const root = createRoot(el);
        
        // Initialize i18n language from user profile if authenticated
        const userLanguage = (props.initialPage.props.auth as any)?.user?.profile?.interface_language;
        if (userLanguage) {
            i18n.changeLanguage(userLanguage);
        }

        root.render(<App {...props} />);
    },
    progress: {
        // Visible top loading bar (brand blue) so navigation feels responsive.
        color: '#4A90E2',
        showSpinner: false,
        delay: 120,
    },
});

// This will set light / dark mode on load...
initializeTheme();

// Register service worker for PWA + push notifications
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(() => {});
    });
}
