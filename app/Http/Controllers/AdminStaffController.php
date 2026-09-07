<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AdminAttendanceService;
use Illuminate\Contracts\View\Factory;
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
     * @return View|Factory
     */
    public function index(): View
    {
        $users = User::all();

        return view('admin.staff-list', compact('users'));
    }

    public function show(Request $request, User $user): View
    {
        $data = $this->adminAttendanceService->getUserMonthlyAttendance($user, $request->input('date'));

        return view('admin.staff-attendance-list', $data);
    }
}
