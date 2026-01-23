<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAddressRequest;
use App\Http\Requests\UpdateAddressRequest;
use App\Http\Resources\AddressResource;
use App\Models\Address;
use App\Services\AddressService;
use App\Services\AddressSuggestionService;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function __construct(protected AddressService $addressService)
    {
        $this->middleware('auth:sanctum')->except('suggestions');
    }

    public function index(Request $request)
    {
        $account = $request->user();

        return AddressResource::collection(
            $this->addressService->listForAccount($account)
        );
    }

    public function store(StoreAddressRequest $request)
    {
        $account = $request->user();

        $address = $this->addressService->create($account, $request->validated());

        return (new AddressResource($address))->response()->setStatusCode(201);
    }

    public function show(Address $address)
    {
        $this->authorize('view', $address);

        return new AddressResource($address);
    }

    public function update(UpdateAddressRequest $request, Address $address)
    {
        $this->authorize('update', $address);

        $address = $this->addressService->update($address, $request->validated(), $request->user());

        return new AddressResource($address);
    }

    public function destroy(Request $request, Address $address)
    {
        $this->authorize('delete', $address);

        $this->addressService->delete($address, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa địa chỉ.',
        ]);
    }

    public function setDefault(Request $request, Address $address)
    {
        $this->authorize('update', $address);

        $address = $this->addressService->setDefault($address, $request->user());

        return new AddressResource($address);
    }

    public function suggestions(Request $request, AddressSuggestionService $suggestionService)
    {
        $request->validate([
            'q' => ['required', 'string', 'min:3'],
        ]);

        $results = $suggestionService->suggest($request->get('q'));

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }
}

