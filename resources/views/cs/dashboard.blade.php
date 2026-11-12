@extends('layouts.cs')

@section('title', 'Dashboard CS Desk - JastipKuy')

@section('content')
<!-- Header Bar (Matches Admin Header Style) -->
<header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 lg:px-8 sticky top-0 z-20 shadow-xs">
    <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-xl bg-slate-900 text-white flex items-center justify-center shadow-xs">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
        </div>
        <div>
            <h1 class="font-extrabold text-sm text-slate-900">Dashboard Ruang Kerja CS</h1>
            <span class="text-[10px] text-slate-500 block -mt-0.5">Pusat Layanan Bantuan & Penyelesaian Sengketa Pesanan</span>
        </div>
    </div>

    <!-- Quick Status Bar & Actions -->
    <div class="flex items-center gap-3">
        <!-- Live status badge -->
        <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-xl border {{ $cs->work_status === 'tersedia' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : ($cs->work_status === 'sibuk' ? 'bg-amber-50 border-amber-200 text-amber-800' : 'bg-slate-100 border-slate-200 text-slate-700') }}">
            <span class="w-2 h-2 rounded-full {{ $cs->work_status === 'tersedia' ? 'bg-emerald-500' : ($cs->work_status === 'sibuk' ? 'bg-amber-500' : 'bg-slate-400') }}"></span>
            <span class="text-xs font-bold capitalize">Status: {{ $cs->work_status }}</span>
        </div>

        <form action="{{ route('cs.status.update') }}" method="POST" class="inline">
            @csrf
            <div class="inline-flex rounded-xl border border-slate-200 bg-slate-50 p-0.5 text-xs">
                <button type="submit" name="work_status" value="tersedia" 
                        class="px-2.5 py-1 rounded-lg font-semibold transition cursor-pointer {{ $cs->work_status === 'tersedia' ? 'bg-white text-emerald-800 shadow-2xs border border-slate-200' : 'text-slate-600 hover:text-slate-900' }}">
                    Tersedia
                </button>
                <button type="submit" name="work_status" value="sibuk" 
                        class="px-2.5 py-1 rounded-lg font-semibold transition cursor-pointer {{ $cs->work_status === 'sibuk' ? 'bg-white text-amber-800 shadow-2xs border border-slate-200' : 'text-slate-600 hover:text-slate-900' }}">
                    Sibuk
                </button>
                <button type="submit" name="work_status" value="istirahat" 
                        class="px-2.5 py-1 rounded-lg font-semibold transition cursor-pointer {{ $cs->work_status === 'istirahat' ? 'bg-white text-slate-800 shadow-2xs border border-slate-200' : 'text-slate-600 hover:text-slate-900' }}">
                    Rehat
                </button>
            </div>
        </form>
    </div>
</header>

<!-- Main Dashboard Body -->
<main class="flex-grow p-4 lg:p-6 space-y-4">

    <!-- Flash message -->
    @if(session('success'))
        <div class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center gap-2 shadow-2xs">
            <svg class="w-4 h-4 text-emerald-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- AUDIT ALERT BANNER: Clean and Professional Alert -->
    @if($stats['unassigned_chats'] > 0 || $stats['unassigned_reports'] > 0)
        <div id="cs-urgent-queue-alert" class="p-4 rounded-2xl bg-amber-50 border border-amber-200 shadow-xs flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div class="flex items-start gap-3">
                <div class="w-9 h-9 rounded-xl bg-amber-100 text-amber-800 border border-amber-200 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
                <div>
                    <h3 class="font-extrabold text-xs sm:text-sm text-slate-900">Perhatian: Ada Tiket & Chat Pelanggan Belum Ditangani</h3>
                    <p class="text-xs text-slate-600 mt-0.5">
                        Terdapat <strong class="text-amber-800 font-bold">{{ $stats['unassigned_chats'] }} chat bantuan</strong> dan <strong class="text-rose-700 font-bold">{{ $stats['unassigned_reports'] }} komplain sengketa</strong> yang sedang menunggu di antrean bebas. Segera klaim agar pelanggan tidak menunggu lama.
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                @if($stats['unassigned_chats'] > 0)
                    <a href="{{ route('cs.chats.index', ['tab' => 'queue']) }}" class="px-3.5 py-1.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold shadow-2xs transition">
                        Ambil Chat ({{ $stats['unassigned_chats'] }})
                    </a>
                @endif
                @if($stats['unassigned_reports'] > 0)
                    <a href="{{ route('cs.reports.index', ['tab' => 'queue']) }}" class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold shadow-2xs transition">
                        Ambil Komplain ({{ $stats['unassigned_reports'] }})
                    </a>
                @endif
            </div>
        </div>
    @endif

    <!-- 4 Key Monitoring Cards (Matches Admin Mutation & Dashboard KPI Grid) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Antrean Chat Bebas -->
        <div class="bg-white p-4 lg:p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-[9px] font-black uppercase tracking-wider text-amber-700 bg-amber-50 px-2 py-0.5 rounded-full border border-amber-200">
                    ANTREAN CHAT BEBAS
                </span>
                <h3 class="text-2xl font-black text-slate-900 mt-2">
                    {{ $stats['unassigned_chats'] }}
                </h3>
                <p class="text-[10px] text-slate-500 mt-0.5">Pelanggan belum mendapat CS</p>
            </div>
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center border border-amber-100 shadow-inner shrink-0">
                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
            </div>
        </div>

        <!-- Antrean Komplain Bebas -->
        <div class="bg-white p-4 lg:p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-[9px] font-black uppercase tracking-wider text-rose-700 bg-rose-50 px-2 py-0.5 rounded-full border border-rose-200">
                    ANTREAN SENGKETA
                </span>
                <h3 class="text-2xl font-black text-slate-900 mt-2">
                    {{ $stats['unassigned_reports'] }}
                </h3>
                <p class="text-[10px] text-slate-500 mt-0.5">Sengketa order butuh resolusi</p>
            </div>
            <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center border border-rose-100 shadow-inner shrink-0">
                <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
        </div>

        <!-- Chat Aktif Saya -->
        <div class="bg-white p-4 lg:p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-[9px] font-black uppercase tracking-wider text-slate-700 bg-slate-100 px-2 py-0.5 rounded-full border border-slate-200">
                    CHAT AKTIF SAYA
                </span>
                <h3 class="text-2xl font-black text-slate-900 mt-2">
                    {{ $stats['my_active_chats'] }}
                </h3>
                <p class="text-[10px] text-slate-500 mt-0.5">Sesi obrolan sedang ditangani</p>
            </div>
            <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center border border-slate-200 shadow-inner shrink-0">
                <svg class="w-5 h-5 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"></path></svg>
            </div>
        </div>

        <!-- Komplain Ditangani Saya -->
        <div class="bg-white p-4 lg:p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-[9px] font-black uppercase tracking-wider text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                    KOMPLAIN SAYA
                </span>
                <h3 class="text-2xl font-black text-slate-900 mt-2">
                    {{ $stats['my_active_reports'] }}
                </h3>
                <p class="text-[10px] text-slate-500 mt-0.5">Selesai: {{ $stats['my_resolved_reports'] }} kasus</p>
            </div>
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center border border-emerald-100 shadow-inner shrink-0">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
        </div>
    </div>

    <!-- 2 Column Section: Antrean Bebas vs Pekerjaan Aktif Saya -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        <!-- KIRI: Antrean Bebas (Menunggu Penanganan - Klaim 1-Klik) -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 space-y-4 flex flex-col">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h3 class="font-extrabold text-sm text-slate-900 flex items-center gap-2">
                        <span>Antrean Bebas Menunggu CS</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                            {{ $unassignedChats->count() + $unassignedReports->count() }} antrean
                        </span>
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">Klaim langsung untuk segera melayani pelanggan yang belum mendapatkan penanganan.</p>
                </div>
            </div>

            <!-- List of Unassigned Items -->
            <div class="space-y-3 flex-1 overflow-y-auto max-h-[420px] pr-1">
                @if($unassignedChats->isEmpty() && $unassignedReports->isEmpty())
                    <div class="text-center py-12 text-slate-400 space-y-2">
                        <div class="w-10 h-10 mx-auto rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-200 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                        <p class="text-xs font-bold text-slate-700">Tidak ada antrean tertunda</p>
                        <p class="text-[11px] text-slate-400">Seluruh chat dan komplain pelanggan telah tertangani dengan baik.</p>
                    </div>
                @else
                    <!-- Unassigned Chats -->
                    @foreach($unassignedChats as $chat)
                        <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50 hover:bg-white hover:border-slate-300 transition flex items-center justify-between gap-3 shadow-2xs">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-9 h-9 rounded-xl bg-slate-900 text-white font-bold text-xs flex items-center justify-center shrink-0 shadow-2xs">
                                    {{ strtoupper(substr($chat->customer?->name ?? 'C', 0, 2)) }}
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <h4 class="font-bold text-xs text-slate-900 truncate">{{ $chat->customer?->name ?? 'Pelanggan' }}</h4>
                                        <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-amber-50 text-amber-700 border border-amber-200">Live Chat</span>
                                    </div>
                                    <p class="text-[11px] text-slate-600 truncate mt-0.5">
                                        {{ $chat->customer?->supportMessages->first()?->message ?? 'Memulai chat baru...' }}
                                    </p>
                                    <span class="text-[10px] text-slate-400 font-mono">{{ $chat->last_message_at?->diffForHumans() ?? 'Baru saja' }}</span>
                                </div>
                            </div>
                            <form action="{{ route('cs.chats.claim', $chat->customer_id) }}" method="POST" class="shrink-0">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition shadow-xs cursor-pointer">
                                    Klaim Chat
                                </button>
                            </form>
                        </div>
                    @endforeach

                    <!-- Unassigned Reports -->
                    @foreach($unassignedReports as $rep)
                        <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50 hover:bg-white hover:border-slate-300 transition flex items-center justify-between gap-3 shadow-2xs">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-9 h-9 rounded-xl bg-rose-50 text-rose-700 border border-rose-200 font-bold text-xs flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <h4 class="font-bold text-xs text-slate-900 truncate">#REP-{{ $rep->id }} (Order #{{ $rep->order_id }})</h4>
                                        <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-rose-50 text-rose-700 border border-rose-200 uppercase">{{ $rep->category }}</span>
                                    </div>
                                    <p class="text-[11px] text-slate-600 truncate mt-0.5">
                                        {{ $rep->description }}
                                    </p>
                                    <span class="text-[10px] text-slate-400 font-mono">{{ $rep->created_at->diffForHumans() }}</span>
                                </div>
                            </div>
                            <form action="{{ route('cs.reports.claim', $rep->id) }}" method="POST" class="shrink-0">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold transition shadow-xs cursor-pointer">
                                    Klaim Sengketa
                                </button>
                            </form>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>

        <!-- KANAN: Pekerjaan Aktif Saya (Live Desk) -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 space-y-4 flex flex-col">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h3 class="font-extrabold text-sm text-slate-900 flex items-center gap-2">
                        <span>Pekerjaan Sedang Saya Tangani</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                            {{ $myActiveChats->count() + $myActiveReports->count() }} aktif
                        </span>
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">Lanjutkan obrolan bantuan atau selesaikan investigasi komplain.</p>
                </div>
            </div>

            <!-- List of My Active Workload -->
            <div class="space-y-3 flex-1 overflow-y-auto max-h-[420px] pr-1">
                @if($myActiveChats->isEmpty() && $myActiveReports->isEmpty())
                    <div class="text-center py-12 text-slate-400 space-y-2">
                        <div class="w-10 h-10 mx-auto rounded-xl bg-slate-100 text-slate-400 flex items-center justify-center">
                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"></path></svg>
                        </div>
                        <p class="text-xs font-bold text-slate-700">Tidak ada tiket aktif saat ini</p>
                        <p class="text-[11px] text-slate-400">Silakan klaim antrean di sebelah kiri atau tunggu pesan masuk dari pelanggan.</p>
                    </div>
                @else
                    <!-- My Active Chats -->
                    @foreach($myActiveChats as $chat)
                        <div class="p-3.5 rounded-xl border border-slate-200 bg-white hover:border-slate-300 transition flex items-center justify-between gap-3 shadow-2xs">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-9 h-9 rounded-xl bg-slate-800 text-white font-bold text-xs flex items-center justify-center shrink-0">
                                    {{ strtoupper(substr($chat->customer?->name ?? 'C', 0, 2)) }}
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <h4 class="font-bold text-xs text-slate-900 truncate">{{ $chat->customer?->name ?? 'Customer' }}</h4>
                                        <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Sesi Aktif</span>
                                    </div>
                                    <p class="text-[11px] text-slate-600 truncate mt-0.5">
                                        {{ $chat->customer?->supportMessages->first()?->message ?? 'Percakapan berlangsung...' }}
                                    </p>
                                    <span class="text-[10px] text-slate-400 font-mono">{{ $chat->last_message_at?->diffForHumans() }}</span>
                                </div>
                            </div>
                            <a href="{{ route('cs.chats.index', ['customer_id' => $chat->customer_id]) }}" 
                               class="px-3.5 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition shadow-2xs shrink-0">
                                Buka Chat
                            </a>
                        </div>
                    @endforeach

                    <!-- My Active Reports -->
                    @foreach($myActiveReports as $rep)
                        <div class="p-3.5 rounded-xl border border-slate-200 bg-white hover:border-slate-300 transition flex items-center justify-between gap-3 shadow-2xs">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-700 border border-amber-200 font-bold text-xs flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <h4 class="font-bold text-xs text-slate-900 truncate">#REP-{{ $rep->id }} (Order #{{ $rep->order_id }})</h4>
                                        <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-amber-50 text-amber-700 border border-amber-200">Investigasi</span>
                                    </div>
                                    <p class="text-[11px] text-slate-600 truncate mt-0.5">
                                        {{ $rep->description }}
                                    </p>
                                    <span class="text-[10px] text-slate-400 font-mono">{{ $rep->created_at->diffForHumans() }}</span>
                                </div>
                            </div>
                            <a href="{{ route('cs.reports.show', $rep->id) }}" 
                               class="px-3.5 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition shadow-2xs shrink-0">
                                Investigasi
                            </a>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>

</main>
@endsection
