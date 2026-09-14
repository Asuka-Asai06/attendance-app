<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * このリクエストを実行できるか判定する
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * リクエストパラメータのバリデーションルールを返す。
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
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
            'email.required' => 'メールアドレスは必須です。',
            'email.email' => '正しいメール形式で入力してください。',
            'password.required' => 'パスワードは必須です。',
        ];
    }
}
