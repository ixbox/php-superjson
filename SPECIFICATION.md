# PHP SuperJSON 仕様書

## 概要

PHP SuperJSONは、Node.jsの[superjson](https://github.com/flightcontrolhq/superjson)との互換性を提供するライブラリで、標準JSONの範囲を超えた拡張型サポートを持つJSONデータのパースと文字列化を行います。

このライブラリにより、PHPアプリケーションはJavaScript superjsonでシリアライズされたJSONデータをパースでき、その逆も可能です。メタデータを通じて型情報を維持します。

## 設計原則

- **型安全性**: 全体を通して`declare(strict_types=1)`と厳密な型ヒントを使用
- **相互運用性**: JavaScript superjsonフォーマットとの完全な互換性
- **拡張性**: 複数の実装オプションを持つプラガブルハンドラー
- **明示的な動作**: 合理的なデフォルト値を持つ明確な設定オプション

## サポートされる型

### 型マッピング表

| JavaScript型 | PHP Parse (JS → PHP) | PHP Stringify (PHP → JS) | 備考 |
|-------------|---------------------|------------------------|------|
| `string` | `string` | `string` | 直接マッピング |
| `number` | `int\|float` | `int\|float` | 直接マッピング |
| `boolean` | `bool` | `bool` | 直接マッピング |
| `null` | `null` | `null` または `undefined` | stringify時に設定可能 |
| `Array` | `array` | `array` | 数値インデックス配列 |
| `Object` | `stdClass` | `stdClass` | 安全性のため常にstdClass |
| `undefined` | `null` | N/A | オプション: キー作成の有無 |
| `bigint` | `string\|\GMP` | `string\|int\|\GMP` | プラガブルハンドラー |
| `Date` | `DateTimeImmutable` | `DateTimeInterface` | ISO 8601形式 |
| `RegExp` | `string` | N/A | パターン文字列（例: "/hello/i"） |
| `Set` | `array` | `array` (marked) | 重複を削除した配列 |
| `Map` | `array` | `array` (marked) | [key, value]ペアの配列 |
| `Error` | `stdClass` | `Throwable` | `{name, message}`構造 |
| `URL` | `string` | N/A | URL文字列 |

## SuperJSON フォーマット構造

### シリアライズされたフォーマット

```json
{
  "json": { /* 変換されたデータ */ },
  "meta": {
    "values": { /* パスから型へのマッピング */ },
    "v": 1
  }
}
```

### メタパスフォーマット

`meta.values`内のパスはドット記法を使用して値の位置を識別します：
- `"key"` - トップレベルのキー
- `"key.nested"` - ネストされたオブジェクトプロパティ
- `"array.0"` - インデックス0の配列要素

例：
```json
{
  "json": {
    "date": "1970-01-01T00:00:00.000Z",
    "nested": {
      "bigint": "9007199254741992"
    }
  },
  "meta": {
    "values": {
      "date": ["Date"],
      "nested.bigint": ["bigint"]
    },
    "v": 1
  }
}
```

## API仕様

### メインクラス

```php
declare(strict_types=1);

namespace Superjson;

final class Superjson
{
    /**
     * SuperJSON文字列をPHP値にパースする
     *
     * @param string $json SuperJSON形式のJSON文字列
     * @param SuperjsonConfig|null $config 設定オプション
     * @return mixed パースされたPHP値
     * @throws SuperjsonException パースエラー時
     */
    public static function parse(string $json, ?SuperjsonConfig $config = null): mixed;

    /**
     * PHP値をSuperJSON文字列に文字列化する
     *
     * @param mixed $data 文字列化するPHP値
     * @param SuperjsonConfig|null $config 設定オプション
     * @return string SuperJSON形式のJSON文字列
     * @throws SuperjsonException 文字列化エラー時
     */
    public static function stringify(mixed $data, ?SuperjsonConfig $config = null): string;

    /**
     * PHP値をSuperJSON配列形式にシリアライズする
     *
     * @param mixed $data シリアライズするPHP値
     * @param SuperjsonConfig|null $config 設定オプション
     * @return array{json: mixed, meta: array{values?: array<string, array<string>>, v: int}}
     * @throws SuperjsonException シリアライズエラー時
     */
    public static function serialize(mixed $data, ?SuperjsonConfig $config = null): array;

    /**
     * SuperJSON配列形式をPHP値にデシリアライズする
     *
     * @param array{json: mixed, meta?: array{values?: array<string, array<string>>, v?: int}} $payload
     * @param SuperjsonConfig|null $config 設定オプション
     * @return mixed デシリアライズされたPHP値
     * @throws SuperjsonException デシリアライズエラー時
     */
    public static function deserialize(array $payload, ?SuperjsonConfig $config = null): mixed;
}
```

### 設定クラス

```php
declare(strict_types=1);

namespace Superjson;

final readonly class SuperjsonConfig
{
    /**
     * @param bool $createUndefinedKeys undefined値をパースする際、null値でキーを作成するか
     * @param bool $nullToUndefined 文字列化時、PHPのnullをJavaScriptのundefinedに変換するか
     * @param BigIntHandlerInterface|null $bigIntHandler BigIntシリアライズのハンドラー（nullの場合は自動検出）
     */
    public function __construct(
        public bool $createUndefinedKeys = true,
        public bool $nullToUndefined = false,
        public ?BigIntHandlerInterface $bigIntHandler = null,
    ) {}

    /**
     * 自動検出されたBigIntハンドラーで設定を作成
     */
    public static function withAutoDetectedBigIntHandler(): self;
}
```

### BigIntハンドラーインターフェース

```php
declare(strict_types=1);

namespace Superjson\Handler;

interface BigIntHandlerInterface
{
    /**
     * BigInt文字列をPHP表現にパースする
     *
     * @param string $value BigInt文字列値
     * @return string|\GMP パースされた値
     */
    public function parse(string $value): string|\GMP;

    /**
     * PHP BigInt表現をJSON用の文字列に変換する
     *
     * @param string|int|\GMP $value BigInt値
     * @return string 文字列表現
     */
    public function stringify(string|int|\GMP $value): string;

    /**
     * このハンドラーが現在の環境で利用可能かチェック
     */
    public static function isAvailable(): bool;
}
```

### BigIntハンドラー実装

```php
namespace Superjson\Handler;

/**
 * GMP拡張ベースのハンドラー（推奨）
 */
final readonly class GMPBigIntHandler implements BigIntHandlerInterface
{
    public function parse(string $value): \GMP;
    public function stringify(string|int|\GMP $value): string;
    public static function isAvailable(): bool; // gmp拡張をチェック
}

/**
 * BCMath拡張ベースのハンドラー
 */
final readonly class BCMathBigIntHandler implements BigIntHandlerInterface
{
    public function parse(string $value): string;
    public function stringify(string|int $value): string;
    public static function isAvailable(): bool; // bcmath拡張をチェック
}

/**
 * 文字列のみのフォールバックハンドラー（算術演算なし）
 */
final readonly class StringBigIntHandler implements BigIntHandlerInterface
{
    public function parse(string $value): string;
    public function stringify(string|int $value): string;
    public static function isAvailable(): bool; // 常にtrue
}
```

### 例外クラス

```php
declare(strict_types=1);

namespace Superjson;

final class SuperjsonException extends \RuntimeException
{
    public static function invalidJson(string $message): self;
    public static function unsupportedType(string $type): self;
    public static function invalidMetaStructure(string $message): self;
}
```

## 設定オプション

### `createUndefinedKeys` (デフォルト: `true`)

JavaScriptからの`undefined`値をパース時にどう扱うかを制御します。

**`true`の場合** (デフォルト):
```php
// JS: {name: "Alice", age: undefined}
// PHP:
$obj = new stdClass();
$obj->name = "Alice";
$obj->age = null; // キーが存在する
isset($obj->age); // false (null値)
property_exists($obj, 'age'); // true
```

**`false`の場合**:
```php
// JS: {name: "Alice", age: undefined}
// PHP:
$obj = new stdClass();
$obj->name = "Alice";
// ageキーは作成されない
isset($obj->age); // false
property_exists($obj, 'age'); // false
```

### `nullToUndefined` (デフォルト: `false`)

PHPの`null`値をJavaScriptにシリアライズする方法を制御します。

**`false`の場合** (デフォルト):
```php
// PHP: ['name' => 'Alice', 'age' => null]
// JS: {name: "Alice", age: null}
```

**`true`の場合**:
```php
// PHP: ['name' => 'Alice', 'age' => null]
// JS: {name: "Alice", age: undefined}
```

### `bigIntHandler`

BigInt値の処理方法を指定します。`null`の場合、自動検出が使用されます：
1. `GMPBigIntHandler`を試行（GMP拡張が利用可能な場合）
2. `BCMathBigIntHandler`にフォールバック（BCMath拡張が利用可能な場合）
3. `StringBigIntHandler`にフォールバック

## 実装フェーズ

### Phase 1: 基本型のパース
- string
- number (int/float)
- boolean
- null
- Array（数値インデックス）
- Object (stdClass)

**成果物:**
- 基本型用のPestテスト
- `parse()`メソッドの実装
- `deserialize()`メソッドの実装
- メタ値パストラバーサル

### Phase 2: DateとBigIntのパース
- Date → DateTimeImmutable
- BigInt → プラガブルハンドラー（GMP/BCMath/String）

**成果物:**
- BigIntハンドラーインターフェースと実装
- 自動検出ロジック
- タイムゾーン処理を含むDateパース
- DateとBigInt用のPestテスト

### Phase 3: 複雑な型のパース
- undefined（`createUndefinedKeys`オプション付き）
- Set → array
- Map → [key, value]タプルの配列
- Error → {name, message}を持つstdClass
- URL → string
- RegExp → string

**成果物:**
- すべての複雑な型のPestテスト
- 完全な`parse()`実装

### Phase 4: Stringify実装
- すべての型変換（PHP → JS）
- メタ生成
- `nullToUndefined`オプションサポート
- `stringify()`と`serialize()`メソッド

**成果物:**
- stringify用のPestテスト
- 完全なラウンドトリップテスト（parse → 変更 → stringify）

### Phase 5: 将来の拡張機能
- POPO/DTO/VOマッピング
- カスタム型ハンドラー
- スキーマ検証

## テスト戦略

### テスト構造
```
tests/
  Unit/
    Handler/
      GMPBigIntHandlerTest.php
      BCMathBigIntHandlerTest.php
      StringBigIntHandlerTest.php
    SuperjsonConfigTest.php
  Feature/
    ParseBasicTypesTest.php
    ParseDateAndBigIntTest.php
    ParseComplexTypesTest.php
    StringifyTest.php
    RoundTripTest.php
```

### テストデータ
互換性検証の参考テストケースとして`sample.json`と`test.mjs`を使用します。

## 型安全性要件

- すべてのファイルは`declare(strict_types=1);`で始まる
- すべてのメソッドパラメータに型ヒントが必要
- すべての戻り値の型を宣言
- 適切な場所でユニオン型を使用（PHP 8.0+）
- PHPStan/Psalm level maxを目指す
- 該当する場所でreadonlyプロパティを使用（PHP 8.1+）
- デフォルトでfinalクラスを使用

## 互換性

- **最小PHPバージョン**: 8.1
- **必須拡張**: なし（コア機能）
- **オプション拡張**:
  - `ext-gmp`（BigInt用に推奨）
  - `ext-bcmath`（BigIntの代替）
- **SuperJSONバージョン**: v1メタフォーマットと互換

## 使用例

### 基本的なパース例

```php
use Superjson\Superjson;

$json = '{"json":{"name":"Alice","age":30},"meta":{"v":1}}';
$data = Superjson::parse($json);

// $dataはstdClass:
// stdClass {
//   name: "Alice"
//   age: 30
// }
```

### 特殊型を含むパース

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

$data = Superjson::parse($json);

// $data->createdはDateTimeImmutable
// $data->countはGMPオブジェクト（またはハンドラーによって文字列）
```

### Stringify例

```php
use Superjson\Superjson;
use Superjson\SuperjsonConfig;

$data = new stdClass();
$data->name = 'Alice';
$data->birthDate = new DateTime('2000-01-01');
$data->active = null;

$config = new SuperjsonConfig(nullToUndefined: true);
$json = Superjson::stringify($data, $config);

// JavaScript側での結果:
// {
//   name: "Alice",
//   birthDate: Date(2000-01-01T00:00:00.000Z),
//   active: undefined
// }
```

### カスタムBigIntハンドラー

```php
use Superjson\Superjson;
use Superjson\SuperjsonConfig;
use Superjson\Handler\GMPBigIntHandler;

$config = new SuperjsonConfig(
    bigIntHandler: new GMPBigIntHandler()
);

$json = '{"json":{"big":"9007199254741992"},"meta":{"values":{"big":["bigint"]},"v":1}}';
$data = Superjson::parse($json, $config);

// $data->bigはGMPオブジェクト
echo gmp_strval($data->big); // "9007199254741992"
```
