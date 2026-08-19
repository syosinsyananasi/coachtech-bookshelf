<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | バリデーションエラー時に表示されるメッセージ。
    | :attribute は attributes 配列の値に置き換わる。
    |
    */

    'accepted' => ':attributeを承認してください。',
    'accepted_if' => ':otherが:valueの場合、:attributeを承認してください。',
    'active_url' => ':attributeは有効なURLではありません。',
    'after' => ':attributeには:after以降の日付を指定してください。',
    'after_or_equal' => ':attributeには:after以降の日付を指定してください。',
    'alpha' => ':attributeはアルファベットのみで指定してください。',
    'alpha_dash' => ':attributeはアルファベット、数字、ハイフン、アンダースコアのみで指定してください。',
    'alpha_num' => ':attributeはアルファベットと数字のみで指定してください。',
    'array' => ':attributeは配列で指定してください。',
    'ascii' => ':attributeは半角英数字と記号のみで指定してください。',
    'before' => ':attributeには:before以前の日付を指定してください。',
    'before_or_equal' => ':attributeには:before以前の日付を指定してください。',
    'between' => [
        'array' => ':attributeは:min個から:max個の間で指定してください。',
        'file' => ':attributeは:min KBから:max KBの間で指定してください。',
        'numeric' => ':attributeは:minから:maxの間で指定してください。',
        'string' => ':attributeは:min文字から:max文字の間で指定してください。',
    ],
    'boolean' => ':attributeはtrueかfalseで指定してください。',
    'can' => ':attributeに許可されていない値が含まれています。',
    'confirmed' => ':attributeと確認用の入力が一致しません。',
    'current_password' => 'パスワードが正しくありません。',
    'date' => ':attributeは正しい日付形式で指定してください。',
    'date_equals' => ':attributeには:dateと同じ日付を指定してください。',
    'date_format' => ':attributeは":format"形式で指定してください。',
    'decimal' => ':attributeは小数点以下:decimal桁で指定してください。',
    'declined' => ':attributeを拒否してください。',
    'declined_if' => ':otherが:valueの場合、:attributeを拒否してください。',
    'different' => ':attributeと:otherには異なる値を指定してください。',
    'digits' => ':attributeは:digits桁で指定してください。',
    'digits_between' => ':attributeは:min桁から:max桁の間で指定してください。',
    'dimensions' => ':attributeの画像サイズが正しくありません。',
    'distinct' => ':attributeに重複した値があります。',
    'doesnt_end_with' => ':attributeの末尾には次のいずれも指定できません。:values',
    'doesnt_start_with' => ':attributeの先頭には次のいずれも指定できません。:values',
    'email' => ':attributeは有効なメールアドレス形式で指定してください。',
    'ends_with' => ':attributeの末尾には次のいずれかを指定してください。:values',
    'enum' => '選択された:attributeは正しくありません。',
    'exists' => '選択された:attributeは正しくありません。',
    'extensions' => ':attributeの拡張子は次のいずれかにしてください。:values',
    'file' => ':attributeはファイルを指定してください。',
    'filled' => ':attributeは必ず入力してください。',
    'gt' => [
        'array' => ':attributeは:value個より多く指定してください。',
        'file' => ':attributeは:value KBより大きいファイルを指定してください。',
        'numeric' => ':attributeは:valueより大きい値を指定してください。',
        'string' => ':attributeは:value文字より多く指定してください。',
    ],
    'gte' => [
        'array' => ':attributeは:value個以上指定してください。',
        'file' => ':attributeは:value KB以上のファイルを指定してください。',
        'numeric' => ':attributeは:value以上の値を指定してください。',
        'string' => ':attributeは:value文字以上で指定してください。',
    ],
    'hex_color' => ':attributeは有効な16進数カラーコードで指定してください。',
    'image' => ':attributeは画像ファイルを指定してください。',
    'in' => '選択された:attributeは正しくありません。',
    'in_array' => ':attributeは:otherに含まれていません。',
    'integer' => ':attributeは整数で指定してください。',
    'ip' => ':attributeは有効なIPアドレス形式で指定してください。',
    'ipv4' => ':attributeは有効なIPv4アドレス形式で指定してください。',
    'ipv6' => ':attributeは有効なIPv6アドレス形式で指定してください。',
    'json' => ':attributeは有効なJSON形式で指定してください。',
    'lowercase' => ':attributeは小文字で指定してください。',
    'lt' => [
        'array' => ':attributeは:value個より少なく指定してください。',
        'file' => ':attributeは:value KBより小さいファイルを指定してください。',
        'numeric' => ':attributeは:valueより小さい値を指定してください。',
        'string' => ':attributeは:value文字より少なく指定してください。',
    ],
    'lte' => [
        'array' => ':attributeは:value個以下で指定してください。',
        'file' => ':attributeは:value KB以下のファイルを指定してください。',
        'numeric' => ':attributeは:value以下の値を指定してください。',
        'string' => ':attributeは:value文字以内で指定してください。',
    ],
    'mac_address' => ':attributeは有効なMACアドレス形式で指定してください。',
    'max' => [
        'array' => ':attributeは:max個以下で指定してください。',
        'file' => ':attributeは:max KB以下のファイルを指定してください。',
        'numeric' => ':attributeは:max以下で指定してください。',
        'string' => ':attributeは:max文字以内で指定してください。',
    ],
    'max_digits' => ':attributeは:max桁以内で指定してください。',
    'mimes' => ':attributeは次のファイル形式で指定してください。:values',
    'mimetypes' => ':attributeは次のファイル形式で指定してください。:values',
    'min' => [
        'array' => ':attributeは:min個以上指定してください。',
        'file' => ':attributeは:min KB以上のファイルを指定してください。',
        'numeric' => ':attributeは:min以上で指定してください。',
        'string' => ':attributeは:min文字以上で指定してください。',
    ],
    'min_digits' => ':attributeは:min桁以上で指定してください。',
    'missing' => ':attributeは指定できません。',
    'missing_if' => ':otherが:valueの場合、:attributeは指定できません。',
    'missing_unless' => ':otherが:value以外の場合、:attributeは指定できません。',
    'missing_with' => ':valuesが指定されている場合、:attributeは指定できません。',
    'missing_with_all' => ':valuesがすべて指定されている場合、:attributeは指定できません。',
    'multiple_of' => ':attributeは:valueの倍数で指定してください。',
    'not_in' => '選択された:attributeは正しくありません。',
    'not_regex' => ':attributeの形式が正しくありません。',
    'numeric' => ':attributeは数値で指定してください。',
    'password' => [
        'letters' => ':attributeにはアルファベットを含めてください。',
        'mixed' => ':attributeには大文字と小文字を両方含めてください。',
        'numbers' => ':attributeには数字を含めてください。',
        'symbols' => ':attributeには記号を含めてください。',
        'uncompromised' => '指定された:attributeは漏洩している可能性があります。別の:attributeを指定してください。',
    ],
    'present' => ':attributeが存在していません。',
    'present_if' => ':otherが:valueの場合、:attributeが存在している必要があります。',
    'present_unless' => ':otherが:value以外の場合、:attributeが存在している必要があります。',
    'present_with' => ':valuesが指定されている場合、:attributeが存在している必要があります。',
    'present_with_all' => ':valuesがすべて指定されている場合、:attributeが存在している必要があります。',
    'prohibited' => ':attributeは指定できません。',
    'prohibited_if' => ':otherが:valueの場合、:attributeは指定できません。',
    'prohibited_unless' => ':otherが:values以外の場合、:attributeは指定できません。',
    'prohibits' => ':attributeを指定する場合、:otherは指定できません。',
    'regex' => ':attributeの形式が正しくありません。',
    'required' => ':attributeは必須です。',
    'required_array_keys' => ':attributeには次のキーを含めてください。:values',
    'required_if' => ':otherが:valueの場合、:attributeは必須です。',
    'required_if_accepted' => ':otherが承認された場合、:attributeは必須です。',
    'required_unless' => ':otherが:values以外の場合、:attributeは必須です。',
    'required_with' => ':valuesが指定されている場合、:attributeは必須です。',
    'required_with_all' => ':valuesがすべて指定されている場合、:attributeは必須です。',
    'required_without' => ':valuesが指定されていない場合、:attributeは必須です。',
    'required_without_all' => ':valuesがすべて指定されていない場合、:attributeは必須です。',
    'same' => ':attributeと:otherが一致しません。',
    'size' => [
        'array' => ':attributeは:size個で指定してください。',
        'file' => ':attributeは:size KBのファイルを指定してください。',
        'numeric' => ':attributeは:sizeで指定してください。',
        'string' => ':attributeは:size文字で指定してください。',
    ],
    'starts_with' => ':attributeの先頭には次のいずれかを指定してください。:values',
    'string' => ':attributeは文字列で指定してください。',
    'timezone' => ':attributeは有効なタイムゾーンで指定してください。',
    'unique' => ':attributeはすでに使用されています。',
    'uploaded' => ':attributeのアップロードに失敗しました。',
    'uppercase' => ':attributeは大文字で指定してください。',
    'url' => ':attributeは有効なURL形式で指定してください。',
    'ulid' => ':attributeは有効なULID形式で指定してください。',
    'uuid' => ':attributeは有効なUUID形式で指定してください。',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | 特定の項目 × 特定のルールだけ文言を変えたいときに使う。
    | 原則は上のテンプレート + attributes で済ませ、ここは例外的に使う。
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | :attribute に差し込まれる項目名。
    | マイグレーションでカラム名が確定してから記入する。
    | 同名カラムで意味が異なるもの（name 等）は、
    | FormRequest 側の attributes() メソッドで個別に上書きする。
    |
    */

    'attributes' => [],

];
