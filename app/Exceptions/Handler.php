<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Throwable;

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
     * Register the exception handling callbacks for the application.
     *
     * 公開API（api/*）で Policy に拒否されたときは、PM 回答（Q-011-2）の文言で 403 の JSON を返す。
     * Policy の AuthorizationException は Laravel が AccessDeniedHttpException に変換してから
     * このコールバックに渡す。Web 側は通常の 403 画面のまま。
     * 未認証（401）は Laravel 既定の {"message": "Unauthenticated."} をそのまま使う（Q-011-1）。
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (AccessDeniedHttpException $e, Request $request): ?JsonResponse {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json(['error' => 'この操作を実行する権限がありません。'], Response::HTTP_FORBIDDEN);
        });
    }
}
