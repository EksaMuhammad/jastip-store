<?php

namespace App\Http\Controllers\Cs;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerService;
use App\Models\Order;
use App\Models\SupportConversation;
use App\Models\SupportMessage;
use App\Services\ActivityLogger;
use App\Services\CustomerSupportRoutingService;
use App\Services\ImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CsSupportChatController extends Controller
{
    public function __construct(
        private CustomerSupportRoutingService $routingService
    ) {}

    /**
     * Dashboard Live Chat Bantuan CS.
     */
    public function index(Request $request)
    {
        /** @var CustomerService $cs */
        $cs = Auth::guard('cs')->user();
        $this->routingService->updateHeartbeat($cs);

        $filter = $request->query('tab', 'my'); // 'my' (chat saya), 'queue' (antrean bebas), 'all'
        $search = $request->query('search');
        $selectedCustomerId = $request->query('customer_id');

        // Otomatis arahkan tab jika customer_id dipilih dari dashboard/antrean tanpa tab eksplisit
        if ($selectedCustomerId && !$request->has('tab')) {
            $targetConv = SupportConversation::where('customer_id', $selectedCustomerId)->first();
            if ($targetConv) {
                if ($targetConv->status === 'menunggu') {
                    $filter = 'queue';
                } elseif ($targetConv->status === 'selesai') {
                    $filter = 'closed';
                } elseif ($targetConv->status === 'aktif' && $targetConv->assigned_cs_id === $cs->id) {
                    $filter = 'my';
                }
            }
        }

        $conversations = $this->getConversationList($cs, $filter, $search);

        if (!$selectedCustomerId && $conversations->isNotEmpty()) {
            $selectedCustomerId = $conversations->first()['customer_id'];
        }

        $selectedCustomer = null;
        $selectedConversation = null;
        $messages = collect();
        $customerOrders = collect();

        if ($selectedCustomerId) {
            $selectedCustomer = Customer::with('wallet')->find($selectedCustomerId);

            if ($selectedCustomer) {
                $selectedConversation = SupportConversation::where('customer_id', $selectedCustomerId)->first();

                // Tandai pesan dari customer sebagai terbaca
                SupportMessage::where('customer_id', $selectedCustomerId)
                    ->where('sender_type', 'customer')
                    ->where('is_read', false)
                    ->update(['is_read' => true]);

                $messages = SupportMessage::where('customer_id', $selectedCustomerId)
                    ->with(['admin', 'cs', 'order'])
                    ->orderBy('created_at', 'asc')
                    ->get()
                    ->map(fn($msg) => $this->formatMessage($msg));

                $customerOrders = Order::where('customer_id', $selectedCustomerId)
                    ->latest()
                    ->limit(5)
                    ->get();
            }
        }

        $unassignedQueueCount = SupportConversation::where('status', 'menunggu')->count();
        $myActiveCount = SupportConversation::where('assigned_cs_id', $cs->id)->where('status', 'aktif')->count();

        return view('cs.support.chat', compact(
            'cs',
            'conversations',
            'selectedCustomer',
            'selectedCustomerId',
            'selectedConversation',
            'messages',
            'customerOrders',
            'filter',
            'search',
            'unassignedQueueCount',
            'myActiveCount'
        ));
    }

    /**
     * Polling daftar percakapan via AJAX.
     */
    public function fetchConversations(Request $request)
    {
        /** @var CustomerService $cs */
        $cs = Auth::guard('cs')->user();
        $this->routingService->updateHeartbeat($cs);

        $filter = $request->query('tab', 'my');
        $search = $request->query('search');
        $conversations = $this->getConversationList($cs, $filter, $search);

        return response()->json([
            'success' => true,
            'conversations' => $conversations,
            'unassigned_count' => SupportConversation::where('status', 'menunggu')->count(),
        ]);
    }

    /**
     * Polling pesan live chat untuk customer terpilih.
     */
    public function fetchMessages(Request $request, $customerId)
    {
        /** @var CustomerService $cs */
        $cs = Auth::guard('cs')->user();
        $this->routingService->updateHeartbeat($cs);

        // Tandai pesan customer sebagai terbaca
        SupportMessage::where('customer_id', $customerId)
            ->where('sender_type', 'customer')
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $conversation = SupportConversation::where('customer_id', $customerId)->first();

        $messages = SupportMessage::where('customer_id', $customerId)
            ->with(['admin', 'cs', 'order'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn($msg) => $this->formatMessage($msg));

        $selectedCustomer = Customer::with('wallet')->find($customerId);
        $customerOrders = Order::where('customer_id', $customerId)->latest()->limit(5)->get()->map(function ($ord) {
            return [
                'id' => $ord->id,
                'status' => $ord->status,
                'description' => $ord->description,
                'fare_formatted' => 'Rp ' . number_format($ord->agreed_fare, 0, ',', '.'),
                'date' => $ord->created_at->format('d/m/y'),
            ];
        });

        return response()->json([
            'success' => true,
            'messages' => $messages,
            'customer' => $selectedCustomer ? [
                'id' => $selectedCustomer->id,
                'name' => $selectedCustomer->name,
                'phone' => $selectedCustomer->phone_number,
                'avatar' => strtoupper(substr($selectedCustomer->name, 0, 2)),
                'wallet_formatted' => 'Rp ' . number_format($selectedCustomer->wallet?->balance ?? 0, 0, ',', '.'),
            ] : null,
            'orders' => $customerOrders,
            'conversation' => $conversation ? [
                'status' => $conversation->status,
                'assigned_cs_id' => $conversation->assigned_cs_id,
                'is_my_chat' => $conversation->assigned_cs_id === $cs->id,
            ] : null,
        ]);
    }

    /**
     * Kirim pesan balasan dari CS ke customer.
     */
    public function sendMessage(Request $request, $customerId)
    {
        /** @var CustomerService $cs */
        $cs = Auth::guard('cs')->user();
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
            $attachmentPath = ImageUploadService::store($request->file('attachment'), 'support/attachments', 'public');
        }

        // Pastikan penugasan percakapan aktif untuk CS ini
        $conversation = SupportConversation::firstOrNew(['customer_id' => $customer->id]);
        if (!$conversation->assigned_cs_id) {
            $conversation->assigned_cs_id = $cs->id;
        }
        $conversation->status = 'aktif';
        $conversation->last_message_at = now();
        $conversation->save();

        $supportMessage = SupportMessage::create([
            'customer_id' => $customer->id,
            'cs_id' => $cs->id,
            'sender_type' => 'admin',
            'message' => $request->message ?? '(Mengirim lampiran foto)',
            'attachment_path' => $attachmentPath,
            'is_read' => false,
        ]);

        $this->routingService->updateHeartbeat($cs);

        ActivityLogger::log([
            'user_type' => 'customer_service',
            'user_id' => $cs->id,
            'user_name' => $cs->name,
            'order_id' => null,
            'category' => 'chat_cs',
            'action' => 'cs_chat_reply',
            'title' => "Respon Petugas CS",
            'description' => "CS {$cs->name} membalas chat Customer {$customer->name}: \"" . Str::limit($supportMessage->message, 80) . "\"",
            'properties' => ['customer_id' => $customer->id, 'support_message_id' => $supportMessage->id],
        ]);

        return response()->json([
            'success' => true,
            'message' => [
                'id' => $supportMessage->id,
                'sender_type' => 'admin',
                'sender_name' => $cs->name,
                'message' => $supportMessage->message,
                'attachment_url' => $supportMessage->attachment_path ? asset('storage/' . $supportMessage->attachment_path) : null,
                'time' => $supportMessage->created_at->format('H:i'),
                'date' => $supportMessage->created_at->translatedFormat('d M Y'),
                'is_staff' => true,
                'is_read' => false,
            ],
        ]);
    }

    /**
     * CS mengambil (claim) chat pelanggan dari antrean bebas.
     */
    public function claim(Request $request, $customerId)
    {
        /** @var CustomerService $cs */
        $cs = Auth::guard('cs')->user();
        $this->routingService->claimChat((int) $customerId, $cs);

        return redirect()->route('cs.chats.index', ['customer_id' => $customerId])
            ->with('success', 'Percakapan berhasil Anda klaim! Anda kini menangani customer ini.');
    }

    /**
     * CS menyelesaikan percakapan.
     */
    public function close(Request $request, $customerId)
    {
        /** @var CustomerService $cs */
        $cs = Auth::guard('cs')->user();
        $this->routingService->closeChat((int) $customerId, $cs);

        return redirect()->route('cs.chats.index')
            ->with('success', 'Sesi percakapan ditutup. Terima kasih telah melayani pelanggan!');
    }

    /**
     * Susun daftar percakapan berdasarkan filter tab.
     */
    private function getConversationList(CustomerService $cs, string $filter, ?string $search = null)
    {
        $convQuery = SupportConversation::with(['customer', 'assignedCs']);

        if ($filter === 'my') {
            $convQuery->where('assigned_cs_id', $cs->id)->where('status', 'aktif');
        } elseif ($filter === 'queue') {
            $convQuery->where('status', 'menunggu');
        } elseif ($filter === 'closed') {
            $convQuery->where('assigned_cs_id', $cs->id)->where('status', 'selesai');
        }

        if ($search) {
            $convQuery->whereHas('customer', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        $conversations = $convQuery->orderByDesc('last_message_at')->get();

        return $conversations->map(function ($conv) {
            $customer = $conv->customer;
            if (!$customer) {
                return null;
            }
            $latestMsg = SupportMessage::where('customer_id', $customer->id)->latest()->first();
            $unreadCount = SupportMessage::where('customer_id', $customer->id)
                ->where('sender_type', 'customer')
                ->where('is_read', false)
                ->count();

            return [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'customer_phone' => $customer->phone_number,
                'customer_avatar' => strtoupper(substr($customer->name, 0, 2)),
                'conversation_status' => $conv->status,
                'assigned_cs_id' => $conv->assigned_cs_id,
                'assigned_cs_name' => $conv->assignedCs?->name,
                'is_unassigned' => ($conv->status === 'menunggu' || is_null($conv->assigned_cs_id)),
                'latest_message' => $latestMsg ? $latestMsg->message : 'Belum ada pesan.',
                'latest_time' => $latestMsg ? $latestMsg->created_at->diffForHumans() : '',
                'latest_raw_time' => $latestMsg ? $latestMsg->created_at->format('H:i') : '',
                'last_sender' => $latestMsg ? $latestMsg->sender_type : '',
                'unread_count' => $unreadCount,
            ];
        })->filter()->values();
    }

    /**
     * Format model SupportMessage menjadi array JSON yang konsisten.
     */
    private function formatMessage(SupportMessage $msg): array
    {
        $senderName = 'Customer';
        if ($msg->sender_type === 'admin') {
            $senderName = $msg->cs?->name ?? ($msg->admin?->name ?? 'Tim Support');
        }

        return [
            'id' => $msg->id,
            'sender_type' => $msg->sender_type,
            'sender_name' => $senderName,
            'message' => $msg->message,
            'attachment_url' => $msg->attachment_path ? asset('storage/' . $msg->attachment_path) : null,
            'order_id' => $msg->order_id,
            'order_desc' => $msg->order?->description,
            'time' => $msg->created_at->format('H:i'),
            'date' => $msg->created_at->translatedFormat('d M Y'),
            'is_staff' => $msg->sender_type === 'admin',
            'is_read' => $msg->is_read,
        ];
    }
}

