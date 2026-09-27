/**
 * Local store for downloaded packs and the work done on them.
 *
 * Everything is scoped by account id: a pack or an attempt belonging to another
 * learner is never read back on a shared device. Attempts are kept until a future
 * sync confirms them, so finishing a session offline never loses the work.
 */

const DB_NAME = 'prepla-offline';
const DB_VERSION = 1;

export interface OfflineQuestion {
    id: string;
    type: string;
    text: string;
    options?: string[] | null;
    correct_answer?: string | null;
    explanation?: string;
}

export interface OfflinePack {
    key: string;
    pack_id: string;
    pack_version: number;
    exercise_id: number;
    user_id: number;
    title: string;
    description: string;
    instructions: string;
    passage?: string | null;
    source_label?: string | null;
    exam_id: number;
    exam_name: string;
    language?: string | null;
    level: string;
    format: string;
    format_label: string;
    questions: OfflineQuestion[];
    downloaded_at: string;
}

export interface OfflineAttempt {
    uuid: string;
    user_id: number;
    pack_key: string;
    pack_id: string;
    exercise_id: number;
    answers: Record<string, string>;
    score: number;
    total: number;
    accuracy: number;
    finished_at: string;
    synced: boolean;
}

export function packKey(userId: number, packId: string): string {
    return `${userId}::${packId}`;
}

function openDb(): Promise<IDBDatabase> {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, DB_VERSION);

        request.onupgradeneeded = () => {
            const db = request.result;
            if (!db.objectStoreNames.contains('packs')) {
                db.createObjectStore('packs', { keyPath: 'key' }).createIndex('user_id', 'user_id');
            }
            if (!db.objectStoreNames.contains('attempts')) {
                db.createObjectStore('attempts', { keyPath: 'uuid' }).createIndex('user_id', 'user_id');
            }
            if (!db.objectStoreNames.contains('meta')) {
                db.createObjectStore('meta', { keyPath: 'key' });
            }
        };

        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error ?? new Error('IndexedDB indisponible'));
    });
}

function run<T>(store: string, mode: IDBTransactionMode, action: (store: IDBObjectStore) => IDBRequest<T>): Promise<T> {
    return openDb().then(
        (db) =>
            new Promise<T>((resolve, reject) => {
                const transaction = db.transaction(store, mode);
                const request = action(transaction.objectStore(store));
                request.onsuccess = () => resolve(request.result);
                request.onerror = () => reject(request.error ?? new Error('Écriture locale refusée'));
                transaction.oncomplete = () => db.close();
            }),
    );
}

export async function savePack(pack: Omit<OfflinePack, 'key'>): Promise<OfflinePack> {
    const stored: OfflinePack = { ...pack, key: packKey(pack.user_id, pack.pack_id) };
    await run('packs', 'readwrite', (store) => store.put(stored));
    return stored;
}

export function listPacks(userId: number): Promise<OfflinePack[]> {
    return run<OfflinePack[]>('packs', 'readonly', (store) => store.index('user_id').getAll(userId));
}

export function deletePack(key: string): Promise<undefined> {
    return run('packs', 'readwrite', (store) => store.delete(key));
}

export function saveAttempt(attempt: OfflineAttempt): Promise<IDBValidKey> {
    return run('attempts', 'readwrite', (store) => store.put(attempt));
}

export function listAttempts(userId: number): Promise<OfflineAttempt[]> {
    return run<OfflineAttempt[]>('attempts', 'readonly', (store) => store.index('user_id').getAll(userId));
}

/** The learner the local data belongs to, remembered at download time. */
export async function rememberOwner(owner: { id: number; name: string }): Promise<void> {
    await run('meta', 'readwrite', (store) => store.put({ key: 'owner', ...owner }));
}

export async function readOwner(): Promise<{ id: number; name: string } | null> {
    const record = await run<{ id: number; name: string } | undefined>('meta', 'readonly', (store) => store.get('owner'));
    return record ? { id: record.id, name: record.name } : null;
}

export function newUuid(): string {
    if (typeof crypto !== 'undefined' && 'randomUUID' in crypto) return crypto.randomUUID();
    return `local-${Date.now()}-${Math.random().toString(16).slice(2)}`;
}
