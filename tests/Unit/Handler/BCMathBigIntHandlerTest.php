<?php

declare(strict_types=1);

use Superjson\Handler\BCMathBigIntHandler;

describe('BCMathBigIntHandler', function () {
    beforeEach(function () {
        if (!BCMathBigIntHandler::isAvailable()) {
            $this->markTestSkipped('BCMath extension is not available');
        }
    });

    it('BigInt文字列をパースできる', function () {
        $handler = new BCMathBigIntHandler();
        $result = $handler->parse('9007199254741992');

        // PHP 8.4+ では BcMath\Number、それ以前では文字列
        if (PHP_VERSION_ID >= 80400) {
            expect($result)->toBeInstanceOf(\BcMath\Number::class)
                ->and((string)$result)->toBe('9007199254741992');
        } else {
            expect($result)->toBe('9007199254741992');
        }
    });

    it('負の数をパースできる', function () {
        $handler = new BCMathBigIntHandler();
        $result = $handler->parse('-9007199254741992');

        // PHP 8.4+ では BcMath\Number、それ以前では文字列
        if (PHP_VERSION_ID >= 80400) {
            expect($result)->toBeInstanceOf(\BcMath\Number::class)
                ->and((string)$result)->toBe('-9007199254741992');
        } else {
            expect($result)->toBe('-9007199254741992');
        }
    });

    it('整数を文字列に変換できる', function () {
        $handler = new BCMathBigIntHandler();
        $result = $handler->stringify(123);

        expect($result)->toBe('123');
    });

    it('文字列を文字列に変換できる', function () {
        $handler = new BCMathBigIntHandler();
        $result = $handler->stringify('9007199254741992');

        expect($result)->toBe('9007199254741992');
    });

    it('BCMath拡張が有効な場合はisAvailableがtrueを返す', function () {
        expect(BCMathBigIntHandler::isAvailable())->toBe(extension_loaded('bcmath'));
    });
});
