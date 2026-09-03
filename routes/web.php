<?php

use App\Http\Controllers\Admin\AdminLogoutController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\CorrectionRequestController;
use App\Http\Controllers\User\AttendanceController;
use Illuminate\Support\Facades\Route;

// 管理者認証
Route::get('/admin/login', [AuthController::class, 'create'])->name('admin.login');
Route::post('/admin/login', [AuthController::class, 'login'])->name('admin.login.store');

// 一般ユーザー認証
Route::middleware(['auth', 'user'])->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');
    Route::get('/attendance/list', [AttendanceController::class, 'attendanceList'])->name('attendance.list');
    Route::get('/attendance/{attendanceRecord}', [AttendanceController::class, 'show'])->name('attendance.show');
    Route::post('/attendance/{attendanceRecord}', [CorrectionRequestController::class, 'store'])->name('attendance.correction.store');
});

// 管理者画面
Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::post('/logout', [AdminLogoutController::class, 'logout']);
        // Route::get('/admin/attendance/list', [AttendanceController::class, 'index'])->name('attendance');
        // Route::get('/admin/attendance/{id}', [AttendanceController::class, 'index'])->name('attendance');
        // Route::get('/admin/staff/list', [AttendanceController::class, 'index'])->name('attendance');
        // Route::get('/admin/attendance/staff/{id}', [AttendanceController::class, 'index'])->name('attendance');
    });

Route::middleware(['auth'])->group(function () {
    Route::get('/stamp_correction_request/list', [CorrectionRequestController::class, 'index'])->name('correction.request.list');
    Route::get('/stamp_correction_request/approve/{correctionRequest}', [CorrectionRequestController::class, 'show'])->name('correction.request.show');
});
/**
*Route::middleware(['auth', 'admin'])
 *   ->prefix('admin')
 *   ->name('admin.')
  *  ->group(function () {
*
  *      Route::get('/', function () {
  *          return view('admin.index');
  *      })->name('index');

  *      Route::get('/attendance/list', [
  *          AttendanceController::class,
  *          'index',
   *     ])->name('attendance.list');
   * });
 */
