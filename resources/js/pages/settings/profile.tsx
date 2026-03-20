import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { 
    User, Mail, Shield, CreditCard, BookOpen, Target, 
    Calendar, ChevronRight, LogOut, Trash2, Moon, 
    Lock, Sparkles, Languages, Award, Flame, Zap
} from 'lucide-react';

import DeleteUser from '@/components/delete-user';
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

    const sectionTitleStyle = "text-xs font-bold uppercase tracking-[0.1em] text-muted-foreground mb-3 px-1";
    const itemStyle = "flex items-center justify-between w-full p-4 text-left transition-colors active:bg-muted/50 group";
    const itemIconBg = "flex h-9 w-9 items-center justify-center rounded-xl mr-3";

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('profile.title', 'Profil')} />

            <div className="mx-auto max-w-2xl space-y-8 py-6 pb-24 md:py-10 px-4">
                {/* 1. User Profile Header */}
                <div className="flex flex-col items-center px-4 text-center">
                    <div className="relative mb-4">
                        <Avatar className="h-24 w-24 border-4 border-background shadow-xl">
                            <AvatarImage src={auth.user.avatar} />
                            <AvatarFallback className="text-2xl font-bold bg-primary text-primary-foreground">
                                {getInitials(auth.user.name)}
                            </AvatarFallback>
                        </Avatar>
                        <div className="absolute -bottom-1 -right-1 flex h-8 w-8 items-center justify-center rounded-full bg-primary text-white shadow-lg border-4 border-background">
                            <Sparkles size={14} fill="currentColor" />
                        </div>
                    </div>
                    <h2 className="text-2xl font-black tracking-tight">{auth.user.name}</h2>
                    <p className="text-sm text-muted-foreground">{auth.user.email}</p>
                    
                    <div className="mt-6 flex gap-3">
                        <div className="flex flex-col items-center rounded-2xl border bg-card px-5 py-3 shadow-sm min-w-[100px]">
                            <span className="text-xs font-bold text-muted-foreground uppercase tracking-wider mb-1">{t('profile.xp_points', 'Points XP')}</span>
                            <div className="flex items-center gap-1.5 text-lg font-black text-primary">
                                <Zap size={18} className="fill-current" />
                                {profile?.xp_total ?? 0}
                            </div>
                        </div>
                        <div className="flex flex-col items-center rounded-2xl border bg-card px-5 py-3 shadow-sm min-w-[100px]">
                            <span className="text-xs font-bold text-muted-foreground uppercase tracking-wider mb-1">{t('profile.streak', 'Série')}</span>
                            <div className="flex items-center gap-1.5 text-lg font-black text-orange-500">
                                <Flame size={18} className="fill-current" />
                                {profile?.streak_current ?? 0}
                            </div>
                        </div>
                    </div>
                </div>

                {/* 2. Subscription Card */}
                <div className="px-4">
                    <Card className="overflow-hidden border-none bg-gradient-to-br from-[#1A2B48] to-[#2a3f6a] text-white shadow-lg">
                        <CardHeader className="pb-2">
                            <div className="flex items-center justify-between">
                                <Badge className={`font-bold ${profile?.plan === 'premium' ? 'bg-amber-400 text-amber-950' : 'bg-neutral-500 text-white'}`}>
                                    {profile?.plan === 'premium' ? 'PREMIUM' : 'STANDARD'}
                                </Badge>
                                <CreditCard size={18} className="opacity-50" />
                            </div>
                            <CardTitle className="text-xl mt-2 font-black">
                                {profile?.plan === 'premium' ? 'PrePla Plus' : 'PrePla Standard'}
                            </CardTitle>
                            <CardDescription className="text-blue-100/70">
                                {profile?.plan === 'premium' 
                                    ? t('profile.premium_desc', 'Accès illimité à tous les examens et correction IA avancée.') 
                                    : t('profile.standard_desc', 'Passez au Premium pour débloquer toutes les fonctionnalités.')}
                            </CardDescription>
                        </CardHeader>
                        <CardFooter className="pt-0">
                            <Link href={route('subscription.index')} className="w-full">
                                <Button 
                                    variant="secondary" 
                                    className="w-full mt-4 bg-white/10 hover:bg-white/20 border-white/20 text-white font-bold"
                                >
                                    {t('profile.manage_subscription', 'Gérer mon abonnement')}
                                </Button>
                            </Link>
                        </CardFooter>
                    </Card>
                </div>

                {/* 3. Learning Settings */}
                <div className="px-4">
                    <h3 className={sectionTitleStyle}>{t('profile.study_settings', 'Paramètres d\'étude')}</h3>
                    <div className="overflow-hidden rounded-2xl border bg-card shadow-sm divide-y">
                        {/* Target Exam */}
                        <div className="p-4 space-y-4">
                            <div className="flex items-center mb-1">
                                <div className={itemIconBg + " bg-blue-100 text-blue-600"}>
                                    <BookOpen size={18} />
                                </div>
                                <div className="flex-1">
                                    <p className="text-sm font-bold">{t('profile.prepared_exam', 'Examen préparé')}</p>
                                    <p className="text-xs text-muted-foreground">{t('profile.select_goal', 'Sélectionnez votre objectif')}</p>
                                </div>
                            </div>
                            <Select 
                                value={learningForm.data.target_exam_id} 
                                onValueChange={(val) => {
                                    learningForm.setData('target_exam_id', val);
                                }}
                            >
                                <SelectTrigger className="w-full bg-muted/30 border-none h-11 font-bold">
                                    <SelectValue placeholder={t('profile.choose_exam', 'Choisir un examen')} />
                                </SelectTrigger>
                                <SelectContent>
                                    {exams.map(exam => (
                                        <SelectItem key={exam.id} value={exam.id.toString()}>
                                            {exam.language.flag} {exam.name}
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
                                <div className={itemIconBg + " bg-purple-100 text-purple-600"}>
                                    <Calendar size={18} />
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
                                <div className={itemIconBg + " bg-orange-100 text-orange-600"}>
                                    <Languages size={18} />
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
                                    <SelectItem value="en">🇺🇸 English</SelectItem>
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
                <div className="px-4">
                    <h3 className={sectionTitleStyle}>{t('profile.personal_info', 'Informations Personnelles')}</h3>
                    <div className="overflow-hidden rounded-2xl border bg-card shadow-sm divide-y">
                        <button 
                            onClick={() => setIsEditingInfo(!isEditingInfo)}
                            className={itemStyle}
                        >
                            <div className="flex items-center">
                                <div className={itemIconBg + " bg-neutral-100 text-neutral-600"}>
                                    <User size={18} />
                                </div>
                                <div>
                                    <p className="text-sm font-bold">{auth.user.name}</p>
                                    <p className="text-xs text-muted-foreground">{t('profile.full_name', 'Nom complet')}</p>
                                </div>
                            </div>
                            <ChevronRight size={16} className="text-muted-foreground opacity-50" />
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
                                <div className={itemIconBg + " bg-neutral-100 text-neutral-600"}>
                                    <Shield size={18} />
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
                <div className="px-4">
                    <h3 className={sectionTitleStyle}>{t('profile.app_settings', 'Application')}</h3>
                    <div className="overflow-hidden rounded-2xl border bg-card shadow-sm divide-y">
                        <Link href={route('appearance')} className={itemStyle}>
                            <div className="flex items-center">
                                <div className={itemIconBg + " bg-neutral-100 text-neutral-600"}>
                                    <Moon size={18} />
                                </div>
                                <p className="text-sm font-bold">{t('profile.appearance', 'Apparence')}</p>
                            </div>
                            <ChevronRight size={16} className="text-muted-foreground opacity-50" />
                        </Link>
                        <Link href={route('password.edit')} className={itemStyle}>
                            <div className="flex items-center">
                                <div className={itemIconBg + " bg-neutral-100 text-neutral-600"}>
                                    <Lock size={18} />
                                </div>
                                <p className="text-sm font-bold">{t('profile.security_password', 'Sécurité & Mot de passe')}</p>
                            </div>
                            <ChevronRight size={16} className="text-muted-foreground opacity-50" />
                        </Link>
                        <Link href={route('logout')} method="post" as="button" className={itemStyle}>
                            <div className="flex items-center text-red-600">
                                <div className={itemIconBg + " bg-red-50 text-red-600"}>
                                    <LogOut size={18} />
                                </div>
                                <p className="text-sm font-bold">{t('profile.logout', 'Déconnexion')}</p>
                            </div>
                        </Link>
                    </div>
                </div>

                {/* 6. Danger Zone */}
                <div className="px-4 opacity-70">
                    <div className="overflow-hidden rounded-2xl border border-red-200 bg-red-50/50 shadow-sm">
                        <DeleteUser />
                    </div>
                </div>

                <p className="text-center text-[10px] text-muted-foreground uppercase tracking-widest font-black opacity-40">
                    PrePla v1.2.0 • {t('profile.made_with', 'Made with')} ❤️ {t('profile.for_success', 'for success')}
                </p>
            </div>
        </AppLayout>
    );
}
