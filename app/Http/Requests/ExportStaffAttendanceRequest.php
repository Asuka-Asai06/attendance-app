<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExportStaffAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 日付が正しい形式か検証する
     *
     * @return array{"year_month": string[]}
     */
    public function rules(): array
    {
        return [
            'year_month' => [
                'required',
                'date_format:Y-m',
            ],
        ];
    }
}
