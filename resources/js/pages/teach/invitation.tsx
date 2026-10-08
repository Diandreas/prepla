import { Head, useForm } from '@inertiajs/react';
import AuthLayout from '@/layouts/auth-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/input-error';

interface InvitationDetails {
    name: string;
    email: string;
    space: string | null;
    classroom: string | null;
    joins_existing: boolean;
}

/**
 * Accepter une invitation d'enseignant.
 *
 * La personne arrive par un lien à usage unique : son nom et son adresse sont déjà
 * connus, elle n'a qu'à choisir son mot de passe. Personne d'autre ne l'aura vu, et
 * son espace plus sa première classe existent dès la validation.
 */
export default function AcceptInvitation({ token, invitation }: { token: string; invitation: InvitationDetails | null }) {
    const form = useForm({ password: '', password_confirmation: '', phone: '' });

    function submit(event: React.FormEvent) {
        event.preventDefault();
        form.post(route('teach.invitation.accept', token), {
            onFinish: () => form.reset('password', 'password_confirmation'),
        });
    }

    if (!invitation) {
        return (
            <AuthLayout title="Invitation expirée" description="Ce lien n'est plus valable.">
                <Head title="Invitation expirée" />
                <p className="text-sm text-muted-foreground">
                    Ce lien d'invitation a déjà été utilisé, ou il a dépassé ses deux semaines de validité.
                    Demande-en un nouveau à la personne qui t'a invité.
                </p>
            </AuthLayout>
        );
    }

    return (
        <AuthLayout
            title={`Bienvenue, ${invitation.name.split(' ')[0]}`}
            description={invitation.joins_existing
                ? `Tu rejoins l'espace ${invitation.space} comme professeur.`
                : 'Choisis ton mot de passe, ton espace est déjà prêt.'}
        >
            <Head title="Rejoindre PrePla" />

            <form onSubmit={submit} className="flex flex-col gap-5">
                <div className="rounded-xl border border-border bg-muted/40 p-3 text-sm">
                    <p className="font-semibold">{invitation.name}</p>
                    <p className="text-muted-foreground">{invitation.email}</p>
                    {invitation.classroom && (
                        <p className="mt-2 text-xs text-muted-foreground">
                            {invitation.joins_existing ? 'Classe' : 'Ton espace et ta première classe'} : {invitation.space ? `${invitation.space} · ` : ''}{invitation.classroom}
                        </p>
                    )}
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="phone">Numéro de téléphone</Label>
                    <Input
                        id="phone"
                        type="tel"
                        required
                        autoFocus
                        autoComplete="tel"
                        value={form.data.phone}
                        onChange={(event) => form.setData('phone', event.target.value)}
                        disabled={form.processing}
                        placeholder="+237 6 00 00 00 00"
                    />
                    <p className="text-xs text-muted-foreground">Pour te joindre si ton espace a un souci. Jamais partagé.</p>
                    <InputError message={form.errors.phone} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="password">Choisis ton mot de passe</Label>
                    <Input
                        id="password"
                        type="password"
                        required
                        autoComplete="new-password"
                        value={form.data.password}
                        onChange={(event) => form.setData('password', event.target.value)}
                        disabled={form.processing}
                    />
                    <p className="text-xs text-muted-foreground">Personne d'autre ne le connaîtra, pas même nous.</p>
                    <InputError message={form.errors.password} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="password_confirmation">Confirme ton mot de passe</Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        required
                        autoComplete="new-password"
                        value={form.data.password_confirmation}
                        onChange={(event) => form.setData('password_confirmation', event.target.value)}
                        disabled={form.processing}
                    />
                    <InputError message={form.errors.password_confirmation} />
                </div>

                <Button type="submit" className="w-full" disabled={form.processing}>
                    {form.processing ? 'Création…' : 'Entrer dans mon espace'}
                </Button>
            </form>
        </AuthLayout>
    );
}
