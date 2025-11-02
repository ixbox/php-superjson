# PHP SuperJSON

Node.jsの[superjson](https://github.com/flightcontrolhq/superjson)との互換性を持つPHPライブラリです。

## 特徴

- **型安全**: PHP 8.2+の厳密な型システムを活用
- **拡張型サポート**: Date、BigInt、Set、Map、Error、URL、RegExpなど標準JSON以上の型をサポート
- **プラガブル設計**: BigIntハンドラーをGMP/BCMath/Stringから選択可能
- **互換性**: JavaScript superjsonとの完全な互換性

## 要件

- PHP 8.2以上
- オプション: `ext-gmp` または `ext-bcmath` (BigInt型のサポートに推奨)

## インストール

```bash
composer require ixbox/php-superjson
```

## 基本的な使い方

### パース

```php
use Superjson\Superjson;

$json = '{"json":{"name":"Alice","age":30},"meta":{"v":1}}';
$data = Superjson::parse($json);

echo $data->name; // "Alice"
```

### 特殊型のパース

```php
$json = '{
  "json": {
    "created": "2024-01-01T00:00:00.000Z",
    "count": "9007199254741992"
  },
  "meta": {
    "values": {
      "created": ["Date"],
      "count": ["bigint"]
    },
    "v": 1
  }
}';

$result = Superjson::parse($json);

// DateTimeImmutableオブジェクト
var_dump($result->created instanceof DateTimeImmutable); // true

// BigInt（GMP拡張がある場合はGMPオブジェクト）
var_dump($result->count);
```

## 設定オプション

### BigIntハンドラー

```php
use Superjson\Superjson;
use Superjson\SuperjsonConfig;
use Superjson\Handler\GMPBigIntHandler;

$config = new SuperjsonConfig(
    bigIntHandler: new GMPBigIntHandler()
);

$result = Superjson::parse($json, $config);
```

### strictBigIntモード

GMP/BCMath拡張がない場合の動作を制御します：

```php
// デフォルト（false）: 文字列にフォールバック
$config = new SuperjsonConfig(strictBigInt: false);

// strict（true）: 例外を投げる
$config = new SuperjsonConfig(strictBigInt: true);
```

## サポートされる型

| JavaScript型 | PHP型 | 備考 |
|-------------|-------|------|
| `string` | `string` | |
| `number` | `int\|float` | |
| `boolean` | `bool` | |
| `null` | `null` | |
| `Array` | `array` | 数値インデックス配列 |
| `Object` | `stdClass` | |
| `undefined` | `null` | オプションでキー作成の有無を制御 |
| `bigint` | `string\|GMP` | プラガブルハンドラー |
| `Date` | `DateTimeImmutable` | |
| `RegExp` | `string` | パターン文字列 |
| `Set` | `array` | |
| `Map` | `array` | [key, value]配列 |
| `Error` | `stdClass` | {name, message} |
| `URL` | `string` | |

## テスト

```bash
composer test
```

## ライセンス

MIT

## 関連リンク

- [Node.js superjson](https://github.com/flightcontrolhq/superjson)
- [仕様書](SPECIFICATION.md)
