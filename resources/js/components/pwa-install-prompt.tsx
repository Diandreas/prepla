import { usePwaInstall } from '@/hooks/use-pwa-install';

export function PwaInstallPrompt() {
    const { shouldShow, showIosPrompt, install, dismiss } = usePwaInstall();

    if (!shouldShow && !showIosPrompt) return null;

    return (
        <div className="fixed bottom-20 left-4 right-4 z-50 md:bottom-6 md:left-auto md:right-6 md:w-80">
            <div className="rounded-2xl border border-border bg-background shadow-2xl overflow-hidden">
                <div className="bg-gradient-to-r from-amber-500/10 to-transparent px-4 py-3 flex items-center gap-3">
                    <img src="/icons/pwa-192.png" alt="PrePla" width={36} height={36} className="rounded-lg" />
                    <div className="flex-1 min-w-0">
                        <p className="text-sm font-bold leading-tight">Installer PrePla</p>
                        <p className="text-xs text-muted-foreground">Accès rapide depuis votre écran d'accueil</p>
                    </div>
                    <button
                        onClick={dismiss}
                        className="text-muted-foreground hover:text-foreground text-lg leading-none p-1"
                        aria-label="Fermer"
                    >
                        ×
                    </button>
                </div>

                <div className="px-4 py-3 space-y-2">
                    {showIosPrompt ? (
                        <div className="text-xs text-muted-foreground space-y-1">
                            <p>Pour installer sur iOS :</p>
                            <ol className="list-decimal list-inside space-y-1">
                                <li>Appuyez sur <span className="font-semibold">Partager</span> <span className="text-base">⬆</span> dans Safari</li>
                                <li>Choisissez <span className="font-semibold">"Sur l'écran d'accueil"</span></li>
                            </ol>
                        </div>
                    ) : (
                        <div className="flex gap-2">
                            <button
                                onClick={dismiss}
                                className="flex-1 rounded-xl border border-border py-2 text-sm font-medium text-muted-foreground hover:bg-muted transition-colors"
                            >
                                Plus tard
                            </button>
                            <button
                                onClick={install}
                                className="flex-1 rounded-xl bg-amber-400 py-2 text-sm font-bold text-amber-950 hover:bg-amber-500 transition-colors"
                            >
                                Installer
                            </button>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
