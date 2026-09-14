<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * ユーザーを認証し、APIトークンを発行する。
     * 認証に失敗した場合は401 Unauthorizedを返す。
     *
     * @param  LoginRequest  $request  ログイン情報を含むリクエスト
     * @return JsonResponse 認証結果とAPIトークン
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'ログイン情報が登録されていません。',
            ], 401);
        }

        $user = Auth::user();

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
        ]);
    }
}
