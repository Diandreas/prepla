import { useState, useEffect, useCallback } from 'react';
import { router } from '@inertiajs/react';

type PermissionState = 'default' | 'granted' | 'denied' | 'unsupported';

export function usePushNotifications() {
    const [permission, setPermission] = useState<PermissionState>('default');
    const [isSubscribed, setIsSubscribed] = useState(false);
    const [isLoading, setIsLoading] = useState(false);

    const vapidKey = (window as any).__vapidKey as string | undefined;

    const isSupported = typeof window !== 'undefined'
        && 'serviceWorker' in navigator
        && 'PushManager' in window
        && 'Notification' in window;

    useEffect(() => {
        if (!isSupported) {
            setPermission('unsupported');
            return;
        }
        setPermission(Notification.permission as PermissionState);

        // Check if already subscribed
        navigator.serviceWorker.ready.then((reg) => {
            reg.pushManager.getSubscription().then((sub) => {
                setIsSubscribed(!!sub);
            });
        });
    }, [isSupported]);

    const subscribe = useCallback(async () => {
        if (!isSupported || !vapidKey) return;

        setIsLoading(true);
        try {
            const reg = await navigator.serviceWorker.ready;
            const permission = await Notification.requestPermission();
            setPermission(permission as PermissionState);

            if (permission !== 'granted') return;

            const sub = await reg.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(vapidKey),
            });

            const subJson = sub.toJSON() as { endpoint: string; keys: { auth: string; p256dh: string } };

            await fetch(route('push.subscribe'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '',
                },
                body: JSON.stringify(subJson),
            });

            setIsSubscribed(true);
        } catch (err) {
            console.error('Push subscription failed', err);
        } finally {
            setIsLoading(false);
        }
    }, [isSupported, vapidKey]);

    const unsubscribe = useCallback(async () => {
        if (!isSupported) return;

        setIsLoading(true);
        try {
            const reg = await navigator.serviceWorker.ready;
            const sub = await reg.pushManager.getSubscription();
            if (!sub) return;

            await fetch(route('push.unsubscribe'), {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '',
                },
                body: JSON.stringify({ endpoint: sub.endpoint }),
            });

            await sub.unsubscribe();
            setIsSubscribed(false);
        } catch (err) {
            console.error('Push unsubscribe failed', err);
        } finally {
            setIsLoading(false);
        }
    }, [isSupported]);

    return { permission, isSubscribed, isLoading, isSupported, subscribe, unsubscribe };
}

function urlBase64ToUint8Array(base64String: string): Uint8Array {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = atob(base64);
    return Uint8Array.from([...rawData].map((c) => c.charCodeAt(0)));
}
