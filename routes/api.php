<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Apis\V1\Cart\CartApiController;
use App\Http\Controllers\Apis\V1\Checkout\CheckoutApiController;
use App\Http\Controllers\Apis\V1\Main\GeneralApiController;
use App\Http\Controllers\Apis\V1\Post\PostApiController;
use App\Http\Controllers\Apis\V1\Product\ProductApiController;
use App\Http\Controllers\Apis\V1\Reels\ReelsApiController;
use App\Http\Controllers\Apis\V1\Search\SearchApiController;
use App\Http\Controllers\Apis\V1\Single\SingleApiController;
use App\Http\Controllers\Apis\V1\User\UserApiController;
use App\Http\Controllers\Apis\V1\Voucher\VoucherApiController;
use App\Http\Controllers\Api\V1\ReelApiController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('/v1')->name('api.v1.')->group(function () {
    Route::prefix('/home')->name('home.')->group(function () {
        Route::get('/products', [ProductApiController::class, 'getProducts'])->name('products');
    });

    Route::prefix('/general')->name('general.')->group(function () {
        Route::get('/geocode', [GeneralApiController::class, 'geocode'])->name('get.geocode');
        Route::get('/districts', [GeneralApiController::class, 'getDistricts'])->name('get.districts');
        Route::get('/wards', [GeneralApiController::class, 'getWards'])->name('get.wards');
        Route::post('/calculate-fee', [GeneralApiController::class, 'calculateFee'])->name('calculate.fee');
    });

    Route::prefix('/cart')->name('cart.')->group(function () {
        Route::get('/', [CartApiController::class, 'index'])->name('index');
        Route::post('/add', [CartApiController::class, 'add'])->name('add');
        Route::put('/update', [CartApiController::class, 'update'])->name('update');
        Route::delete('/remove', [CartApiController::class, 'remove'])->name('remove');
        Route::delete('/clear', [CartApiController::class, 'clear'])->name('clear');
    });

    Route::prefix('/checkout')->name('checkout.')->group(function () {
        Route::post('/process', [CheckoutApiController::class, 'processCheckout'])->name('process');
    });

    Route::prefix('/vouchers')->name('vouchers.')->group(function () {
        Route::post('/apply', [VoucherApiController::class, 'applyVoucher'])->name('apply');
        Route::post('/remove', [VoucherApiController::class, 'removeVoucher'])->name('remove');
    });

    Route::prefix('/single')->name('single.')->group(function () {
        Route::get('/{slug}', [SingleApiController::class, 'getProductBySlug'])->name('product.slug');
        Route::get('/variant/{id}', [SingleApiController::class, 'getVariantById'])->name('variant.id');
        Route::get('/reviews/{id}', [SingleApiController::class, 'getProductReviews'])->name('reviews');
    });

    Route::prefix('/search')->name('search.')->group(function () {
        Route::get('/suggest', [SearchApiController::class, 'suggest'])->name('suggest');
    });

    Route::prefix('/posts')->name('posts.')->group(function () {
        Route::get('/', [PostApiController::class, 'index'])->name('index');
        Route::get('/{slug}', [PostApiController::class, 'show'])->name('show');
        Route::get('/category/{slug}', [PostApiController::class, 'category'])->name('category');
        Route::get('/comments/{id}', [PostApiController::class, 'comments'])->name('comments');
        Route::post('/comments/{id}', [PostApiController::class, 'storeComment'])->name('comments.store');
    });

    Route::prefix('/users')->name('users.')->group(function () {
        Route::get('/profile', [UserApiController::class, 'profile'])->name('profile');
        Route::put('/profile', [UserApiController::class, 'updateProfile'])->name('profile.update');
        Route::get('/orders', [UserApiController::class, 'orders'])->name('orders');
    });

    Route::prefix('/payos')->name('payos.')->group(function () {
        Route::post('/webhook', [\App\Http\Controllers\Apis\V1\PayOS\PayOSController::class, 'handleWebhook'])->name('webhook');
        Route::get('/payment/{id}', [\App\Http\Controllers\Apis\V1\PayOS\PayOSController::class, 'getPaymentInfo'])->name('payment.info');
        Route::post('/cancel/{id}', [\App\Http\Controllers\Apis\V1\PayOS\PayOSController::class, 'cancelPayment'])->name('cancel');
        Route::get('/payments', [\App\Http\Controllers\Apis\V1\PayOS\PayOSController::class, 'getPayments'])->name('payments');
    });

    // Reels API Routes (v1)
    Route::prefix('/reels')->middleware('anonymous.identity')->name('reels.')->group(function () {
        Route::get('/identity/me', [ReelApiController::class, 'me'])->name('identity.me');
        Route::get('/stickers', [ReelApiController::class, 'getStickers'])->name('stickers');
        Route::get('/stickers/categories', [ReelApiController::class, 'getStickerCategories'])->name('stickers.categories');

        Route::get('/', [ReelApiController::class, 'index'])->name('index');
        Route::get('/{id}', [ReelApiController::class, 'show'])->whereNumber('id')->name('show');
        Route::get('/slug/{slug}', [ReelApiController::class, 'showBySlug'])->name('slug');
        Route::post('/{id}/view', [ReelApiController::class, 'view'])->whereNumber('id')->name('view');
        
        // Thả tim Reel & Comment (Rate Limit chống spam bot)
        Route::get('/{id}/like-status', [ReelApiController::class, 'getLikeStatus'])->whereNumber('id')->name('like.status');
        Route::middleware('throttle:60,1')->group(function () {
            Route::put('/{id}/like', [ReelApiController::class, 'like'])->whereNumber('id')->name('like.put');
            Route::delete('/{id}/like', [ReelApiController::class, 'unlike'])->whereNumber('id')->name('like.delete');
            Route::post('/{id}/like', [ReelApiController::class, 'like'])->whereNumber('id')->name('like'); // Fallback POST

            Route::put('/comments/{id}/like', [ReelApiController::class, 'likeComment'])->whereNumber('id')->name('comments.like.put');
            Route::delete('/comments/{id}/like', [ReelApiController::class, 'unlikeComment'])->whereNumber('id')->name('comments.like.delete');
            Route::post('/comments/{id}/like', [ReelApiController::class, 'likeComment'])->whereNumber('id')->name('comments.like.post');
        });

        Route::post('/{id}/save', [ReelApiController::class, 'save'])->whereNumber('id')->name('save');
        Route::post('/{id}/share', [ReelApiController::class, 'share'])->whereNumber('id')->name('share');
        
        // Bình luận
        Route::get('/{id}/comments', [ReelApiController::class, 'getComments'])->whereNumber('id')->name('comments.get');
        Route::post('/{id}/comments', [ReelApiController::class, 'postComment'])->whereNumber('id')->name('comments.post');
        Route::get('/comments/{id}/replies', [ReelApiController::class, 'getCommentReplies'])->whereNumber('id')->name('comments.replies');
    });
});

// Direct alias for /api/reels
Route::prefix('/reels')->middleware('anonymous.identity')->group(function () {
    Route::get('/identity/me', [ReelApiController::class, 'me']);
    Route::get('/stickers', [ReelApiController::class, 'getStickers']);
    Route::get('/stickers/categories', [ReelApiController::class, 'getStickerCategories']);

    Route::get('/', [ReelApiController::class, 'index']);
    Route::get('/{id}', [ReelApiController::class, 'show'])->whereNumber('id');
    Route::get('/slug/{slug}', [ReelApiController::class, 'showBySlug']);
    Route::post('/{id}/view', [ReelApiController::class, 'view'])->whereNumber('id');
    Route::get('/{id}/like-status', [ReelApiController::class, 'getLikeStatus'])->whereNumber('id');

    Route::middleware('throttle:60,1')->group(function () {
        Route::put('/{id}/like', [ReelApiController::class, 'like'])->whereNumber('id');
        Route::delete('/{id}/like', [ReelApiController::class, 'unlike'])->whereNumber('id');
        Route::post('/{id}/like', [ReelApiController::class, 'like'])->whereNumber('id');

        Route::put('/comments/{id}/like', [ReelApiController::class, 'likeComment'])->whereNumber('id');
        Route::delete('/comments/{id}/like', [ReelApiController::class, 'unlikeComment'])->whereNumber('id');
    });

    Route::post('/{id}/save', [ReelApiController::class, 'save'])->whereNumber('id');
    Route::post('/{id}/share', [ReelApiController::class, 'share'])->whereNumber('id');
    Route::get('/{id}/comments', [ReelApiController::class, 'getComments'])->whereNumber('id');
    Route::post('/{id}/comments', [ReelApiController::class, 'postComment'])->whereNumber('id');
    Route::get('/comments/{id}/replies', [ReelApiController::class, 'getCommentReplies'])->whereNumber('id');
});
