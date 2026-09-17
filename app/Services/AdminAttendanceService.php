<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;

class AdminAttendanceService
{
    /**
     * 指定日の全ユーザーの勤怠情報を取得する。
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
            ->whereDate('date', $date)
            ->get();

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
     * 管理者用の勤怠詳細を取得する。
     *
     * @param  AttendanceRecord  $attendanceRecord  勤怠記録
     * @return array<string, mixed> 管理者用勤怠詳細
     */
    public function getAttendanceDetail(AttendanceRecord $attendanceRecord): array
    {
        $attendanceRecord->load([
            'user',
            'breakTimes',
        ]);

        return [
            'attendanceRecord' => [
                'id' => $attendanceRecord->id,

                'year' => Carbon::parse($attendanceRecord->date)
                    ->format('Y年'),

                'date' => Carbon::parse($attendanceRecord->date)
                    ->format('m月d日'),

                'clock_in' => $attendanceRecord->clock_in_at
                    ->format('H:i'),

                'clock_out' => $attendanceRecord->clock_out_at
                    ?->format('H:i') ?? '',

                'breaks' => $attendanceRecord->breakTimes
                    ->map(function ($breakTime): array {
                        return [
                            'break_in' => $breakTime->break_start_at
                                ->format('H:i'),
                            'break_out' => $breakTime->break_end_at
                                ?->format('H:i') ?? '',
                        ];
                    })
                    ->values()
                    ->toArray(),

                'comment' => $attendanceRecord->comment,
            ],

            'user' => $attendanceRecord->user,
        ];
    }

    /**
     * ユーザーごとの月次勤怠を取得する。
     *
     * @param  User  $user  対象ユーザー
     * @param  string|null  $selectedDate  表示する年月
     * @return array<string, mixed> 月次勤怠データ
     */
    public function getUserMonthlyAttendance(User $user, ?string $selectedDate): array
    {
        $date = $selectedDate
            ? Carbon::parse($selectedDate)
            : today();

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

                    'date' => Carbon::parse($attendanceRecord->date)
                        ->format('m/d'),

                    'clock_in' => $attendanceRecord->clock_in,

                    'clock_out' => $attendanceRecord->clock_out ?? '',

                    'total_break_time' => $attendanceRecord
                        ->total_break_time,

                    'total_time' => $attendanceRecord->total_time,
                ];
            })
            ->all();

        return [
            'user' => $user,
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
}
