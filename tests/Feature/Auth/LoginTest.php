<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PG12 ログイン（Fortify）。認証の成功・失敗、必須チェック、試行回数制限、
 * ログイン済みユーザーのリダイレクト（RedirectIfAuthenticated）を検証する。
 */
class LoginTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['email' => 'yamada@example.com', 'password' => 'password']);
    }

    /**
     * ログイン画面が表示され、メール・パスワードの入力欄と会員登録リンクがあること。
     */
    public function test_login_screen_is_displayed(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('メールアドレス')
            ->assertSee('パスワード')
            ->assertSee(route('register'));
    }

    /**
     * 正しいメール・パスワードでログインでき、書籍一覧へリダイレクトされること。
     */
    public function test_user_can_login(): void
    {
        $this->post(route('login'), ['email' => 'yamada@example.com', 'password' => 'password'])
            ->assertRedirect(route('books.index'));

        $this->assertAuthenticatedAs($this->user);
    }

    /**
     * パスワードが違うとき認証に失敗し、日本語のエラーがセッションに入ること。
     */
    public function test_login_fails_with_wrong_password(): void
    {
        $this->post(route('login'), ['email' => 'yamada@example.com', 'password' => 'wrong-password'])
            ->assertSessionHasErrors(['email' => 'ログイン情報が登録されていません。']);

        $this->assertGuest();
    }

    /**
     * 登録されていないメールアドレスのとき認証に失敗すること。
     */
    public function test_login_fails_with_unknown_email(): void
    {
        $this->post(route('login'), ['email' => 'unknown@example.com', 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    /**
     * メールアドレスが未入力のとき required エラーになること。
     */
    public function test_email_is_required(): void
    {
        $this->post(route('login'), ['email' => '', 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    /**
     * パスワードが未入力のとき required エラーになること。
     */
    public function test_password_is_required(): void
    {
        $this->post(route('login'), ['email' => 'yamada@example.com', 'password' => ''])
            ->assertSessionHasErrors('password');

        $this->assertGuest();
    }

    /**
     * 認証失敗時、ログインフォームにエラーメッセージが実際に描画されること。
     */
    public function test_login_error_is_visible_on_screen(): void
    {
        $this->from(route('login'))
            ->followingRedirects()
            ->post(route('login'), ['email' => 'yamada@example.com', 'password' => 'wrong-password'])
            ->assertOk()
            ->assertSee('ログイン情報が登録されていません。');
    }

    /**
     * 5回失敗すると 6回目は正しいパスワードでも 429 で拒否されること。
     *
     * 機能要件には無い挙動。Fortify 導入時に生成された RateLimiter::for('login')（同一メール + IP で 1分 5回）が
     * 実際に動作しているため、このテストでその挙動を固定する。
     * 合わせて FortifyServiceProvider の当該クロージャをカバレッジ対象に含める目的もある（このテストが無いと未実行のまま残る）。
     *
     * 要件外のため config/fortify.php の limiters.login を null にして無効化することも検討したが、採用しなかった。
     * Fortify は limiters.login が空だとログイン処理に自前の EnsureLoginIsNotThrottled を差し込む実装になっており
     * （vendor の AuthenticatedSessionController::loginPipeline()）、失敗 5回 でロックアウトされる制限は残る。
     * つまり制限を完全に無くす手段は無く、変わるのは「429 の画面」か「ログイン画面にエラー表示」かの見た目だけ。
     * それなら Fortify 既定の RateLimiter を総当たり対策としてそのまま残す方が、設定を増やさず説明も簡単と判断した。
     */
    public function test_login_is_throttled_after_five_failures(): void
    {
        foreach (range(1, 5) as $attempt) {
            $this->post(route('login'), ['email' => 'yamada@example.com', 'password' => 'wrong-password']);
        }

        $this->post(route('login'), ['email' => 'yamada@example.com', 'password' => 'password'])
            ->assertTooManyRequests();

        $this->assertGuest();
    }

    /**
     * ログイン済みでログイン画面を開くと書籍一覧へリダイレクトされること（RedirectIfAuthenticated の検証）。
     */
    public function test_authenticated_user_is_redirected_from_login(): void
    {
        $this->actingAs($this->user)
            ->get(route('login'))
            ->assertRedirect(route('books.index'));
    }
}
