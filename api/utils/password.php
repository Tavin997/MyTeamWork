<?php

namespace MyTeamWork\Utils;

/**
 * Password Utils - Segurança para senhas
 * Usa password_hash com algoritmo Argon2id (mais seguro)
 */
class Password
{
    public static function hash(string $password): string
    {
        $options = [
            'memory_cost' => 1 << 17, // 128MB
            'time_cost'   => 4,
            'threads'     => 2,
        ];

        return password_hash($password, PASSWORD_ARGON2ID, $options);
    }

    public static function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public static function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_ARGON2ID, [
            'memory_cost' => 1 << 17,
            'time_cost'   => 4,
            'threads'     => 2,
        ]);
    }

    /**
     * Gera senha aleatória segura
     */
    public static function generate(int $length = 16): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%&*?';
        $password = '';
        $max = strlen($chars) - 1;

        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, $max)];
        }

        return $password;
    }
}