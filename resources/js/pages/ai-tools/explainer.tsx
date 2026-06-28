import { Head } from '@inertiajs/react';
import axios from 'axios';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Markdown } from '@/components/markdown';
function Icon({ name, size = 20, style, className }: { name: string; size?: number; style?: React.CSSProperties; className?: string }) {
    return <img src={`/icons/${name}.png`} alt="" width={size} height={size} style={{ objectFit: 'contain', ...style }} className={className} />;
}
import { useState, useEffect, useRef } from 'react';

interface Message {
    role: 'user' | 'assistant';
    content: string;
}

const STORAGE_KEY = 'prepla-explainer-chat';
const GREETING: Message = {
    role: 'assistant',
    content: 'Bonjour ! Je suis votre tuteur linguistique IA. Posez-moi n\'importe quelle question sur la grammaire, le vocabulaire, les stratégies d\'examen ou l\'apprentissage des langues. Comment puis-je vous aider aujourd\'hui ?',
};

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
    const endRef = useRef<HTMLDivElement>(null);

    // Save on every change.
    useEffect(() => {
        try { localStorage.setItem(STORAGE_KEY, JSON.stringify(messages)); } catch { /* ignore */ }
    }, [messages]);

    // Auto-scroll to the latest message.
    useEffect(() => {
        endRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages, sending]);

    function clearChat() {
        setMessages([GREETING]);
        try { localStorage.removeItem(STORAGE_KEY); } catch { /* ignore */ }
    }

    async function handleSend() {
        if (!input.trim() || sending) return;
        const userMessage: Message = { role: 'user', content: input };
        const newMessages = [...messages, userMessage];
        setMessages(newMessages);
        setInput('');
        setSending(true);

        try {
            const res = await axios.post(route('ai-tools.explainer.ask'), {
                messages: newMessages.slice(1) // exclude local greeting
            });
            setMessages((prev) => [
                ...prev,
                { role: 'assistant', content: res.data.reply }
            ]);
        } catch (error) {
            setMessages((prev) => [
                ...prev,
                { role: 'assistant', content: "Erreur de connexion à l'API Mistral." }
            ]);
        } finally {
            setSending(false);
        }
    }

    return (
        <AppLayout>
            <Head title="Explicateur IA" />
            {/* Height tuned for mobile: leave room for the bottom tab bar + safe area
                so the chat is fully scrollable and the input stays reachable. */}
            <div className="flex h-[calc(100dvh-9rem)] flex-col p-3 md:h-[calc(100vh-8rem)] md:p-6">
                <div className="mb-3 flex items-start justify-between gap-3">
                    <div>
                        <h1 className="text-xl md:text-2xl font-bold">Explicateur IA</h1>
                        <p className="text-sm text-muted-foreground">Posez des questions sur la langue et la préparation aux examens</p>
                    </div>
                    {messages.length > 1 && (
                        <button
                            onClick={clearChat}
                            className="shrink-0 rounded-lg border border-border px-3 py-1.5 text-xs font-bold text-muted-foreground hover:bg-muted"
                        >
                            Effacer
                        </button>
                    )}
                </div>

                <Card className="flex flex-1 flex-col overflow-hidden">
                    <CardContent className="flex flex-1 flex-col p-0">
                        {/* Messages */}
                        <div className="flex-1 overflow-y-auto p-4 space-y-4">
                            {messages.map((msg, i) => (
                                <div
                                    key={i}
                                    className={`flex gap-3 ${msg.role === 'user' ? 'justify-end' : ''}`}
                                >
                                    {msg.role === 'assistant' && (
                                        <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary/10">
                                            <Icon name="sparkles" size={16} className="text-primary" />
                                        </div>
                                    )}
                                    <div
                                        className={`max-w-[80%] rounded-lg p-3 text-sm ${
                                            msg.role === 'user'
                                                ? 'bg-primary text-primary-foreground'
                                                : 'bg-muted'
                                        }`}
                                    >
                                        {msg.role === 'assistant'
                                            ? <Markdown content={msg.content} />
                                            : msg.content}
                                    </div>
                                    {msg.role === 'user' && (
                                        <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-muted">
                                            <Icon name="user" size={16} />
                                        </div>
                                    )}
                                </div>
                            ))}
                            {sending && (
                                <div className="flex gap-3">
                                    <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary/10">
                                        <Icon name="sparkles" size={16} className="text-primary" />
                                    </div>
                                    <div className="rounded-lg bg-muted p-3 text-sm text-muted-foreground">
                                        <span className="inline-flex gap-1">
                                            <span className="h-2 w-2 animate-bounce rounded-full bg-current" style={{ animationDelay: '0ms' }} />
                                            <span className="h-2 w-2 animate-bounce rounded-full bg-current" style={{ animationDelay: '150ms' }} />
                                            <span className="h-2 w-2 animate-bounce rounded-full bg-current" style={{ animationDelay: '300ms' }} />
                                        </span>
                                    </div>
                                </div>
                            )}
                            <div ref={endRef} />
                        </div>

                        {/* Input */}
                        <div className="border-t border-border p-4">
                            <div className="flex gap-2">
                                <input
                                    className="flex-1 rounded-lg border border-border bg-background px-3 py-2 text-sm focus:border-primary focus:outline-none"
                                    placeholder="Posez une question..."
                                    value={input}
                                    onChange={(e) => setInput(e.target.value)}
                                    onKeyDown={(e) => e.key === 'Enter' && handleSend()}
                                />
                                <Button size="icon" onClick={handleSend} disabled={!input.trim() || sending}>
                                    <Icon name="send" size={16} />
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
