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
    public $paymentMethod = 'bank_transfer_va'; // 'bank_transfer_va' atau 'qris'

    // Default route/layout (karena bukan full page di routes/web.php)
    // Bisa digunakan lewat @livewire('customer.wallet')

    public function openTopupModal()
    {
        $this->amountToTopup = '';
        $this->paymentMethod = 'bank_transfer_va';
        $this->showTopupModal = true;
    }

    public function processTopup(WalletService $walletService)
    {
        $minTopup = (int) app(\App\Services\TariffService::class)->get('min_topup_amount', 10000);

        $this->validate([
            'amountToTopup' => 'required|numeric|min:' . $minTopup,
            'paymentMethod' => 'required|in:bank_transfer_va,qris',
        ], [
            'amountToTopup.required' => 'Nominal top up wajib diisi.',
            'amountToTopup.numeric' => 'Nominal harus berupa angka.',
            'amountToTopup.min' => 'Minimal top up adalah Rp' . number_format($minTopup, 0, ',', '.') . '.',
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
            $channel = $this->paymentMethod === 'bank_transfer_va' ? 'bank_transfer_va' : 'qris';
            $bank = $channel === 'bank_transfer_va' ? 'bca' : null;

            $topup = $walletService->initiateTopup($wallet, $this->amountToTopup, $channel, $bank);
            
            $this->showTopupModal = false;
            
            $methodName = $channel === 'bank_transfer_va' ? 'BCA Virtual Account' : 'QRIS';
            session()->flash('success', "Tiket pembayaran Top Up ({$methodName}) berhasil dibuat! Silakan selesaikan pembayaran via Midtrans.");
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
