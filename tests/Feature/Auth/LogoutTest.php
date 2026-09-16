<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ログアウト（Fortify）。セッションが破棄されてログイン画面へリダイレクトされること。
 */
class LogoutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ログアウトするとゲストになり、ログイン画面へリダイレクトされること。
     */
    public function test_user_can_logout(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    /**
     * 未認証でログアウトを送るとログイン画面へリダイレクトされること。
     *
     * 機能要件には無い挙動。Fortify のログアウトルートに付く auth ミドルウェアの動作を固定する。
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $this->post(route('logout'))
            ->assertRedirect(route('login'));
    }
}
