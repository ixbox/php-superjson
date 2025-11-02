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
     * @param bool $strictBigInt GMP/BCMath拡張がない場合に例外を投げるか（falseの場合はStringハンドラーにフォールバック）
     */
    public function __construct(
        public bool $createUndefinedKeys = true,
        public bool $nullToUndefined = false,
        public ?BigIntHandlerInterface $bigIntHandler = null,
        public bool $strictBigInt = false,
    ) {}

    /**
     * 自動検出されたBigIntハンドラーで設定を作成
     */
    public static function withAutoDetectedBigIntHandler(): self
    {
        return new self(
            bigIntHandler: self::detectBigIntHandler(false)
        );
    }

    /**
     * 利用可能なBigIntハンドラーを自動検出
     *
     * @param bool $strict GMP/BCMathがない場合に例外を投げるか
     * @throws SuperjsonException strictモードでGMP/BCMathがない場合
     */
    private static function detectBigIntHandler(bool $strict): BigIntHandlerInterface
    {
        if (GMPBigIntHandler::isAvailable()) {
            return new GMPBigIntHandler();
        }

        if (BCMathBigIntHandler::isAvailable()) {
            return new BCMathBigIntHandler();
        }

        if ($strict) {
            throw SuperjsonException::unsupportedType(
                'BigInt requires GMP or BCMath extension in strict mode'
            );
        }

        return new StringBigIntHandler();
    }

    /**
     * BigIntハンドラーを取得（設定されていない場合は自動検出）
     *
     * @throws SuperjsonException strictBigIntがtrueでGMP/BCMathがない場合
     */
    public function getBigIntHandler(): BigIntHandlerInterface
    {
        return $this->bigIntHandler ?? self::detectBigIntHandler($this->strictBigInt);
    }
}
