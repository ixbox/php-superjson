<?php

declare(strict_types=1);

namespace Superjson;

use Superjson\Handler\BigIntHandlerInterface;
use Superjson\Handler\GMPBigIntHandler;
use Superjson\Handler\BCMathBigIntHandler;
use Superjson\Handler\StringBigIntHandler;

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
        return new self(
            bigIntHandler: self::detectBigIntHandler()
        );
    }

    /**
     * 利用可能なBigIntハンドラーを自動検出
     */
    private static function detectBigIntHandler(): BigIntHandlerInterface
    {
        if (GMPBigIntHandler::isAvailable()) {
            return new GMPBigIntHandler();
        }

        if (BCMathBigIntHandler::isAvailable()) {
            return new BCMathBigIntHandler();
        }

        return new StringBigIntHandler();
    }

    /**
     * BigIntハンドラーを取得（設定されていない場合は自動検出）
     */
    public function getBigIntHandler(): BigIntHandlerInterface
    {
        return $this->bigIntHandler ?? self::detectBigIntHandler();
    }
}
