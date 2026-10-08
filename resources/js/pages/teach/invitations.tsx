import { Head, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/input-error';

interface InvitationRow {
    id: number;
    name: string;
    email: string;
    space: string | null;
    accepted_at: string | null;
    expires_at: string;
    usable: boolean;
}

interface ExamOption { id: number; name: string; language?: string | null }

/**
 * Inviter un enseignant. Le lien généré est affiché une seule fois : la base n'en
 * garde qu'une empreinte, on ne peut donc pas le retrouver ensuite.
 */
export default function Invitations({ invitations, ownCenter, exams }: {
    invitations: InvitationRow[];
    ownCenter?: { id: number; name: string } | null;
    exams: ExamOption[];
}) {
    const flash = usePage().props.flash as { invitationLink?: string } | undefined;
    const [copied, setCopied] = useState(false);
    const form = useForm({ name: '', email: '', exam_id: '', space_name: '', classroom_name: 'Première classe', level: '' });

    function submit(event: React.FormEvent) {
        event.preventDefault();
        form.post(route('teach.invitations.store'), {
            preserveScroll: true,
            onSuccess: () => { form.reset('name', 'email'); setCopied(false); },
        });
    }

    async function copy(link: string) {
        try {
            await navigator.clipboard.writeText(link);
            setCopied(true);
        } catch {
            setCopied(false);
        }
    }

    return (
        <AppLayout>
            <Head title="Inviter un enseignant" />
            <div className="mx-auto w-full max-w-2xl space-y-5 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Inviter un enseignant</h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        {ownCenter
                            ? `Il rejoindra ${ownCenter.name} comme professeur.`
                            : 'Son espace et sa première classe seront créés à son arrivée.'}
                        {' '}Il choisira son mot de passe lui-même.
                    </p>
                </div>

                {flash?.invitationLink && (
                    <Card className="border-emerald-300 dark:border-emerald-800">
                        <CardHeader>
                            <CardTitle className="text-base">Lien à transmettre</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <p className="break-all rounded-lg bg-muted p-3 font-mono text-xs">{flash.invitationLink}</p>
                            <div className="flex items-center gap-3">
                                <Button type="button" onClick={() => copy(flash.invitationLink!)}>
                                    {copied ? 'Copié' : 'Copier le lien'}
                                </Button>
                                <p className="text-xs text-muted-foreground">
                                    Valable 14 jours, utilisable une seule fois. Note-le maintenant : il ne sera plus affiché.
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Nouvelle invitation</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-4">
                            <div className="grid gap-2">
                                <Label htmlFor="inv-name">Nom de l'enseignant</Label>
                                <Input id="inv-name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} disabled={form.processing} placeholder="Zidane Mbarga" />
                                <InputError message={form.errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="inv-email">Son adresse e-mail</Label>
                                <Input id="inv-email" type="email" value={form.data.email} onChange={(e) => form.setData('email', e.target.value.toLowerCase())} disabled={form.processing} placeholder="prof@exemple.com" />
                                <InputError message={form.errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="inv-exam">Examen préparé</Label>
                                <select
                                    id="inv-exam"
                                    className="w-full rounded-lg border border-border bg-background px-3 py-2.5 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                    value={form.data.exam_id}
                                    onChange={(e) => form.setData('exam_id', e.target.value)}
                                    disabled={form.processing}
                                >
                                    <option value="">Il choisira lui-même</option>
                                    {exams.map((exam) => (
                                        <option key={exam.id} value={exam.id}>{exam.language ? `${exam.language} — ${exam.name}` : exam.name}</option>
                                    ))}
                                </select>
                                <InputError message={form.errors.exam_id} />
                            </div>

                            {!ownCenter && (
                                <div className="grid gap-2">
                                    <Label htmlFor="inv-space">Nom de son espace</Label>
                                    <Input id="inv-space" value={form.data.space_name} onChange={(e) => form.setData('space_name', e.target.value)} disabled={form.processing} placeholder="Laissé vide : son nom + « cours de langue »" />
                                    <InputError message={form.errors.space_name} />
                                </div>
                            )}

                            <div className="grid gap-4 sm:grid-cols-[1fr_7rem]">
                                <div className="grid gap-2">
                                    <Label htmlFor="inv-class">Sa première classe</Label>
                                    <Input id="inv-class" value={form.data.classroom_name} onChange={(e) => form.setData('classroom_name', e.target.value)} disabled={form.processing} />
                                    <InputError message={form.errors.classroom_name} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="inv-level">Niveau</Label>
                                    <Input id="inv-level" value={form.data.level} onChange={(e) => form.setData('level', e.target.value.toUpperCase())} disabled={form.processing} placeholder="A2" />
                                </div>
                            </div>

                            <Button type="submit" className="w-full" disabled={form.processing || !form.data.name.trim() || !form.data.email.trim()}>
                                {form.processing ? 'Création…' : 'Générer le lien'}
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                {invitations.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Invitations envoyées</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            {invitations.map((invitation) => (
                                <div key={invitation.id} className="flex items-center justify-between gap-3 rounded-lg border border-border/70 p-3 text-sm">
                                    <div className="min-w-0">
                                        <p className="truncate font-semibold">{invitation.name}</p>
                                        <p className="truncate text-xs text-muted-foreground">{invitation.email}{invitation.space ? ` · ${invitation.space}` : ''}</p>
                                    </div>
                                    <span className={`shrink-0 rounded-full px-2.5 py-1 text-xs font-bold ${invitation.accepted_at ? 'bg-emerald-100 text-emerald-700' : invitation.usable ? 'bg-amber-100 text-amber-700' : 'bg-muted text-muted-foreground'}`}>
                                        {invitation.accepted_at ? `Entré le ${invitation.accepted_at}` : invitation.usable ? `Valable jusqu'au ${invitation.expires_at}` : 'Expirée'}
                                    </span>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
