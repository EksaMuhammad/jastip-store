@php
    $currentCs = Auth::guard('cs')->user();
    $unassignedChatsCount = \App\Models\SupportConversation::where('status', 'menunggu')->count();
    $unassignedReportsCount = \App\Models\Report::where('cs_claim_status', 'unassigned')->where('status', 'menunggu')->count();
@endphp

<!-- UNIFIED CUSTOMER SERVICE (CS) DESK SIDEBAR: Matches Admin Portal Tone -->
<aside class="w-72 bg-slate-950 text-slate-350 flex flex-col justify-between shrink-0 border-r border-slate-800 hidden lg:flex sticky top-0 h-screen select-none z-30">
    <div class="flex flex-col min-h-0 flex-1">
        <!-- Sidebar Header Brand -->
        <div class="h-16 flex items-center px-6 border-b border-slate-850 gap-3 shrink-0">
            <div class="w-8 h-8 bg-slate-800 border border-slate-700 rounded-xl flex items-center justify-center font-bold text-white text-xs shadow-xs">
                CS
            </div>
            <div class="min-w-0">
                <h3 class="font-bold text-sm text-white tracking-wide truncate">JastipKuy</h3>
                <span class="text-[9px] font-semibold text-slate-400 tracking-wider uppercase block -mt-0.5 font-mono">Helpdesk CS Portal</span>
            </div>
        </div>

        <!-- Profile & Live Work Status Box -->
        <div class="p-4 border-b border-slate-900 bg-slate-950 shrink-0">
            <div class="flex items-center gap-3">
                <div class="relative shrink-0">
                    <div class="w-10 h-10 bg-slate-850 border border-slate-750 rounded-full flex items-center justify-center font-bold text-white text-xs shadow-inner">
                        {{ strtoupper(substr($currentCs->name ?? 'CS', 0, 2)) }}
                    </div>
                    <span class="absolute bottom-0 right-0 w-3 h-3 rounded-full border-2 border-slate-950 {{ $currentCs->work_status === 'tersedia' ? 'bg-emerald-500' : ($currentCs->work_status === 'sibuk' ? 'bg-amber-500' : 'bg-slate-500') }}"></span>
                </div>
                <div class="min-w-0 flex-1">
                    <h4 class="font-bold text-xs text-white truncate">{{ $currentCs->name ?? 'Customer Service' }}</h4>
                    <span class="text-[10px] text-slate-400 block truncate font-mono">{{ $currentCs->email ?? 'cs@jastipkuy.com' }}</span>
                </div>
            </div>

            <!-- Quick Status Switcher Form -->
            <form action="{{ route('cs.status.update') }}" method="POST" class="mt-3 pt-3 border-t border-slate-900 flex items-center justify-between gap-2">
                @csrf
                <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Status:</span>
                <select name="work_status" onchange="this.form.submit()" 
                        class="text-[11px] font-bold bg-slate-900 text-slate-200 border border-slate-750 rounded-lg px-2.5 py-1 focus:ring-1 focus:ring-slate-500 focus:outline-none cursor-pointer">
                    <option value="tersedia" {{ $currentCs->work_status === 'tersedia' ? 'selected' : '' }}>Tersedia (Ready)</option>
                    <option value="sibuk" {{ $currentCs->work_status === 'sibuk' ? 'selected' : '' }}>Sibuk (Busy)</option>
                    <option value="istirahat" {{ $currentCs->work_status === 'istirahat' ? 'selected' : '' }}>Istirahat (Break)</option>
                </select>
            </form>
        </div>

        <!-- Sidebar Navigation Menu -->
        <nav class="p-3.5 space-y-4 overflow-y-auto flex-1 custom-scrollbar">

            <!-- SEGMEN UTAMA -->
            <div class="space-y-1">
                <span class="text-[9px] font-bold text-slate-500 uppercase tracking-wider block px-3 mb-1.5">Workspace CS</span>

                <!-- Dashboard CS -->
                <a href="{{ route('cs.dashboard') }}" 
                   class="flex items-center justify-between px-3 py-2 rounded-xl text-xs transition {{ request()->routeIs('cs.dashboard') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:text-white hover:bg-slate-850 font-medium' }}">
                    <div class="flex items-center gap-3">
                        <svg class="w-4 h-4 {{ request()->routeIs('cs.dashboard') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                        <span>Dashboard Overview</span>
                    </div>
                    @if(request()->routeIs('cs.dashboard'))
                        <span class="w-1.5 h-1.5 bg-slate-400 rounded-full"></span>
                    @endif
                </a>

                <!-- Live Chat Bantuan CS -->
                <a href="{{ route('cs.chats.index') }}" 
                   class="flex items-center justify-between px-3 py-2 rounded-xl text-xs transition {{ request()->routeIs('cs.chats*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:text-white hover:bg-slate-850 font-medium' }}">
                    <div class="flex items-center gap-3">
                        <svg class="w-4 h-4 {{ request()->routeIs('cs.chats*') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                        <span>Live Chat Bantuan</span>
                    </div>
                    @if($unassignedChatsCount > 0)
                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-amber-950 text-amber-400 border border-amber-800/80">
                            {{ $unassignedChatsCount }} antrean
                        </span>
                    @elseif(request()->routeIs('cs.chats*'))
                        <span class="w-1.5 h-1.5 bg-slate-400 rounded-full"></span>
                    @endif
                </a>

                <!-- Tiket Pengaduan & Sengketa Komplain -->
                <a href="{{ route('cs.reports.index') }}" 
                   class="flex items-center justify-between px-3 py-2 rounded-xl text-xs transition {{ request()->routeIs('cs.reports*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:text-white hover:bg-slate-850 font-medium' }}">
                    <div class="flex items-center gap-3">
                        <svg class="w-4 h-4 {{ request()->routeIs('cs.reports*') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        <span>Tiket Komplain & Sengketa</span>
                    </div>
                    @if($unassignedReportsCount > 0)
                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-rose-950 text-rose-400 border border-rose-800/80">
                            {{ $unassignedReportsCount }} bebas
                        </span>
                    @elseif(request()->routeIs('cs.reports*'))
                        <span class="w-1.5 h-1.5 bg-slate-400 rounded-full"></span>
                    @endif
                </a>
            </div>

            <!-- ZERO-NEGLECT AUDIT NOTICE (Matches Admin Style) -->
            <div class="p-3 bg-slate-900 border border-slate-800 rounded-xl space-y-1.5">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                    <span class="text-[10px] font-bold text-slate-300 uppercase tracking-wide">Protokol Penanganan CS</span>
                </div>
                <p class="text-[11px] text-slate-400 leading-relaxed">
                    Setiap chat & pengaduan baru diprioritaskan ke petugas CS yang berstatus free. Pastikan antrean terbuka segera ditindaklanjuti.
                </p>
            </div>

        </nav>
    </div>

    <!-- Sidebar Footer Action (Logout) -->
    <div class="p-3.5 border-t border-slate-900 bg-slate-950 shrink-0">
        <form action="{{ route('cs.logout') }}" method="POST">
            @csrf
            <button type="submit" class="w-full flex items-center gap-3 px-3 py-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-850 font-medium text-xs transition text-left cursor-pointer">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                <span>Keluar (Logout)</span>
            </button>
        </form>
    </div>
</aside>
