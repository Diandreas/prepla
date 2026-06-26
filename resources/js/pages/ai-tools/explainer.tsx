import { Head } from '@inertiajs/react';
import axios from 'axios';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Markdown } from '@/components/markdown';
function Icon({ name, size = 20, style, className }: { name: string; size?: number; style?: React.CSSProperties; className?: string }) {
    return <img src={`/icons/${name}.png`} alt="" width={size} height={size} style={{ objectFit: 'contain', ...style }} className={className} />;
}
import { useState } from 'react';

interface Message {
    role: 'user' | 'assistant';
    content: string;
}

export default function Explainer() {
    const [messages, setMessages] = useState<Message[]>([
        {
            role: 'assistant',
            content: 'Bonjour ! Je suis votre tuteur linguistique IA. Posez-moi n\'importe quelle question sur la grammaire, le vocabulaire, les stratégies d\'examen ou l\'apprentissage des langues. Comment puis-je vous aider aujourd\'hui ?',
        },
    ]);
    const [input, setInput] = useState('');

    async function handleSend() {
        if (!input.trim()) return;
        const userMessage: Message = { role: 'user', content: input };
        const newMessages = [...messages, userMessage];
        setMessages(newMessages);
        setInput('');

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
        }
    }

    return (
        <AppLayout>
            <Head title="Explicateur IA" />
            <div className="flex h-[calc(100vh-8rem)] flex-col p-4 md:p-6">
                <div className="mb-4">
                    <h1 className="text-2xl font-bold">Explicateur IA</h1>
                    <p className="text-muted-foreground">Posez des questions sur la langue et la préparation aux examens</p>
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
                                <Button size="icon" onClick={handleSend} disabled={!input.trim()}>
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
