<?php

namespace App\Http\Controllers\User;

use App\Actions\AttendanceAction;
use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Services\AdminAttendanceService;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    public function __construct(
        private AttendanceAction $attendanceAction,
        private AttendanceService $attendanceService,
        private AdminAttendanceService $adminAttendanceService,
    ) {}

    /**
     * 勤怠登録画面を表示
     */
    public function index(): View
    {
        $user = Auth::user();

        $now = Carbon::now();

        $formattedDate = $now->format('Y年n月j日');

        $formattedTime = $now->format('H:i');

        return view('user.attendance-register', compact('user', 'formattedDate', 'formattedTime'));
    }

    /**
     * 勤怠操作を処理
     */
    public function store(Request $request): RedirectResponse
    {
        $action = $request->input('action');

        $user = $request->user();

        match ($action) {
            'clock_in' => $this->attendanceAction->clockIn($user),
            'clock_out' => $this->attendanceAction->clockOut($user),
            'break_in' => $this->attendanceAction->breakIn($user),
            'break_out' => $this->attendanceAction->breakOut($user),
            default => abort(400),
        };

        return redirect()->route('attendance.index');
    }

    /**
     * 勤怠一覧を表示
     */
    public function attendanceList(Request $request): View
    {
        $data = $this->attendanceService->getMonthlyAttendance(
            $request->user(),
            $request->date
        );

        return view('user.user-attendance-list', $data);
    }

    /**
     * 勤怠詳細を表示する。
     *
     * 一般ユーザーの場合は一般ユーザー用画面を表示し、
     * 管理者の場合は管理者用画面を表示する。
     *
     * @return View 勤怠詳細画面
     */
    public function show(Request $request, AttendanceRecord $attendanceRecord): View
    {
        $user = $request->user();

        $this->authorize('view', $attendanceRecord);

        if ($user->admin_status) {
            return view('admin.admin-detail', $this->adminAttendanceService->getAttendanceDetail($attendanceRecord));
        }

        return view('user.user-detail', $this->attendanceService->getAttendanceDetail($user, $attendanceRecord));
    }
}
