<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAddressRequest;
use App\Http\Requests\UpdateAddressRequest;
use App\Services\AddressService;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function __construct(protected AddressService $addressService)
    {
        $this->middleware('auth:web');
    }

    public function index(Request $request)
    {
        $account = $request->user();

        return view('clients.pages.profile.addresses', [
            'addresses' => $this->addressService->listForAccount($account),
        ]);
    }

    public function store(StoreAddressRequest $request)
    {
        $account = $request->user();
        $this->addressService->create($account, $request->validated());

        return back()->with('success', 'Đã thêm địa chỉ mới.');
    }

    public function update(UpdateAddressRequest $request, int $addressId)
    {
        $address = $request->user()->addresses()->findOrFail($addressId);
        $this->addressService->update($address, $request->validated(), $request->user());

        return back()->with('success', 'Đã cập nhật địa chỉ.');
    }

    public function destroy(Request $request, int $addressId)
    {
        $address = $request->user()->addresses()->findOrFail($addressId);
        $this->addressService->delete($address, $request->user());

        return back()->with('success', 'Đã xóa địa chỉ.');
    }

    public function setDefault(Request $request, int $addressId)
    {
        $address = $request->user()->addresses()->findOrFail($addressId);
        $this->addressService->setDefault($address, $request->user());

        return back()->with('success', 'Đã đặt địa chỉ mặc định.');
    }
}

