<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/app/bootstrap.php';
require dirname(__DIR__,2).'/vendor/autoload.php';
use App\Auth; use App\Security;
if(Auth::user()){header('Location: index.php');exit;}
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 Security::requireCsrf();
 try { if(Auth::attempt(db(),(string)($_POST['email']??''),(string)($_POST['password']??''))){header('Location: index.php');exit;} $error='بيانات الدخول غير صحيحة أو تم تجاوز عدد المحاولات.'; }
 catch(Throwable $e){error_log($e->getMessage());$error='تعذر تسجيل الدخول حالياً. تحقق من إعداد قاعدة البيانات.';}
}
?><!doctype html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>دخول الإدارة | الفخامة</title><link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet"><link rel="stylesheet" href="../assets/css/store.css"><style>body{min-height:100vh;display:grid;place-items:center;background:radial-gradient(circle at 50% 20%,#30200c,#080808 55%)}.login{width:min(430px,calc(100% - 32px));padding:34px;border:1px solid #76501c;border-radius:22px;background:#111;box-shadow:0 20px 80px #000}.login h1{color:#f4c45e;text-align:center}.login label{display:block;margin:14px 0 6px}.login input{width:100%;padding:13px;border-radius:10px;border:1px solid #49371c;background:#080808;color:white;font:inherit}.login button{width:100%;margin-top:22px}.error{color:#ffaaa0;background:#321713;padding:10px;border-radius:8px;font-size:13px}</style></head><body><form class="login" method="post"><div class="brand" style="justify-content:center"><span class="brand-mark">ف</span><span><strong>الفخامة</strong><small>ADMINISTRATION</small></span></div><h1>تسجيل دخول الإدارة</h1><?php if($error):?><p class="error"><?=e($error)?></p><?php endif;?><input type="hidden" name="_csrf" value="<?=e(Security::csrfToken())?>"><label for="email">البريد الإلكتروني</label><input id="email" type="email" name="email" autocomplete="username" required><label for="password">كلمة المرور</label><input id="password" type="password" name="password" autocomplete="current-password" required><button class="btn btn-gold" type="submit">تسجيل الدخول</button></form></body></html>