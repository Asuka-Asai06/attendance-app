<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class AttendanceService
{
    /**
     * 指定された月の勤怠一覧を取得
     */
    public function getMonthlyAttendance(User $user, ?string $month): array
    {
        $date = $month
            ? Carbon::createFromFormat('Y-m', $month)->startOfMonth()
            : now()->startOfMonth();

        $attendanceRecords = $user->attendanceRecords()
            ->with('breakTimes')
            ->whereBetween('clock_in_at', [
                $date->copy()->startOfMonth(),
                $date->copy()->endOfMonth(),
            ])
            ->orderBy('clock_in_at')
            ->get();

        $formattedAttendanceRecords = $attendanceRecords
            ->map(function (AttendanceRecord $attendanceRecord): array {
                $totalBreakSeconds = $this->calculateBreakSeconds(
                    $attendanceRecord->breakTimes
                );

                return [
                    'id' => $attendanceRecord->id,
                    'date' => $attendanceRecord->clock_in_at->format('m/d'),
                    'clock_in' => $attendanceRecord->clock_in_at->format('H:i'),
                    'clock_out' => $attendanceRecord->clock_out_at?->format('H:i') ?? '',
                    'total_break_time' => $this->formatDuration(
                        $totalBreakSeconds
                    ),
                    'total_time' => $this->calculateWorkTime(
                        $attendanceRecord,
                        $totalBreakSeconds
                    ),
                ];
            })
            ->all();

        return [
            'date' => $date,
            'previousMonth' => $date->copy()
                ->subMonth()
                ->format('Y-m'),
            'nextMonth' => $date->copy()
                ->addMonth()
                ->format('Y-m'),
            'formattedAttendanceRecords' => $formattedAttendanceRecords,
        ];
    }

    /**
     * 休憩時間の合計秒数を計算する。
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
     */
    private function formatDuration(int $seconds): ?Carbon
    {
        if ($seconds <= 0) {
            return null;
        }

        return Carbon::createFromTime(0, 0, 0)
            ->addSeconds($seconds);
    }

    /**
     * 勤怠詳細を取得
     */
    public function getAttendanceDetail(User $user, AttendanceRecord $attendanceRecord): array
    {
        // 承認待ちの修正申請を取得
        $attendanceRecord->load([
            'breakTimes',
            'correctionRequests' => function ($query) {
                $query
                    ->where('approval_status', ApprovalStatus::Pending)
                    ->with('breakTimes');
            },
        ]);

        $application = $attendanceRecord->correctionRequests->first();

        // 修正申請中の場合は、申請した日時・休憩時間を表示する
        if ($application) {
            $clockIn = $application->requested_clock_in_at;
            $clockOut = $application->requested_clock_out_at;
            $breakTimes = $application->breakTimes;
        } else {
            // 修正申請がない場合は、元の勤怠情報を表示する
            $clockIn = $attendanceRecord->clock_in_at;
            $clockOut = $attendanceRecord->clock_out_at;
            $breakTimes = $attendanceRecord->breakTimes;
        }

        return [
            'user' => $user,
            'data' => [
                'id' => $attendanceRecord->id,
                'year' => $attendanceRecord->clock_in_at->format('Y年'),
                'date' => $attendanceRecord->clock_in_at->format('m月d日'),

                'clock_in' => $clockIn?->format('H:i'),
                'clock_out' => $clockOut?->format('H:i'),

                'breaks' => $breakTimes
                    ->map(function ($breakTime) {
                        return [
                            'break_in' => $breakTime->break_start_at->format('H:i'),
                            'break_out' => $breakTime->break_end_at?->format('H:i'),
                        ];
                    })
                    ->toArray(),

                'comment' => $application?->comment ?? '',
                'application' => $application,
            ],
        ];
    }
}
