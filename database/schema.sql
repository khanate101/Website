
CREATE TABLE roles (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(80) NOT NULL UNIQUE,
 label VARCHAR(120) NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE permissions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 permission_key VARCHAR(120) NOT NULL UNIQUE,
 label VARCHAR(160) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE role_permissions (
 role_id BIGINT UNSIGNED NOT NULL,
 permission_id BIGINT UNSIGNED NOT NULL,
 PRIMARY KEY(role_id,permission_id),
 FOREIGN KEY(role_id) REFERENCES roles(id) ON DELETE CASCADE,
 FOREIGN KEY(permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE users (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 role_id BIGINT UNSIGNED NOT NULL,
 name VARCHAR(160) NOT NULL,
 email VARCHAR(190) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 last_login_at DATETIME NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY(role_id) REFERENCES roles(id)
) ENGINE=InnoDB;

CREATE TABLE categories (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 parent_id BIGINT UNSIGNED NULL,
 name VARCHAR(160) NOT NULL,
 slug VARCHAR(190) NOT NULL UNIQUE,
 description TEXT NULL,
 image_path VARCHAR(500) NULL,
 is_active TINYINT(1) NOT NULL DEFAULT 0,
 compliance_status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
 sort_order INT NOT NULL DEFAULT 0,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY(parent_id) REFERENCES categories(id) ON DELETE SET NULL,
 INDEX idx_categories_visible(is_active,compliance_status,sort_order)
) ENGINE=InnoDB;

CREATE TABLE products (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 category_id BIGINT UNSIGNED NULL,
 name VARCHAR(220) NOT NULL,
 slug VARCHAR(240) NOT NULL UNIQUE,
 sku VARCHAR(100) NOT NULL UNIQUE,
 short_description VARCHAR(500) NULL,
 description MEDIUMTEXT NULL,
 price DECIMAL(12,2) NOT NULL,
 stock_quantity INT UNSIGNED NOT NULL DEFAULT 0,
 reserved_quantity INT UNSIGNED NOT NULL DEFAULT 0,
 low_stock_threshold INT UNSIGNED NOT NULL DEFAULT 5,
 status ENUM('draft','active','hidden','archived') NOT NULL DEFAULT 'draft',
 compliance_status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
 is_featured TINYINT(1) NOT NULL DEFAULT 0,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY(category_id) REFERENCES categories(id) ON DELETE SET NULL,
 INDEX idx_products_catalog(status,compliance_status,created_at),
 INDEX idx_products_category(category_id,status)
) ENGINE=InnoDB;

CREATE TABLE product_images (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 product_id BIGINT UNSIGNED NOT NULL,
 image_path VARCHAR(500) NOT NULL,
 alt_text VARCHAR(220) NULL,
 is_primary TINYINT(1) NOT NULL DEFAULT 0,
 sort_order INT NOT NULL DEFAULT 0,
 FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE,
 INDEX idx_product_images(product_id,is_primary,sort_order)
) ENGINE=InnoDB;

CREATE TABLE customers (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 full_name VARCHAR(160) NOT NULL,
 phone VARCHAR(30) NOT NULL,
 email VARCHAR(190) NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_customers_phone(phone)
) ENGINE=InnoDB;

CREATE TABLE carts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 customer_id BIGINT UNSIGNED NULL,
 session_key VARCHAR(128) NOT NULL UNIQUE,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY(customer_id) REFERENCES customers(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE cart_items (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 cart_id BIGINT UNSIGNED NOT NULL,
 product_id BIGINT UNSIGNED NOT NULL,
 quantity INT UNSIGNED NOT NULL,
 UNIQUE KEY uq_cart_product(cart_id,product_id),
 FOREIGN KEY(cart_id) REFERENCES carts(id) ON DELETE CASCADE,
 FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE orders (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 order_number VARCHAR(40) NOT NULL UNIQUE,
 customer_id BIGINT UNSIGNED NOT NULL,
 fulfillment_method ENUM('delivery','pickup') NOT NULL,
 city VARCHAR(120) NULL,
 address TEXT NULL,
 customer_note TEXT NULL,
 rejection_reason TEXT NULL,
 status ENUM('new','reviewing','accepted','preparing','ready','completed','rejected','cancelled') NOT NULL DEFAULT 'new',
 subtotal DECIMAL(12,2) NOT NULL,
 delivery_fee DECIMAL(12,2) NOT NULL DEFAULT 0,
 total DECIMAL(12,2) NOT NULL,
 idempotency_key CHAR(64) NOT NULL UNIQUE,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY(customer_id) REFERENCES customers(id),
 INDEX idx_orders_status_date(status,created_at)
) ENGINE=InnoDB;

CREATE TABLE order_items (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 order_id BIGINT UNSIGNED NOT NULL,
 product_id BIGINT UNSIGNED NULL,
 product_name_snapshot VARCHAR(220) NOT NULL,
 sku_snapshot VARCHAR(100) NOT NULL,
 unit_price DECIMAL(12,2) NOT NULL,
 quantity INT UNSIGNED NOT NULL,
 line_total DECIMAL(12,2) NOT NULL,
 FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE,
 FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE order_status_history (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 order_id BIGINT UNSIGNED NOT NULL,
 user_id BIGINT UNSIGNED NULL,
 old_status VARCHAR(30) NULL,
 new_status VARCHAR(30) NOT NULL,
 note TEXT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE,
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE inventory_movements (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 product_id BIGINT UNSIGNED NOT NULL,
 user_id BIGINT UNSIGNED NULL,
 order_id BIGINT UNSIGNED NULL,
 movement_type ENUM('in','reserve','release','out','adjustment') NOT NULL,
 quantity INT NOT NULL,
 note VARCHAR(500) NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(product_id) REFERENCES products(id),
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL,
 FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE SET NULL,
 INDEX idx_inventory_product_date(product_id,created_at)
) ENGINE=InnoDB;

CREATE TABLE settings (
 setting_key VARCHAR(120) PRIMARY KEY,
 setting_value TEXT NULL,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE banners (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 title VARCHAR(220) NOT NULL,
 subtitle VARCHAR(500) NULL,
 image_path VARCHAR(500) NULL,
 button_label VARCHAR(80) NULL,
 button_url VARCHAR(500) NULL,
 is_active TINYINT(1) NOT NULL DEFAULT 0,
 sort_order INT NOT NULL DEFAULT 0,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE user_permissions (
 user_id BIGINT UNSIGNED NOT NULL,
 permission_id BIGINT UNSIGNED NOT NULL,
 allowed TINYINT(1) NOT NULL DEFAULT 1,
 PRIMARY KEY(user_id,permission_id),
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
 FOREIGN KEY(permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NULL,
 action VARCHAR(120) NOT NULL,
 entity_type VARCHAR(80) NULL,
 entity_id BIGINT UNSIGNED NULL,
 ip_address VARCHAR(45) NULL,
 details JSON NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL,
 INDEX idx_audit_date(created_at)
) ENGINE=InnoDB;

INSERT INTO roles(name,label) VALUES
 ('super_admin','المدير الرئيسي'),('manager','مدير مساعد'),('staff','موظف');
INSERT INTO settings(setting_key,setting_value) VALUES
 ('store_name','الفخامة'),('currency','SAR'),('delivery_fee','0'),('phone',''),('whatsapp','');
