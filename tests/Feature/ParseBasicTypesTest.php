<?php

declare(strict_types=1);

use Superjson\Superjson;

describe('基本型のパース', function () {
    it('文字列をパースできる', function () {
        $json = '{"json":{"name":"Alice"},"meta":{"v":1}}';
        $result = Superjson::parse($json);

        expect($result)->toBeInstanceOf(stdClass::class)
            ->and($result->name)->toBe('Alice');
    });

    it('整数をパースできる', function () {
        $json = '{"json":{"age":30},"meta":{"v":1}}';
        $result = Superjson::parse($json);

        expect($result)->toBeInstanceOf(stdClass::class)
            ->and($result->age)->toBe(30);
    });

    it('浮動小数点数をパースできる', function () {
        $json = '{"json":{"price":99.99},"meta":{"v":1}}';
        $result = Superjson::parse($json);

        expect($result)->toBeInstanceOf(stdClass::class)
            ->and($result->price)->toBe(99.99);
    });

    it('真偽値（true）をパースできる', function () {
        $json = '{"json":{"active":true},"meta":{"v":1}}';
        $result = Superjson::parse($json);

        expect($result)->toBeInstanceOf(stdClass::class)
            ->and($result->active)->toBeTrue();
    });

    it('真偽値（false）をパースできる', function () {
        $json = '{"json":{"active":false},"meta":{"v":1}}';
        $result = Superjson::parse($json);

        expect($result)->toBeInstanceOf(stdClass::class)
            ->and($result->active)->toBeFalse();
    });

    it('nullをパースできる', function () {
        $json = '{"json":{"value":null},"meta":{"v":1}}';
        $result = Superjson::parse($json);

        expect($result)->toBeInstanceOf(stdClass::class)
            ->and($result->value)->toBeNull();
    });

    it('数値インデックス配列をパースできる', function () {
        $json = '{"json":{"items":[1,2,3]},"meta":{"v":1}}';
        $result = Superjson::parse($json);

        expect($result)->toBeInstanceOf(stdClass::class)
            ->and($result->items)->toBe([1, 2, 3]);
    });

    it('ネストされたオブジェクトをパースできる', function () {
        $json = '{"json":{"user":{"name":"Alice","age":30}},"meta":{"v":1}}';
        $result = Superjson::parse($json);

        expect($result)->toBeInstanceOf(stdClass::class)
            ->and($result->user)->toBeInstanceOf(stdClass::class)
            ->and($result->user->name)->toBe('Alice')
            ->and($result->user->age)->toBe(30);
    });

    it('複数のプロパティを持つオブジェクトをパースできる', function () {
        $json = '{"json":{"string":"hello","number":123,"boolean":true,"null":null},"meta":{"v":1}}';
        $result = Superjson::parse($json);

        expect($result)->toBeInstanceOf(stdClass::class)
            ->and($result->string)->toBe('hello')
            ->and($result->number)->toBe(123)
            ->and($result->boolean)->toBeTrue()
            ->and($result->null)->toBeNull();
    });

    it('配列内のオブジェクトをパースできる', function () {
        $json = '{"json":{"users":[{"name":"Alice"},{"name":"Bob"}]},"meta":{"v":1}}';
        $result = Superjson::parse($json);

        expect($result)->toBeInstanceOf(stdClass::class)
            ->and($result->users)->toBeArray()
            ->and($result->users[0])->toBeInstanceOf(stdClass::class)
            ->and($result->users[0]->name)->toBe('Alice')
            ->and($result->users[1])->toBeInstanceOf(stdClass::class)
            ->and($result->users[1]->name)->toBe('Bob');
    });

    it('ネストされた配列をパースできる', function () {
        $json = '{"json":{"matrix":[[1,2],[3,4]]},"meta":{"v":1}}';
        $result = Superjson::parse($json);

        expect($result)->toBeInstanceOf(stdClass::class)
            ->and($result->matrix)->toBe([[1, 2], [3, 4]]);
    });

    it('空のオブジェクトをパースできる', function () {
        $json = '{"json":{},"meta":{"v":1}}';
        $result = Superjson::parse($json);

        expect($result)->toBeInstanceOf(stdClass::class)
            ->and((array)$result)->toBeEmpty();
    });

    it('空の配列をパースできる', function () {
        $json = '{"json":{"items":[]},"meta":{"v":1}}';
        $result = Superjson::parse($json);

        expect($result)->toBeInstanceOf(stdClass::class)
            ->and($result->items)->toBe([]);
    });
});

describe('deserializeメソッド', function () {
    it('配列形式のペイロードをデシリアライズできる', function () {
        $payload = [
            'json' => ['name' => 'Alice', 'age' => 30],
            'meta' => ['v' => 1],
        ];
        $result = Superjson::deserialize($payload);

        expect($result)->toBeInstanceOf(stdClass::class)
            ->and($result->name)->toBe('Alice')
            ->and($result->age)->toBe(30);
    });

    it('metaなしのペイロードをデシリアライズできる', function () {
        $payload = [
            'json' => ['name' => 'Bob'],
        ];
        $result = Superjson::deserialize($payload);

        expect($result)->toBeInstanceOf(stdClass::class)
            ->and($result->name)->toBe('Bob');
    });

    it('ネストされた構造をデシリアライズできる', function () {
        $payload = [
            'json' => [
                'user' => [
                    'profile' => [
                        'name' => 'Alice',
                        'age' => 30,
                    ],
                ],
            ],
            'meta' => ['v' => 1],
        ];
        $result = Superjson::deserialize($payload);

        expect($result)->toBeInstanceOf(stdClass::class)
            ->and($result->user)->toBeInstanceOf(stdClass::class)
            ->and($result->user->profile)->toBeInstanceOf(stdClass::class)
            ->and($result->user->profile->name)->toBe('Alice')
            ->and($result->user->profile->age)->toBe(30);
    });
});

describe('sample.jsonとの互換性', function () {
    it('sample.jsonの基本型をパースできる', function () {
        $json = file_get_contents(__DIR__ . '/../../sample.json');
        $result = Superjson::parse($json);

        expect($result)->toBeInstanceOf(stdClass::class)
            ->and($result->string)->toBe('hello')
            ->and($result->number)->toBe(123)
            ->and($result->boolean)->toBeTrue()
            ->and($result->null)->toBeNull()
            ->and($result->array)->toBe([1, 2, 3])
            ->and($result->object)->toBeInstanceOf(stdClass::class)
            ->and($result->object->key)->toBe('value');
    });
});
