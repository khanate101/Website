<?php
declare(strict_types=1);
namespace App;
use PDO;
final class Admin {
 public static function requirePermission(string $key): array {
  $user=Auth::requireUser(); if(($user['role_name']??'')==='super_admin') return $user;
  $q=db()->prepare("SELECT p.id FROM permissions p JOIN role_permissions rp ON rp.permission_id=p.id LEFT JOIN user_permissions up ON up.permission_id=p.id AND up.user_id=? WHERE p.permission_key=? AND (up.allowed IS NULL OR up.allowed=1) AND rp.role_id=? LIMIT 1");
  $q->execute([(int)$user['id'],$key,(int)$user['role_id']]); if(!$q->fetchColumn()){http_response_code(403);exit('ليس لديك صلاحية لتنفيذ هذا الإجراء.');} return $user;
 }
 public static function audit(string $action,string $type='',?int $id=null,array $details=[]): void {
  $user=Auth::user();$q=db()->prepare("INSERT INTO audit_logs(user_id,action,entity_type,entity_id,ip_address,details) VALUES(?,?,?,?,?,?)");
  $q->execute([$user['id']??null,$action,$type?:null,$id,$_SERVER['REMOTE_ADDR']??null,json_encode($details,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]);
 }
 public static function slug(string $value): string {
  $value=trim(mb_strtolower($value));$value=preg_replace('/[^\p{L}\p{N}]+/u','-',$value)??'';return trim($value,'-')?:bin2hex(random_bytes(5));
 }
 public static function uploadImage(array $file,string $folder='products'): string {
  if(($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK) throw new \RuntimeException('يرجى اختيار صورة صالحة.');
  if(($file['size']??0)>5*1024*1024) throw new \RuntimeException('حجم الصورة يجب ألا يتجاوز 5 ميجابايت.');
  $tmp=$file['tmp_name']??'';$mime=(new \finfo(FILEINFO_MIME_TYPE))->file($tmp);
  $allowed=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
  if(!isset($allowed[$mime])||!@getimagesize($tmp)) throw new \RuntimeException('نوع الصورة غير مدعوم. استخدم JPG أو PNG أو WebP.');
  $dir=dirname(__DIR__).'/public/uploads/'.$folder;if(!is_dir($dir)&&!mkdir($dir,0755,true)&&!is_dir($dir))throw new \RuntimeException('تعذر إنشاء مجلد الصور.');
  $name=bin2hex(random_bytes(16)).'.'.$allowed[$mime];if(!move_uploaded_file($tmp,$dir.'/'.$name))throw new \RuntimeException('تعذر حفظ الصورة.');
  return 'uploads/'.$folder.'/'.$name;
 }
}
