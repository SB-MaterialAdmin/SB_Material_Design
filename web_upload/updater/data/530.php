<?php
$db = \DatabaseManager::GetConnection();
$Tables = $DB->Query("SHOW TABLES")->All();

foreach ($Tables as $Table) {
  $Table = array_values($Table);
  $TableName = $Table[0];

  $DB->Query("ALTER TABLE `$TableName` ENGINE InnoDB");
}