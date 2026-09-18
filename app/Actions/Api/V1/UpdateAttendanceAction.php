<?php

namespace App\Actions\Api\V1;

use App\Models\AttendanceRecord;
use Illuminate\Support\Facades\DB;

class UpdateAttendanceAction
{
    /**
     * 勤怠を更新する。
     *
     * @param  AttendanceRecord  $attendanceRecord  更新対象の勤怠
     * @param  array<string, mixed>  $data  更新データ
     * @return AttendanceRecord 更新後の勤怠
     */
    public function execute(AttendanceRecord $attendanceRecord, array $data): AttendanceRecord
    {
        return DB::transaction(function () use (
            $attendanceRecord,
            $data
        ): AttendanceRecord {
            $date = $data['date'] ?? $attendanceRecord->date;

            $attendanceRecord->update([
                'date' => $date,
                'clock_in_at' => $this->createDateTime(
                    $date,
                    $data['clock_in']
                ),
                'clock_out_at' => isset($data['clock_out'])
                    ? $this->createDateTime(
                        $date,
                        $data['clock_out']
                    )
                    : null,
                'comment' => $data['comment'] ?? $attendanceRecord->comment,
            ]);

            if (array_key_exists('breaks', $data)) {
                $this->updateBreakTimes(
                    $attendanceRecord,
                    $date,
                    $data['breaks'] ?? []
                );
            }

            return $attendanceRecord;
        });
    }

    /**
     * 休憩時間を追加・更新する。
     *
     * @param  AttendanceRecord  $attendanceRecord  対象の勤怠
     * @param  string  $date  勤怠日
     * @param  array<int, array<string, mixed>>  $breaks  休憩データ
     */
    private function updateBreakTimes(AttendanceRecord $attendanceRecord, string $date, array $breaks): void
    {
        foreach ($breaks as $break) {
            $breakData = [
                'break_start_at' => $this->createDateTime(
                    $date,
                    $break['break_in']
                ),
                'break_end_at' => $this->createDateTime(
                    $date,
                    $break['break_out']
                ),
            ];

            if (isset($break['id'])) {
                $attendanceRecord->breakTimes()
                    ->whereKey($break['id'])
                    ->update($breakData);

                continue;
            }

            $attendanceRecord->breakTimes()->create($breakData);
        }
    }

    /**
     * 日付と時刻から日時を生成する。
     */
    private function createDateTime(string $date, string $time): string
    {
        return "{$date} {$time}";
    }
}
