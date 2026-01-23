<?php

namespace App\Http\Controllers\Apis\V1\GHN\Clients;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GHNClientApiController extends Controller
{
    protected $baseUrl;
    protected $token;
    protected $shopId;
    protected $fromDistrictId;
    protected $serviceId;
    protected $serviceTypeId;
    protected $fromWardCode;

    public function __construct()
    {
        $this->baseUrl = config('services.ghn.base_url');
        $this->token   = config('services.ghn.token');
        $this->shopId  = 5236454; // ID cửa hàng
        $this->fromDistrictId  = 1588; // Quận Lê Chân
        $this->serviceId  = 53320; // GHN Tiêu chuẩn (Hàng nhẹ)
        $this->serviceTypeId  = 2; // Hàng dưới 20kg
        $this->fromWardCode  = 30212; // Phường Vĩnh Niệm
    }

    public function getProvince()
    {
        $response = Http::withHeaders([
            'Token' => $this->token,
            'Content-Type' => 'application/json',
        ])->get($this->baseUrl . 'master-data/province');

        if ($response->failed()) {
            return response()->json([
                'code' => $response->status(),
                'message' => 'Không thể lấy danh sách Tỉnh/Thành Phố!',
                'data' => []
            ], $response->status());
        }

        try {
            $data = collect($response->json('data', []))->map(function ($item) {
                return [
                    'provinceId'    => $item['ProvinceID']   ?? null,
                    'provinceName'  => $item['ProvinceName'] ?? null,
                    'countryID'     => $item['CountryID']    ?? null,
                    // Một số môi trường GHN không trả về trường Code / NameExtension
                    'code'          => $item['Code']         ?? null,
                    'nameExtension' => $item['NameExtension'] ?? [],
                ];
            });
        } catch (\Throwable $e) {
            return response()->json([
                'code'    => 500,
                'message' => 'Lỗi xử lý dữ liệu từ GHN: ' . $e->getMessage(),
                'data'    => []
            ], 500);
        }

        return response()->json([
            'code' => 200,
            'message' => 'Lấy Tỉnh/Thành Phố thành công!',
            'data' => $data
        ]);
    }

    public function getDistrict($provinceId)
    {
        if (!$provinceId) {
            return response()->json([
                'code' => 400,
                'message' => 'Thiếu tham số province_id',
                'data' => []
            ], 400);
        }

        $response = Http::withHeaders([
            'token' => $this->token,
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . 'master-data/district', [
            'province_id' => (int) $provinceId
        ]);

        if ($response->failed()) {
            return response()->json([
                'code' => $response->status(),
                'message' => 'Không thể lấy danh sách Quận/Huyện. Tỉnh/TP không tồn tại!',
                'data' => []
            ], $response->status());
        }

        try {
            $data = collect($response->json('data', []))->map(function ($item) {
                return [
                    'districtID'    => $item['DistrictID']    ?? null,
                    'provinceId'    => $item['ProvinceID']    ?? null,
                    'districtName'  => $item['DistrictName']  ?? null,
                    'type'          => $item['Type']          ?? null,
                    'supportType'   => $item['SupportType']   ?? null,
                    'nameExtension' => $item['NameExtension'] ?? [],
                ];
            });
        } catch (\Throwable $e) {
            return response()->json([
                'code'    => 500,
                'message' => 'Lỗi xử lý dữ liệu từ GHN: ' . $e->getMessage(),
                'data'    => []
            ], 500);
        }


        return response()->json([
            'code' => 200,
            'message' => 'Lấy Quận/Huyện thành công!',
            'data' => $data
        ]);
    }

    public function getWard($districtId)
    {
        if (!$districtId) {
            return response()->json([
                'code' => 400,
                'message' => 'Thiếu tham số district_id',
                'data' => []
            ], 400);
        }

        $response = Http::withHeaders([
            'token' => $this->token,
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . 'master-data/ward', [
            'district_id' => (int) $districtId
        ]);

        if ($response->failed()) {
            return response()->json([
                'code' => $response->status(),
                'message' => 'Không thể lấy danh sách Xã/Phường. Quận/Huyện không tồn tại!',
                'data' => []
            ], $response->status());
        }

        try {
            $data = collect($response->json('data', []))->map(function ($item) {
                return [
                    'wardCode'      => $item['WardCode']      ?? null,
                    'districtID'    => $item['DistrictID']    ?? null,
                    'wardName'      => $item['WardName']      ?? null,
                    'nameExtension' => $item['NameExtension'] ?? [],
                ];
            });
        } catch (\Throwable $e) {
            return response()->json([
                'code'    => 500,
                'message' => 'Lỗi xử lý dữ liệu từ GHN: ' . $e->getMessage(),
                'data'    => []
            ], 500);
        }

        return response()->json([
            'code' => 200,
            'message' => 'Lấy Xã/Phường thành công!',
            'data' => $data
        ]);
    }

    public function getServices($districtId)
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'token' => $this->token,
        ])->get($this->baseUrl . 'v2/shipping-order/available-services', [
            'shop_id' => 5236454,
            'from_district' => '1588',
            'to_district' => $districtId
        ]);

        if ($response->failed()) {
            return response()->json([
                'code' => $response->status(),
                'message' => 'Khu vực của bạn không có phương thức giao hàng phù hợp! Vui lòng liên hệ nhân viên để tạo đơn trực tiếp!',
                'data' => []
            ], $response->status());
        }

        try {
            $data = collect($response->json('data', []))->map(function ($item) {
                return [
                    'serviceId'      => $item['service_id']      ?? null,
                    'shortName'    => $item['short_name']    ?? null,
                    'serviceTypeId'    => $item['service_type_id']    ?? null
                ];
            });
        } catch (\Throwable $e) {
            return response()->json([
                'code'    => 500,
                'message' => 'Lỗi xử lý dữ liệu từ GHN: ' . $e->getMessage(),
                'data'    => []
            ], 500);
        }
        return response()->json([
            'code' => 200,
            'message' => 'Lấy phương thức giao hàng thành công!',
            'data' => $data
        ]);
    }

    public function calculateFee(Request $request)
    {
        try {
            // ======= LẤY DỮ LIỆU TỪ FRONTEND =======
            $items = $request->input('items', []);
            $toDistrictId = $request->input('districtId');
            $toWardId = $request->input('wardId');
            $serviceId = $request->input('serviceId');
            $serviceTypeId = $request->input('serviceTypeId');
            $totalValue = $request->input('total', 0);
            

            // ======= KIỂM TRA DỮ LIỆU =======
            if (!$toDistrictId || !$toWardId) {
                return response()->json([
                    'code' => 400,
                    'message' => 'Thiếu thông tin địa chỉ giao hàng (districtId hoặc wardId)!',
                    'data' => []
                ], 400);
            }

            // ======= TÍNH KÍCH THƯỚC & KHỐI LƯỢNG =======
            $itemCount = max(count($items), 1);

            $height = min(10 * $itemCount, 50);   // cm
            $length = min(30 * $itemCount, 60);   // cm
            $width  = min(25 * $itemCount, 60);   // cm
            $weight = min(400 * $itemCount, 5000); // gram

            // ======= TẠO DANH SÁCH ITEMS CHO GHN =======
            $ghnItems = collect($items)->map(function ($item) {
                return [
                    'name' => $item['name'] ?? 'Sản phẩm',
                    'quantity' => $item['quantity'] ?? 1,
                    'weight' => 400,   // trung bình 400g mỗi sản phẩm
                    'length' => 30,
                    'width' => 25,
                    'height' => 10
                ];
            })->toArray();
            

            // Nếu mảng trống, thêm 1 item mặc định
            if (empty($ghnItems)) {
                $ghnItems[] = [
                    'name' => 'Sản phẩm mặc định',
                    'quantity' => 1,
                    'weight' => 400,
                    'length' => 30,
                    'width' => 25,
                    'height' => 10
                ];
            }

            // ======= GỬI REQUEST TỚI GHN =======
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Token' => $this->token,
                'ShopId' => $this->shopId,
                // 'Content-Type' => 'text/plain',
            ])->post($this->baseUrl . 'v2/shipping-order/fee', [
                'from_district_id' => $this->fromDistrictId,
                'from_ward_code' => (string) $this->fromWardCode,
                'service_id' => (int) $serviceId,
                'service_type_id' => (int) $serviceTypeId,
                'to_district_id' => (int) $toDistrictId,
                'to_ward_code' => (string) $toWardId,
                'height' => $height,
                'length' => $length,
                'width'  => $width,
                'weight' => $weight,
                'insurance_value' => $totalValue,
                'cod_failed_amount' => 0,
                'coupon' => null,
                'items' => $ghnItems
            ]);

            return response()->json($response->json());

            // ======= XỬ LÝ KẾT QUẢ =======
            if ($response->failed()) {
                return response()->json([
                    'code' => $response->status(),
                    'message' => 'Không thể tính phí giao hàng. Vui lòng thử lại sau!',
                    'data' => $response->json()
                ], $response->status());
            }

            $data = $response->json();

            return response()->json([
                'code' => 200,
                'message' => 'Tính phí giao hàng thành công!',
                'data' => $data['data'] ?? []
            ], 200);
        } catch (\Throwable $e) {
            // ======= XỬ LÝ LỖI HỆ THỐNG =======
            return response()->json([
                'code' => 500,
                'message' => 'Lỗi hệ thống khi tính phí giao hàng 🚚. Vui lòng thử lại!',
                'error' => $e->getMessage(),
                'data' => []
            ], 500);
        }
    }
}
