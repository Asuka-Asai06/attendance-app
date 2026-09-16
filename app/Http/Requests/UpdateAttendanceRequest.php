<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator;
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
     * バリデーションルールを取得する
     *
     * @return array<string, mixed> バリデーションルール
     */
    public function rules(): array
    {
        return [
            'new_clock_in' => ['required', 'date_format:H:i'],
            'new_clock_out' => ['required', 'date_format:H:i', 'after:new_clock_in'],

            'new_break_in' => ['nullable', 'array'],
            'new_break_in.*' => ['nullable', 'date_format:H:i'],

            'new_break_out' => ['nullable', 'array'],
            'new_break_out.*' => ['nullable', 'date_format:H:i'],

            'comment' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * バリデーションエラーメッセージを取得する
     *
     * @return array<string, string> エラーメッセージ
     */
    public function messages(): array
    {
        return [
            'new_clock_in.required' => '出勤時間を入力してください',
            'new_clock_in.date_format' => '出勤時間はHH:MM形式で入力してください',

            'new_clock_out.required' => '退勤時間を入力してください',
            'new_clock_out.date_format' => '退勤時間はHH:MM形式で入力してください',
            'new_clock_out.after' => '退勤時間は出勤時間より後にしてください',

            'new_break_in.array' => '休憩開始時間の形式が不正です',
            'new_break_in.*.date_format' => '休憩開始時間はHH:MM形式で入力してください',

            'new_break_out.array' => '休憩終了時間の形式が不正です',
            'new_break_out.*.date_format' => '休憩終了時間はHH:MM形式で入力してください',

            'comment.required' => '備考を記入してください',
            'comment.string' => '備考は文字列で入力してください',
            'comment.max' => '備考は255文字以内で入力してください',
        ];
    }

    /**
     * バリデーション後の追加チェックを行う。
     *
     * @return array<int, callable> 追加バリデーション
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $this->validateClockInOut($validator);
                $this->validateBreakTimes($validator);
            },
        ];
    }

    /**
     * 出勤時間と退勤時間の前後関係を検証する。
     *
     * @param  Validator  $validator  バリデータ
     */
    private function validateClockInOut(Validator $validator): void
    {
        if (
            ! $this->filled('new_clock_in') ||
            ! $this->filled('new_clock_out')
        ) {
            return;
        }

        if (
            $this->hasDateFormatError($validator, 'new_clock_in') ||
            $this->hasDateFormatError($validator, 'new_clock_out')
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
            $this->addErrorOnce(
                $validator,
                'new_clock_in',
                $this->getClockInOutErrorMessage()
            );
        }
    }

    /**
     * 休憩時間を検証する。
     *
     * @param  Validator  $validator  バリデータ
     */
    private function validateBreakTimes(Validator $validator): void
    {
        $breakIns = $this->input('new_break_in', []);
        $breakOuts = $this->input('new_break_out', []);

        if (
            ! $this->filled('new_clock_in') ||
            ! $this->filled('new_clock_out')
        ) {
            return;
        }

        if (
            $this->hasDateFormatError($validator, 'new_clock_in') ||
            $this->hasDateFormatError($validator, 'new_clock_out')
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

            $breakInHasFormatError = $this->hasDateFormatError(
                $validator,
                "new_break_in.{$index}"
            );

            $breakOutHasFormatError = $this->hasDateFormatError(
                $validator,
                "new_break_out.{$index}"
            );

            if (
                ! $breakInHasFormatError &&
                ! $breakOutHasFormatError &&
                filled($breakIn) &&
                filled($breakOut)
            ) {
                $this->validateBreakTimeOrder(
                    $validator,
                    $breakIn,
                    $breakOut,
                    $index
                );
            }

            if (
                ! $breakInHasFormatError &&
                filled($breakIn)
            ) {
                $breakStart = $this->createTime($breakIn);

                if ($breakStart->lessThan($clockIn)) {
                    $this->addErrorOnce(
                        $validator,
                        "new_break_in.{$index}",
                        '休憩時間が不適切な値です'
                    );
                }

                if ($breakStart->greaterThan($clockOut)) {
                    $this->addErrorOnce(
                        $validator,
                        "new_break_in.{$index}",
                        '休憩時間が不適切な値です'
                    );
                }
            }

            if (
                ! $breakOutHasFormatError &&
                filled($breakOut)
            ) {
                $breakEnd = $this->createTime($breakOut);

                if ($breakEnd->lessThan($clockIn)) {
                    $this->addErrorOnce(
                        $validator,
                        "new_break_out.{$index}",
                        '休憩時間が不適切な値です'
                    );
                }

                if ($breakEnd->greaterThan($clockOut)) {
                    $this->addErrorOnce(
                        $validator,
                        "new_break_out.{$index}",
                        '休憩時間もしくは退勤時間が不適切な値です'
                    );
                }
            }
        }
    }

    /**
     * 休憩開始・終了が両方入力されているか検証する。
     *
     * @param  Validator  $validator  バリデータ
     * @param  string|null  $breakIn  休憩開始時間
     * @param  string|null  $breakOut  休憩終了時間
     * @param  int  $index  休憩のインデックス
     */
    private function validateBreakPair(
        Validator $validator,
        ?string $breakIn,
        ?string $breakOut,
        int $index
    ): void {
        if (filled($breakIn) && blank($breakOut)) {
            $this->addErrorOnce(
                $validator,
                "new_break_out.{$index}",
                '休憩終了時間を入力してください'
            );
        }

        if (blank($breakIn) && filled($breakOut)) {
            $this->addErrorOnce(
                $validator,
                "new_break_in.{$index}",
                '休憩開始時間を入力してください'
            );
        }
    }

    /**
     * 休憩開始時間と休憩終了時間の前後関係を検証する。
     *
     * @param  Validator  $validator  バリデータ
     * @param  string|null  $breakIn  休憩開始時間
     * @param  string|null  $breakOut  休憩終了時間
     * @param  int  $index  休憩のインデックス
     */
    private function validateBreakTimeOrder(Validator $validator, ?string $breakIn, ?string $breakOut, int $index): void
    {
        if (
            blank($breakIn) ||
            blank($breakOut)
        ) {
            return;
        }

        $breakStart = $this->createTime($breakIn);
        $breakEnd = $this->createTime($breakOut);

        if ($breakStart->greaterThan($breakEnd)) {
            $this->addErrorOnce(
                $validator,
                "new_break_in.{$index}",
                '休憩時間が不適切な値です'
            );
        }
    }

    /**
     * 時刻形式のエラーがあるか判定する。
     *
     * @param  Validator  $validator  バリデータ
     * @param  string  $field  フィールド名
     * @return bool 時刻形式のエラーがある場合はtrue
     */
    private function hasDateFormatError(Validator $validator, string $field): bool
    {
        $failed = $validator->failed();

        return isset($failed[$field]['DateFormat']);
    }

    /**
     * 同じフィールドに同じエラーメッセージが存在する場合は追加しない。
     *
     * @param  Validator  $validator  バリデータ
     * @param  string  $field  フィールド名
     * @param  string  $message  エラーメッセージ
     */
    private function addErrorOnce(Validator $validator, string $field, string $message): void
    {
        if (
            ! in_array(
                $message,
                $validator->errors()->get($field),
                true
            )
        ) {
            $validator->errors()->add(
                $field,
                $message
            );
        }
    }

    /**
     * 出勤・退勤の前後関係に関するエラーメッセージを取得する。
     *
     * 一般ユーザーと管理者で仕様が異なる。
     *
     * @return string エラーメッセージ
     */
    private function getClockInOutErrorMessage(): string
    {
        return $this->user()?->admin_status === true
            ? '出勤時間もしくは退勤時間が不適切な値です'
            : '出勤時間が不適切な値です';
    }

    /**
     * 時刻文字列をCarbonに変換する。
     *
     * @param  string  $time  時刻
     * @return Carbon Carbonオブジェクト
     */
    private function createTime(string $time): Carbon
    {
        return Carbon::createFromFormat('H:i', $time);
    }
}
