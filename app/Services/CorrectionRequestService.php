<?php

namespace App\Services;

use App\Models\CorrectionRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class CorrectionRequestService
{
    /**
     * 修正申請一覧を取得
     *
     * 管理者の場合は全ユーザーの申請、一般ユーザーの場合は自分の申請のみを取得
     *
     * @param  User  $user  ログインユーザー
     * @return Collection<int, CorrectionRequest>
     */
    public function getApplications(User $user): Collection
    {
        $query = CorrectionRequest::query()
            ->with([
                'user',
                'attendanceRecord',
            ]);

        if (! $user->admin_status) {
            $query->where('user_id', $user->id);
        }

        return $query
            ->latest()
            ->get();
    }

    /**
     * 修正申請の詳細を取得
     *
     * @param  CorrectionRequest  $correctionRequest  修正申請
     * @return CorrectionRequest 修正申請の詳細
     */
    public function getApplicationDetail(CorrectionRequest $correctionRequest): CorrectionRequest
    {
        $correctionRequest->load([
            'user',
            'attendanceRecord',
            'breakTimes',
        ]);

        $correctionRequest->new_date = $correctionRequest
            ->attendanceRecord
            ->clock_in_at;

        $correctionRequest->new_clock_in = $correctionRequest
            ->requested_clock_in_at
            ?->format('H:i');

        $correctionRequest->new_clock_out = $correctionRequest
            ->requested_clock_out_at
            ?->format('H:i');

        $correctionRequest->proposalBreaks = $correctionRequest
            ->breakTimes
            ->map(function ($breakTime) {
                return (object) [
                    'break_in' => $breakTime->break_start_at,
                    'break_out' => $breakTime->break_end_at,
                ];
            });

        return $correctionRequest;
    }
}
