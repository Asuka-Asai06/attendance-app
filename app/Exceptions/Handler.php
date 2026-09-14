<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * アプリケーションの例外処理を登録する。
     */
    public function register(): void
    {
        $this->renderable(function (
            ModelNotFoundException $e,
            $request
        ) {
            if ($request->is('api/*')) {
                return response()->json([
                    'error' => '勤怠情報が見つかりませんでした。',
                ], 404);
            }
        });

        $this->renderable(function (
            ValidationException $e,
            $request
        ) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        $this->renderable(function (
            AuthenticationException $e,
            $request
        ) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Unauthenticated.',
                ], 401);
            }
        });

        $this->renderable(function (
            AuthorizationException $e,
            $request
        ) {
            if ($request->is('api/*')) {
                return response()->json([
                    'error' => 'この操作を実行する権限がありません。',
                ], 403);
            }
        });
    }
}
