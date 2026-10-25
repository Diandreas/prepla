import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { useIsMobile } from '@/hooks/use-mobile';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';

export function NavMain({ items = [] }: { items: NavItem[] }) {
    const page = usePage();
    // Sur telephone ce menu est un tiroir, ferme la plupart du temps, mais ses
    // liens restent montes : precharger huit pages a chaque navigation ferait
    // payer de la donnee pour un menu que l'apprenant n'ouvre pas. Les cinq
    // destinations chaudes sont deja prechargees par la barre d'onglets.
    const isMobile = useIsMobile();
    const prefetch = isMobile ? 'hover' : (['mount', 'hover'] as const);
    return (
        <SidebarGroup className="px-2 py-0">
            <SidebarGroupLabel className="text-[0.65rem] font-bold tracking-widest uppercase opacity-50 mb-1">
                Navigation
            </SidebarGroupLabel>
            <SidebarMenu>
                {items.map((item) => {
                    const isActive = item.url === page.url || page.url.startsWith(item.url + '/');
                    return (
                        <SidebarMenuItem key={item.title}>
                            <SidebarMenuButton
                                asChild
                                isActive={isActive}
                                tooltip={item.title}
                                className="app-nav-item h-10 gap-3 rounded-lg transition-all"
                            >
                                <Link href={item.url} aria-label={item.title} aria-current={isActive ? 'page' : undefined} prefetch={prefetch as any} cacheFor="1m">
                                    {item.icon && (
                                        <span className="flex items-center justify-center w-7 h-7 shrink-0">
                                            <item.icon />
                                        </span>
                                    )}
                                    <span className="font-medium">{item.title}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    );
                })}
            </SidebarMenu>
        </SidebarGroup>
    );
}
