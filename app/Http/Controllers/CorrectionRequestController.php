<?php

namespace App\Http\Controllers;

use App\Actions\CorrectionRequestAction;
use App\Http\Requests\StoreCorrectionRequestRequest;
use App\Models\AttendanceRecord;
use App\Models\CorrectionRequest;
use App\Services\AttendanceService;
use App\Services\CorrectionRequestService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CorrectionRequestController extends Controller
{
    public function __construct(
        private AttendanceService $attendanceService,
        private CorrectionRequestAction $correctionRequestAction,
        private CorrectionRequestService $correctionRequestService,
    ) {}

    /**
     * 修正申請一覧を表示
     */
    public function index(Request $request, CorrectionRequestService $correctionRequestService): View
    {
        $applications = $correctionRequestService->getApplications(
            $request->user()
        );

        return view('admin.admin-application-list', compact('applications'));
    }

    /**
     * 修正申請の詳細を表示
     *
     * 管理者の場合は管理者用の申請詳細画面を表示し、
     * 一般ユーザーの場合は対象勤怠の詳細画面を表示する
     *
     * @param  Request  $request  ログインユーザーを取得
     * @param  CorrectionRequest  $correctionRequest  修正申請
     * @return View 修正申請の詳細画面
     */
    public function show(Request $request, CorrectionRequest $correctionRequest): View
    {
        $user = $request->user();

        $this->authorize('view', $correctionRequest);

        if ($user->admin_status) {
            $application = $this->correctionRequestService
                ->getAdminApplicationDetail($correctionRequest);

            // 管理者の場合
            return view('admin.application-detail', ['application' => $application, 'user' => $correctionRequest->user]);
        }

        // 一般ユーザーの場合
        $attendanceRecord = $correctionRequest->attendanceRecord;

        return view('user.user-detail', $this->attendanceService->getAttendanceDetail($user, $attendanceRecord));
    }

    /**
     * 勤怠の修正申請を作成
     *
     * @param  StoreCorrectionRequestRequest  $request  バリデーション済みの修正申請
     * @param  AttendanceRecord  $attendanceRecord  修正対象の勤怠情報
     * @return RedirectResponse|Redirector
     */
    public function store(StoreCorrectionRequestRequest $request, AttendanceRecord $attendanceRecord): RedirectResponse
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
