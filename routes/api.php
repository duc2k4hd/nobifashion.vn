<?php

use App\Http\Controllers\Api\V1\CategoryController as ApiCategoryController;
use App\Http\Controllers\Api\V1\PostController as ApiPostController;
use App\Http\Controllers\Api\V1\VoucherController as ApiVoucherController;
use App\Http\Controllers\Api\V1\TagController as ApiTagController;
use App\Http\Controllers\Api\V1\CommentController as ApiCommentController;
use App\Http\Controllers\Apis\V1\GHN\Clients\GHNClientApiController;
use App\Http\Controllers\Apis\V1\Main\GeneralApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:30,1')->prefix('/v1')->name('api.v1.')->group(function() {
    Route::prefix('/ghn')->name('client.')->group(function() {
        Route::get('/province', [GHNClientApiController::class, 'getProvince'])->name('get.province');
        Route::post('/district/{province_id}', [GHNClientApiController::class, 'getDistrict'])->name('get.district');
        Route::post('/ward/{district_id}', [GHNClientApiController::class, 'getWard'])->name('get.ward');

        // Services
        Route::get('/services/{district_id}', [GHNClientApiController::class, 'getServices'])->name('get.services');
        Route::post('/calculate-fee', [GHNClientApiController::class, 'calculateFee'])->name('calculate.fee');
    });

    Route::prefix('/general')->name('general.')->group(function() {
        Route::get('/geocode', [GeneralApiController::class, 'geocode'])->name('get.geocode');
    });

    Route::prefix('/posts')->name('posts.')->group(function () {
        Route::get('/', [ApiPostController::class, 'index'])->name('index');
        Route::get('/{slug}', [ApiPostController::class, 'show'])->name('show');

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/', [ApiPostController::class, 'store'])->name('store');
            Route::put('/{post}', [ApiPostController::class, 'update'])->name('update');
            Route::delete('/{post}', [ApiPostController::class, 'destroy'])->name('destroy');
        });
    });

    Route::get('/categories', [ApiCategoryController::class, 'index'])->name('categories.index');
    
    // Comments API
    Route::prefix('/comments')->name('comments.')->group(function () {
        Route::get('/', [ApiCommentController::class, 'index'])->name('index');
        Route::get('/{comment}', [ApiCommentController::class, 'show'])->name('show');
        Route::post('/', [ApiCommentController::class, 'store'])->name('store');
        Route::post('/{comment}/report', [ApiCommentController::class, 'report'])->name('report');

        Route::middleware('auth:sanctum')->group(function () {
            Route::put('/{comment}', [ApiCommentController::class, 'update'])->name('update');
            Route::delete('/{comment}', [ApiCommentController::class, 'destroy'])->name('destroy');
            Route::post('/{comment}/approve', [ApiCommentController::class, 'approve'])->name('approve');
        });
    });

    // Tags API
    Route::prefix('/tags')->name('tags.')->group(function () {
        Route::get('/', [ApiTagController::class, 'index'])->name('index');
        Route::get('/suggest', [ApiTagController::class, 'suggest'])->name('suggest');
        Route::get('/by-entity/{entity_type}/{entity_id}', [ApiTagController::class, 'getByEntity'])->name('by-entity');
        Route::get('/{tag}', [ApiTagController::class, 'show'])->name('show');
        
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/', [ApiTagController::class, 'store'])->name('store');
            Route::put('/{tag}', [ApiTagController::class, 'update'])->name('update');
            Route::delete('/{tag}', [ApiTagController::class, 'destroy'])->name('destroy');
            Route::post('/assign', [ApiTagController::class, 'assign'])->name('assign');
            Route::post('/remove', [ApiTagController::class, 'remove'])->name('remove');
        });
    });
    Route::prefix('/vouchers')->name('vouchers.')->group(function () {
        Route::get('/active', [ApiVoucherController::class, 'active'])->name('active');
        Route::get('/{code}', [ApiVoucherController::class, 'show'])->name('show');
    });
    Route::post('/voucher/apply', [ApiVoucherController::class, 'apply'])->name('voucher.apply');

    Route::middleware('auth:sanctum')->prefix('/addresses')->name('addresses.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\V1\AddressController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\Api\V1\AddressController::class, 'store'])->name('store');
        Route::get('/{address}', [\App\Http\Controllers\Api\V1\AddressController::class, 'show'])->name('show');
        Route::put('/{address}', [\App\Http\Controllers\Api\V1\AddressController::class, 'update'])->name('update');
        Route::delete('/{address}', [\App\Http\Controllers\Api\V1\AddressController::class, 'destroy'])->name('destroy');
        Route::put('/{address}/set-default', [\App\Http\Controllers\Api\V1\AddressController::class, 'setDefault'])->name('set-default');
    });

    Route::get('/address-suggestions', [\App\Http\Controllers\Api\V1\AddressController::class, 'suggestions'])->name('addresses.suggestions');

    // PayOS API Routes
    Route::prefix('/payos')->name('payos.')->group(function() {
        // Test endpoint
        Route::get('/test', [\App\Http\Controllers\Apis\V1\PayOS\PayOSController::class, 'test'])->name('test');
        
        // Create payment link
        Route::post('/create-payment', [\App\Http\Controllers\Apis\V1\PayOS\PayOSController::class, 'createPayment'])->name('create.payment');
        
        // Webhook callback (không cần middleware auth)
        Route::post('/webhook', [\App\Http\Controllers\Apis\V1\PayOS\PayOSController::class, 'webhook'])->name('webhook');
        
        // Cancel payment
        Route::post('/cancel-payment', [\App\Http\Controllers\Apis\V1\PayOS\PayOSController::class, 'cancelPayment'])->name('cancel.payment');
        
        // Get payment info
        Route::get('/payment-info/{orderCode}', [\App\Http\Controllers\Apis\V1\PayOS\PayOSController::class, 'getPaymentInfo'])->name('payment.info');
        
        // Get payments list
        Route::get('/payments', [\App\Http\Controllers\Apis\V1\PayOS\PayOSController::class, 'getPayments'])->name('payments');
    });
});
