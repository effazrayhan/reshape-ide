<?php
namespace App\Lib;

class Jwt {
    public static function generate(array $p, string $secret, int $expiry = 3600): ?string {
        if (!$secret) return null;
        $p['iat'] = time();
        $p['exp'] = time() + $expiry;
        $h = self::b64(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $pl = self::b64(json_encode($p));
        $s = self::b64(hash_hmac('sha256', "$h.$pl", $secret, true));
        return "$h.$pl.$s";
    }

    public static function verify(string $t, string $secret): ?array {
        if (!$secret) return null;
        $p = explode('.', $t);
        if (count($p) !== 3) return null;
        $s = self::b64(hash_hmac('sha256', "{$p[0]}.{$p[1]}", $secret, true));
        if (!hash_equals($s, $p[2])) return null;
        $pl = json_decode(self::d64($p[1]), true);
        return ($pl && !isset($pl['exp']) || $pl['exp'] > time()) ? $pl : null;
    }

    public static function extractHeader(): ?string {
        foreach (getallheaders() as $n => $v) if (strtolower($n) === 'authorization' && preg_match('/^Bearer\s+(.+)$/i', $v, $m)) return $m[1];
        return null;
    }

    private static function b64(string $d): string { return rtrim(strtr(base64_encode($d), '+/', '-_'), '='); }
    private static function d64(string $d): string { return base64_decode(strtr($d, '-_', '+/')); }
}
