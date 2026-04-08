<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductSlugRedirect;
use App\Models\Voucher;

class ProductDetailController extends Controller
{
    public function index($slug)
    {
        $product = Product::where('slug', $slug)
            ->active()
            ->with(['primaryImage', 'brand'])
            ->first();

        // Nếu không tìm thấy product với slug hiện tại, kiểm tra redirect
        if (! $product) {
            $finalSlug = $this->resolveRedirectSlug($slug);

            if ($finalSlug && $finalSlug !== $slug) {
                $product = Product::where('slug', $finalSlug)
                    ->active()
                    ->with(['primaryImage', 'brand'])
                    ->first();

                if ($product) {
                    return redirect()->route('client.product.detail', ['slug' => $finalSlug], 301);
                }
            }

            return view('clients.pages.errors.404');
        }

        $vouchers = Voucher::active()
            ->orderBy('created_at', 'desc')
            ->limit(4)
            ->get();

        $productNew = Product::active()
            ->with('primaryImage')
            ->orderBy('created_at', 'desc')
            ->inRandomOrder()
            ->limit(9)
            ->get() ?? [];
        $productRelated = Product::related($product);

        return view('clients.pages.single.index', compact('product', 'vouchers', 'productNew', 'productRelated'));
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
            if (in_array($currentSlug, $visited, true)) {
                break;
            }

            $visited[] = $currentSlug;

            $redirect = ProductSlugRedirect::where('old_slug', $currentSlug)->first();

            if (! $redirect) {
                return $currentSlug;
            }

            $product = Product::where('slug', $redirect->new_slug)->active()->first();
            if ($product) {
                return $redirect->new_slug;
            }

            $currentSlug = $redirect->new_slug;
            $depth++;
        }

        return null;
    }
}
