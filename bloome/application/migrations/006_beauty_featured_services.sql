ALTER TABLE `beauty_services`
	ADD COLUMN `is_featured` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_active`;

UPDATE `beauty_services`
SET `is_featured` = 1
WHERE `name` IN ('Manicure', 'SPA Pedicure', 'Lashlift & Tint');
