<?php

$DB = \DatabaseManager::GetConnection();
$DB->Query('
ALTER TABLE `{{prefix}}servers`
	ADD COLUMN `token` VARCHAR(64) NULL DEFAULT NULL AFTER `enabled`
');

$DB->Query('
ALTER TABLE `{{prefix}}admins_servers_groups`
	CHANGE COLUMN `group_id` `group_id` INT(10) NULL DEFAULT NULL AFTER `admin_id`,
	CHANGE COLUMN `srv_group_id` `srv_group_id` INT(10) NULL DEFAULT NULL AFTER `group_id`,
	CHANGE COLUMN `server_id` `server_id` INT(10) NULL DEFAULT NULL AFTER `srv_group_id`,
	ADD COLUMN `permission_combination_id` INT(10) NOT NULL AUTO_INCREMENT FIRST,
	ADD PRIMARY KEY (`permission_combination_id`);
');