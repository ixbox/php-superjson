<?php

declare(strict_types=1);

namespace Superjson;

final class SuperjsonException extends \RuntimeException
{
    public static function invalidJson(string $message): self
    {
        return new self("Invalid JSON: {$message}");
    }

    public static function unsupportedType(string $type): self
    {
        return new self("Unsupported type: {$type}");
    }

    public static function invalidMetaStructure(string $message): self
    {
        return new self("Invalid meta structure: {$message}");
    }
}
