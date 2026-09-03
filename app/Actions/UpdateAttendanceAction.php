<?php

namespace App\Actions;

use App\Models\AttendanceRecord;
use App\Models\CorrectionRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class UpdateAttendanceAction
{
    /**
     * 管理者による勤怠修正を行う。
     *
     * 修正内容を修正申請として保存し、
     * その申請内容を勤怠へ反映したうえで承認済みにする。
     *
     * @param  User  $admin  修正を行う管理者
     * @param  AttendanceRecord  $attendanceRecord  修正対象の勤怠記録
     * @param  array  $data  バリデーション済みの入力データ
     * @return CorrectionRequest 作成した修正申請
     *
     * @throws RuntimeException 承認待ちの修正申請が存在する場合
     */
    public function execute(
        User $admin,
        AttendanceRecord $attendanceRecord,
        array $data
    ): CorrectionRequest {
        // 承認待ちの修正申請がある場合は更新できない
        $hasPendingRequest = $attendanceRecord
            ->correctionRequests()
            ->where('approval_status', '承認待ち')
            ->exists();

        if ($hasPendingRequest) {
            throw new RuntimeException(
                '承認待ちのため修正はできません。'
            );
        }

        return DB::transaction(function () use (
            $admin,
            $attendanceRecord,
            $data
        ): CorrectionRequest {
            $date = $attendanceRecord
                ->clock_in_at
                ->format('Y-m-d');

            // 1. 修正申請を作成する
            $correctionRequest = CorrectionRequest::create([
                'attendance_record_id' => $attendanceRecord->id,
                'user_id' => $attendanceRecord->user_id,
                'requested_clock_in_at' => $this->createDateTime(
                    $date,
                    $data['new_clock_in']
                ),
                'requested_clock_out_at' => $this->createDateTime(
                    $date,
                    $data['new_clock_out']
                ),
                'comment' => $data['comment'],
                'approval_status' => '承認済み',
                'approved_by' => $admin->id,
                'approved_at' => now(),
            ]);

            // 2. 修正申請に紐づく休憩を作成する
            $this->createCorrectionBreaks(
                $correctionRequest,
                $date,
                $data
            );

            // 3. 修正申請の内容を勤怠へ反映する
            $attendanceRecord->update([
                'clock_in_at' => $correctionRequest->requested_clock_in_at,
                'clock_out_at' => $correctionRequest->requested_clock_out_at,
            ]);

            // 4. 既存の休憩を削除する
            $attendanceRecord->breakTimes()->delete();

            // 5. 修正申請の休憩を勤怠へ反映する
            $this->createAttendanceBreaks(
                $attendanceRecord,
                $correctionRequest
            );

            return $correctionRequest;
        });
    }

    /**
     * 日付と時刻から日時を作成する。
     *
     * @param  string  $date  勤怠日
     * @param  string  $time  時刻
     * @return string 日時
     */
    private function createDateTime(
        string $date,
        string $time
    ): string {
        return "{$date} {$time}:00";
    }

    /**
     * 修正申請に含まれる休憩を作成する。
     *
     * @param  CorrectionRequest  $correctionRequest  修正申請
     * @param  string  $date  勤怠日
     * @param  array  $data  バリデーション済みの入力データ
     */
    private function createCorrectionBreaks(
        CorrectionRequest $correctionRequest,
        string $date,
        array $data
    ): void {
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

    /**
     * 修正申請の休憩を勤怠に反映する。
     *
     * @param  AttendanceRecord  $attendanceRecord  勤怠記録
     * @param  CorrectionRequest  $correctionRequest  修正申請
     */
    private function createAttendanceBreaks(
        AttendanceRecord $attendanceRecord,
        CorrectionRequest $correctionRequest
    ): void {
        foreach ($correctionRequest->breakTimes as $breakTime) {
            $attendanceRecord->breakTimes()->create([
                'break_start_at' => $breakTime->break_start_at,
                'break_end_at' => $breakTime->break_end_at,
            ]);
        }
    }
}
