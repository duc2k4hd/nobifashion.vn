<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Services\AccountLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function __construct(protected AccountLogService $accountLogService)
    {
    }

    public function showLoginForm()
    {
        Log::info('=== ADMIN LOGIN DEBUG START ===');
        Log::info('Auth::check(): ' . (Auth::check() ? 'true' : 'false'));
        
        if (Auth::check()) {
            $user = Auth::user();
            Log::info('User ID: ' . $user->id);
            Log::info('User Email: ' . $user->email);
            Log::info('isAdmin(): ' . ($user->isAdmin() ? 'true' : 'false'));
            Log::info('isActive(): ' . ($user->isActive() ? 'true' : 'false'));
            Log::info('account_status: ' . $user->account_status);
            Log::info('is_active: ' . ($user->is_active ? 'true' : 'false'));
            
            // If user is already authenticated and is admin, redirect to dashboard
            if ($user->isAdmin() && $user->isActive()) {
                Log::info('Redirecting to admin.dashboard (user is admin and active)');
                try {
                    $dashboardUrl = route('admin.dashboard');
                    Log::info('Dashboard URL: ' . $dashboardUrl);
                    return redirect($dashboardUrl);
                } catch (\Exception $e) {
                    Log::error('Error generating dashboard route: ' . $e->getMessage());
                    // Fallback to direct URL
                    return redirect('/admin/dashboard');
                }
            }
            
            // If user is authenticated but not admin, redirect to home
            Log::info('Redirecting to client.home.index (user is not admin or not active)');
            return redirect()->route('client.home.index');
        }
        
        Log::info('Showing login form (user not authenticated)');
        Log::info('=== ADMIN LOGIN DEBUG END ===');
        return view('admins.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $remember = $request->boolean('remember');
        $account = Account::where('email', $credentials['email'])->first();
        $threshold = (int) config('auth.admin_lock_threshold', 5);

        if (!Auth::attempt($credentials, $remember)) {
            if ($account) {
                $attempts = $account->increment('login_attempts');

                $payload = [
                    'attempts' => $attempts,
                    'ip' => $request->ip(),
                    'agent' => $request->userAgent(),
                ];

                if ($attempts >= $threshold && $account->is_active) {
                    $account->forceFill([
                        'is_active' => false,
                        'account_status' => Account::STATUS_LOCKED,
                    ])->save();

                    $this->accountLogService->record('auth.locked', $account->id, null, $payload, [
                        'reason' => 'too_many_failed_attempts',
                    ]);
                } else {
                    $this->accountLogService->record('auth.failed', $account->id, null, $payload);
                }
            }

            return back()->withErrors([
                'email' => 'Email hoặc mật khẩu không đúng.',
            ])->onlyInput('email');
        }

        /** @var Account $user */
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            if ($user) {
                $this->accountLogService->record('auth.denied', $user->id, null, [], [
                    'reason' => 'not_admin',
                ]);
            }
            Auth::logout();

            return back()->withErrors([
                'email' => 'Tài khoản không có quyền truy cập trang quản trị.',
            ]);
        }

        if (!$user->isActive()) {
            $this->accountLogService->record('auth.denied', $user->id, null, [], [
                'reason' => 'inactive',
            ]);
            Auth::logout();

            return back()->withErrors([
                'email' => 'Tài khoản đã bị khóa.',
            ]);
        }

        $user->forceFill([
            'login_attempts' => 0,
            'login_history' => now(),
            'account_status' => $user->account_status === Account::STATUS_LOCKED
                ? Account::STATUS_ACTIVE
                : $user->account_status,
        ])->save();

        $this->accountLogService->record('auth.login', $user->id, null, [], [
            'ip' => $request->ip(),
            'agent' => $request->userAgent(),
        ]);

        $request->session()->regenerate();

        return redirect()
            ->intended(route('admin.dashboard'))
            ->with('success', 'Đăng nhập thành công.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')
            ->with('success', 'Đăng xuất thành công.');
    }
}

