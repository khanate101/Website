<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/bootstrap.php';
require dirname(__DIR__, 2) . '/vendor/autoload.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

try {
    $pdo = db();
    $adminCount = (int)$pdo->query("SELECT COUNT(*) FROM users u JOIN roles r ON r.id=u.role_id WHERE r.name='super_admin'")->fetchColumn();
    if ($adminCount > 0) {
        http_response_code(404);
        exit('Not found.');
    }
} catch (Throwable $e) {
    error_log('First admin setup error: ' . $e->getMessage());
    http_response_code(503);
    exit('تعذر الاتصال بقاعدة البيانات. تحقق من إعدادات الموقع.');
}

$setupToken = (string)(getenv('ADMIN_SETUP_TOKEN') ?: '');
if (strlen($setupToken) < 32) {
    http_response_code(503);
    exit('إعداد إنشاء المدير غير مكتمل. أضف ADMIN_SETUP_TOKEN إلى ملف .env.');
}

if (empty($_SESSION['first_admin_csrf'])) {
    $_SESSION['first_admin_csrf'] = bin2hex(random_bytes(32));
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = (string)($_POST['_csrf'] ?? '');
    $providedToken = (string)($_POST['setup_token'] ?? '');
    $name = trim((string)($_POST['name'] ?? ''));
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');

    if (!hash_equals((string)$_SESSION['first_admin_csrf'], $csrf)) {
        $error = 'انتهت صلاحية النموذج. أعد تحميل الصفحة وحاول مجدداً.';
    } elseif (!hash_equals($setupToken, $providedToken)) {
        $error = 'رمز الإعداد غير صحيح.';
    } elseif ($name === '' || mb_strlen($name) > 160) {
        $error = 'يرجى إدخال اسم صحيح.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
        $error = 'يرجى إدخال بريد إلكتروني صحيح.';
    } elseif (strlen($password) < 12) {
        $error = 'يجب أن تكون كلمة المرور 12 حرفاً على الأقل.';
    } else {
        try {
            $pdo->beginTransaction();
            $count = (int)$pdo->query("SELECT COUNT(*) FROM users u JOIN roles r ON r.id=u.role_id WHERE r.name='super_admin' FOR UPDATE")->fetchColumn();
            if ($count > 0) {
                $pdo->rollBack();
                http_response_code(404);
                exit('Not found.');
            }
            $roleId = $pdo->query("SELECT id FROM roles WHERE name='super_admin' LIMIT 1")->fetchColumn();
            if (!$roleId) {
                throw new RuntimeException('Missing super_admin role.');
            }
            $stmt = $pdo->prepare('INSERT INTO users (role_id, name, email, password_hash) VALUES (?, ?, ?, ?)');
            $stmt->execute([(int)$roleId, $name, $email, password_hash($password, PASSWORD_DEFAULT)]);
            $pdo->commit();
            unset($_SESSION['first_admin_csrf']);
            $success = true;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('First admin creation failed: ' . $e->getMessage());
            $error = 'تعذر إنشاء الحساب. تحقق من أن البريد الإلكتروني غير مستخدم وأن جداول قاعدة البيانات جاهزة.';
        }
    }
}
?><!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>إنشاء المدير الأول | الفخامة</title>
<style>
*{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;background:#090909;color:#eee;font-family:Tahoma,Arial,sans-serif;padding:20px}.panel{width:min(100%,460px);background:#151515;border:1px solid #76501c;border-radius:18px;padding:26px;box-shadow:0 18px 60px #000}h1{color:#f4c45e;font-size:23px}p{color:#bbb;line-height:1.8;font-size:14px}label{display:block;margin:15px 0 7px}input{width:100%;padding:12px;border-radius:9px;border:1px solid #574321;background:#080808;color:#fff;font-size:16px}button,a.btn{display:block;width:100%;margin-top:20px;padding:13px;border:0;border-radius:9px;background:#d6a847;color:#17110a;font-weight:bold;text-align:center;text-decoration:none;font-size:15px}.error{background:#391b18;color:#ffb8ad;padding:11px;border-radius:8px}.ok{background:#183522;color:#b8f0c7;padding:12px;border-radius:8px}
</style>
</head>
<body><main class="panel">
<h1>إنشاء حساب المدير الرئيسي</h1>
<p>هذه صفحة إعداد مؤقتة لإنشاء أول مدير للمتجر. بعد إنشاء الحساب ستُغلق هذه الصفحة تلقائياً أمام أي محاولة أخرى.</p>
<?php if ($success): ?>
<div class="ok">تم إنشاء حساب المدير الرئيسي بنجاح. احذف ملف create-first-admin.php من المستودع والاستضافة الآن، ثم سجّل الدخول.</div>
<a class="btn" href="login.php">الانتقال إلى تسجيل الدخول</a>
<?php else: ?>
<?php if ($error): ?><div class="error"><?=e($error)?></div><?php endif; ?>
<form method="post" autocomplete="off">
<input type="hidden" name="_csrf" value="<?=e($_SESSION['first_admin_csrf'])?>">
<label for="token">رمز الإعداد</label><input id="token" name="setup_token" type="password" required autocomplete="off">
<label for="name">اسم المدير</label><input id="name" name="name" maxlength="160" required>
<label for="email">البريد الإلكتروني</label><input id="email" name="email" type="email" maxlength="190" required autocomplete="username">
<label for="password">كلمة المرور (12 حرفاً على الأقل)</label><input id="password" name="password" type="password" minlength="12" required autocomplete="new-password">
<button type="submit">إنشاء حساب المدير</button>
</form>
<?php endif; ?>
</main></body></html>
