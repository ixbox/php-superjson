<?php

declare(strict_types=1);

namespace Superjson\Handler;

/**
 * 文字列のみのフォールバックBigIntハンドラー（算術演算なし）
 */
final readonly class StringBigIntHandler implements BigIntHandlerInterface
{
    public function parse(string $value): string
    {
        return $value;
    }

    public function stringify(string|int|object $value): string
    {
        if ($value instanceof \GMP) {
            // GMP拡張が利用可能な場合のみGMPオブジェクトを処理できる
            if (extension_loaded('gmp')) {
                return gmp_strval($value);
            }

            throw new \RuntimeException(
                'GMP extension is required to stringify GMP objects. Use GMPBigIntHandler instead.'
            );
        }

        // PHP 8.4+ の BcMath\Number オブジェクト
        if (PHP_VERSION_ID >= 80400 && is_object($value) && $value instanceof \BcMath\Number) {
            return (string)$value;
        }

        if (is_object($value)) {
            throw new \RuntimeException(
                'StringBigIntHandler does not support ' . get_class($value) . ' objects.'
            );
        }

        return (string)$value;
    }

    public static function isAvailable(): bool
    {
        return true;
    }
}
