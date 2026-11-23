<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/app/bootstrap.php';
$email=trim((string)($argv[1]??''));$name=trim((string)($argv[2]??'مدير المتجر'));
if(!filter_var($email,FILTER_VALIDATE_EMAIL)){fwrite(STDERR,"Usage: php bin/create-admin.php admin@example.com 'Admin Name'\n");exit(1);}
fwrite(STDOUT,"New admin password: ");$password=trim((string)fgets(STDIN));
if(strlen($password)<12){fwrite(STDERR,"Password must be at least 12 characters.\n");exit(1);}
$pdo=db();$roleId=$pdo->query("SELECT id FROM roles WHERE name='super_admin'")->fetchColumn();
if(!$roleId){fwrite(STDERR,"Import database/schema.sql first.\n");exit(1);}
$stmt=$pdo->prepare("INSERT INTO users(role_id,name,email,password_hash) VALUES(?,?,?,?)");
try{$stmt->execute([$roleId,$name,strtolower($email),password_hash($password,PASSWORD_DEFAULT)]);fwrite(STDOUT,"Admin account created.\n");}catch(PDOException $e){fwrite(STDERR,"Could not create account: ".$e->getMessage()."\n");exit(1);}
