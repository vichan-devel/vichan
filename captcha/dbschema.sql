SET NAMES utf8;

CREATE TABLE `captchas` (
  `cookie` VARCHAR(50),
  `extra` VARCHAR(200),
  `text` VARCHAR(255),
  `created_at` INT(11),
  PRIMARY KEY (cookie, extra)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4;
