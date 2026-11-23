<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';

$categories = $products = [];
$dbReady = true;
try {
    $pdo = db();
    $categories = $pdo->query("SELECT id,name,slug,image_path FROM categories WHERE is_active=1 AND compliance_status='approved' ORDER BY sort_order,name LIMIT 12")->fetchAll();
    $products = $pdo->query("SELECT p.id,p.name,p.slug,p.price,(p.stock_quantity-p.reserved_quantity) AS available_quantity,p.short_description,
        (SELECT pi.image_path FROM product_images pi WHERE pi.product_id=p.id ORDER BY pi.is_primary DESC,pi.sort_order ASC LIMIT 1) AS image_path
        FROM products p JOIN categories c ON c.id=p.category_id
        WHERE p.status='active' AND p.compliance_status='approved' AND c.is_active=1 AND c.compliance_status='approved'
        ORDER BY p.is_featured DESC,p.created_at DESC LIMIT 8")->fetchAll();
} catch (Throwable $exception) {
    $dbReady = false;
    error_log('Storefront database error: ' . $exception->getMessage());
}
$storeName = 'الفخامة';
try { if ($dbReady) { $s = $pdo->query("SELECT setting_value FROM settings WHERE setting_key='store_name'")->fetchColumn(); if ($s) $storeName = $s; } } catch (Throwable) {}
$cartCount = array_sum(array_map('intval', $_SESSION['cart'] ?? []));
$csrf = \\App\\Security::csrfToken();
?><!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#080808">
<title><?= e($storeName) ?> | متجر الفخامة</title>
<meta name="description" content="اكتشف تجربة تسوق أنيقة في متجر الفخامة.">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/store.css">
</head>
<body>
<div class="topbar"><div class="container topbar-inner"><span>✦ متجر أنيق وتجربة موثوقة</span><span>التوصيل حسب نطاق الخدمة المعتمد</span><a href="tel:" aria-label="اتصل بنا">خدمة العملاء</a></div></div>
<header class="site-header"><div class="container nav-wrap">
<a class="brand" href="./" aria-label="الرئيسية"><span class="brand-mark">ف</span><span><strong>الفخامة</strong><small>AL FAKHAMAH</small></span></a>
<button class="menu-toggle" type="button" aria-expanded="false" aria-controls="main-nav">القائمة</button>
<nav id="main-nav" class="main-nav" aria-label="القائمة الرئيسية"><a class="active" href="./">الرئيسية</a><a href="#categories">الأقسام</a><a href="#products">المنتجات</a><a href="#about">من نحن</a></nav>
<div class="nav-actions"><a href="cart.php" class="cart-link" aria-label="السلة">السلة <b><?= $cartCount ?></b></a></div>
</div></header>
<main>
<section class="hero"><div class="hero-glow"></div><div class="container hero-content"><div class="hero-copy"><span class="eyebrow">مرحباً بكم في</span><h1>الفخامة <em>تبدأ من التفاصيل</em></h1><p>اكتشف تجربة تسوق راقية، بتفاصيل مختارة بعناية وخدمة تليق بك.</p><div class="hero-actions"><a class="btn btn-gold" href="#products">اكتشف المنتجات <span>←</span></a><a class="text-link" href="#categories">تصفح الأقسام</a></div><div class="trust-row"><span>✧ جودة مختارة</span><span>◇ تجربة سهلة</span><span>◈ خدمة موثوقة</span></div></div><div class="hero-art" aria-hidden="true"><div class="gold-orbit"></div><div class="hero-emblem">ف</div><span class="hero-caption">AL FAKHAMAH</span></div></div></section>
<?php if (!$dbReady): ?><div class="container"><div class="notice">المتجر قيد الإعداد. يرجى إعداد قاعدة البيانات واستيراد ملف database/schema.sql لعرض المحتوى.</div></div><?php endif; ?>
<section class="section categories-section" id="categories"><div class="container"><div class="section-heading"><div><span class="eyebrow">تسوق حسب اهتمامك</span><h2>أقسام المتجر</h2></div><span class="heading-line"></span></div>
<?php if ($categories): ?><div class="category-grid"><?php foreach ($categories as $category): ?><a class="category-card" href="category.php?slug=<?= rawurlencode($category['slug']) ?>"><span class="category-icon">✧</span><strong><?= e($category['name']) ?></strong><small>استكشف المجموعة</small></a><?php endforeach; ?></div><?php else: ?><div class="empty-state"><span>✧</span><h3>نجهز لك تجربة مميزة</h3><p>ستظهر الأقسام المعتمدة هنا فور إضافتها من لوحة الإدارة.</p></div><?php endif; ?>
</div></section>
<section class="section products-section" id="products"><div class="container"><div class="section-heading"><div><span class="eyebrow">اختياراتنا لك</span><h2>أحدث المنتجات</h2></div><span class="heading-line"></span></div>
<?php if ($products): ?><div class="product-grid"><?php foreach ($products as $product): ?><article class="product-card"><a class="product-image" href="product.php?slug=<?= rawurlencode($product['slug']) ?>"><?php if ($product['image_path']): ?><img loading="lazy" src="<?= e($product['image_path']) ?>" alt="<?= e($product['name']) ?>"><?php else: ?><span class="image-placeholder">الفخامة</span><?php endif; ?></a><div class="product-info"><h3><?= e($product['name']) ?></h3><p><?= e($product['short_description']) ?></p><div class="product-bottom"><strong><?= number_format((float)$product['price'],2) ?> <small>ر.س</small></strong><span class="<?= (int)$product['available_quantity'] > 0 ? 'available' : 'unavailable' ?>"><?= (int)$product['available_quantity'] > 0 ? 'متوفر' : 'غير متوفر' ?></span></div><div style="display:flex;gap:8px"><a class="btn btn-outline" href="product.php?slug=<?= rawurlencode($product['slug']) ?>">التفاصيل</a><?php if((int)$product['available_quantity']>0): ?><form method="post" action="add-to-cart.php"><input type="hidden" name="_csrf" value="<?=e($csrf)?>"><input type="hidden" name="product_id" value="<?=(int)$product['id']?>"><input type="hidden" name="return_to" value="./"><button class="btn btn-gold">أضف للسلة</button></form><?php endif; ?></div></div></article><?php endforeach; ?></div><?php else: ?><div class="empty-state"><span>✧</span><h3>منتجات مختارة بعناية</h3><p>ستظهر المنتجات المتاحة والمعتمدة هنا بعد إضافتها من لوحة التحكم.</p></div><?php endif; ?>
</div></section>
<section class="about-strip" id="about"><div class="container about-inner"><span class="about-symbol">ف</span><div><span class="eyebrow">الفخامة في كل تفصيل</span><h2>تجربة تسوق تليق بذوقك</h2><p>نعمل على تقديم تجربة واضحة وآمنة، من تصفح المنتجات حتى متابعة الطلب.</p></div></div></section>
</main>
<footer class="footer"><div class="container footer-inner"><a class="brand" href="./"><span class="brand-mark">ف</span><span><strong>الفخامة</strong><small>AL FAKHAMAH</small></span></a><p>الفخامة تبدأ من التفاصيل.</p><small>© <?= date('Y') ?> الفخامة. جميع الحقوق محفوظة.</small></div></footer>
<script src="assets/js/store.js" defer></script>
</body></html>