<?php

declare(strict_types=1);

use Superjson\Superjson;
use Superjson\SuperjsonConfig;
use Superjson\Handler\GMPBigIntHandler;
use Superjson\Handler\BCMathBigIntHandler;
use Superjson\Handler\StringBigIntHandler;

describe('Dateのパース', function () {
    it('Date型をDateTimeImmutableにパースできる', function () {
        $json = '{
            "json": {"created": "2024-01-01T00:00:00.000Z"},
            "meta": {"values": {"created": ["Date"]}, "v": 1}
        }';
        $result = Superjson::parse($json);

        expect($result)->toBeInstanceOf(stdClass::class)
            ->and($result->created)->toBeInstanceOf(DateTimeImmutable::class)
            ->and($result->created->format('Y-m-d\TH:i:s.v\Z'))->toBe('2024-01-01T00:00:00.000Z');
    });

    it('タイムゾーン付きのDateをパースできる', function () {
        $json = '{
            "json": {"created": "2024-01-01T09:00:00.000+09:00"},
            "meta": {"values": {"created": ["Date"]}, "v": 1}
        }';
        $result = Superjson::parse($json);

        expect($result->created)->toBeInstanceOf(DateTimeImmutable::class)
            ->and($result->created->format('c'))->toBe('2024-01-01T09:00:00+09:00');
    });

    it('ネストされたオブジェクト内のDateをパースできる', function () {
        $json = '{
            "json": {
                "user": {
                    "name": "Alice",
                    "createdAt": "2024-01-01T00:00:00.000Z"
                }
            },
            "meta": {"values": {"user.createdAt": ["Date"]}, "v": 1}
        }';
        $result = Superjson::parse($json);

        expect($result->user->createdAt)->toBeInstanceOf(DateTimeImmutable::class)
            ->and($result->user->createdAt->format('Y-m-d'))->toBe('2024-01-01');
    });

    it('配列内のDateをパースできる', function () {
        $json = '{
            "json": {
                "dates": ["2024-01-01T00:00:00.000Z", "2024-12-31T23:59:59.999Z"]
            },
            "meta": {
                "values": {
                    "dates.0": ["Date"],
                    "dates.1": ["Date"]
                },
                "v": 1
            }
        }';
        $result = Superjson::parse($json);

        expect($result->dates)->toBeArray()
            ->and($result->dates[0])->toBeInstanceOf(DateTimeImmutable::class)
            ->and($result->dates[1])->toBeInstanceOf(DateTimeImmutable::class);
    });
});

describe('BigIntのパース（GMPハンドラー）', function () {
    beforeEach(function () {
        if (!GMPBigIntHandler::isAvailable()) {
            $this->markTestSkipped('GMP extension is not available');
        }
    });

    it('BigInt型をGMPオブジェクトにパースできる', function () {
        $json = '{
            "json": {"count": "9007199254741992"},
            "meta": {"values": {"count": ["bigint"]}, "v": 1}
        }';
        $config = new SuperjsonConfig(bigIntHandler: new GMPBigIntHandler());
        $result = Superjson::parse($json, $config);

        expect($result)->toBeInstanceOf(stdClass::class)
            ->and($result->count)->toBeInstanceOf(GMP::class)
            ->and(gmp_strval($result->count))->toBe('9007199254741992');
    });

    it('負のBigIntをパースできる', function () {
        $json = '{
            "json": {"count": "-9007199254741992"},
            "meta": {"values": {"count": ["bigint"]}, "v": 1}
        }';
        $config = new SuperjsonConfig(bigIntHandler: new GMPBigIntHandler());
        $result = Superjson::parse($json, $config);

        expect(gmp_strval($result->count))->toBe('-9007199254741992');
    });
});

describe('BigIntのパース（BCMathハンドラー）', function () {
    beforeEach(function () {
        if (!BCMathBigIntHandler::isAvailable()) {
            $this->markTestSkipped('BCMath extension is not available');
        }
    });

    it('BigInt型を文字列にパースできる', function () {
        $json = '{
            "json": {"count": "9007199254741992"},
            "meta": {"values": {"count": ["bigint"]}, "v": 1}
        }';
        $config = new SuperjsonConfig(bigIntHandler: new BCMathBigIntHandler());
        $result = Superjson::parse($json, $config);

        expect($result)->toBeInstanceOf(stdClass::class)
            ->and($result->count)->toBeString()
            ->and($result->count)->toBe('9007199254741992');
    });
});

describe('BigIntのパース（Stringハンドラー）', function () {
    it('BigInt型を文字列にパースできる', function () {
        $json = '{
            "json": {"count": "9007199254741992"},
            "meta": {"values": {"count": ["bigint"]}, "v": 1}
        }';
        $config = new SuperjsonConfig(bigIntHandler: new StringBigIntHandler());
        $result = Superjson::parse($json, $config);

        expect($result)->toBeInstanceOf(stdClass::class)
            ->and($result->count)->toBeString()
            ->and($result->count)->toBe('9007199254741992');
    });

    it('Stringハンドラーは常に利用可能', function () {
        expect(StringBigIntHandler::isAvailable())->toBeTrue();
    });
});

describe('BigIntハンドラーの自動検出', function () {
    it('自動検出されたハンドラーでパースできる', function () {
        $json = '{
            "json": {"count": "9007199254741992"},
            "meta": {"values": {"count": ["bigint"]}, "v": 1}
        }';
        $config = SuperjsonConfig::withAutoDetectedBigIntHandler();
        $result = Superjson::parse($json, $config);

        // GMPまたは文字列のいずれかである
        $isValid = $result->count instanceof GMP || is_string($result->count);
        expect($isValid)->toBeTrue();
    });
});

describe('DateとBigIntの組み合わせ', function () {
    it('DateとBigIntを同時にパースできる', function () {
        $json = '{
            "json": {
                "created": "2024-01-01T00:00:00.000Z",
                "count": "9007199254741992"
            },
            "meta": {
                "values": {
                    "created": ["Date"],
                    "count": ["bigint"]
                },
                "v": 1
            }
        }';
        $config = new SuperjsonConfig(bigIntHandler: new StringBigIntHandler());
        $result = Superjson::parse($json, $config);

        expect($result->created)->toBeInstanceOf(DateTimeImmutable::class)
            ->and($result->count)->toBe('9007199254741992');
    });
});

describe('sample.jsonとの互換性（Phase 2）', function () {
    it('sample.jsonのDateとBigIntをパースできる', function () {
        $json = file_get_contents(__DIR__ . '/../../sample.json');
        $config = new SuperjsonConfig(bigIntHandler: new StringBigIntHandler());
        $result = Superjson::parse($json, $config);

        expect($result->date)->toBeInstanceOf(DateTimeImmutable::class)
            ->and($result->date->format('Y-m-d\TH:i:s.v\Z'))->toBe('1970-01-01T00:00:00.000Z')
            ->and($result->bigint)->toBeString()
            ->and($result->bigint)->toBe('9007199254741992');
    });
});
