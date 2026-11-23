<?php
declare(strict_types=1);
namespace App;
final class Security {
    public static function csrfToken(): string {
        if (empty($_SESSION['_csrf'])) $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        return $_SESSION['_csrf'];
    }
    public static function verifyCsrf(?string $token): bool {
        return is_string($token) && isset($_SESSION['_csrf']) && hash_equals($_SESSION['_csrf'], $token);
    }
    public static function requireCsrf(): void {
        if (!self::verifyCsrf($_POST['_csrf'] ?? null)) {
            http_response_code(419); exit('انتهت صلاحية النموذج. أعد تحميل الصفحة وحاول مجدداً.');
        }
    }
}
