<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AdminAttendanceService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AdminStaffController extends Controller
{
    public function __construct(
        private AdminAttendanceService $adminAttendanceService
    ) {}

    /**
     * スタッフ一覧を表示
     *
     * @return View スタッフ一覧ページ
     */
    public function index(): View
    {
        $users = User::query()
            ->where('admin_status', false)
            ->get();

        return view('admin.staff-list', compact('users'));
    }

    /**
     * スタッフ別勤怠一覧ページを表示
     *
     * @param  User  $user  表示対象のユーザー
     * @return View スタッフ詳細ページ
     */
    public function show(Request $request, User $user): View
    {
        $data = $this->adminAttendanceService->getUserMonthlyAttendance($user, $request->input('date'));

        return view('admin.staff-attendance-list', $data);
    }
}
