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

    public function stringify(string|int|\GMP $value): string
    {
        if ($value instanceof \GMP) {
            return gmp_strval($value);
        }

        return (string)$value;
    }

    public static function isAvailable(): bool
    {
        return true;
    }
}
