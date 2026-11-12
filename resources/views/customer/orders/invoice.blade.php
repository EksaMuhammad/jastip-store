<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota Transaksi #ORD-{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }} - JastipKuy Official</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, sans-serif;
            background-color: #f8fafc;
            color: #0f172a;
        }
        @media print {
            body {
                background-color: #ffffff;
                color: #000000;
                padding: 0 !important;
            }
            .no-print {
                display: none !important;
            }
            .print-shadow-none {
                box-shadow: none !important;
                border: 1px solid #cbd5e1 !important;
            }
            @page {
                size: A4;
                margin: 10mm;
            }
        }
        .stamp-lunas {
            border: 3px double #059669;
            color: #059669;
            background-color: rgba(16, 185, 129, 0.06);
            transform: rotate(-5deg);
        }
    </style>
</head>
<body class="py-6 px-3 sm:px-6">

    <!-- Action Toolbar (Hidden in Print) -->
    <div class="max-w-3xl mx-auto mb-5 flex flex-wrap items-center justify-between gap-3 no-print">
        <div class="flex items-center gap-2">
            @if(Auth::guard('customer')->check() && Auth::guard('customer')->id() === $order->customer_id)
                <a href="{{ route('customer.orders.show', $order->id) }}" class="inline-flex items-center gap-2 px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs rounded-xl border border-slate-200 transition shadow-xs">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    <span>Kembali ke Detail Pesanan</span>
                </a>
            @else
                <div class="inline-flex items-center gap-2 px-3.5 py-2 bg-white text-slate-800 font-extrabold text-xs rounded-xl border border-slate-200 shadow-xs">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span>JastipKuy Official • Bukti Transaksi Resmi</span>
                </div>
            @endif
        </div>

        <div class="flex items-center gap-2">
            <!-- Tombol Bagikan Nota -->
            <button type="button" onclick="shareReceipt()" class="inline-flex items-center gap-1.5 px-4 py-2 bg-white hover:bg-slate-50 active:scale-95 text-slate-700 font-extrabold text-xs rounded-xl border border-slate-200 transition shadow-xs cursor-pointer">
                <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path>
                </svg>
                <span>Bagikan</span>
            </button>

            <!-- Tombol Cetak / PDF -->
            <button type="button" onclick="window.print()" class="inline-flex items-center gap-1.5 px-4 py-2 bg-rose-600 hover:bg-rose-700 active:scale-95 text-white font-extrabold text-xs rounded-xl transition shadow-md shadow-rose-600/20 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                <span>Cetak / PDF</span>
            </button>
        </div>
    </div>

    <!-- Toast Notification for Copy Share -->
    <div id="share-toast" class="hidden fixed top-5 left-1/2 -translate-x-1/2 z-50 bg-slate-900/95 backdrop-blur text-white text-xs font-bold px-4 py-2.5 rounded-full shadow-2xl flex items-center gap-2 border border-slate-700 transition duration-300">
        <svg class="w-4 h-4 text-emerald-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
        <span id="share-toast-msg">Tautan nota berhasil disalin!</span>
    </div>

    <!-- Official Invoice Document Sheet -->
    @include('customer.orders.partials.invoice-sheet', [
        'order' => $order,
        'customer' => $customer ?? $order->customer,
        'latestPayment' => $latestPayment ?? $order->payments->first()
    ])

    <script>
        function showToast(msg) {
            const toast = document.getElementById('share-toast');
            const toastMsg = document.getElementById('share-toast-msg');
            toastMsg.textContent = msg;
            toast.classList.remove('hidden');
            setTimeout(() => {
                toast.classList.add('hidden');
            }, 3000);
        }

        function shareReceipt() {
            const shareUrl = '{{ $order->public_receipt_url }}';
            const shareData = {
                title: 'Nota Transaksi JastipKuy #{{ $order->id }}',
                text: 'Berikut nota transaksi resmi pesanan JastipKuy #{{ $order->id }}',
                url: shareUrl
            };

            if (navigator.share) {
                navigator.share(shareData).catch(err => {
                    if (err.name !== 'AbortError') {
                        fallbackCopy(shareUrl);
                    }
                });
            } else {
                fallbackCopy(shareUrl);
            }
        }

        function fallbackCopy(url) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(() => {
                    showToast('Tautan nota berhasil disalin ke clipboard!');
                }).catch(() => {
                    prompt('Salin tautan nota berikut:', url);
                });
            } else {
                const textarea = document.createElement('textarea');
                textarea.value = url;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                showToast('Tautan nota berhasil disalin!');
            }
        }

        @if(request('print'))
        window.addEventListener('load', () => {
            setTimeout(() => window.print(), 400);
        });
        @endif
    </script>
</body>
</html>
