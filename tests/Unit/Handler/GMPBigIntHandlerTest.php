<?php

declare(strict_types=1);

use Superjson\Handler\GMPBigIntHandler;

describe('GMPBigIntHandler', function () {
    beforeEach(function () {
        if (!GMPBigIntHandler::isAvailable()) {
            $this->markTestSkipped('GMP extension is not available');
        }
    });

    it('文字列をGMPオブジェクトにパースできる', function () {
        $handler = new GMPBigIntHandler();
        $result = $handler->parse('9007199254741992');

        expect($result)->toBeInstanceOf(GMP::class)
            ->and(gmp_strval($result))->toBe('9007199254741992');
    });

    it('負の数をパースできる', function () {
        $handler = new GMPBigIntHandler();
        $result = $handler->parse('-9007199254741992');

        expect(gmp_strval($result))->toBe('-9007199254741992');
    });

    it('GMPオブジェクトを文字列に変換できる', function () {
        $handler = new GMPBigIntHandler();
        $gmp = gmp_init('9007199254741992');
        $result = $handler->stringify($gmp);

        expect($result)->toBe('9007199254741992');
    });

    it('整数を文字列に変換できる', function () {
        $handler = new GMPBigIntHandler();
        $result = $handler->stringify(123);

        expect($result)->toBe('123');
    });

    it('文字列を文字列に変換できる', function () {
        $handler = new GMPBigIntHandler();
        $result = $handler->stringify('9007199254741992');

        expect($result)->toBe('9007199254741992');
    });

    it('GMP拡張が有効な場合はisAvailableがtrueを返す', function () {
        expect(GMPBigIntHandler::isAvailable())->toBe(extension_loaded('gmp'));
    });
});
