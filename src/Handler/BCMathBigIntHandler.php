<?php

declare(strict_types=1);

namespace Superjson\Handler;

/**
 * BCMath拡張ベースのBigIntハンドラー
 */
final readonly class BCMathBigIntHandler implements BigIntHandlerInterface
{
    public function parse(string $value): string
    {
        // BCMathは文字列で数値を扱うため、そのまま返す
        return $value;
    }

    public function stringify(string|int|\GMP $value): string
    {
        if ($value instanceof \GMP) {
            return gmp_strval($value);
        }

        return (string)$value;
    }

    public static function isAvailable(): bool
    {
        return extension_loaded('bcmath');
    }
}
