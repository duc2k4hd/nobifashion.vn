<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductSlugRedirect;
use App\Models\Voucher;
use Illuminate\Http\Request;

class ProductDetailController extends Controller
{
    public function index($slug) {
        $product = Product::where('slug', $slug)->active()->with('primaryImage')->first();

        // Nếu không tìm thấy product với slug hiện tại, kiểm tra redirect
        if(!$product) {
            $finalSlug = $this->resolveRedirectSlug($slug);
            
            if ($finalSlug && $finalSlug !== $slug) {
                // Lấy product với slug cuối cùng
                $product = Product::where('slug', $finalSlug)->active()->with('primaryImage')->first();
                
                if ($product) {
                    // Redirect 301 (Permanent Redirect) sang slug mới
                    return redirect()->route('client.product.detail', ['slug' => $finalSlug], 301);
                }
            }
            
            return view('clients.pages.errors.404');
        }

        $vouchers = Voucher::active()
            ->orderBy('created_at', 'desc')
            ->limit(4)
            ->get();

        $productNew = Product::active()->with('primaryImage')->orderBy('created_at', 'desc')->inRandomOrder()->limit(9)->get() ?? [];
        $productRelated = Product::related($product);
        
        // dd($product->variants[0]);
        return view('clients.pages.single.index',
            compact('product', 'vouchers', 'productNew', 'productRelated')
        );
    }

    /**
     * Giải quyết redirect slug, xử lý trường hợp redirect nhiều lần
     * Ví dụ: slug1 -> slug2 -> slug3, sẽ trả về slug3
     */
    private function resolveRedirectSlug(string $slug, int $maxDepth = 10): ?string
    {
        $currentSlug = $slug;
        $visited = [];
        $depth = 0;

        while ($depth < $maxDepth) {
            // Tránh vòng lặp vô hạn
            if (in_array($currentSlug, $visited)) {
                break;
            }
            $visited[] = $currentSlug;

            $redirect = ProductSlugRedirect::where('old_slug', $currentSlug)->first();
            
            if (!$redirect) {
                // Không có redirect nào, trả về slug hiện tại
                return $currentSlug;
            }

            // Kiểm tra xem slug mới có phải là slug hợp lệ không
            $product = Product::where('slug', $redirect->new_slug)->active()->first();
            if ($product) {
                // Slug mới hợp lệ, trả về nó
                return $redirect->new_slug;
            }

            // Slug mới không hợp lệ, tiếp tục tìm redirect tiếp theo
            $currentSlug = $redirect->new_slug;
            $depth++;
        }

        // Nếu vượt quá độ sâu tối đa, trả về null
        return null;
    }
}
