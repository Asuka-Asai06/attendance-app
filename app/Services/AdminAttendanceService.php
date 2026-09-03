<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class AdminAttendanceService
{
    /**
     * 指定日の全ユーザーの勤怠情報を取得
     *
     * @param  string|null  $selectedDate  表示する日付
     * @return array<string, mixed> 管理者用勤怠一覧データ
     */
    public function getDailyAttendance(?string $selectedDate): array
    {
        $date = $selectedDate
            ? Carbon::parse($selectedDate)
            : today();

        $users = User::query()
            ->orderBy('id')
            ->get();

        $attendanceRecords = AttendanceRecord::query()
            ->with('breakTimes')
            ->whereDate('clock_in_at', $date)
            ->get()
            ->map(function (AttendanceRecord $attendanceRecord) {
                $totalBreakSeconds = $this->calculateBreakSeconds(
                    $attendanceRecord->breakTimes
                );

                return (object) [
                    'id' => $attendanceRecord->id,
                    'user_id' => $attendanceRecord->user_id,

                    'clock_in' => $attendanceRecord->clock_in_at,
                    'clock_out' => $attendanceRecord->clock_out_at,

                    'total_break_time' => $this->formatDuration(
                        $totalBreakSeconds
                    ),

                    'total_time' => $this->calculateWorkTime(
                        $attendanceRecord,
                        $totalBreakSeconds
                    ),
                ];
            });

        return [
            'users' => $users,
            'attendanceRecords' => $attendanceRecords,
            'date' => $date,
            'previousDay' => $date->copy()
                ->subDay()
                ->format('Y-m-d'),
            'nextDay' => $date->copy()
                ->addDay()
                ->format('Y-m-d'),
        ];
    }

    /**
     * 休憩時間の合計秒数を計算
     *
     * @param  Collection  $breakTimes  休憩記録
     * @return int 休憩時間の合計秒数
     */
    private function calculateBreakSeconds(Collection $breakTimes): int
    {
        return $breakTimes->sum(function ($breakTime): int {
            if ($breakTime->break_end_at === null) {
                return 0;
            }

            return $breakTime->break_start_at->diffInSeconds(
                $breakTime->break_end_at
            );
        });
    }

    /**
     * 実働時間を計算
     *
     * @param  AttendanceRecord  $attendanceRecord  勤怠記録
     * @param  int  $totalBreakSeconds  休憩時間の合計秒数
     * @return Carbon|null 実働時間
     */
    private function calculateWorkTime(AttendanceRecord $attendanceRecord, int $totalBreakSeconds): ?Carbon
    {
        if ($attendanceRecord->clock_out_at === null) {
            return null;
        }

        $workSeconds = $attendanceRecord->clock_in_at
            ->diffInSeconds($attendanceRecord->clock_out_at);

        $actualWorkSeconds = $workSeconds - $totalBreakSeconds;

        return $this->formatDuration($actualWorkSeconds);
    }

    /**
     * 秒数を時間として扱えるCarbonに変換
     *
     * @param  int  $seconds  秒数
     * @return Carbon|null 時間
     */
    private function formatDuration(int $seconds): ?Carbon
    {
        if ($seconds <= 0) {
            return null;
        }

        return Carbon::createFromTime(0, 0, 0)
            ->addSeconds($seconds);
    }
}
