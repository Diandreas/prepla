import { SUPPORT_PHONE_DISPLAY, SUPPORT_TEL_URL, SUPPORT_WHATSAPP_URL } from '@/lib/contact';
import { useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';

export function WhatsAppGlyph({ size = 20 }: { size?: number }) {
    return (
        <svg width={size} height={size} viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347M12.05 21.785h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413" />
        </svg>
    );
}

export function PhoneGlyph({ size = 20 }: { size?: number }) {
    return (
        <svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.36 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.34 1.85.573 2.81.7A2 2 0 0 1 22 16.92" />
        </svg>
    );
}

/**
 * Bouton d'aide flottant, présent sur toutes les pages une fois connecté.
 * Il ouvre le support WhatsApp ou lance un appel classique sur le même numéro.
 * Positionné au-dessus de la barre d'onglets mobile pour ne rien recouvrir.
 */
export function SupportFab() {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);
    const rootRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (!open) return;

        const onPointerDown = (e: MouseEvent | TouchEvent) => {
            if (rootRef.current && !rootRef.current.contains(e.target as Node)) setOpen(false);
        };
        const onKeyDown = (e: KeyboardEvent) => {
            if (e.key === 'Escape') setOpen(false);
        };

        document.addEventListener('mousedown', onPointerDown);
        document.addEventListener('touchstart', onPointerDown);
        document.addEventListener('keydown', onKeyDown);
        return () => {
            document.removeEventListener('mousedown', onPointerDown);
            document.removeEventListener('touchstart', onPointerDown);
            document.removeEventListener('keydown', onKeyDown);
        };
    }, [open]);

    const actionClass =
        'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-foreground transition-colors hover:bg-accent';

    return (
        <div
            ref={rootRef}
            className="fixed right-4 bottom-[84px] z-50 flex flex-col items-end gap-2 md:right-6 md:bottom-6"
            style={{ paddingBottom: 'env(safe-area-inset-bottom)' }}
        >
            {open && (
                <div className="w-60 overflow-hidden rounded-2xl border border-border bg-popover p-1.5 shadow-xl">
                    <p className="px-3 pt-2 pb-1.5 text-[11px] font-bold tracking-wide text-muted-foreground uppercase">
                        {t('support.title', 'Aide & support')}
                    </p>

                    <a
                        href={SUPPORT_WHATSAPP_URL}
                        target="_blank"
                        rel="noopener noreferrer"
                        className={actionClass}
                        onClick={() => setOpen(false)}
                    >
                        <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#25D366]/15 text-[#25D366]">
                            <WhatsAppGlyph size={17} />
                        </span>
                        <span className="flex flex-col leading-tight">
                            <span>{t('support.whatsapp', 'WhatsApp')}</span>
                            <span className="text-[11px] font-medium text-muted-foreground">{SUPPORT_PHONE_DISPLAY}</span>
                        </span>
                    </a>

                    <a href={SUPPORT_TEL_URL} className={actionClass} onClick={() => setOpen(false)}>
                        <span className="bg-primary/10 text-primary flex h-8 w-8 shrink-0 items-center justify-center rounded-lg">
                            <PhoneGlyph size={16} />
                        </span>
                        <span className="flex flex-col leading-tight">
                            <span>{t('support.call', 'Appeler')}</span>
                            <span className="text-[11px] font-medium text-muted-foreground">{SUPPORT_PHONE_DISPLAY}</span>
                        </span>
                    </a>
                </div>
            )}

            <button
                type="button"
                onClick={() => setOpen((v) => !v)}
                aria-expanded={open}
                aria-label={t('support.title', 'Aide & support')}
                className="flex h-12 w-12 items-center justify-center rounded-full text-white shadow-lg transition-transform duration-200 active:scale-90"
                style={{ background: open ? '#128C7E' : '#25D366', boxShadow: '0 8px 22px rgba(37,211,102,0.4)' }}
            >
                {open ? (
                    <svg width={20} height={20} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2.5} strokeLinecap="round" aria-hidden="true">
                        <path d="M18 6 6 18M6 6l12 12" />
                    </svg>
                ) : (
                    <WhatsAppGlyph size={24} />
                )}
            </button>
        </div>
    );
}
