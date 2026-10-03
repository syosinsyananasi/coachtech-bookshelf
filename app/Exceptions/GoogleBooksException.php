<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Google Books API との通信に失敗したときに投げる例外（★応用 ISBN 検索）。
 *
 * タイムアウト・接続失敗・Google 側の 4xx / 5xx をまとめて表す。
 * 「該当なし」は正常な結果なので、この例外ではなく null で表す。
 */
class GoogleBooksException extends RuntimeException {}
