import InputError from '@/components/input-error';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Transition } from '@headlessui/react';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler, useRef } from 'react';
import { ChevronLeft, Lock } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Profil',
        href: '/settings/profile',
    },
    {
        title: 'Sécurité',
        href: '/settings/password',
    },
];

export default function Password() {
    const passwordInput = useRef<HTMLInputElement>(null);
    const currentPasswordInput = useRef<HTMLInputElement>(null);

    const { data, setData, errors, put, reset, processing, recentlySuccessful } = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const updatePassword: FormEventHandler = (e) => {
        e.preventDefault();

        put(route('password.update'), {
            preserveScroll: true,
            onSuccess: () => reset(),
            onError: (errors) => {
                if (errors.password) {
                    reset('password', 'password_confirmation');
                    passwordInput.current?.focus();
                }

                if (errors.current_password) {
                    reset('current_password');
                    currentPasswordInput.current?.focus();
                }
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Sécurité" />

            <div className="mx-auto max-w-2xl space-y-6 py-6 pb-24 md:py-10 px-4">
                {/* Mobile Back Button */}
                <div className="md:hidden mb-6">
                    <Link href={route('profile.edit')} className="inline-flex items-center text-sm font-medium text-muted-foreground hover:text-foreground">
                        <ChevronLeft className="mr-1 h-4 w-4" />
                        Retour au profil
                    </Link>
                </div>

                <div className="flex items-center gap-3 mb-6">
                    <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary">
                        <Lock size={20} />
                    </div>
                    <div>
                        <h2 className="text-xl font-black">Sécurité & Mot de passe</h2>
                        <p className="text-sm text-muted-foreground">Sécurisez votre compte avec un mot de passe fort</p>
                    </div>
                </div>

                <div className="overflow-hidden rounded-2xl border bg-card shadow-sm p-4 md:p-6">
                    <form onSubmit={updatePassword} className="space-y-6">
                        <div className="grid gap-2">
                            <Label htmlFor="current_password">Mot de passe actuel</Label>
                            <Input
                                id="current_password"
                                ref={currentPasswordInput}
                                value={data.current_password}
                                onChange={(e) => setData('current_password', e.target.value)}
                                type="password"
                                className="mt-1 block w-full bg-muted/30 border-none h-11"
                                autoComplete="current-password"
                                placeholder="Entrez votre mot de passe actuel"
                            />
                            <InputError message={errors.current_password} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password">Nouveau mot de passe</Label>
                            <Input
                                id="password"
                                ref={passwordInput}
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                type="password"
                                className="mt-1 block w-full bg-muted/30 border-none h-11"
                                autoComplete="new-password"
                                placeholder="Nouveau mot de passe"
                            />
                            <InputError message={errors.password} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password_confirmation">Confirmer le mot de passe</Label>
                            <Input
                                id="password_confirmation"
                                value={data.password_confirmation}
                                onChange={(e) => setData('password_confirmation', e.target.value)}
                                type="password"
                                className="mt-1 block w-full bg-muted/30 border-none h-11"
                                autoComplete="new-password"
                                placeholder="Répétez le nouveau mot de passe"
                            />
                            <InputError message={errors.password_confirmation} />
                        </div>

                        <div className="pt-2">
                            <Button disabled={processing} className="w-full font-bold shadow-md h-11">
                                Enregistrer le mot de passe
                            </Button>
                        </div>

                        <Transition
                            show={recentlySuccessful}
                            enter="transition ease-in-out duration-300"
                            enterFrom="opacity-0 translate-y-2"
                            enterTo="opacity-100 translate-y-0"
                            leave="transition ease-in-out duration-300"
                            leaveFrom="opacity-100"
                            leaveTo="opacity-0"
                        >
                            <div className="rounded-xl bg-emerald-50 border border-emerald-200 p-3 text-center text-sm font-medium text-emerald-600 dark:bg-emerald-950/30">
                                Votre mot de passe a été mis à jour avec succès.
                            </div>
                        </Transition>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}
