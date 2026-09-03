<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminAttendanceService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminAttendanceController extends Controller
{
    public function __construct(
        private AdminAttendanceService $adminAttendanceService
    ) {}

    /**
     * 指定日の全ユーザーの勤怠一覧を表示する。
     *
     * @return View 管理者用勤怠一覧画面
     */
    public function index(Request $request): View
    {
        $data = $this->adminAttendanceService->getDailyAttendance(
            $request->input('date')
        );

        return view('admin.admin-attendance-list', $data);
    }
}
