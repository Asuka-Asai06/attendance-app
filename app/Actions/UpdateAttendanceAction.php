<?php

namespace App\Actions;

use App\Models\AttendanceRecord;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class UpdateAttendanceAction
{
    /**
     * 管理者による勤怠修正を行う
     *
     * @param  AttendanceRecord  $attendanceRecord  修正対象の勤怠記録
     * @param  array<string, mixed>  $data  バリデーション済みの入力データ
     * @return AttendanceRecord 更新した勤怠記録
     *
     * @throws RuntimeException 承認待ちの修正申請が存在する場合
     */
    public function execute(AttendanceRecord $attendanceRecord, array $data): AttendanceRecord
    {
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
            $attendanceRecord,
            $data
        ): AttendanceRecord {
            $date = $attendanceRecord->date;

            $attendanceRecord->update([
                'date' => $date,
                'clock_in_at' => $this->createDateTime(
                    $date,
                    $data['new_clock_in']
                ),
                'clock_out_at' => $this->createDateTime(
                    $date,
                    $data['new_clock_out']
                ),
                'comment' => $data['comment'],
            ]);

            $attendanceRecord->breakTimes()->delete();

            $this->createAttendanceBreaks(
                $attendanceRecord,
                $date,
                $data
            );

            return $attendanceRecord;
        });
    }

    /**
     * 日付と時刻から日時を作成する。
     *
     * @param  string  $date  勤怠日
     * @param  string  $time  時刻
     * @return string 日時
     */
    private function createDateTime(string $date, string $time): string
    {
        return "{$date} {$time}:00";
    }

    /**
     * 勤怠の休憩時間を作成する。
     *
     * @param  AttendanceRecord  $attendanceRecord  勤怠記録
     * @param  string  $date  勤怠日
     * @param  array<string, mixed>  $data  バリデーション済みの入力データ
     */
    private function createAttendanceBreaks(AttendanceRecord $attendanceRecord, string $date, array $data): void
    {
        foreach ($data['new_break_in'] ?? [] as $index => $breakIn) {
            $breakOut = $data['new_break_out'][$index] ?? null;

            if (blank($breakIn) && blank($breakOut)) {
                continue;
            }

            $attendanceRecord->breakTimes()->create([
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
