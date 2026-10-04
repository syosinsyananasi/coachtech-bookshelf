<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * 各テストの前処理。
     *
     * Blade の @vite はビルド成果物（public/build/manifest.json）が無いと例外になり、画面を描画するテストが 500 で落ちる。
     * CI ではフロントエンドをビルドしないため、テスト中は withoutVite() で @vite の出力を無効にし、
     * ビルドの有無に関係なくテストが通るようにする。
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }
}
