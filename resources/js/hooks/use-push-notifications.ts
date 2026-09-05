import { useCallback, useEffect, useState } from 'react';

type PermissionState = 'default' | 'granted' | 'denied' | 'unsupported';

export function usePushNotifications(vapidKey?: string) {
    const [permission, setPermission] = useState<PermissionState>('default');
    const [isSubscribed, setIsSubscribed] = useState(false);
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const isSupported = typeof window !== 'undefined'
        && 'serviceWorker' in navigator
        && 'PushManager' in window
        && 'Notification' in window
        && Boolean(vapidKey);

    useEffect(() => {
        if (!isSupported) {
            setPermission('unsupported');
            return;
        }
        setPermission(Notification.permission as PermissionState);

        // Check if already subscribed
        navigator.serviceWorker.ready
            .then((reg) => reg.pushManager.getSubscription())
            .then((sub) => setIsSubscribed(!!sub))
            .catch(() => setError('Impossible de vérifier les notifications sur cet appareil.'));
    }, [isSupported]);

    const subscribe = useCallback(async () => {
        if (!isSupported || !vapidKey) return;

        setIsLoading(true);
        setError(null);
        try {
            // Ask directly from the click gesture. Safari may reject permission
            // requests that happen after unrelated asynchronous work.
            const permission = await Notification.requestPermission();
            setPermission(permission as PermissionState);

            if (permission !== 'granted') return;

            const reg = await navigator.serviceWorker.ready;
            const sub = await reg.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(vapidKey),
            });

            const subJson = sub.toJSON() as { endpoint: string; keys: { auth: string; p256dh: string } };

            const response = await fetch(route('push.subscribe'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '',
                },
                body: JSON.stringify(subJson),
            });

            if (!response.ok) {
                await sub.unsubscribe().catch(() => false);
                throw new Error(`Push subscription failed with status ${response.status}`);
            }

            setIsSubscribed(true);
        } catch (err) {
            console.error('Push subscription failed', err);
            setError('Activation impossible pour le moment. Réessayez plus tard.');
        } finally {
            setIsLoading(false);
        }
    }, [isSupported, vapidKey]);

    const unsubscribe = useCallback(async () => {
        if (!isSupported) return;

        setIsLoading(true);
        setError(null);
        try {
            const reg = await navigator.serviceWorker.ready;
            const sub = await reg.pushManager.getSubscription();
            if (!sub) return;

            const response = await fetch(route('push.unsubscribe'), {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '',
                },
                body: JSON.stringify({ endpoint: sub.endpoint }),
            });

            if (!response.ok) throw new Error(`Push unsubscribe failed with status ${response.status}`);

            await sub.unsubscribe();
            setIsSubscribed(false);
        } catch (err) {
            console.error('Push unsubscribe failed', err);
            setError('Désactivation impossible pour le moment. Réessayez plus tard.');
        } finally {
            setIsLoading(false);
        }
    }, [isSupported]);

    return { permission, isSubscribed, isLoading, isSupported, error, subscribe, unsubscribe };
}

function urlBase64ToUint8Array(base64String: string): Uint8Array {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = atob(base64);
    return Uint8Array.from([...rawData].map((c) => c.charCodeAt(0)));
}
