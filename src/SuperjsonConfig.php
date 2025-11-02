<?php

declare(strict_types=1);

namespace Superjson;

use Superjson\Handler\BigIntHandlerInterface;

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
    public static function withAutoDetectedBigIntHandler(): self
    {
        // Phase 2で実装
        return new self();
    }
}
