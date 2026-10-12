CREATE TABLE `studio_reviews` (
	`id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`name` varchar(100) NOT NULL,
	`review_text` varchar(1000) NOT NULL,
	`rating` tinyint(3) UNSIGNED NOT NULL DEFAULT 5,
	`source` varchar(40) NOT NULL DEFAULT 'Google Review',
	`sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
	`is_active` tinyint(1) NOT NULL DEFAULT 1,
	`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `studio_reviews` (`name`, `review_text`, `rating`, `source`, `sort_order`, `is_active`) VALUES
	('Aulia Askha', 'studionya comfortable, wangi dan nyaman untuk work out & treatment. Kak Dinda, best coach! super duper ramah banget. highly recommended', 5, 'Google Review', 1, 1),
	('Cut Alia Masturina', 'Warga cinere must try, lokasi nya ada di ruko blkg cibel. Oke dan affordable, ada meni pedi nya juga', 5, 'Google Review', 2, 1),
	('Rae Taylor', 'Studio baru di cinere yang estetik banget, lengkap ada yoga ada pilates, best banget ❤️', 5, 'Google Review', 3, 1);

CREATE TABLE `studio_gallery` (
	`id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`image_path` varchar(255) NOT NULL,
	`alt_text` varchar(160) NOT NULL,
	`sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
	`is_active` tinyint(1) NOT NULL DEFAULT 1,
	`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `studio_gallery` (`image_path`, `alt_text`, `sort_order`, `is_active`) VALUES
	('gallery-studio-01.jpg', 'Sesi Pilates reformer di Bloomè Studio', 1, 1),
	('gallery-studio-02.jpg', 'Ruang kelas yoga Bloomè Studio', 2, 1),
	('gallery-studio-03.jpg', 'Area lounge Bloomè Studio', 3, 1),
	('gallery-studio-04.jpg', 'Area perawatan beauty Bloomè Studio', 4, 1);
