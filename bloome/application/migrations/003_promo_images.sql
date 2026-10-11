ALTER TABLE `promos`
	ADD COLUMN `image_path` VARCHAR(255) DEFAULT NULL AFTER `terms_note`;

UPDATE `promos`
SET `image_path` = CASE `code`
	WHEN 'PILATES-1010' THEN '10.10-pilates.jpeg'
	WHEN 'YOGA-AFTER-OFFICE' THEN 'yoga-promo.jpeg'
	ELSE `image_path`
END
WHERE `code` IN ('PILATES-1010', 'YOGA-AFTER-OFFICE');
