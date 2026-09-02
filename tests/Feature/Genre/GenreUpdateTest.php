<?php

namespace Tests\Feature\Genre;

use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PG08 ジャンル編集。一意チェックから自身のレコードを除外することを重点的に検証する。
 */
class GenreUpdateTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create();
    }

    /**
     * 編集画面に現在のジャンル名が初期値として表示されること。
     */
    public function test_edit_screen_shows_current_name(): void
    {
        $genre = Genre::factory()->create(['name' => '小説']);

        $this->actingAs($this->user())
            ->get(route('genres.edit', $genre))
            ->assertOk()
            ->assertSee('value="小説"', false);
    }

    /**
     * ジャンル名が更新され、成功メッセージがセッションに入ること。
     */
    public function test_genre_is_updated(): void
    {
        $genre = Genre::factory()->create(['name' => '小説']);

        $this->actingAs($this->user())
            ->put(route('genres.update', $genre), ['name' => 'ビジネス'])
            ->assertRedirect(route('genres.index'))
            ->assertSessionHas('success', 'ジャンルを更新しました。');

        $this->assertDatabaseHas('genres', ['id' => $genre->id, 'name' => 'ビジネス']);
    }

    /**
     * リダイレクト先の画面に成功メッセージが実際に描画されること。
     */
    public function test_success_message_is_visible_on_screen(): void
    {
        $genre = Genre::factory()->create(['name' => '小説']);

        $this->actingAs($this->user())
            ->followingRedirects()
            ->put(route('genres.update', $genre), ['name' => 'ビジネス'])
            ->assertOk()
            ->assertSee('ジャンルを更新しました。');
    }

    /**
     * 名前を変えずに更新できること。
     * Rule::unique()->ignore() が効いていることの検証で、ignore を外すと自分自身にヒットして必ず失敗する。
     */
    public function test_same_name_can_be_submitted(): void
    {
        $genre = Genre::factory()->create(['name' => '小説']);

        $this->actingAs($this->user())
            ->put(route('genres.update', $genre), ['name' => '小説'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('genres.index'));

        $this->assertDatabaseHas('genres', ['id' => $genre->id, 'name' => '小説']);
    }

    /**
     * 他のジャンルと同名にすると unique エラーになること。
     */
    public function test_name_must_be_unique_against_other_genres(): void
    {
        Genre::factory()->create(['name' => 'ビジネス']);
        $genre = Genre::factory()->create(['name' => '小説']);

        $this->actingAs($this->user())
            ->put(route('genres.update', $genre), ['name' => 'ビジネス'])
            ->assertSessionHasErrors(['name' => 'そのジャンル名は既に使用されています。']);

        $this->assertDatabaseHas('genres', ['id' => $genre->id, 'name' => '小説']);
    }

    /**
     * ジャンル名が未入力のとき required エラーになり、更新されないこと。
     */
    public function test_name_is_required(): void
    {
        $genre = Genre::factory()->create(['name' => '小説']);

        $this->actingAs($this->user())
            ->put(route('genres.update', $genre), ['name' => ''])
            ->assertSessionHasErrors(['name' => 'ジャンル名は必須です。']);

        $this->assertDatabaseHas('genres', ['id' => $genre->id, 'name' => '小説']);
    }

    /**
     * ジャンル名が256文字のとき max エラーになり、更新されないこと。
     */
    public function test_name_cannot_exceed255_characters(): void
    {
        $genre = Genre::factory()->create(['name' => '小説']);

        $this->actingAs($this->user())
            ->put(route('genres.update', $genre), ['name' => str_repeat('あ', 256)])
            ->assertSessionHasErrors(['name' => 'ジャンル名は255文字以内で入力してください。']);

        $this->assertDatabaseHas('genres', ['id' => $genre->id, 'name' => '小説']);
    }

    /**
     * バリデーション失敗時、編集フォームにエラーメッセージが実際に描画されること。
     *
     * セッションの検証だけでは Blade の @error ブロックが消えても気づけないため、
     * リダイレクト先の画面まで追って確認する（機能要件「失敗時→バリデーションエラーが表示される」）。
     */
    public function test_validation_error_is_visible_on_screen(): void
    {
        $genre = Genre::factory()->create(['name' => '小説']);

        $this->actingAs($this->user())
            ->from(route('genres.edit', $genre))
            ->followingRedirects()
            ->put(route('genres.update', $genre), ['name' => ''])
            ->assertOk()
            ->assertSee('ジャンル名は必須です。');
    }
}
