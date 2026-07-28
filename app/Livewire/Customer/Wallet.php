<?php

namespace App\Livewire\Customer;

use App\Models\Topup;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Wallet extends Component
{
    use WithPagination;

    public $amountToTopup = '';
    public $showTopupModal = false;

    // Default route/layout (karena bukan full page di routes/web.php)
    // Bisa digunakan lewat @livewire('customer.wallet')

    public function openTopupModal()
    {
        $this->amountToTopup = '';
        $this->showTopupModal = true;
    }

    public function processTopup(WalletService $walletService)
    {
        $this->validate([
            'amountToTopup' => 'required|numeric|min:10000',
        ], [
            'amountToTopup.required' => 'Nominal top up wajib diisi.',
            'amountToTopup.numeric' => 'Nominal harus berupa angka.',
            'amountToTopup.min' => 'Minimal top up adalah Rp10.000.',
        ]);

        $customer = Auth::guard('customer')->user();
        $wallet = $customer->wallet;

        // Bikin wallet kalau belum ada
        if (!$wallet) {
            $wallet = $customer->wallet()->create([
                'owner_role' => 'customer',
                'balance' => 0,
            ]);
        }

        try {
            // Untuk sementara kita default pakai QRIS agar mudah (tanpa pilih bank)
            // Idealnya ada UI pemilihan metode pembayaran.
            $topup = $walletService->initiateTopup($wallet, $this->amountToTopup, 'qris');
            
            $this->showTopupModal = false;
            
            // Redirect ke halaman simulasi pembayaran atau show QRIS modal
            session()->flash('success', 'Berhasil membuat tagihan Topup. Silakan selesaikan pembayaran.');
            return redirect()->route('customer.wallet.pay_topup', ['id' => $topup->id]);

        } catch (\Exception $e) {
            session()->flash('error', 'Gagal memproses top up: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $customer = Auth::guard('customer')->user();
        $wallet = $customer->wallet;
        $balance = $wallet ? $wallet->balance : 0;

        $transactions = [];
        if ($wallet) {
            $transactions = WalletTransaction::where('wallet_id', $wallet->id)
                ->latest()
                ->paginate(10);
        }

        return view('components.customer.⚡wallet', [
            'balance' => $balance,
            'transactions' => $transactions,
        ]);
    }
}
