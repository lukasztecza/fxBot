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
    `name` VARCHAR(64) COLLATE utf8_general_ci NOT NULL,
    `type` VARCHAR(16) COLLATE utf8_general_ci DEFAULT NULL,
    `forecast` DECIMAL(10,5) DEFAULT NULL,
    `actual` DECIMAL(10,5) NOT NULL,
    PRIMARY KEY (`instrument`, `datetime`, `name`),
    KEY (`instrument`),
    KEY (`datetime`),
    KEY (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS `price` (
    `instrument` CHAR(7) COLLATE utf8_general_ci NOT NULL,
    `datetime` DATETIME NOT NULL,
    `open` DECIMAL(10,5) NOT NULL,
    `high` DECIMAL(10,5) NOT NULL,
    `low` DECIMAL(10,5) NOT NULL,
    `close` DECIMAL(10,5) NOT NULL,
    PRIMARY KEY (`instrument`, `datetime`),
    KEY (`instrument`),
    KEY (`datetime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS `trade` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `external_id` VARCHAR(32) COLLATE utf8_general_ci NOT NULL,
    `account` VARCHAR(32) COLLATE utf8_general_ci NOT NULL,
    `instrument` CHAR(7) COLLATE utf8_general_ci NOT NULL,
    `units` INT(11) NOT NULL,
    `price` DECIMAL(10,5) NOT NULL,
    `take_profit` DECIMAL(10,5) NOT NULL,
    `stop_loss` DECIMAL(10,5) NOT NULL,
    `balance` DECIMAL(10,5) NOT NULL,
    `datetime` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    KEY (`account`),
    KEY (`external_id`),
    KEY (`datetime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS `simulation` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `final_balance` DECIMAL(10,5) NOT NULL,
    `max_balance` DECIMAL(10,5) NOT NULL,
    `min_balance` DECIMAL(10,5) NOT NULL,
    `profits` INT(11) NOT NULL,
    `losses` INT(11) NOT NULL,
    `simulation_start` DATETIME NOT NULL,
    `simulation_end` DATETIME NOT NULL,
    `datetime` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    KEY (`instrument`),
    KEY (`datetime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS `parameter` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(32) COLLATE utf8_general_ci NOT NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `name` UNIQUE (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS `simulation_parameter` (
    `simulation_id` INT(11) NOT NULL,
    `parameter_id` INT(11) NOT NULL,
    `value` VARCHAR(128) COLLATE utf8_general_ci NOT NULL,
    FOREIGN KEY (`simulation_id`) REFERENCES `simulation`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`parameter_id`) REFERENCES `parameter`(`id`) ON DELETE CASCADE,
    CONSTRAINT simulation_id_parameter_id UNIQUE (`simulation_id`, `parameter_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS `trade_parameter` (
    `trade_id` INT(11) NOT NULL,
    `parameter_id` INT(11) NOT NULL,
    `value` VARCHAR(128) COLLATE utf8_general_ci NOT NULL,
    FOREIGN KEY (`trade_id`) REFERENCES `trade`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`parameter_id`) REFERENCES `parameter`(`id`) ON DELETE CASCADE,
    CONSTRAINT trade_id_parameter_id UNIQUE (`trade_id`, `parameter_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS `learning` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `total` DECIMAL(10,5) NOT NULL,
    `max_balance` DECIMAL(10,5) NOT NULL,
    `min_balance` DECIMAL(10,5) NOT NULL,
    `pack` VARCHAR(32) COLLATE utf8_general_ci NOT NULL,
    PRIMARY KEY (`id`),
    KEY (`pack`),
    KEY (`total`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS `learning_simulation` (
    `learning_id` INT(11) NOT NULL,
    `simulation_id` INT(11) NOT NULL,
    FOREIGN KEY (`learning_id`) REFERENCES `learning`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`simulation_id`) REFERENCES `simulation`(`id`) ON DELETE CASCADE,
    CONSTRAINT simulation_id_learning_id UNIQUE (`simulation_id`, `learning_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
