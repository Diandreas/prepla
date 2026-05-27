import { useEffect, useState } from 'react';

interface BeforeInstallPromptEvent extends Event {
    prompt: () => Promise<void>;
    userChoice: Promise<{ outcome: 'accepted' | 'dismissed' }>;
}

const DISMISSED_KEY = 'prepla-pwa-dismissed';

export function usePwaInstall() {
    const [installPrompt, setInstallPrompt] = useState<BeforeInstallPromptEvent | null>(null);
    const [isInstallable, setIsInstallable] = useState(false);
    const [isInstalled, setIsInstalled] = useState(false);
    const [isDismissed, setIsDismissed] = useState(false);

    useEffect(() => {
        // Already running as PWA
        if (window.matchMedia('(display-mode: standalone)').matches) {
            setIsInstalled(true);
            return;
        }

        // User previously dismissed
        const dismissed = localStorage.getItem(DISMISSED_KEY);
        if (dismissed) {
            setIsDismissed(true);
        }

        const handler = (e: Event) => {
            e.preventDefault();
            setInstallPrompt(e as BeforeInstallPromptEvent);
            setIsInstallable(true);
        };

        window.addEventListener('beforeinstallprompt', handler);
        window.addEventListener('appinstalled', () => setIsInstalled(true));

        return () => window.removeEventListener('beforeinstallprompt', handler);
    }, []);

    const install = async () => {
        if (!installPrompt) return;
        await installPrompt.prompt();
        const { outcome } = await installPrompt.userChoice;
        if (outcome === 'accepted') {
            setIsInstalled(true);
            setIsInstallable(false);
        }
        setInstallPrompt(null);
    };

    const dismiss = () => {
        localStorage.setItem(DISMISSED_KEY, '1');
        setIsDismissed(true);
    };

    // Show popup if: installable AND not dismissed AND not already installed
    const shouldShow = isInstallable && !isDismissed && !isInstalled;

    // iOS detection (Safari doesn't fire beforeinstallprompt)
    const isIos = /iphone|ipad|ipod/.test(navigator.userAgent.toLowerCase());
    const isInStandaloneMode = window.matchMedia('(display-mode: standalone)').matches;
    const showIosPrompt = isIos && !isInStandaloneMode && !isDismissed;

    return { shouldShow, showIosPrompt, install, dismiss, isInstalled };
}
