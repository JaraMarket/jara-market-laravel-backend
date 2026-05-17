<?php

use App\Http\Controllers\AddressController;
use App\Http\Controllers\AdvertisementController;
use App\Http\Controllers\API\BankController;
use App\Http\Controllers\API\ForgotPasswordController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\PaymentController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\ResetPasswordController;
use App\Http\Controllers\API\SettingsController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\VendorDashboardController;
use App\Http\Controllers\API\WalletController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\FavoritesController;
use App\Http\Controllers\FoodController;
use App\Http\Controllers\HelpTicketController;
use App\Http\Controllers\LgaController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PinController;
use App\Http\Controllers\StateController;
use App\Http\Controllers\VendorCategoryController;
use App\Http\Controllers\API\VerificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
 
Route::get('/queue-check', function () {
    return [
        'pending_jobs' => DB::table('jobs')->count(),
        'failed_jobs' => DB::table('failed_jobs')->count(),
        'queue_connection' => config('queue.default'),
        'timestamp' => now()->toDateTimeString(),
    ];
});

Route::prefix('jaram')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | PUBLIC
    |--------------------------------------------------------------------------
    */

    // Paystack webhook — public, verified by paystack-webhook middleware
    Route::post('/webhook/paystack', [PaymentController::class, 'handlePaystackWebhook'])
        ->middleware('paystack-webhook');

    // Verify transaction after Paystack redirect
    Route::get('/verify-transaction/{reference}', [PaymentController::class, 'verifyTransaction']);

    // Geo
    Route::get('/states', [StateController::class, 'index']);
    Route::get('/states/{state}', [StateController::class, 'findState']);
    Route::get('/lgas', [LgaController::class, 'index']);
    Route::get('/lgas/{lga}', [LgaController::class, 'findLga']);
    Route::get('/country', [CountryController::class, 'index']);
    Route::get('/country/{c}/states', [CountryController::class, 'states']);

    // Public catalogue
    Route::get('/vendors/categories', [ProductController::class, 'getVendorCategories']);
    
    // Email Verification (Magic Link)
    Route::get('/verify-email/{id}/{hash}', [\App\Http\Controllers\API\VerificationController::class, 'verify'])
        ->name('api.verification.verify');

    /*
    |--------------------------------------------------------------------------
    | GUEST AUTH
    |--------------------------------------------------------------------------
    */
    Route::middleware('guest')->group(function () {
        // Registration: throttle to 10 attempts per minute per IP
        Route::post('/register', [UserController::class, 'registerUser'])->middleware('throttle:10,1');
        Route::post('/login', [UserController::class, 'login'])->middleware('throttle:10,1');
        Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink'])->middleware('throttle:5,1');
        Route::post('/reset-password', [ResetPasswordController::class, 'reset']);
        Route::post('/profile-update/{email}', [UserController::class, 'updateProfile']);
        Route::post('/update-vendor-categories/{email}', [VendorCategoryController::class, 'store']);

        // OTP routes: strictly throttled — max 5 attempts per minute per IP
        Route::post('/validate-otp', [UserController::class, 'validateUserRegisterOTP'])->middleware('throttle:5,1');
        Route::post('/validate-email', [UserController::class, 'verifyEmailWithOTP'])->middleware('throttle:5,1');
        Route::post('/resend-otp', [UserController::class, 'resendOtp'])->middleware('throttle:3,1');

        // Social Authentication (Google, Apple, Facebook)
        Route::post('/social/{provider}', [\App\Http\Controllers\API\SocialAuthController::class, 'authenticate']);
    });

    /*
    |--------------------------------------------------------------------------
    | AUTHENTICATED
    |--------------------------------------------------------------------------
    */
    Route::middleware('auth:sanctum')->group(function () {

        Route::post('/logout', [UserController::class, 'logout']);

        // Profile
        Route::get('/fetch-user', [UserController::class, 'fetchUserProfile']);
        Route::post('/update-profile', [UserController::class, 'editUserProfile']);
        Route::patch('/user/change-password', [UserController::class, 'changePassword']);
        Route::get('/my-referrals', [UserController::class, 'myRefferals']);

        /*
        |----------------------------------------------------------------------
        | WALLET
        |----------------------------------------------------------------------
        */
        Route::prefix('wallet')->controller(WalletController::class)->group(function () {
            Route::get('/', 'balance');         // GET  /jaram/wallet
            Route::get('/transactions', 'transactions');    // GET  /jaram/wallet/transactions
            Route::post('/transfer-to-bank', 'transferToBank'); // POST /jaram/wallet/transfer-to-bank
        });

        // Backward-compat alias
        Route::get('/fetch-wallet', [UserController::class, 'fetchUserWallet']);

        /*
        |----------------------------------------------------------------------
        | PAYMENTS
        |----------------------------------------------------------------------
        */
        Route::prefix('payments')->controller(PaymentController::class)->group(function () {
            Route::post('/initialize-transaction', 'initializeFunding'); // POST /jaram/payments/initialize-transaction
            Route::get('/', 'all');               // GET  /jaram/payments
            Route::get('/{id}', 'show');              // GET  /jaram/payments/{id}
        });

        Route::get('/transfers', [PaymentController::class, 'getTransfers']); // GET /jaram/transfers

        // Banks
        Route::get('/banks', [BankController::class, 'index']);

        // Settings
        Route::get('/settings', [SettingsController::class, 'index']);
        Route::post('/settings', [SettingsController::class, 'store']);

        /*
        |----------------------------------------------------------------------
        | NOTIFICATIONS
        |----------------------------------------------------------------------
        */
        Route::prefix('notifications')->controller(NotificationController::class)->group(function () {
            Route::get('/', 'index');
            Route::put('/{id}/read', 'markAsRead');
            Route::post('/token', 'updateFcmToken');
            Route::get('/unread-count', fn () => response()->json([
                'unread' => auth()->user()->unreadNotifications()->count(),
            ]));
        });

        /*
        |----------------------------------------------------------------------
        | CUSTOMER ORDERS
        |----------------------------------------------------------------------
        */
        Route::prefix('orders')->controller(OrderController::class)->group(function () {
            Route::get('/', 'all');
            Route::get('/{order}', 'show');
            Route::post('/', 'store');
            Route::post('/{order}/cancel', 'cancel');
        });

        /*
        |----------------------------------------------------------------------
        | SUPPORT
        |----------------------------------------------------------------------
        */
        Route::prefix('support')->controller(HelpTicketController::class)->group(function () {
            Route::get('/', 'index');
            Route::post('/', 'store');
            Route::get('/{id}', 'show');
            Route::patch('/{id}/status', 'updateStatus');
        });

        /*
        |----------------------------------------------------------------------
        | FAVOURITES
        |----------------------------------------------------------------------
        */
        Route::prefix('favorites')->controller(FavoritesController::class)->group(function () {
            Route::get('/', 'index');
            Route::post('/', 'store');
            Route::delete('/{id}', 'destroy');
        });

        /*
        |----------------------------------------------------------------------
        | ADDRESSES
        |----------------------------------------------------------------------
        */
        Route::prefix('addresses')->controller(AddressController::class)->group(function () {
            Route::get('/', 'index');
            Route::post('/', 'store');
            Route::put('/{address}', 'update');
        });

        /*
        |----------------------------------------------------------------------
        | CATALOGUE
        |----------------------------------------------------------------------
        */
        Route::prefix('fetch')->controller(ProductController::class)->group(function () {
            Route::get('/categories-all-products', 'getCategoriesAllProducts');
            Route::get('/categories-limit-products', 'getCategoriesLimitProducts');
            Route::get('/ingredients', 'fetchingredient');
            Route::get('/product', 'fetchProduct');
            Route::get('/uom', 'fetchUom');
            Route::get('/product/{id}', 'getProductById');
        });

        Route::get('/advertisements', [AdvertisementController::class, 'fetch_adverts']);
        Route::post('/foods', [FoodController::class, 'store']);

        /*
        |----------------------------------------------------------------------
        | PIN
        |----------------------------------------------------------------------
        */
        Route::prefix('pin')->controller(PinController::class)->group(function () {
            Route::post('/set', 'setPin');
            Route::post('/verify', 'verifyPin');
            Route::get('/validate', 'validatePinToken');
            Route::post('/clear', 'clearPinToken');
            Route::post('/request-reset', 'requestReset');
            Route::post('/reset', 'resetPin');
        });

    }); // end auth:sanctum

    /*
    |--------------------------------------------------------------------------
    | VENDOR
    |--------------------------------------------------------------------------
    */
    Route::middleware(['auth:sanctum', 'vendor'])->prefix('vendor')->group(function () {
        Route::get('/orders', [OrderController::class, 'getAvailableOrders']);
        Route::get('/orders/accepted', [OrderController::class, 'myOrders']);
        Route::get('/orders/{item_id}', [OrderController::class, 'showOrderByItemId']);
        Route::post('/orders/item/{item_id}/decision', [OrderController::class, 'decide']);
        Route::get('/dashboard', [VendorDashboardController::class, 'index']);
    });

}); // end /jaram

/*
|==========================================================================
| /api — Master Endpoint Group
|
| These routes satisfy the full master endpoint list for the Flutter apps.
| They map to the same controllers as /jaram (zero duplication of logic),
| plus newly created controllers for missing functionality.
|
| GOLDEN RULE: /jaram routes above are UNTOUCHED.
|==========================================================================
*/

use App\Http\Controllers\API\AuthApiController;
use App\Http\Controllers\API\CustomerApiController;
use App\Http\Controllers\API\VendorApiController;
use App\Http\Controllers\API\AdminApiController;
use App\Http\Controllers\API\PaymentApiController;
use App\Http\Controllers\API\ReviewController;

Route::prefix('api')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | AUTH — Public (guest)
    |--------------------------------------------------------------------------
    */
    Route::prefix('auth')->group(function () {
        // Register + Login — delegate to existing UserController
        Route::post('/register', [UserController::class, 'registerUser']);
        Route::post('/login',    [UserController::class, 'login']);
        Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink']);
        Route::post('/reset-password',  [ResetPasswordController::class, 'reset']);

        // Protected auth routes
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout',         [UserController::class, 'logout']);
            Route::get('/me',              [AuthApiController::class, 'me']);
            Route::put('/update-profile',  [AuthApiController::class, 'updateProfile']);
            Route::post('/upload-avatar',  [AuthApiController::class, 'uploadAvatar']);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | CUSTOMER — Public catalogue (no auth required)
    |--------------------------------------------------------------------------
    */
    Route::get('/vendors',        [CustomerApiController::class, 'vendors']);
    Route::get('/vendors/{id}',   [CustomerApiController::class, 'showVendor']);
    Route::get('/categories',     [CustomerApiController::class, 'categories']);
    Route::get('/products',       [CustomerApiController::class, 'products']);
    Route::get('/products/{id}',  [CustomerApiController::class, 'showProduct']);
    Route::get('/vendors/{id}/reviews',  [ReviewController::class, 'index']);
    Route::middleware('auth:sanctum')->post('/vendors/{id}/reviews', [ReviewController::class, 'store']);
    Route::get('/customers/{id}/reviews', [ReviewController::class, 'indexCustomerReviews']);

    /*
    |--------------------------------------------------------------------------
    | CUSTOMER — Authenticated
    |--------------------------------------------------------------------------
    */
    Route::middleware('auth:sanctum')->group(function () {

        // Orders
        Route::post('/orders',              [OrderController::class, 'store']);
        Route::get('/orders',               [OrderController::class, 'all']);
        Route::get('/orders/{order}',       [OrderController::class, 'show']);
        Route::put('/orders/{order}/cancel',[OrderController::class, 'cancel']);

        // Cart
        Route::apiResource('cart', \App\Http\Controllers\CartController::class);

        // Payments
        Route::post('/payments/initiate',   [PaymentApiController::class, 'initiate']);
        Route::post('/payments/verify',     [PaymentApiController::class, 'verify']);
        Route::get('/payments/history',     [PaymentApiController::class, 'history']);

        // Notifications
        Route::post('/notifications/token', function (Request $request) {
            $request->validate(['token' => 'required|string']);
            $request->user()->update(['fcm_token' => $request->token]);
            return response()->json(['status' => true, 'message' => 'FCM token saved.', 'data' => []]);
        });
        Route::get('/notifications',             [NotificationController::class, 'index']);
        Route::put('/notifications/{id}/read',   [NotificationController::class, 'markAsRead']);
    });

    /*
    |--------------------------------------------------------------------------
    | VENDOR — Authenticated + vendor role
    |--------------------------------------------------------------------------
    | Vendors can only view ingredients linked to their categories.
    | Management is handled by Admin.
    |--------------------------------------------------------------------------
    */
    Route::middleware(['auth:sanctum', 'vendor'])->prefix('vendor')->group(function () {
        // Profile
        Route::get('/profile',         [VendorApiController::class, 'profile']);
        Route::put('/profile',         [VendorApiController::class, 'updateProfile']);
        Route::post('/upload-logo',    [VendorApiController::class, 'uploadLogo']);
        Route::post('/upload-banner',  [VendorApiController::class, 'uploadBanner']);

        // Products (ingredients in vendor context — READ ONLY)
        Route::get('/products',            [VendorApiController::class, 'products']);
        Route::get('/products/{id}',       [VendorApiController::class, 'showProduct']);

        // Orders
        Route::get('/orders',              [VendorApiController::class, 'orders']);
        Route::get('/orders/{id}',         [VendorApiController::class, 'showOrder']);
        Route::put('/orders/{id}/status',  [VendorApiController::class, 'updateOrderStatus']);

        // Earnings & Payouts
        Route::get('/earnings',            [VendorApiController::class, 'earnings']);
        Route::get('/payouts',             [VendorApiController::class, 'payouts']);
        Route::post('/payouts/request',    [VendorApiController::class, 'requestPayout']);
        Route::post('/customers/{id}/reviews', [ReviewController::class, 'storeCustomerReview']);
    });

    /*
    |--------------------------------------------------------------------------
    | ADMIN — Authenticated + admin role
    |--------------------------------------------------------------------------
    */
    Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
        // Users
        Route::get('/users',                    [AdminApiController::class, 'users']);
        Route::put('/users/{id}/suspend',       [AdminApiController::class, 'suspendUser']);

        // Vendors
        Route::get('/vendors',                  [AdminApiController::class, 'vendors']);
        Route::put('/vendors/{id}/approve',     [AdminApiController::class, 'approveVendor']);
        Route::put('/vendors/{id}/reject',      [AdminApiController::class, 'rejectVendor']);

        // Ingredients Management (Products)
        Route::get('/ingredients',               [AdminApiController::class, 'ingredients']);
        Route::post('/ingredients',              [AdminApiController::class, 'storeIngredient']);
        Route::put('/ingredients/{id}',          [AdminApiController::class, 'updateIngredient']);
        Route::delete('/ingredients/{id}',       [AdminApiController::class, 'destroyIngredient']);
        Route::post('/ingredients/{id}/upload-image', [AdminApiController::class, 'uploadIngredientImage']);

        // Orders & Payments
        Route::get('/orders',                   [AdminApiController::class, 'orders']);
        Route::get('/payments',                 [AdminApiController::class, 'payments']);

        // Categories
        Route::get('/categories',               [AdminApiController::class, 'categories']);
        Route::post('/categories',              [AdminApiController::class, 'storeCategory']);
        Route::put('/categories/{id}',          [AdminApiController::class, 'updateCategory']);
        Route::delete('/categories/{id}',       [AdminApiController::class, 'destroyCategory']);

        // Notifications & Dashboard
        Route::post('/notifications/send',      [AdminApiController::class, 'sendNotification']);
        Route::get('/dashboard/stats',          [AdminApiController::class, 'dashboardStats']);
    });

}); // end /api
