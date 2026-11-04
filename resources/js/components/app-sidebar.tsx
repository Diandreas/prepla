import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { SUPPORT_WHATSAPP_URL } from '@/lib/contact';
import { type Marque, type MenusDisponibles, type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import AppLogo from './app-logo';
import { CentreCourant } from './centre-courant';

// Custom icon component using icons from /public/icons
// Uses CSS filter to colorize PNGs based on active state and dark/light mode
function SidebarIcon({ name }: { name: string }) {
    return (
        <img
            src={`/icons/${name}.png`}
            alt={name}
            width={32}
            height={32}
            style={{ objectFit: 'contain' }}
        />
    );
}

export function AppSidebar() {
    const { t } = useTranslation();
    const page = usePage();
    const { auth } = page.props as any;
    const role: string | undefined = auth?.role;
    const centerRole: string | undefined = auth?.center?.role;
    // Une page livree par lot n'entre au menu que si sa route existe cote serveur.
    const menus = (page.props as { menus?: MenusDisponibles }).menus;
    // En marque blanche, le lien du logo porte le nom du centre, pas PrePla.
    const marque = (page.props as { marque?: Marque }).marque;
    const nomDuSite = marque?.masquer_prepla ? marque.nom : 'PrePla';

    // Sur telephone le menu est un tiroir : sans cela il reste ouvert par-dessus
    // la page d'arrivee quand on touche une entree. On le referme a chaque
    // changement d'URL, par l'evenement que le fournisseur ecoute deja.
    useEffect(() => {
        window.dispatchEvent(new Event('mobile-navigation'));
    }, [page.url]);

    // Super-admin → back-office. Center staff → center management. Otherwise the
    // regular learner navigation. (Students of a center keep the learner nav;
    // their assignments surface on the dashboard.)
    let mainNavItems: NavItem[];

    if (role === 'super_admin') {
        mainNavItems = [
            { title: 'Centres', url: '/admin/centers', icon: () => <SidebarIcon name="layout-grid" /> },
            ...(menus?.releves
                ? [{ title: 'Relevés', url: '/admin/releves', icon: () => <SidebarIcon name="statistics" /> }]
                : []),
        ];
    } else if (centerRole === 'center_admin' || centerRole === 'teacher') {
        mainNavItems = [
            { title: 'Tableau de bord', url: '/center', icon: () => <SidebarIcon name="home" /> },
            { title: 'Classes', url: '/center/classes', icon: () => <SidebarIcon name="layout-grid" /> },
            { title: 'Élèves', url: '/center/students', icon: () => <SidebarIcon name="profile" /> },
            // L'equipe du centre — qui enseigne quelles classes, et l'invitation
            // d'un collegue — pour son responsable, comme l'autorisation.
            ...(centerRole === 'center_admin'
                ? [{ title: 'Équipe', url: '/center/equipe', icon: () => <SidebarIcon name="profile" /> }]
                : []),
            { title: 'Contenu', url: '/center/exercises', icon: () => <SidebarIcon name="puzzle" /> },
            { title: 'Devoirs', url: '/center/assignments', icon: () => <SidebarIcon name="tasks" /> },
            // Les reglages du centre (marque, examens, adresse, formule, acces
            // vendus, parrainage) sous une seule entree : le responsable ne
            // trouvait pas « les parametres de son centre » parmi trois entrees
            // separees. Sans la page d'accueil, on garde les entrees d'avant.
            ...(centerRole === 'center_admin' && menus?.parametres
                ? [{
                    title: 'Paramètres du centre',
                    url: '/center/parametres',
                    icon: () => <SidebarIcon name="settings" />,
                    // Les pages de reglage ont leur propre adresse : l'entree reste
                    // en surbrillance quand on en ouvre une.
                    aussiActifSur: ['/center/personnalisation', '/center/acces', '/center/parrainage'],
                }]
                : [
                    ...(centerRole === 'center_admin' && menus?.personnalisation
                        ? [{ title: 'Personnalisation', url: '/center/personnalisation', icon: () => <SidebarIcon name="settings" /> }]
                        : []),
                    ...(centerRole === 'center_admin' && menus?.acces
                        ? [{ title: 'Accès vendus', url: '/center/acces', icon: () => <SidebarIcon name="tasks" /> }]
                        : []),
                    ...(centerRole === 'center_admin' && menus?.parrainage
                        ? [{ title: 'Parrainage', url: '/center/parrainage', icon: () => <SidebarIcon name="users" /> }]
                        : []),
                ]),
        ];
    } else {
        // Eleve d'un centre : « Mes devoirs » ; si son centre cree le parcours de
        // ses classes, « Mon parcours », et ni pratique libre, ni guide du
        // parcours PrePla, ni outils IA PrePla.
        const eleveDeCentre = (page.props as { eleveDeCentre?: { contenu: 'libre' | 'centre' } }).eleveDeCentre;
        const libre = eleveDeCentre?.contenu !== 'centre';
        mainNavItems = [
            { title: t('sidebar.home', 'Accueil'), url: '/dashboard', icon: () => <SidebarIcon name="home" /> },
            ...(eleveDeCentre ? [{ title: t('sidebar.my_assignments', 'Mes devoirs'), url: '/devoirs', icon: () => <SidebarIcon name="tasks" /> }] : []),
            ...(eleveDeCentre && !libre ? [{ title: t('sidebar.my_path', 'Mon parcours'), url: '/mon-parcours', icon: () => <SidebarIcon name="puzzle" /> }] : []),
            ...(libre
                ? [
                      { title: t('sidebar.guide', 'Comment apprendre'), url: '/guide', icon: () => <SidebarIcon name="book" /> },
                      { title: t('sidebar.practice', 'Pratiquer'), url: '/practice', icon: () => <SidebarIcon name="puzzle" /> },
                  ]
                : []),
            { title: t('sidebar.my_words', 'Mes mots'), url: '/dictionary', icon: () => <SidebarIcon name="book" /> },
            ...(libre ? [{ title: t('sidebar.ai_tools', 'Outils IA'), url: '/ai-tools', icon: () => <SidebarIcon name="sparkles" /> }] : []),
            { title: t('sidebar.results', 'Résultats'), url: '/results', icon: () => <SidebarIcon name="statistics" /> },
            { title: t('sidebar.leaderboard', 'Classement'), url: '/leaderboard', icon: () => <SidebarIcon name="trophy" /> },
            // Rejoindre la classe de son professeur avec un code n'etait propose
            // nulle part : l'ecran existait, aucun chemin n'y conduisait.
            { title: t('sidebar.join_class', 'Rejoindre une classe'), url: '/join', icon: () => <SidebarIcon name="layout-grid" /> },
        ];
    }

    const footerNavItems: NavItem[] = [
        // Un professeur n'avait aucun moyen de trouver l'espace enseignant : la
        // creation d'un centre etait reservee au super-administrateur et aucun lien
        // n'y menait. On l'offre a qui n'appartient encore a aucun espace.
        ...(!centerRole && role !== 'super_admin'
            ? [{
                title: t('sidebar.teach', 'Suivre mes eleves'),
                url: '/enseigner',
                icon: () => <SidebarIcon name="profile" />,
            }]
            : []),
        // Un responsable peut ouvrir un AUTRE espace — un second etablissement :
        // sans ce lien, il devait se creer un second compte.
        ...(centerRole === 'center_admin'
            ? [{
                title: 'Ouvrir un autre espace',
                url: '/enseigner',
                icon: () => <SidebarIcon name="layout-grid" />,
            }]
            : []),
        {
            title: t('sidebar.support', 'Aide & support'),
            url: SUPPORT_WHATSAPP_URL,
            icon: () => <SidebarIcon name="help" />,
            external: true,
        },
        {
            // Pour l'equipe d'un centre (responsable, enseignant), ce lien mene au
            // compte personnel : on le distingue des « Parametres du centre » du
            // menu principal, ecrit en francais comme le reste du menu equipe.
            // Un apprenant de centre (role student) garde le libelle traduit.
            title: centerRole === 'center_admin' || centerRole === 'teacher'
                ? 'Mon compte'
                : t('sidebar.settings', 'Paramètres'),
            url: '/settings/profile',
            icon: () => <SidebarIcon name="settings" />,
        },
    ];

    return (
        <Sidebar className="app-sidebar" collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/dashboard" aria-label={`${nomDuSite} — Accueil`} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <CentreCourant />
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
