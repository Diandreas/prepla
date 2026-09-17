import { SidebarGroup, SidebarGroupContent, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';

export function NavFooter({
    items,
    className,
    ...props
}: React.ComponentPropsWithoutRef<typeof SidebarGroup> & {
    items: NavItem[];
}) {
    return (
        <SidebarGroup {...props} className={`group-data-[collapsible=icon]:p-0 ${className || ''}`}>
            <SidebarGroupContent>
                <SidebarMenu>
                    {items.map((item) => {
                        const content = (
                            <>
                                {item.icon && (
                                    <span className="flex items-center justify-center w-7 h-7 shrink-0">
                                        <item.icon />
                                    </span>
                                )}
                                <span className="font-medium">{item.title}</span>
                            </>
                        );

                        return (
                            <SidebarMenuItem key={item.title}>
                                <SidebarMenuButton
                                    asChild
                                    tooltip={item.title}
                                    className="h-10 gap-3 rounded-lg transition-all"
                                >
                                    {item.external ? (
                                        <a href={item.url} aria-label={item.title} target="_blank" rel="noopener noreferrer">
                                            {content}
                                        </a>
                                    ) : (
                                        <Link href={item.url} aria-label={item.title}>{content}</Link>
                                    )}
                                </SidebarMenuButton>
                            </SidebarMenuItem>
                        );
                    })}
                </SidebarMenu>
            </SidebarGroupContent>
        </SidebarGroup>
    );
}
