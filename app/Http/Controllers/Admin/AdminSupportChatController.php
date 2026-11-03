<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\SupportMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminSupportChatController extends Controller
{
    /**
     * Dashboard Live Chat Bantuan & Customer Support di Panel Admin.
     */
    public function index(Request $request)
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin) {
            return redirect()->route('admin.login')->with('error', 'Silakan login sebagai admin.');
        }

        $search = $request->query('search');
        $selectedCustomerId = $request->query('customer_id');

        // Ambil daftar percakapan pelanggan yang pernah mengirim/menerima pesan
        $conversations = $this->getConversationList($search);

        // Jika belum ada customer_id terpilih, pilih percakapan teratas
        if (!$selectedCustomerId && $conversations->isNotEmpty()) {
            $selectedCustomerId = $conversations->first()['customer_id'];
        }

        $selectedCustomer = null;
        $messages = collect();
        $customerOrders = collect();

        if ($selectedCustomerId) {
            $selectedCustomer = Customer::with('wallet')->find($selectedCustomerId);

            if ($selectedCustomer) {
                // Tandai pesan dari customer ini sebagai dibaca
                SupportMessage::where('customer_id', $selectedCustomerId)
                    ->where('sender_type', 'customer')
                    ->where('is_read', false)
                    ->update(['is_read' => true]);

                $messages = SupportMessage::where('customer_id', $selectedCustomerId)
                    ->with(['admin', 'order'])
                    ->orderBy('created_at', 'asc')
                    ->get();

                $customerOrders = Order::where('customer_id', $selectedCustomerId)
                    ->latest()
                    ->limit(5)
                    ->get();
            }
        }

        $totalUnreadAll = SupportMessage::unreadForAdmin()->count();

        return view('admin.support.index', compact(
            'conversations',
            'selectedCustomer',
            'selectedCustomerId',
            'messages',
            'customerOrders',
            'totalUnreadAll',
            'search'
        ));
    }

    /**
     * API: Daftar percakapan customer untuk polling sidebar admin.
     */
    public function fetchConversations(Request $request)
    {
        $search = $request->query('search');
        $conversations = $this->getConversationList($search);

        return response()->json([
            'success' => true,
            'conversations' => $conversations,
            'total_unread' => SupportMessage::unreadForAdmin()->count(),
        ]);
    }

    /**
     * API: Ambil riwayat percakapan dengan customer tertentu.
     */
    public function fetchMessages(Request $request, $customerId)
    {
        // Tandai pesan dari customer ini sebagai telah dibaca admin
        SupportMessage::where('customer_id', $customerId)
            ->where('sender_type', 'customer')
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $messages = SupportMessage::where('customer_id', $customerId)
            ->with(['admin', 'order'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($msg) {
                return [
                    'id' => $msg->id,
                    'sender_type' => $msg->sender_type,
                    'sender_name' => $msg->sender_type === 'admin' ? ($msg->admin?->name ?? 'Admin') : ($msg->customer?->name ?? 'Customer'),
                    'message' => $msg->message,
                    'attachment_url' => $msg->attachment_path ? asset('storage/' . $msg->attachment_path) : null,
                    'order_id' => $msg->order_id,
                    'order_desc' => $msg->order?->description,
                    'time' => $msg->created_at->format('H:i'),
                    'date' => $msg->created_at->translatedFormat('d M Y'),
                    'is_admin' => $msg->sender_type === 'admin',
                    'is_read' => $msg->is_read,
                ];
            });

        return response()->json([
            'success' => true,
            'messages' => $messages,
        ]);
    }

    /**
     * Balas pesan customer sebagai Admin.
     */
    public function sendMessage(Request $request, $customerId)
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $customer = Customer::findOrFail($customerId);

        $request->validate([
            'message' => 'required_without:attachment|nullable|string|max:2000',
            'attachment' => 'nullable|file|extensions:jpeg,png,jpg,webp,heic,heif|max:20480',
        ], [
            'attachment.extensions' => 'Format lampiran harus berupa JPG, PNG, WEBP, atau HEIC/HEIF (iPhone).',
            'attachment.max' => 'Ukuran lampiran maksimal 20MB.',
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = \App\Services\ImageUploadService::store($request->file('attachment'), 'support/attachments', 'public');
        }

        $supportMessage = SupportMessage::create([
            'customer_id' => $customer->id,
            'admin_id' => $admin->id,
            'sender_type' => 'admin',
            'message' => $request->message ?? '(Mengirim lampiran foto)',
            'attachment_path' => $attachmentPath,
            'is_read' => false,
        ]);

        \App\Services\ActivityLogger::log([
            'user_type' => 'admin',
            'user_id' => $admin->id,
            'user_name' => $admin->name ?? 'Admin Support',
            'order_id' => null,
            'category' => 'chat_cs',
            'action' => 'support_chat_reply',
            'title' => "Respon Admin Bantuan CS",
            'description' => "Admin " . ($admin->name ?? 'Admin Support') . " membalas pesan Customer {$customer->name}: \"" . \Illuminate\Support\Str::limit($supportMessage->message, 100) . "\"",
            'properties' => ['customer_id' => $customer->id, 'support_message_id' => $supportMessage->id],
        ]);

        return response()->json([
            'success' => true,
            'message' => [
                'id' => $supportMessage->id,
                'sender_type' => 'admin',
                'sender_name' => $admin->name ?? 'Admin JastipKuy',
                'message' => $supportMessage->message,
                'attachment_url' => $supportMessage->attachment_path ? asset('storage/' . $supportMessage->attachment_path) : null,
                'time' => $supportMessage->created_at->format('H:i'),
                'date' => $supportMessage->created_at->translatedFormat('d M Y'),
                'is_admin' => true,
                'is_read' => false,
            ],
        ]);
    }

    /**
     * Helper untuk mengambil dan menyusun daftar percakapan support.
     */
    private function getConversationList(?string $search = null)
    {
        // Ambil customer ID unik dari support_messages
        $query = DB::table('support_messages')
            ->select('customer_id', DB::raw('MAX(created_at) as last_activity'))
            ->whereNull('deleted_at')
            ->groupBy('customer_id')
            ->orderBy('last_activity', 'desc');

        $customerActivities = $query->get();

        $conversations = collect();

        foreach ($customerActivities as $item) {
            $customer = Customer::find($item->customer_id);
            if (!$customer) continue;

            if ($search) {
                $matchName = str_contains(strtolower($customer->name), strtolower($search));
                $matchPhone = str_contains($customer->phone_number, $search);
                if (!$matchName && !$matchPhone) continue;
            }

            $latestMsg = SupportMessage::where('customer_id', $customer->id)->latest()->first();
            $unreadCount = SupportMessage::where('customer_id', $customer->id)
                ->where('sender_type', 'customer')
                ->where('is_read', false)
                ->count();

            $conversations->push([
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'customer_phone' => $customer->phone_number,
                'customer_avatar' => strtoupper(substr($customer->name, 0, 2)),
                'latest_message' => $latestMsg ? $latestMsg->message : '',
                'latest_time' => $latestMsg ? $latestMsg->created_at->diffForHumans() : '',
                'latest_raw_time' => $latestMsg ? $latestMsg->created_at->format('H:i') : '',
                'last_sender' => $latestMsg ? $latestMsg->sender_type : '',
                'unread_count' => $unreadCount,
            ]);
        }

        return $conversations;
    }
}
