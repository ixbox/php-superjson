<?php

declare(strict_types=1);

namespace Superjson\Handler;

/**
 * BCMath拡張ベースのBigIntハンドラー
 */
final readonly class BCMathBigIntHandler implements BigIntHandlerInterface
{
    public function parse(string $value): string|object
    {
        // PHP 8.4+ では BcMath\Number オブジェクトを返す
        if (PHP_VERSION_ID >= 80400) {
            return new \BcMath\Number($value);
        }

        // PHP < 8.4 では文字列で数値を扱う
        return $value;
    }

    public function stringify(string|int|object $value): string
    {
        if ($value instanceof \GMP) {
            throw new \InvalidArgumentException(
                'BCMathBigIntHandler does not support GMP objects. Use GMPBigIntHandler instead.'
            );
        }

        // PHP 8.4+ の BcMath\Number オブジェクトの場合
        if (PHP_VERSION_ID >= 80400 && is_object($value) && $value instanceof \BcMath\Number) {
            return (string)$value;
        }

        if (is_object($value)) {
            throw new \InvalidArgumentException(
                'BCMathBigIntHandler does not support ' . get_class($value) . ' objects.'
            );
        }

        return (string)$value;
    }

    public static function isAvailable(): bool
    {
        return extension_loaded('bcmath');
    }
}
