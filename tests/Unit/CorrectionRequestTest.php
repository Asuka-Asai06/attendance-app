<?php

namespace Tests\Unit;

use App\Models\AttendanceRecord;
use App\Models\CorrectionBreak;
use App\Models\CorrectionRequest;
use App\Models\User;
use Tests\TestCase;

class CorrectionRequestTest extends TestCase
{
    /**
     * 修正申請が勤怠記録に紐づいていることを確認する。
     */
    public function test_勤怠記録とのリレーションが正しい(): void
    {
        $attendanceRecord = AttendanceRecord::factory()->create();

        $correctionRequest = CorrectionRequest::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
        ]);

        $this->assertTrue(
            $correctionRequest->attendanceRecord->is($attendanceRecord)
        );
    }

    /**
     * 修正申請が申請ユーザーに紐づいていることを確認する。
     */
    public function test_ユーザーとのリレーションが正しい(): void
    {
        $user = User::factory()->create();

        $correctionRequest = CorrectionRequest::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->assertTrue(
            $correctionRequest->user->is($user)
        );
    }

    /**
     * 修正申請が複数の休憩時間を持つことを確認する。
     */
    public function test_休憩時間とのリレーションが正しい(): void
    {
        $correctionRequest = CorrectionRequest::factory()->create();

        $correctionBreaks = CorrectionBreak::factory()
            ->count(2)
            ->create([
                'correction_request_id' => $correctionRequest->id,
            ]);

        $this->assertCount(2, $correctionRequest->breakTimes);

        $this->assertTrue(
            $correctionRequest->breakTimes->contains(
                fn ($break) => $break->is($correctionBreaks[0])
            )
        );

        $this->assertTrue(
            $correctionRequest->breakTimes->contains(
                fn ($break) => $break->is($correctionBreaks[1])
            )
        );
    }
}
