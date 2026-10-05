import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';

export function LearningModePicker() {
    const preferences = usePage().props.learningPreferences as { speaking_enabled: boolean; audio_enabled: boolean } | undefined;
    const [saving, setSaving] = useState(false);
    return <fieldset disabled={saving} className="flex flex-wrap gap-2 text-xs">
        <legend className="mb-2 text-muted-foreground">Aujourd’hui, je peux…</legend>
        {([{ key: 'speaking_enabled', label: 'Parler au micro' }, { key: 'audio_enabled', label: 'Écouter de l’audio' }] as const).map(({ key, label }) => <button
            key={key} type="button" aria-pressed={preferences?.[key] !== false}
            className={`rounded-full border px-3 py-2 font-semibold ${preferences?.[key] !== false ? 'border-primary/30 bg-primary/10 text-primary' : 'border-border text-muted-foreground'}`}
            onClick={() => router.patch(route('learning.preferences'), { [key]: preferences?.[key] === false }, { preserveScroll: true, onStart: () => setSaving(true), onFinish: () => setSaving(false) })}
        >{preferences?.[key] !== false ? '✓ ' : ''}{label}</button>)}
    </fieldset>;
}
