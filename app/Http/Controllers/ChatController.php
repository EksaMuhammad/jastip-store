<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Order;
use App\Services\ChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Controller khusus fitur Chat Personal per order. Dipisah dari DashboardController
 * (yang sudah ~1000 baris) supaya tidak makin menggemuk.
 *
 * Kedua method di sini (send & history) didaftarkan 2x di routes/web.php — sekali di
 * bawah middleware('auth:customer'), sekali di bawah middleware('auth:jastiper') — jadi
 * satu method yang sama harus bisa melayani kedua role. Role aktif ditentukan lewat
 * resolveActor(), bukan lewat 2 method terpisah customerX()/jastiperX() seperti pola
 * lama di DashboardController, karena logic kirim/ambil chat 100% sama untuk kedua sisi.
 */
class ChatController extends Controller
{
    public function __construct(private ChatService $chatService)
    {
    }

    /**
     * Kirim pesan baru (teks dan/atau foto) untuk sebuah order.
     *
     * POST /customer/orders/{id}/chat atau /jastiper/orders/{id}/chat
     */
    public function send(Request $request, $orderId)
    {
        [$role, $user] = $this->resolveActor();

        $order = Order::find($orderId);

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order tidak ditemukan.'], 404);
        }

        $accessError = $this->checkOrderAccess($order, $role, $user);
        if ($accessError) {
            return $accessError;
        }

        $validated = $request->validate([
            'message' => ['nullable', 'string', 'max:2000', 'required_without:attachment'],
            'attachment' => ['nullable', 'image', 'max:2048', 'required_without:message'], // Max 2MB, ikut pola reference_photo
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('chats/attachments', 'public');
        }

        $chat = $this->chatService->sendMessage(
            $order,
            $role,
            $user->id,
            (string) ($validated['message'] ?? ''),
            $attachmentPath
        );

        return response()->json([
            'success' => true,
            'message' => $this->formatChat($chat, $order, $role),
        ]);
    }

    /**
     * Ambil seluruh riwayat chat untuk sebuah order, urut waktu naik. Mengakses
     * endpoint ini otomatis menandai pesan LAWAN BICARA (bukan pesan sendiri)
     * sebagai sudah dibaca. Dipakai untuk render awal halaman/modal chat maupun
     * untuk polling berkala (Tahap 5, tiap ~4-5 detik).
     *
     * GET /customer/orders/{id}/chat atau /jastiper/orders/{id}/chat
     */
    public function history(Request $request, $orderId)
    {
        [$role, $user] = $this->resolveActor();

        $order = Order::find($orderId);

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order tidak ditemukan.'], 404);
        }

        $accessError = $this->checkOrderAccess($order, $role, $user);
        if ($accessError) {
            return $accessError;
        }

        // Tandai pesan lawan bicara (bukan dari $role sendiri) sebagai sudah dibaca,
        // karena endpoint ini dianggap "membuka chat" dari sisi $role.
        Chat::forOrder($order->id)->unreadFor($role)->update(['is_read' => true]);

        $chats = Chat::forOrder($order->id)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        return response()->json([
            'success' => true,
            'messages' => $chats->map(fn (Chat $chat) => $this->formatChat($chat, $order, $role))->values(),
        ]);
    }

    /**
     * Tentukan role yang sedang login (customer atau jastiper) beserta user-nya.
     * Route chat didaftarkan di 2 middleware group berbeda yang keduanya mengarah
     * ke method yang sama di controller ini, jadi hanya salah satu guard yang
     * akan ->check() bernilai true tergantung route mana yang dipanggil.
     *
     * @return array{0: string, 1: \Illuminate\Contracts\Auth\Authenticatable}
     */
    private function resolveActor(): array
    {
        if (Auth::guard('customer')->check()) {
            return ['customer', Auth::guard('customer')->user()];
        }

        if (Auth::guard('jastiper')->check()) {
            return ['jastiper', Auth::guard('jastiper')->user()];
        }

        abort(403, 'Tidak terautentikasi.');
    }

    /**
     * Guard kepemilikan order + guard status order layak-chat.
     *
     * Kepemilikan: customer/jastiper yang login harus memang jadi salah satu
     * pihak di order tsb (order->customer_id / order->jastiper_id cocok) —
     * mencegah orang mengintip/kirim chat ke order milik orang lain.
     *
     * Status: order minimal sudah 'deal' (jastiper_id sudah terkunci). Order
     * yang masih 'menunggu_tawaran'/'ada_tawaran' belum punya pairing 1:1,
     * jadi belum ada room chat yang valid untuk diakses.
     *
     * @return \Illuminate\Http\JsonResponse|null Null kalau lolos guard, JsonResponse kalau ditolak.
     */
    private function checkOrderAccess(Order $order, string $role, $user)
    {
        $isOwner = $role === 'customer'
            ? $order->customer_id === $user->id
            : $order->jastiper_id === $user->id;

        if (!$isOwner) {
            return response()->json(['success' => false, 'message' => 'Anda tidak memiliki akses ke chat order ini.'], 403);
        }

        if ($order->jastiper_id === null || in_array($order->status, ['menunggu_tawaran', 'ada_tawaran'])) {
            return response()->json(['success' => false, 'message' => 'Chat belum tersedia — order ini belum memiliki jastiper yang di-deal-kan.'], 409);
        }

        return null;
    }

    /**
     * Format 1 baris Chat jadi array JSON siap-pakai frontend.
     *
     * PENTING: sengaja TIDAK memanggil $chat->sender (relasi morphTo) sama sekali
     * di sini — lihat catatan di app/Models/Chat.php kenapa itu akan error untuk
     * baris sender_role = 'system' ('system' tidak ada di Relation::morphMap()).
     * Nama pengirim di-resolve manual dari relasi $order->customer / $order->jastiper.
     */
    private function formatChat(Chat $chat, Order $order, string $viewerRole): array
    {
        return [
            'id' => $chat->id,
            'sender_role' => $chat->sender_role,
            'sender_name' => $this->resolveSenderName($chat, $order),
            'is_mine' => $chat->sender_role === $viewerRole,
            'message_type' => $chat->message_type,
            'message' => $chat->message,
            'attachment_url' => $chat->attachment_path ? Storage::url($chat->attachment_path) : null,
            'action_type' => $chat->action_type,
            'is_read' => (bool) $chat->is_read,
            'created_at' => $chat->created_at->toIso8601String(),
        ];
    }

    private function resolveSenderName(Chat $chat, Order $order): string
    {
        return match ($chat->sender_role) {
            'system' => 'Sistem',
            'customer' => $order->customer->name ?? 'Customer',
            'jastiper' => $order->jastiper->name ?? 'Jastiper',
            default => '-',
        };
    }
}
