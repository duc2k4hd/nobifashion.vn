<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EmailAccountRequest;
use App\Models\EmailAccount;
use App\Services\MailConfigService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class EmailAccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = EmailAccount::query();

        if ($keyword = $request->get('keyword')) {
            $query->where(function ($q) use ($keyword) {
                $q->where('email', 'like', "%{$keyword}%")
                  ->orWhere('name', 'like', "%{$keyword}%");
            });
        }

        if (($status = $request->get('status')) !== null && $status !== '') {
            $query->where('is_active', (bool) $status);
        }

        $emailAccounts = $query->ordered()
            ->paginate(20)
            ->appends($request->query());

        $stats = [
            'total' => EmailAccount::count(),
            'active' => EmailAccount::where('is_active', true)->count(),
            'inactive' => EmailAccount::where('is_active', false)->count(),
            'default' => EmailAccount::where('is_default', true)->first(),
        ];

        return view('admins.email-accounts.index', compact('emailAccounts', 'stats'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $emailAccount = new EmailAccount();
        return view('admins.email-accounts.create', compact('emailAccount'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(EmailAccountRequest $request)
    {
        DB::transaction(function () use ($request) {
            $data = $request->validated();
            
            // Xóa password nếu để trống
            if (empty($data['mail_password'])) {
                unset($data['mail_password']);
            }
            
            // Nếu đặt làm mặc định, bỏ mặc định các email khác
            if (!empty($data['is_default'])) {
                EmailAccount::where('id', '!=', 0)->update(['is_default' => false]);
            }

            EmailAccount::create($data);
        });

        return redirect()
            ->route('admin.email-accounts.index')
            ->with('success', 'Đã tạo email thành công.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EmailAccount $emailAccount)
    {
        return view('admins.email-accounts.edit', compact('emailAccount'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(EmailAccountRequest $request, EmailAccount $emailAccount)
    {
        DB::transaction(function () use ($request, $emailAccount) {
            $data = $request->validated();
            
            // Xóa password nếu để trống (giữ nguyên password cũ)
            if (empty($data['mail_password'])) {
                unset($data['mail_password']);
            }
            
            // Nếu đặt làm mặc định, bỏ mặc định các email khác
            if (!empty($data['is_default'])) {
                EmailAccount::where('id', '!=', $emailAccount->id)->update(['is_default' => false]);
            }

            $emailAccount->update($data);
        });

        return redirect()
            ->route('admin.email-accounts.index')
            ->with('success', 'Đã cập nhật email thành công.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EmailAccount $emailAccount)
    {
        // Không cho xóa email mặc định
        if ($emailAccount->is_default) {
            return redirect()
                ->route('admin.email-accounts.index')
                ->with('error', 'Không thể xóa email mặc định. Vui lòng đặt email khác làm mặc định trước.');
        }

        $emailAccount->delete();

        return redirect()
            ->route('admin.email-accounts.index')
            ->with('success', 'Đã xóa email thành công.');
    }

    /**
     * Đặt email làm mặc định
     */
    public function setDefault(EmailAccount $emailAccount)
    {
        $emailAccount->setAsDefault();

        return redirect()
            ->route('admin.email-accounts.index')
            ->with('success', 'Đã đặt email làm mặc định.');
    }

    /**
     * Bật / Tắt trạng thái hoạt động nhanh
     */
    public function toggleActive(EmailAccount $emailAccount)
    {
        if ($emailAccount->is_default && $emailAccount->is_active) {
            $msg = 'Không thể tắt tài khoản email mặc định. Vui lòng đặt tài khoản khác làm mặc định trước.';
            if (request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->with('error', $msg);
        }

        $emailAccount->update(['is_active' => !$emailAccount->is_active]);

        $statusText = $emailAccount->is_active ? 'kích hoạt' : 'tạm dừng';
        $msg = "Đã {$statusText} tài khoản {$emailAccount->email} thành công.";

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'is_active' => $emailAccount->is_active
            ]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Gửi email thử nghiệm từ tài khoản này
     */
    public function sendTest(Request $request, EmailAccount $emailAccount)
    {
        $recipient = $request->input('recipient_email') ?: auth('web')->user()?->email ?: $emailAccount->email;

        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $msg = 'Địa chỉ email người nhận không hợp lệ.';
            return request()->wantsJson() 
                ? response()->json(['success' => false, 'message' => $msg], 422)
                : back()->with('error', $msg);
        }

        try {
            $start = microtime(true);
            MailConfigService::sendWithAccount($emailAccount->id, function () use ($recipient, $emailAccount) {
                Mail::raw("Xin chào Quản trị viên!\n\nEmail này được gửi thử nghiệm thành công từ hệ thống NOBI FASHION.\n- Tài khoản gửi: {$emailAccount->name} <{$emailAccount->email}>\n- SMTP Host: " . ($emailAccount->mail_host ?: config('mail.mailers.smtp.host')) . "\n- SMTP Port: " . ($emailAccount->mail_port ?: config('mail.mailers.smtp.port')) . "\n- Thời gian: " . now()->format('d/m/Y H:i:s') . "\n\nCấu hình kết nối SMTP đang hoạt động chính xác!", function ($message) use ($recipient, $emailAccount) {
                    $message->to($recipient)
                            ->subject('[NOBI FASHION] Kiểm tra kết nối SMTP thành công: ' . $emailAccount->email);
                });
            });
            $duration = round((microtime(true) - $start) * 1000);

            $msg = "Gửi email thử nghiệm thành công tới [{$recipient}] ({$duration}ms)! Kết nối SMTP hoạt động ổn định.";
            return request()->wantsJson()
                ? response()->json(['success' => true, 'message' => $msg, 'duration' => $duration])
                : back()->with('success', $msg);
        } catch (\Throwable $e) {
            $errorMsg = 'Gửi email thử nghiệm thất bại: ' . $e->getMessage();
            return request()->wantsJson()
                ? response()->json(['success' => false, 'message' => $errorMsg], 500)
                : back()->with('error', $errorMsg);
        }
    }

    /**
     * Kiểm tra trực tiếp kết nối SMTP từ form trước khi lưu
     */
    public function testConnection(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'recipient_email' => 'required|email',
            'mail_port' => 'nullable|numeric',
        ]);

        $host = $request->input('mail_host') ?: config('mail.mailers.smtp.host');
        $port = $request->input('mail_port') ?: config('mail.mailers.smtp.port');
        $username = $request->input('mail_username') ?: config('mail.mailers.smtp.username');
        $password = $request->input('mail_password') ?: config('mail.mailers.smtp.password');
        $encryption = $request->input('mail_encryption') ?: config('mail.mailers.smtp.encryption');
        $fromEmail = $request->input('email');
        $fromName = $request->input('name') ?: 'NOBI FASHION Test';
        $recipient = $request->input('recipient_email');

        $oldConfig = [
            'host' => config('mail.mailers.smtp.host'),
            'port' => config('mail.mailers.smtp.port'),
            'username' => config('mail.mailers.smtp.username'),
            'password' => config('mail.mailers.smtp.password'),
            'encryption' => config('mail.mailers.smtp.encryption'),
            'from_address' => config('mail.from.address'),
            'from_name' => config('mail.from.name'),
        ];

        try {
            Config::set('mail.mailers.smtp.host', $host);
            Config::set('mail.mailers.smtp.port', $port);
            Config::set('mail.mailers.smtp.username', $username);
            Config::set('mail.mailers.smtp.password', $password);
            Config::set('mail.mailers.smtp.encryption', $encryption);
            Config::set('mail.from.address', $fromEmail);
            Config::set('mail.from.name', $fromName);

            if (App::bound('mail.manager')) {
                App::forgetInstance('mail.manager');
            }

            $start = microtime(true);
            Mail::raw("Xin chào Quản trị viên!\n\nEmail này được gửi kiểm tra cấu hình SMTP trực tiếp từ hệ thống NOBI FASHION.\n- Host: {$host}\n- Port: {$port}\n- Mã hóa: " . ($encryption ?: 'None') . "\n- Thời gian: " . now()->format('d/m/Y H:i:s') . "\n\nThông số máy chủ SMTP hoạt động hoàn hảo!", function ($message) use ($recipient, $fromEmail, $fromName) {
                $message->to($recipient)
                        ->subject('[NOBI FASHION] Kiểm tra thông số kết nối SMTP thành công');
            });
            $duration = round((microtime(true) - $start) * 1000);

            return response()->json([
                'success' => true,
                'message' => "Kết nối SMTP và gửi email thành công tới [{$recipient}] ({$duration}ms)!",
                'duration' => $duration
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi kết nối SMTP: ' . $e->getMessage()
            ], 500);
        } finally {
            Config::set('mail.mailers.smtp.host', $oldConfig['host']);
            Config::set('mail.mailers.smtp.port', $oldConfig['port']);
            Config::set('mail.mailers.smtp.username', $oldConfig['username']);
            Config::set('mail.mailers.smtp.password', $oldConfig['password']);
            Config::set('mail.mailers.smtp.encryption', $oldConfig['encryption']);
            Config::set('mail.from.address', $oldConfig['from_address']);
            Config::set('mail.from.name', $oldConfig['from_name']);
        }
    }
}
