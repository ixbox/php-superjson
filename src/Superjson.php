<?php

declare(strict_types=1);

namespace Superjson;

final class Superjson
{
    /**
     * SuperJSON文字列をPHP値にパースする
     *
     * @param string $json SuperJSON形式のJSON文字列
     * @param SuperjsonConfig|null $config 設定オプション
     * @return mixed パースされたPHP値
     * @throws SuperjsonException パースエラー時
     */
    public static function parse(string $json, ?SuperjsonConfig $config = null): mixed
    {
        // オブジェクトと配列を区別するため、第2引数をfalseにする
        $payload = json_decode($json, false);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw SuperjsonException::invalidJson(json_last_error_msg());
        }

        if (!is_object($payload)) {
            throw SuperjsonException::invalidJson('Payload must be an object');
        }

        if (!isset($payload->json)) {
            throw SuperjsonException::invalidMetaStructure('Missing "json" key in payload');
        }

        // json_decode(false)の場合、すでにstdClass/arrayに変換済みなのでそのまま返す
        return $payload->json;
    }

    /**
     * SuperJSON配列形式をPHP値にデシリアライズする
     *
     * @param array{json: mixed, meta?: array{values?: array<string, array<string>>, v?: int}} $payload
     * @param SuperjsonConfig|null $config 設定オプション
     * @return mixed デシリアライズされたPHP値
     * @throws SuperjsonException デシリアライズエラー時
     */
    public static function deserialize(array $payload, ?SuperjsonConfig $config = null): mixed
    {
        if (!isset($payload['json'])) {
            throw SuperjsonException::invalidMetaStructure('Missing "json" key in payload');
        }

        $json = $payload['json'];
        $meta = $payload['meta'] ?? null;

        // Phase 1: 基本型のみサポート（metaは後で処理）
        // 配列形式で渡された場合は、一旦stdClassに変換してから処理
        if (is_array($json)) {
            $json = self::arrayToMixed($json);
        }

        return $json;
    }

    /**
     * 配列を適切な型（stdClassまたは配列）に変換する
     *
     * @param array $array 変換する配列
     * @return mixed 変換後の値
     */
    private static function arrayToMixed(array $array): mixed
    {
        if (self::isAssociativeArray($array)) {
            $obj = new \stdClass();
            foreach ($array as $key => $value) {
                $obj->{$key} = is_array($value) ? self::arrayToMixed($value) : $value;
            }
            return $obj;
        } else {
            return array_map(
                fn($value) => is_array($value) ? self::arrayToMixed($value) : $value,
                $array
            );
        }
    }

    /**
     * PHP値をSuperJSON文字列に文字列化する
     *
     * @param mixed $data 文字列化するPHP値
     * @param SuperjsonConfig|null $config 設定オプション
     * @return string SuperJSON形式のJSON文字列
     * @throws SuperjsonException 文字列化エラー時
     */
    public static function stringify(mixed $data, ?SuperjsonConfig $config = null): string
    {
        $payload = self::serialize($data, $config);
        return json_encode($payload, JSON_THROW_ON_ERROR);
    }

    /**
     * PHP値をSuperJSON配列形式にシリアライズする
     *
     * @param mixed $data シリアライズするPHP値
     * @param SuperjsonConfig|null $config 設定オプション
     * @return array{json: mixed, meta: array{values?: array<string, array<string>>, v: int}}
     * @throws SuperjsonException シリアライズエラー時
     */
    public static function serialize(mixed $data, ?SuperjsonConfig $config = null): array
    {
        // Phase 4で実装
        throw new \BadMethodCallException('Not implemented yet');
    }

    /**
     * 配列が連想配列かどうかを判定する
     *
     * @param array $array 判定する配列
     * @return bool 連想配列の場合true
     */
    private static function isAssociativeArray(array $array): bool
    {
        if ($array === []) {
            return true; // 空の配列は、parse()経由の場合はstdClass、deserialize()経由の場合は文脈により判断
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }
}
