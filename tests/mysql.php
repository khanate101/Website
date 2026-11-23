<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

$pdo = db();
$checks = 0;
$assert = static function (bool $condition, string $message) use (&$checks): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
    $checks++;
};

$requiredTables = [
    'roles', 'permissions', 'role_permissions', 'users', 'categories',
    'products', 'product_images', 'customers', 'carts', 'cart_items',
    'orders', 'order_items', 'order_status_history', 'inventory_movements',
    'settings', 'banners', 'user_permissions', 'audit_logs',
];
$found = $pdo->query(
    "SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE()"
)->fetchAll(PDO::FETCH_COLUMN);
foreach ($requiredTables as $table) {
    $assert(in_array($table, $found, true), "Missing table: {$table}");
}

$roles = $pdo->query("SELECT name FROM roles")->fetchAll(PDO::FETCH_COLUMN);
foreach (['super_admin', 'manager', 'staff'] as $role) {
    $assert(in_array($role, $roles, true), "Missing role: {$role}");
}
$permissionCount = (int) $pdo->query("SELECT COUNT(*) FROM permissions")->fetchColumn();
$assert($permissionCount >= 10, 'Permission seed data is incomplete.');

$pdo->beginTransaction();
try {
    $suffix = bin2hex(random_bytes(6));

    $category = $pdo->prepare(
        "INSERT INTO categories(name, slug, is_active, compliance_status) VALUES(?, ?, 1, 'approved')"
    );
    $category->execute(['CI Category', 'ci-' . $suffix]);
    $categoryId = (int) $pdo->lastInsertId();
    $assert($categoryId > 0, 'Category insert failed.');

    $product = $pdo->prepare(
        "INSERT INTO products(category_id, name, slug, sku, price, stock_quantity, status, compliance_status)
         VALUES(?, ?, ?, ?, 10.50, 8, 'active', 'approved')"
    );
    $product->execute([$categoryId, 'CI Product', 'ci-product-' . $suffix, 'CI-' . strtoupper($suffix)]);
    $productId = (int) $pdo->lastInsertId();
    $assert($productId > 0, 'Product insert failed.');

    $lock = $pdo->prepare("SELECT stock_quantity, reserved_quantity FROM products WHERE id = ? FOR UPDATE");
    $lock->execute([$productId]);
    $stock = $lock->fetch();
    $assert($stock !== false && (int) $stock['stock_quantity'] - (int) $stock['reserved_quantity'] === 8, 'Product row lock/read failed.');

    $customer = $pdo->prepare("INSERT INTO customers(full_name, phone) VALUES(?, ?)");
    $customer->execute(['CI Customer', '+966500000000']);
    $customerId = (int) $pdo->lastInsertId();

    $key = hash('sha256', 'ci-' . $suffix);
    $order = $pdo->prepare(
        "INSERT INTO orders(order_number, customer_id, fulfillment_method, status, subtotal, delivery_fee, total, idempotency_key)
         VALUES(?, ?, 'pickup', 'new', 21.00, 0, 21.00, ?)"
    );
    $order->execute(['CI-' . strtoupper($suffix), $customerId, $key]);
    $orderId = (int) $pdo->lastInsertId();
    $assert($orderId > 0, 'Order insert failed.');

    $item = $pdo->prepare(
        "INSERT INTO order_items(order_id, product_id, product_name_snapshot, sku_snapshot, unit_price, quantity, line_total)
         VALUES(?, ?, 'CI Product snapshot', ?, 10.50, 2, 21.00)"
    );
    $item->execute([$orderId, $productId, 'CI-' . strtoupper($suffix)]);
    $history = $pdo->prepare(
        "INSERT INTO order_status_history(order_id, old_status, new_status, note) VALUES(?, NULL, 'new', 'CI test')"
    );
    $history->execute([$orderId]);
    $movement = $pdo->prepare(
        "INSERT INTO inventory_movements(product_id, order_id, movement_type, quantity, note)
         VALUES(?, ?, 'reserve', 2, 'CI test')"
    );
    $movement->execute([$productId, $orderId]);

    $assert((int) $pdo->query("SELECT COUNT(*) FROM order_items WHERE order_id = {$orderId}")->fetchColumn() === 1, 'Order item relationship failed.');
    $assert((int) $pdo->query("SELECT COUNT(*) FROM order_status_history WHERE order_id = {$orderId}")->fetchColumn() === 1, 'Order history relationship failed.');
    $assert((int) $pdo->query("SELECT COUNT(*) FROM inventory_movements WHERE order_id = {$orderId}")->fetchColumn() === 1, 'Inventory movement relationship failed.');

    $duplicateRejected = false;
    try {
        $order->execute(['CI-DUP-' . strtoupper($suffix), $customerId, $key]);
    } catch (PDOException) {
        $duplicateRejected = true;
    }
    $assert($duplicateRejected, 'Duplicate idempotency key was not rejected by the database.');
} finally {
    $pdo->rollBack();
}

fwrite(STDOUT, "MySQL integration checks passed: {$checks}\n");
