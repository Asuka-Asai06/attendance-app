<?php

namespace App\Http\Requests\Api\V1\Concerns;

use Carbon\Carbon;
use Illuminate\Validation\Validator;

trait ValidatesAttendanceTimes
{
    /**
     * 勤怠時間のカスタムバリデーションを追加する。
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validateClockInOut($validator);
            $this->validateBreakTimes($validator);
        });
    }

    /**
     * 出勤時間と退勤時間の前後関係を検証する。
     */
    private function validateClockInOut(Validator $validator): void
    {
        $clockIn = $this->input('clock_in');
        $clockOut = $this->input('clock_out');

        if (
            blank($clockIn)
            || blank($clockOut)
            || $validator->errors()->has('clock_in')
            || $validator->errors()->has('clock_out')
        ) {
            return;
        }

        $clockInTime = $this->createTime($clockIn);
        $clockOutTime = $this->createTime($clockOut);

        if ($clockInTime->greaterThanOrEqualTo($clockOutTime)) {
            $validator->errors()->add(
                'clock_in',
                '出勤時間もしくは退勤時間が不適切な値です。'
            );
        }
    }

    /**
     * 休憩時間の前後関係と勤怠時間内であることを検証する。
     */
    private function validateBreakTimes(Validator $validator): void
    {
        $breaks = $this->input('breaks');

        if (
            ! is_array($breaks)
            || $validator->errors()->has('clock_in')
        ) {
            return;
        }

        $clockIn = $this->input('clock_in');
        $clockOut = $this->input('clock_out');

        if (blank($clockIn)) {
            return;
        }

        $clockInTime = $this->createTime($clockIn);

        $clockOutTime = blank($clockOut)
            ? null
            : $this->createTime($clockOut);

        foreach ($breaks as $index => $break) {
            $breakIn = $break['break_in'] ?? null;
            $breakOut = $break['break_out'] ?? null;

            if (
                blank($breakIn)
                || blank($breakOut)
                || $validator->errors()->has("breaks.$index.break_in")
                || $validator->errors()->has("breaks.$index.break_out")
            ) {
                continue;
            }

            $breakInTime = $this->createTime($breakIn);
            $breakOutTime = $this->createTime($breakOut);

            // 休憩開始が休憩終了以降の場合
            if ($breakInTime->greaterThanOrEqualTo($breakOutTime)) {
                $validator->errors()->add(
                    "breaks.$index.break_in",
                    '休憩開始時間もしくは休憩終了時間が不適切な値です。'
                );

                continue;
            }

            // 休憩開始が出勤時間より前の場合
            if ($breakInTime->lessThan($clockInTime)) {
                $validator->errors()->add(
                    "breaks.$index.break_in",
                    '休憩時間が不適切な値です。'
                );
            }

            // 退勤時間がある場合は、休憩終了が退勤時間を超えていないか確認
            if (
                $clockOutTime !== null
                && $breakOutTime->greaterThan($clockOutTime)
            ) {
                $validator->errors()->add(
                    "breaks.$index.break_out",
                    '休憩時間もしくは退勤時間が不適切な値です。'
                );
            }

            // 休憩開始が退勤時間より後の場合
            if (
                $clockOutTime !== null
                && $breakInTime->greaterThan($clockOutTime)
            ) {
                $validator->errors()->add(
                    "breaks.$index.break_in",
                    '休憩時間が不適切な値です。'
                );
            }

            // 休憩終了が出勤時間より前の場合
            if ($breakOutTime->lessThan($clockInTime)) {
                $validator->errors()->add(
                    "breaks.$index.break_out",
                    '休憩時間が不適切な値です。'
                );
            }
        }
    }

    /**
     * HH:MM:SS形式の時刻をCarbonに変換する。
     */
    private function createTime(string $time): Carbon
    {
        return Carbon::createFromFormat('H:i:s', $time);
    }
}
