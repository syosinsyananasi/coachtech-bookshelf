<?php

namespace Tests\Feature\Genre;

use App\Models\Genre;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 未認証でジャンル一覧を開くとログイン画面にリダイレクトされること。
     */
    public function test_guest_cannot_view_index(): void
    {
        $this->get(route('genres.index'))
            ->assertStatus(302)
            ->assertRedirect(route('login'));
    }

    /**
     * 未認証でジャンル登録画面を開くとログイン画面にリダイレクトされること。
     */
    public function test_guest_cannot_view_create_screen(): void
    {
        $this->get(route('genres.create'))
            ->assertStatus(302)
            ->assertRedirect(route('login'));
    }

    /**
     * 未認証でジャンル詳細を開くとログイン画面にリダイレクトされること。
     */
    public function test_guest_cannot_view_show_screen(): void
    {
        $genre = Genre::factory()->create();

        $this->get(route('genres.show', $genre))
            ->assertStatus(302)
            ->assertRedirect(route('login'));
    }

    /**
     * 未認証でジャンル編集画面を開くとログイン画面にリダイレクトされること。
     */
    public function test_guest_cannot_view_edit_screen(): void
    {
        $genre = Genre::factory()->create();

        $this->get(route('genres.edit', $genre))
            ->assertStatus(302)
            ->assertRedirect(route('login'));
    }

    /**
     * 未認証の登録リクエストが弾かれ、ジャンルが保存されないこと。
     */
    public function test_guest_cannot_store_genre(): void
    {
        $this->post(route('genres.store'), ['name' => '小説'])
            ->assertStatus(302)
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('genres', 0);
    }

    /**
     * 未認証の更新リクエストが弾かれ、ジャンル名が変更されないこと。
     */
    public function test_guest_cannot_update_genre(): void
    {
        $genre = Genre::factory()->create(['name' => '小説']);

        $this->put(route('genres.update', $genre), ['name' => 'ビジネス'])
            ->assertStatus(302)
            ->assertRedirect(route('login'));

        $this->assertDatabaseHas('genres', ['id' => $genre->id, 'name' => '小説']);
    }

    /**
     * 未認証の削除リクエストが弾かれ、ジャンルが残ること。
     */
    public function test_guest_cannot_delete_genre(): void
    {
        $genre = Genre::factory()->create();

        $this->delete(route('genres.destroy', $genre))
            ->assertStatus(302)
            ->assertRedirect(route('login'));

        $this->assertDatabaseHas('genres', ['id' => $genre->id]);
    }
}
