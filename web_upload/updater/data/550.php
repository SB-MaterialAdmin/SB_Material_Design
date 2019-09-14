<?php
$DB = \DatabaseManager::GetConnection();

$TableQueries = [
  "CREATE TABLE `{{prefix}}admins_auths` (
    `aid` INT(11) NOT NULL,
    `type` ENUM('steam','ip','name') NOT NULL DEFAULT 'steam' COLLATE 'utf8_unicode_ci',
    `identifier` VARCHAR(64) NOT NULL COLLATE 'utf8_unicode_ci',
    UNIQUE INDEX `aid_type` (`aid`, `type`),
    CONSTRAINT `FK__{{prefix}}admins_auths` FOREIGN KEY (`aid`) REFERENCES `{{prefix}}admins` (`aid`) ON UPDATE CASCADE ON DELETE CASCADE
  )
  ENGINE=InnoDB;",

  "CREATE TABLE `{{prefix}}admins_rights` (
    `aid` INT(11) NOT NULL,
    `servers` BLOB NOT NULL,
    `gid` INT(11) NULL DEFAULT NULL,
    `password` VARCHAR(64) NOT NULL DEFAULT '',
    `expires` INT(11) NOT NULL,
    `immunity` INT(11) NOT NULL DEFAULT '0',
    `web_flags` INT(11) NOT NULL DEFAULT '0',
    `server_flags` INT(11) NOT NULL DEFAULT '0',
    INDEX `FK__{{prefix}}admins_rights` (`aid`),
    CONSTRAINT `FK__{{prefix}}admins_rights` FOREIGN KEY (`aid`) REFERENCES `{{prefix}}admins` (`aid`) ON UPDATE CASCADE ON DELETE CASCADE
  )
  ENGINE=InnoDB;",

  "CREATE TABLE `{{prefix}}permissions_cache` (
    `user` VARCHAR(64) NOT NULL,
    `auth_type` ENUM('steam','ip','name') NOT NULL,
    `auth_identifier` VARCHAR(64) NOT NULL,
    `sid` INT(11) NOT NULL,
    `gid` INT(11) NOT NULL,
    `password` VARCHAR(128) NOT NULL,
    `srv_flags` INT(11) NOT NULL,
    `web_flags` INT(11) NOT NULL,
    `immunity` INT(11) NOT NULL,
    UNIQUE INDEX `admin` (`user`, `sid`, `auth_type`, `auth_identifier`)
  )
  ENGINE=InnoDB;",
];

$DB->BeginTxn();
foreach ($TableQueries as $Query) {
  $DB->Query($Query);
}
$DB->EndTxn();