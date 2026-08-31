<?php

use App\Http\Controllers\Admin\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// 管理者認証
Route::get('/admin/login', [AuthController::class, 'create'])->name('admin.login');
Route::post('/admin/login', [AuthController::class, 'login'])->name('admin.login.store');

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
