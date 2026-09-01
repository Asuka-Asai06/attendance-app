<?php

namespace App\Enums;

/**
 * 修正申請の承認状態を表すEnum
 */
enum ApprovalStatus: string
{
    case Pending = '承認待ち';
    case Approved = '承認済み';
}
