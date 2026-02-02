<?php

use App\Http\Controllers\AccountEmailVerificationController;
use App\Http\Controllers\Admins\AuthController;
use App\Http\Controllers\Clients\AuthController as ClientAuthController;
use App\Http\Controllers\Clients\ContactController;
use App\Http\Controllers\Clients\ShopController;
use App\Http\Controllers\Clients\CartController;
use App\Http\Controllers\Clients\BlogController;
use App\Http\Controllers\Clients\CheckoutController;
use App\Http\Controllers\Clients\HomeController;
use App\Http\Controllers\Clients\ProductDetailController;
use App\Http\Controllers\Clients\ProfileController;
use App\Http\Middleware\LocaleMiddleware;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Clients\PaymentController;
use App\Http\Controllers\Clients\FavoriteController;
use App\Http\Controllers\Clients\NewsletterController;
use App\Http\Controllers\Clients\VoucherController;
use App\Http\Controllers\Admins\ImportExcelController;
use App\Http\Controllers\Admins\CategoryController;
use App\Http\Controllers\Admins\AccountController;
use App\Http\Controllers\Admins\AccountApiController;
use App\Http\Controllers\Admins\AccountLogController;
use App\Http\Controllers\Admins\AccountProfileController;
use App\Http\Controllers\Admins\AdminMediaAssignController;
use App\Http\Controllers\Admins\AdminMediaController;
use App\Http\Controllers\Admins\AdminMediaDeleteController;
use App\Http\Controllers\Admins\AdminMediaSearchController;
use App\Http\Controllers\Admins\AdminMediaTargetController;
use App\Http\Controllers\Admins\AdminMediaUploadController;
use App\Http\Controllers\Admins\SettingController;
use App\Http\Controllers\Admins\BannerController;
use App\Http\Controllers\Admins\EmailAccountController;
use App\Http\Controllers\Admins\PostController as AdminPostController;
use App\Http\Controllers\Admins\SeoController as AdminSeoController;
use App\Http\Controllers\Admins\ProductController;
use App\Http\Controllers\Admins\DashboardController;
use App\Http\Controllers\Admins\FlashSaleController;
use App\Models\Product;

 // Forgot Password
Route::get('/auth/forgot-password', [ClientAuthController::class, 'showForgotPasswordForm'])->name('client.auth.forgot-password');
Route::post('/auth/forgot-password', [ClientAuthController::class, 'sendResetLink'])->name('client.auth.forgot-password.send');
Route::get('/auth/reset-password/{token}', [ClientAuthController::class, 'showResetPasswordForm'])->name('client.auth.reset-password');
Route::post('/auth/reset-password', [ClientAuthController::class, 'resetPassword'])->name('client.auth.reset-password.handle');

Route::prefix('/auth')->name('client.auth.')->group(function () {
    Route::get('/login', [ClientAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [ClientAuthController::class, 'login'])->name('login.handle');
    Route::get('/register', [ClientAuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [ClientAuthController::class, 'register'])->name('register.handle');

    Route::post('/logout', [ClientAuthController::class, 'logout'])
        ->middleware('auth:web')
        ->name('logout');
});

Route::middleware('auth:web')->group(function () {
    Route::get('/profile', [ProfileController::class, 'index'])->name('client.profile.index');
    Route::post('/profile', [ProfileController::class, 'update'])->name('client.profile.update');
    Route::post('/profile/change-password', [ProfileController::class, 'changePassword'])->name('client.profile.change-password');
    Route::post('/profile/preferences', [ProfileController::class, 'updatePreferences'])->name('client.profile.preferences');
    Route::get('/profile/activities', [ProfileController::class, 'activities'])->name('client.profile.activities');

    Route::prefix('profile/addresses')->name('client.addresses.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Clients\AddressController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\Clients\AddressController::class, 'store'])->name('store');
        Route::post('/{address}/update', [\App\Http\Controllers\Clients\AddressController::class, 'update'])->name('update');
        Route::delete('/{address}', [\App\Http\Controllers\Clients\AddressController::class, 'destroy'])->name('destroy');
        Route::post('/{address}/set-default', [\App\Http\Controllers\Clients\AddressController::class, 'setDefault'])->name('set-default');
    });

    Route::get('/checkout/addresses', [CheckoutController::class, 'addressesEndpoint'])->name('client.checkout.addresses');
    Route::post('/checkout/select-address', [CheckoutController::class, 'selectAddress'])->name('client.checkout.address.select');
});

// Deals Hot / Flash Sale
Route::get('/deals', function () {
    $flashSale = \App\Models\FlashSale::where('is_active', true)
        ->where('status', 'active')
        ->where('start_time', '<=', now())
        ->where('end_time', '>=', now())
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
            'items.product.primaryImage'
        ])
        ->first();

    $perPage = min(25, max(1, (int) request('per_page', 25)));
    $page = (int) request('page', 1);

    $items = new \Illuminate\Pagination\LengthAwarePaginator(
        collect(),
        0,
        $perPage,
        $page,
        ['path' => url('/deals'), 'pageName' => 'page']
    );

    if ($flashSale && $flashSale->items) {
        $source = collect($flashSale->items)
            ->filter(function ($item) {
                return $item->is_active
                    && ($item->stock > $item->sold)
                    && $item->product
                    && $item->product->is_active
                    && ($item->product->stock_quantity > 0);
            })
            ->map(function ($item) {
                if (!$item->product) {
                    return null;
                }
                $product = clone $item->product;
                $product->old_price = $item->original_price ?? $product->price;
                $product->price = $item->sale_price ?? $product->price;
                $product->flash_sale_stock = $item->stock;
                $product->flash_sale_sold = $item->sold;
                $product->flash_sale_item_id = $item->id;
                return $product;
            })
            ->filter()
            ->values();

        $total = $source->count();
        $results = $source->forPage($page, $perPage)->values();
        if ($total === 0) {
            $flashSale = null;
        } else {
            $items = new \Illuminate\Pagination\LengthAwarePaginator(
                $results,
                $total,
                $perPage,
                $page,
                ['path' => url('/deals'), 'pageName' => 'page']
            );
        }
    } else {
        $flashSale = null;
    }

    return view('clients.pages.deals.index', compact('flashSale', 'items'));
})->name('client.deals.index');

Route::get('/cart', [CartController::class, 'index'])->name('client.cart.index');
Route::post('/cart/add', [CartController::class, 'add'])->name('client.cart.add');
Route::post('/cart/update-quantity', [CartController::class, 'updateQuantity'])->name('client.cart.update.quantity');
Route::post('/cart/remove-item/{id}', [CartController::class, 'removeItem'])->name('client.cart.remove.item');
Route::post('/cart/clear', [CartController::class, 'clear'])->name('client.cart.clear');

Route::get('/check-out/item/{uuid}', [CheckoutController::class, 'checkoutCartItem'])->name('client.checkout.cart.item');
Route::post('/check-out/create/item', [CheckoutController::class, 'createCheckoutItem'])->name('client.checkout.create.item');

// Checkout toàn giỏ hàng
Route::get('/check-out', [CheckoutController::class, 'checkoutCart'])->name('client.checkout.cart');
Route::post('/check-out/create/cart', [CheckoutController::class, 'createCheckoutCart'])->name('client.checkout.create.cart');

// PayOS payment routes
Route::get('/payment/payos/return', [PaymentController::class, 'return'])->name('payment.payos.return');
Route::get('/payment/payos/cancel', [PaymentController::class, 'cancel'])->name('payment.payos.cancel');
Route::post('/payment/payos/webhook', [PaymentController::class, 'webhook'])->name('payment.payos.webhook');

// Payment management page
Route::get('/payment/pending', [PaymentController::class, 'pending'])->name('payment.pending');
Route::post('/payment/pending/retry', [PaymentController::class, 'retry'])->name('payment.pending.retry');
Route::get('/payment/pending/retry', [PaymentController::class, 'retry'])->name('payment.pending.retry.get'); // Thêm GET method
Route::post('/payment/pending/cancel', [PaymentController::class, 'cancelPending'])->name('payment.pending.cancel');

// Order routes
Route::get('/order', [\App\Http\Controllers\Clients\OrderController::class, 'index'])->name('client.order.index');
Route::get('/order/track', [\App\Http\Controllers\Clients\OrderTrackingController::class, 'show'])->name('client.order.track');
Route::post('/order/track', [\App\Http\Controllers\Clients\OrderTrackingController::class, 'lookup'])->name('client.order.track.lookup');
Route::get('/order/{id}', [\App\Http\Controllers\Clients\OrderController::class, 'show'])->name('client.order.show');

// Voucher routes
Route::middleware('auth:web')->group(function () {
    Route::post('/voucher/validate', [VoucherController::class, 'validateVoucher'])->name('client.voucher.validate');
    Route::get('/voucher/available', [VoucherController::class, 'getAvailableVouchers'])->name('client.voucher.available');
});

// Favorites (Wishlist)
Route::prefix('favorites')->name('client.favorites.')->group(function () {
    Route::get('/', [FavoriteController::class, 'index'])->name('index');
    Route::post('/toggle/{productId}', [FavoriteController::class, 'toggle'])->name('toggle');
    Route::delete('/remove/{productId}', [FavoriteController::class, 'remove'])->name('remove');
});

// AJAX Newsletter Subscription
Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe'])
    ->name('newsletter.subscribe');
Route::get('/newsletter/verify/{token}', [NewsletterController::class, 'verify'])
    ->name('newsletter.verify');
Route::get('/newsletter/unsubscribe/{token}', [NewsletterController::class, 'unsubscribe'])
    ->name('newsletter.unsubscribe');

Route::get('/chinh-sach-doi-tra', function() {
    return view('clients.pages.policy.return');
})->name('client.policy.return');

Route::get('/chinh-sach-ban-hang', function() {
    return view('clients.pages.policy.sale');
})->name('client.policy.sale');

Route::get('/chinh-sach-bao-hanh', function() {
    return view('clients.pages.policy.warranty');
})->name('client.policy.warranty');

Route::get('/security/email/verify/{token}', [AccountEmailVerificationController::class, 'verify'])
    ->middleware('signed')
    ->name('account.email.verify');

Route::get('/dieu-khoan-su-dung', function() {
    return view('clients.pages.policy.terms');
})->name('client.policy.terms');

Route::get('/chinh-sach-giao-hang', function() {
    return view('clients.pages.policy.delivery');
})->name('client.policy.delivery');

Route::get('/chinh-sach-bao-mat', function() {
    return view('clients.pages.policy.privacy');
})->name('client.policy.privacy');

Route::get('/chinh-sach-thanh-toan', function() {
    return view('clients.pages.policy.payment');
})->name('client.policy.payment');

Route::get('/gioi-thieu', function() {
    $productNew = Product::active()->with('primaryImage')->orderBy('created_at', 'desc')->inRandomOrder()->limit(9)->get() ?? [];
    return view('clients.pages.home.introduction', compact('productNew'));
})->name('client.page.introduction');

Route::get('/lien-he', function() {
    $productNew = Product::active()->with('primaryImage')->orderBy('created_at', 'desc')->inRandomOrder()->limit(9)->get() ?? [];
    return view('clients.pages.home.contact', compact('productNew'));
})->name('client.page.contact');

Route::post('/api/contact', [\App\Http\Controllers\Clients\ContactController::class, 'store'])->name('client.contact.store');

Route::get('/', [HomeController::class, 'index'])->name('client.home.index');
Route::get('/san-pham/{slug}', [ProductDetailController::class, 'index'])->name('client.product.detail');

Route::get('/shop', [ShopController::class, 'index'])->name('client.product.shop.index');
Route::get('/shop/search', [ShopController::class, 'searchKeyword'])->name('client.product.shop.search.keyword');
Route::post('/api/search', [ShopController::class, 'search'])->name('client.product.shop.search');

Route::post('/contact/phone', [ContactController::class, 'store'])->name('client.page.contact.store');
Route::post('/product/phone-request', [ContactController::class, 'sendPhoneRequest'])->name('client.product.phone.request');

Route::prefix('blog')->name('client.blog.')->group(function () {
    Route::get('/', [BlogController::class, 'index'])->name('index');
    Route::get('/{post:slug}', [BlogController::class, 'show'])->name('show');
});

Route::post('/comments', [\App\Http\Controllers\Api\V1\CommentController::class, 'store'])->name('client.comments.store');

// Tags routes
Route::prefix('tags')->name('client.tags.')->group(function () {
    Route::get('/{slug}', [\App\Http\Controllers\Clients\TagController::class, 'show'])->name('show');
    Route::get('/entity/{entityType}', [\App\Http\Controllers\Clients\TagController::class, 'index'])->name('index');
});

// Robots.txt
Route::get('/robots.txt', [\App\Http\Controllers\RobotsController::class, 'index'])->name('robots.txt');

// Sitemap routes
Route::get('/sitemap', [\App\Http\Controllers\SitemapController::class, 'html'])->name('client.sitemap.html');
Route::get('/sitemap.xml', [\App\Http\Controllers\SitemapController::class, 'index'])->name('sitemap.index');
Route::get('/sitemap-index.xml', [\App\Http\Controllers\SitemapController::class, 'index'])->name('sitemap.index.alias');
Route::get('/sitemap-posts.xml', [\App\Http\Controllers\SitemapController::class, 'posts'])->name('sitemap.posts');
Route::get('/sitemap-posts-{page}.xml', [\App\Http\Controllers\SitemapController::class, 'posts'])->name('sitemap.posts.page');
Route::get('/sitemap-products.xml', [\App\Http\Controllers\SitemapController::class, 'products'])->name('sitemap.products');
Route::get('/sitemap-products-{page}.xml', [\App\Http\Controllers\SitemapController::class, 'products'])->name('sitemap.products.page');
Route::get('/sitemap-categories.xml', [\App\Http\Controllers\SitemapController::class, 'categories'])->name('sitemap.categories');
Route::get('/sitemap-tags.xml', [\App\Http\Controllers\SitemapController::class, 'tags'])->name('sitemap.tags');
Route::get('/sitemap-pages.xml', [\App\Http\Controllers\SitemapController::class, 'pages'])->name('sitemap.pages');
Route::get('/sitemap-images.xml', [\App\Http\Controllers\SitemapController::class, 'images'])->name('sitemap.images');

// Admin routes
Route::prefix('admin')->name('admin.')->group(function () {
    // Remove guest middleware from login route - handle redirect in controller
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');

    Route::middleware(['auth:web', 'admin'])->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('index');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::prefix('products')->name('products.')->group(function () {
            Route::get('/', [ProductController::class, 'index'])->name('index');
            Route::get('/create', [ProductController::class, 'create'])->name('create');
            Route::post('/', [ProductController::class, 'store'])->name('store');
            Route::get('/{product}/edit', [ProductController::class, 'edit'])->name('edit');
            Route::put('/{product}', [ProductController::class, 'update'])->name('update');
            Route::patch('/{product}/restore', [ProductController::class, 'restore'])->name('restore');
            Route::delete('/{product}', [ProductController::class, 'destroy'])->name('destroy');
            Route::post('/{product}/release-lock', [ProductController::class, 'releaseLock'])->name('release-lock');
            Route::post('/bulk-action', [ProductController::class, 'bulkAction'])->name('bulk-action');

            // JSON variants for admin order create
            Route::get('/{product}/variants', [ProductController::class, 'variants'])->name('variants');

            Route::get('/import-excel', [ImportExcelController::class, 'index'])->name('import-excel');
            Route::post('/import-excel', [ImportExcelController::class, 'import'])->name('import-excel.process');
            Route::get('/export-excel', [ImportExcelController::class, 'export'])->name('export-excel');
        });

        Route::prefix('categories')->name('categories.')->group(function () {
            Route::get('/', [CategoryController::class, 'index'])->name('index');
            Route::get('/create', [CategoryController::class, 'create'])->name('create');
            Route::post('/', [CategoryController::class, 'store'])->name('store');
            Route::get('/{category}/edit', [CategoryController::class, 'edit'])->name('edit');
            Route::put('/{category}', [CategoryController::class, 'update'])->name('update');
            Route::patch('/{category}/toggle', [CategoryController::class, 'toggleStatus'])->name('toggle');
            Route::post('/bulk-action', [CategoryController::class, 'bulkAction'])->name('bulk-action');
        });

        Route::prefix('accounts')->name('accounts.')->group(function () {
            Route::get('/', [AccountController::class, 'index'])->name('index');
            Route::get('/create', [AccountController::class, 'create'])->name('create');
            Route::post('/', [AccountController::class, 'store'])->name('store');
            Route::get('/{account}/edit', [AccountController::class, 'edit'])->name('edit');
            Route::put('/{account}', [AccountController::class, 'update'])->name('update');
            Route::patch('/{account}/toggle', [AccountController::class, 'toggleStatus'])->name('toggle');
            Route::post('/bulk-action', [AccountController::class, 'bulkAction'])->name('bulk-action');
        });

        Route::prefix('newsletters')->name('newsletters.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admins\AdminNewsletterController::class, 'index'])->name('index');
            Route::get('/campaign', [\App\Http\Controllers\Admins\AdminNewsletterController::class, 'showCampaignForm'])->name('campaign');
            Route::get('/{id}', [\App\Http\Controllers\Admins\AdminNewsletterController::class, 'show'])->name('show');
            Route::delete('/{id}', [\App\Http\Controllers\Admins\AdminNewsletterController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/change-status', [\App\Http\Controllers\Admins\AdminNewsletterController::class, 'changeStatus'])->name('change-status');
            Route::post('/{id}/resend-verify', [\App\Http\Controllers\Admins\AdminNewsletterController::class, 'resendVerifyEmail'])->name('resend-verify');
            Route::post('/send', [\App\Http\Controllers\Admins\AdminNewsletterController::class, 'sendBulkEmail'])->name('send');
        });

        Route::prefix('tags')->name('tags.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admins\TagController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admins\TagController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admins\TagController::class, 'store'])->name('store');
            Route::get('/suggest', [\App\Http\Controllers\Admins\TagController::class, 'suggest'])->name('suggest');
            Route::get('/suggest-from-content', [\App\Http\Controllers\Admins\TagController::class, 'suggestFromContent'])->name('suggest-from-content');
            Route::get('/entities', [\App\Http\Controllers\Admins\TagController::class, 'getEntities'])->name('entities');
            Route::get('/{tag}/edit', [\App\Http\Controllers\Admins\TagController::class, 'edit'])->name('edit');
            Route::put('/{tag}', [\App\Http\Controllers\Admins\TagController::class, 'update'])->name('update');
            Route::delete('/{tag}', [\App\Http\Controllers\Admins\TagController::class, 'destroy'])->name('destroy');
            Route::post('/bulk-delete', [\App\Http\Controllers\Admins\TagController::class, 'destroyMultiple'])->name('bulk-delete');
            Route::post('/merge', [\App\Http\Controllers\Admins\TagController::class, 'merge'])->name('merge');
        });

        Route::prefix('sitemap')->name('sitemap.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admins\SitemapController::class, 'index'])->name('index');
            Route::post('/config', [\App\Http\Controllers\Admins\SitemapController::class, 'updateConfig'])->name('config.update');
            Route::post('/rebuild', [\App\Http\Controllers\Admins\SitemapController::class, 'rebuild'])->name('rebuild');
            Route::post('/clear-cache', [\App\Http\Controllers\Admins\SitemapController::class, 'clearCache'])->name('clear-cache');
            Route::get('/preview', [\App\Http\Controllers\Admins\SitemapController::class, 'preview'])->name('preview');
            Route::post('/ping', [\App\Http\Controllers\Admins\SitemapController::class, 'ping'])->name('ping');
            Route::post('/excludes', [\App\Http\Controllers\Admins\SitemapController::class, 'storeExclude'])->name('excludes.store');
            Route::delete('/excludes/{id}', [\App\Http\Controllers\Admins\SitemapController::class, 'deleteExclude'])->name('excludes.delete');
            Route::patch('/excludes/{id}/toggle', [\App\Http\Controllers\Admins\SitemapController::class, 'toggleExclude'])->name('excludes.toggle');
        });

        Route::prefix('media')->name('media.')->group(function () {
            Route::get('/', [AdminMediaController::class, 'index'])->name('index');
            Route::get('/search', AdminMediaSearchController::class)->name('search');
            Route::get('/targets', AdminMediaTargetController::class)->name('targets');
            Route::post('/upload', [AdminMediaUploadController::class, 'store'])->name('upload');
            Route::post('/update/{id}', [AdminMediaController::class, 'update'])->name('update');
            Route::post('/delete/{id}', AdminMediaDeleteController::class)->name('delete');
            Route::post('/assign-to-model', AdminMediaAssignController::class)->name('assign');
            
            // Media Library routes (WordPress-style)
            Route::get('/library', [\App\Http\Controllers\Admins\MediaLibraryController::class, 'index'])->name('library.index');
            Route::post('/library', [\App\Http\Controllers\Admins\MediaLibraryController::class, 'store'])->name('library.store');
            Route::delete('/library/{id}', [\App\Http\Controllers\Admins\MediaLibraryController::class, 'destroy'])->name('library.destroy');
        });

        Route::prefix('vouchers')->name('vouchers.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admins\VoucherController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admins\VoucherController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admins\VoucherController::class, 'store'])->name('store');
            Route::post('/upload-image', [\App\Http\Controllers\Admins\VoucherController::class, 'uploadImage'])->name('upload-image');
            Route::get('/products', [\App\Http\Controllers\Admins\VoucherController::class, 'getProducts'])->name('products');
            Route::get('/analytics', [\App\Http\Controllers\Admins\VoucherAnalyticsController::class, 'dashboard'])->name('analytics');
            Route::get('/analytics/{voucherId}', [\App\Http\Controllers\Admins\VoucherAnalyticsController::class, 'voucherDetail'])->name('analytics.detail');
            Route::get('/analytics/api/revenue-trend/{voucherId}', [\App\Http\Controllers\Admins\VoucherAnalyticsController::class, 'getRevenueTrendData'])->name('analytics.api.revenue-trend');
            Route::get('/analytics/api/overall-stats', [\App\Http\Controllers\Admins\VoucherAnalyticsController::class, 'getOverallStats'])->name('analytics.api.overall-stats');
            Route::get('/{voucher}', [\App\Http\Controllers\Admins\VoucherController::class, 'show'])->name('show');
            Route::get('/{voucher}/edit', [\App\Http\Controllers\Admins\VoucherController::class, 'edit'])->name('edit');
            Route::put('/{voucher}', [\App\Http\Controllers\Admins\VoucherController::class, 'update'])->name('update');
            Route::delete('/{voucher}', [\App\Http\Controllers\Admins\VoucherController::class, 'destroy'])->name('destroy');
            Route::post('/{voucher}/toggle', [\App\Http\Controllers\Admins\VoucherController::class, 'toggle'])->name('toggle');
            Route::post('/{voucher}/duplicate', [\App\Http\Controllers\Admins\VoucherController::class, 'duplicate'])->name('duplicate');
            Route::post('/{voucherId}/restore', [\App\Http\Controllers\Admins\VoucherController::class, 'restore'])->name('restore');
            Route::post('/test/simulate', [\App\Http\Controllers\Admins\VoucherController::class, 'test'])->name('test');
        });

        Route::prefix('api/accounts')->name('api.accounts.')->group(function () {
            Route::get('/', [AccountApiController::class, 'index'])->name('index');
            Route::post('/', [AccountApiController::class, 'store'])->name('store');
            Route::get('/{account}', [AccountApiController::class, 'show'])->name('show');
            Route::put('/{account}', [AccountApiController::class, 'update'])->name('update');
            Route::delete('/{account}', [AccountApiController::class, 'destroy'])->name('destroy');
            Route::patch('/{account}/toggle', [AccountApiController::class, 'toggle'])->name('toggle');
            Route::patch('/{account}/role', [AccountApiController::class, 'changeRole'])->name('change-role');
            Route::patch('/{account}/password', [AccountApiController::class, 'resetPassword'])->name('reset-password');
            Route::post('/{account}/force-logout', [AccountApiController::class, 'forceLogout'])->name('force-logout');
            Route::post('/{account}/verify-email', [AccountApiController::class, 'verifyEmail'])->name('verify-email');

            Route::get('/{account}/logs', [AccountLogController::class, 'index'])->name('logs.index');
            Route::get('/{account}/logs/export', [AccountLogController::class, 'export'])->name('logs.export');

            Route::get('/{account}/profile', [AccountProfileController::class, 'show'])->name('profile.show');
            Route::put('/{account}/profile', [AccountProfileController::class, 'update'])->name('profile.update');
            Route::patch('/{account}/profile/visibility', [AccountProfileController::class, 'toggleVisibility'])->name('profile.visibility');
            Route::post('/{account}/profile/avatar', [AccountProfileController::class, 'upload'])->name('profile.avatar');
        });

        Route::resource('settings', SettingController::class)->except(['show'])->names('settings');
        Route::resource('banners', BannerController::class)->except(['show'])->names('banners');
        Route::patch('banners/{banner}/toggle', [BannerController::class, 'toggle'])->name('banners.toggle');

        Route::resource('email-accounts', EmailAccountController::class)->names('email-accounts');
        Route::post('email-accounts/{email_account}/set-default', [EmailAccountController::class, 'setDefault'])->name('email-accounts.set-default');

        Route::resource('posts', AdminPostController::class)->names('posts');
        Route::post('posts/{post}/publish', [AdminPostController::class, 'publish'])->name('posts.publish');
        Route::post('posts/{post}/archive', [AdminPostController::class, 'archive'])->name('posts.archive');
        Route::post('posts/{post}/duplicate', [AdminPostController::class, 'duplicate'])->name('posts.duplicate');
        Route::post('posts/{post}/feature', [AdminPostController::class, 'feature'])->name('posts.feature');
        Route::post('posts/{post}/unfeature', [AdminPostController::class, 'unfeature'])->name('posts.unfeature');
        Route::patch('posts/{post}/restore', [AdminPostController::class, 'restore'])->name('posts.restore');
        Route::get('posts/{post}/revisions', [AdminPostController::class, 'revisions'])->name('posts.revisions');
        Route::post('posts/{post}/autosave', [AdminPostController::class, 'autosave'])->name('posts.autosave');
        Route::post('posts/{post}/revisions/{revisionId}/restore', [AdminPostController::class, 'restoreRevision'])->name('posts.revisions.restore');

        Route::post('seo/analyze', [AdminSeoController::class, 'analyze'])->name('seo.analyze');

        // Flash Sale routes
        Route::prefix('flash-sales')->name('flash-sales.')->group(function () {
            Route::get('/', [FlashSaleController::class, 'index'])->name('index');
            Route::get('/create', [FlashSaleController::class, 'create'])->name('create');
            Route::post('/', [FlashSaleController::class, 'store'])->name('store');
            
            // Search products - Phải đặt TRƯỚC route /{flash_sale} để tránh conflict
            Route::get('/search-products', [FlashSaleController::class, 'searchProducts'])->name('search-products');
        Route::get('/products-by-category', [FlashSaleController::class, 'productsByCategory'])->name('products.by-category');
            
            Route::get('/{flash_sale}', [FlashSaleController::class, 'show'])->name('show');
            Route::get('/{flash_sale}/edit', [FlashSaleController::class, 'edit'])->name('edit');
            Route::put('/{flash_sale}', [FlashSaleController::class, 'update'])->name('update');
            Route::delete('/{flash_sale}', [FlashSaleController::class, 'destroy'])->name('destroy');
            Route::post('/{flash_sale}/duplicate', [FlashSaleController::class, 'duplicate'])->name('duplicate');
            Route::post('/{flash_sale}/publish', [FlashSaleController::class, 'publish'])->name('publish');
            Route::post('/{flash_sale}/toggle-active', [FlashSaleController::class, 'toggleActive'])->name('toggle-active');
            Route::get('/{flash_sale}/preview', [FlashSaleController::class, 'preview'])->name('preview');
            Route::get('/{flash_sale}/stats', [FlashSaleController::class, 'stats'])->name('stats');
            Route::get('/{flash_sale}/statistics/revenue', [FlashSaleController::class, 'revenueByTime'])->name('statistics.revenue');
            Route::get('/{flash_sale}/statistics/conversion', [FlashSaleController::class, 'conversionMetrics'])->name('statistics.conversion');
            Route::get('/{flash_sale}/statistics/heatmap', [FlashSaleController::class, 'salesHeatmap'])->name('statistics.heatmap');
            Route::get('/compare', [FlashSaleController::class, 'compare'])->name('compare');
            
            // Items management
            Route::get('/{flash_sale}/items', [FlashSaleController::class, 'items'])->name('items');
            Route::post('/{flash_sale}/items', [FlashSaleController::class, 'addItem'])->name('items.store');
            Route::post('/{flash_sale}/items/by-categories', [FlashSaleController::class, 'addItemsByCategories'])->name('items.by-categories');
            Route::post('/{flash_sale}/items/import-excel', [FlashSaleController::class, 'importItemsFromExcel'])->name('items.import-excel');
            Route::get('/{flash_sale}/items/import-template', [FlashSaleController::class, 'downloadImportTemplate'])->name('items.import-template');
            Route::post('/{flash_sale}/items/bulk-action', [FlashSaleController::class, 'bulkActionItems'])->name('items.bulk-action');
            Route::get('/{flash_sale}/items/{item}/price-logs', [FlashSaleController::class, 'priceLogs'])->name('items.price-logs');
            Route::get('/{flash_sale}/suggest-products', [FlashSaleController::class, 'suggestBestSellingProducts'])->name('suggest-products');
            Route::put('/{flash_sale}/items/{item}', [FlashSaleController::class, 'updateItem'])->name('items.update');
            Route::delete('/{flash_sale}/items/{item}', [FlashSaleController::class, 'deleteItem'])->name('items.destroy');
            Route::delete('/{flash_sale}/items', [FlashSaleController::class, 'deleteAllItems'])->name('items.delete-all');
            Route::post('/{flash_sale}/items/bulk-add', [FlashSaleController::class, 'bulkAddItems'])->name('items.bulk-add');
        });

        Route::prefix('carts')->name('carts.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admins\CartController::class, 'index'])->name('index');
            Route::get('/create-order', [\App\Http\Controllers\Admins\CartController::class, 'createOrderIndex'])->name('create-order.index');
            Route::get('/{cart}', [\App\Http\Controllers\Admins\CartController::class, 'show'])->name('show');
            Route::get('/{cart}/edit', [\App\Http\Controllers\Admins\CartController::class, 'edit'])->name('edit');
            Route::put('/{cart}', [\App\Http\Controllers\Admins\CartController::class, 'update'])->name('update');
            Route::delete('/{cart}', [\App\Http\Controllers\Admins\CartController::class, 'destroy'])->name('destroy');
            Route::post('/{cart}/recalculate', [\App\Http\Controllers\Admins\CartController::class, 'recalculate'])->name('recalculate');
            Route::get('/{cart}/create-order', [\App\Http\Controllers\Admins\CartController::class, 'createOrder'])->name('create-order');
            Route::post('/{cart}/store-order', [\App\Http\Controllers\Admins\CartController::class, 'storeOrder'])->name('store-order');
        });

        Route::prefix('cart-items')->name('cart-items.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admins\CartItemController::class, 'index'])->name('index');
            Route::get('/{cartItem}/edit', [\App\Http\Controllers\Admins\CartItemController::class, 'edit'])->name('edit');
            Route::put('/{cartItem}', [\App\Http\Controllers\Admins\CartItemController::class, 'update'])->name('update');
            Route::delete('/{cartItem}', [\App\Http\Controllers\Admins\CartItemController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('trash')->name('trash.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admins\TrashController::class, 'index'])->name('index');
            Route::patch('/{type}/{id}', [\App\Http\Controllers\Admins\TrashController::class, 'restore'])->name('restore');
            Route::delete('/{type}/{id}', [\App\Http\Controllers\Admins\TrashController::class, 'forceDelete'])->name('force-delete');
        });

        Route::prefix('contacts')->name('contacts.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admins\ContactController::class, 'index'])->name('index');
            Route::get('/{contact}', [\App\Http\Controllers\Admins\ContactController::class, 'show'])->name('show');
            Route::post('/{contact}/update-status', [\App\Http\Controllers\Admins\ContactController::class, 'updateStatus'])->name('update-status');
            Route::post('/{contact}/update-note', [\App\Http\Controllers\Admins\ContactController::class, 'updateNote'])->name('update-note');
            Route::post('/{contact}/reply', [\App\Http\Controllers\Admins\ContactController::class, 'reply'])->name('reply');
            Route::post('/bulk-action', [\App\Http\Controllers\Admins\ContactController::class, 'bulkAction'])->name('bulk-action');
            Route::delete('/{contact}', [\App\Http\Controllers\Admins\ContactController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/restore', [\App\Http\Controllers\Admins\ContactController::class, 'restore'])->name('restore');
        });

        Route::prefix('addresses')->name('addresses.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admins\AddressController::class, 'index'])->name('index');
            Route::get('/{address}', [\App\Http\Controllers\Admins\AddressController::class, 'show'])->name('show');
            Route::get('/{address}/edit', [\App\Http\Controllers\Admins\AddressController::class, 'edit'])->name('edit');
            Route::put('/{address}', [\App\Http\Controllers\Admins\AddressController::class, 'update'])->name('update');
            Route::delete('/{address}', [\App\Http\Controllers\Admins\AddressController::class, 'destroy'])->name('destroy');
            Route::post('/{address}/set-default', [\App\Http\Controllers\Admins\AddressController::class, 'setDefault'])->name('set-default');
        });

        // Comments management
        Route::prefix('comments')->name('comments.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admins\CommentController::class, 'index'])->name('index');
            Route::get('/{comment}', [\App\Http\Controllers\Admins\CommentController::class, 'show'])->name('show');
            Route::put('/{comment}', [\App\Http\Controllers\Admins\CommentController::class, 'update'])->name('update');
            Route::delete('/{comment}', [\App\Http\Controllers\Admins\CommentController::class, 'destroy'])->name('destroy');
            Route::post('/{comment}/toggle-approve', [\App\Http\Controllers\Admins\CommentController::class, 'toggleApprove'])->name('toggle-approve');
            Route::post('/bulk-delete', [\App\Http\Controllers\Admins\CommentController::class, 'bulkDelete'])->name('bulk-delete');
        });

        Route::resource('posts', \App\Http\Controllers\Admins\PostController::class)->names('posts');
        Route::post('posts/{post}/publish', [\App\Http\Controllers\Admins\PostController::class, 'publish'])->name('posts.publish');
        Route::post('posts/{post}/archive', [\App\Http\Controllers\Admins\PostController::class, 'archive'])->name('posts.archive');
        Route::post('posts/{post}/duplicate', [\App\Http\Controllers\Admins\PostController::class, 'duplicate'])->name('posts.duplicate');
        Route::post('posts/{post}/feature', [\App\Http\Controllers\Admins\PostController::class, 'feature'])->name('posts.feature');
        Route::post('posts/{post}/unfeature', [\App\Http\Controllers\Admins\PostController::class, 'unfeature'])->name('posts.unfeature');
        Route::post('posts/{post}/restore', [\App\Http\Controllers\Admins\PostController::class, 'restore'])->name('posts.restore');
        Route::get('posts/{post}/revisions', [\App\Http\Controllers\Admins\PostController::class, 'revisions'])->name('posts.revisions');
        Route::post('posts/{post}/autosave', [\App\Http\Controllers\Admins\PostController::class, 'autosave'])->name('posts.autosave');
        Route::post('posts/{post}/revisions/{revision}/restore', [\App\Http\Controllers\Admins\PostController::class, 'restoreRevision'])->name('posts.revisions.restore');
        Route::post('seo/analyze', [\App\Http\Controllers\Admins\SeoController::class, 'analyze'])->name('seo.analyze');

        Route::prefix('orders')->name('orders.')->group(function () {
            Route::get('/pick-shifts', [\App\Http\Controllers\Admins\OrderController::class, 'getPickShifts'])->name('get-pick-shifts');
            Route::post('/{order}/create-ghn', [\App\Http\Controllers\Admins\OrderController::class, 'createGHNOrder'])->name('create-ghn');
            Route::get('/{order}/print-ghn', [\App\Http\Controllers\Admins\OrderController::class, 'printGhnOrder'])->name('print-ghn');
            Route::post('/{order}/shipping-status', [\App\Http\Controllers\Admins\OrderController::class, 'addShippingStatus'])->name('shipping-status.store');
            Route::post('/{order}/sync-ghn', [\App\Http\Controllers\Admins\OrderController::class, 'syncGHNStatus'])->name('sync-ghn');
            Route::post('/{order}/create-ghn-ticket', [\App\Http\Controllers\Admins\OrderController::class, 'createGhnTicket'])->name('create-ghn-ticket');
            Route::get('/{order}/get-ghn-ticket', [\App\Http\Controllers\Admins\OrderController::class, 'getGhnTicket'])->name('get-ghn-ticket');
            Route::post('/{order}/sync-ghn-ticket', [\App\Http\Controllers\Admins\OrderController::class, 'syncGhnTicket'])->name('sync-ghn-ticket');
            Route::post('/{order}/sync-ghn-ticket-list', [\App\Http\Controllers\Admins\OrderController::class, 'syncGhnTicketList'])->name('sync-ghn-ticket-list');
            Route::post('/{order}/reply-ghn-ticket', [\App\Http\Controllers\Admins\OrderController::class, 'replyGhnTicket'])->name('reply-ghn-ticket');
            Route::get('/{order}/edit-ghn', [\App\Http\Controllers\Admins\OrderController::class, 'editGhnOrder'])->name('edit-ghn');
            Route::put('/{order}/update-ghn', [\App\Http\Controllers\Admins\OrderController::class, 'updateGhnOrder'])->name('update-ghn');
            Route::get('/track', [\App\Http\Controllers\Admins\OrderController::class, 'trackForm'])->name('track');
            Route::post('/track', [\App\Http\Controllers\Admins\OrderController::class, 'trackLookup'])->name('track.lookup');
            Route::get('/{order}/invoice', [\App\Http\Controllers\Admins\OrderController::class, 'invoice'])->name('invoice');
            Route::get('/{order}/invoice/pdf', [\App\Http\Controllers\Admins\OrderController::class, 'invoicePdf'])->name('invoice.pdf');
            Route::get('/', [\App\Http\Controllers\Admins\OrderController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admins\OrderController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admins\OrderController::class, 'store'])->name('store');
            Route::get('/{order}', [\App\Http\Controllers\Admins\OrderController::class, 'show'])->name('show');
            Route::get('/{order}/edit', [\App\Http\Controllers\Admins\OrderController::class, 'edit'])->name('edit');
            Route::put('/{order}', [\App\Http\Controllers\Admins\OrderController::class, 'update'])->name('update');
            Route::delete('/{order}', [\App\Http\Controllers\Admins\OrderController::class, 'destroy'])->name('destroy');
            Route::patch('/{order}/update-status', [\App\Http\Controllers\Admins\OrderController::class, 'updateStatus'])->name('update-status');
            Route::patch('/{order}/cancel', [\App\Http\Controllers\Admins\OrderController::class, 'cancel'])->name('cancel');
            Route::patch('/{order}/complete', [\App\Http\Controllers\Admins\OrderController::class, 'complete'])->name('complete');
            Route::post('/{order}/recalculate', [\App\Http\Controllers\Admins\OrderController::class, 'recalculate'])->name('recalculate');
        });

        Route::prefix('order-items')->name('order-items.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admins\OrderItemController::class, 'index'])->name('index');
            Route::get('/{orderItem}/edit', [\App\Http\Controllers\Admins\OrderItemController::class, 'edit'])->name('edit');
            Route::put('/{orderItem}', [\App\Http\Controllers\Admins\OrderItemController::class, 'update'])->name('update');
            Route::delete('/{orderItem}', [\App\Http\Controllers\Admins\OrderItemController::class, 'destroy'])->name('destroy');
        });

        // Canifa Crawler Tool
        Route::prefix('canifa-crawler')->name('canifa-crawler.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admins\CanifaCrawlerController::class, 'index'])->name('index');
            Route::post('/crawl', [\App\Http\Controllers\Admins\CanifaCrawlerController::class, 'crawl'])->name('crawl');
            Route::post('/generate-urls', [\App\Http\Controllers\Admins\CanifaCrawlerController::class, 'generateUrls'])->name('generate-urls');
        });

        // Routine Crawler Tool
        Route::prefix('routine-crawler')->name('routine-crawler.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admins\RoutineCrawlerController::class, 'index'])->name('index');
            Route::post('/crawl', [\App\Http\Controllers\Admins\RoutineCrawlerController::class, 'crawl'])->name('crawl');
        });

        // Onoff Crawler Tool
        Route::prefix('onoff-crawler')->name('onoff-crawler.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admins\OnoffCrawlerController::class, 'index'])->name('index');
            Route::post('/crawl', [\App\Http\Controllers\Admins\OnoffCrawlerController::class, 'crawl'])->name('crawl');
        });

        // Coolmate Crawler Tool
        Route::prefix('coolmate-crawler')->name('coolmate-crawler.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admins\CoolmateCrawlerController::class, 'index'])->name('index');
            Route::post('/crawl', [\App\Http\Controllers\Admins\CoolmateCrawlerController::class, 'crawl'])->name('crawl');
        });
    });
});

Route::get('/{slug}', [ShopController::class, 'index'])->name('client.product.category.index');

// Fallback: any unmatched web route → custom 404 view
Route::fallback(function () {
    return response()->view('clients.pages.errors.404', [], 404);
});
