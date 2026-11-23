<?php
declare(strict_types=1);
namespace App;
use PDO;
final class Auth {
    public static function user(): ?array { return $_SESSION['admin_user'] ?? null; }
    public static function attempt(PDO $pdo,string $email,string $password): bool {
        $now=time(); $_SESSION['_login_attempts']=$_SESSION['_login_attempts']??[];
        $_SESSION['_login_attempts']=array_values(array_filter($_SESSION['_login_attempts'],fn($t)=>$t>$now-900));
        if(count($_SESSION['_login_attempts'])>=8) return false;
        $stmt=$pdo->prepare("SELECT u.id,u.name,u.email,u.password_hash,u.role_id,r.name AS role_name FROM users u JOIN roles r ON r.id=u.role_id WHERE u.email=? AND u.is_active=1 LIMIT 1");
        $stmt->execute([mb_strtolower(trim($email))]); $user=$stmt->fetch();
        if(!$user || !password_verify($password,$user['password_hash'])) { $_SESSION['_login_attempts'][]=$now; return false; }
        session_regenerate_id(true); unset($user['password_hash']); $_SESSION['admin_user']=$user; $_SESSION['_login_attempts']=[];
        $pdo->prepare("UPDATE users SET last_login_at=NOW() WHERE id=?")->execute([$user['id']]);
        return true;
    }
    public static function requireUser(): array {
        $user=self::user();
        if(!$user) { header('Location: login.php'); exit; }
        return $user;
    }
    public static function logout(): void { unset($_SESSION['admin_user']); session_regenerate_id(true); }
}
