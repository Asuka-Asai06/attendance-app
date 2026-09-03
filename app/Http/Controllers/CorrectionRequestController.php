<?php

namespace App\Http\Controllers;

use App\Actions\CorrectionRequestAction;
use App\Actions\UpdateAttendanceAction;
use App\Http\Requests\UpdateAttendanceRequest;
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
        private UpdateAttendanceAction $updateAttendanceAction
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
                ->getApplicationDetail($correctionRequest);

            // 管理者の場合
            return view('admin.admin-application-detail', ['application' => $application, 'user' => $correctionRequest->user]);
        }

        // 一般ユーザーの場合
        $attendanceRecord = $correctionRequest->attendanceRecord;

        return view('user.user-detail', $this->attendanceService->getAttendanceDetail($user, $attendanceRecord));
    }

    /**
     * 勤怠を修正する。
     *
     * 一般ユーザーの場合は修正申請を作成する。
     * 管理者ユーザーの場合は修正申請を作成したうえで勤怠に反映する。
     *
     * @param  UpdateAttendanceRequest  $request  バリデーション済みの勤怠修正データ
     * @param  AttendanceRecord  $attendanceRecord  修正対象の勤怠記録
     */
    public function update(UpdateAttendanceRequest $request, AttendanceRecord $attendanceRecord): RedirectResponse
    {
        $user = $request->user();

        $this->authorize('update', $attendanceRecord);

        // 管理者ユーザーの場合
        if ($user->admin_status) {
            $this->updateAttendanceAction->execute(
                $user,
                $attendanceRecord,
                $request->validated()
            );
            // 一般ユーザーの場合
        } else {
            $this->correctionRequestAction->execute(
                $user,
                $attendanceRecord,
                $request->validated()
            );
        }

        return redirect()->route('attendance.show', $attendanceRecord);
    }
}
