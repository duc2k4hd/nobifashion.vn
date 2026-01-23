<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AddressFilterRequest;
use App\Models\Address;
use App\Models\AddressAudit;
use App\Models\Account;
use App\Services\AddressService;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function __construct(protected AddressService $addressService)
    {
        $this->middleware(['auth:web', 'admin']);
    }

    public function index(AddressFilterRequest $request)
    {
        $query = Address::with('account')->filter($request->validated());

        $addresses = $query->orderByDesc('updated_at')->paginate(20)->withQueryString();

        return view('admins.addresses.index', [
            'addresses' => $addresses,
            'filters' => $request->validated(),
            'accounts' => Account::orderBy('name')->select('id', 'name', 'email')->get(),
        ]);
    }

    public function show(Address $address)
    {
        $address->load('account');

        return view('admins.addresses.show', [
            'address' => $address,
            'audits' => AddressAudit::where('address_id', $address->id)->latest()->limit(20)->get(),
        ]);
    }

    public function edit(Address $address)
    {
        $address->load('account');

        return view('admins.addresses.edit', [
            'address' => $address,
            'accounts' => Account::orderBy('name')->select('id', 'name')->get(),
        ]);
    }

    public function update(Request $request, Address $address)
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'regex:/^(0|\+84)[0-9]{9,10}$/'],
            'detail_address' => ['required', 'string', 'max:500'],
            'ward' => ['nullable', 'string', 'max:255'],
            'district' => ['required', 'string', 'max:255'],
            'province' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:100'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'address_type' => ['nullable', 'in:home,work'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $this->addressService->update($address, $data, $request->user('admin'));

        return redirect()->route('admin.addresses.show', $address)->with('success', 'Đã cập nhật địa chỉ.');
    }

    public function destroy(Address $address)
    {
        $this->addressService->delete($address, auth('admin')->user());

        return redirect()->route('admin.addresses.index')->with('success', 'Đã xóa địa chỉ.');
    }

    public function setDefault(Address $address)
    {
        $this->addressService->setDefault($address, auth('admin')->user());

        return back()->with('success', 'Đã đặt làm địa chỉ mặc định.');
    }
}

