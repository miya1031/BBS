-- Adminer 4.8.1 MySQL 8.0.33 dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;

SET NAMES utf8mb4;

DROP TABLE IF EXISTS `members`;
CREATE TABLE `members` (
    `id` int NOT NULL AUTO_INCREMENT,
    `name` varchar(255) DEFAULT NULL,
    `email` varchar(255) DEFAULT NULL,
    `password` varchar(255) DEFAULT NULL,
    `icon` varchar(255) DEFAULT NULL,
    `created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `posts`;
CREATE TABLE `posts` (
    `id` int NOT NULL AUTO_INCREMENT,
    `member_id` int DEFAULT NULL,
    `message` text,
    `reply_post_id` int DEFAULT NULL,
    `created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

DROP TABLE IF EXISTS `likes`;
CREATE TABLE `likes` (
    `id` int NOT NULL AUTO_INCREMENT,
    `post_id` int DEFAULT NULL,
    `liker_id` int DEFAULT NULL,
    `created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


-- 2023-05-22 05:38:19
