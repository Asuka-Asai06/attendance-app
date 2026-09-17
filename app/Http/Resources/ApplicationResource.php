<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationResource extends JsonResource
{
    /**
     * 修正申請リソースを配列に変換する。
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'requested_clock_in_at' => $this->requested_clock_in_at?->format('Y-m-d H:i:s'),
            'requested_clock_out_at' => $this->requested_clock_out_at?->format('Y-m-d H:i:s'),
            'comment' => $this->comment,
            'approval_status' => $this->approval_status,
            'approved_at' => $this->approved_at?->format('Y-m-d H:i:s'),

            'breaks' => CorrectionBreakResource::collection(
                $this->whenLoaded('breakTimes')
            ),
        ];
    }
}
