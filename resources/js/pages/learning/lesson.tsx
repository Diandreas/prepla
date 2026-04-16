import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { useEffect, useState, useCallback } from 'react';

interface QuizQuestion {
    question: string;
    options: string[];
    correct_answer: string;
    explanation?: string;
}

interface CommonMistake {
    mistake: string;
    correction: string;
    tip: string;
}

interface LessonData {
    id: number;
    title: string;
    concept: string | null;
    theory_markdown: string;
    key_takeaways: string[];
    common_mistakes: CommonMistake[];
    comprehension_quiz: QuizQuestion[];
    status: string;
    generated_at: string;
    based_on_errors: any[];
}

interface SkeletonInfo {
    current_objective: { title: string; concept: string } | null;
    current_index: number;
    total_objectives: number;
    consecutive_failures: number;
}

interface Props {
    lesson: LessonData;
    skeleton: SkeletonInfo | null;
}

const OXFORD = '#1A2B48';
const SKY = '#4A90E2';
const GOLD = '#F5A623';
const GREEN = '#48b77b';

type Phase = 'lesson' | 'quiz' | 'results';

export default function LessonPage({ lesson, skeleton }: Props) {
    const [mounted, setMounted] = useState(false);
    const [phase, setPhase] = useState<Phase>('lesson');
    const [quizAnswers, setQuizAnswers] = useState<Record<number, string>>({});
    const [quizResults, setQuizResults] = useState<any>(null);
    const [submittingQuiz, setSubmittingQuiz] = useState(false);

    useEffect(() => setMounted(true), []);

    const stagger = (i: number) => ({
        opacity: mounted ? 1 : 0,
        transform: mounted ? 'translateY(0)' : 'translateY(16px)',
        transition: `all 0.5s cubic-bezier(0.22, 1, 0.36, 1) ${i * 100}ms`,
    });

    const quiz = lesson.comprehension_quiz || [];
    const hasQuiz = quiz.length > 0;

    const handleQuizAnswer = useCallback((qIndex: number, answer: string) => {
        setQuizAnswers(prev => ({ ...prev, [qIndex]: answer }));
    }, []);

    const allQuizAnswered = quiz.length > 0 && Object.keys(quizAnswers).length >= quiz.length;

    const submitQuiz = async () => {
        if (!allQuizAnswered) return;
        setSubmittingQuiz(true);
        try {
            const res = await fetch(`/lessons/${lesson.id}/quiz`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ answers: Object.values(quizAnswers) }),
            });
            const data = await res.json();
            setQuizResults(data);
            setPhase('results');
        } catch (e) {
            console.error('Quiz submission failed', e);
        } finally {
            setSubmittingQuiz(false);
        }
    };

    // Render markdown-like content (simplified)
    const renderMarkdown = (md: string) => {
        if (!md) return null;
        
        // 1. Pre-process lines into semantic blocks
        // Normalize line endings and handle invisible BOM
        const lines = md.replace(/\r\n/g, '\n').replace(/^\uFEFF/, '').split('\n');
        const blocks: { type: string; lines: string[] }[] = [];
        
        lines.forEach(line => {
            const raw = line;
            const trimmed = line.trim();
            const lastBlock = blocks[blocks.length - 1];

            if (!trimmed) {
                if (lastBlock?.type !== 'empty') {
                    blocks.push({ type: 'empty', lines: [''] });
                }
                return;
            }

            // Detect type
            if (trimmed.startsWith('#')) {
                const level = (trimmed.match(/^#+/) || ['#'])[0].length;
                blocks.push({ type: `h${Math.min(level, 3)}`, lines: [trimmed.replace(/^#+\s*/, '')] });
            } 
            // Handle Numbered Headers (e.g., "1. Title") - common in AI output
            else if (/^\d+\.\s+[A-Z\u00C0-\u017F]/.test(trimmed) && trimmed.length < 100) {
                 blocks.push({ type: 'h2', lines: [trimmed] });
            }
            else if (trimmed.startsWith('|')) {
                if (lastBlock?.type === 'table') {
                    lastBlock.lines.push(trimmed);
                } else {
                    blocks.push({ type: 'table', lines: [trimmed] });
                }
            } else if (/^[-*]\s+/.test(trimmed)) {
                const content = trimmed.replace(/^[-*]\s+/, '');
                if (lastBlock?.type === 'list') {
                    lastBlock.lines.push(content);
                } else {
                    blocks.push({ type: 'list', lines: [content] });
                }
            } else if (trimmed.startsWith('>')) {
                const content = trimmed.replace(/^>\s*/, '');
                if (lastBlock?.type === 'quote') {
                    lastBlock.lines.push(content);
                } else {
                    blocks.push({ type: 'quote', lines: [content] });
                }
            } else {
                blocks.push({ type: 'text', lines: [trimmed] });
            }
        });

        const parseInline = (text: string) => {
            return text
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/\*(.*?)\*/g, '<em>$1</em>')
                .replace(/`(.*?)`/g, '<code class="bg-gray-100 px-1 rounded text-[10px]">$1</code>');
        };

        return blocks.map((block, bi) => {
            switch (block.type) {
                case 'h1': return <h1 key={bi} className="mt-8 mb-4 text-2xl font-black" style={{ color: OXFORD }}>{block.lines[0]}</h1>;
                case 'h2': return <h2 key={bi} className="mt-7 mb-3 text-xl font-black" style={{ color: SKY }}>{block.lines[0]}</h2>;
                case 'h3': return <h3 key={bi} className="mt-6 mb-2 text-lg font-black" style={{ color: OXFORD }}>{block.lines[0]}</h3>;
                case 'empty': return <div key={bi} className="h-4" />;
                case 'text': return (
                    <p key={bi} className="text-sm leading-relaxed mb-3" style={{ color: OXFORD }} 
                       dangerouslySetInnerHTML={{ __html: parseInline(block.lines[0]) }} />
                );
                case 'quote': return (
                    <div key={bi} className="border-l-4 pl-5 py-2 my-5 rounded-r-2xl shadow-sm" style={{ borderColor: GOLD, background: 'rgba(245,166,35,0.06)' }}>
                        {block.lines.map((l, li) => (
                            <p key={li} className="text-sm italic mb-1" style={{ color: OXFORD }} dangerouslySetInnerHTML={{ __html: parseInline(l) }} />
                        ))}
                    </div>
                );
                case 'list': return (
                    <ul key={bi} className="space-y-3 my-5 ml-4">
                        {block.lines.map((l, li) => (
                            <li key={li} className="flex gap-3">
                                <span className="mt-1.5 h-2 w-2 rounded-full shrink-0 shadow-sm" style={{ background: SKY }} />
                                <p className="text-sm leading-relaxed" style={{ color: OXFORD }} dangerouslySetInnerHTML={{ __html: parseInline(l) }} />
                            </li>
                        ))}
                    </ul>
                );
                case 'table':
                    const headerRows = block.lines.filter(l => !l.includes('---'));
                    if (headerRows.length === 0) return null;

                    const tableData = headerRows.map(l => {
                             const parts = l.split('|');
                             // Remove empty parts at start/end
                             if (parts[0].trim() === '') parts.shift();
                             if (parts[parts.length - 1]?.trim() === '') parts.pop();
                             return parts.map(c => c.trim());
                    });
                    
                    return (
                        <div key={bi} className="my-8 overflow-x-auto rounded-2xl border-2 border-gray-100 bg-white shadow-sm">
                            <table className="w-full text-left text-xs border-collapse">
                                <thead className="bg-gray-50/80">
                                    <tr>
                                        {tableData[0].map((h, hi) => (
                                            <th key={hi} className="px-5 py-4 font-black border-b border-gray-100" style={{ color: SKY }}>
                                                {h}
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {tableData.slice(1).map((row, ri) => (
                                        <tr key={ri} className="hover:bg-gray-50/50 transition-colors">
                                            {row.map((cell, ci) => (
                                                <td key={ci} className="px-5 py-3.5" style={{ color: OXFORD }} dangerouslySetInnerHTML={{ __html: parseInline(cell || '') }} />
                                            ))}
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    );
                default: return null;
            }
        });
    };

    return (
        <AppLayout>
            <Head title={lesson.title} />
            <div className="mx-auto max-w-2xl px-4 py-8">
                {/* Header back link */}
                <div className="mb-4" style={stagger(0)}>
                    <Link href="/lessons" className="text-xs font-bold flex items-center gap-1" style={{ color: SKY }}>
                        ← Retour au parcours
                    </Link>
                </div>

                {/* Lesson status banner */}
                {lesson.status === 'consolidation' && (
                    <div
                        className="mb-4 rounded-2xl p-4 flex items-center gap-3"
                        style={{ background: 'rgba(231,76,60,0.08)', border: '2px solid rgba(231,76,60,0.2)' }}
                    >
                        <span className="text-2xl">🔄</span>
                        <div>
                            <p className="text-sm font-black" style={{ color: '#E74C3C' }}>Leçon de consolidation</p>
                            <p className="text-xs text-muted-foreground">
                                Ce concept est repris avec une approche différente pour t'aider à mieux le comprendre.
                            </p>
                        </div>
                    </div>
                )}

                {/* Phase indicator */}
                <div className="flex items-center gap-2 mb-6" style={stagger(1)}>
                    {['lesson', 'quiz', 'results'].map((p, i) => (
                        <div key={p} className="flex items-center gap-2">
                            <div
                                className="flex h-7 w-7 items-center justify-center rounded-full text-xs font-black transition-all"
                                style={{
                                    background: phase === p ? SKY : (
                                        ['lesson', 'quiz', 'results'].indexOf(phase) > i ? GREEN : '#e5e7eb'
                                    ),
                                    color: phase === p || ['lesson', 'quiz', 'results'].indexOf(phase) > i ? '#fff' : '#9ca3af',
                                }}
                            >
                                {['lesson', 'quiz', 'results'].indexOf(phase) > i ? '✓' : i + 1}
                            </div>
                            <span className="text-[10px] font-bold uppercase tracking-wider" style={{
                                color: phase === p ? OXFORD : '#9ca3af'
                            }}>
                                {p === 'lesson' ? 'Leçon' : p === 'quiz' ? 'Quiz' : 'Résultat'}
                            </span>
                            {i < 2 && <div className="h-[2px] w-6 rounded-full" style={{ background: '#e5e7eb' }} />}
                        </div>
                    ))}
                </div>

                {/* ─── PHASE: LESSON ─── */}
                {phase === 'lesson' && (
                    <div style={stagger(2)}>
                        {/* Title card */}
                        <div className="duo-card mb-6 p-6" style={{
                            borderTop: `4px solid ${lesson.status === 'consolidation' ? '#E74C3C' : SKY}`
                        }}>
                            <h1 className="text-xl font-black mb-1" style={{ color: OXFORD }}>
                                {lesson.title}
                            </h1>
                            {lesson.concept && (
                                <p className="text-xs font-semibold text-muted-foreground">
                                    {lesson.concept.split('.')
                                        .map(segment => segment.charAt(0).toUpperCase() + segment.slice(1).replace(/_/g, ' '))
                                        .join(' › ')}
                                </p>
                            )}
                            {skeleton && (
                                <p className="mt-2 text-[10px] font-bold" style={{ color: SKY }}>
                                    Objectif {skeleton.current_index + 1} / {skeleton.total_objectives}
                                </p>
                            )}
                        </div>

                        {/* Theory content */}
                        <div className="duo-card mb-6 p-6">
                            <div className="lesson-content">
                                {renderMarkdown(lesson.theory_markdown)}
                            </div>
                        </div>

                        {/* Key takeaways */}
                        {lesson.key_takeaways && lesson.key_takeaways.length > 0 && (
                            <div className="duo-card mb-6 p-5" style={{ background: 'rgba(74,144,226,0.04)' }}>
                                <p className="text-xs font-black uppercase tracking-widest mb-3" style={{ color: SKY }}>
                                    💡 Points clés à retenir
                                </p>
                                <div className="space-y-2">
                                    {lesson.key_takeaways.map((t: string, i: number) => (
                                        <div key={i} className="flex items-start gap-2">
                                            <div className="mt-1 h-2 w-2 rounded-full shrink-0" style={{ background: SKY }} />
                                            <p className="text-sm font-semibold" style={{ color: OXFORD }}>{t}</p>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}

                        {/* Common mistakes */}
                        {lesson.common_mistakes && lesson.common_mistakes.length > 0 && (
                            <div className="duo-card mb-6 p-5" style={{ background: 'rgba(231,76,60,0.04)' }}>
                                <p className="text-xs font-black uppercase tracking-widest mb-3" style={{ color: '#E74C3C' }}>
                                    ⚠️ Pièges typiques
                                </p>
                                <div className="space-y-3">
                                    {lesson.common_mistakes.map((m: CommonMistake, i: number) => (
                                        <div key={i} className="rounded-xl bg-white p-3" style={{ border: '1px solid rgba(231,76,60,0.15)' }}>
                                            <p className="text-xs font-bold" style={{ color: '#E74C3C' }}>
                                                ✗ {m.mistake}
                                            </p>
                                            <p className="text-xs font-bold mt-1" style={{ color: GREEN }}>
                                                ✓ {m.correction}
                                            </p>
                                            {m.tip && (
                                                <p className="text-[10px] text-muted-foreground mt-1 italic">
                                                    💡 {m.tip}
                                                </p>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}

                        {/* Continue to quiz */}
                        <div className="flex justify-center">
                            {hasQuiz ? (
                                <button
                                    onClick={() => setPhase('quiz')}
                                    className="group rounded-2xl px-8 py-3.5 text-sm font-black text-white transition-all hover:scale-[1.02]"
                                    style={{
                                        background: `linear-gradient(135deg, ${GREEN}, #3a9d68)`,
                                        boxShadow: `0 4px 0 0 #2d7d52`,
                                    }}
                                >
                                    J'ai compris ! Passer au quiz →
                                </button>
                            ) : (
                                <Link
                                    href="/lessons/next"
                                    className="group rounded-2xl px-8 py-3.5 text-sm font-black text-white transition-all hover:scale-[1.02]"
                                    style={{
                                        background: `linear-gradient(135deg, ${SKY}, #3478c8)`,
                                        boxShadow: `0 4px 0 0 #2a6fc0`,
                                    }}
                                >
                                    Prochaine leçon →
                                </Link>
                            )}
                        </div>
                    </div>
                )}

                {/* ─── PHASE: QUIZ ─── */}
                {phase === 'quiz' && (
                    <div style={stagger(0)}>
                        <div className="duo-card mb-6 p-5" style={{ borderTop: `4px solid ${GOLD}` }}>
                            <p className="text-xs font-black uppercase tracking-widest mb-1" style={{ color: GOLD }}>
                                Quiz de compréhension
                            </p>
                            <p className="text-sm text-muted-foreground">
                                Vérifions que tu as bien compris la leçon. Réponds aux 3 questions suivantes.
                            </p>
                        </div>

                        <div className="space-y-4">
                            {quiz.map((q: QuizQuestion, qIndex: number) => (
                                <div key={qIndex} className="duo-card p-5">
                                    <p className="text-sm font-bold mb-3" style={{ color: OXFORD }}>
                                        <span className="text-xs font-black mr-2 inline-flex h-6 w-6 items-center justify-center rounded-full text-white" style={{ background: SKY }}>
                                            {qIndex + 1}
                                        </span>
                                        {q.question}
                                    </p>
                                    <div className="space-y-2">
                                        {q.options.map((opt: string, oIndex: number) => {
                                            const selected = quizAnswers[qIndex] === opt;
                                            return (
                                                <button
                                                    key={oIndex}
                                                    onClick={() => handleQuizAnswer(qIndex, opt)}
                                                    className="w-full rounded-xl px-4 py-3 text-left text-sm font-semibold transition-all"
                                                    style={{
                                                        background: selected ? 'rgba(74,144,226,0.1)' : '#f9fafb',
                                                        border: `2px solid ${selected ? SKY : '#e5e7eb'}`,
                                                        color: selected ? SKY : OXFORD,
                                                        transform: selected ? 'scale(1.01)' : 'scale(1)',
                                                    }}
                                                >
                                                    <span className="mr-2 text-xs font-black opacity-40">
                                                        {String.fromCharCode(65 + oIndex)}.
                                                    </span>
                                                    {opt}
                                                </button>
                                            );
                                        })}
                                    </div>
                                </div>
                            ))}
                        </div>

                        <div className="mt-6 flex items-center justify-between">
                            <button
                                onClick={() => setPhase('lesson')}
                                className="text-sm font-bold"
                                style={{ color: '#9ca3af' }}
                            >
                                ← Relire la leçon
                            </button>
                            <button
                                onClick={submitQuiz}
                                disabled={!allQuizAnswered || submittingQuiz}
                                className="rounded-2xl px-8 py-3.5 text-sm font-black text-white transition-all disabled:opacity-40"
                                style={{
                                    background: allQuizAnswered ? `linear-gradient(135deg, ${GREEN}, #3a9d68)` : '#d1d5db',
                                    boxShadow: allQuizAnswered ? `0 4px 0 0 #2d7d52` : 'none',
                                }}
                            >
                                {submittingQuiz ? 'Envoi…' : 'Valider le quiz ✓'}
                            </button>
                        </div>
                    </div>
                )}

                {/* ─── PHASE: RESULTS ─── */}
                {phase === 'results' && quizResults && (
                    <div style={stagger(0)}>
                        {/* Result banner */}
                        <div
                            className="duo-card mb-6 p-6 text-center text-white"
                            style={{
                                background: quizResults.passed
                                    ? `linear-gradient(135deg, ${GREEN}, #3a9d68)`
                                    : `linear-gradient(135deg, #E74C3C, #c0392b)`,
                                boxShadow: quizResults.passed
                                    ? `0 4px 0 0 #2d7d52`
                                    : `0 4px 0 0 #962d22`,
                            }}
                        >
                            <p className="text-5xl mb-3">{quizResults.passed ? '🎉' : '📖'}</p>
                            <p className="text-2xl font-black">{quizResults.accuracy}%</p>
                            <p className="text-sm font-bold opacity-90 mt-1">
                                {quizResults.message}
                            </p>
                        </div>

                        {/* Question details */}
                        <div className="space-y-3 mb-6">
                            {quizResults.results?.map((r: any, i: number) => (
                                <div
                                    key={i}
                                    className="duo-card p-4"
                                    style={{ borderLeft: `3px solid ${r.correct ? GREEN : '#E74C3C'}` }}
                                >
                                    <div className="flex items-start gap-2 mb-2">
                                        <span className="text-base">{r.correct ? '✅' : '❌'}</span>
                                        <p className="text-sm font-bold" style={{ color: OXFORD }}>{r.question}</p>
                                    </div>
                                    {!r.correct && (
                                        <div className="ml-7">
                                            <p className="text-xs text-muted-foreground">
                                                Ta réponse : <span className="font-bold" style={{ color: '#E74C3C' }}>{r.user_answer}</span>
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                Bonne réponse : <span className="font-bold" style={{ color: GREEN }}>{r.correct_answer}</span>
                                            </p>
                                            {r.explanation && (
                                                <p className="text-xs text-muted-foreground mt-1 italic">💡 {r.explanation}</p>
                                            )}
                                        </div>
                                    )}
                                </div>
                            ))}
                        </div>

                        {/* Next actions */}
                        <div className="flex flex-col items-center gap-6 w-full ">
                            {quizResults.passed ? (
                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 w-full">
                                    <Link
                                        href={lesson.node_id ? `/node/${lesson.node_id}/start` : '/lessons/next'}
                                        className="group relative rounded-2xl px-6 py-5 transition-all hover:scale-[1.02] active:scale-[0.98]"
                                        style={{
                                            background: `linear-gradient(135deg, ${GOLD}, #e08c10)`,
                                            boxShadow: `0 5px 0 0 #b36e05`,
                                        }}
                                    >
                                        <div className="flex items-center justify-between">
                                            <div className="text-left">
                                                <p className="text-[10px] font-black text-white/70 uppercase tracking-widest">Recommandé</p>
                                                <p className="text-sm font-black text-white">🚀 Pratiquer ce concept</p>
                                            </div>
                                            <div className="h-10 w-10 rounded-xl bg-white/20 flex items-center justify-center text-xl">
                                                🎯
                                            </div>
                                        </div>
                                    </Link>

                                    <Link
                                        href="/lessons/next"
                                        className="group relative rounded-2xl px-6 py-5 transition-all hover:scale-[1.02] active:scale-[0.98] border-2 bg-white"
                                        style={{ borderColor: '#e5e7eb', boxShadow: '0 4px 0 0 #e5e7eb' }}
                                    >
                                        <div className="flex items-center justify-between">
                                            <div className="text-left">
                                                <p className="text-[10px] font-black text-muted-foreground uppercase tracking-widest">Progression</p>
                                                <p className="text-sm font-black" style={{ color: OXFORD }}>📖 Leçon suivante</p>
                                            </div>
                                            <div className="h-10 w-10 rounded-xl bg-gray-50 flex items-center justify-center text-xl">
                                                ➡️
                                            </div>
                                        </div>
                                    </Link>
                                </div>
                            ) : (
                                <Link
                                    href="/lessons/next"
                                    className="w-full rounded-2xl px-8 py-4 text-sm font-black text-white transition-all hover:scale-[1.02]"
                                    style={{
                                        background: `linear-gradient(135deg, ${SKY}, #3478c8)`,
                                        boxShadow: `0 5px 0 0 #2a6fc0`,
                                    }}
                                >
                                    {quizResults.outcome === 'consolidation'
                                        ? '🔄 Essayer une autre leçon'
                                        : '▶ Réessayer la leçon'}
                                </Link>
                            )}

                            <Link
                                href="/lessons"
                                className="text-xs font-bold text-muted-foreground hover:text-sky-600 transition-colors flex items-center gap-1"
                            >
                                🗺️ Retour au parcours complet
                            </Link>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
