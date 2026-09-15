<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * PG13 会員登録（Fortify）。ユーザーが作成されてログイン状態になること、
 * バリデーションは RegisterRequest のルールとメッセージに 1対1 で対応させる。
 * ログイン済みユーザーは登録画面へ入れないこと（RedirectIfAuthenticated）。
 */
class RegisterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * バリデーションを通過する入力値を返す。
     *
     * @param  array<string, mixed>  $overrides  上書きしたい項目
     * @return array<string, mixed>
     */
    private function validData(array $overrides = []): array
    {
        return array_merge([
            'name' => '山田太郎',
            'email' => 'yamada@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ], $overrides);
    }

    /**
     * 会員登録画面が表示され、名前・メール・パスワード・確認の入力欄があること。
     */
    public function test_register_screen_is_displayed(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('お名前')
            ->assertSee('メールアドレス')
            ->assertSee('パスワード')
            ->assertSee('name="password_confirmation"', false);
    }

    /**
     * 正常な入力でユーザーが作成され、ログイン状態で書籍一覧へリダイレクトされること。
     */
    public function test_user_can_register(): void
    {
        $this->post(route('register'), $this->validData())
            ->assertRedirect(route('books.index'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['name' => '山田太郎', 'email' => 'yamada@example.com']);
    }

    /**
     * パスワードがハッシュ化されて保存されること（平文で保存されないこと）。
     */
    public function test_password_is_hashed(): void
    {
        $this->post(route('register'), $this->validData());

        $user = User::firstWhere('email', 'yamada@example.com');
        $this->assertNotSame('password', $user->password);
        $this->assertTrue(Hash::check('password', $user->password));
    }

    /**
     * 名前が未入力のとき required エラーになり、ユーザーが作成されないこと。
     */
    public function test_name_is_required(): void
    {
        $this->post(route('register'), $this->validData(['name' => '']))
            ->assertSessionHasErrors(['name' => 'お名前を入力してください']);

        $this->assertDatabaseCount('users', 0);
    }

    /**
     * メールアドレスが未入力のとき required エラーになること。
     */
    public function test_email_is_required(): void
    {
        $this->post(route('register'), $this->validData(['email' => '']))
            ->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);

        $this->assertDatabaseCount('users', 0);
    }

    /**
     * メールアドレスの形式が不正なとき email エラーになること。
     */
    public function test_email_must_be_valid(): void
    {
        $this->post(route('register'), $this->validData(['email' => 'not-an-email']))
            ->assertSessionHasErrors(['email' => 'メールアドレスはメール形式で入力してください']);

        $this->assertDatabaseCount('users', 0);
    }

    /**
     * 既に登録済みのメールアドレスのとき unique エラーになること（一意性の担保）。
     */
    public function test_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'yamada@example.com']);

        $this->post(route('register'), $this->validData(['email' => 'yamada@example.com']))
            ->assertSessionHasErrors(['email' => 'そのメールアドレスは既に使用されています。']);

        $this->assertDatabaseCount('users', 1);
    }

    /**
     * パスワードが未入力のとき required エラーになること。
     */
    public function test_password_is_required(): void
    {
        $this->post(route('register'), $this->validData(['password' => '', 'password_confirmation' => '']))
            ->assertSessionHasErrors(['password' => 'パスワードを入力してください']);

        $this->assertDatabaseCount('users', 0);
    }

    /**
     * パスワードと確認用が一致しないとき confirmed エラーになること。
     */
    public function test_password_must_be_confirmed(): void
    {
        $this->post(route('register'), $this->validData(['password_confirmation' => 'different']))
            ->assertSessionHasErrors(['password' => 'パスワードと一致しません']);

        $this->assertDatabaseCount('users', 0);
    }

    /**
     * パスワードが 7文字 のとき min エラーになること（min:8）。
     */
    public function test_password_must_be_at_least8_characters(): void
    {
        $this->post(route('register'), $this->validData(['password' => 'short12', 'password_confirmation' => 'short12']))
            ->assertSessionHasErrors(['password' => 'パスワードは8文字以上で入力してください']);

        $this->assertDatabaseCount('users', 0);
    }

    /**
     * 名前・メール・パスワードが 256文字 のとき max エラーになること。
     */
    public function test_fields_cannot_exceed255_characters(): void
    {
        $long = str_repeat('a', 256);

        $this->post(route('register'), $this->validData([
            'name' => $long,
            'email' => $long.'@example.com',
            'password' => $long,
            'password_confirmation' => $long,
        ]))->assertSessionHasErrors([
            'name' => 'お名前は255文字以内で入力してください。',
            'email' => 'メールアドレスは255文字以内で入力してください。',
            'password' => 'パスワードは255文字以内で入力してください。',
        ]);

        $this->assertDatabaseCount('users', 0);
    }

    /**
     * バリデーション失敗時、登録フォームにエラーメッセージが実際に描画されること。
     */
    public function test_validation_error_is_visible_on_screen(): void
    {
        $this->from(route('register'))
            ->followingRedirects()
            ->post(route('register'), $this->validData(['name' => '']))
            ->assertOk()
            ->assertSee('お名前を入力してください');
    }

    /**
     * ログイン済みで登録画面を開くと書籍一覧へリダイレクトされること（RedirectIfAuthenticated の検証）。
     */
    public function test_authenticated_user_is_redirected_from_register(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('register'))
            ->assertRedirect(route('books.index'));
    }
}
