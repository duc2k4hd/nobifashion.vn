<?php

namespace App\Http\Controllers\Clients;

use App\Helpers\EmailHelper;
use App\Http\Controllers\Controller;
use App\Mail\PasswordResetMail;
use App\Models\Account;
use App\Services\MailConfigService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Hiển thị form đăng nhập
     */
    public function showLoginForm()
    {
        // Không cần check auth, cho phép truy cập
        return view('clients.pages.auth.login.index');
    }

    /**
     * Xử lý đăng nhập
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:8',
        ], [
            'email.required' => 'Vui lòng nhập email',
            'email.email' => 'Email không hợp lệ',
            'password.required' => 'Vui lòng nhập mật khẩu',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự',
        ]);

        $credentials = $request->only('email', 'password');
        $remember = $request->has('remember');

        // Tìm account
        $account = Account::where('email', $credentials['email'])->first();

        if (!$account) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Email hoặc mật khẩu không đúng']);
        }

        // Kiểm tra trạng thái tài khoản
        if (!$account->is_active || $account->account_status === Account::STATUS_SUSPENDED || $account->account_status === Account::STATUS_LOCKED || $account->account_status === Account::STATUS_BANNED) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Tài khoản của bạn đã bị khóa hoặc tạm ngưng. Vui lòng liên hệ admin để được hỗ trợ.']);
        }

        // Thử đăng nhập
        if (Auth::guard('web')->attempt($credentials, $remember)) {
            $request->session()->regenerate();

            // Cập nhật login history
            $account->login_history = now();
            $account->login_attempts = 0;
            $account->save();

            // Redirect về trang trước hoặc home
            return redirect()->intended(route('client.home.index'))->with('success', 'Đăng nhập thành công!');
        }

        // Tăng số lần thử đăng nhập sai
        if ($account) {
            $account->login_attempts = ($account->login_attempts ?? 0) + 1;
            $account->save();
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => 'Email hoặc mật khẩu không đúng']);
    }

    /**
     * Hiển thị form đăng ký
     */
    public function showRegisterForm()
    {
        // Không cần check auth, cho phép truy cập
        return view('clients.pages.auth.register.index');
    }

    /**
     * Xử lý đăng ký
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:accounts,email',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'name.required' => 'Vui lòng nhập họ và tên',
            'email.required' => 'Vui lòng nhập email',
            'email.email' => 'Email không hợp lệ',
            'email.unique' => 'Email này đã được sử dụng',
            'password.required' => 'Vui lòng nhập mật khẩu',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự',
            'password.confirmed' => 'Mật khẩu xác nhận không khớp',
        ]);

        try {
            $account = Account::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => Account::ROLE_USER,
                'is_active' => true,
                'account_status' => Account::STATUS_ACTIVE,
                'last_password_changed_at' => now(),
            ]);

            // Tự động đăng nhập sau khi đăng ký
            Auth::guard('web')->login($account);

            return redirect()->route('client.home.index')->with('success', 'Đăng ký thành công! Chào mừng bạn đến với ' . (config('site.short_name') ?? 'Nobi Fashion'));
        } catch (\Exception $e) {
            Log::error('Register error: ' . $e->getMessage());
            return back()
                ->withInput()
                ->withErrors(['email' => 'Đã có lỗi xảy ra. Vui lòng thử lại sau.']);
        }
    }

    /**
     * Xử lý đăng xuất
     */
    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('client.auth.login')->with('success', 'Đăng xuất thành công!');
    }

    /**
     * Hiển thị form quên mật khẩu
     */
    public function showForgotPasswordForm()
    {
        // Không cần check auth, cho phép truy cập
        return view('clients.pages.auth.forgot-password.index');
    }

    /**
     * Gửi link đặt lại mật khẩu
     */
    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ], [
            'email.required' => 'Vui lòng nhập email',
            'email.email' => 'Email không hợp lệ',
        ]);

        $email = $request->email;
        $account = Account::where('email', $email)->first();

        // Kiểm tra email có tồn tại không
        if (!$account) {
            return back()
                ->withInput()
                ->withErrors(['email' => 'Email này không tồn tại trong hệ thống.']);
        }

        try {
            // Tạo token
            $token = Str::random(64);
            
            // Lưu token vào database (xóa token cũ nếu có)
            DB::table('password_reset_tokens')
                ->where('email', $email)
                ->delete();
            
            DB::table('password_reset_tokens')->insert([
                'email' => $email,
                'token' => Hash::make($token),
                'created_at' => now(),
            ]);

            // Tạo reset URL
            $resetUrl = route('client.auth.reset-password', ['token' => $token]);

            // Lấy email account ID từ config
            $emailAccountId = config('email_defaults.password_reset');

            Log::info('Sending password reset email to: ' . $email);

            // Gửi email
            MailConfigService::sendWithAccount($emailAccountId, function () use ($account, $resetUrl, $emailAccountId) {
                Mail::to($account->email)->send(new PasswordResetMail($account, $resetUrl, $emailAccountId));
            });

            Log::info('Password reset email sent successfully to: ' . $email);

            return back()->with('status', 'Chúng tôi đã gửi link đặt lại mật khẩu tới email của bạn. Vui lòng kiểm tra hộp thư.');
        } catch (\Exception $e) {
            Log::error('Error sending password reset email: ' . $e->getMessage());
            return back()
                ->withInput()
                ->withErrors(['email' => 'Đã có lỗi xảy ra khi gửi email. Vui lòng thử lại sau.']);
        }
    }

    /**
     * Hiển thị form đặt lại mật khẩu
     */
    public function showResetPasswordForm($token)
    {
        // Không cần check auth, cho phép truy cập
        // Validate token
        $tokenExists = DB::table('password_reset_tokens')
            ->where('created_at', '>', now()->subHours(24))
            ->get()
            ->first(function ($record) use ($token) {
                return Hash::check($token, $record->token);
            });

        if (!$tokenExists) {
            return redirect()->route('client.auth.forgot-password')
                ->with('error', 'Link đặt lại mật khẩu không hợp lệ hoặc đã hết hạn. Vui lòng yêu cầu lại.');
        }

        return view('clients.pages.auth.reset-password.index', [
            'token' => $token,
        ]);
    }

    /**
     * Xử lý đặt lại mật khẩu
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'token.required' => 'Token không hợp lệ',
            'password.required' => 'Vui lòng nhập mật khẩu mới',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự',
            'password.confirmed' => 'Mật khẩu xác nhận không khớp',
        ]);

        // Tìm token hợp lệ
        $tokenRecord = DB::table('password_reset_tokens')
            ->where('created_at', '>', now()->subHours(24))
            ->get()
            ->first(function ($record) use ($request) {
                return Hash::check($request->token, $record->token);
            });

        if (!$tokenRecord) {
            return back()
                ->withInput()
                ->withErrors(['token' => 'Link đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.']);
        }

        // Tìm account
        $account = Account::where('email', $tokenRecord->email)->first();

        if (!$account) {
            return back()
                ->withInput()
                ->withErrors(['email' => 'Tài khoản không tồn tại.']);
        }

        // Kiểm tra giới hạn đổi mật khẩu (1 lần/ngày)
        if ($account->last_password_changed_at && $account->last_password_changed_at->isToday()) {
            return back()
                ->withInput()
                ->withErrors(['password' => 'Bạn chỉ có thể đổi mật khẩu một lần mỗi ngày. Vui lòng thử lại vào ngày mai.']);
        }

        try {
            // Cập nhật mật khẩu
            $account->password = Hash::make($request->password);
            $account->last_password_changed_at = now();
            $account->save();

            // Xóa token
            DB::table('password_reset_tokens')
                ->where('email', $tokenRecord->email)
                ->delete();

            return redirect()->route('client.auth.login')
                ->with('success', 'Đặt lại mật khẩu thành công! Vui lòng đăng nhập với mật khẩu mới.');
        } catch (\Exception $e) {
            Log::error('Error resetting password: ' . $e->getMessage());
            return back()
                ->withInput()
                ->withErrors(['password' => 'Đã có lỗi xảy ra. Vui lòng thử lại sau.']);
        }
    }
}

