@php
    $colors = [
        'bronze' => 'bg-amber-700 text-amber-50 border-amber-800',
        'silver' => 'bg-slate-400 text-slate-900 border-slate-500',
        'gold' => 'bg-amber-400 text-amber-900 border-amber-500',
        'platinum' => 'bg-cyan-300 text-cyan-900 border-cyan-400'
    ];
    $badgeColor = $colors[strtolower($level)] ?? $colors['bronze'];
    $icon = match(strtolower($level)) {
        'platinum' => '💎',
        'gold' => '👑',
        'silver' => '🛡️',
        default => '🥉',
    };
@endphp

@if($compact)
    <span class="inline-flex items-center gap-1 {{ $badgeColor }} border px-1.5 py-0.5 rounded text-[8px] font-black uppercase tracking-wider" title="Jastiper {{ ucfirst($level) }}">
        <span>{{ $icon }}</span>
        <span>{{ $level }}</span>
    </span>
@else
    <div class="inline-flex items-center gap-1.5 {{ $badgeColor }} border px-2 py-0.5 rounded-full shadow-sm" title="Jastiper {{ ucfirst($level) }}">
        <span class="text-[10px]">{{ $icon }}</span>
        <span class="text-[9px] font-black uppercase tracking-wider">{{ $level }}</span>
        @if($rating !== null)
            <span class="text-[9px] font-bold border-l border-current pl-1.5 ml-0.5 flex items-center gap-0.5 opacity-90">
                <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                {{ number_format($rating, 1) }}
            </span>
        @endif
    </div>
@endif
