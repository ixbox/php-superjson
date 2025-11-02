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

        $config = $config ?? new SuperjsonConfig();
        $meta = isset($payload->meta) ? (array)$payload->meta : null;

        // メタデータを処理して型を復元
        return self::applyMeta($payload->json, $meta, $config);
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
        $config = $config ?? new SuperjsonConfig();

        // 配列形式で渡された場合は、一旦stdClassに変換してから処理
        if (is_array($json)) {
            $json = self::arrayToMixed($json);
        }

        // メタデータを処理して型を復元
        return self::applyMeta($json, $meta, $config);
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
     * メタデータを適用して値を復元する
     *
     * @param mixed $value 変換する値
     * @param array|null $meta メタデータ
     * @param SuperjsonConfig $config 設定
     * @return mixed 変換後の値
     */
    private static function applyMeta(mixed $value, ?array $meta, SuperjsonConfig $config): mixed
    {
        if ($meta === null || !isset($meta['values'])) {
            return $value;
        }

        $values = (array)$meta['values'];

        // メタデータの各パスに対して変換を適用
        foreach ($values as $path => $types) {
            $value = self::applyMetaAtPath($value, (string)$path, $types, $config);
        }

        return $value;
    }

    /**
     * 特定のパスにメタデータを適用
     *
     * @param mixed $root ルート値
     * @param string $path ドット記法のパス
     * @param array $types 型情報の配列
     * @param SuperjsonConfig $config 設定
     * @return mixed 変換後の値
     */
    private static function applyMetaAtPath(mixed $root, string $path, array $types, SuperjsonConfig $config): mixed
    {
        if ($path === '') {
            return self::transformByType($root, $types, $config);
        }

        $parts = explode('.', $path);
        return self::applyMetaRecursive($root, $parts, $types, $config);
    }

    /**
     * 再帰的にパスをたどってメタデータを適用
     *
     * @param mixed $current 現在の値
     * @param array $pathParts パスのパーツ配列
     * @param array $types 型情報の配列
     * @param SuperjsonConfig $config 設定
     * @return mixed 変換後の値
     */
    private static function applyMetaRecursive(mixed $current, array $pathParts, array $types, SuperjsonConfig $config): mixed
    {
        if (empty($pathParts)) {
            return $current;
        }

        $key = array_shift($pathParts);

        if (empty($pathParts)) {
            // 最後のキー：変換を適用
            if (is_object($current) && property_exists($current, $key)) {
                $current->{$key} = self::transformByType($current->{$key}, $types, $config);
            } elseif (is_array($current) && array_key_exists($key, $current)) {
                $current[$key] = self::transformByType($current[$key], $types, $config);
            }
        } else {
            // 中間のキー：再帰的に処理
            if (is_object($current) && property_exists($current, $key)) {
                $current->{$key} = self::applyMetaRecursive($current->{$key}, $pathParts, $types, $config);
            } elseif (is_array($current) && array_key_exists($key, $current)) {
                $current[$key] = self::applyMetaRecursive($current[$key], $pathParts, $types, $config);
            }
        }

        return $current;
    }

    /**
     * 型情報に基づいて値を変換
     *
     * @param mixed $value 変換する値
     * @param array $types 型情報の配列
     * @param SuperjsonConfig $config 設定
     * @return mixed 変換後の値
     */
    private static function transformByType(mixed $value, array $types, SuperjsonConfig $config): mixed
    {
        foreach ($types as $type) {
            $value = match ($type) {
                'Date' => self::parseDate($value),
                'bigint' => self::parseBigInt($value, $config),
                default => $value,
            };
        }

        return $value;
    }

    /**
     * Date文字列をDateTimeImmutableに変換
     *
     * @param mixed $value 変換する値
     * @return \DateTimeImmutable
     */
    private static function parseDate(mixed $value): \DateTimeImmutable
    {
        if ($value instanceof \DateTimeImmutable) {
            return $value;
        }

        if (!is_string($value)) {
            throw SuperjsonException::invalidMetaStructure('Date value must be a string');
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception $e) {
            throw SuperjsonException::invalidMetaStructure("Invalid date format: {$value}");
        }
    }

    /**
     * BigInt文字列を適切な型に変換
     *
     * @param mixed $value 変換する値
     * @param SuperjsonConfig $config 設定
     * @return string|\GMP
     */
    private static function parseBigInt(mixed $value, SuperjsonConfig $config): string|\GMP
    {
        if (!is_string($value)) {
            throw SuperjsonException::invalidMetaStructure('BigInt value must be a string');
        }

        $handler = $config->getBigIntHandler();
        return $handler->parse($value);
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
