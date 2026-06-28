import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { route as routeFn } from 'ziggy-js';
import { initializeTheme } from './hooks/use-appearance';
import i18n from './lib/i18n/i18n';
import { preloadAssets } from './lib/preload-assets';

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

// Register service worker for PWA + push notifications.
// CRITICAL: also detect when a new SW takes control and reload ONCE, so a deploy
// is picked up immediately instead of being pinned to the old cached bundle until
// every tab is closed (the "I deployed but see nothing" problem).
if ('serviceWorker' in navigator) {
    let reloading = false;
    navigator.serviceWorker.addEventListener('controllerchange', () => {
        if (reloading) return;
        reloading = true;
        window.location.reload();
    });

    window.addEventListener('load', () => {
        // Warm the cache with sounds/animations/icons so they're ready when needed.
        preloadAssets();
        navigator.serviceWorker.register('/sw.js', { scope: '/' }).then((reg) => {
            // Poll for updates on load and periodically.
            reg.update().catch(() => {});
            setInterval(() => reg.update().catch(() => {}), 60 * 60 * 1000);

            reg.addEventListener('updatefound', () => {
                const newWorker = reg.installing;
                if (!newWorker) return;
                newWorker.addEventListener('statechange', () => {
                    // A new worker is installed AND an old one already controls the
                    // page → a fresh deploy is ready. skipWaiting() in sw.js will
                    // activate it, triggering controllerchange → reload above.
                    if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                        newWorker.postMessage?.({ type: 'SKIP_WAITING' });
                    }
                });
            });
        }).catch(() => {});
    });
}
