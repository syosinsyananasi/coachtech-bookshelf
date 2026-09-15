<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * ログイン済みユーザーを送る既定のパス。
     *
     * RedirectIfAuthenticated ミドルウェアが、ログイン済みで /login や /register を開いたときの
     * リダイレクト先として参照する。機能要件「既にログイン済みでアクセスすると書籍一覧にリダイレクト」に合わせ、
     * Laravel 初期値の /home（ルート未定義で 404 になる）から書籍一覧に変更した。
     * ログイン・登録成功後の遷移先は Fortify 側の config('fortify.home') が担うため、同じ値にそろえている。
     *
     * @var string
     */
    public const HOME = '/books';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
