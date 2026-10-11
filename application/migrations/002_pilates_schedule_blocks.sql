ALTER TABLE `studio_booking_sessions`
	ADD COLUMN `duration_minutes` SMALLINT UNSIGNED NOT NULL DEFAULT 60 AFTER `start_time`;

UPDATE `class_schedules`
SET `is_active` = 0
WHERE `discipline` = 'pilates';

INSERT INTO `class_schedules`
	(`discipline`, `class_type`, `day_of_week`, `start_time`, `duration_minutes`, `coach_id`, `is_active`)
VALUES
	('pilates','reformer',1,'07:00:00',420,(SELECT `id` FROM `coaches` WHERE `name`='Coach Zul'),1),
	('pilates','mat',1,'15:00:00',60,(SELECT `id` FROM `coaches` WHERE `name`='Coach Dinda'),1),
	('pilates','reformer',1,'16:00:00',240,(SELECT `id` FROM `coaches` WHERE `name`='Coach Dinda'),1),
	('pilates','reformer',2,'07:00:00',480,(SELECT `id` FROM `coaches` WHERE `name`='Coach Diza'),1),
	('pilates','reformer',2,'16:00:00',180,(SELECT `id` FROM `coaches` WHERE `name`='Coach Diza'),1),
	('pilates','reformer',2,'20:00:00',60,(SELECT `id` FROM `coaches` WHERE `name`='Coach Diza'),1),
	('pilates','mat',3,'07:00:00',60,(SELECT `id` FROM `coaches` WHERE `name`='Coach Dea'),1),
	('pilates','reformer',3,'08:00:00',240,(SELECT `id` FROM `coaches` WHERE `name`='Coach Dea'),1),
	('pilates','reformer',3,'13:00:00',420,NULL,1),
	('pilates','reformer',4,'07:00:00',360,(SELECT `id` FROM `coaches` WHERE `name`='Coach Riefaldi'),1),
	('pilates','reformer',4,'14:00:00',360,(SELECT `id` FROM `coaches` WHERE `name`='Coach Dinda'),1),
	('pilates','reformer',5,'07:00:00',540,(SELECT `id` FROM `coaches` WHERE `name`='Coach Diza'),1),
	('pilates','reformer',5,'17:00:00',180,(SELECT `id` FROM `coaches` WHERE `name`='Coach Diza'),1),
	('pilates','mat',6,'07:00:00',60,(SELECT `id` FROM `coaches` WHERE `name`='Coach Jane'),1),
	('pilates','reformer',6,'08:00:00',180,(SELECT `id` FROM `coaches` WHERE `name`='Coach Jane'),1),
	('pilates','reformer',6,'12:00:00',180,(SELECT `id` FROM `coaches` WHERE `name`='Coach Zul'),1),
	('pilates','reformer',6,'16:00:00',240,(SELECT `id` FROM `coaches` WHERE `name`='Coach Jane'),1),
	('pilates','reformer',7,'07:00:00',360,(SELECT `id` FROM `coaches` WHERE `name`='Coach Putri'),1),
	('pilates','reformer',7,'14:00:00',360,(SELECT `id` FROM `coaches` WHERE `name`='Coach Han'),1)
ON DUPLICATE KEY UPDATE
	`class_type` = VALUES(`class_type`),
	`duration_minutes` = VALUES(`duration_minutes`),
	`coach_id` = VALUES(`coach_id`),
	`is_active` = VALUES(`is_active`);
