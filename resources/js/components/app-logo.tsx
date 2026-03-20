export default function AppLogo() {
    return (
        <>
            <div className="flex aspect-square size-8 items-center justify-center rounded-md overflow-hidden">
                <img
                    src="/icons/logo.png"
                    alt="PrePla Logo"
                    className="h-7 w-7"
                    style={{ objectFit: 'contain' }}
                />
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-none font-bold tracking-tight">
                    Pre<span className="text-primary">Pla</span>
                </span>
            </div>
        </>
    );
}
