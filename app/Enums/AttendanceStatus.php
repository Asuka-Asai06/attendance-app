<?php

namespace App\Enums;

/**
 * 勤務状態のステータスを表すEnum
 */
enum AttendanceStatus: string
{
    case Outside = '勤務外';
    case Working = '出勤中';
    case OnBreak = '休憩中';
    case Finished = '退勤済';
}
