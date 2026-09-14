import { Head, Link } from '@inertiajs/react';
import axios from 'axios';
import { ArrowLeft, Send, Trash2 } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { ArtIcon } from '@/components/art-icon';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Markdown } from '@/components/markdown';
import { useState, useEffect, useRef } from 'react';

interface Message {
    role: 'user' | 'assistant';
    content: string;
}

const STORAGE_KEY = 'prepla-explainer-chat';
const GREETING: Message = {
    role: 'assistant',
    content: 'Bonjour ! Je suis ton tuteur linguistique IA. Pose-moi n\'importe quelle question sur la grammaire, le vocabulaire, les stratégies d\'examen ou l\'apprentissage des langues. Comment puis-je t\'aider aujourd\'hui ?',
};

const STARTERS = [
    'Explique-moi une règle de grammaire avec des exemples.',
    'Comment retenir le vocabulaire plus facilement ?',
    'Comment structurer une réponse à l’oral ?',
];

export default function Explainer() {
    // Persist the conversation so it survives reloads/navigation.
    const [messages, setMessages] = useState<Message[]>(() => {
        try {
            const saved = localStorage.getItem(STORAGE_KEY);
            if (saved) {
                const parsed = JSON.parse(saved);
                if (Array.isArray(parsed) && parsed.length) return parsed;
            }
        } catch { /* ignore */ }
        return [GREETING];
    });
    const [input, setInput] = useState('');
    const [sending, setSending] = useState(false);
    const [sendError, setSendError] = useState<string | null>(null);
    const endRef = useRef<HTMLDivElement>(null);
    const inputRef = useRef<HTMLTextAreaElement>(null);

    // Save on every change.
    useEffect(() => {
        try { localStorage.setItem(STORAGE_KEY, JSON.stringify(messages)); } catch { /* ignore */ }
    }, [messages]);

    // Auto-scroll to the latest message.
    useEffect(() => {
        if (messages.length > 1 || sending) {
            endRef.current?.scrollIntoView({
                behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth',
                block: 'nearest',
            });
        }
    }, [messages, sending]);

    function clearChat() {
        setMessages([GREETING]);
        setSendError(null);
        try { localStorage.removeItem(STORAGE_KEY); } catch { /* ignore */ }
    }

    async function handleSend() {
        if (!input.trim() || sending) return;
        const question = input.trim();
        const userMessage: Message = { role: 'user', content: question };
        const newMessages = [...messages, userMessage];
        setMessages(newMessages);
        setInput('');
        setSending(true);
        setSendError(null);

        try {
            const res = await axios.post(route('ai-tools.explainer.ask'), {
                messages: newMessages.slice(1) // exclude local greeting
            });
            setMessages((prev) => [
                ...prev,
                { role: 'assistant', content: res.data.reply }
            ]);
        } catch (error) {
            setMessages((prev) => prev.slice(0, -1));
            setInput(question);
            setSendError(axios.isAxiosError(error) && error.response?.status === 429
                ? 'Le tuteur est très sollicité pour le moment. Ta question est conservée : réessaie dans quelques instants.'
                : 'La réponse n’a pas pu être chargée. Ta question est conservée : vérifie ta connexion et réessaie.');
        } finally {
            setSending(false);
        }
    }

    return (
        <AppLayout>
            <Head title="Explicateur IA" />
            {/* Height tuned for mobile: leave room for the bottom tab bar + safe area
                so the chat is fully scrollable and the input stays reachable. */}
            <div className="studio-page mx-auto flex h-[calc(100dvh-9rem)] min-h-[480px] w-full max-w-4xl flex-col p-3 md:h-[calc(100dvh-8rem)] md:p-6">
                <div className="mb-4 flex items-center justify-between gap-3">
                    <div className="flex min-w-0 items-center gap-3">
                        <Link href="/ai-tools" aria-label="Retour aux outils IA" className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-border bg-card text-foreground transition-colors hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring">
                            <ArrowLeft className="h-4 w-4" aria-hidden="true" />
                        </Link>
                        <div>
                            <h1 className="text-base font-bold text-foreground sm:text-lg">Ton tuteur, à ton rythme</h1>
                            <p className="mt-0.5 text-xs text-muted-foreground">Une question est un bon début.</p>
                        </div>
                    </div>
                    {messages.length > 1 && (
                        <button
                            onClick={clearChat}
                            disabled={sending}
                            aria-label="Effacer la conversation"
                            className="flex h-10 shrink-0 items-center gap-2 rounded-xl border border-border px-3 text-xs font-bold text-muted-foreground hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring disabled:opacity-50"
                        >
                            <Trash2 className="h-4 w-4" aria-hidden="true" /><span className="hidden sm:inline">Effacer</span>
                        </button>
                    )}
                </div>

                <Card className="studio-card flex min-h-0 flex-1 flex-col overflow-hidden rounded-3xl">
                    <CardContent className="flex min-h-0 flex-1 flex-col p-0">
                        {/* Messages */}
                        <div className="min-h-0 flex-1 space-y-5 overflow-y-auto p-4 sm:p-6" role="log" aria-label="Conversation avec le tuteur" aria-live="polite" aria-relevant="additions text">
                            {messages.map((msg, i) => (
                                <div
                                    key={i}
                                    className={`flex gap-3 ${msg.role === 'user' ? 'justify-end' : ''}`}
                                >
                                    {msg.role === 'assistant' && (
                                        <ArtIcon name="lightbulb" size={34} tone="amber" className="shrink-0" />
                                    )}
                                    <div
                                        className={`max-w-[85%] min-w-0 overflow-x-hidden break-words rounded-2xl p-4 text-sm leading-relaxed ${
                                            msg.role === 'user'
                                                ? 'rounded-tr-sm bg-[#1a2b48] text-white dark:bg-blue-900'
                                                : 'rounded-tl-sm bg-muted/70 text-foreground'
                                        }`}
                                    >
                                        {msg.role === 'assistant'
                                            ? <Markdown content={msg.content} />
                                            : msg.content}
                                    </div>
                                </div>
                            ))}
                            {sending && (
                                <div className="flex gap-3">
                                    <ArtIcon name="lightbulb" size={34} tone="amber" className="shrink-0" />
                                    <div className="rounded-2xl bg-muted p-4 text-sm text-muted-foreground" role="status">
                                        <span className="sr-only">Le tuteur prépare sa réponse…</span>
                                        <span className="inline-flex gap-1" aria-hidden="true">
                                            <span className="h-2 w-2 rounded-full bg-current motion-safe:animate-bounce" style={{ animationDelay: '0ms' }} />
                                            <span className="h-2 w-2 rounded-full bg-current motion-safe:animate-bounce" style={{ animationDelay: '150ms' }} />
                                            <span className="h-2 w-2 rounded-full bg-current motion-safe:animate-bounce" style={{ animationDelay: '300ms' }} />
                                        </span>
                                    </div>
                                </div>
                            )}
                            {messages.length === 1 && !sending && (
                                <div className="space-y-2 pl-0 sm:pl-[46px]">
                                    <p className="mb-3 text-xs font-semibold text-muted-foreground">Besoin d’une idée pour commencer ?</p>
                                    {STARTERS.map((question) => (
                                        <button key={question} type="button" onClick={() => { setInput(question); inputRef.current?.focus(); }} className="block w-full rounded-xl border border-border bg-background px-4 py-3 text-left text-xs leading-relaxed text-foreground transition-colors hover:border-primary/50 hover:bg-primary/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                            {question}
                                        </button>
                                    ))}
                                </div>
                            )}
                            <div ref={endRef} />
                        </div>

                        {/* Input */}
                        <div className="shrink-0 border-t border-border bg-card p-3 sm:p-4">
                            {sendError && <p role="alert" className="mb-3 rounded-xl bg-rose-50 p-3 text-xs leading-relaxed text-rose-800 dark:bg-rose-950/40 dark:text-rose-200">{sendError}</p>}
                            <form className="flex items-end gap-2" onSubmit={(event) => { event.preventDefault(); void handleSend(); }}>
                                <textarea
                                    ref={inputRef}
                                    rows={2}
                                    aria-label="Ta question au tuteur"
                                    disabled={sending}
                                    className="min-w-0 flex-1 resize-none rounded-2xl border border-border bg-background px-4 py-3 text-base text-foreground placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 sm:text-sm"
                                    placeholder="Qu’aimerais-tu comprendre ?"
                                    value={input}
                                    onChange={(e) => setInput(e.target.value)}
                                    onKeyDown={(e) => {
                                        if (e.key === 'Enter' && !e.shiftKey && !e.nativeEvent.isComposing) {
                                            e.preventDefault();
                                            void handleSend();
                                        }
                                    }}
                                />
                                <Button type="submit" size="icon" aria-label="Envoyer ma question" className="mb-1 h-11 w-11 shrink-0 rounded-xl bg-[#1a2b48] text-white hover:bg-[#2a4165] dark:bg-blue-800 dark:hover:bg-blue-700" disabled={!input.trim() || sending}>
                                    <Send className="h-4 w-4" aria-hidden="true" />
                                </Button>
                            </form>
                            <p className="mt-2 px-1 text-[10px] leading-relaxed text-muted-foreground">L’IA peut se tromper. Vérifie les points importants avec tes supports de cours.</p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
