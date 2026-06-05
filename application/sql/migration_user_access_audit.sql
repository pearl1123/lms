-- User access management: audit log + explicit permission denials (grants use aauth_perm_to_user).

CREATE TABLE IF NOT EXISTS `aauth_perm_deny_to_user` (
  `perm_id` int(11) unsigned NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`perm_id`,`user_id`),
  KEY `idx_apdu_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `lms_user_access_audit` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `affected_user_id` int(11) NOT NULL,
  `admin_user_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `entity_type` varchar(30) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_luaa_affected` (`affected_user_id`),
  KEY `idx_luaa_admin` (`admin_user_id`),
  KEY `idx_luaa_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
