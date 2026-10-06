<?php

namespace App\Providers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Favorite;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Throwable;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // --- SETTINGS (Cache vĩnh viễn, không query schema lãng phí) ---
        try {
            $settings = Cache::rememberForever('settings', function () {
                return Setting::active()
                    ->get(['key', 'value', 'type'])
                    ->mapWithKeys(fn($s) => [$s->key => $s->getParsedValue()])
                    ->toArray();
            });

            config(['settings' => $settings]);
            View::share('settings', (object) $settings);
        } catch (\Throwable $e) {
            // Bỏ qua lỗi khi database chưa sẵn sàng
        }

        // --- CATEGORIES (1 query duy nhất + Dựng cây quan hệ trong RAM cực nhanh 0.05ms + Cache vĩnh viễn) ---
        try {
            $categories = Cache::rememberForever('view.categories.tree.v3', function () {
                $all = Category::query()
                    ->where('is_active', true)
                    ->select(['id', 'name', 'slug', 'image', 'parent_id', 'sort_order'])
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->get();

                $grouped = $all->groupBy('parent_id');
                foreach ($all as $item) {
                    $item->setRelation('children', $grouped->get($item->id, collect()));
                }

                return $grouped->get(null, collect());
            });

            View::share('categories', $categories);
            View::share('headerCategoryProducts', []);
        } catch (\Throwable $e) {
            // Bỏ qua lỗi khi database chưa sẵn sàng
        }

        // --- ACCOUNT + CART (Global composer) ---
        // Chỉ đăng ký View composer khi không chạy trong console
        if (!app()->runningInConsole()) {
            View::composer('*', function ($view) {
                static $sharedPayload = null;

                if ($sharedPayload === null) {
                    try {
                        $account = auth('web')->user() ?? null;
                        $sessionId = session()->getId();

                    $cartQuery = Cart::query()->active()->with(['items' => function ($q) {
                        $q->where(function ($q2) {
                            $q2->whereNull('status')->orWhere('status', 'active');
                        });
                    }]);

                    if (auth('web')->check()) {
                        $cartQuery->where('account_id', auth('web')->id());
                    } else {
                        $cartQuery->whereNull('account_id')->where('session_id', $sessionId);
                    }

                    $cart = $cartQuery->orderByDesc('id')->first();

                    // Tính tổng số lượng trực tiếp từ quan hệ items đã nạp trong RAM (tiết kiệm 1 subquery EXISTS nặng nề)
                    $cartCount = $cart ? (int) $cart->items->sum('quantity') : 0;
                    $cartLink = $cartCount > 0 ? route('client.cart.index') : null;

                    $favorites = Favorite::ofOwner(auth('web')->id(), $sessionId)->pluck('product_id');
                    $favCount = $favorites->count();
                    $favIds = $favorites->toArray();
                    $favLink = $favCount > 0 ? route('client.favorites.index') : null;

                    $sharedPayload = [
                        'account' => $account,
                        'cart' => $cart,
                        'cartCount' => $cartCount,
                        'cartLink' => $cartLink,
                        'cartQuantity' => $cartCount,
                        'cartQty' => $cartCount,
                        'cart_items_count' => $cartCount,
                        'cartUrl' => $cartLink,
                        'wishlistCount' => $favCount,
                        'wishlistLink' => $favLink,
                        'favoriteProductIds' => $favIds,
                    ];
                } catch (Throwable $e) {
                    Log::debug('Trình soạn thảo ViewServiceProvider đã bỏ qua', [
                        'error' => $e->getMessage()
                    ]);

                    $sharedPayload = [
                        'account' => null,
                        'cart' => null,
                        'cartCount' => 0,
                        'cartLink' => null,
                        'cartQuantity' => 0,
                        'cartQty' => 0,
                        'cart_items_count' => 0,
                        'cartUrl' => null,
                        'wishlistCount' => 0,
                        'wishlistLink' => null,
                        'favoriteProductIds' => [],
                    ];
                }
            }

                foreach ($sharedPayload as $key => $value) {
                    $view->with($key, $value);
                }
            });
        }
    }
}
