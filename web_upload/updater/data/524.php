<?php
$DB = \DatabaseManager::GetConnection();
$DB->BeginTxn();

// Create table for routes.
$DB->Query('CREATE TABLE IF NOT EXISTS `{{prefix}}routes` (`id` int(11) NOT NULL, `url` varchar(128) NOT NULL, `parameters` varchar(256) NOT NULL, PRIMARY KEY (`id`), UNIQUE KEY `url` (`url`)) ENGINE=InnoDB DEFAULT CHARSET=utf8;');

// Init routes.
\Router::Initialize();
\Router::Create('', [
  'controller'  => 'Home',
  'action'      => 'index'
]);

\Router::Create('bans');
\Router::Create('comms');
\Router::Create('servers');
\Router::Create('adminlist');
\Router::Create('banprotest');
\Router::Create('submission');

\Router::Create('admin/home', [
  'namespace'   => 'Admin',
  'controller'  => 'Home'
]);
\Router::Create('admin/bans', [
  'namespace'   => 'Admin',
  'controller'  => 'Bans'
]);
\Router::Create('admin/mods', [
  'namespace'   => 'Admin',
  'controller'  => 'GameMods'
]);
\Router::Create('admin/menu', [
  'namespace'   => 'Admin',
  'controller'  => 'Menu'
]);
\Router::Create('admin/admins', [
  'namespace'   => 'Admin',
  'controller'  => 'Admins'
]);
\Router::Create('admin/groups', [
  'namespace'   => 'Admin',
  'controller'  => 'Groups'
]);
\Router::Create('admin/servers', [
  'namespace'   => 'Admin',
  'controller'  => 'Servers'
]);
\Router::Create('admin/settings', [
  'namespace'   => 'Admin',
  'controller'  => 'Settings'
]);

// Done.
$DB->EndTxn();