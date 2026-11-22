<?php

namespace Flame;

use InvalidArgumentException;

class Jwt
{
    public static function encode(array $claims, string $secret, int $ttl = 3600)
    {
        if ($secret === '' || $ttl < 1) {
            throw new InvalidArgumentException('A non-empty JWT secret and positive lifetime are required.');
        }

        $now = time();
        $claims['iat'] = $claims['iat'] ?? $now;
        $claims['exp'] = $claims['exp'] ?? ($now + $ttl);
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];
        $encodedHeader = json_encode($header, JSON_UNESCAPED_SLASHES);
        $encodedClaims = json_encode($claims, JSON_UNESCAPED_SLASHES);
        if ($encodedHeader === false || $encodedClaims === false) {
            throw new InvalidArgumentException('JWT header or claims could not be encoded as JSON.');
        }
        $segments = [self::base64UrlEncode($encodedHeader), self::base64UrlEncode($encodedClaims)];
        $signingInput = implode('.', $segments);
        $segments[] = self::base64UrlEncode(hash_hmac('sha256', $signingInput, $secret, true));

        return implode('.', $segments);
    }

    public static function decode(string $token, string $secret, array $options = [])
    {
        if ($secret === '') {
            throw new InvalidArgumentException('A non-empty JWT secret is required.');
        }

        $segments = explode('.', $token);
        if (count($segments) !== 3) {
            throw new InvalidArgumentException('Malformed JWT.');
        }

        $header = json_decode(self::base64UrlDecode($segments[0]), true);
        $claims = json_decode(self::base64UrlDecode($segments[1]), true);
        $signature = self::base64UrlDecode($segments[2]);
        if (!is_array($header) || !is_array($claims) || ($header['alg'] ?? null) !== 'HS256') {
            throw new InvalidArgumentException('Unsupported JWT header or claims.');
        }

        $expected = hash_hmac('sha256', $segments[0] . '.' . $segments[1], $secret, true);
        if (!hash_equals($expected, $signature)) {
            throw new InvalidArgumentException('Invalid JWT signature.');
        }

        $now = time();
        $leeway = max(0, (int) ($options['leeway'] ?? 0));
        if (!isset($claims['exp']) || !is_numeric($claims['exp']) || (int) $claims['exp'] < $now - $leeway) {
            throw new InvalidArgumentException('JWT is expired or has no valid expiration.');
        }
        if (isset($claims['nbf']) && (!is_numeric($claims['nbf']) || (int) $claims['nbf'] > $now + $leeway)) {
            throw new InvalidArgumentException('JWT is not yet valid.');
        }
        if (isset($claims['iat']) && (!is_numeric($claims['iat']) || (int) $claims['iat'] > $now + $leeway)) {
            throw new InvalidArgumentException('JWT issued-at time is in the future.');
        }
        if (isset($options['issuer']) && ($claims['iss'] ?? null) !== $options['issuer']) {
            throw new InvalidArgumentException('JWT issuer does not match.');
        }
        if (isset($options['audience'])) {
            $audience = $claims['aud'] ?? null;
            $audiences = is_array($audience) ? $audience : [$audience];
            if (!in_array($options['audience'], $audiences, true)) {
                throw new InvalidArgumentException('JWT audience does not match.');
            }
        }

        return $claims;
    }

    private static function base64UrlEncode(string $value)
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function base64UrlDecode($value)
    {
        if (!is_string($value) || !preg_match('/^[A-Za-z0-9_-]*$/D', $value)) {
            throw new InvalidArgumentException('Malformed base64url value in JWT.');
        }

        $decoded = base64_decode(strtr($value, '-_', '+/') . str_repeat('=', (4 - strlen($value) % 4) % 4), true);
        if ($decoded === false) {
            throw new InvalidArgumentException('Malformed base64url value in JWT.');
        }

        return $decoded;
    }
}
