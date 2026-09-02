<?php

namespace App\Actions;

use App\Enums\ApprovalStatus;
use App\Models\AttendanceRecord;
use App\Models\CorrectionRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CorrectionRequestAction
{
    /**
     * 勤怠修正申請を作成する。
     *
     * @param  User  $user  修正申請を行うユーザー
     * @param  AttendanceRecord  $attendanceRecord  修正対象の勤怠記録
     * @param  array  $data  バリデーション済みの入力データ
     * @return CorrectionRequest 作成した修正申請
     */
    public function execute(User $user, AttendanceRecord $attendanceRecord, array $data): CorrectionRequest
    {
        $hasPendingRequest = $attendanceRecord
            ->correctionRequests()
            ->where(
                'approval_status',
                ApprovalStatus::Pending->value
            )
            ->exists();

        if ($hasPendingRequest) {
            throw new RuntimeException(
                'すでに承認待ちの修正申請があります。'
            );
        }

        return DB::transaction(function () use (
            $user,
            $attendanceRecord,
            $data
        ) {
            $date = $attendanceRecord
                ->clock_in_at
                ->format('Y-m-d');

            $correctionRequest = CorrectionRequest::create([
                'attendance_record_id' => $attendanceRecord->id,
                'user_id' => $user->id,

                'requested_clock_in_at' => $this->createDateTime(
                    $date,
                    $data['new_clock_in']
                ),

                'requested_clock_out_at' => $this->createDateTime(
                    $date,
                    $data['new_clock_out']
                ),

                'comment' => $data['comment'],

                'approval_status' => ApprovalStatus::Pending,
            ]);

            $this->createCorrectionBreaks(
                $correctionRequest,
                $date,
                $data
            );

            return $correctionRequest;
        });
    }

    /**
     * 日付と時刻から日時を作成する。
     */
    private function createDateTime(string $date, string $time): string
    {
        return "{$date} {$time}:00";
    }

    /**
     * 修正申請の休憩時間を作成する。
     */
    private function createCorrectionBreaks(CorrectionRequest $correctionRequest, string $date, array $data): void
    {
        foreach ($data['new_break_in'] ?? [] as $index => $breakIn) {
            $breakOut = $data['new_break_out'][$index] ?? null;

            if (blank($breakIn) && blank($breakOut)) {
                continue;
            }

            $correctionRequest->breakTimes()->create([
                'break_start_at' => $this->createDateTime(
                    $date,
                    $breakIn
                ),
                'break_end_at' => $this->createDateTime(
                    $date,
                    $breakOut
                ),
            ]);
        }
    }
}
