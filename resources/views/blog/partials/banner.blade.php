@php
/**
 * Original inline-SVG banner per article theme — no external/stock images
 * (licensing risk on a commercial site), just simple geometric glyphs coded
 * directly here. $theme: canada | ielts | france | germany | method.
 * $size: 'full' (article header) or 'thumb' (index card).
 */
$theme = $theme ?? 'method';
$size = $size ?? 'full';
$h = $size === 'full' ? 148 : 84;
$iconSize = $size === 'full' ? 56 : 36;
@endphp
<div style="height:{{ $h }}px; border-radius:1rem; margin-bottom:{{ $size === 'full' ? '1.5rem' : '1rem' }}; overflow:hidden; position:relative; background:linear-gradient(135deg,#13233f,#1A2B48 55%,#3B82E0); display:flex; align-items:center; justify-content:center;">
    {{-- subtle dot texture --}}
    <div style="position:absolute; inset:0; opacity:.15; background-image:radial-gradient(#fff 1px, transparent 1px); background-size:14px 14px;"></div>

    <div style="position:relative; width:{{ $iconSize }}px; height:{{ $iconSize }}px;">
        @switch($theme)
            @case('canada')
                {{-- Maple leaf --}}
                <svg viewBox="0 0 64 64" width="100%" height="100%" fill="#e8453c" stroke="#fff" stroke-width="1.5" stroke-linejoin="round">
                    <path d="M32 4 L36 16 L46 11 L43 21 L54 22 L45 29 L52 37 L41 36 L42 47 L34 40 L32 60 L30 40 L22 47 L23 36 L12 37 L19 29 L10 22 L21 21 L18 11 L28 16 Z" />
                </svg>
                @break
            @case('france')
                {{-- Pennant flag, bleu-blanc-rouge --}}
                <svg viewBox="0 0 64 64" width="100%" height="100%">
                    <path d="M8 6 H56 L34 32 L56 58 H8 Z" fill="#fff" stroke="#fff" stroke-width="2"/>
                    <path d="M8 6 H24 V58 H8 Z" fill="#002395"/>
                    <path d="M40 6 H56 L34 32 L56 58 H40 Z" fill="#ED2939"/>
                </svg>
                @break
            @case('germany')
                {{-- Pennant flag, noir-rouge-or --}}
                <svg viewBox="0 0 64 64" width="100%" height="100%">
                    <path d="M8 6 H56 L34 32 L56 58 H8 Z" fill="#FFCE00"/>
                    <path d="M8 6 H56 L46 22 H8 Z" fill="#000"/>
                    <path d="M8 42 H49 L56 58 H8 Z" fill="#DD0000"/>
                </svg>
                @break
            @case('ielts')
                {{-- Globe --}}
                <svg viewBox="0 0 64 64" width="100%" height="100%" fill="none" stroke="#fff" stroke-width="2.5">
                    <circle cx="32" cy="32" r="26"/>
                    <ellipse cx="32" cy="32" rx="11" ry="26"/>
                    <line x1="6" y1="32" x2="58" y2="32"/>
                    <line x1="9" y1="19" x2="55" y2="19"/>
                    <line x1="9" y1="45" x2="55" y2="45"/>
                </svg>
                @break
            @case('toefl')
                {{-- Laptop (TOEFL iBT is fully computer-based) --}}
                <svg viewBox="0 0 64 64" width="100%" height="100%" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="12" y="12" width="40" height="28" rx="2"/>
                    <line x1="6" y1="50" x2="58" y2="50"/>
                    <line x1="24" y1="50" x2="40" y2="50" stroke="#F5A623"/>
                </svg>
                @break
            @case('speaking')
                {{-- Microphone (pronunciation/oral articles) --}}
                <svg viewBox="0 0 64 64" width="100%" height="100%" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="24" y="6" width="16" height="28" rx="8"/>
                    <path d="M16 28 C16 40 24 46 32 46 C40 46 48 40 48 28"/>
                    <line x1="32" y1="46" x2="32" y2="58"/>
                    <line x1="20" y1="58" x2="44" y2="58"/>
                </svg>
                @break
            @case('letter')
                {{-- Envelope (motivation letter / visa application articles) --}}
                <svg viewBox="0 0 64 64" width="100%" height="100%" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="8" y="14" width="48" height="36" rx="3"/>
                    <path d="M8 16 L32 38 L56 16"/>
                </svg>
                @break
            @case('resources')
                {{-- Open book (free resources / reading lists) --}}
                <svg viewBox="0 0 64 64" width="100%" height="100%" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M32 14 C26 9 16 8 8 10 V46 C16 44 26 45 32 50 C38 45 48 44 56 46 V10 C48 8 38 9 32 14 Z"/>
                    <line x1="32" y1="14" x2="32" y2="50"/>
                </svg>
                @break
            @case('budget')
                {{-- Coin (cost / budgeting articles) --}}
                <svg viewBox="0 0 64 64" width="100%" height="100%" fill="none" stroke="#F5A623" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="32" cy="32" r="24"/>
                    <line x1="32" y1="20" x2="32" y2="44"/>
                    <path d="M26 24 h8 a5 5 0 0 1 0 10 h-6 a5 5 0 0 0 0 10 h9"/>
                </svg>
                @break
            @default
                {{-- Hourglass (method/time articles) --}}
                <svg viewBox="0 0 64 64" width="100%" height="100%" fill="none" stroke="#F5A623" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 6 H50 M14 58 H50 M16 6 C16 24 32 26 32 32 C32 26 48 24 48 6 M16 58 C16 40 32 38 32 32 C32 38 48 40 48 58"/>
                </svg>
        @endswitch
    </div>
</div>
