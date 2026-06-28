import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

export default function Join({ alreadyInCenter }: { alreadyInCenter: boolean }) {
    const form = useForm({ code: '' });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.post(route('center.join.store'));
    }

    return (
        <AppLayout>
            <Head title="Rejoindre un centre" />
            <div className="mx-auto w-full max-w-md space-y-5 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Rejoindre votre centre</h1>
                    <p className="text-sm text-muted-foreground">
                        Saisissez le code d'invitation fourni par votre établissement.
                    </p>
                </div>

                {alreadyInCenter ? (
                    <Card>
                        <CardContent className="p-4 text-sm text-muted-foreground">
                            Vous appartenez déjà à un centre. Contactez votre établissement pour changer.
                        </CardContent>
                    </Card>
                ) : (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Code d'invitation</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={submit} className="space-y-4">
                                <input
                                    autoFocus
                                    className="w-full rounded-lg border border-border bg-background px-3 py-2.5 text-center text-lg font-bold uppercase tracking-widest focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                    placeholder="ABC12345"
                                    value={form.data.code}
                                    onChange={(e) => form.setData('code', e.target.value.toUpperCase())}
                                    maxLength={12}
                                />
                                {form.errors.code && <p className="text-xs text-rose-500">{form.errors.code}</p>}
                                <Button type="submit" disabled={form.processing || !form.data.code.trim()} className="w-full">
                                    {form.processing ? 'Vérification…' : 'Rejoindre'}
                                </Button>
                            </form>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
