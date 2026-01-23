<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AccountStoreRequest;
use App\Http\Requests\Admin\AccountUpdateRequest;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only([
            'keyword',
            'role',
            'status',
            'account_status',
            'email_verified',
            'gender',
            'location',
            'last_login_from',
            'last_login_to',
        ]);

        $query = Account::query()
            ->with('profile')
            ->applyFilters($filters);

        $accounts = $query->orderByDesc('id')
            ->paginate(25)
            ->appends($filters);

        $roles = Account::roles();
        $accountStatuses = Account::statuses();

        $stats = [
            'total' => Account::count(),
            'active' => Account::where('is_active', true)->count(),
            'inactive' => Account::where('is_active', false)->count(),
            'verified' => Account::whereNotNull('email_verified_at')->count(),
            'locked' => Account::where('account_status', Account::STATUS_LOCKED)->count(),
            'suspended' => Account::where('account_status', Account::STATUS_SUSPENDED)->count(),
        ];

        return view('admins.accounts.index', [
            'accounts' => $accounts,
            'roles' => $roles,
            'accountStatuses' => $accountStatuses,
            'filters' => $filters,
            'stats' => $stats,
        ]);
    }

    public function create()
    {
        $account = new Account();
        $roles = Account::roles();

        return view('admins.accounts.create', compact('account', 'roles'));
    }

    public function store(AccountStoreRequest $request)
    {
        try {
            $data = $request->validated();

            if (Account::where('email', $data['email'])->exists()) {
                return back()
                    ->withInput()
                    ->with('error', 'Email đã tồn tại, vui lòng dùng email khác.');
            }

            DB::beginTransaction();

            $account = Account::create($data);

            DB::commit();

            return redirect()->route('admin.accounts.index')
                ->with('success', 'Tạo tài khoản thành công.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', 'Không thể tạo tài khoản: ' . $e->getMessage());
        }
    }

    public function edit(Account $account)
    {
        $account->load('profile');
        $roles = Account::roles();
        $accountStatuses = Account::statuses();

        return view('admins.accounts.edit', [
            'account_admin' => $account,
            'roles' => $roles,
            'accountStatuses' => $accountStatuses,
        ]);
    }

    public function update(AccountUpdateRequest $request, Account $account)
    {
        try {
            $data = $request->validated();

            // Kiểm tra email trùng với tài khoản khác
            if (isset($data['email']) && $data['email'] !== $account->email) {
                if (Account::where('email', $data['email'])
                    ->where('id', '!=', $account->id)
                    ->exists()) {
                    return back()
                        ->withInput()
                        ->with('error', 'Email đã tồn tại, vui lòng dùng email khác.');
                }
            }

            DB::beginTransaction();

            if ($account->email === 'admin@gmail.com' && $data['role'] !== $account->role) {
                return back()
                    ->withInput()
                    ->with('error', 'Không thể đổi vai trò của tài khoản admin@gmail.com.');
            }

            // Không đổi password nếu để trống
            if (empty($data['password'])) {
                unset($data['password']);
            }

            $account->update($data);
            DB::commit();

            // Nếu user đang sửa chính tài khoản của mình → buộc logout
            if (Auth::id() === $account->id) {
                Auth::logout();
                session()->invalidate();
                session()->regenerateToken();

                 return redirect()
                     ->route('admin.login')
                     ->with('success', 'Cập nhật tài khoản thành công. Vui lòng đăng nhập lại.');
            }

            // Nếu admin sửa tài khoản người khác → quay lại trang edit
            return redirect()
                ->route('admin.accounts.edit', $account)
                ->with('success', 'Cập nhật tài khoản thành công.');
        } 
        catch (\Throwable $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', 'Không thể cập nhật tài khoản: ' . $e->getMessage());
        }
    }


    public function toggleStatus(Account $account)
    {
        if (Auth::id() === $account->id) {
            return back()->with('error', 'Không thể thay đổi trạng thái tài khoản đang đăng nhập.');
        }

        $account->update(['is_active' => !$account->is_active]);

        return back()->with('success', 'Đã cập nhật trạng thái tài khoản.');
    }

    public function bulkAction(Request $request)
    {
        $request->validate([
            'selected' => ['required', 'array'],
            'selected.*' => ['integer', 'exists:accounts,id'],
            'bulk_action' => ['required', 'in:activate,deactivate'],
        ]);

        $ids = $request->input('selected', []);
        $action = $request->input('bulk_action');
        $currentId = Auth::id();

        if (in_array($currentId, $ids)) {
            return back()->with('error', 'Không thể thay đổi trạng thái tài khoản đang đăng nhập.');
        }

        if ($action === 'activate') {
            Account::whereIn('id', $ids)->update(['is_active' => true]);
            return back()->with('success', 'Đã kích hoạt ' . count($ids) . ' tài khoản.');
        }

        if ($action === 'deactivate') {
            Account::whereIn('id', $ids)->update(['is_active' => false]);
            return back()->with('success', 'Đã vô hiệu hóa ' . count($ids) . ' tài khoản.');
        }

        return back()->with('error', 'Hành động không hợp lệ.');
    }

}


