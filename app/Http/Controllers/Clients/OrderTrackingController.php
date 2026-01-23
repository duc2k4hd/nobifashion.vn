<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Services\GHNService;
use Illuminate\Http\Request;

class OrderTrackingController extends Controller
{
    protected GHNService $ghnService;

    public function __construct(GHNService $ghnService)
    {
        $this->ghnService = $ghnService;
    }

    public function show(Request $request)
    {
        $trackingCode = $request->get('tracking_code');
        $result = $request->session()->get('tracking_result');

        return view('clients.pages.order.track', compact('trackingCode', 'result'));
    }

    public function lookup(Request $request)
    {
        $data = $request->validate([
            'tracking_code' => ['required', 'string', 'max:50'],
        ]);

        $result = $this->ghnService->getOrderInfo($data['tracking_code']);

        return redirect()
            ->route('client.order.track', ['tracking_code' => $data['tracking_code']])
            ->with('tracking_result', $result);
    }
}

