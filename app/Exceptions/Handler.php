<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
            NotFoundHttpException $e,
            Request $request
        ) {
            if (
                $request->is('api/*')
                && $e->getPrevious() instanceof ModelNotFoundException
            ) {
                return response()->json([
                    'error' => '勤怠情報が見つかりませんでした。',
                ], 404);
            }

            return null;
        });

        $this->renderable(function (
            ValidationException $e,
            Request $request
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
            Request $request
        ) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Unauthenticated.',
                ], 401);
            }
        });

        $this->renderable(function (
            AuthorizationException|AccessDeniedHttpException $e,
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
