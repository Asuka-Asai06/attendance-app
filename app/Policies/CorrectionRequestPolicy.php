<?php

namespace App\Policies;

use App\Models\CorrectionRequest;
use App\Models\User;

class CorrectionRequestPolicy
{
    /**
     * 修正申請の詳細を閲覧できるか判定する。
     */
    public function view(User $user, CorrectionRequest $correctionRequest): bool
    {
        return $user->id === $correctionRequest->user_id;
    }
}
