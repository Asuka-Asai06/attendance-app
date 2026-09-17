<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Api\V1\StoreAttendanceAction;
use App\Actions\Api\V1\UpdateAttendanceAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexAttendanceRecordRequest;
use App\Http\Requests\Api\V1\StoreAttendanceRecordRequest;
use App\Http\Requests\Api\V1\UpdateAttendanceRecordRequest;
use App\Http\Resources\AttendanceRecordResource;
use App\Models\AttendanceRecord;
use App\Services\Api\V1\AttendanceRecordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class AttendanceRecordController extends Controller
{
    public function __construct(
        private AttendanceRecordService $attendanceRecordService,
        private UpdateAttendanceAction $updateAttendanceAction,
        private StoreAttendanceAction $storeAttendanceAction
    ) {}

    /**
     * 勤怠一覧を取得する
     *
     * @param  IndexAttendanceRecordRequest  $request  検索条件を含むリクエスト
     */
    public function index(IndexAttendanceRecordRequest $request): AnonymousResourceCollection
    {
        $attendanceRecords = $this->attendanceRecordService
            ->getAttendanceRecords($request->validated());

        return AttendanceRecordResource::collection($attendanceRecords);
    }

    /**
     * 勤怠を登録する。
     *
     * @return JsonResponse 登録した勤怠のjsonレスポンス
     */
    public function store(StoreAttendanceRecordRequest $request): JsonResponse
    {
        $attendanceRecord = $this->storeAttendanceAction->execute(
            $request->user()->id,
            $request->validated()
        );

        $attendanceRecord->load([
            'user',
            'breakTimes',
            'correctionRequests.breakTimes',
        ]);

        return (new AttendanceRecordResource($attendanceRecord))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * 指定された勤怠の詳細を取得する
     *
     * @param  AttendanceRecord  $attendanceRecord  詳細を取得する勤怠記録
     * @return AttendanceRecordResource 勤怠詳細のリソース
     */
    public function show(AttendanceRecord $attendanceRecord): AttendanceRecordResource
    {
        $attendanceRecord->load([
            'user',
            'breakTimes',
            'correctionRequests.breakTimes',
        ]);

        return new AttendanceRecordResource($attendanceRecord);
    }

    /**
     * 指定した勤怠を更新する。
     *
     * @param  AttendanceRecord  $attendanceRecord  更新対象の勤怠
     * @return AttendanceRecordResource 更新した勤怠のリソース
     */
    public function update(UpdateAttendanceRecordRequest $request, AttendanceRecord $attendanceRecord): AttendanceRecordResource
    {
        $this->authorize('update', $attendanceRecord);

        $attendanceRecord = $this->updateAttendanceAction->execute(
            $attendanceRecord,
            $request->validated()
        );

        return new AttendanceRecordResource($attendanceRecord);
    }

    /**
     * 指定した勤怠を削除し、204 No Content を返す。
     *
     * @param  AttendanceRecord  $attendanceRecord  削除対象の勤怠
     */
    public function destroy(AttendanceRecord $attendanceRecord): Response
    {
        $this->authorize('delete', $attendanceRecord);

        $attendanceRecord->delete();

        return response()->noContent();
    }
}
