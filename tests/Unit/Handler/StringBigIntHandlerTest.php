<?php

declare(strict_types=1);

use Superjson\Handler\StringBigIntHandler;

describe('StringBigIntHandler', function () {
    it('文字列をそのまま返す', function () {
        $handler = new StringBigIntHandler();
        $result = $handler->parse('9007199254741992');

        expect($result)->toBe('9007199254741992');
    });

    it('負の数をパースできる', function () {
        $handler = new StringBigIntHandler();
        $result = $handler->parse('-9007199254741992');

        expect($result)->toBe('-9007199254741992');
    });

    it('整数を文字列に変換できる', function () {
        $handler = new StringBigIntHandler();
        $result = $handler->stringify(123);

        expect($result)->toBe('123');
    });

    it('文字列を文字列に変換できる', function () {
        $handler = new StringBigIntHandler();
        $result = $handler->stringify('9007199254741992');

        expect($result)->toBe('9007199254741992');
    });

    it('isAvailableは常にtrueを返す', function () {
        expect(StringBigIntHandler::isAvailable())->toBeTrue();
    });
});
