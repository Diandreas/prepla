import { Sparkles } from 'lucide-react';

export default function AppLogo() {
    return (
        <>
            <div className="bg-sidebar-primary text-sidebar-primary-foreground flex aspect-square size-8 items-center justify-center rounded-md">
                <Sparkles className="size-4 text-white" />
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-none font-bold tracking-tight">
                    Pre<span className="text-primary">Pla</span>
                </span>
            </div>
        </>
    );
}
