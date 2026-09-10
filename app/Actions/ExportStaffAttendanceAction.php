<?php

namespace App\Actions;

use App\Models\User;
use App\Services\AttendanceService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportStaffAttendanceAction
{
    /**
     * @param  AttendanceService  $attendanceService  勤怠情報取得サービス
     */
    public function __construct(
        private AttendanceService $attendanceService
    ) {}

    /**
     * スタッフの指定月の勤怠情報をCSV出力する
     *
     * @param  User  $user  CSV出力対象のユーザー
     * @param  string  $yearMonth  CSV出力対象の年月（Y-m）
     * @return StreamedResponse CSVファイル
     */
    public function execute(User $user, string $yearMonth): StreamedResponse
    {
        $attendanceData = $this->attendanceService
            ->getMonthlyAttendance($user, $yearMonth);

        $filename = sprintf(
            '%s_%s_勤怠一覧.csv',
            $user->name,
            $yearMonth
        );

        return response()->streamDownload(
            function () use ($attendanceData): void {
                $stream = fopen('php://output', 'w');

                fwrite($stream, "\xEF\xBB\xBF");

                fputcsv($stream, [
                    '日付',
                    '出勤',
                    '退勤',
                    '休憩',
                    '合計',
                ]);

                foreach (
                    $attendanceData['formattedAttendanceRecords'] as $attendanceRecord
                ) {
                    fputcsv($stream, [
                        $attendanceRecord['date'],
                        $attendanceRecord['clock_in'],
                        $attendanceRecord['clock_out'],
                        $attendanceRecord['total_break_time']?->format('G:i') ?? '',
                        $attendanceRecord['total_time']?->format('G:i') ?? '',
                    ]);
                }

                fclose($stream);
            },
            $filename,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]
        );
    }
}
