import { Badge } from '@/components/ui/badge';
import type { Language } from '@/data/languages';

interface LanguagesGridProps {
    languages: Language[];
}

export function LanguagesGrid({ languages }: LanguagesGridProps) {
    return (
        <section id="languages" className="py-24">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div className="text-center">
                    <h2 className="text-3xl font-bold tracking-tight sm:text-4xl">
                        10 Languages, 20+ Exams
                    </h2>
                    <p className="mt-4 text-lg text-muted-foreground">
                        Choose your target language and exam to start practicing
                    </p>
                </div>

                <div className="mt-16 grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                    {languages.map((lang) => (
                        <div
                            key={lang.slug}
                            className="group relative rounded-xl border border-border bg-card p-5 text-center transition-all hover:border-primary/50 hover:shadow-lg"
                        >
                            <span className="text-4xl">{lang.flag}</span>
                            <h3 className="mt-3 font-semibold">{lang.name}</h3>
                            <p className="text-sm text-muted-foreground">{lang.native_name}</p>
                            <div className="mt-3 flex flex-wrap justify-center gap-1">
                                {lang.exams.map((exam) => (
                                    <Badge key={exam.slug} variant="secondary" className="text-xs">
                                        {exam.name}
                                    </Badge>
                                ))}
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}
