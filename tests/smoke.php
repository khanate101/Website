<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/bootstrap.php';
require dirname(__DIR__).'/vendor/autoload.php';
use App\Admin;
use App\Security;
$checks=0;
if(Admin::slug('Al Fakhamah 2026')!=='al-fakhamah-2026')throw new RuntimeException('Slug helper test failed.');$checks++;
$token=Security::csrfToken();if(strlen($token)!==64||!Security::verifyCsrf($token)||Security::verifyCsrf(str_repeat('0',64)))throw new RuntimeException('CSRF helper test failed.');$checks++;
if(e('<script>')!=='&lt;script&gt;')throw new RuntimeException('HTML escaping test failed.');$checks++;
fwrite(STDOUT,"Smoke tests passed: {$checks}\n");
