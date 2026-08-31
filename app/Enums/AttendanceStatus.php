<?php

namespace App\Enums;

/**
 * 勤務状態のステータスを表すEnum
 */
enum AttendanceStatus: string
{
    // 出勤中
    case Working = 'working';

    // 休憩中
    case OnBreak = 'on_break';

    // 退勤済
    case Finished = 'finished';
}
