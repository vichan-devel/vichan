SET NAMES utf8mb4;

CREATE TABLE `captchas` (
  `cookie` varchar(50) NOT NULL,
  `extra` varchar(200) NOT NULL,
  `text` varchar(255) DEFAULT NULL,
  `created_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`cookie`,`extra`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
