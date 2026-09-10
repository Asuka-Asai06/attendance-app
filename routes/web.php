<?php

use App\Http\Controllers\AdminAttendanceController;
use App\Http\Controllers\AdminLogoutController;
use App\Http\Controllers\AdminStaffController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CorrectionRequestController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('attendance.index');
});

// 管理者認証
Route::get('/admin/login', [AuthController::class, 'create'])->name('admin.login');
Route::post('/admin/login', [AuthController::class, 'login'])->name('admin.login.store');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');
    Route::get('/attendance/list', [AttendanceController::class, 'attendanceList'])->name('attendance.list');
    Route::get('/stamp_correction_request/list', [CorrectionRequestController::class, 'index'])->name('correction.request.list');
    Route::get('/stamp_correction_request/approve/{correctionRequest}', [CorrectionRequestController::class, 'show'])->name('correction.request.show');
    Route::post('/stamp_correction_request/approve/{correctionRequest}', [CorrectionRequestController::class, 'approve'])->name('correction.request.approve');
    Route::get('/attendance/{attendanceRecord}', [AttendanceController::class, 'show'])->name('attendance.show');
    Route::post('/attendance/{attendanceRecord}', [CorrectionRequestController::class, 'update'])->name('attendance.correction.store');
});

// 管理者画面
Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::post('/logout', [AdminLogoutController::class, 'logout']);
        Route::get('/attendance/list', [AdminAttendanceController::class, 'index'])->name('attendance.list');
        Route::get('/staff/list', [AdminStaffController::class, 'index'])->name('staff.index');
        Route::get('/attendance/staff/{user}', [AdminStaffController::class, 'show'])->name('staff.list');
    });
