<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Jastiper;
use App\Models\Wilayah;
use App\Models\WithdrawRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Services\WhatsAppService;

class AuthApiController extends Controller
{
    /**
     * Normalisasi nomor HP (08xxx)
     */
    private function normalizePhone(string $phone): string
    {
        $cleaned = preg_replace('/\D/', '', $phone);
        if (str_starts_with($cleaned, '62')) {
            $cleaned = '0' . substr($cleaned, 2);
        }
        if (str_starts_with($cleaned, '8') && !str_starts_with($cleaned, '08')) {
            $cleaned = '0' . $cleaned;
        }
        return $cleaned;
    }

    /**
     * Cek apakah nomor HP sudah terdaftar.
     */
    public function checkPhone(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string',
            'role' => 'required|in:customer,jastiper',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $phone = $this->normalizePhone($request->input('phone_number'));
        $role = $request->input('role');

        $exists = ($role === 'customer') 
            ? Customer::where('phone_number', $phone)->exists()
            : Jastiper::where('phone_number', $phone)->exists();

        return response()->json([
            'success' => true,
            'exists' => $exists,
            'phone_number' => $phone
        ]);
    }

    /**
     * Login menggunakan kata sandi (password).
     */
    public function loginPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string',
            'role' => 'required|in:customer,jastiper',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $phone = $this->normalizePhone($request->input('phone_number'));
        $role = $request->input('role');
        $password = $request->input('password');

        $user = ($role === 'customer')
            ? Customer::where('phone_number', $phone)->first()
            : Jastiper::where('phone_number', $phone)->first();

        if (!$user || !$user->password || !Hash::check($password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Kata sandi atau nomor HP salah.'
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone_number' => $user->phone_number,
                'role' => $role
            ]
        ]);
    }

    /**
     * Registrasi pengguna baru + kirim OTP.
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string',
            'role' => 'required|in:customer,jastiper',
            'name' => 'required|string|min:3|max:50',
            'password' => 'required|string|min:6',
            'wilayah_id' => 'required_if:role,jastiper|exists:wilayah,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $phone = $this->normalizePhone($request->input('phone_number'));
        $role = $request->input('role');

        // Cek duplikasi
        $exists = ($role === 'customer')
            ? Customer::where('phone_number', $phone)->exists()
            : Jastiper::where('phone_number', $phone)->exists();

        if ($exists) {
            return response()->json(['success' => false, 'message' => 'Nomor HP sudah terdaftar.'], 400);
        }

        // Generate OTP
        $otp = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $otpExpires = now()->addMinutes(5);

        if ($role === 'customer') {
            $user = Customer::create([
                'phone_number' => $phone,
                'name' => $request->input('name'),
                'password' => Hash::make($request->input('password')),
                'otp_code' => $otp,
                'otp_expires_at' => $otpExpires
            ]);
            // Provision wallet with Rp 1.000.000 starting balance
            $user->wallet()->create(['balance' => 1000000]);
        } else {
            $user = Jastiper::create([
                'phone_number' => $phone,
                'name' => $request->input('name'),
                'password' => Hash::make($request->input('password')),
                'otp_code' => $otp,
                'otp_expires_at' => $otpExpires,
                'wilayah_id' => $request->input('wilayah_id'),
                'radius_km' => 5.00,
                'is_available' => true,
                'verification_status' => 'approved', // Auto approve for testing
                'work_status' => 'offline'
            ]);
            // Provision wallet with Rp 1.000.000 starting balance
            $user->wallet()->create(['balance' => 1000000]);
        }

        // Send simulation WA
        $this->sendSimulationWa($phone, $user->name, $otp);

        return response()->json([
            'success' => true,
            'message' => 'Registrasi berhasil. OTP telah dikirim.',
            'debug_otp' => $otp
        ]);
    }

    /**
     * Kirim/Kirim Ulang OTP.
     */
    public function sendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string',
            'role' => 'required|in:customer,jastiper',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $phone = $this->normalizePhone($request->input('phone_number'));
        $role = $request->input('role');

        $user = ($role === 'customer')
            ? Customer::where('phone_number', $phone)->first()
            : Jastiper::where('phone_number', $phone)->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Pengguna tidak ditemukan.'], 404);
        }

        $otp = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $user->otp_code = $otp;
        $user->otp_expires_at = now()->addMinutes(5);
        $user->save();

        $this->sendSimulationWa($phone, $user->name, $otp);

        return response()->json([
            'success' => true,
            'message' => 'OTP berhasil dikirim.',
            'debug_otp' => $otp
        ]);
    }

    /**
     * Verifikasi OTP untuk login.
     */
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string',
            'role' => 'required|in:customer,jastiper',
            'otp_code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $phone = $this->normalizePhone($request->input('phone_number'));
        $role = $request->input('role');
        $otp = $request->input('otp_code');

        $user = ($role === 'customer')
            ? Customer::where('phone_number', $phone)->first()
            : Jastiper::where('phone_number', $phone)->first();

        if (!$user || $user->otp_code !== $otp || !$user->otp_expires_at || $user->otp_expires_at->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'Kode verifikasi OTP salah atau sudah kedaluwarsa.'
            ], 400);
        }

        // Clear OTP
        $user->otp_code = null;
        $user->otp_expires_at = null;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Verifikasi OTP berhasil.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone_number' => $user->phone_number,
                'role' => $role
            ]
        ]);
    }

    private function sendSimulationWa($phone, $name, $otp)
    {
        $msg = "Halo *{$name}*!\n\nKode verifikasi OTP JastipKuy Anda adalah:\n\n*{$otp}*\n\nKode ini berlaku selama 5 menit. Jangan bagikan kode ini kepada siapa pun! 🔐";
        try {
            WhatsAppService::sendMessage($phone, $msg);
        } catch (\Exception $e) {
            // Ignore WA gateway errors in testing
        }
    }

    public function jastiperEarnings(Request $request)
    {
        $jastiperId = $request->query('jastiper_id');
        $jastiper = Jastiper::find($jastiperId);
        if (!$jastiper) {
            return response()->json(['success' => false, 'message' => 'Jastiper tidak ditemukan.'], 404);
        }

        $period = $request->query('period', 'harian');

        $baseQuery = DB::table('komisi')
            ->join('orders', 'komisi.order_id', '=', 'orders.id')
            ->where('orders.jastiper_id', $jastiper->id)
            ->whereNull('komisi.deleted_at');

        if ($period === 'mingguan') {
            $rows = (clone $baseQuery)
                ->selectRaw("YEARWEEK(komisi.created_at, 3) as period_key, MIN(DATE(komisi.created_at)) as period_start, SUM(komisi.gross_amount) as gross_amount, SUM(komisi.commission_amount) as commission_amount, SUM(komisi.net_amount) as net_amount, COUNT(*) as orders_count")
                ->groupBy('period_key')
                ->orderByDesc('period_key')
                ->limit(8)
                ->get();

            $breakdown = $rows->map(function ($row) {
                return [
                    'label' => 'Minggu ' . \Carbon\Carbon::parse($row->period_start)->format('d/m'),
                    'gross_amount' => (float) $row->gross_amount,
                    'commission_amount' => (float) $row->commission_amount,
                    'net_amount' => (float) $row->net_amount,
                    'orders_count' => (int) $row->orders_count,
                ];
            })->values();
        } else {
            $rows = (clone $baseQuery)
                ->selectRaw('DATE(komisi.created_at) as period_key, SUM(komisi.gross_amount) as gross_amount, SUM(komisi.commission_amount) as commission_amount, SUM(komisi.net_amount) as net_amount, COUNT(*) as orders_count')
                ->groupBy('period_key')
                ->orderByDesc('period_key')
                ->limit(14)
                ->get();

            $breakdown = $rows->map(function ($row) {
                return [
                    'label' => \Carbon\Carbon::parse($row->period_key)->format('d/m/Y'),
                    'gross_amount' => (float) $row->gross_amount,
                    'commission_amount' => (float) $row->commission_amount,
                    'net_amount' => (float) $row->net_amount,
                    'orders_count' => (int) $row->orders_count,
                ];
            })->values();
        }

        $wallet = $jastiper->wallet;
        $saldo = $wallet ? (float) $wallet->balance : 0.0;

        $pendingWithdraw = WithdrawRequest::where('jastiper_id', $jastiper->id)
            ->where('status', 'menunggu')
            ->latest()
            ->first();

        $recentWithdraws = WithdrawRequest::where('jastiper_id', $jastiper->id)
            ->latest()
            ->limit(5)
            ->get(['id', 'amount', 'status', 'admin_note', 'created_at', 'processed_at']);

        return response()->json([
            'success' => true,
            'period' => $period,
            'saldo' => $saldo,
            'breakdown' => $breakdown,
            'total_net' => $breakdown->sum('net_amount'),
            'pending_withdraw' => $pendingWithdraw,
            'recent_withdraws' => $recentWithdraws,
        ]);
    }

    public function jastiperWithdraw(Request $request)
    {
        $jastiperId = $request->input('jastiper_id');
        $jastiper = Jastiper::find($jastiperId);
        if (!$jastiper) {
            return response()->json(['success' => false, 'message' => 'Jastiper tidak ditemukan.'], 404);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'bank_name' => 'required|string|max:100',
            'bank_account_number' => 'required|string|max:50',
            'bank_account_holder' => 'required|string|max:150',
        ]);

        try {
            $withdrawService = app(\App\Services\WithdrawService::class);
            $withdrawRequest = $withdrawService->requestWithdraw(
                $jastiper,
                (float) $validated['amount'],
                $validated['bank_name'],
                $validated['bank_account_number'],
                $validated['bank_account_holder']
            );
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan withdraw berhasil dikirim, tunggu persetujuan admin.',
            'withdraw_request' => $withdrawRequest
        ]);
    }
}
