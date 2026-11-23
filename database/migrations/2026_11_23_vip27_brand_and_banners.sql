-- VIP 27 storefront branding and banner metadata migration.
-- Run once against the existing database; no existing rows are deleted.
UPDATE settings SET setting_value='معسلات VIP 27' WHERE setting_key='store_name';
INSERT INTO settings(setting_key,setting_value) VALUES
 ('store_name','معسلات VIP 27'),
 ('hero_title','معسلات VIP 27'),
 ('hero_subtitle','اكتشف تجربة تسوق راقية، بتفاصيل مختارة بعناية وخدمة تليق بك.')
ON DUPLICATE KEY UPDATE setting_value=IF(setting_value IS NULL OR setting_value='',VALUES(setting_value),setting_value);
ALTER TABLE banners ADD COLUMN IF NOT EXISTS image_alt VARCHAR(220) NULL;
ALTER TABLE banners ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
