<?php

namespace App\Services\Api\V1;

use App\Models\AttendanceRecord;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AttendanceRecordService
{
    /**
     * 勤怠一覧を取得する
     *
     * @param  array<string, mixed>  $filters
     */
    public function getAttendanceRecords(array $filters): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 20;

        return AttendanceRecord::query()
            ->with('user')
            ->when(
                $filters['user_id'] ?? null,
                function ($query, $userId) {
                    $query->where('user_id', $userId);
                }
            )
            ->when(
                $filters['date'] ?? null,
                function ($query, $date) {
                    $query->whereDate('clock_in_at', $date);
                }
            )
            ->when(
                $filters['month'] ?? null,
                function ($query, $month) {
                    $query->whereMonth('clock_in_at', date('m', strtotime($month)))
                        ->whereYear('clock_in_at', date('Y', strtotime($month)));
                }
            )
            ->orderBy('clock_in_at')
            ->paginate($perPage);
    }
}
