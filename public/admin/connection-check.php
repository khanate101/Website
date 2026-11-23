<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/bootstrap.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Robots-Tag: noindex, nofollow');

$expected = (string)(getenv('ADMIN_SETUP_TOKEN') ?: '');
if (strlen($expected) < 32) {
    http_response_code(503);
    exit('ADMIN_SETUP_TOKEN is missing or too short in the project-root .env file.');
}
if (empty($_SESSION['db_check_csrf'])) {
    $_SESSION['db_check_csrf'] = bin2hex(random_bytes(32));
}

$report = [];
$authorized = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = (string)($_POST['_csrf'] ?? '');
    $token = (string)($_POST['token'] ?? '');
    if (!hash_equals((string)$_SESSION['db_check_csrf'], $csrf)) {
        $report[] = ['FAIL', 'Security token expired. Reload this page.'];
    } elseif (!hash_equals($expected, $token)) {
        $report[] = ['FAIL', 'Setup token does not match the value in .env.'];
    } else {
        $authorized = true;
        $report[] = ['OK', 'Project bootstrap loaded.'];
        $required = ['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS'];
        foreach ($required as $key) {
            $value = getenv($key);
            $report[] = [$value !== false && $value !== '' ? 'OK' : 'FAIL', $key . ($key === 'DB_PASS' ? ' is set (value hidden).' : ' = ' . ($value === false || $value === '' ? 'MISSING' : $value))];
        }
        try {
            $pdo = db();
            $report[] = ['OK', 'PDO connected to MySQL successfully.'];
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            $report[] = ['OK', 'Database returned ' . count($tables) . ' tables.'];
            foreach (['roles', 'users', 'permissions', 'role_permissions'] as $table) {
                $report[] = [in_array($table, $tables, true) ? 'OK' : 'FAIL', 'Table ' . $table . (in_array($table, $tables, true) ? ' exists.' : ' is missing.')];
            }
            if (in_array('roles', $tables, true)) {
                $role = $pdo->query("SELECT id FROM roles WHERE name='super_admin' LIMIT 1")->fetchColumn();
                $report[] = [$role ? 'OK' : 'FAIL', $role ? 'super_admin role exists.' : 'super_admin role is missing.'];
            }
            if (in_array('users', $tables, true)) {
                $columns = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
                foreach (['role_id', 'name', 'email', 'password_hash', 'is_active'] as $column) {
                    $report[] = [in_array($column, $columns, true) ? 'OK' : 'FAIL', 'users.' . $column . (in_array($column, $columns, true) ? ' exists.' : ' is missing.')];
                }
            }
        } catch (Throwable $e) {
            error_log('Protected DB diagnostic: ' . get_class($e) . ' ' . $e->getMessage());
            $detail = get_class($e) . ': ' . $e->getMessage();
            if ($e instanceof PDOException && isset($e->errorInfo[0])) {
                $detail .= ' | SQLSTATE=' . $e->errorInfo[0];
                if (isset($e->errorInfo[1])) $detail .= ' | DRIVER_CODE=' . $e->errorInfo[1];
            }
            $report[] = ['FAIL', 'Database check failed: ' . $detail];
        }
        unset($_SESSION['db_check_csrf']);
    }
}
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Protected DB diagnostic</title>
<style>body{font:15px system-ui;background:#101010;color:#eee;padding:24px}.panel{max-width:760px;margin:auto;background:#1b1b1b;padding:24px;border-radius:14px}input,button{font:inherit;padding:12px;margin:8px 0;border-radius:8px}input{width:95%;background:#090909;color:white;border:1px solid #555}button{background:#d6a847;border:0;font-weight:bold}.ok{color:#8fe0a2}.fail{color:#ff9388}li{margin:9px 0;overflow-wrap:anywhere}</style></head>
<body><main class="panel"><h2>Protected database diagnostic</h2><p>Enter the ADMIN_SETUP_TOKEN from the project-root .env file. Password values are never displayed.</p>
<form method="post"><input type="hidden" name="_csrf" value="<?=e($_SESSION['db_check_csrf'])?>"><input type="password" name="token" placeholder="Setup token" required autocomplete="off"><button type="submit">Run checks</button></form>
<?php if ($report): ?><ol><?php foreach ($report as [$status,$message]): ?><li class="<?= $status === 'OK' ? 'ok' : 'fail' ?>"><strong><?=e($status)?></strong> — <?=e($message)?></li><?php endforeach; ?></ol><?php endif; ?>
<p>Delete this file from the server and GitHub after diagnosis.</p></main></body></html>
