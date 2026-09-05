import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { route as routeFn } from 'ziggy-js';
import { PwaUpdatePrompt } from './components/pwa-update-prompt';
import { initializeTheme } from './hooks/use-appearance';
import i18n from './lib/i18n/i18n';
import { preloadAssets } from './lib/preload-assets';

declare global {
    const route: typeof routeFn;
}

type InitialPageProps = {
    auth?: {
        user?: {
            profile?: { interface_language?: string };
        };
    };
};

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx')),
    setup({ el, App, props }) {
        const root = createRoot(el);
        
        // Initialize i18n language from user profile if authenticated
        const userLanguage = (props.initialPage.props as InitialPageProps).auth?.user?.profile?.interface_language;
        if (userLanguage) {
            i18n.changeLanguage(userLanguage);
        }

        root.render(
            <>
                <App {...props} />
                <PwaUpdatePrompt />
            </>,
        );
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

// Register the service worker for PWA assets and push notifications. Updates are
// offered to the user instead of force-reloading: a deploy must never interrupt
// an exercise or a timed exam.
if ('serviceWorker' in navigator) {
    const UPDATE_REQUESTED_KEY = 'prepla-pwa-update-requested';
    let reloading = false;

    navigator.serviceWorker.addEventListener('controllerchange', () => {
        if (reloading || sessionStorage.getItem(UPDATE_REQUESTED_KEY) !== '1') return;
        reloading = true;
        sessionStorage.removeItem(UPDATE_REQUESTED_KEY);
        window.location.reload();
    });

    window.addEventListener('load', () => {
        // Warm the cache with sounds/animations/icons so they're ready when needed.
        preloadAssets();
        navigator.serviceWorker.register('/sw.js', { scope: '/' }).then((reg) => {
            const announceUpdate = () => {
                window.dispatchEvent(new CustomEvent('prepla:pwa-update-ready', { detail: reg }));
            };

            // An update may already be waiting when the page is opened.
            if (reg.waiting && navigator.serviceWorker.controller) announceUpdate();

            // Poll for updates on load and periodically.
            reg.update().catch(() => {});
            setInterval(() => reg.update().catch(() => {}), 60 * 60 * 1000);

            reg.addEventListener('updatefound', () => {
                const newWorker = reg.installing;
                if (!newWorker) return;
                newWorker.addEventListener('statechange', () => {
                    // A fresh deploy is ready. Let the user decide when it is safe
                    // to activate it rather than disrupting their current work.
                    if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                        announceUpdate();
                    }
                });
            });
        }).catch(() => {});
    });
}
