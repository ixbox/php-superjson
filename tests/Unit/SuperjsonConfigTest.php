<?php

declare(strict_types=1);

use Superjson\SuperjsonConfig;
use Superjson\SuperjsonException;
use Superjson\Handler\GMPBigIntHandler;
use Superjson\Handler\BCMathBigIntHandler;
use Superjson\Handler\StringBigIntHandler;

describe('SuperjsonConfig', function () {
    it('デフォルト値を持つ', function () {
        $config = new SuperjsonConfig();

        expect($config->createUndefinedKeys)->toBeTrue()
            ->and($config->nullToUndefined)->toBeFalse()
            ->and($config->bigIntHandler)->toBeNull()
            ->and($config->strictBigInt)->toBeFalse();
    });

    it('カスタム値を設定できる', function () {
        $handler = new StringBigIntHandler();
        $config = new SuperjsonConfig(
            createUndefinedKeys: false,
            nullToUndefined: true,
            bigIntHandler: $handler,
            strictBigInt: true
        );

        expect($config->createUndefinedKeys)->toBeFalse()
            ->and($config->nullToUndefined)->toBeTrue()
            ->and($config->bigIntHandler)->toBe($handler)
            ->and($config->strictBigInt)->toBeTrue();
    });

    it('getBigIntHandlerは自動検出されたハンドラーを返す（非strictモード）', function () {
        $config = new SuperjsonConfig();
        $handler = $config->getBigIntHandler();

        // GMP > BCMath > String の優先順位
        if (GMPBigIntHandler::isAvailable()) {
            expect($handler)->toBeInstanceOf(GMPBigIntHandler::class);
        } elseif (BCMathBigIntHandler::isAvailable()) {
            expect($handler)->toBeInstanceOf(BCMathBigIntHandler::class);
        } else {
            expect($handler)->toBeInstanceOf(StringBigIntHandler::class);
        }
    });

    it('getBigIntHandlerは設定されたハンドラーを返す', function () {
        $customHandler = new StringBigIntHandler();
        $config = new SuperjsonConfig(bigIntHandler: $customHandler);

        expect($config->getBigIntHandler())->toBe($customHandler);
    });

    it('strictBigIntモードでGMP/BCMathがない場合は例外を投げる', function () {
        // GMP/BCMathが両方利用可能な場合はスキップ
        if (GMPBigIntHandler::isAvailable() || BCMathBigIntHandler::isAvailable()) {
            $this->markTestSkipped('GMP or BCMath is available');
        }

        $config = new SuperjsonConfig(strictBigInt: true);

        expect(fn() => $config->getBigIntHandler())
            ->toThrow(SuperjsonException::class, 'BigInt requires GMP or BCMath extension in strict mode');
    });

    it('withAutoDetectedBigIntHandlerは自動検出されたハンドラーで設定を作成', function () {
        $config = SuperjsonConfig::withAutoDetectedBigIntHandler();
        $handler = $config->getBigIntHandler();

        if (GMPBigIntHandler::isAvailable()) {
            expect($handler)->toBeInstanceOf(GMPBigIntHandler::class);
        } elseif (BCMathBigIntHandler::isAvailable()) {
            expect($handler)->toBeInstanceOf(BCMathBigIntHandler::class);
        } else {
            expect($handler)->toBeInstanceOf(StringBigIntHandler::class);
        }
    });
});
