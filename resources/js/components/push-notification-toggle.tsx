import { usePushNotifications } from '@/hooks/use-push-notifications';
import { usePage } from '@inertiajs/react';
import { Bell, BellOff } from 'lucide-react';
import { Button } from '@/components/ui/button';
import type { SharedData } from '@/types';

export default function PushNotificationToggle({ compact = false }: { compact?: boolean }) {
    const { vapidPublicKey } = usePage<SharedData & { vapidPublicKey?: string }>().props;

    // Inject vapid key for the hook
    if (vapidPublicKey && typeof window !== 'undefined') {
        (window as any).__vapidKey = vapidPublicKey;
    }

    const { permission, isSubscribed, isLoading, isSupported, subscribe, unsubscribe } = usePushNotifications();

    if (!isSupported || permission === 'denied') return null;

    if (compact) {
        return (
            <Button
                variant="ghost"
                size="sm"
                onClick={isSubscribed ? unsubscribe : subscribe}
                disabled={isLoading}
                className="gap-2"
            >
                {isSubscribed ? <BellOff size={16} /> : <Bell size={16} />}
                {isSubscribed ? 'Désactiver les rappels' : 'Activer les rappels'}
            </Button>
        );
    }

    return (
        <div className="rounded-2xl border border-border bg-card p-4 space-y-3">
            <div className="flex items-center gap-3">
                <div className="rounded-xl bg-primary/10 p-2.5">
                    <Bell size={20} className="text-primary" />
                </div>
                <div>
                    <p className="font-semibold text-sm">Rappels de pratique</p>
                    <p className="text-xs text-muted-foreground">Recevez une notification quotidienne pour maintenir votre rythme</p>
                </div>
            </div>
            <Button
                className="w-full"
                variant={isSubscribed ? 'outline' : 'default'}
                onClick={isSubscribed ? unsubscribe : subscribe}
                disabled={isLoading}
            >
                {isLoading && <span className="animate-spin mr-2 h-4 w-4 border-2 border-current border-t-transparent rounded-full inline-block" />}
                {isSubscribed ? 'Désactiver les rappels' : 'Activer les rappels quotidiens'}
            </Button>
        </div>
    );
}
