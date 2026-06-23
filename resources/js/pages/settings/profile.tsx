import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import * as Flags from 'country-flag-icons/react/3x2';

function flagEmojiToCode(flag: string): string {
    const points = [...flag].map(c => c.codePointAt(0)! - 0x1F1E6);
    if (points.length === 2 && points[0] >= 0 && points[0] <= 25) {
        return String.fromCharCode(65 + points[0], 65 + points[1]);
    }
    return '';
}

function FlagImg({ flag, size = 18 }: { flag: string; size?: number }) {
    const code = flagEmojiToCode(flag);
    const FlagComponent = code ? (Flags as Record<string, React.ComponentType<{ style?: React.CSSProperties }>>)[code] : null;
    if (FlagComponent) return <FlagComponent style={{ width: size, borderRadius: 2, display: 'inline-block', verticalAlign: 'middle' }} />;
    return <span>{flag}</span>;
}

import DeleteUser from '@/components/delete-user';
import PushNotificationToggle from '@/components/push-notification-toggle';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import AppLayout from '@/layouts/app-layout';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/hooks/use-initials';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Badge } from '@/components/ui/badge';

// Custom icon component using icons from /public/icons
function CustomIcon({ name, className, style }: { name: string; className?: string; style?: React.CSSProperties }) {
    return (
        <img
            src={`/icons/${name}.png`}
            alt={name}
            className={className || 'h-5 w-5'}
            style={{ objectFit: 'contain', ...style }}
        />
    );
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Mon Profil',
        href: '/settings/profile',
    },
];

interface Exam {
    id: number;
    name: string;
    language: {
        name: string;
        flag: string;
    };
}

interface ProfileProps {
    mustVerifyEmail: boolean;
    status?: string;
    profile: any;
    exams: Exam[];
}

export default function Profile({ mustVerifyEmail, status, profile, exams }: ProfileProps) {
    const { auth } = usePage<SharedData>().props;
    const getInitials = useInitials();
    const [isEditingInfo, setIsEditingInfo] = useState(false);
    const [showExamChangeDialog, setShowExamChangeDialog] = useState(false);
    const pendingExamId = useRef<string | null>(null);
    const { t, i18n } = useTranslation();

    // Profile Info Form
    const infoForm = useForm({
        name: auth.user.name,
        email: auth.user.email,
    });

    // Learning Settings Form
    const learningForm = useForm({
        target_exam_id: profile?.target_exam_id?.toString() ?? '',
        target_score: profile?.target_score ?? '',
        exam_date: profile?.exam_date ? new Date(profile.exam_date).toISOString().split('T')[0] : '',
        current_level: profile?.current_level ?? 'A1',
        interface_language: profile?.interface_language ?? 'fr',
    });

    const submitInfo: FormEventHandler = (e) => {
        e.preventDefault();
        infoForm.patch(route('profile.update'), {
            onSuccess: () => setIsEditingInfo(false)
        });
    };

    const submitLearning = () => {
        learningForm.patch(route('profile.update_learning'), {
            onSuccess: () => {
                i18n.changeLanguage(learningForm.data.interface_language);
            }
        });
    };

    const handleExamChange = (val: string) => {
        const currentExamId = profile?.target_exam_id?.toString() ?? '';
        if (currentExamId && val !== currentExamId) {
            pendingExamId.current = val;
            setShowExamChangeDialog(true);
        } else {
            learningForm.setData('target_exam_id', val);
        }
    };

    const confirmExamChange = () => {
        if (pendingExamId.current) {
            learningForm.setData('target_exam_id', pendingExamId.current);
            pendingExamId.current = null;
        }
        setShowExamChangeDialog(false);
    };

    const cancelExamChange = () => {
        pendingExamId.current = null;
        setShowExamChangeDialog(false);
    };

    const sectionTitleStyle = "text-[11px] font-bold uppercase tracking-[0.1em] text-muted-foreground mb-2 px-1";
    const itemStyle = "flex items-center justify-between w-full px-3.5 py-3 text-left transition-colors active:bg-muted/50 group";
    const itemIconBg = "flex h-9 w-9 items-center justify-center rounded-xl mr-3";

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('profile.title', 'Profil')} />

            <div className="mx-auto max-w-2xl space-y-4 py-5 pb-24 md:py-8 px-4">
                {/* 1. User Profile Header — compact row (XP/streak already shown in top bar) */}
                <div className="flex items-center gap-4 px-1">
                    <div className="relative shrink-0">
                        <Avatar className="h-16 w-16 border-2 border-background shadow-md">
                            <AvatarImage src={auth.user.avatar} />
                            <AvatarFallback className="text-xl font-bold bg-primary text-primary-foreground">
                                {getInitials(auth.user.name)}
                            </AvatarFallback>
                        </Avatar>
                    </div>
                    <div className="min-w-0">
                        <h2 className="text-lg font-black tracking-tight truncate">{auth.user.name}</h2>
                        <p className="text-xs text-muted-foreground truncate">{auth.user.email}</p>
                        <div className="mt-1 flex items-center gap-3 text-xs font-black">
                            <span className="flex items-center gap-1 text-primary">
                                <CustomIcon name="zap" className="h-3.5 w-3.5" style={{ filter: 'brightness(0) saturate(100%) invert(84%) sepia(40%) saturate(1734%) hue-rotate(353deg) brightness(94%) contrast(86%)' }} />
                                {profile?.xp_total ?? 0}
                            </span>
                            <span className="flex items-center gap-1 text-orange-500">
                                <img src="/animation/Fire.gif" alt="Série" className="h-4 w-4 object-contain" />
                                {profile?.streak_current ?? 0}
                            </span>
                        </div>
                    </div>
                </div>

                {/* 2. Subscription — compact row */}
                <Link
                    href={route('subscription.index')}
                    className="flex items-center justify-between gap-3 rounded-2xl p-3.5 text-white"
                    style={{ background: 'linear-gradient(135deg, #1A2B48, #2a3f6a)' }}
                >
                    <div className="flex items-center gap-2.5 min-w-0">
                        <CustomIcon name="credit-card" className="h-5 w-5 shrink-0 opacity-70" style={{ filter: 'brightness(0) saturate(100%) invert(100%)' }} />
                        <div className="min-w-0">
                            <p className="text-sm font-black truncate">{profile?.plan === 'premium' ? 'PrePla Plus' : 'PrePla Standard'}</p>
                            <p className="text-[10px] font-bold opacity-70">{profile?.plan === 'premium' ? 'Premium actif' : 'Passe au Premium'}</p>
                        </div>
                    </div>
                    <span className="shrink-0 rounded-lg bg-white/15 px-3 py-1.5 text-[11px] font-black">Gérer</span>
                </Link>

                {/* 3. Learning Settings */}
                <div>
                    <h3 className={sectionTitleStyle}>{t('profile.study_settings', 'Paramètres d\'étude')}</h3>
                    <div className="overflow-hidden rounded-2xl border bg-card shadow-sm divide-y">
                        {/* Target Exam */}
                        <div className="p-4 space-y-4">
                            <div className="flex items-center mb-1">
                                <div className={itemIconBg + " bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-300"}>
                                    <CustomIcon name="book" className="h-4 w-4" />
                                </div>
                                <div className="flex-1">
                                    <p className="text-sm font-bold">{t('profile.prepared_exam', 'Examen préparé')}</p>
                                    <p className="text-xs text-muted-foreground">{t('profile.select_goal', 'Sélectionnez votre objectif')}</p>
                                </div>
                            </div>
                            <Select
                                value={learningForm.data.target_exam_id}
                                onValueChange={handleExamChange}
                            >
                                <SelectTrigger className="w-full bg-muted/30 border-none h-11 font-bold">
                                    <SelectValue placeholder={t('profile.choose_exam', 'Choisir un examen')} />
                                </SelectTrigger>
                                <SelectContent>
                                    {exams.map(exam => (
                                        <SelectItem key={exam.id} value={exam.id.toString()}>
                                            <span className="inline-flex items-center gap-1.5"><FlagImg flag={exam.language.flag} size={16} /> {exam.name}</span>
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        {/* Level & Goal */}
                        <div className="p-4 grid grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label className="text-[10px] font-bold uppercase tracking-wider text-muted-foreground ml-1">{t('profile.current_level', 'Niveau actuel')}</Label>
                                <Select
                                    value={learningForm.data.current_level}
                                    onValueChange={(val) => learningForm.setData('current_level', val)}
                                >
                                    <SelectTrigger className="bg-muted/30 border-none h-11 font-bold">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {['A1', 'A2', 'B1', 'B2', 'C1', 'C2'].map(lv => (
                                            <SelectItem key={lv} value={lv}>{lv}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-2">
                                <Label className="text-[10px] font-bold uppercase tracking-wider text-muted-foreground ml-1">{t('profile.target_score', 'Score visé')}</Label>
                                <Input
                                    type="number"
                                    step="0.1"
                                    className="bg-muted/30 border-none h-11 font-bold text-center"
                                    value={learningForm.data.target_score}
                                    onChange={e => learningForm.setData('target_score', e.target.value)}
                                />
                            </div>
                        </div>

                        {/* Exam Date */}
                        <div className="p-4 flex items-center justify-between">
                            <div className="flex items-center">
                                <div className={itemIconBg + " bg-purple-100 dark:bg-purple-900/40 text-purple-600 dark:text-purple-300"}>
                                    <CustomIcon name="calendar" className="h-4 w-4" />
                                </div>
                                <div>
                                    <p className="text-sm font-bold">{t('profile.exam_date', 'Date de l\'examen')}</p>
                                    <p className="text-xs text-muted-foreground">{t('profile.exam_day', 'Jour J')}</p>
                                </div>
                            </div>
                            <Input
                                type="date"
                                className="w-auto bg-muted/30 border-none font-bold"
                                value={learningForm.data.exam_date}
                                onChange={e => learningForm.setData('exam_date', e.target.value)}
                            />
                        </div>

                        {/* Interface Language */}
                        <div className="p-4 flex items-center justify-between">
                            <div className="flex items-center">
                                <div className={itemIconBg + " bg-orange-100 dark:bg-orange-900/40 text-orange-600 dark:text-orange-300"}>
                                    <CustomIcon name="languages" className="h-4 w-4" />
                                </div>
                                <div>
                                    <p className="text-sm font-bold">{t('profile.interface_language', 'Langue de l\'interface')}</p>
                                    <p className="text-xs text-muted-foreground">{t('profile.menu_instructions', 'Menu et consignes')}</p>
                                </div>
                            </div>
                            <Select
                                value={learningForm.data.interface_language}
                                onValueChange={(val) => learningForm.setData('interface_language', val)}
                            >
                                <SelectTrigger className="w-auto min-w-[120px] bg-muted/30 border-none h-11 font-bold">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="fr">🇫🇷 Français</SelectItem>
                                    <SelectItem value="en">🇬🇧 English</SelectItem>
                                    <SelectItem value="de">🇩🇪 Deutsch</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="p-4 bg-muted/5 text-center">
                            <Button
                                onClick={submitLearning}
                                disabled={learningForm.processing}
                                className="w-full font-bold shadow-md rounded-xl"
                            >
                                {learningForm.recentlySuccessful ? t('common.saved', 'Enregistré !') : t('profile.save_settings', 'Sauvegarder les paramètres')}
                            </Button>
                        </div>
                    </div>
                </div>

                {/* 4. Personal Information */}
                <div>
                    <h3 className={sectionTitleStyle}>{t('profile.personal_info', 'Informations Personnelles')}</h3>
                    <div className="overflow-hidden rounded-2xl border bg-card shadow-sm divide-y">
                        <button
                            onClick={() => setIsEditingInfo(!isEditingInfo)}
                            className={itemStyle}
                        >
                            <div className="flex items-center">
                                <div className={itemIconBg + " bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-300"}>
                                    <CustomIcon name="user" className="h-4 w-4" />
                                </div>
                                <div>
                                    <p className="text-sm font-bold">{auth.user.name}</p>
                                    <p className="text-xs text-muted-foreground">{t('profile.full_name', 'Nom complet')}</p>
                                </div>
                            </div>
                            <CustomIcon name="chevron-right" className="h-4 w-4 text-muted-foreground opacity-50" />
                        </button>

                        {isEditingInfo && (
                            <form onSubmit={submitInfo} className="p-4 space-y-4 bg-muted/5 animate-in slide-in-from-top-1 duration-200">
                                <div className="grid gap-2">
                                    <Label htmlFor="name">{t('profile.name', 'Nom')}</Label>
                                    <Input id="name" value={infoForm.data.name} onChange={e => infoForm.setData('name', e.target.value)} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="email">{t('profile.email', 'Email')}</Label>
                                    <Input id="email" type="email" value={infoForm.data.email} onChange={e => infoForm.setData('email', e.target.value)} />
                                </div>
                                <div className="flex justify-end gap-2">
                                    <Button type="button" variant="ghost" size="sm" onClick={() => setIsEditingInfo(false)}>{t('common.cancel', 'Annuler')}</Button>
                                    <Button type="submit" size="sm" disabled={infoForm.processing}>{t('common.save', 'Enregistrer')}</Button>
                                </div>
                            </form>
                        )}

                        <div className={itemStyle}>
                            <div className="flex items-center">
                                <div className={itemIconBg + " bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-300"}>
                                    <CustomIcon name="shield" className="h-4 w-4" />
                                </div>
                                <div>
                                    <p className="text-sm font-bold">{t('profile.verified_account', 'Compte Vérifié')}</p>
                                    <p className="text-xs text-muted-foreground">{t('profile.security_status', 'Statut de sécurité')}</p>
                                </div>
                            </div>
                            <Badge variant="outline" className="text-[10px] text-emerald-600 border-emerald-200 bg-emerald-50 font-bold">{t('profile.active', 'ACTIF')}</Badge>
                        </div>
                    </div>
                </div>

                {/* 5. App Settings */}
                <div>
                    <h3 className={sectionTitleStyle}>{t('profile.app_settings', 'Application')}</h3>
                    <div className="overflow-hidden rounded-2xl border bg-card shadow-sm divide-y">
                        <Link href={route('appearance')} className={itemStyle}>
                            <div className="flex items-center">
                                <div className={itemIconBg + " bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-300"}>
                                    <CustomIcon name="moon" className="h-4 w-4" />
                                </div>
                                <p className="text-sm font-bold">{t('profile.appearance', 'Apparence')}</p>
                            </div>
                            <CustomIcon name="chevron-right" className="h-4 w-4 text-muted-foreground opacity-50" />
                        </Link>
                        <Link href={route('password.edit')} className={itemStyle}>
                            <div className="flex items-center">
                                <div className={itemIconBg + " bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-300"}>
                                    <CustomIcon name="lock" className="h-4 w-4" />
                                </div>
                                <p className="text-sm font-bold">{t('profile.security_password', 'Sécurité & Mot de passe')}</p>
                            </div>
                            <CustomIcon name="chevron-right" className="h-4 w-4 text-muted-foreground opacity-50" />
                        </Link>
                        <Link href={route('logout')} method="post" as="button" className={itemStyle}>
                            <div className="flex items-center text-red-600">
                                <div className={itemIconBg + " bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-300"}>
                                    <CustomIcon name="log-out" className="h-4 w-4" />
                                </div>
                                <p className="text-sm font-bold">{t('profile.logout', 'Déconnexion')}</p>
                            </div>
                        </Link>
                    </div>
                </div>

                {/* 6. Notifications */}
                <div>
                    <PushNotificationToggle />
                </div>

                {/* 7. Danger Zone */}
                <div className="px-4 opacity-70">
                    <div className="overflow-hidden rounded-2xl border border-red-200 bg-red-50/50 shadow-sm">
                        <DeleteUser />
                    </div>
                </div>

                <p className="text-center text-[10px] text-muted-foreground uppercase tracking-widest font-black opacity-40">
                    PrePla v1.2.0 • {t('profile.made_with', 'Made with')} ❤️ {t('profile.for_success', 'for success')}
                </p>
            </div>

            <AlertDialog open={showExamChangeDialog} onOpenChange={setShowExamChangeDialog}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle className="text-xl font-black">Changer d'examen ?</AlertDialogTitle>
                        <AlertDialogDescription className="space-y-3 text-sm leading-relaxed">
                            <span className="block">
                                Changer d'examen va <strong>effacer toute votre progression actuelle</strong> (parcours, nœuds complétés, leçons générées) et créer un tout nouveau programme adapté au nouvel examen.
                            </span>
                            <span className="block text-muted-foreground">
                                Vos XP, votre série et votre historique d'exercices seront conservés.
                            </span>
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel onClick={cancelExamChange}>
                            Annuler
                        </AlertDialogCancel>
                        <AlertDialogAction
                            onClick={confirmExamChange}
                            className="bg-red-500 hover:bg-red-600 text-white font-bold"
                        >
                            Oui, changer et recommencer
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </AppLayout>
    );
}
