import { useState, useRef, useEffect } from 'react';
import { Markdown } from '@/components/markdown';

// Read the freshest CSRF token (cookie tracks the live session) — the <meta>
// tag is frozen at page load and goes stale on a long-running session.
function csrfToken(): string {
    const cookie = document.cookie.split('; ').find(c => c.startsWith('XSRF-TOKEN='));
    if (cookie) return decodeURIComponent(cookie.split('=')[1]);
    return (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content ?? '';
}

interface Message {
    role: 'user' | 'assistant';
    content: string;
}

interface Props {
    isOpen: boolean;
    onClose: () => void;
    context: {
        prompt: string;
        user_answer: string;
        correct_answer: string;
        language: string;
    };
    initialExplanation?: string;
}

export function AiChatFeedback({ isOpen, onClose, context, initialExplanation }: Props) {
    const [messages, setMessages] = useState<Message[]>([]);
    const [inputValue, setInputValue] = useState('');
    const [isLoading, setIsLoading] = useState(false);
    const scrollRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (isOpen && messages.length === 0 && initialExplanation) {
            setMessages([{ role: 'assistant', content: initialExplanation }]);
        }
    }, [isOpen, initialExplanation]);

    useEffect(() => {
        if (scrollRef.current) {
            scrollRef.current.scrollTop = scrollRef.current.scrollHeight;
        }
    }, [messages]);

    if (!isOpen) return null;

    const handleSend = async () => {
        if (!inputValue.trim() || isLoading) return;

        const userMsg: Message = { role: 'user', content: inputValue };
        setMessages(prev => [...prev, userMsg]);
        setInputValue('');
        setIsLoading(true);

        try {
            const response = await fetch('/api/ai/chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-XSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify({
                    messages: [...messages, userMsg].map(m => ({ role: m.role, content: m.content })),
                    context: context
                })
            });

            const data = await response.json();
            if (data.message) {
                setMessages(prev => [...prev, { role: 'assistant', content: data.message }]);
            }
        } catch (error) {
            console.error('Chat error:', error);
        } finally {
            setIsLoading(false);
        }
    };

    return (
        <div className="fixed inset-0 z-[100] flex items-center justify-center bg-black/40 backdrop-blur-sm p-4 animate-in fade-in duration-200">
            <div className="w-full max-w-lg overflow-hidden rounded-3xl bg-white shadow-2xl dark:bg-slate-900 animate-in zoom-in-95 slide-in-from-bottom-4 duration-300">
                {/* Header */}
                <div className="flex items-center justify-between border-b p-4 dark:border-slate-800">
                    <div className="flex items-center gap-3">
                        <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-600 text-white shadow-lg shadow-indigo-600/20">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 1 1-7.6-11.7c.9 0 1.8.2 2.6.5l4.3-1.6-1.6 4.3Z" /></svg>
                        </div>
                        <div>
                            <h3 className="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider">Tuteur IA Personnel</h3>
                            <p className="text-[11px] font-medium text-slate-500">Prêt à clarifier vos doutes</p>
                        </div>
                    </div>
                    <button onClick={onClose} className="rounded-full p-2 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors text-slate-400">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><path d="M18 6L6 18M6 6l12 12" /></svg>
                    </button>
                </div>

                {/* Messages Area */}
                <div 
                    ref={scrollRef}
                    className="h-[400px] overflow-y-auto p-6 space-y-6 scroll-smooth"
                >
                    {messages.map((msg, i) => (
                        <div key={i} className={`flex ${msg.role === 'user' ? 'justify-end' : 'justify-start'} animate-in fade-in slide-in-from-bottom-2 duration-300`}>
                            <div className={`max-w-[85%] rounded-2xl p-4 text-sm shadow-sm ${
                                msg.role === 'user'
                                ? 'bg-indigo-600 text-white rounded-tr-none'
                                : 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-200 rounded-tl-none border dark:border-slate-700'
                            }`}>
                                {msg.role === 'assistant'
                                    ? <Markdown content={msg.content} />
                                    : msg.content}
                            </div>
                        </div>
                    ))}
                    {isLoading && (
                        <div className="flex justify-start">
                            <div className="bg-slate-100 dark:bg-slate-800 rounded-2xl rounded-tl-none p-4 border dark:border-slate-700">
                                <div className="flex gap-1">
                                    <div className="w-1.5 h-1.5 bg-indigo-400 rounded-full animate-bounce" />
                                    <div className="w-1.5 h-1.5 bg-indigo-400 rounded-full animate-bounce [animation-delay:0.2s]" />
                                    <div className="w-1.5 h-1.5 bg-indigo-400 rounded-full animate-bounce [animation-delay:0.4s]" />
                                </div>
                            </div>
                        </div>
                    )}
                </div>

                {/* Input Area */}
                <div className="border-t p-4 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50">
                    <div className="relative flex items-center gap-2">
                        <input
                            type="text"
                            placeholder="Posez une question sur votre erreur..."
                            className="flex-1 rounded-2xl border-none bg-white px-5 py-3 text-sm shadow-inner ring-1 ring-slate-200 focus:ring-2 focus:ring-indigo-500 dark:bg-slate-800 dark:ring-slate-700"
                            value={inputValue}
                            onChange={(e) => setInputValue(e.target.value)}
                            onKeyDown={(e) => e.key === 'Enter' && handleSend()}
                        />
                        <button 
                            onClick={handleSend}
                            disabled={!inputValue.trim() || isLoading}
                            className="flex h-11 w-11 items-center justify-center rounded-2xl bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 transition-all hover:scale-105 active:scale-95 disabled:opacity-50"
                        >
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z" /></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}
