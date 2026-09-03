<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAttendanceRequest extends FormRequest
{
    /**
     * このリクエストを実行できるか判定する。
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * バリデーションルールを取得する。
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'new_clock_in' => ['required', 'date_format:H:i'],
            'new_clock_out' => ['required', 'date_format:H:i'],

            'new_break_in' => ['nullable', 'array'],
            'new_break_in.*' => ['nullable', 'date_format:H:i'],

            'new_break_out' => ['nullable', 'array'],
            'new_break_out.*' => ['nullable', 'date_format:H:i'],

            'comment' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * バリデーションエラーメッセージを取得する。
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'new_clock_in.required' => '出勤時間を入力してください',
            'new_clock_in.date_format' => '出勤時間は「時:分」（例：09:30）の形式で入力してください',

            'new_clock_out.required' => '退勤時間を入力してください',
            'new_clock_out.date_format' => '退勤時間は「時:分」（例：09:30）の形式で入力してください',

            'new_break_in.*.date_format' => '休憩時間は「時:分」（例：09:30）の形式で入力してください',
            'new_break_out.*.date_format' => '休憩時間は「時:分」（例：09:30）の形式で入力してください',

            'comment.required' => '備考を記入してください',
            'comment.string' => '備考の形式が正しくありません',
        ];
    }

    /**
     * バリデーション後の追加チェックを行う。
     */
    public function after(): array
    {
        return [
            function ($validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $this->validateClockInOut($validator);
                $this->validateBreakTimes($validator);
            },
        ];
    }

    /**
     * 出勤時間と退勤時間の前後関係を検証する。
     */
    private function validateClockInOut($validator): void
    {
        if (
            ! $this->filled('new_clock_in') ||
            ! $this->filled('new_clock_out')
        ) {
            return;
        }

        $clockIn = $this->createTime(
            $this->input('new_clock_in')
        );

        $clockOut = $this->createTime(
            $this->input('new_clock_out')
        );

        if ($clockIn->greaterThan($clockOut)) {
            $validator->errors()->add(
                'new_clock_in',
                '出勤時間が不適切な値です'
            );
        }
    }

    /**
     * 休憩時間を検証する。
     */
    private function validateBreakTimes($validator): void
    {
        $breakIns = $this->input('new_break_in', []);
        $breakOuts = $this->input('new_break_out', []);

        if (
            ! $this->filled('new_clock_in') ||
            ! $this->filled('new_clock_out')
        ) {
            return;
        }

        $clockIn = $this->createTime(
            $this->input('new_clock_in')
        );

        $clockOut = $this->createTime(
            $this->input('new_clock_out')
        );

        foreach ($breakIns as $index => $breakIn) {
            $breakOut = $breakOuts[$index] ?? null;

            $this->validateBreakPair(
                $validator,
                $breakIn,
                $breakOut,
                $index
            );

            $this->validateBreakTimeOrder(
                $validator,
                $breakIn,
                $breakOut,
                $index
            );

            $this->validateBreakInBeforeClockIn(
                $validator,
                $breakIn,
                $clockIn,
                $index
            );

            $this->validateBreakOutBeforeClockIn(
                $validator,
                $breakOut,
                $clockIn,
                $index
            );

            $this->validateBreakInAfterClockOut(
                $validator,
                $breakIn,
                $clockOut,
                $index
            );

            $this->validateBreakOutAfterClockOut(
                $validator,
                $breakOut,
                $clockOut,
                $index
            );
        }
    }

    /**
     * 休憩開始・終了が両方入力されているか検証する。
     */
    private function validateBreakPair(
        $validator,
        ?string $breakIn,
        ?string $breakOut,
        int $index
    ): void {
        if (filled($breakIn) && blank($breakOut)) {
            $validator->errors()->add(
                "new_break_out.{$index}",
                '休憩終了時間を入力してください'
            );
        }

        if (blank($breakIn) && filled($breakOut)) {
            $validator->errors()->add(
                "new_break_in.{$index}",
                '休憩開始時間を入力してください'
            );
        }
    }

    /**
     * 休憩開始時間と休憩終了時間の前後関係を検証する。
     */
    private function validateBreakTimeOrder(
        $validator,
        ?string $breakIn,
        ?string $breakOut,
        int $index
    ): void {
        if (blank($breakIn) || blank($breakOut)) {
            return;
        }

        $breakStart = $this->createTime($breakIn);
        $breakEnd = $this->createTime($breakOut);

        if ($breakStart->greaterThan($breakEnd)) {
            $validator->errors()->add(
                "new_break_in.{$index}",
                '休憩時間が不適切な値です'
            );
        }
    }

    /**
     * 休憩開始時間が出勤時間より前になっていないか検証する。
     */
    private function validateBreakInBeforeClockIn(
        $validator,
        ?string $breakIn,
        Carbon $clockIn,
        int $index
    ): void {
        if (blank($breakIn)) {
            return;
        }

        $breakStart = $this->createTime($breakIn);

        if ($breakStart->lessThan($clockIn)) {
            $validator->errors()->add(
                "new_break_in.{$index}",
                '休憩時間もしくは出勤時間が不適切な値です'
            );
        }
    }

    /**
     * 休憩終了時間が出勤時間より前になっていないか検証する。
     */
    private function validateBreakOutBeforeClockIn(
        $validator,
        ?string $breakOut,
        Carbon $clockIn,
        int $index
    ): void {
        if (blank($breakOut)) {
            return;
        }

        $breakEnd = $this->createTime($breakOut);

        if ($breakEnd->lessThan($clockIn)) {
            $validator->errors()->add(
                "new_break_out.{$index}",
                '休憩時間もしくは出勤時間が不適切な値です'
            );
        }
    }

    /**
     * 休憩開始時間が退勤時間より後になっていないか検証する。
     */
    private function validateBreakInAfterClockOut(
        $validator,
        ?string $breakIn,
        Carbon $clockOut,
        int $index
    ): void {
        if (blank($breakIn)) {
            return;
        }

        $breakStart = $this->createTime($breakIn);

        if ($breakStart->greaterThan($clockOut)) {
            $validator->errors()->add(
                "new_break_in.{$index}",
                '休憩時間が不適切な値です'
            );
        }
    }

    /**
     * 休憩終了時間が退勤時間より後になっていないか検証する。
     */
    private function validateBreakOutAfterClockOut(
        $validator,
        ?string $breakOut,
        Carbon $clockOut,
        int $index
    ): void {
        if (blank($breakOut)) {
            return;
        }

        $breakEnd = $this->createTime($breakOut);

        if ($breakEnd->greaterThan($clockOut)) {
            $validator->errors()->add(
                "new_break_out.{$index}",
                '休憩時間が不適切な値です'
            );
        }
    }

    /**
     * 時刻文字列をCarbonに変換する。
     */
    private function createTime(string $time): Carbon
    {
        return Carbon::createFromFormat('H:i', $time);
    }
}
