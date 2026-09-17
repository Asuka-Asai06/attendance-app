<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;

class AttendanceService
{
    /**
     * 指定された月の勤怠一覧を取得する。
     */
    public function getMonthlyAttendance(User $user, ?string $month): array
    {
        $date = $month
            ? Carbon::createFromFormat('Y-m', $month)->startOfMonth()
            : now()->startOfMonth();

        $attendanceRecords = $user->attendanceRecords()
            ->with('breakTimes')
            ->whereBetween('date', [
                $date->copy()->startOfMonth(),
                $date->copy()->endOfMonth(),
            ])
            ->orderBy('date')
            ->get();

        $formattedAttendanceRecords = $attendanceRecords
            ->map(function (AttendanceRecord $attendanceRecord): array {
                return [
                    'id' => $attendanceRecord->id,
                    'date' => $attendanceRecord->date,
                    'clock_in' => $attendanceRecord->clock_in_at->format('H:i'),
                    'clock_out' => $attendanceRecord->clock_out_at?->format('H:i') ?? '',
                    'total_break_time' => $attendanceRecord->total_break_time,
                    'total_time' => $attendanceRecord->total_time,
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
     * 勤怠詳細を取得する。
     */
    public function getAttendanceDetail(User $user, AttendanceRecord $attendanceRecord): array
    {
        $attendanceRecord->load([
            'breakTimes',
            'correctionRequests' => function ($query) {
                $query
                    ->where('approval_status', '承認待ち')
                    ->with('breakTimes');
            },
        ]);

        $application = $attendanceRecord->correctionRequests->first();

        $clockIn = $attendanceRecord->clock_in_at;
        $clockOut = $attendanceRecord->clock_out_at;
        $breakTimes = $attendanceRecord->breakTimes;

        return [
            'user' => $user,
            'data' => [
                'id' => $attendanceRecord->id,
                'year' => Carbon::parse($attendanceRecord->date)->format('Y年'),
                'date' => Carbon::parse($attendanceRecord->date)->format('m月d日'),
                'clock_in' => $clockIn?->format('H:i'),
                'clock_out' => $clockOut?->format('H:i'),

                'breaks' => $breakTimes
                    ->map(function ($breakTime): array {
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
