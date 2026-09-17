<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceBreakResource extends JsonResource
{
    /**
     * 休憩時間リソースを配列に変換する。
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'break_in' => $this->break_start_at?->format('H:i:s'),
            'break_out' => $this->break_end_at?->format('H:i:s'),
        ];
    }
}
