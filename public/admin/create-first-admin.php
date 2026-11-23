<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/bootstrap.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Robots-Tag: noindex, nofollow');

if (empty($_SESSION['first_admin_csrf'])) {
    $_SESSION['first_admin_csrf'] = bin2hex(random_bytes(32));
}

$error = '';
$success = false;
$pdo = null;

try {
    $pdo = db();
    $check = $pdo->prepare("SELECT COUNT(*) FROM users u JOIN roles r ON r.id = u.role_id WHERE r.name = 'super_admin'");
    $check->execute();
    if ((int)$check->fetchColumn() > 0) {
        http_response_code(404);
        exit('هذه الصفحة لم تعد متاحة.');
    }
} catch (Throwable $e) {
    error_log('First admin page database check: ' . get_class($e) . ' ' . $e->getMessage());
    $safe = get_class($e);
    if ($e instanceof PDOException && isset($e->errorInfo[0])) {
        $safe .= ' | SQLSTATE=' . $e->errorInfo[0];
        if (isset($e->errorInfo[1])) $safe .= ' | DRIVER_CODE=' . (int)$e->errorInfo[1];
    }
    http_response_code(503);
    exit('تعذر الاتصال بقاعدة البيانات. رمز التشخيص: ' . htmlspecialchars($safe, ENT_QUOTES, 'UTF-8') . '. أرسل لي هذا الرمز فقط.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = (string)($_POST['_csrf'] ?? '');
    $name = trim((string)($_POST['name'] ?? ''));
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');

    if (!hash_equals((string)$_SESSION['first_admin_csrf'], $csrf)) {
        $error = 'انتهت صلاحية النموذج. أعد تحميل الصفحة.';
    } elseif ($name === '' || mb_strlen($name) > 160) {
        $error = 'أدخل اسم المدير بشكل صحيح.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
        $error = 'أدخل بريداً إلكترونياً صحيحاً.';
    } elseif (strlen($password) < 12) {
        $error = 'كلمة المرور يجب ألا تقل عن 12 حرفاً.';
    } elseif (!hash_equals($password, $confirm)) {
        $error = 'كلمتا المرور غير متطابقتين.';
    } else {
        try {
            $pdo->beginTransaction();

            // Lock the super-admin role row to serialize simultaneous first-admin requests.
            $roleStmt = $pdo->prepare("SELECT id FROM roles WHERE name = 'super_admin' LIMIT 1 FOR UPDATE");
            $roleStmt->execute();
            $roleId = $roleStmt->fetchColumn();
            if (!$roleId) {
                throw new RuntimeException('The super_admin role is missing.');
            }

            $check = $pdo->prepare("SELECT COUNT(*) FROM users u JOIN roles r ON r.id = u.role_id WHERE r.name = 'super_admin'");
            $check->execute();
            if ((int)$check->fetchColumn() > 0) {
                $pdo->rollBack();
                http_response_code(404);
                exit('تم إنشاء المدير الرئيسي مسبقاً، والصفحة لم تعد متاحة.');
            }

            $insert = $pdo->prepare('INSERT INTO users (role_id, name, email, password_hash) VALUES (?, ?, ?, ?)');
            $insert->execute([(int)$roleId, $name, $email, password_hash($password, PASSWORD_DEFAULT)]);
            $pdo->commit();

            unset($_SESSION['first_admin_csrf']);
            $success = true;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('First admin creation failed: ' . $e->getMessage());
            $error = 'فشل إنشاء الحساب. قد يكون البريد مستخدماً أو توجد مشكلة في جداول قاعدة البيانات.';
        }
    }
}
?><!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>إنشاء المدير الأول | الفخامة</title>
<style>
*{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;background:radial-gradient(circle at top,#30200c,#090909 58%);color:#eee;font-family:Tahoma,Arial,sans-serif;padding:20px}.panel{width:min(100%,460px);background:#151515;border:1px solid #76501c;border-radius:18px;padding:26px;box-shadow:0 18px 60px #000}h1{color:#f4c45e;font-size:23px;text-align:center}p{color:#bbb;line-height:1.8;font-size:14px}label{display:block;margin:15px 0 7px}input{width:100%;padding:12px;border-radius:9px;border:1px solid #574321;background:#080808;color:#fff;font-size:16px}button,a.btn{display:block;width:100%;margin-top:20px;padding:13px;border:0;border-radius:9px;background:#d6a847;color:#17110a;font-weight:bold;text-align:center;text-decoration:none;font-size:15px}.error{background:#391b18;color:#ffb8ad;padding:11px;border-radius:8px}.ok{background:#183522;color:#b8f0c7;padding:12px;border-radius:8px}
</style>
</head>
<body><main class="panel">
<h1>إنشاء حساب المدير الرئيسي</h1>
<p>أنشئ بيانات المدير الأول للموقع. هذه الصفحة تعمل فقط قبل إنشاء أي مدير رئيسي.</p>
<?php if ($success): ?>
<div class="ok">تم إنشاء حساب المدير الرئيسي بنجاح.</div>
<p>احذف ملف create-first-admin.php من الاستضافة وGitHub الآن، ثم سجّل الدخول بحسابك.</p>
<a class="btn" href="login.php">الانتقال إلى تسجيل الدخول</a>
<?php else: ?>
<?php if ($error): ?><div class="error"><?=e($error)?></div><?php endif; ?>
<form method="post" autocomplete="off">
<input type="hidden" name="_csrf" value="<?=e($_SESSION['first_admin_csrf'])?>">
<label for="name">اسم المدير</label><input id="name" name="name" maxlength="160" required>
<label for="email">البريد الإلكتروني</label><input id="email" name="email" type="email" maxlength="190" required autocomplete="username">
<label for="password">كلمة المرور (12 حرفاً على الأقل)</label><input id="password" name="password" type="password" minlength="12" required autocomplete="new-password">
<label for="confirm_password">تأكيد كلمة المرور</label><input id="confirm_password" name="confirm_password" type="password" minlength="12" required autocomplete="new-password">
<button type="submit">حفظ وإنشاء حساب المدير</button>
</form>
<?php endif; ?>
</main></body></html>
