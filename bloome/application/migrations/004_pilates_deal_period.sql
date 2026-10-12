ALTER TABLE `studio_bookings`
	ADD COLUMN `promo_code` VARCHAR(40) DEFAULT NULL AFTER `package_id`;

UPDATE `studio_bookings`
SET `promo_code` = 'PILATES-1010'
WHERE `service` = 'pilates'
	AND `package_name` = '10.10 Pilates Deal'
	AND `price` = 190000;

UPDATE `promos`
SET `valid_from` = '2026-10-10',
	`valid_until` = '2026-10-31'
WHERE `code` = 'PILATES-1010'
	AND `discipline` = 'pilates';
