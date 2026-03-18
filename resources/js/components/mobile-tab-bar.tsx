import { Link, usePage } from '@inertiajs/react';
import { BarChart3, BookOpen, Home, Sparkles, User } from 'lucide-react';

const tabs = [
    { label: 'Home', href: '/dashboard', icon: Home },
    { label: 'Practice', href: '/practice', icon: BookOpen },
    { label: 'AI Tools', href: '/ai-tools', icon: Sparkles },
    { label: 'Results', href: '/results', icon: BarChart3 },
    { label: 'Profile', href: '/settings/profile', icon: User },
];

export function MobileTabBar() {
    const { url } = usePage();

    return (
        <nav className="fixed bottom-0 left-0 right-0 z-50 border-t border-border bg-background/95 backdrop-blur-lg md:hidden">
            <div className="flex h-16 items-center justify-around px-2">
                {tabs.map((tab) => {
                    const isActive = url.startsWith(tab.href);
                    return (
                        <Link
                            key={tab.href}
                            href={tab.href}
                            className={`flex flex-1 flex-col items-center gap-1 py-1 text-xs transition-colors ${
                                isActive
                                    ? 'text-primary'
                                    : 'text-muted-foreground hover:text-foreground'
                            }`}
                        >
                            <tab.icon className="h-5 w-5" />
                            <span>{tab.label}</span>
                        </Link>
                    );
                })}
            </div>
        </nav>
    );
}
