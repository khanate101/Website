<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/app/bootstrap.php'; require dirname(__DIR__,2).'/vendor/autoload.php';
use App\Auth; use App\Security;
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);exit;}
Security::requireCsrf(); Auth::logout(); header('Location: login.php'); exit;
