<?php

declare(strict_types=1);

namespace Superjson\Handler;

/**
 * GMP拡張ベースのBigIntハンドラー（推奨）
 */
final readonly class GMPBigIntHandler implements BigIntHandlerInterface
{
    public function parse(string $value): \GMP
    {
        return gmp_init($value);
    }

    public function stringify(string|int|object $value): string
    {
        if ($value instanceof \GMP) {
            return gmp_strval($value);
        }

        if (is_object($value)) {
            throw new \InvalidArgumentException(
                'GMPBigIntHandler only supports GMP objects, not ' . get_class($value)
            );
        }

        return (string)$value;
    }

    public static function isAvailable(): bool
    {
        return extension_loaded('gmp');
    }
}
