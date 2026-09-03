<?php

namespace App\Actions;

use App\Models\CorrectionRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ApproveCorrectionRequestAction
{
    /**
     * 修正申請を承認し、勤怠へ反映する。
     *
     * @param  User  $admin  承認を行う管理者
     * @param  CorrectionRequest  $correctionRequest  承認対象の修正申請
     * @return CorrectionRequest 承認した修正申請
     *
     * @throws RuntimeException 承認待ちではない場合
     */
    public function execute(User $admin, CorrectionRequest $correctionRequest): CorrectionRequest
    {
        if ($correctionRequest->approval_status !== '承認待ち') {
            throw new RuntimeException(
                'この修正申請は承認できません'
            );
        }

        return DB::transaction(function () use (
            $admin,
            $correctionRequest
        ): CorrectionRequest {
            $correctionRequest->load([
                'attendanceRecord',
                'breakTimes',
            ]);

            $attendanceRecord = $correctionRequest->attendanceRecord;

            $attendanceRecord->update([
                'clock_in_at' => $correctionRequest->requested_clock_in_at,
                'clock_out_at' => $correctionRequest->requested_clock_out_at,
            ]);

            $attendanceRecord->breakTimes()->delete();

            foreach ($correctionRequest->breakTimes as $breakTime) {
                $attendanceRecord->breakTimes()->create([
                    'break_start_at' => $breakTime->break_start_at,
                    'break_end_at' => $breakTime->break_end_at,
                ]);
            }

            $correctionRequest->update([
                'approval_status' => '承認済み',
                'approved_by' => $admin->id,
                'approved_at' => now(),
            ]);

            return $correctionRequest;
        });
    }
}
