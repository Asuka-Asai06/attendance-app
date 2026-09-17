<?php

namespace App\Actions\Api\V1;

use App\Models\AttendanceRecord;
use Illuminate\Support\Facades\DB;

class StoreAttendanceAction
{
    /**
     * 勤怠を登録する
     *
     * @param  int  $userId  勤怠を登録するユーザーID
     * @param  array<string, mixed>  $data  登録データ
     * @return AttendanceRecord 登録した勤怠
     */
    public function execute(int $userId, array $data): AttendanceRecord
    {
        return DB::transaction(function () use (
            $userId,
            $data
        ): AttendanceRecord {
            $attendanceRecord = AttendanceRecord::create([
                'user_id' => $userId,
                'date' => $data['date'],
                'clock_in_at' => $this->createDateTime(
                    $data['date'],
                    $data['clock_in']
                ),
                'clock_out_at' => isset($data['clock_out'])
                    ? $this->createDateTime(
                        $data['date'],
                        $data['clock_out']
                    )
                    : null,
                'comment' => $data['comment'] ?? null,
            ]);

            foreach ($data['breaks'] ?? [] as $break) {
                $attendanceRecord->breakTimes()->create([
                    'break_start_at' => $this->createDateTime(
                        $data['date'],
                        $break['break_in']
                    ),
                    'break_end_at' => $this->createDateTime(
                        $data['date'],
                        $break['break_out']
                    ),
                ]);
            }

            return $attendanceRecord;
        });
    }

    /**
     * 日付と時刻から日時を生成する。
     */
    private function createDateTime(string $date, string $time): string
    {
        return "{$date} {$time}";
    }
}
