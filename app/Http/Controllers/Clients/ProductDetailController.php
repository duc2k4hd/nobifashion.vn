<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Voucher;
use Illuminate\Http\Request;

class ProductDetailController extends Controller
{
    public function index($slug) {
        $product = Product::where('slug', $slug)->active()->with('primaryImage')->first();

        
        if(!$product) {
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
}
