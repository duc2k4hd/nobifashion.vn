<?php

namespace App\Providers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
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
        // --- SETTINGS ---
        if (Schema::hasTable('settings')) {
            $settings = Cache::rememberForever('settings', function () {
                return Setting::active()
                    ->get() // ❗ quan trọng
                    ->mapWithKeys(fn($s) => [$s->key => $s->getParsedValue()])
                    ->toArray();
            });

            View::share('settings', (object) $settings);
        }

        // --- CATEGORIES ---
        if (Schema::hasTable('categories')) {
            $categories = Category::query()->active()
                ->whereNull('parent_id')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->with('children.children')   // tự sort theo quan hệ
                ->get();

            View::share('categories', $categories);
        }

        // --- ACCOUNT + CART (Global composer) ---
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

                    $cartItemSumQuery = CartItem::query()->active()
                        ->where(function ($q) {
                            $q->whereNull('status')->orWhere('status', 'active');
                        })
                        ->whereHas('cart', function ($q) use ($sessionId) {
                            if (auth('web')->check()) {
                                $q->where('account_id', auth('web')->id());
                            } else {
                                $q->whereNull('account_id')->where('session_id', $sessionId);
                            }
                        });

                    $cartCount = (int) ($cartItemSumQuery->sum('quantity') ?? 0);
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
