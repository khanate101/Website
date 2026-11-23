<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/bootstrap.php';

$categories=[];
try{
 $pdo=db();
 $categories=$pdo->query("SELECT c.id,c.name,c.slug,c.description,c.image_path,COUNT(p.id) product_count
  FROM categories c
  LEFT JOIN products p ON p.category_id=c.id AND p.status='active' AND p.compliance_status='approved'
  WHERE c.is_active=1 AND c.compliance_status='approved'
  GROUP BY c.id,c.name,c.slug,c.description,c.image_path
  ORDER BY c.sort_order,c.name")->fetchAll();
}catch(Throwable $e){error_log('Categories page error: '.$e->getMessage());}
?><!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#080808"><title>أقسام المتجر | معسلات VIP 27</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/store.css">
</head>
<body>
<header class="site-header"><div class="container nav-wrap">
<a class="brand" href="./"><span class="brand-mark">27</span><span><strong>معسلات VIP 27</strong><small>VIP 27</small></span></a>
<nav class="main-nav" style="display:flex"><a href="./">الرئيسية</a><a class="active" href="categories.php">الأقسام</a><a href="./#products">المنتجات</a></nav>
<a class="btn btn-outline" href="cart.php">السلة</a>
</div></header>
<main class="section"><div class="container">
<div class="section-heading"><div><span class="eyebrow">تسوق حسب اهتمامك</span><h1>جميع أقسام المتجر</h1><p class="muted">اختر القسم الذي تفضله لاستعراض منتجاته.</p></div><a class="section-more" href="./#products">عرض كل المنتجات ←</a></div>
<?php if($categories):?>
<div class="categories-page-grid">
<?php foreach($categories as $category):?>
<a class="category-card" href="./?category=<?=rawurlencode($category['slug'])?>#products">
<?php if(!empty($category['image_path'])):?><img class="category-card-image" loading="lazy" src="<?=e($category['image_path'])?>" alt="<?=e($category['name'])?>"><?php else:?><span class="category-all-icon">✦</span><?php endif;?>
<span class="category-card-caption"><strong><?=e($category['name'])?></strong><small><?= (int)$category['product_count'] ?> منتج · استكشف القسم</small></span>
</a>
<?php endforeach;?>
</div>
<?php else:?>
<div class="empty-state"><span>✧</span><h3>لا توجد أقسام منشورة حالياً</h3><p>ستظهر الأقسام هنا بعد إضافتها وتفعيلها واعتمادها من لوحة التحكم.</p><a class="btn btn-outline" href="./">العودة للرئيسية</a></div>
<?php endif;?>
</div></main>
<footer class="footer"><div class="container footer-inner"><a class="brand" href="./"><span class="brand-mark">27</span><span><strong>معسلات VIP 27</strong><small>VIP 27</small></span></a><small>© <?=date('Y')?> معسلات VIP 27. جميع الحقوق محفوظة.</small></div></footer>
</body></html>
