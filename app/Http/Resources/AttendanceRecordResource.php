<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceRecordResource extends JsonResource
{
    /**
     * 勤怠記録のリソースを配列に変換する
     *
     * @return array{applications: mixed, breaks: mixed, "clock_in": mixed, "clock_out": mixed, comment: mixed, date: mixed, id: mixed, "total_break_time": mixed, "total_time": mixed, user: UserResource, "user_id": mixed}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,

            'user' => new UserResource($this->whenLoaded('user')),

            'date' => $this->date,
            'clock_in' => $this->clock_in,
            'clock_out' => $this->clock_out,

            'total_time' => $this->total_time,
            'total_break_time' => $this->total_break_time,

            'comment' => $this->comment,

            'breaks' => AttendanceBreakResource::collection($this->whenLoaded('breakTimes')),

            'applications' => ApplicationResource::collection($this->whenLoaded('correctionRequests')),
        ];
    }
}
