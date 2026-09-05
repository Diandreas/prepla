import { RefreshCw, X } from 'lucide-react';
import { useEffect, useState } from 'react';

const UPDATE_REQUESTED_KEY = 'prepla-pwa-update-requested';

export function PwaUpdatePrompt() {
    const [registration, setRegistration] = useState<ServiceWorkerRegistration | null>(null);
    const [isApplying, setIsApplying] = useState(false);

    useEffect(() => {
        if (!('serviceWorker' in navigator)) return;

        const handleUpdate = (event: Event) => {
            const registration = (event as CustomEvent<ServiceWorkerRegistration>).detail;
            if (registration?.waiting) setRegistration(registration);
        };

        window.addEventListener('prepla:pwa-update-ready', handleUpdate);
        navigator.serviceWorker.getRegistration().then((current) => {
            if (current?.waiting && navigator.serviceWorker.controller) setRegistration(current);
        }).catch(() => {});

        return () => window.removeEventListener('prepla:pwa-update-ready', handleUpdate);
    }, []);

    const applyUpdate = () => {
        if (!registration?.waiting) return;
        setIsApplying(true);
        sessionStorage.setItem(UPDATE_REQUESTED_KEY, '1');
        registration.waiting.postMessage({ type: 'SKIP_WAITING' });
    };

    if (!registration) return null;

    return (
        <aside
            className="fixed bottom-[calc(5.5rem+env(safe-area-inset-bottom))] left-4 right-4 z-[70] md:bottom-6 md:left-auto md:right-6 md:w-[22rem]"
            role="status"
            aria-live="polite"
        >
            <div className="overflow-hidden rounded-2xl border border-blue-200/70 bg-background shadow-2xl dark:border-blue-900">
                <div className="flex items-start gap-3 p-4">
                    <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-300">
                        <RefreshCw className={isApplying ? 'h-5 w-5 animate-spin' : 'h-5 w-5'} />
                    </span>
                    <div className="min-w-0 flex-1">
                        <p className="text-sm font-bold">Mise à jour disponible</p>
                        <p className="mt-0.5 text-xs leading-relaxed text-muted-foreground">
                            Installez la nouvelle version lorsque vous avez terminé votre exercice.
                        </p>
                    </div>
                    <button
                        type="button"
                        onClick={() => setRegistration(null)}
                        className="rounded-lg p-1 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                        aria-label="Plus tard"
                        disabled={isApplying}
                    >
                        <X className="h-4 w-4" />
                    </button>
                </div>
                <button
                    type="button"
                    onClick={applyUpdate}
                    disabled={isApplying}
                    className="w-full border-t border-border bg-blue-600 px-4 py-2.5 text-sm font-bold text-white transition-colors hover:bg-blue-700 disabled:cursor-wait disabled:opacity-70"
                >
                    {isApplying ? 'Mise à jour…' : 'Mettre à jour maintenant'}
                </button>
            </div>
        </aside>
    );
}
