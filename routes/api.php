<?php

use Illuminate\Http\Request;
use App\Http\Middleware\ForceJsonResponse;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\CategoryController;

// Pickup/Deliveries
Route::get('/orders/scheduled', [OrderController::class, 'scheduled']);
Route::middleware([ForceJsonResponse::class])->group(function () {
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::post('/products', [ProductController::class, 'store']);
Route::post('/orders', [OrderController::class, 'store']);   // place new order
Route::get('/orders/{id}', [OrderController::class, 'show']); // view order details
Route::get('/orders/{id}/status', [OrderController::class, 'status']); // track status
Route::patch('/orders/{id}/status', [OrderController::class, 'updateStatus']);

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\AdminController::class, 'dashboard']);
    // more admin endpoints...
});



Route::middleware('auth:sanctum')->group(function () {
        // Get authenticated user info
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', [AuthController::class, 'logout']);

    // Profile management
     Route::put('/profile/update', [ProfileController::class, 'updateProfile'])
        ->withoutMiddleware([\App\Http\Middleware\ForceJsonResponse::class]); // Important for file uploads!
    Route::put('/profile/change-password', [ProfileController::class, 'changePassword']);
});

Route::get('/ping', fn() => response()->json(['message' => 'API is working!']));
//Online payment processing
Route::post('/payments/{order_id}', [PaymentController::class, 'processPayment']);

// 🔔 Notifications
Route::get('/notifications/{user_id}', [NotificationController::class, 'index']);
Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);

// 📦 Order Tracking
Route::get('/orders/user/{user_id}', [OrderController::class, 'userOrders']);



});