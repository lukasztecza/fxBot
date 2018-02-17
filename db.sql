CREATE TABLE IF NOT EXISTS `file` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) COLLATE utf8_general_ci NOT NULL,
    `type` TINYINT(4) NOT NULL,
    PRIMARY KEY (`id`),
    KEY (`name`),
    KEY (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS `indicator` (
    `instrument` CHAR(3) COLLATE utf8_general_ci NOT NULL,
    `datetime` DATETIME NOT NULL,
    `name` VARCHAR(32) COLLATE utf8_general_ci NOT NULL,
    `unit` VARCHAR(16) DEFAULT NULL,
    `forecast` DECIMAL(10,5) DEFAULT NULL,
    `market` DECIMAL(10,5) DEFAULT NULL,
    `actual` DECIMAL(10,5) NOT NULL,
    PRIMARY KEY (`instrument`, `datetime`, `name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS `price` (
    `instrument` CHAR(7) COLLATE utf8_general_ci NOT NULL,
    `datetime` DATETIME NOT NULL,
    `open` DECIMAL(10,5) NOT NULL,
    `high` DECIMAL(10,5) NOT NULL,
    `low` DECIMAL(10,5) NOT NULL,
    `close` DECIMAL(10,5) NOT NULL,
    PRIMARY KEY (`instrument`, `datetime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS `trade` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `account` VARCHAR(32) COLLATE utf8_general_ci NOT NULL,
    `instrument` CHAR(7) COLLATE utf8_general_ci NOT NULL,
    `units` INT(11) NOT NULL,
    `price` DECIMAL(10,5) NOT NULL,
    `take_profit` DECIMAL(10,5) NOT NULL,
    `stop_loss` DECIMAL(10,5) NOT NULL,
    `balance` DECIMAL(10,5) NOT NULL,
    `datetime` DATETIME NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
