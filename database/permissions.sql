USE alfakhamah;
INSERT IGNORE INTO permissions(permission_key,label) VALUES
('categories.view','عرض الأقسام'),('categories.manage','إدارة الأقسام'),
('products.view','عرض المنتجات'),('products.manage','إدارة المنتجات'),
('orders.view','عرض الطلبات'),('orders.manage','إدارة الطلبات'),
('inventory.view','عرض المخزون'),('inventory.manage','إدارة المخزون'),
('reports.view','عرض التقارير'),('users.manage','إدارة المستخدمين'),('settings.manage','إدارة الإعدادات');
INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r CROSS JOIN permissions p WHERE r.name IN ('super_admin','manager');
