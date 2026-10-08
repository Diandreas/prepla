import { useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';

/**
 * Demander son numéro à qui est entré par Google.
 *
 * Ce chemin ne demande qu'un clic : personne n'a saisi de numéro, et sans numéro on
 * ne peut joindre personne pour recueillir un retour. La question est posée une
 * seule fois — le serveur retient qu'elle l'a été, qu'on réponde ou qu'on décline —
 * et elle ne bloque rien : qui veut travailler ferme et travaille.
 */
export function PhonePrompt() {
    const needsPhone = usePage().props.needsPhone as boolean | undefined;
    const [closed, setClosed] = useState(false);
    const form = useForm({ phone: '' });

    if (!needsPhone || closed) return null;

    function submit(event: React.FormEvent) {
        event.preventDefault();
        form.post(route('phone.store'), { preserveScroll: true, onSuccess: () => setClosed(true) });
    }

    function later() {
        setClosed(true);
        form.post(route('phone.dismiss'), { preserveScroll: true });
    }

    return (
        <div role="dialog" aria-modal="true" aria-labelledby="phone-prompt-title" className="fixed inset-0 z-[120] flex items-end justify-center bg-black/40 p-0 backdrop-blur-sm sm:items-center sm:p-4">
            <div className="w-full max-w-sm rounded-t-2xl border border-border bg-card p-5 shadow-2xl sm:rounded-2xl">
                <h2 id="phone-prompt-title" className="text-base font-bold">Ton numéro de téléphone</h2>
                <p className="mt-1.5 text-sm leading-relaxed text-muted-foreground">
                    Pour te joindre si ton compte a un souci, et te demander ton avis sur PrePla. Jamais partagé, et tu peux refuser.
                </p>

                <form onSubmit={submit} className="mt-4 space-y-3">
                    <input
                        autoFocus
                        type="tel"
                        autoComplete="tel"
                        inputMode="tel"
                        aria-label="Numéro de téléphone"
                        placeholder="+237 6 00 00 00 00"
                        className="w-full rounded-lg border border-border bg-background px-3 py-2.5 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                        value={form.data.phone}
                        onChange={(event) => form.setData('phone', event.target.value)}
                        maxLength={32}
                    />
                    {form.errors.phone && <p className="text-xs text-rose-500">{form.errors.phone}</p>}

                    <div className="flex gap-2">
                        <Button type="button" variant="ghost" className="flex-1" onClick={later} disabled={form.processing}>
                            Plus tard
                        </Button>
                        <Button type="submit" className="flex-1" disabled={form.processing || form.data.phone.trim().length < 6}>
                            {form.processing ? 'Envoi…' : 'Enregistrer'}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    );
}
