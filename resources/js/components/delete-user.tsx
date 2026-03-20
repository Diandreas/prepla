import { Link, useForm } from '@inertiajs/react';
import { FormEventHandler, useRef } from 'react';

// Components...
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

import HeadingSmall from '@/components/heading-small';

import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger } from '@/components/ui/dialog';

export default function DeleteUser() {
    const passwordInput = useRef<HTMLInputElement>(null);
    const { data, setData, delete: destroy, processing, reset, errors, clearErrors } = useForm({ password: '' });

    const deleteUser: FormEventHandler = (e) => {
        e.preventDefault();

        destroy(route('profile.destroy'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
            onError: () => passwordInput.current?.focus(),
            onFinish: () => reset(),
        });
    };

    const closeModal = () => {
        clearErrors();
        reset();
    };

    return (
        <div className="p-4 space-y-4">
            <div className="flex items-center gap-3 text-red-600">
                <div className="flex h-9 w-9 items-center justify-center rounded-xl bg-red-100 dark:bg-red-900/30">
                    <img src="/icons/trash.png" alt="" width={18} height={18} style={{ objectFit: 'contain' }} />
                </div>
                <div>
                    <p className="text-sm font-bold">Zone de danger</p>
                    <p className="text-xs text-muted-foreground opacity-80">Action irréversible</p>
                </div>
            </div>

            <Dialog>
                <DialogTrigger asChild>
                    <Button variant="destructive" className="w-full font-bold">Supprimer mon compte</Button>
                </DialogTrigger>
                <DialogContent>
                    <DialogTitle>Êtes-vous sûr de vouloir supprimer votre compte ?</DialogTitle>
                    <DialogDescription>
                        Cette action est définitive. Toutes vos données de progression, XP et accomplissements seront supprimés à jamais.
                    </DialogDescription>
                    <form className="space-y-6" onSubmit={deleteUser}>
                        <div className="grid gap-2">
                            <Label htmlFor="password">Mot de passe</Label>

                            <Input
                                id="password"
                                type="password"
                                name="password"
                                ref={passwordInput}
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                placeholder="Confirmez avec votre mot de passe"
                                autoComplete="current-password"
                            />

                            <InputError message={errors.password} />
                        </div>

                        <DialogFooter className="gap-2 sm:gap-0">
                            <DialogClose asChild>
                                <Button variant="secondary" onClick={closeModal} className="font-bold">
                                    Annuler
                                </Button>
                            </DialogClose>

                            <Button variant="destructive" disabled={processing} type="submit" className="font-bold">
                                Supprimer définitivement
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    );
}
