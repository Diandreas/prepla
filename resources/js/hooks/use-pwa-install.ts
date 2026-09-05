import { useEffect, useState } from 'react';

interface BeforeInstallPromptEvent extends Event {
    prompt: () => Promise<void>;
    userChoice: Promise<{ outcome: 'accepted' | 'dismissed' }>;
}

const DISMISSED_KEY = 'prepla-pwa-dismissed';
const DISMISS_COOLDOWN_MS = 7 * 24 * 60 * 60 * 1000;

function isRunningStandalone() {
    if (typeof window === 'undefined') return false;

    return window.matchMedia('(display-mode: standalone)').matches
        || window.matchMedia('(display-mode: fullscreen)').matches
        || (navigator as Navigator & { standalone?: boolean }).standalone === true;
}

export function usePwaInstall() {
    const [installPrompt, setInstallPrompt] = useState<BeforeInstallPromptEvent | null>(null);
    const [isInstallable, setIsInstallable] = useState(false);
    const [isInstalled, setIsInstalled] = useState(false);
    const [isDismissed, setIsDismissed] = useState(false);

    useEffect(() => {
        // Already running as PWA
        if (isRunningStandalone()) {
            setIsInstalled(true);
            return;
        }

        // A dismissal is temporary: users can be invited again after a week.
        try {
            const dismissedAt = Number(localStorage.getItem(DISMISSED_KEY));
            if (dismissedAt && Date.now() - dismissedAt < DISMISS_COOLDOWN_MS) setIsDismissed(true);
            else localStorage.removeItem(DISMISSED_KEY);
        } catch {
            // Storage may be unavailable in privacy mode; installation still works.
        }

        const handler = (e: Event) => {
            e.preventDefault();
            setInstallPrompt(e as BeforeInstallPromptEvent);
            setIsInstallable(true);
        };

        const handleInstalled = () => {
            setIsInstalled(true);
            setIsInstallable(false);
            setInstallPrompt(null);
        };

        window.addEventListener('beforeinstallprompt', handler);
        window.addEventListener('appinstalled', handleInstalled);

        return () => {
            window.removeEventListener('beforeinstallprompt', handler);
            window.removeEventListener('appinstalled', handleInstalled);
        };
    }, []);

    const install = async () => {
        if (!installPrompt) return;
        await installPrompt.prompt();
        const { outcome } = await installPrompt.userChoice;
        if (outcome === 'accepted') {
            setIsInstalled(true);
            setIsInstallable(false);
        } else {
            dismiss();
        }
        setInstallPrompt(null);
    };

    const dismiss = () => {
        try {
            localStorage.setItem(DISMISSED_KEY, String(Date.now()));
        } catch {
            // Keep the in-memory dismissal even when storage is unavailable.
        }
        setIsDismissed(true);
        setInstallPrompt(null);
    };

    // Show popup if: installable AND not dismissed AND not already installed
    const shouldShow = isInstallable && !isDismissed && !isInstalled;

    // iOS detection (Safari doesn't fire beforeinstallprompt)
    const isIos = typeof navigator !== 'undefined' && /iphone|ipad|ipod/.test(navigator.userAgent.toLowerCase());
    const isInStandaloneMode = isRunningStandalone();
    const showIosPrompt = isIos && !isInStandaloneMode && !isDismissed;

    return { shouldShow, showIosPrompt, install, dismiss, isInstalled };
}
