<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Http\Requests\PublicContactRequest;
use App\Http\Requests\ProductPhoneRequestRequest;
use App\Models\Contact;
use App\Models\Product;
use App\Services\ContactService;
use App\Notifications\NewContactNotification;
use App\Mail\ProductPhoneRequestMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use App\Services\MailConfigService;

class ContactController extends Controller
{
    protected ContactService $contactService;

    public function __construct(ContactService $contactService)
    {
        $this->contactService = $contactService;
    }

    /**
     * Gửi liên hệ (Public API)
     */
    public function store(PublicContactRequest $request)
    {
        try {
            $contact = $this->contactService->createContact(
                $request->validated(),
                $request->file('attachment')
            );

            // Gửi notification cho admin (chỉ khi không phải spam)
            if ($contact->status !== 'spam') {
                $admins = \App\Models\Account::where('role', \App\Models\Account::ROLE_ADMIN)
                    ->where('is_active', true)
                    ->get();

                if ($admins->isNotEmpty()) {
                    Notification::send($admins, new \App\Notifications\NewContactNotification($contact));
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Cảm ơn bạn đã liên hệ! Chúng tôi sẽ phản hồi sớm nhất có thể.',
                'contact_id' => $contact->id,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ. Vui lòng kiểm tra lại.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to create contact', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => [
                    'name' => $request->input('name'),
                    'email' => $request->input('email'),
                    'phone' => $request->input('phone'),
                    'subject' => $request->input('subject'),
                    'has_attachment' => $request->hasFile('attachment'),
                ],
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Không thể gửi liên hệ. Vui lòng thử lại sau.',
            ], 500);
        }
    }

    /**
     * Gửi yêu cầu tư vấn qua số điện thoại (không lưu DB, chỉ gửi email)
     */
    public function sendPhoneRequest(ProductPhoneRequestRequest $request)
    {
        try {
            $ip = $request->ip();
            $key = 'product-phone-request:' . sha1($ip);

            // Rate limiting: 2 lần/ngày theo IP
            if (RateLimiter::tooManyAttempts($key, 2)) {
                $seconds = RateLimiter::availableIn($key);
                $hours = ceil($seconds / 3600);
                
                return response()->json([
                    'success' => false,
                    'message' => "Bạn đã gửi quá nhiều yêu cầu. Vui lòng thử lại sau {$hours} giờ.",
                ], 429);
            }

            // Tăng số lần thử
            RateLimiter::hit($key, now()->addDay());

            // Lấy thông tin sản phẩm
            $product = Product::findOrFail($request->product_id);
            $phone = $request->phone;
            $productUrl = route('client.product.detail', $product->slug);

            // Gửi email với cấu hình từ email account mặc định
            $emailAccountId = config('email_defaults.phone_request');
            MailConfigService::sendWithAccount($emailAccountId, function () use ($phone, $product, $productUrl, $ip, $request) {
                Mail::to('info@nobifashion.vn')->send(
                    new ProductPhoneRequestMail(
                        phone: $phone,
                        productName: $product->name,
                        productSku: $product->sku ?? 'N/A',
                        productUrl: $productUrl,
                        ipAddress: $ip,
                        userAgent: $request->userAgent() ?? 'Unknown'
                    )
                );
            });

            return response()->json([
                'success' => true,
                'message' => 'Cảm ơn bạn! Chúng tôi sẽ gọi lại cho bạn sớm nhất có thể.',
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ. Vui lòng kiểm tra lại.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to send phone request', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => [
                    'phone' => $request->input('phone'),
                    'product_id' => $request->input('product_id'),
                    'ip' => $request->ip(),
                ],
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Không thể gửi yêu cầu. Vui lòng thử lại sau.',
            ], 500);
        }
    }
}
