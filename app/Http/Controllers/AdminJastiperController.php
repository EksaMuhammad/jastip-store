<?php

namespace App\Http\Controllers;

use App\Models\Jastiper;
use App\Models\Wilayah;
use Illuminate\Http\Request;

class AdminJastiperController extends Controller
{
    /**
     * Menampilkan daftar data mitra Jastiper (Driver).
     */
    public function index(Request $request)
    {
        $search = trim($request->query('search', ''));
        $workStatus = $request->query('work_status', 'all');
        $verificationStatus = $request->query('verification_status', 'all');
        $wilayahId = $request->query('wilayah_id', 'all');

        $query = Jastiper::with(['wilayah', 'wallet', 'badge'])
            ->withCount([
                'orders as total_orders_count',
                'orders as completed_orders_count' => function ($q) {
                    $q->where('status', 'selesai');
                },
                'orders as active_orders_count' => function ($q) {
                    $q->whereNotIn('status', ['selesai', 'dibatalkan', 'bermasalah']);
                },
                'ratings'
            ])
            ->withAvg('ratings', 'rating')
            ->latest();

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%")
                  ->orWhere('checkin_location', 'like', "%{$search}%")
                  ->orWhere('id', 'like', "%{$search}%");
            });
        }

        if ($workStatus !== 'all' && in_array($workStatus, ['tersedia', 'standby', 'offline'], true)) {
            $query->where('work_status', $workStatus);
        }

        if ($verificationStatus !== 'all' && in_array($verificationStatus, ['approved', 'pending', 'rejected'], true)) {
            $query->where('verification_status', $verificationStatus);
        }

        if ($wilayahId !== 'all' && !empty($wilayahId)) {
            $query->where('wilayah_id', $wilayahId);
        }

        $jastipers = $query->paginate(15)->withQueryString();

        $wilayahList = Wilayah::all();
        $totalJastiperCount = Jastiper::count();
        $totalApprovedCount = Jastiper::where('verification_status', 'approved')->count();
        $totalOnlineCount = Jastiper::where('verification_status', 'approved')
            ->whereIn('work_status', ['tersedia', 'standby'])
            ->count();

        return view('admin.jastipers.index', compact(
            'jastipers',
            'wilayahList',
            'search',
            'workStatus',
            'verificationStatus',
            'wilayahId',
            'totalJastiperCount',
            'totalApprovedCount',
            'totalOnlineCount'
        ));
    }
}
