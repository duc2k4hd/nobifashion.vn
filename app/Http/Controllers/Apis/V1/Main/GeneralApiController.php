<?php

namespace App\Http\Controllers\Apis\V1\Main;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GeneralApiController extends Controller
{
    public function geocode(Request $request)
    {
        $address = $request->query('q');

        if (!$address) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Thiếu tham số địa chỉ (q)',
                ],
                422,
            );
        }

        $apiKey = config('services.here.api_key');

        $response = Http::get('https://geocode.search.hereapi.com/v1/geocode', [
            'q' => $address,
            'apiKey' => $apiKey,
        ]);

        if ($response->failed()) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Không thể gọi API HERE',
                ],
                500,
            );
        }

        return response()->json($response->json());
    }
}
