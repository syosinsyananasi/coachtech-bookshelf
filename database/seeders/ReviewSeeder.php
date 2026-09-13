<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    /**
     * レビューの初期データを投入する。
     *
     * 要件で定められた 32 件を固定で登録する。5 人のユーザーが 11 冊の書籍に対して
     * 各書籍 2〜4 件、rating 3〜5 の範囲で投稿する。
     * 投稿者はメールアドレス、対象書籍は ISBN から引き、レコード ID に依存しない。
     * create を用いるため、繰り返し実行すると重複する（投入し直す場合は migrate:fresh --seed を使う）。
     */
    public function run(): void
    {
        $users = User::all()->keyBy('email');
        $books = Book::all()->keyBy('isbn');

        $reviews = [
            // 1. 吾輩は猫である（3件）
            ['isbn' => '9784101010014', 'email' => 'suzuki@example.com', 'rating' => 5, 'comment' => '猫の視点から人間を観察する構図が秀逸で、皮肉とユーモアのバランスが絶妙です。何度読んでも新しい発見があります。'],
            ['isbn' => '9784101010014', 'email' => 'tanaka@example.com', 'rating' => 4, 'comment' => '文語調に慣れるまで少し時間がかかりましたが、慣れてしまえば登場人物の掛け合いが楽しくて一気に読めました。'],
            ['isbn' => '9784101010014', 'email' => 'sato@example.com', 'rating' => 3, 'comment' => '古典として読んでおきたい一冊。ただ長さの割に筋らしい筋がないので、通読するには根気が要ります。'],

            // 2. 人を動かす（3件）
            ['isbn' => '9784422100524', 'email' => 'yamada@example.com', 'rating' => 5, 'comment' => '「相手に重要感を持たせる」という原則は仕事でも家庭でもすぐに使えました。読後に人との接し方が変わった実感があります。'],
            ['isbn' => '9784422100524', 'email' => 'takahashi@example.com', 'rating' => 4, 'comment' => '事例が豊富で説得力があります。内容は普遍的ですが、古い時代のエピソードが多いので少し置き換えて読む必要があります。'],
            ['isbn' => '9784422100524', 'email' => 'suzuki@example.com', 'rating' => 4, 'comment' => '自己啓発書の原点と言われる理由がよく分かりました。批判ではなく理解から始める姿勢を意識するようになりました。'],

            // 3. リーダブルコード（4件）
            ['isbn' => '9784873115658', 'email' => 'yamada@example.com', 'rating' => 5, 'comment' => '命名とコメントの章だけでも読む価値があります。自分の書いたコードを見返して恥ずかしくなるほど具体的な指摘ばかりです。'],
            ['isbn' => '9784873115658', 'email' => 'tanaka@example.com', 'rating' => 5, 'comment' => 'チームの新人に最初に渡す本はこれと決めています。薄いのにコードレビューで指摘される内容のほとんどが網羅されています。'],
            ['isbn' => '9784873115658', 'email' => 'sato@example.com', 'rating' => 4, 'comment' => '例が短くて分かりやすい。ただ内容はどれも基本的なので、中級者以上には確認用という位置づけになると思います。'],
            ['isbn' => '9784873115658', 'email' => 'takahashi@example.com', 'rating' => 4, 'comment' => '「コードは他の人が最短時間で理解できるように書く」という一文が全てを表しています。定期的に読み返したい本です。'],

            // 4. 7つの習慣（3件）
            ['isbn' => '9784863940246', 'email' => 'suzuki@example.com', 'rating' => 4, 'comment' => '第2の習慣「終わりを思い描くことから始める」が特に響きました。分厚いですが章ごとに独立して読めるので挫折しにくいです。'],
            ['isbn' => '9784863940246', 'email' => 'sato@example.com', 'rating' => 3, 'comment' => '内容は納得できるものの、説明が冗長で同じことを何度も繰り返している印象です。要約版でも十分かもしれません。'],
            ['isbn' => '9784863940246', 'email' => 'takahashi@example.com', 'rating' => 5, 'comment' => '小手先のテクニックではなく人格を磨くという根本的な話なので、読んだ後の効き目が長続きします。人生で何度も読み返す本です。'],

            // 5. 坊っちゃん（2件）
            ['isbn' => '9784101010021', 'email' => 'yamada@example.com', 'rating' => 4, 'comment' => 'テンポが良く、主人公の無鉄砲さが痛快です。漱石の作品の中では圧倒的に読みやすいので入門に向いています。'],
            ['isbn' => '9784101010021', 'email' => 'tanaka@example.com', 'rating' => 3, 'comment' => '中学の教科書以来の再読でした。大人になって読むと赤シャツ側の事情も少し分かってしまい、素直に笑えない部分もありました。'],

            // 6. サピエンス全史（3件）
            ['isbn' => '9784309226712', 'email' => 'yamada@example.com', 'rating' => 5, 'comment' => '「虚構を信じる力」が人類を支配的にしたという視点に衝撃を受けました。歴史書というより人類についての思考の枠組みを変える本です。'],
            ['isbn' => '9784309226712', 'email' => 'suzuki@example.com', 'rating' => 4, 'comment' => '上下巻で長いですが、文章が平易で飽きません。農業革命が人類を幸せにしなかったという章は考えさせられました。'],
            ['isbn' => '9784309226712', 'email' => 'takahashi@example.com', 'rating' => 4, 'comment' => '大胆な仮説が多く、学術的にどこまで正しいかは議論があると思いますが、読み物としては最高に面白いです。'],

            // 7. Clean Code（3件）
            ['isbn' => '9784048930598', 'email' => 'tanaka@example.com', 'rating' => 5, 'comment' => '関数は小さく、ひとつのことだけをする。この原則を徹底するだけでコードの見通しが劇的に良くなりました。'],
            ['isbn' => '9784048930598', 'email' => 'sato@example.com', 'rating' => 3, 'comment' => 'Java のサンプルが中心なので他言語の人には少し読みにくいです。前半の原則部分は言語を問わず参考になります。'],
            ['isbn' => '9784048930598', 'email' => 'yamada@example.com', 'rating' => 4, 'comment' => 'リーダブルコードの次に読む本として最適です。テストコードやエラー処理の章まで踏み込んでいるのが良いところです。'],

            // 8. 嫌われる勇気（3件）
            ['isbn' => '9784478025819', 'email' => 'suzuki@example.com', 'rating' => 5, 'comment' => '「課題の分離」を知ってから、他人の評価に振り回されることが減りました。対話形式なので難しい心理学の話もすっと入ってきます。'],
            ['isbn' => '9784478025819', 'email' => 'takahashi@example.com', 'rating' => 4, 'comment' => '青年の反論が自分の疑問そのままで、読み進めるうちに一緒に納得していく感覚がありました。実践は簡単ではありませんが。'],
            ['isbn' => '9784478025819', 'email' => 'tanaka@example.com', 'rating' => 3, 'comment' => 'トラウマを否定する考え方には賛否があると思います。刺激的ではありますが、鵜呑みにせず距離を置いて読むのが良さそうです。'],

            // 9. 火花（3件）
            ['isbn' => '9784163902302', 'email' => 'sato@example.com', 'rating' => 4, 'comment' => '売れない芸人の日常が丁寧に描かれていて、笑いに人生を賭ける人の切実さが伝わってきました。終盤の展開は少し驚きました。'],
            ['isbn' => '9784163902302', 'email' => 'yamada@example.com', 'rating' => 3, 'comment' => '文章は美しいのですが、物語としての起伏は少なめです。神谷という人物の魅力で最後まで引っ張られた感じがします。'],
            ['isbn' => '9784163902302', 'email' => 'suzuki@example.com', 'rating' => 4, 'comment' => '芸人が書いた小説という先入観を良い意味で裏切られました。「面白いとは何か」を真剣に考える二人の会話が印象に残ります。'],

            // 10. FACTFULNESS（3件）
            ['isbn' => '9784822289607', 'email' => 'takahashi@example.com', 'rating' => 5, 'comment' => '冒頭のクイズで自分の思い込みの強さを痛感しました。データで世界を見る習慣がつく本で、ニュースの受け取り方が変わります。'],
            ['isbn' => '9784822289607', 'email' => 'tanaka@example.com', 'rating' => 4, 'comment' => '10 の本能という切り口が分かりやすく、ビジネスの意思決定にも応用できます。グラフが多くて視覚的にも理解しやすいです。'],
            ['isbn' => '9784822289607', 'email' => 'sato@example.com', 'rating' => 4, 'comment' => '世界は思っているより良くなっている、というメッセージに救われました。悲観的なニュースに疲れている人におすすめです。'],

            // 11. コンテナ物語（2件）
            ['isbn' => '9784822251468', 'email' => 'yamada@example.com', 'rating' => 4, 'comment' => '地味な箱の規格化が世界経済を変えたという話が、これほど面白く読めるとは思いませんでした。標準化の重要性を実感します。'],
            ['isbn' => '9784822251468', 'email' => 'takahashi@example.com', 'rating' => 3, 'comment' => '港湾労働や規格争いの細部まで書かれていて情報量は圧倒的ですが、その分テンポは遅めです。興味のある章から読むのが良いと思います。'],
        ];

        collect($reviews)->each(fn (array $review) => Review::create([
            'user_id' => $users[$review['email']]->id,
            'book_id' => $books[$review['isbn']]->id,
            'rating' => $review['rating'],
            'comment' => $review['comment'],
        ]));
    }
}
