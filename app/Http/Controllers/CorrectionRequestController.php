<?php

namespace App\Http\Controllers;

use App\Actions\ApproveCorrectionRequestAction;
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
        private UpdateAttendanceAction $updateAttendanceAction,
        private CorrectionRequestAction $correctionRequestAction,
        private CorrectionRequestService $correctionRequestService,
        private ApproveCorrectionRequestAction $approveCorrectionRequestAction
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
     * 管理者は勤怠を直接修正し、
     * 一般ユーザーは修正申請を作成する。
     *
     * @param  UpdateAttendanceRequest  $request  バリデーション済みの勤怠修正データ
     * @param  AttendanceRecord  $attendanceRecord  修正対象の勤怠記録
     * @return RedirectResponse 処理後の画面へリダイレクト
     */
    public function update(UpdateAttendanceRequest $request, AttendanceRecord $attendanceRecord): RedirectResponse
    {
        $this->authorize('update', $attendanceRecord);

        $user = $request->user();

        if ($user->admin_status) {
            $this->updateAttendanceAction->execute(
                $attendanceRecord,
                $request->validated()
            );

            return redirect()->route('admin.attendance.list');
        }

        $this->correctionRequestAction->execute(
            $user,
            $attendanceRecord,
            $request->validated()
        );

        return redirect()->route('correction.request.list');
    }

    /**
     * 修正申請を承認する。
     *
     * @param  Request  $request  ログイン中の管理者
     * @param  CorrectionRequest  $correctionRequest  承認対象の修正申請
     * @return RedirectResponse 承認後の申請一覧画面
     */
    public function approve(Request $request, CorrectionRequest $correctionRequest): RedirectResponse
    {
        $this->approveCorrectionRequestAction->execute(
            $request->user(),
            $correctionRequest
        );

        return redirect()->route('correction.request.list');
    }
}
