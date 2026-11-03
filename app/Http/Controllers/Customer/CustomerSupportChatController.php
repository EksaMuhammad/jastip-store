<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\SupportMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CustomerSupportChatController extends Controller
{
    /**
     * Halaman Live Chat Bantuan dengan Admin / Customer Support.
     */
    public function index(Request $request)
    {
        $customer = Auth::guard('customer')->user();
        if (!$customer) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        // Tandai pesan dari admin sebagai telah dibaca
        SupportMessage::where('customer_id', $customer->id)
            ->where('sender_type', 'admin')
            ->where('is_read', false)
            ->update(['is_read' => true]);

        // Ambil riwayat pesan
        $messages = SupportMessage::where('customer_id', $customer->id)
            ->with(['admin', 'order'])
            ->orderBy('created_at', 'asc')
            ->get();

        // Pesanan aktif customer untuk opsi referensi kendala
        $recentOrders = Order::where('customer_id', $customer->id)
            ->whereNotIn('status', ['dibatalkan'])
            ->latest()
            ->limit(5)
            ->get();

        return view('customer.support.chat', compact('customer', 'messages', 'recentOrders'));
    }

    /**
     * Endpoint API untuk polling riwayat pesan support secara realtime.
     */
    public function fetchMessages(Request $request)
    {
        $customer = Auth::guard('customer')->user();
        if (!$customer) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Tandai pesan dari admin sebagai dibaca
        SupportMessage::where('customer_id', $customer->id)
            ->where('sender_type', 'admin')
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $messages = SupportMessage::where('customer_id', $customer->id)
            ->with(['admin', 'order'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($msg) {
                return [
                    'id' => $msg->id,
                    'sender_type' => $msg->sender_type,
                    'sender_name' => $msg->sender_type === 'admin' ? ($msg->admin?->name ?? 'Admin JastipKuy') : 'Anda',
                    'message' => $msg->message,
                    'attachment_url' => $msg->attachment_path ? asset('storage/' . $msg->attachment_path) : null,
                    'order_id' => $msg->order_id,
                    'order_desc' => $msg->order?->description,
                    'time' => $msg->created_at->format('H:i'),
                    'date' => $msg->created_at->translatedFormat('d M Y'),
                    'is_mine' => $msg->sender_type === 'customer',
                    'is_read' => $msg->is_read,
                ];
            });

        return response()->json([
            'success' => true,
            'messages' => $messages,
        ]);
    }

    /**
     * Kirim pesan baru ke Admin / Customer Support.
     */
    public function sendMessage(Request $request)
    {
        $customer = Auth::guard('customer')->user();
        if (!$customer) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $request->validate([
            'message' => 'required_without:attachment|nullable|string|max:2000',
            'attachment' => 'nullable|file|extensions:jpeg,png,jpg,webp,heic,heif|max:20480',
            'order_id' => 'nullable|exists:orders,id',
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
            'sender_type' => 'customer',
            'message' => $request->message ?? '(Mengirim lampiran foto)',
            'attachment_path' => $attachmentPath,
            'is_read' => false,
            'order_id' => $request->order_id,
        ]);

        \App\Services\ActivityLogger::log([
            'user_type' => 'customer',
            'user_id' => $customer->id,
            'user_name' => $customer->name,
            'order_id' => $request->order_id,
            'category' => 'chat_cs',
            'action' => 'support_chat_sent',
            'title' => "Pesan Bantuan CS dari Pelanggan",
            'description' => "Customer {$customer->name} mengirim pesan bantuan: \"" . \Illuminate\Support\Str::limit($supportMessage->message, 100) . "\"",
            'properties' => ['support_message_id' => $supportMessage->id, 'has_attachment' => !empty($attachmentPath)],
        ]);

        $supportMessage->load(['admin', 'order']);

        return response()->json([
            'success' => true,
            'message' => [
                'id' => $supportMessage->id,
                'sender_type' => 'customer',
                'sender_name' => 'Anda',
                'message' => $supportMessage->message,
                'attachment_url' => $supportMessage->attachment_path ? asset('storage/' . $supportMessage->attachment_path) : null,
                'order_id' => $supportMessage->order_id,
                'order_desc' => $supportMessage->order?->description,
                'time' => $supportMessage->created_at->format('H:i'),
                'date' => $supportMessage->created_at->translatedFormat('d M Y'),
                'is_mine' => true,
                'is_read' => false,
            ],
        ]);
    }
}
