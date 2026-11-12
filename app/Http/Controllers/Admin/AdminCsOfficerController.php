<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerService;
use App\Models\Report;
use App\Models\SupportConversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminCsOfficerController extends Controller
{
    /**
     * Tampilkan daftar petugas CS, metrik ketersediaan, dan monitoring live antrean.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');
        $statusFilter = $request->query('status', 'all');

        $query = CustomerService::withCount([
            'activeConversations',
            'activeReports',
        ]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        if ($statusFilter === 'online') {
            $query->where('is_online', true)->where('last_active_at', '>=', now()->subMinutes(10));
        } elseif ($statusFilter === 'tersedia') {
            $query->where('is_online', true)->where('work_status', 'tersedia');
        } elseif ($statusFilter === 'sibuk') {
            $query->where('is_online', true)->where('work_status', 'sibuk');
        } elseif ($statusFilter === 'offline') {
            $query->where(function ($q) {
                $q->where('is_online', false)
                  ->orWhereNull('last_active_at')
                  ->orWhere('last_active_at', '<', now()->subMinutes(10));
            });
        }

        $officers = $query->latest('id')->paginate(15)->withQueryString();

        // Metrik Ringkasan Monitoring
        $allOfficers = CustomerService::all();
        $totalCs = $allOfficers->count();
        $onlineCs = $allOfficers->filter(fn ($cs) => $cs->isReallyOnline())->count();
        $freeCs = $allOfficers->filter(fn ($cs) => $cs->isReallyOnline() && $cs->work_status === 'tersedia')->count();
        $busyCs = $allOfficers->filter(fn ($cs) => $cs->isReallyOnline() && $cs->work_status === 'sibuk')->count();
        $offlineCs = $totalCs - $onlineCs;

        // Antrean Customer yang Belum Ditangani (Agar Tidak Terlantar)
        $unassignedChatsCount = SupportConversation::where('status', 'menunggu')->count();
        $unassignedReportsCount = Report::where('status', 'menunggu')->whereNull('assigned_cs_id')->count();
        $activeChatsTotal = SupportConversation::where('status', 'aktif')->count();
        $activeReportsTotal = Report::where('status', 'menunggu')->count();

        return view('admin.cs.index', compact(
            'officers',
            'totalCs',
            'onlineCs',
            'freeCs',
            'busyCs',
            'offlineCs',
            'unassignedChatsCount',
            'unassignedReportsCount',
            'activeChatsTotal',
            'activeReportsTotal',
            'search',
            'statusFilter'
        ));
    }

    /**
     * Tambah akun petugas Customer Service baru.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:150|unique:customer_services,email',
            'password' => 'required|string|min:6',
            'phone_number' => 'nullable|string|max:20',
        ], [
            'name.required' => 'Nama petugas CS wajib diisi.',
            'email.required' => 'Email login CS wajib diisi.',
            'email.unique' => 'Email ini sudah terdaftar untuk petugas CS lain.',
            'password.required' => 'Password akun wajib diisi.',
            'password.min' => 'Password minimal 6 karakter.',
        ]);

        CustomerService::create([
            'name' => $request->name,
            'email' => $request->email,
            'password_hash' => Hash::make($request->password),
            'phone_number' => $request->phone_number,
            'is_active' => true,
            'is_online' => false,
            'work_status' => 'tersedia',
        ]);

        return redirect()->route('admin.cs.index')
            ->with('success', "Akun Petugas CS {$request->name} berhasil dibuat!");
    }

    /**
     * Update data akun petugas CS.
     */
    public function update(Request $request, $id)
    {
        $officer = CustomerService::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:150|unique:customer_services,email,' . $officer->id,
            'password' => 'nullable|string|min:6',
            'phone_number' => 'nullable|string|max:20',
            'work_status' => 'nullable|in:tersedia,sibuk,istirahat',
        ]);

        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'work_status' => $request->work_status ?? $officer->work_status,
        ];

        if ($request->filled('password')) {
            $updateData['password_hash'] = Hash::make($request->password);
        }

        $officer->update($updateData);

        return redirect()->route('admin.cs.index')
            ->with('success', "Data akun CS {$officer->name} berhasil diperbarui!");
    }

    /**
     * Toggle status aktif/nonaktif akun CS.
     */
    public function toggleActive($id)
    {
        $officer = CustomerService::findOrFail($id);
        $newStatus = !$officer->is_active;
        $officer->update([
            'is_active' => $newStatus,
            'is_online' => $newStatus ? $officer->is_online : false,
        ]);

        $msg = $newStatus 
            ? "Akun CS {$officer->name} telah diaktifkan kembali." 
            : "Akun CS {$officer->name} telah dinonaktifkan.";

        return redirect()->route('admin.cs.index')->with('success', $msg);
    }

    /**
     * Hapus (soft delete) akun CS.
     */
    public function destroy($id)
    {
        $officer = CustomerService::findOrFail($id);
        $name = $officer->name;
        $officer->delete();

        return redirect()->route('admin.cs.index')
            ->with('success', "Akun CS {$name} berhasil dihapus.");
    }

    /**
     * API Polling untuk monitoring live status CS di panel admin.
     */
    public function monitoringApi()
    {
        $officers = CustomerService::withCount(['activeConversations', 'activeReports'])
            ->get()
            ->map(function ($cs) {
                return [
                    'id' => $cs->id,
                    'name' => $cs->name,
                    'is_online' => $cs->isReallyOnline(),
                    'work_status' => $cs->work_status,
                    'active_chats' => $cs->active_conversations_count,
                    'active_reports' => $cs->active_reports_count,
                    'last_active' => $cs->last_active_at ? $cs->last_active_at->diffForHumans() : 'Belum aktif',
                ];
            });

        $unassignedChats = SupportConversation::where('status', 'menunggu')->count();
        $unassignedReports = Report::where('status', 'menunggu')->whereNull('assigned_cs_id')->count();

        return response()->json([
            'success' => true,
            'timestamp' => now()->toIso8601String(),
            'metrics' => [
                'total_cs' => $officers->count(),
                'online_cs' => $officers->filter(fn($c) => $c['is_online'])->count(),
                'free_cs' => $officers->filter(fn($c) => $c['is_online'] && $c['work_status'] === 'tersedia')->count(),
                'busy_cs' => $officers->filter(fn($c) => $c['is_online'] && $c['work_status'] === 'sibuk')->count(),
                'offline_cs' => $officers->filter(fn($c) => !$c['is_online'])->count(),
                'unassigned_chats' => $unassignedChats,
                'unassigned_reports' => $unassignedReports,
            ],
            'officers' => $officers,
        ]);
    }
}
