<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Category;
use App\Models\FlashSale;
use App\Models\Product;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function index() {
        $banners = Banner::home()->active()->ordered()->get() ?? [];
        $home_banner = Banner::where('position', 'home_banner')->active()->ordered()->limit(2)->get() ?? [];
        $vouchers = Voucher::active();
        $productsFeatured = Product::active()->featured()->take(18)->get() ?? [];
        // 1. Cache phần dữ liệu nặng - CHỈ lấy flash sale đang chạy (không lấy scheduled)
        $flashSale = Cache::remember('flash_sale_data', 300, function () {
            return FlashSale::where('is_active', true)
                ->where('status', 'active')
                ->where('start_time', '<=', now())  // Đã bắt đầu
                ->where('end_time', '>=', now())    // Chưa kết thúc
                ->orderBy('start_time', 'desc')
                ->with([
                    'items' => function ($query) {
                        $query->where('is_active', true)
                              ->whereRaw('stock > sold')
                              ->whereHas('product', function ($productQuery) {
                                  $productQuery->where('is_active', true)
                                               ->where('stock_quantity', '>', 0);
                              })
                              ->orderBy('sort_order')
                              ->orderBy('id');
                    },
                    'items.product' => function ($productQuery) {
                        $productQuery->where('is_active', true);
                    },
                    'items.product.primaryImage',
                    'items.product.primaryCategory'
                ])
                ->first()
                ?->makeHidden([
                    'start_time', 'end_time', 'created_at', 'updated_at'
                ]);
        });

        // 2. Lấy thời gian realtime và kiểm tra lại điều kiện
        if ($flashSale) {
            $flashSaleTime = FlashSale::where('id', $flashSale->id)
                ->select('id', 'start_time', 'end_time', 'is_active', 'status')
            ->first();
            
            // 3. Kiểm tra lại điều kiện: phải đang chạy (không phải scheduled)
            if ($flashSaleTime 
                && $flashSaleTime->is_active 
                && $flashSaleTime->status === 'active'
                && $flashSaleTime->start_time <= now()
                && $flashSaleTime->end_time >= now()) {
                
                $flashSale->start_time = $flashSaleTime->start_time;
                $flashSale->end_time = $flashSaleTime->end_time;
                
                // Lọc lại items nếu cần (đảm bảo chỉ lấy items active và còn hàng)
                $flashSale->setRelation('items', $flashSale->items->filter(function ($item) {
                    return $item->is_active
                        && ($item->stock > $item->sold)
                        && $item->product
                        && $item->product->is_active
                        && ($item->product->stock_quantity > 0);
                }));
            } else {
                // Flash sale không còn đang chạy (đã tắt, đã kết thúc, hoặc chưa bắt đầu)
                $flashSale = null;
            }
        }
        $productClothing = Product::active()
            ->when($category = Category::where('slug', 'thoi-trang')->first(), function ($query) use ($category) {
                $query->inCategory($category->id);
            })
            ->limit(20)->inRandomOrder()->get();

        return view('clients.pages.home.index', compact('banners', 'home_banner', 'vouchers', 'productsFeatured', 'flashSale', 'productClothing'));
    }
}
