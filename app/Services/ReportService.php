<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class ReportService
{
    /**
     * 過去6ヶ月分の勤怠レポートを取得する。
     *
     * @param  User  $user  レポート対象のユーザー
     * @return array<string, mixed> レポート表示用データ
     */
    public function getReport(User $user): array
    {
        $startDate = now()
            ->subMonths(5)
            ->startOfMonth();

        $endDate = now()
            ->endOfMonth();

        $attendanceRecords = $user->attendanceRecords()
            ->with('breakTimes')
            ->whereBetween('clock_in_at', [
                $startDate,
                $endDate,
            ])
            ->orderBy('clock_in_at')
            ->get();

        return [
            'summary' => $this->calculateSummary($attendanceRecords),
            'monthlyTrend' => $this->calculateMonthlyTrend(
                $attendanceRecords,
                $startDate
            ),
            'anomalies' => $this->calculateAnomalies(
                $attendanceRecords
            ),
        ];
    }

    /**
     * 基本サマリーを計算する。
     *
     * @param  Collection<int, AttendanceRecord>  $attendanceRecords  勤怠記録
     * @return array<string, int> 基本サマリー
     */
    private function calculateSummary(Collection $attendanceRecords): array
    {
        $totalWorkMinutes = 0;
        $totalOvertimeMinutes = 0;
        $workDayCount = 0;

        foreach ($attendanceRecords as $attendanceRecord) {
            $workMinutes = $this->calculateWorkMinutes(
                $attendanceRecord
            );

            if ($workMinutes === null) {
                continue;
            }

            $totalWorkMinutes += $workMinutes;
            $totalOvertimeMinutes += max(
                0,
                $workMinutes - 8 * 60
            );

            $workDayCount++;
        }

        return [
            'total_work_minutes' => $totalWorkMinutes,
            'total_overtime_minutes' => $totalOvertimeMinutes,
            'avg_work_minutes' => $workDayCount > 0
                ? intdiv($totalWorkMinutes, $workDayCount)
                : 0,
        ];
    }

    /**
     * 月次推移を計算する。
     *
     * 過去6ヶ月を対象に、月ごとの労働時間と残業時間を集計する。
     *
     * @param  Collection<int, AttendanceRecord>  $attendanceRecords  勤怠記録
     * @param  Carbon  $startDate  集計開始日
     * @return array<int, array<string, int|string>> 月次集計
     */
    private function calculateMonthlyTrend(Collection $attendanceRecords, Carbon $startDate): array
    {
        $monthlyTrend = [];

        for ($monthOffset = 0; $monthOffset < 6; $monthOffset++) {
            $month = $startDate
                ->copy()
                ->addMonths($monthOffset);

            $monthRecords = $attendanceRecords->filter(
                function (AttendanceRecord $attendanceRecord) use ($month): bool {
                    return $attendanceRecord->clock_in_at->isSameMonth(
                        $month
                    );
                }
            );

            $workMinutes = 0;
            $overtimeMinutes = 0;

            foreach ($monthRecords as $attendanceRecord) {
                $recordWorkMinutes = $this->calculateWorkMinutes(
                    $attendanceRecord
                );

                if ($recordWorkMinutes === null) {
                    continue;
                }

                $workMinutes += $recordWorkMinutes;
                $overtimeMinutes += max(
                    0,
                    $recordWorkMinutes - 8 * 60
                );
            }

            $monthlyTrend[] = [
                'month' => $month->format('Y/m'),
                'work_minutes' => $workMinutes,
                'overtime_minutes' => $overtimeMinutes,
            ];
        }

        return $monthlyTrend;
    }

    /**
     * 当月の異常件数を計算する。
     *
     * @param  Collection<int, AttendanceRecord>  $attendanceRecords  勤怠記録
     * @return array<string, int> 異常件数
     */
    private function calculateAnomalies(Collection $attendanceRecords): array
    {
        $currentMonth = now();

        $currentMonthRecords = $attendanceRecords->filter(
            function (AttendanceRecord $attendanceRecord) use ($currentMonth): bool {
                return $attendanceRecord->clock_in_at->isSameMonth(
                    $currentMonth
                );
            }
        );

        $lateCount = 0;
        $earlyLeaveCount = 0;
        $longWorkCount = 0;

        foreach ($currentMonthRecords as $attendanceRecord) {
            if (
                $attendanceRecord->clock_in_at->format('H:i')
                > '09:00'
            ) {
                $lateCount++;
            }

            if (
                $attendanceRecord->clock_out_at !== null
                && $attendanceRecord->clock_out_at->format('H:i')
                < '18:00'
            ) {
                $earlyLeaveCount++;
            }

            $workMinutes = $this->calculateWorkMinutes(
                $attendanceRecord
            );

            if (
                $workMinutes !== null
                && $workMinutes > 10 * 60
            ) {
                $longWorkCount++;
            }
        }

        return [
            'late_count' => $lateCount,
            'early_leave_count' => $earlyLeaveCount,
            'long_work_count' => $longWorkCount,
        ];
    }

    /**
     * 実働時間を分単位で計算する。
     *
     * @param  AttendanceRecord  $attendanceRecord  勤怠記録
     * @return int|null 実働時間（分）
     */
    private function calculateWorkMinutes(AttendanceRecord $attendanceRecord): ?int
    {
        if ($attendanceRecord->clock_out_at === null) {
            return null;
        }

        $workMinutes = $attendanceRecord->clock_in_at
            ->diffInMinutes($attendanceRecord->clock_out_at);

        $breakMinutes = $attendanceRecord->breakTimes->sum(
            function ($breakTime): int {
                if ($breakTime->break_end_at === null) {
                    return 0;
                }

                return $breakTime->break_start_at
                    ->diffInMinutes($breakTime->break_end_at);
            }
        );

        return max(
            0,
            $workMinutes - $breakMinutes
        );
    }
}
