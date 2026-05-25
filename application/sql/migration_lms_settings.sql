-- Platform settings (key-value JSON). Safe to re-run.

CREATE TABLE IF NOT EXISTS `lms_settings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(120) NOT NULL,
  `setting_value` mediumtext NULL,
  `date_encoded` datetime DEFAULT NULL,
  `encoded_by` int(11) DEFAULT NULL,
  `date_last_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_lms_settings_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
