export default function AppLogo() {
    return (
        <>
            <div className="flex aspect-square size-9 items-center justify-center rounded-lg overflow-hidden shrink-0"
                style={{ background: 'linear-gradient(135deg, #1A2B48 0%, #2a3f6a 100%)' }}>
                <img
                    src="/icons/logo.png?v=4"
                    alt="PrePla Logo"
                    className="h-7 w-7"
                    style={{ objectFit: 'contain', filter: 'brightness(0) saturate(100%) invert(100%)' }}
                />
            </div>
            <div className="ml-1 grid flex-1 text-left leading-tight">
                <span className="truncate font-bold tracking-tight" style={{ fontSize: '0.95rem', letterSpacing: '-0.01em' }}>
                    Pre<span style={{ color: '#4A90E2' }}>Pla</span>
                    <span style={{
                        marginLeft: '0.3rem',
                        padding: '0.1rem 0.3rem',
                        borderRadius: '0.2rem',
                        fontSize: '0.5rem',
                        fontWeight: 700,
                        letterSpacing: '0.08em',
                        textTransform: 'uppercase' as const,
                        background: 'rgba(74,144,226,0.12)',
                        color: '#4A90E2',
                    }}>IA</span>
                </span>
            </div>
        </>
    );
}
