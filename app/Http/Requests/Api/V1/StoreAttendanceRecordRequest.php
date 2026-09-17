<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Api\V1\Concerns\ValidatesAttendanceTimes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttendanceRecordRequest extends FormRequest
{
    use ValidatesAttendanceTimes;

    /**
     * このリクエストを実行できるか判定する
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * バリデーションルールを取得する
     */
    public function rules(): array
    {
        return [
            'date' => [
                'required',
                'date_format:Y-m-d',
                Rule::unique('attendance_records', 'date')
                    ->where('user_id', $this->user()->id),
            ],

            'clock_in' => ['required', 'date_format:H:i:s'],
            'clock_out' => ['nullable', 'date_format:H:i:s', 'after:clock_in'],

            'breaks' => ['nullable', 'array'],
            'breaks.*.break_in' => ['required', 'date_format:H:i:s'],
            'breaks.*.break_out' => ['required', 'date_format:H:i:s'],

            'comment' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * バリデーションエラーメッセージを返す。
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date.required' => '勤怠日は必須です。',
            'date.date_format' => '勤怠日は YYYY-MM-DD 形式で指定してください。',
            'date.unique' => 'この日付の勤怠は既に登録されています。',
            'clock_in.required' => '出勤時刻は必須です。',
            'clock_in.date_format' => '出勤時刻は HH:MM:SS 形式で指定してください。',
            'clock_out.date_format' => '退勤時刻は HH:MM:SS 形式で指定してください。',
            'clock_out.after' => '退勤時刻は出勤時刻より後の時刻を指定してください。',

            'breaks.array' => '休憩時間の形式が不正です。',
            'breaks.*.break_in.required' => '休憩開始時刻を入力してください。',
            'breaks.*.break_in.date_format' => '休憩開始時刻はHH:MM:SS形式で入力してください。',
            'breaks.*.break_out.required' => '休憩終了時刻を入力してください。',
            'breaks.*.break_out.date_format' => '休憩終了時刻はHH:MM:SS形式で入力してください。',

            'comment.max' => '備考は 255 文字以内で入力してください。',
        ];
    }
}
