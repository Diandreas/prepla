import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';

export function NavMain({ items = [] }: { items: NavItem[] }) {
    const page = usePage();
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
                                className="h-10 gap-3 rounded-lg transition-all"
                            >
                                <Link href={item.url} prefetch>
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
