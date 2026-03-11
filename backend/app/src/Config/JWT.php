<?php

namespace App\Config;

use Firebase\JWT\JWT as FirebaseJWT;
use Firebase\JWT\Key;

/**
 * JWT helper — generates and validates JSON Web Tokens.
 *
 * Uses firebase/php-jwt under the hood.
 * The secret key comes from the JWT_SECRET environment variable.
 *
 * Tokens contain:
 *   - sub: user ID
 *   - username: the username
 *   - role: 'player' or 'admin'
 *   - iat: issued-at timestamp
 *   - exp: expiration timestamp (default 24 hours)
 */
class JWT
{
    /**
     * Generate a JWT for the given user.
     *
     * @param array $user  Associative array with at least 'id', 'username', 'role'
     * @return string The encoded JWT string
     */
    public static function generate(array $user): string
    {
        $secret = getenv('JWT_SECRET');
        $now = time();

        $payload = [
            'sub' => (int) $user['id'],
            'username' => $user['username'],
            'role' => $user['role'],
            'iat' => $now,
            'exp' => $now + 86400, // 24 hours
        ];

        return FirebaseJWT::encode($payload, $secret, 'HS256');
    }

    /**
     * Validate and decode a JWT.
     *
     * @param string $token The raw JWT string
     * @return object|null The decoded payload, or null if invalid/expired
     */
    public static function validate(string $token): ?object
    {
        try {
            $secret = getenv('JWT_SECRET');
            return FirebaseJWT::decode($token, new Key($secret, 'HS256'));
        } catch (\Exception $e) {
            return null;
        }
    }
}
