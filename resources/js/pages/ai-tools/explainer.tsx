import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Send, User, Bot } from 'lucide-react';
import { useState } from 'react';

interface Message {
    role: 'user' | 'assistant';
    content: string;
}

export default function Explainer() {
    const [messages, setMessages] = useState<Message[]>([
        {
            role: 'assistant',
            content: 'Hi! I\'m your AI language tutor. Ask me anything about grammar, vocabulary, exam strategies, or language learning. How can I help you today?',
        },
    ]);
    const [input, setInput] = useState('');

    function handleSend() {
        if (!input.trim()) return;
        const userMessage: Message = { role: 'user', content: input };
        setMessages((prev) => [...prev, userMessage]);
        setInput('');

        // Mock response - replace with SSE streaming later
        setTimeout(() => {
            setMessages((prev) => [
                ...prev,
                {
                    role: 'assistant',
                    content: `That's a great question about "${input}". This is a mock response. Connect the Mistral AI API for real explanations. The AI explainer will provide detailed answers about grammar rules, vocabulary usage, exam tips, and more.`,
                },
            ]);
        }, 500);
    }

    return (
        <AppLayout>
            <Head title="AI Explainer" />
            <div className="flex h-[calc(100vh-8rem)] flex-col p-4 md:p-6">
                <div className="mb-4">
                    <h1 className="text-2xl font-bold">AI Explainer</h1>
                    <p className="text-muted-foreground">Ask questions about language and exam preparation</p>
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
                                            <Bot className="h-4 w-4 text-primary" />
                                        </div>
                                    )}
                                    <div
                                        className={`max-w-[80%] rounded-lg p-3 text-sm ${
                                            msg.role === 'user'
                                                ? 'bg-primary text-primary-foreground'
                                                : 'bg-muted'
                                        }`}
                                    >
                                        {msg.content}
                                    </div>
                                    {msg.role === 'user' && (
                                        <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-muted">
                                            <User className="h-4 w-4" />
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
                                    placeholder="Ask a question..."
                                    value={input}
                                    onChange={(e) => setInput(e.target.value)}
                                    onKeyDown={(e) => e.key === 'Enter' && handleSend()}
                                />
                                <Button size="icon" onClick={handleSend} disabled={!input.trim()}>
                                    <Send className="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
