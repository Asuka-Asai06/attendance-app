<?php

namespace Tests\Unit;

use App\Models\CorrectionBreak;
use App\Models\CorrectionRequest;
use Tests\TestCase;

class CorrectionBreakTest extends TestCase
{
    public function test_修正申請とのリレーションが正しい(): void
    {
        $correctionRequest = CorrectionRequest::factory()->create();

        $correctionBreak = CorrectionBreak::factory()->create([
            'correction_request_id' => $correctionRequest->id,
        ]);

        $this->assertTrue(
            $correctionBreak->correctionRequest->is($correctionRequest)
        );
    }
}
