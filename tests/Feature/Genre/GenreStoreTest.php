<?php

namespace Tests\Feature\Genre;

use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PG07 ジャンル登録。バリデーションは PM回答の
 * required / string / max:255 / unique:genres,name に 1対1 で対応させる。
 */
class GenreStoreTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create();
    }

    /**
     * 認証済みユーザーがジャンル登録画面を開けること。
     */
    public function test_create_screen_is_displayed(): void
    {
        $this->actingAs($this->user())
            ->get(route('genres.create'))
            ->assertOk()
            ->assertSee('ジャンル登録');
    }

    /**
     * 正常な入力でジャンルが保存されること。
     */
    public function test_genre_is_stored(): void
    {
        $this->actingAs($this->user())
            ->post(route('genres.store'), ['name' => '小説']);

        $this->assertDatabaseHas('genres', ['name' => '小説']);
    }

    /**
     * 登録後に一覧へリダイレクトされ、成功メッセージがセッションに入ること。
     */
    public function test_redirects_to_index_with_success_message(): void
    {
        $this->actingAs($this->user())
            ->post(route('genres.store'), ['name' => '小説'])
            ->assertRedirect(route('genres.index'))
            ->assertSessionHas('success', 'ジャンルを作成しました。');
    }

    /**
     * リダイレクト先の画面に成功メッセージが実際に描画されること。
     */
    public function test_success_message_is_visible_on_screen(): void
    {
        $this->actingAs($this->user())
            ->followingRedirects()
            ->post(route('genres.store'), ['name' => '小説'])
            ->assertOk()
            ->assertSee('ジャンルを作成しました。');
    }

    /**
     * ジャンル名が未入力のとき required エラーになり、保存されないこと。
     */
    public function test_name_is_required(): void
    {
        $this->actingAs($this->user())
            ->post(route('genres.store'), ['name' => ''])
            ->assertSessionHasErrors(['name' => 'ジャンル名は必須です。']);

        $this->assertDatabaseCount('genres', 0);
    }

    /**
     * ジャンル名に配列を送ると string エラーになること。
     */
    public function test_name_must_be_string(): void
    {
        $this->actingAs($this->user())
            ->post(route('genres.store'), ['name' => ['小説']])
            ->assertSessionHasErrors(['name' => 'ジャンル名は文字列で入力してください。']);

        $this->assertDatabaseCount('genres', 0);
    }

    /**
     * ジャンル名が256文字のとき max エラーになること。
     */
    public function test_name_cannot_exceed255_characters(): void
    {
        $this->actingAs($this->user())
            ->post(route('genres.store'), ['name' => str_repeat('あ', 256)])
            ->assertSessionHasErrors(['name' => 'ジャンル名は255文字以内で入力してください。']);

        $this->assertDatabaseCount('genres', 0);
    }

    /**
     * ジャンル名が255文字ちょうどなら保存できること（境界値）。
     */
    public function test_name_of255_characters_is_accepted(): void
    {
        $name = str_repeat('あ', 255);

        $this->actingAs($this->user())
            ->post(route('genres.store'), ['name' => $name])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('genres', ['name' => $name]);
    }

    /**
     * 既存と同名のジャンル名で unique エラーになること。
     */
    public function test_name_must_be_unique(): void
    {
        Genre::factory()->create(['name' => '小説']);

        $this->actingAs($this->user())
            ->post(route('genres.store'), ['name' => '小説'])
            ->assertSessionHasErrors(['name' => 'そのジャンル名は既に使用されています。']);

        $this->assertDatabaseCount('genres', 1);
    }

    /**
     * バリデーション失敗時、登録フォームにエラーメッセージが実際に描画されること。
     *
     * セッションの検証だけでは Blade の @error ブロックが消えても気づけないため、
     * リダイレクト先の画面まで追って確認する（機能要件「失敗時→バリデーションエラーが表示される」）。
     */
    public function test_validation_error_is_visible_on_screen(): void
    {
        $this->actingAs($this->user())
            ->from(route('genres.create'))
            ->followingRedirects()
            ->post(route('genres.store'), ['name' => ''])
            ->assertOk()
            ->assertSee('ジャンル名は必須です。');
    }
}
