<?php

declare(strict_types=1);

namespace Superjson\Handler;

interface BigIntHandlerInterface
{
    /**
     * BigInt文字列をPHP表現にパースする
     *
     * @param string $value BigInt文字列値
     * @return string|\GMP パースされた値
     */
    public function parse(string $value): string|\GMP;

    /**
     * PHP BigInt表現をJSON用の文字列に変換する
     *
     * @param string|int|\GMP $value BigInt値
     * @return string 文字列表現
     */
    public function stringify(string|int|\GMP $value): string;

    /**
     * このハンドラーが現在の環境で利用可能かチェック
     */
    public static function isAvailable(): bool;
}
