<?php
// *************************************************************************
//  This file is part of SourceBans++.
//
//  Copyright (C) 2014-2016 Sarabveer Singh <me@sarabveer.me>
//
//  SourceBans++ is free software: you can redistribute it and/or modify
//  it under the terms of the GNU General Public License as published by
//  the Free Software Foundation, per version 3 of the License.
//
//  SourceBans++ is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//  GNU General Public License for more details.
//
//  You should have received a copy of the GNU General Public License
//  along with SourceBans++. If not, see <http://www.gnu.org/licenses/>.
//
//  This file is based off work covered by the following copyright(s):  
//
//   SourceBans 1.4.11
//   Copyright (C) 2007-2015 SourceBans Team - Part of GameConnect
//   Licensed under GNU GPL version 3, or later.
//   Page: <http://www.sourcebans.net/> - <https://github.com/GameConnect/sourcebansv1>
//
// *************************************************************************

// ---------------------------------------------------
// Disable error output
// ---------------------------------------------------
ini_set('display_startup_errors', 0);
ini_set('display_errors', 0);
error_reporting(0);

// ---------------------------------------------------
//  Directories
// ---------------------------------------------------
define('ROOT', dirname(__FILE__) . "/");
define('USER_DATA', ROOT . "data/");
define('TEMPLATES_PATH', ROOT . 'pages');
define('INCLUDES_PATH', ROOT . 'includes');
define('SB_DEMO_LOCATION','demos');
define('SB_ICON_LOCATION','images/games');
define('SB_MAP_LOCATION', ROOT . 'images/maps');
define('SB_ICONS', ROOT . SB_ICON_LOCATION);
define('SB_DEMOS', USER_DATA . SB_DEMO_LOCATION);

define('SB_THEME', ROOT . 'theme');
define('SB_USER_THEME', USER_DATA . 'theme');
define('SB_THEME_COMPILE', USER_DATA . 'theme_c/');

define('SCRIPT_PATH', SB_THEME . '/js');

define('IN_SB', true);
define('XAJAX_REQUEST_URI', './index.php');

include_once(INCLUDES_PATH . "/CSystemLog.php");
include_once(INCLUDES_PATH . "/CUserManager.php");
include_once(INCLUDES_PATH . "/CUI.php");
include_once(INCLUDES_PATH . "/system-functions.php");
include_once("theme/theme.conf.php");

// ---------------------------------------------------
// Init new framework
// ---------------------------------------------------
include_once(INCLUDES_PATH . '/__loader.php');
RegisterDirForAutoload(INCLUDES_PATH . '/classes');
RegisterDirForAutoload(INCLUDES_PATH . '/cron');

include_once(USER_DATA . '/db.php');

// ---------------------------------------------------
//  Init translator
// ---------------------------------------------------
require_once(INCLUDES_PATH . "/KruzyaExceptions.php");
require_once(INCLUDES_PATH . "/Language.php");
try {
    $GLOBALS['translator'] = new Kruzya\Generic\Language(USER_DATA . "langs/");
} catch (Exception $e) {
    // nope.
    die("Can't initialize multi-language system. Try request again later.");
}

// ---------------------------------------------------
//  Fix some $_SERVER vars
// ---------------------------------------------------
// Fix for IIS, which doesn't set REQUEST_URI
if(!isset($_SERVER['REQUEST_URI']) || trim($_SERVER['REQUEST_URI']) == '') 
{ $_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'];
    if (isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING'])) 
    { $_SERVER['REQUEST_URI'] .= '?' . $_SERVER['QUERY_STRING']; } 
} 
// Fix for Dreamhost and other PHP as CGI hosts
if(strstr($_SERVER['SCRIPT_NAME'], 'php.cgi')) unset($_SERVER['PATH_INFO']);
if(trim($_SERVER['PHP_SELF']) == '') $_SERVER['PHP_SELF'] = preg_replace("/(\?.*)?$/",'', $_SERVER["REQUEST_URI"]);

// ---------------------------------------------------
//  Are we installed?
// ---------------------------------------------------
$cfg_path = '';
if(!FindConfig($cfg_path) || !include_once($cfg_path)) {
	// No were not
	if($_SERVER['HTTP_HOST'] != "localhost")
	{
		echo($GLOBALS['translator']->retrieve("init::not_installed"));
		die();
	}
}
if(!defined("DEVELOPER_MODE") && !defined("IS_UPDATE") && file_exists(ROOT."/install"))
{
	if($_SERVER['HTTP_HOST'] != "localhost")
	{
		echo($GLOBALS['translator']->retrieve("init::security_direxists"));
		die();
	}
}

if(!defined("DEVELOPER_MODE") && !defined("IS_UPDATE") && file_exists(ROOT."/updater"))
{
	if($_SERVER['HTTP_HOST'] != "localhost")
	{
		echo($GLOBALS['translator']->retrieve("init::redirect_updater"));
		echo("<script>setTimeout(function() { window.location.replace('./updater'); }, 2000);</script>");
		die();
	}
}

// ---------------------------------------------------
//  Initial setup
// ---------------------------------------------------

if(!defined('SB_VERSION')){
	define('SB_VERSION', '1.5.4.7');
	define('MA_BRANCH', 'master');
}
define('LOGIN_COOKIE_LIFETIME', (60*60*24*7)*2);
define('COOKIE_PATH', '/');
define('COOKIE_DOMAIN', '');
define('COOKIE_SECURE', false);
define('SB_SALT', 'SourceBans');

// ---------------------------------------------------
//  Setup PHP
// ---------------------------------------------------
ini_set('include_path', '.:/php/includes:' . INCLUDES_PATH .'/adodb');
ini_set('date.timezone', 'GMT');

if(defined("SB_MEM"))
	ini_set('memory_limit', SB_MEM);

// ---------------------------------------------------
//  Setup our DB
// ---------------------------------------------------
include_once(INCLUDES_PATH . "/adodb/adodb.inc.php");
include_once(INCLUDES_PATH . "/adodb/adodb-errorhandler.inc.php");

$GLOBALS['db'] = ADONewConnection("mysqli://".DB_USER.':'.DB_PASS.'@'.DB_HOST.':'.DB_PORT.'/'.DB_NAME);
$GLOBALS['log'] = new CSystemLog();

if( !is_object($GLOBALS['db']) )
				die();
				
$mysql_server_info = $GLOBALS['db']->ServerInfo();
$GLOBALS['db_version'] = $mysql_server_info['version'];

$debug = $GLOBALS['db']->Execute("SELECT value FROM `".DB_PREFIX."_settings` WHERE setting = 'config.debug';");
if($debug->fields['value']=="1") {
	define("DEVELOPER_MODE", true);
}

// ---------------------------------------------------
//  Setup our custom error handler
// ---------------------------------------------------
require_once(INCLUDES_PATH . '/CErrorHandler.php');
CErrorHandler::Init();

// ---------------------------------------------------
//  Some defs
// ---------------------------------------------------
define('EMAIL_FORMAT', "/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/");
define('URL_FORMAT', "/^(http|https):\/\/[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,5}((:[0-9]{1,5})?\/.*)?$/i");
define('STEAM_FORMAT', "/^STEAM_[0-9]:[0-9]:[0-9]+$/");
define('STATUS_PARSE', '/# +([0-9 ]+) +"(.+)" +(STEAM_[0-9]:[0-9]:[0-9]+|\[U:[0-9]:[0-9]+\]) +([0-9:]+) +([0-9]+) +([0-9]+) +([a-zA-Z]+) +([0-9.:]+)/');
define('IP_FORMAT', '/\b(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\b/');

// Web admin-flags
define('ADMIN_LIST_ADMINS', 	(1<<0));
define('ADMIN_ADD_ADMINS', 		(1<<1));
define('ADMIN_EDIT_ADMINS', 	(1<<2));
define('ADMIN_DELETE_ADMINS', 	(1<<3));

define('ADMIN_LIST_SERVERS', 	(1<<4));
define('ADMIN_ADD_SERVER', 		(1<<5));
define('ADMIN_EDIT_SERVERS', 	(1<<6));
define('ADMIN_DELETE_SERVERS', 	(1<<7));

define('ADMIN_ADD_BAN', 		(1<<8));
define('ADMIN_EDIT_OWN_BANS', 	(1<<10));
define('ADMIN_EDIT_GROUP_BANS', (1<<11));
define('ADMIN_EDIT_ALL_BANS', 	(1<<12));
define('ADMIN_BAN_PROTESTS', 	(1<<13));
define('ADMIN_BAN_SUBMISSIONS', (1<<14));
define('ADMIN_DELETE_BAN',		(1<<25));
define('ADMIN_UNBAN', 			(1<<26));
define('ADMIN_BAN_IMPORT',		(1<<27));
define('ADMIN_UNBAN_OWN_BANS',	(1<<30));
define('ADMIN_UNBAN_GROUP_BANS',(1<<31));

define('ADMIN_LIST_GROUPS', 	(1<<15));
define('ADMIN_ADD_GROUP', 		(1<<16));
define('ADMIN_EDIT_GROUPS', 	(1<<17));
define('ADMIN_DELETE_GROUPS', 	(1<<18));

define('ADMIN_WEB_SETTINGS', 	(1<<19));

define('ADMIN_LIST_MODS', 		(1<<20));
define('ADMIN_ADD_MODS', 		(1<<21));
define('ADMIN_EDIT_MODS', 		(1<<22));
define('ADMIN_DELETE_MODS', 	(1<<23));

define('ADMIN_NOTIFY_SUB',	(1<<28));
define('ADMIN_NOTIFY_PROTEST',	(1<<29));

define('ADMIN_OWNER', 			(1<<24));

// Server admin-flags
define('SM_RESERVED_SLOT', 		"a");
define('SM_GENERIC', 			"b");
define('SM_KICK', 				"c");
define('SM_BAN', 				"d");
define('SM_UNBAN', 				"e");
define('SM_SLAY', 				"f");
define('SM_MAP', 				"g");
define('SM_CVAR', 				"h");
define('SM_CONFIG', 			"i");
define('SM_CHAT', 				"j");
define('SM_VOTE',				"k");
define('SM_PASSWORD', 			"l");
define('SM_RCON', 				"m");
define('SM_CHEATS', 			"n");
define('SM_ROOT', 				"z");

define('SM_CUSTOM1', 			"o");
define('SM_CUSTOM2', 			"p");
define('SM_CUSTOM3', 			"q");
define('SM_CUSTOM4', 			"r");
define('SM_CUSTOM5', 			"s");
define('SM_CUSTOM6', 			"t");


define('ALL_WEB', ADMIN_LIST_ADMINS|ADMIN_ADD_ADMINS|ADMIN_EDIT_ADMINS|ADMIN_DELETE_ADMINS|ADMIN_LIST_SERVERS|ADMIN_ADD_SERVER|
				  ADMIN_EDIT_SERVERS|ADMIN_DELETE_SERVERS|ADMIN_ADD_BAN|ADMIN_EDIT_OWN_BANS|ADMIN_EDIT_GROUP_BANS|
				  ADMIN_EDIT_ALL_BANS|ADMIN_BAN_PROTESTS|ADMIN_BAN_SUBMISSIONS|ADMIN_LIST_GROUPS|ADMIN_ADD_GROUP|ADMIN_EDIT_GROUPS|
				  ADMIN_DELETE_GROUPS|ADMIN_WEB_SETTINGS|ADMIN_LIST_MODS|ADMIN_ADD_MODS|ADMIN_EDIT_MODS|ADMIN_DELETE_MODS|ADMIN_OWNER|
				  ADMIN_DELETE_BAN|ADMIN_UNBAN|ADMIN_BAN_IMPORT|ADMIN_UNBAN_OWN_BANS|ADMIN_UNBAN_GROUP_BANS|ADMIN_NOTIFY_SUB|ADMIN_NOTIFY_PROTEST);

define('ALL_SERVER', SM_RESERVED_SLOT.SM_GENERIC.SM_KICK.SM_BAN.SM_UNBAN.SM_SLAY.SM_MAP.SM_CVAR.SM_CONFIG.SM_VOTE.SM_PASSWORD.SM_RCON.
					 SM_CHEATS.SM_CUSTOM1.SM_CUSTOM2.SM_CUSTOM3. SM_CUSTOM4.SM_CUSTOM5.SM_CUSTOM6.SM_ROOT);

$GLOBALS['db']->Execute("SET NAMES utf8;");

// Prepare DB handle.
$DB = \DatabaseManager::GetConnection();

// ---------------------------------------------------
// Load settings from Database
// ---------------------------------------------------
$SettingsStatement = $DB->Query('SELECT * FROM `{{prefix}}settings` GROUP BY `setting`, `value`');
$GLOBALS['config'] = [];

while ($row = $SettingsStatement->Single()) {
  $setting = $row['setting'];
  $value   = $row['value'];

  $GLOBALS['config'][$setting] = $value;
}
$SettingsStatement->EndData();

define('SB_BANS_PER_PAGE', $GLOBALS['config']['banlist.bansperpage']);
define('MIN_PASS_LENGTH', $GLOBALS['config']['config.password.minlength']);
$dateformat = !empty($GLOBALS['config']['config.dateformat'])?$GLOBALS['config']['config.dateformat']:"m-d-y H:i";

if(empty($GLOBALS['config']['config.timezone'])) {
    define('SB_TIMEZONE', 0);
} else {
    define('SB_TIMEZONE', $GLOBALS['config']['config.timezone']);
}

// ---------------------------------------------------
// Load admin flags for SM from Database
// ---------------------------------------------------
/*$FlagsStatement = $DB->Query('SELECT * FROM `{{prefix}}flags` ORDER BY `bit` ASC');
$GLOBALS['flags'] = [];

while ($row = $FlagsStatement->Single()) {
  $flag = $row['flag_char'];
  $name = $row['name'];
  $bit  = $row['bit'];
  $GLOBALS['flags'][$flag] = [
    'name'  => $name,
    'bit'   => $bit
  ];
}

$FlagsStatement->EndData();
*/

// ---------------------------------------------------
// Setup our templater
// ---------------------------------------------------
global $userbank;

if(!@file_exists(SB_THEME . "/theme.conf.php"))
  die($GLOBALS['translator']->retrieve("init::incorrect_theme"));

if(!@is_writable(SB_THEME_COMPILE))
  die($GLOBALS['translator']->retrieve("init::themec_not_writable", ["cache_path" => SB_THEME_COMPILE]));

\TemplateManager::Initialize(SB_THEME, SB_THEME_COMPILE, INCLUDES_PATH . '/Twig/');

// ---------------------------------------------------
// Start our session manager.
// ---------------------------------------------------
\SessionManager::startSession(\SessionManager::getSessionName());

// ---------------------------------------------------
// Setup our user manager
// ---------------------------------------------------
$aid   = filterInput(INPUT_SESSION, 'admin_id',    FILTER_VALIDATE_INT);

\UserManager::init($aid);
$userbank = \UserManager::getInstance(); // for old code.