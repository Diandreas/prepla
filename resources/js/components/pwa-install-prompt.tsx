import { usePwaInstall } from '@/hooks/use-pwa-install';
import { Download, Share, X } from 'lucide-react';

export function PwaInstallPrompt() {
    const { shouldShow, showIosPrompt, install, dismiss } = usePwaInstall();

    if (!shouldShow && !showIosPrompt) return null;

    return (
        <aside
            className="fixed bottom-[calc(5.5rem+env(safe-area-inset-bottom))] left-4 right-4 z-50 md:bottom-6 md:left-auto md:right-6 md:w-80"
            role="dialog"
            aria-label="Installer PrePla"
        >
            <div className="overflow-hidden rounded-2xl border border-border bg-background shadow-2xl">
                <div className="bg-gradient-to-r from-amber-500/10 to-transparent px-4 py-3 flex items-center gap-3">
                    <img src="/icons/pwa-192-v4.png" alt="" width={40} height={40} className="rounded-xl" />
                    <div className="flex-1 min-w-0">
                        <p className="text-sm font-bold leading-tight">Installer PrePla</p>
                        <p className="text-xs text-muted-foreground">Plein écran, accès rapide et rappels de pratique</p>
                    </div>
                    <button
                        type="button"
                        onClick={dismiss}
                        className="rounded-lg p-1 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                        aria-label="Fermer"
                    >
                        <X className="h-4 w-4" />
                    </button>
                </div>

                <div className="px-4 py-3 space-y-2">
                    {showIosPrompt ? (
                        <div className="text-xs text-muted-foreground space-y-1">
                            <p>Pour installer sur iOS :</p>
                            <ol className="list-decimal list-inside space-y-1">
                                <li>Appuyez sur <span className="font-semibold">Partager</span> <Share className="inline h-3.5 w-3.5" /> dans Safari</li>
                                <li>Choisissez <span className="font-semibold">"Sur l'écran d'accueil"</span></li>
                            </ol>
                        </div>
                    ) : (
                        <div className="flex gap-2">
                            <button
                                type="button"
                                onClick={dismiss}
                                className="flex-1 rounded-xl border border-border py-2 text-sm font-medium text-muted-foreground hover:bg-muted transition-colors"
                            >
                                Plus tard
                            </button>
                            <button
                                type="button"
                                onClick={install}
                                className="flex flex-1 items-center justify-center gap-1.5 rounded-xl bg-amber-400 py-2 text-sm font-bold text-amber-950 transition-colors hover:bg-amber-500"
                            >
                                <Download className="h-4 w-4" />
                                Installer
                            </button>
                        </div>
                    )}
                </div>
            </div>
        </aside>
    );
}
