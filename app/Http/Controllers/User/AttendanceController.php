<?php

namespace App\Http\Controllers\User;

use App\Actions\AttendanceAction;
use App\Actions\CorrectionRequestAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCorrectionRequestRequest;
use App\Models\AttendanceRecord;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    public function __construct(
        private AttendanceAction $attendanceAction,
        private CorrectionRequestAction $correctionRequestAction,
        private AttendanceService $attendanceService,
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
     * 勤怠詳細を表示
     */
    public function show(Request $request, AttendanceRecord $attendanceRecord): View
    {
        $this->authorize('view', $attendanceRecord);

        $data = $this->attendanceService->getAttendanceDetail(
            $request->user(),
            $attendanceRecord
        );

        return view('user.user-detail', $data);
    }

    /**
     * 勤怠の修正申請
     *
     * @param  StoreCorrectionRequestRequest  $request  バリデーション済みの修正申請
     * @param  AttendanceRecord  $attendanceRecord  修正対象の勤怠情報
     * @return RedirectResponse|Redirector
     */
    public function requestCorrection(StoreCorrectionRequestRequest $request, AttendanceRecord $attendanceRecord): RedirectResponse
    {
        $this->authorize('update', $attendanceRecord);

        $this->correctionRequestAction->execute(
            $request->user(),
            $attendanceRecord,
            $request->validated()
        );

        return redirect('/attendance/'.$attendanceRecord->id);
    }
}
