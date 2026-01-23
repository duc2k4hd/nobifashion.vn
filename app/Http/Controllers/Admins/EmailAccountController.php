<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EmailAccountRequest;
use App\Models\EmailAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        return view('admins.email-accounts.index', compact('emailAccounts'));
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
}
