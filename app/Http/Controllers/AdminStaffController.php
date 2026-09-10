<?php

namespace App\Http\Controllers;

use App\Actions\ExportStaffAttendanceAction;
use App\Http\Requests\ExportStaffAttendanceRequest;
use App\Models\User;
use App\Services\AdminAttendanceService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminStaffController extends Controller
{
    public function __construct(
        private AdminAttendanceService $adminAttendanceService,
        private ExportStaffAttendanceAction $exportStaffAttendanceAction,
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

    /**
     * スタッフ別勤怠一覧をCSV出力
     *
     * @param  ExportStaffAttendanceRequest  $request  バリデーション済みのリクエスト
     * @param  User  $user  対象ユーザー
     * @param  ExportStaffAttendanceAction  $action  CSV出力アクション
     * @return StreamedResponse CSVファイル
     */
    public function export(ExportStaffAttendanceRequest $request, User $user, ExportStaffAttendanceAction $action): StreamedResponse
    {
        return $action->execute(
            $user,
            $request->validated('year_month')
        );
    }
}
