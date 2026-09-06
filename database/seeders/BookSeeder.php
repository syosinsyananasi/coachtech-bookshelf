<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Database\Seeder;

class BookSeeder extends Seeder
{
    /**
     * 書籍の初期データを投入する。
     *
     * 要件で定められた11件を固定で登録し、登録者は User::first()（山田太郎）とする。
     * firstOrCreate を用いるため、繰り返し実行しても ISBN が重複しない。
     * ジャンルはジャンル名から ID を引き、genres()->sync() で中間テーブルに紐付ける。
     */
    public function run(): void
    {
        $user = User::first();

        $books = [
            [
                'title' => '吾輩は猫である',
                'author' => '夏目漱石',
                'isbn' => '9784101010014',
                'published_date' => '1905-01-01',
                'description' => '中学教師・珍野苦沙弥の家に飼われる猫の視点から、明治の知識人たちの日常を風刺的に描いた夏目漱石の処女作。',
                'genres' => ['小説'],
            ],
            [
                'title' => '人を動かす',
                'author' => 'D・カーネギー',
                'isbn' => '9784422100524',
                'published_date' => '1936-10-01',
                'description' => '人間関係の原則を豊富な実例とともに説いた自己啓発の古典。相手を尊重し、自ら動きたくなるよう導く方法を示す。',
                'genres' => ['ビジネス', '自己啓発'],
            ],
            [
                'title' => 'リーダブルコード',
                'author' => 'Dustin Boswell',
                'isbn' => '9784873115658',
                'published_date' => '2012-06-23',
                'description' => '他人が読んで理解しやすいコードを書くための技術を、命名・コメント・制御フローなどの観点から具体的に解説する。',
                'genres' => ['技術書'],
            ],
            [
                'title' => '7つの習慣',
                'author' => 'スティーブン・R・コヴィー',
                'isbn' => '9784863940246',
                'published_date' => '2013-08-30',
                'description' => '主体性や相互依存など、人格に根ざした7つの習慣を通じて、公私にわたる真の成功を築く原則を説く。',
                'genres' => ['ビジネス', '自己啓発'],
            ],
            [
                'title' => '坊っちゃん',
                'author' => '夏目漱石',
                'isbn' => '9784101010021',
                'published_date' => '1906-04-01',
                'description' => '江戸っ子気質で無鉄砲な青年教師が、四国の中学校で巻き起こす騒動を軽快に描いた夏目漱石の代表作。',
                'genres' => ['小説'],
            ],
            [
                'title' => 'サピエンス全史',
                'author' => 'ユヴァル・ノア・ハラリ',
                'isbn' => '9784309226712',
                'published_date' => '2016-09-08',
                'description' => '認知革命・農業革命・科学革命を軸に、ホモ・サピエンスが地球を支配するに至った歴史を壮大なスケールで読み解く。',
                'genres' => ['歴史', '科学'],
            ],
            [
                'title' => 'Clean Code',
                'author' => 'Robert C. Martin',
                'isbn' => '9784048930598',
                'published_date' => '2017-12-18',
                'description' => 'アジャイルソフトウェア開発の第一人者が、保守しやすく美しいコードを書くための原則と実践的な手法を解説する。',
                'genres' => ['技術書'],
            ],
            [
                'title' => '嫌われる勇気',
                'author' => '岸見一郎・古賀史健',
                'isbn' => '9784478025819',
                'published_date' => '2013-12-13',
                'description' => '哲人と青年の対話形式で、アドラー心理学の核心である「課題の分離」や「共同体感覚」を分かりやすく解き明かす。',
                'genres' => ['自己啓発'],
            ],
            [
                'title' => '火花',
                'author' => '又吉直樹',
                'isbn' => '9784163902302',
                'published_date' => '2015-03-11',
                'description' => '売れない芸人・徳永と、彼が師と仰ぐ先輩芸人・神谷の交流を通して、笑いと人生の本質を描いた芥川賞受賞作。',
                'genres' => ['小説'],
            ],
            [
                'title' => 'FACTFULNESS',
                'author' => 'ハンス・ロスリング',
                'isbn' => '9784822289607',
                'published_date' => '2019-01-11',
                'description' => '人が世界を実際より悪く捉えてしまう10の思い込みを挙げ、データに基づいて世界を正しく見る習慣を提案する。',
                'genres' => ['ビジネス', '科学'],
            ],
            [
                'title' => 'コンテナ物語',
                'author' => 'マルク・レビンソン',
                'isbn' => '9784822251468',
                'published_date' => '2007-01-18',
                'description' => '一見地味な輸送用コンテナの標準化が、いかにして世界の物流と経済のあり方を根底から変えたかを描く経済史。',
                'genres' => ['ビジネス', '歴史'],
            ],
        ];

        foreach ($books as $index => $bookData) {
            $book = Book::firstOrCreate(
                ['isbn' => $bookData['isbn']],
                [
                    'user_id' => $user->id,
                    'title' => $bookData['title'],
                    'author' => $bookData['author'],
                    'published_date' => $bookData['published_date'],
                    'description' => $bookData['description'],
                    'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text='.($index + 1),
                ],
            );

            $genreIds = Genre::whereIn('name', $bookData['genres'])->pluck('id');
            $book->genres()->sync($genreIds);
        }
    }
}
