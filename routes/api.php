<?php

use App\Http\Controllers\Api\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Api\Admin\OfficeController as AdminOfficeController;
use App\Http\Controllers\Api\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Api\Admin\RequestTypeController as AdminRequestTypeController;
use App\Http\Controllers\Api\Admin\ServiceRequestController as AdminServiceRequestController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EmailVerificationController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OfficeController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\RequestTypeController;
use App\Http\Controllers\Api\ServiceRequestController;
use Illuminate\Support\Facades\Route;

// --- Public (Login page) ---
// Named prefixes here and on the verification routes below keep these two
// groups on separate rate-limit buckets — Laravel's bare "throttle:N,M"
// otherwise keys solely by route domain + IP (no domain is set on any of
// these routes), so without a prefix every group would share one counter
// and a burst on one endpoint (e.g. login) would lock out an unrelated one
// (e.g. verifying an email).
Route::middleware('throttle:5,1,auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword']);
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);
});

// Hit directly from the emailed verification link — no bearer token is
// available there, so this is validated by its signature instead of auth.
Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware(['signed', 'throttle:6,1,verify-email'])
    ->name('verification.verify');

// --- Authenticated (everything behind DashboardLayout) ---
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1,verify-email')
        ->name('verification.send');

    // Dashboard page: stats + recent requests
    Route::get('/dashboard', [DashboardController::class, 'summary']);

    // My Requests page + New Request page
    Route::get('/requests', [ServiceRequestController::class, 'index']);
    Route::post('/requests', [ServiceRequestController::class, 'store'])->middleware('verified');
    Route::get('/requests/{serviceRequest}', [ServiceRequestController::class, 'show']);
    Route::patch('/requests/{serviceRequest}/cancel', [ServiceRequestController::class, 'cancel']);

    // Lookup data for the New Request form's Select inputs
    Route::get('/offices', [OfficeController::class, 'index']);
    Route::get('/request-types', [RequestTypeController::class, 'index']);

    // Notifications page
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);

    // Profile & settings page
    Route::patch('/profile', [ProfileController::class, 'update']);

    // --- Admin area (role-gated: 'admin' and 'staff' can reach the queue, 'admin' only past that) ---
    Route::prefix('admin')->group(function () {
        Route::middleware('role:admin,staff')->group(function () {
            Route::get('/dashboard', [AdminDashboardController::class, 'summary']);
            Route::get('/requests', [AdminServiceRequestController::class, 'index']);
            Route::patch('/requests/{serviceRequest}/status', [AdminServiceRequestController::class, 'updateStatus']);
        });

        Route::middleware('role:admin')->group(function () {
            Route::get('/users', [AdminUserController::class, 'index']);
            Route::patch('/users/{user}/role', [AdminUserController::class, 'updateRole']);

            Route::get('/offices', [AdminOfficeController::class, 'index']);
            Route::post('/offices', [AdminOfficeController::class, 'store']);
            Route::patch('/offices/{office}', [AdminOfficeController::class, 'update']);
            Route::delete('/offices/{office}', [AdminOfficeController::class, 'destroy']);

            Route::get('/request-types', [AdminRequestTypeController::class, 'index']);
            Route::post('/request-types', [AdminRequestTypeController::class, 'store']);
            Route::patch('/request-types/{requestType}', [AdminRequestTypeController::class, 'update']);
            Route::delete('/request-types/{requestType}', [AdminRequestTypeController::class, 'destroy']);

            Route::get('/reports', [AdminReportController::class, 'summary']);
        });
    });
});