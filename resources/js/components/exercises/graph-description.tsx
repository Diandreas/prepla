import { useState } from 'react';

interface ChartDataset {
    label: string;
    data: number[];
}

interface ChartData {
    type: 'bar' | 'line' | 'pie';
    labels: string[];
    datasets: ChartDataset[];
}

interface GraphDescriptionProps {
    question: {
        id: string;
        text: string;
        image_url?: string;
        chart_data?: ChartData;
        min_words?: number;
        max_words?: number;
    };
    onAnswer: (questionId: string, answer: string) => void;
    selectedAnswer?: string;
    disabled?: boolean;
}

const COLORS = ['#4A90E2', '#F5A623', '#48b77b', '#e74c3c', '#9b59b6', '#1abc9c'];

function SimpleChart({ data }: { data: ChartData }) {
    if (data.type === 'pie') {
        return <PieChart data={data} />;
    }
    return <BarLineChart data={data} />;
}

function BarLineChart({ data }: { data: ChartData }) {
    const allValues = data.datasets.flatMap((d) => d.data);
    const maxVal = Math.max(...allValues, 1);
    const isLine = data.type === 'line';

    return (
        <div className="space-y-2">
            {/* Legend */}
            <div className="flex flex-wrap gap-3">
                {data.datasets.map((ds, i) => (
                    <div key={i} className="flex items-center gap-1.5 text-xs">
                        <div className="h-3 w-3 rounded" style={{ background: COLORS[i % COLORS.length] }} />
                        <span className="font-medium">{ds.label}</span>
                    </div>
                ))}
            </div>

            {/* Chart area */}
            <div className="flex items-end gap-1 pt-2" style={{ height: 200 }}>
                {data.labels.map((label, li) => (
                    <div key={li} className="flex flex-1 flex-col items-center gap-1">
                        <div className="flex w-full items-end justify-center gap-0.5" style={{ height: 160 }}>
                            {data.datasets.map((ds, di) => {
                                const h = (ds.data[li] / maxVal) * 100;
                                return (
                                    <div
                                        key={di}
                                        className={`relative flex-1 max-w-[30px] ${isLine ? '' : 'rounded-t'}`}
                                        style={{
                                            height: `${h}%`,
                                            background: COLORS[di % COLORS.length],
                                            minHeight: 4,
                                            opacity: 0.85,
                                        }}
                                    >
                                        <span className="absolute -top-5 left-1/2 -translate-x-1/2 text-[9px] font-bold text-foreground">
                                            {ds.data[li]}
                                        </span>
                                    </div>
                                );
                            })}
                        </div>
                        <span className="text-[10px] font-medium text-muted-foreground text-center leading-tight">
                            {label}
                        </span>
                    </div>
                ))}
            </div>
        </div>
    );
}

function PieChart({ data }: { data: ChartData }) {
    const values = data.datasets[0]?.data ?? [];
    const total = values.reduce((a, b) => a + b, 0) || 1;

    let cumAngle = 0;
    const slices = values.map((val, i) => {
        const angle = (val / total) * 360;
        const start = cumAngle;
        cumAngle += angle;
        return { label: data.labels[i], val, angle, start, color: COLORS[i % COLORS.length] };
    });

    return (
        <div className="flex items-center gap-6">
            <svg viewBox="0 0 100 100" className="h-40 w-40">
                {slices.map((s, i) => {
                    const startRad = (s.start - 90) * (Math.PI / 180);
                    const endRad = (s.start + s.angle - 90) * (Math.PI / 180);
                    const large = s.angle > 180 ? 1 : 0;
                    const x1 = 50 + 45 * Math.cos(startRad);
                    const y1 = 50 + 45 * Math.sin(startRad);
                    const x2 = 50 + 45 * Math.cos(endRad);
                    const y2 = 50 + 45 * Math.sin(endRad);
                    return (
                        <path
                            key={i}
                            d={`M50,50 L${x1},${y1} A45,45 0 ${large},1 ${x2},${y2} Z`}
                            fill={s.color}
                            opacity={0.85}
                        />
                    );
                })}
            </svg>
            <div className="space-y-1">
                {slices.map((s, i) => (
                    <div key={i} className="flex items-center gap-2 text-xs">
                        <div className="h-3 w-3 rounded" style={{ background: s.color }} />
                        <span className="font-medium">{s.label}</span>
                        <span className="text-muted-foreground">({Math.round((s.val / total) * 100)}%)</span>
                    </div>
                ))}
            </div>
        </div>
    );
}

export function GraphDescription({ question, onAnswer, selectedAnswer, disabled }: GraphDescriptionProps) {
    const [value, setValue] = useState(selectedAnswer ?? '');
    const minWords = question.min_words ?? 150;
    const maxWords = question.max_words ?? 250;
    const wordCount = value.trim() ? value.trim().split(/\s+/).length : 0;
    const inRange = wordCount >= minWords && wordCount <= maxWords;

    const handleChange = (text: string) => {
        setValue(text);
        onAnswer(question.id, text);
    };

    return (
        <div className="space-y-4">
            <p className="text-sm font-medium">{question.text}</p>

            {/* Chart or image */}
            <div className="rounded-xl border bg-background p-5">
                {question.chart_data ? (
                    <SimpleChart data={question.chart_data} />
                ) : question.image_url ? (
                    <img src={question.image_url} alt="Chart" className="mx-auto max-h-64 rounded-lg object-contain" />
                ) : (
                    <p className="py-8 text-center text-sm text-muted-foreground">Aucun graphique disponible</p>
                )}
            </div>

            {/* Writing area */}
            <textarea
                className="min-h-[180px] w-full rounded-lg border border-border bg-background p-4 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary disabled:opacity-50"
                placeholder="Décrivez le graphique..."
                value={value}
                onChange={(e) => handleChange(e.target.value)}
                disabled={disabled}
            />

            <div className="flex justify-between text-sm">
                <span className={inRange ? 'font-medium text-green-600' : wordCount > maxWords ? 'font-medium text-red-500' : 'text-muted-foreground'}>
                    {wordCount} mot{wordCount !== 1 ? 's' : ''}
                </span>
                <span className="text-muted-foreground">{minWords}–{maxWords} mots</span>
            </div>
        </div>
    );
}
