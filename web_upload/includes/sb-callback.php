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


require_once('xajax.inc.php');
include_once('system-functions.php');
include_once('user-functions.php');
$xajax = new xajax();
//$xajax->debugOn();
$xajax->setRequestURI(XAJAX_REQUEST_URI);
global $userbank;

$methods = array(
  'admin' => array(
    'AddMod', 'RemoveMod', 'AddGroup', 'RemoveGroup', 'RemoveAdmin',
    'RemoveSubmission', 'RemoveServer', 'UpdateGroupPermissions',
    'UpdateAdminPermissions', 'AddAdmin', 'SetupEditServer',
    'AddServerGroupName', 'AddServer', 'AddBan', 'RehashAdmins',
    'EditGroup', 'RemoveProtest', 'SendRcon', 'EditAdminPerms',
    'AddComment', 'EditComment', 'RemoveComment', 'PrepareReban',
    'Maintenance', 'KickPlayer', 'GroupBan', 'BanMemberOfGroup',
    'GetGroups', 'BanFriends', 'SendMessage', 'ViewCommunityProfile',
    'SetupBan', 'CheckPassword', 'ChangePassword', 'CheckSrvPassword',
    'ChangeSrvPassword', 'ChangeEmail', 'SendMail', 'AddBlock',
    'PrepareReblock', 'PrepareBlockFromBan', 'removeExpiredAdmins',
    'AddSupport', 'ChangeAdminsInfos', 'InstallMOD', 'UpdateGroupPermissions',
    'PastePlayerData', 'AddWarning', 'RemoveWarning'
  ),
  'default' => array(
    'Plogin', 'ServerHostPlayers', 'ServerHostProperty',
    'ServerHostPlayers_list', 'ServerPlayers', 'LostPassword',
    'RefreshServer', 'AddAdmin_pay', 'RehashAdmins_pay',
    'CSRF', 'GetVACBan'
  )
);

if(\UserManager::getMyID() != -1)
    foreach ($methods['admin'] as $method)
        $xajax->registerFunction($method);

foreach ($methods['default'] as $method)
    $xajax->registerFunction($method);

global $userbank;
$username = $userbank->GetProperty("user");

function GetVACBan($bid) {
  $xajax = new xajaxResponse();
  $DB = \DatabaseManager::GetConnection();

  $DB->Prepare('SELECT `authid` FROM `{{prefix}}bans` WHERE `bid` = :bid');
  $DB->BindData('bid', $bid);
  $Result = $DB->Finish();

  $Ban = $Result->Single();
  $Result->EndData();

  if ($Ban) {
    $Ban = $Ban['authid'];

    if (GetVACStatus($Ban)) {
      $xajax->assign("vacban_{$bid}", 'innerHTML', 'Забанен');
      $xajax->assign("vacban_{$bid}", 'style', 'color: #F00;');
    } else {
      $xajax->assign("vacban_{$bid}", 'innerHTML', 'Отсутствует');
      $xajax->assign("vacban_{$bid}", 'style', '');
    }
  }
  return $xajax;
}

function CSRF() {
  return new xajaxResponse();
}

function InstallMOD($modfolder, $status = 0) {
    global $userbank;
    
    $objResponse = new xajaxResponse();
    $objResponse->addAlert("Выключено. Находится в стадии разработки");
    return $objResponse;
    
    /* TODO: Добавить загрузку данных из репозитория */
    $mapformat = str_replace('{%folder%}', $GameData['folder'], $RepoData['mapformat']);
    $PathIcon = sprintf('%s/%s', SB_ICON_LOCATION, $GameData['icon']);
    $PathMaps = sprintf('%s/%s', SB_MAP_LOCATION, $mapformat);
    
    if ($status == 0) {
        /* Build install dialog */
        $objResponse->addAssign("install_log", "innerHTML", "[".SBDate($GLOBALS['config']['config.dateformat'], time())."] Загрузка файлов с зеркала...");
        $objResponse->addAssign("install_current", "innerHTML", "Загрузка файлов с зеркала");
        $objResponse->addScript('xajax_InstallMOD("'.$modfolder.'", 1);');
    } else if ($status == 1) {
        /* Download files */
        file_put_contents($PathIcon, sprintf('%s%s%s', $RepoData['mirror'], $RepoData['icons_dir'], $GameData['icon']));
        file_put_contents($PathMaps, sprintf('%s%s%s', $RepoData['mirror'], $RepoData['maps_dir'], $mapformat));
        
        $objResponse->addAppend("install_log", "innerHTML", "<br />[".SBDate($GLOBALS['config']['config.dateformat'], time())."] Распаковка архива");
        $objResponse->addAssign("install_current", "innerHTML", "Распаковка архива");
        
        $objResponse->addScript('xajax_InstallMOD("'.$modfolder.'", 2);');
    } else if ($status == 2) {
        /* Decompress maps dir */
        decompress_tar($PathMaps, SB_MAP_LOCATION.'/'.$GameData['folder'].'/');
        
        $objResponse->addAppend("install_log", "innerHTML", "<br />[".SBDate($GLOBALS['config']['config.dateformat'], time())."] Удаление временных файлов");
        $objResponse->addAssign("install_current", "innerHTML", "Удаление временных файлов");
        
        $objResponse->addScript('xajax_InstallMOD("'.$modfolder.'", 3);');
    } else if ($status == 3) {
        /* Insert to DB */
        $GLOBALS['db']->Execute(sprintf("INSERT INTO `%s_mods` (`name`, `icon`, `modfolder`, `steam_universe`, `enabled`) VALUES (%s, %s, %s, %d, 1);", DB_PREFIX, $GLOBALS['db']->qstr($GameData['name']), $GLOBALS['db']->qstr($GameData['icon']), $GLOBALS['db']->qstr($GameData['folder']), (int) $GameData['steamcode']));
    
        $objResponse->addAppend("install_log", "innerHTML", "<br />[".SBDate($GLOBALS['config']['config.dateformat'], time())."] Завершено.");
        $objResponse->addAssign("install_current", "innerHTML", "Установка завершена.");
    }
    
    return $objResponse;
}

function AddSupport($aid)
{
  $objResponse = new xajaxResponse();
    global $userbank, $username;
  $aid = (int)$aid;
    if(!$userbank->is_logged_in())
  {
    $objResponse->redirect("index.php?p=login&m=no_access", 0);
    $log = new CSystemLog("w", "Ошибка доступа", $username . " пытается назначить администратора ".$userbank->GetProperty('user', $aid)." в Support-List, не имея на это прав.");
    return $objResponse;
  }elseif(!$userbank->HasAccess(ADMIN_OWNER)){
    $objResponse->addScript('ShowBox("Ошибка!", "У Вас недостаточно прав для выполнения этой операции!", "red", "index.php");');
    $log = new CSystemLog("w", "Ошибка доступа", $username . " пытался назначить администратора в Support-List, не имея на это прав.");
    return $objResponse;
  }
  

  $res = $GLOBALS['db']->GetOne("SELECT `support` FROM `".DB_PREFIX."_admins` WHERE `aid` = '".$aid."'");
  if($res == "1"){
    $chek = "0";
    $chek1 = "убран";
  }else{
    $chek = "1";
    $chek1 = "добавлен";
  }  
  $query = $GLOBALS['db']->Execute("UPDATE `" . DB_PREFIX . "_admins` SET `support` = ? WHERE `aid` = '".$aid."'", array((int)$chek));
  if($query)
    $objResponse->addScript('ShowBox("Support-List", "Администратор был '.$chek1.', обновите страницу, чтобы увидеть результат, либо продолжайте дальнейшую работу.", "blue", "", true);');
  
  return $objResponse;
}
function removeExpiredAdmins()
{
  global $userbank, $username;
  $objResponse = new xajaxResponse();

  if(!$userbank->HasAccess(ADMIN_OWNER|ADMIN_DELETE_ADMINS))
  {
    $objResponse->addScript('ShowBox("Ошибка!", "У Вас недостаточно прав для выполнения этой операции!.", "red", "index.php?p=admin&c=admins");');
    $log = new CSystemLog("w", "Ошибка доступа", $username . " пытался удалить истёкших админов, не имея на это прав.");
    return $objResponse;
  }
  if($GLOBALS['db']->Execute("DELETE FROM `".DB_PREFIX."_admins` WHERE `expired` < ".time()." AND `expired` <> 0")) {
    $objResponse->addScript('ShowBox("Успешно!", "Все истёкшие админки удалены.", "green", "index.php?p=admin&c=admins");');
    $log = new CSystemLog("m", "Удаление админов", $username . " удалил всех истёкших админов.");
  }
  else {
    $objResponse->addScript('ShowBox("Ошибка!", "Ошибка в удалении истёкших админок. <br /> Смотрите в системный лог для подробной информации.", "red", "index.php?p=admin&c=admins");');
    $log = new CSystemLog("w", "Удаление админов", "Ошибка удаления истёкших админок.");
  }
  
  return $objResponse;
}

function Plogin($username, $password, $remember, $redirect, $nopass) {
  $objResponse = new xajaxResponse();

  $errReason = '';
  if (\UserManager::login($username, $password, $errReason)) {
    if (empty($redirect))
      $redirect = 'p=account';
    $objResponse->addRedirect('?' . $redirect, 0);
  } else 
    $objResponse->addScript("ShowBox('Ошибка авторизации', '$errReason', 'red', '', true);");

  return $objResponse;
}

function LostPassword($email)
{
  $objResponse = new xajaxResponse();
  $q = $GLOBALS['db']->GetRow("SELECT * FROM `" . DB_PREFIX . "_admins` WHERE `email` = ?", array($email));

  if(!$q[0])
  {
    $objResponse->addScript("ShowBox('Ошибка', 'Введенный Вами адрес e-mail не найден в базе', 'red', '', true);");
      return $objResponse;
  }
  else {
    $objResponse->addScript("$('msg-red').setStyle('display', 'none');");
  }

  $validation = md5(generate_salt(20).generate_salt(20)).md5(generate_salt(20).generate_salt(20));
  $query = $GLOBALS['db']->Execute("UPDATE `" . DB_PREFIX . "_admins` SET `validate` = ? WHERE `email` = ?", array($validation, $email));
  $message = "";
  $message .= "Привет " . $q['user'] . "\n";
  $message .= "Вы запросили смену пароля в системе Sourcebans.\n";
  $message .= "Для завершения процедуры смены пароля перейдите по ссылке ниже.\n";
  $message .= "ПРИМЕЧАНИЕ: если Вы не запрашивали смену пароля, просто проигнорируйте это сообщение.\n\n";

  $message .= "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . "?p=lostpassword&email=". RemoveCode($email) . "&validation=" . $validation;

  $headers = 'From: Sourcebans@' . $_SERVER['HTTP_HOST'] . "\n" .
    'X-Mailer: PHP/' . phpversion();
  $m = EMail($email, "Сброс пароля SourceBans", $message, $headers);

  if ($m) $objResponse->addScript("ShowBox('Проверьте почту', 'На Ваш электронный ящик было отправлено письмо с ссылкой для сброса пароля.', 'blue', '', true);");
  else $objResponse->addScript("ShowBox('Ошибка', 'Не удалось отправить письмо на Ваш электронный ящик. Напишите главному администратору.', 'red', '', true);");
  return $objResponse;
}

function CheckSrvPassword($aid, $srv_pass)
{
  $objResponse = new xajaxResponse();
    global $userbank, $username;
  $aid = (int)$aid;
    if(!$userbank->is_logged_in() || $aid != $userbank->GetAid())
  {
    $objResponse->redirect("index.php?p=login&m=no_access", 0);
    $log = new CSystemLog("w", "Ошибка доступа", $username . " пытается проверить пароль сервера ".$userbank->GetProperty('user', $aid).", не имея на это прав.");
    return $objResponse;
  }
  $res = $GLOBALS['db']->Execute("SELECT `srv_password` FROM `".DB_PREFIX."_admins` WHERE `aid` = '".$aid."'");
  if($res->fields['srv_password'] != NULL && $res->fields['srv_password'] != $srv_pass)
  {
    $objResponse->addScript("$('scurrent.msg').setStyle('display', 'block');");
    $objResponse->addScript("$('scurrent.msg').setHTML('Неверный пароль.');");
    $objResponse->addScript("set_error(1);");

  }
  else
  {
    $objResponse->addScript("$('scurrent.msg').setStyle('display', 'none');");
    $objResponse->addScript("set_error(0);");
  }
  return $objResponse;
}

function ChangeSrvPassword($aid, $srv_pass)
{
  $objResponse = new xajaxResponse();
    global $userbank, $username;
    $aid = (int)$aid;
    if(!$userbank->is_logged_in() || $aid != $userbank->GetAid())
  {
    $objResponse->redirect("index.php?p=login&m=no_access", 0);
    $log = new CSystemLog("w", "Ошибка доступа", $username . " пытается изменить пароль сервера ".$userbank->GetProperty('user', $aid).", не имея на это прав.");
    return $objResponse;
  }
    
  if($srv_pass == "NULL")
    $GLOBALS['db']->Execute("UPDATE `".DB_PREFIX."_admins` SET `srv_password` = NULL WHERE `aid` = '".$aid."'");
  else
    $GLOBALS['db']->Execute("UPDATE `".DB_PREFIX."_admins` SET `srv_password` = ? WHERE `aid` = ?", array($srv_pass, $aid));
  $objResponse->addScript("ShowBox('Пароль сервера изменён', 'Пароль сервера был успешно изменён.', 'green', 'index.php?p=account', true);");
  $log = new CSystemLog("m", "Изменён пароль сервера", "Пароль сменил администратор (".$aid.")");
  return $objResponse;
}

function ChangeEmail($aid, $email, $password)
{
    global $userbank, $username;
  $objResponse = new xajaxResponse();
  $aid = (int)$aid;
    
    if(!$userbank->is_logged_in() || $aid != $userbank->GetAid())
  {
    $objResponse->redirect("index.php?p=login&m=no_access", 0);
    $log = new CSystemLog("w", "Ошибка доступа", $username . " пытался сменить e-mail ".$userbank->GetProperty('user', $aid).", не имея на это прав.");
    return $objResponse;
  }
    
    if($userbank->encrypt_password($password) != $userbank->getProperty('password'))
    {
        $objResponse->addScript("$('emailpw.msg').setStyle('display', 'block');");
    $objResponse->addScript("$('emailpw.msg').setHTML('Введённый пароль неверен.');");
    $objResponse->addScript("set_error(1);");
    return $objResponse;
  } else {
    $objResponse->addScript("$('emailpw.msg').setStyle('display', 'none');");
    $objResponse->addScript("set_error(0);");
  }
    
  if(!check_email($email)) {
    $objResponse->addScript("$('email1.msg').setStyle('display', 'block');");
    $objResponse->addScript("$('email1.msg').setHTML('Введите действительный адрес электронной почты.');");
    $objResponse->addScript("set_error(1);");
    return $objResponse;
  } else {
    $objResponse->addScript("$('email1.msg').setStyle('display', 'none');");
    $objResponse->addScript("set_error(0);");
  }

  $GLOBALS['db']->Execute("UPDATE `".DB_PREFIX."_admins` SET `email` = ? WHERE `aid` = ?", array($email, $aid));
  $objResponse->addScript("ShowBox('E-mail изменён', 'Ваш e-mail адрес успешно изменён.', 'green', 'index.php?p=account', true);");
  $log = new CSystemLog("m", "E-mail изменён", "E-mail изменил админ (".$aid.")");
  return $objResponse;
}

function AddGroup($name, $type, $bitmask, $srvflags)
{
  $objResponse = new xajaxResponse();
  global $userbank, $username;
  if(!$userbank->HasAccess(ADMIN_OWNER|ADMIN_ADD_GROUP))
  {
    $objResponse->redirect("index.php?p=login&m=no_access", 0);
    $log = new CSystemLog("w", "Ошибка доступа", $username . " попытался добавить группу, не имея на это прав.");
    return $objResponse;
  }

  $error = 0;
  $query = $GLOBALS['db']->GetRow("SELECT `gid` FROM `" . DB_PREFIX . "_groups` WHERE `name` = ?", array($name));
  $query2 = $GLOBALS['db']->GetRow("SELECT `id` FROM `" . DB_PREFIX . "_srvgroups` WHERE `name` = ?", array($name));
  if(strlen($name) == 0 || count($query) > 0 || count($query2) > 0)
  {
    if(strlen($name) == 0)
    {
      $objResponse->addScript("$('name.msg').setStyle('display', 'block');");
      $objResponse->addScript("$('name.msg').setHTML('Введите имя для группы.');");
      $error++;
    }
    else if(strstr($name, ','))  {
      $objResponse->addScript("$('name.msg').setStyle('display', 'block');");
      $objResponse->addScript("$('name.msg').setHTML('В имени группы не может быть запятой.');");
      $error++;
    }
    else if(count($query) > 0 || count($query2) > 0){
      $objResponse->addScript("$('name.msg').setStyle('display', 'block');");
      $objResponse->addScript("$('name.msg').setHTML('Имя группы уже используется \'" . $name . "\'');");
      $error++;
    }
    else {
      $objResponse->addScript("$('name.msg').setStyle('display', 'none');");
      $objResponse->addScript("$('name.msg').setHTML('');");
    }
  }
  if($type == "0")
  {
    $objResponse->addScript("$('type.msg').setStyle('display', 'block');");
    $objResponse->addScript("$('type.msg').setHTML('Выберите тип группы.');");
    $error++;
  }
  else {
    $objResponse->addScript("$('type.msg').setStyle('display', 'none');");
    $objResponse->addScript("$('type.msg').setHTML('');");
  }
  if($error > 0)
    return $objResponse;

  $query = $GLOBALS['db']->GetRow("SELECT MAX(gid) AS next_gid FROM `" . DB_PREFIX . "_groups`");
  if($type == "1")
  {
    // add the web group
    $query1 = $GLOBALS['db']->Execute("INSERT INTO `" . DB_PREFIX . "_groups` (`gid`, `type`, `name`, `flags`) VALUES (". (int)($query['next_gid']+1) .", '" . (int)$type . "', ?, '" . (int)$bitmask . "')", array($name));
  }
  elseif($type == "2")
  {
    if(strstr($srvflags, "#"))
    {
      $immunity = "0";
      $immunity = substr($srvflags, strpos($srvflags, "#")+1);
      $srvflags = substr($srvflags, 0, strlen($srvflags) - strlen($immunity)-1);
    }
    $immunity = (isset($immunity) && $immunity>0) ? $immunity : 0;
    $add_group = $GLOBALS['db']->Prepare("INSERT INTO ".DB_PREFIX."_srvgroups(immunity,flags,name,groups_immune)
          VALUES (?,?,?,?)");
    $GLOBALS['db']->Execute($add_group,array($immunity, $srvflags, $name, " "));
  }
  elseif($type == "3")
  {
    // We need to add the server into the table
    $query1 = $GLOBALS['db']->Execute("INSERT INTO `" . DB_PREFIX . "_groups` (`gid`, `type`, `name`, `flags`) VALUES (". ($query['next_gid']+1) .", '3', ?, '0')", array($name));
  }

  $log = new CSystemLog("m", "Группа создана", "Новая группа ($name) успешно создана");
    $objResponse->addScript("ShowBox('Группа создана', 'Группа была успешно создана.', 'green', 'index.php?p=admin&c=groups', true);");
    $objResponse->addScript("TabToReload();");
  return $objResponse;
}

function RemoveGroup($gid, $type)
{
  $objResponse = new xajaxResponse();
  global $userbank, $username;
  if(!$userbank->HasAccess(ADMIN_OWNER|ADMIN_DELETE_GROUPS))
  {
    $objResponse->redirect("index.php?p=login&m=no_access", 0);
    $log = new CSystemLog("w", "Ошибка доступа", $username . " попытался удалить группу, не имея на это прав.");
    return $objResponse;
  }

  $gid = (int)$gid;


  if($type == "web") {
    $query2 = $GLOBALS['db']->Execute("UPDATE `" . DB_PREFIX . "_admins` SET gid = -1 WHERE gid = $gid");
    $query1 = $GLOBALS['db']->Execute("DELETE FROM `" . DB_PREFIX . "_groups` WHERE gid = $gid");
  }
  else if($type == "server") {
    $query2 = $GLOBALS['db']->Execute("DELETE FROM `" . DB_PREFIX . "_servers_groups` WHERE group_id = $gid");
    $query1 = $GLOBALS['db']->Execute("DELETE FROM `" . DB_PREFIX . "_groups` WHERE gid = $gid");
  }
  else {
    $query2 = $GLOBALS['db']->Execute("UPDATE `" . DB_PREFIX . "_admins` SET srv_group = NULL WHERE srv_group = (SELECT name FROM `" . DB_PREFIX . "_srvgroups` WHERE id = $gid)");
    $query1 = $GLOBALS['db']->Execute("DELETE FROM `" . DB_PREFIX . "_srvgroups` WHERE id = $gid");
    $query0 = $GLOBALS['db']->Execute("DELETE FROM `" . DB_PREFIX . "_srvgroups_overrides` WHERE group_id = $gid");
  }
  
  if(isset($GLOBALS['config']['config.enableadminrehashing']) && $GLOBALS['config']['config.enableadminrehashing'] == 1)
  {
    // rehash the settings out of the database on all servers
    $serveraccessq = $GLOBALS['db']->GetAll("SELECT sid FROM ".DB_PREFIX."_servers WHERE enabled = 1;");
    $allservers = array();
    foreach($serveraccessq as $access) {
      if(!in_array($access['sid'], $allservers)) {
        $allservers[] = $access['sid'];
      }
    }
    $rehashing = true;
  }

  $objResponse->addScript("SlideUp('gid_$gid');");
  if($query1)
  {
    if(isset($rehashing))
      $objResponse->addScript("ShowRehashBox('".implode(",", $allservers)."', 'Группа удалена', 'Выбранная группа была успешно удалена из базы данных', 'green', 'index.php?p=admin&c=groups', true);");
    else
      $objResponse->addScript("ShowBox('Группа удалена', 'Выбранная группа была успешно удалена из базы данных', 'green', 'index.php?p=admin&c=groups', true);");
    $log = new CSystemLog("m", "Группа удалена", "Группа (" . $gid . ") удалена");
  }
  else
    $objResponse->addScript("ShowBox('Ошибка', 'Не получилось удалить группу из базы данных. Смотрите системный лог для дополнительной информации', 'red', 'index.php?p=admin&c=groups', true);");

  return $objResponse;
}

function RemoveSubmission($sid, $archiv)
{
  $objResponse = new xajaxResponse();
  global $userbank, $username;
  if(!$userbank->HasAccess(ADMIN_OWNER|ADMIN_BAN_SUBMISSIONS))
  {
    $objResponse->redirect("index.php?p=login&m=no_access", 0);
    $log = new CSystemLog("w", "Ошибка доступа", $username . " пытался удалить предложение бана, не имея на это прав.");
    return $objResponse;
  }
  $sid = (int)$sid;
  if($archiv == "1") { // move submission to archiv
    $query1 = $GLOBALS['db']->Execute("UPDATE `" . DB_PREFIX . "_submissions` SET archiv = '1', archivedby = '".$userbank->GetAid()."' WHERE subid = $sid");
    $query = $GLOBALS['db']->GetRow("SELECT count(subid) AS cnt FROM `" . DB_PREFIX . "_submissions` WHERE archiv = '0'");
    $objResponse->addScript("$('subcount').setHTML('" . $query['cnt'] . "');");

    $objResponse->addScript("SlideUp('sid_$sid');");
    $objResponse->addScript("SlideUp('sid_" . $sid . "a');");

    if($query1)
    {
      $objResponse->addScript("ShowBox('Заявка отправлена в архив', 'Выбранная заявка была перемещена в архив!', 'green', 'index.php?p=admin&c=bans', true);");
      $log = new CSystemLog("m", "Заявка отправлена в архив", "Заявка (" . $sid . ") была перемещена в архив");
    }
    else
      $objResponse->addScript("ShowBox('Ошибка', 'Не получилось переместить заявку. Смотрите системный лог для дополнительной информации', 'red', 'index.php?p=admin&c=bans', true);");
  } else if($archiv == "0") { // delete submission
    $query1 = $GLOBALS['db']->Execute("DELETE FROM `" . DB_PREFIX . "_submissions` WHERE subid = $sid");
    $query2 = $GLOBALS['db']->Execute("DELETE FROM `" . DB_PREFIX . "_demos` WHERE demid = '".$sid."' AND demtype = 'S'");
    $query = $GLOBALS['db']->GetRow("SELECT count(subid) AS cnt FROM `" . DB_PREFIX . "_submissions` WHERE archiv = '1'");
    $objResponse->addScript("$('subcountarchiv').setHTML('" . $query['cnt'] . "');");

    $objResponse->addScript("SlideUp('asid_$sid');");
    $objResponse->addScript("SlideUp('asid_" . $sid . "a');");

    if($query1)
    {
      $objResponse->addScript("ShowBox('Заявка удалена', 'Выбранная заявка была удалена из базы данных', 'green', 'index.php?p=admin&c=bans', true);");
      $log = new CSystemLog("m", "Заявка удалена", "Заявка (" . $sid . ") была удалена");
    }
    else
      $objResponse->addScript("ShowBox('Ошибка', 'Не получилось удалить заявку. Смотрите системный лог для дополнительной информации', 'red', 'index.php?p=admin&c=bans', true);");
  } else if($archiv == "2") { // restore the submission
    $query1 = $GLOBALS['db']->Execute("UPDATE `" . DB_PREFIX . "_submissions` SET archiv = '0', archivedby = NULL WHERE subid = $sid");
    $query = $GLOBALS['db']->GetRow("SELECT count(subid) AS cnt FROM `" . DB_PREFIX . "_submissions` WHERE archiv = '0'");
    $objResponse->addScript("$('subcountarchiv').setHTML('" . $query['cnt'] . "');");

    $objResponse->addScript("SlideUp('asid_$sid');");
    $objResponse->addScript("SlideUp('asid_" . $sid . "a');");

    if($query1)
    {
      $objResponse->addScript("ShowBox('Заявка восстановлена', 'Выбранная заявка была восстановлена из архива!', 'green', 'index.php?p=admin&c=bans', true);");
      $log = new CSystemLog("m", "Заявка восстановлена", "Заявка (" . $sid . ") была восстановлена из архива");
    }
    else
      $objResponse->addScript("ShowBox('Ошибка', 'Не получилось восстановить заявку. Смотрите системный лог для дополнительной информации', 'red', 'index.php?p=admin&c=bans', true);");
  }
  return $objResponse;
}

function RemoveProtest($pid, $archiv)
{
  $objResponse = new xajaxResponse();
  global $userbank, $username;
  if(!$userbank->HasAccess(ADMIN_OWNER|ADMIN_BAN_PROTESTS))
  {
    $objResponse->redirect("index.php?p=login&m=no_access", 0);
    $log = new CSystemLog("w", "Ошибка доступа", $username . " пытался удалить протест, не имея на это прав.");
    return $objResponse;
  }
  $pid = (int)$pid;
  if($archiv == '0') { // delete protest
    $query1 = $GLOBALS['db']->Execute("DELETE FROM `" . DB_PREFIX . "_protests` WHERE pid = $pid");
    $query2 = $GLOBALS['db']->Execute("DELETE FROM `" . DB_PREFIX . "_comments` WHERE type = 'P' AND bid = $pid;");
    $query = $GLOBALS['db']->GetRow("SELECT count(pid) AS cnt FROM `" . DB_PREFIX . "_protests` WHERE archiv = '1'");
    $objResponse->addScript("$('protcountarchiv').setHTML('" . $query['cnt'] . "');");
    $objResponse->addScript("SlideUp('apid_$pid');");
    $objResponse->addScript("SlideUp('apid_" . $pid . "a');");

    if($query1)
    {
      $objResponse->addScript("ShowBox('Протест удалён', 'Выбранный протест был удалён из базы данных', 'green', 'index.php?p=admin&c=bans', true);");
      $log = new CSystemLog("m", "Протест удалён", "Протест (" . $pid . ") был удалён");
    }
    else
      $objResponse->addScript("ShowBox('Ошибка', 'Не получилось удалить протест. Смотрите системный лог для дополнительной информации', 'red', 'index.php?p=admin&c=bans', true);");
  } else if($archiv == '1') { // move protest to archiv
    $query1 = $GLOBALS['db']->Execute("UPDATE `" . DB_PREFIX . "_protests` SET archiv = '1', archivedby = '".$userbank->GetAid()."' WHERE pid = $pid");
    $query = $GLOBALS['db']->GetRow("SELECT count(pid) AS cnt FROM `" . DB_PREFIX . "_protests` WHERE archiv = '0'");
    $objResponse->addScript("$('protcount').setHTML('" . $query['cnt'] . "');");
    $objResponse->addScript("SlideUp('pid_$pid');");
    $objResponse->addScript("SlideUp('pid_" . $pid . "a');");

    if($query1)
    {
      $objResponse->addScript("ShowBox('Протест отправлен в архив', 'Выбранный протест был отправлен в архив.', 'green', 'index.php?p=admin&c=bans', true);");
      $log = new CSystemLog("m", "Протест в архиве", "Протест (" . $pid . ") был отправлен в архив.");
    }
    else
      $objResponse->addScript("ShowBox('Ошибка', 'Не получилось отправить в архив протест. Смотрите системный лог для дополнительной информации', 'red', 'index.php?p=admin&c=bans', true);");
  } else if($archiv == '2') { // restore protest
    $query1 = $GLOBALS['db']->Execute("UPDATE `" . DB_PREFIX . "_protests` SET archiv = '0', archivedby = NULL WHERE pid = $pid");
    $query = $GLOBALS['db']->GetRow("SELECT count(pid) AS cnt FROM `" . DB_PREFIX . "_protests` WHERE archiv = '1'");
    $objResponse->addScript("$('protcountarchiv').setHTML('" . $query['cnt'] . "');");
    $objResponse->addScript("SlideUp('apid_$pid');");
    $objResponse->addScript("SlideUp('apid_" . $pid . "a');");

    if($query1)
    {
      $objResponse->addScript("ShowBox('Протест восстановлен', 'Выбранный протест был успешно восстановлен из архива.', 'green', 'index.php?p=admin&c=bans', true);");
      $log = new CSystemLog("m", "Протест восстановлен", "Протест (" . $pid . ") был восстановлен из архива.");
    }
    else
      $objResponse->addScript("ShowBox('Ошибка', 'Не получилось восстановить протест из архива. Смотрите системный лог для дополнительной информации', 'red', 'index.php?p=admin&c=bans', true);");
  }
  return $objResponse;
}

function RemoveServer($sid)
{
  $objResponse = new xajaxResponse();
  global $userbank, $username;
  if(!$userbank->HasAccess(ADMIN_OWNER|ADMIN_DELETE_SERVERS))
  {
    $objResponse->redirect("index.php?p=login&m=no_access", 0);
    $log = new CSystemLog("w", "Ошибка доступа", $username . " пытался удалить сервер, не имея на это прав.");
    return $objResponse;
  }
  $sid = (int)$sid;
  $objResponse->addScript("SlideUp('sid_$sid');");
  $servinfo = $GLOBALS['db']->GetRow("SELECT ip, port FROM `" . DB_PREFIX . "_servers` WHERE sid = $sid");
  $query1 = $GLOBALS['db']->Execute("DELETE FROM `" . DB_PREFIX . "_servers` WHERE sid = $sid");
  $query2 = $GLOBALS['db']->Execute("DELETE FROM `" . DB_PREFIX . "_servers_groups` WHERE server_id = $sid");
    $query3 = $GLOBALS['db']->Execute("UPDATE `" . DB_PREFIX . "_admins_servers_groups` SET server_id = -1 WHERE server_id = $sid");

  $query = $GLOBALS['db']->GetRow("SELECT count(sid) AS cnt FROM `" . DB_PREFIX . "_servers`");
  $objResponse->addScript("$('srvcount').setHTML('" . $query['cnt'] . "');");


  if($query1)
  {
    $objResponse->addScript("ShowBox('Сервер удалён', 'Выбранный сервер был успешно удалён из базы данных', 'green', 'index.php?p=admin&c=servers', true);");
    $log = new CSystemLog("m", "Сервер удалён", "Сервер ((" . $servinfo['ip'] . ":" . $servinfo['port'] . ") был удалён");
  }
  else
    $objResponse->addScript("ShowBox('Ошибка', 'Не получилось удалить сервер. Смотрите системный лог для дополнительной информации', 'red', 'index.php?p=admin&c=servers', true);");
  return $objResponse;
}

function RemoveMod($mid)
{
  $objResponse = new xajaxResponse();
  global $userbank, $username;
  if(!$userbank->HasAccess(ADMIN_OWNER|ADMIN_DELETE_MODS))
  {
    $objResponse->redirect("index.php?p=login&m=no_access", 0);
    $log = new CSystemLog("w", "Ошибка доступа", $username . " пытался удалить мод, не имея на это прав.");
    return $objResponse;
  }
  $mid = (int)$mid;
  $objResponse->addScript("SlideUp('mid_$mid');");

  $modicon = $GLOBALS['db']->GetRow("SELECT icon, name FROM `" . DB_PREFIX . "_mods` WHERE mid = '" . $mid . "';");
  @unlink(SB_ICONS."/".$modicon['icon']);

  $query1 = $GLOBALS['db']->Execute("DELETE FROM `" . DB_PREFIX . "_mods` WHERE mid = '" . $mid . "'");

  if($query1)
  {
    $objResponse->addScript("ShowBox('МОД удалён', 'Выбранный МОД был удалён из базы данных', 'green', 'index.php?p=admin&c=mods', true);");
    $log = new CSystemLog("m", "МОД удалён", "МОД (" . $modicon['name'] . ") был удалён");
  }
  else
    $objResponse->addScript("ShowBox('Ошибка', 'Не получилось удалить МОД. Смотрите системный лог для дополнительной информации', 'red', 'index.php?p=admin&c=mods', true);");
  return $objResponse;
}

function RemoveAdmin($aid)
{
  $objResponse = new xajaxResponse();
  global $userbank, $username;
  if(!$userbank->HasAccess(ADMIN_OWNER|ADMIN_DELETE_ADMINS))
  {
    $objResponse->redirect("index.php?p=login&m=no_access", 0);
    $log = new CSystemLog("w", "Ошибка доступа", $username . " пытался удалить админа, не имея на это прав.");
    return $objResponse;
  }
  $aid = (int)$aid;
  $gid = $GLOBALS['db']->GetRow("SELECT gid, authid, extraflags, user FROM `" . DB_PREFIX . "_admins` WHERE aid = $aid");
  if((intval($gid[2]) & ADMIN_OWNER) != 0)
  {
    $objResponse->addAlert("Ошибка: Вы не можете удалить владельца.");
    return $objResponse;
  }

  $delquery = $GLOBALS['db']->Execute(sprintf("DELETE FROM `%s_admins` WHERE aid = %d LIMIT 1", DB_PREFIX, $aid));
  if($delquery) {
    if(isset($GLOBALS['config']['config.enableadminrehashing']) && $GLOBALS['config']['config.enableadminrehashing'] == 1)
    {
      // rehash the admins for the servers where this admin was on
      $serveraccessq = $GLOBALS['db']->GetAll("SELECT s.sid FROM `".DB_PREFIX."_servers` s
                        LEFT JOIN `".DB_PREFIX."_admins_servers_groups` asg ON asg.admin_id = '".(int)$aid."'
                        LEFT JOIN `".DB_PREFIX."_servers_groups` sg ON sg.group_id = asg.srv_group_id
                        WHERE ((asg.server_id != '-1' AND asg.srv_group_id = '-1')
                        OR (asg.srv_group_id != '-1' AND asg.server_id = '-1'))
                        AND (s.sid IN(asg.server_id) OR s.sid IN(sg.server_id)) AND s.enabled = 1");
      $allservers = array();
      foreach($serveraccessq as $access) {
        if(!in_array($access['sid'], $allservers)) {
          $allservers[] = $access['sid'];
        }
      }
      $rehashing = true;
    }

    $GLOBALS['db']->Execute(sprintf("DELETE FROM `%s_admins_servers_groups` WHERE admin_id = %d", DB_PREFIX, $aid));
   }

  $query = $GLOBALS['db']->GetRow("SELECT count(aid) AS cnt FROM `" . DB_PREFIX . "_admins`");
  $objResponse->addScript("SlideUp('aid_$aid');");
  $objResponse->addScript("$('admincount').setHTML('" . $query['cnt'] . "');");
  if($delquery)
  {
    if(isset($rehashing))
      $objResponse->addScript("ShowRehashBox('".implode(",", $allservers)."', 'Админ удалён', 'Выбранный админ был удалён из базы данных', 'green', 'index.php?p=admin&c=admins', true);");
    else
      $objResponse->addScript("ShowBox('Админ удалён', 'Выбранный админ был удалён из базы данных', 'green', 'index.php?p=admin&c=admins', true);");
    $log = new CSystemLog("m", "Админ удалён", "Админ (" . $gid['user'] . ") был удалён");
  }
  else
    $objResponse->addScript("ShowBox('Ошибка', 'Не получилось удалить админа. Смотрите системный лог для дополнительной информации', 'red', 'index.php?p=admin&c=admins', true);");
  return $objResponse;
}

function AddServer($ip, $port, $rcon, $rcon2, $mod, $enabled, $group, $group_name, $priority)
{
  $objResponse = new xajaxResponse();
  global $userbank, $username;
  if(!$userbank->HasAccess(ADMIN_OWNER|ADMIN_ADD_SERVER))
  {
    $objResponse->redirect("index.php?p=login&m=no_access", 0);
    $log = new CSystemLog("w", "Ошибка доступа", $username . " пытался добавить сервер, не имея на это прав.");
    return $objResponse;
  }
  $ip = RemoveCode($ip);
  $group_name = RemoveCode($group_name);

  $error = 0;
  // ip
  if((empty($ip)))
  {
    $error++;
    $objResponse->addAssign("address.msg", "innerHTML", "Введите адрес сервера.");
    $objResponse->addScript("$('address.msg').setStyle('display', 'block');");
  }
  else
  {
    $objResponse->addAssign("address.msg", "innerHTML", "");
    if(!validate_ip($ip) && !is_string($ip))
    {
      $error++;
      $objResponse->addAssign("address.msg", "innerHTML", "Введите действительный IP сервера.");
      $objResponse->addScript("$('address.msg').setStyle('display', 'block');");
    }
    else
      $objResponse->addAssign("address.msg", "innerHTML", "");
  }
  // Port
  if((empty($port)))
  {
    $error++;
    $objResponse->addAssign("port.msg", "innerHTML", "Введите порт сервера.");
    $objResponse->addScript("$('port.msg').setStyle('display', 'block');");
  }
  else
  {
    $objResponse->addAssign("port.msg", "innerHTML", "");
    if(!is_numeric($port))
    {
      $error++;
      $objResponse->addAssign("port.msg", "innerHTML", "Введите действительный порт <b>цифрами</b>.");
      $objResponse->addScript("$('port.msg').setStyle('display', 'block');");
    }
    else
    {
      $objResponse->addScript("$('port.msg').setStyle('display', 'none');");
      $objResponse->addAssign("port.msg", "innerHTML", "");
    }
  }
  // rcon
  if(!empty($rcon) && $rcon != $rcon2)
  {
    $error++;
    $objResponse->addAssign("rcon2.msg", "innerHTML", "Пароли не совпадают.");
    $objResponse->addScript("$('rcon2.msg').setStyle('display', 'block');");
  }
  else
    $objResponse->addAssign("rcon2.msg", "innerHTML", "");

  // Please Select
  if($mod == -2)
  {
    $error++;
    $objResponse->addAssign("mod.msg", "innerHTML", "Выберите МОД сервера.");
    $objResponse->addScript("$('mod.msg').setStyle('display', 'block');");
  }
  else
    $objResponse->addAssign("mod.msg", "innerHTML", "");

  if($group == -2)
  {
    $error++;
    $objResponse->addAssign("group.msg", "innerHTML", "Вы должны выбрать опцию.");
    $objResponse->addScript("$('group.msg').setStyle('display', 'block');");
  }
  else
    $objResponse->addAssign("group.msg", "innerHTML", "");

  if($error)
    return $objResponse;
  
  // Check for dublicates afterwards
  $chk = $GLOBALS['db']->GetRow('SELECT sid FROM `'.DB_PREFIX.'_servers` WHERE ip = ? AND port = ?;', array($ip, (int)$port));
  if($chk)
  {
    $objResponse->addScript("ShowBox('Ошибка', 'Введённый сервер уже существует в базе.', 'red');");
    return $objResponse;
  }

  // ##############################################################
  // ##                     Start adding to DB                   ##
  // ##############################################################
  //they wanna make a new group
  $gid = -1;
  $sid = nextSid();
  
  $enable = ($enabled=="true"?1:0);

  // Add the server
  $addserver = $GLOBALS['db']->Prepare("INSERT INTO ".DB_PREFIX."_servers (`sid`, `ip`, `port`, `rcon`, `modid`, `enabled`, `priority`)
                      VALUES (?,?,?,?,?,?,?)");
  $GLOBALS['db']->Execute($addserver,array($sid, $ip, (int)$port, $rcon, $mod, $enable, intval($priority)));

  // Add server to each group specified
  $groups = explode(",", $group);
  $addtogrp = $GLOBALS['db']->Prepare("INSERT INTO ".DB_PREFIX."_servers_groups (`server_id`, `group_id`) VALUES (?,?)");
  foreach($groups AS $g)
  {
    if($g)
      $GLOBALS['db']->Execute($addtogrp,array($sid, $g));
  }


  $objResponse->addScript("ShowBox('Сервер добавлен', 'Выш сервер был успешно создан.', 'green', 'index.php?p=admin&c=servers');");
    $objResponse->addScript("TabToReload();");
    $log = new CSystemLog("m", "Сервер добавлен", "Сервер (" . $ip . ":" . $port . ") добавлен");
  return $objResponse;
}


function UpdateGroupPermissions($gid)
{
  $objResponse = new xajaxResponse();
  global $userbank;
  $gid = (int)$gid;
  if($gid == 1)
  {
    $permissions = @file_get_contents(TEMPLATES_PATH . "/groups.web.perm.php");
    $permissions = str_replace("{title}", "Разрешения доступа к сайту", $permissions);
  }
  elseif($gid == 2)
  {
    $permissions = @file_get_contents(TEMPLATES_PATH . "/groups.server.perm.php");
    $permissions = str_replace("{title}", "Разрешения доступа к серверу", $permissions);
  }
  elseif($gid == 3)
    $permissions = "";

  $objResponse->addAssign("perms", "innerHTML", $permissions);
  if(!$userbank->HasAccess(ADMIN_OWNER))
    $objResponse->addScript('if($("wrootcheckbox")) { 
                  $("wrootcheckbox").setStyle("display", "none");
                }
                if($("srootcheckbox")) { 
                  $("srootcheckbox").setStyle("display", "none");
                }');
  $objResponse->addScript("$('type.msg').setHTML('');");
  $objResponse->addScript("$('type.msg').setStyle('display', 'none');");
  return $objResponse;
}

function UpdateAdminPermissions($type, $value)
{
  $objResponse = new xajaxResponse();
  global $userbank;
  $type = (int)$type;
  if($type == 1)
  {
    $id = "web";
    if($value == "c")
    {
      $permissions = @file_get_contents(TEMPLATES_PATH . "/groups.web.perm.php");
      $permissions = str_replace("{title}", "Разрешения доступа к сайту", $permissions);
    }
    elseif($value == "n")
    {
      $permissions = @file_get_contents(TEMPLATES_PATH . "/group.name.php") . @file_get_contents(TEMPLATES_PATH . "/groups.web.perm.php");
      $permissions = str_replace("{name}", "webname", $permissions);
      $permissions = str_replace("{title}", "Добавить группу доступа", $permissions);
    }
    else
      $permissions = "";
  }
  if($type == 2)
  {
    $id = "server";
    if($value == "c")
    {
      $permissions = file_get_contents(TEMPLATES_PATH . "/groups.server.perm.php");
      $permissions = str_replace("{title}", "Разрешения доступа к серверу", $permissions);
    }
    elseif($value == "n")
    {
      $permissions = @file_get_contents(TEMPLATES_PATH . "/group.name.php") . @file_get_contents(TEMPLATES_PATH . "/groups.server.perm.php");
      $permissions = str_replace("{name}", "servername", $permissions);
      $permissions = str_replace("{title}", "Добавить группу доступа", $permissions);
    }
    else
      $permissions = "";
  }

  $objResponse->addAssign($id."perm", "innerHTML", $permissions);
  if(!$userbank->HasAccess(ADMIN_OWNER))
    $objResponse->addScript('if($("wrootcheckbox")) { 
                  $("wrootcheckbox").setStyle("display", "none");
                }
                if($("srootcheckbox")) { 
                  $("srootcheckbox").setStyle("display", "none");
                }');
  $objResponse->addAssign($id.".msg", "innerHTML", "");
  return $objResponse;

}

function AddServerGroupName()
{
  $objResponse = new xajaxResponse();
  global $userbank, $username;
  if(!$userbank->HasAccess(ADMIN_OWNER|ADMIN_EDIT_GROUPS))
  {
    $objResponse->redirect("index.php?p=login&m=no_access", 0);
    $log = new CSystemLog("w", "Ошибка доступа", $username . " пытался изменить имя группы, не имея на это прав.");
    return $objResponse;
  }
  $inject = '<td valign="top"><div class="rowdesc">' . HelpIcon("Имя группы серверов", "Введите имя новой группы.") . 'Имя группы </div></td>';
  $inject .= '<td><div align="left">
        <input type="text" style="border: 1px solid #000000; width: 105px; font-size: 14px; background-color: rgb(215, 215, 215);width: 200px;" id="sgroup" name="sgroup" />
      </div>
        <div id="group_name.msg" style="color:#CC0000;width:195px;display:none;"></div></td>
  ';
  $objResponse->addAssign("nsgroup", "innerHTML", $inject);
  $objResponse->addAssign("group.msg", "innerHTML", "");
  return $objResponse;

}

function AddAdmin_pay($mask, $srv_mask, $a_name, $a_steam, $a_email, $a_password, $a_password2,  $a_sg, $a_wg, $a_serverpass, $a_webname, $a_servername, $server, $singlesrv, $skype, $comment, $vk, $a_code)
{
  // TODO: Снести нахер эту функцию. Подвергнуть фатальному
  // рефакторингу весь AJAX код на бэке после увиденного.

  $objResponse = new xajaxResponse();
  global $userbank, $username;
  
  $mask = "";
  $srv_mask = "";
  $a_sg = "";
  $a_wg = "";
  $a_serverpass = "-1";
  $a_webname = "0";
  $a_servername = "0";
  $server = "";
  $comment = "";
  
  $vk = RemoveCode($vk);
  $vk = str_replace(array("http://","https://","/","vk.com"), "", $vk);
  $skype = RemoveCode($skype);
  $a_code = RemoveCode($a_code);
  $a_code = preg_replace("/[^0-9]/", '', $a_code);
  
  $srv_sql_val = $GLOBALS['db']->GetOne("SELECT `servers` FROM `" . DB_PREFIX . "_vay4er` WHERE `value` = '".$a_code."'");
  if($srv_sql_val == "-1"){
    $singlesrv = "";
  }elseif((stristr($srv_sql_val, ',') && stristr($srv_sql_val, 's')) == TRUE){
    $singlesrv = $srv_sql_val;
  }
  
  $qwe = $GLOBALS['db']->GetOne("SELECT `activ` FROM `" . DB_PREFIX . "_vay4er` WHERE `value` = '".$a_code."'");
  if($qwe == "0" || $qwe != "1"){
    $objResponse->addScript("ShowBox('Активация', 'Ваш ваучер уже был успешно активирован! Повторная активация - невозможна. Переадресация...', 'red', 'index.php', false);");
    $log = new CSystemLog("w", "Ваучер", $a_name . " пытался активировать ваучер повторно.");
    return $objResponse;
    exit();
  }
  
  $pay_days_sql = $GLOBALS['db']->GetOne("SELECT `days` FROM `" . DB_PREFIX . "_vay4er` WHERE `value` = '".$a_code."'");
  if(!$pay_days_sql == "0"){
    $pay_days_sql = (time() + $pay_days_sql * 86400);
  }
  $a_name = RemoveCode($a_name);
  $a_steam = RemoveCode($a_steam);
  $a_email = RemoveCode($a_email);
  $a_servername = ($a_servername=="0" ? null : RemoveCode($a_servername));
  $a_webname = RemoveCode($a_webname);
  $mask = (int)$mask;

  $error=0;
  
    //No name
  if(empty($a_name))
  {
    $error++;
    $objResponse->addAssign("name.msg", "innerHTML", "Введите имя админа.");
    $objResponse->addScript("$('name.msg').setStyle('display', 'block');");
  }
  else{
    if(strstr($a_name, "'"))
    {
      $error++;
      $objResponse->addAssign("name.msg", "innerHTML", "Имя админа не должно содержать символы \" ' \".");
      $objResponse->addScript("$('name.msg').setStyle('display', 'block');");
    }
    else
    {
      if(is_taken("admins", "user", $a_name))
      {
          $error++;
          $objResponse->addAssign("name.msg", "innerHTML", "Администратор с таким именем уже существует");
          $objResponse->addScript("$('name.msg').setStyle('display', 'block');");
      }
      else
      {
          $objResponse->addAssign("name.msg", "innerHTML", "");
          $objResponse->addScript("$('name.msg').setStyle('display', 'none');");
      }
    }
  }
  // If they didnt type a steamid
  if ($type == 0)
  {
    if(empty($a_steam))
    {
      $error++;
      $objResponse->addAssign("steam.msg", "innerHTML", "Введите Steam ID или Community ID");
      $objResponse->addScript("$('steam.msg').setStyle('display', 'block');");
    }
    else
    {
      try
      {
        $a_steam = \CSteamID::factory($a_steam)->v2;
      }
      catch (\Exception $e)
      {
        $error++;
        $objResponse->addAssign("steam.msg", "innerHTML", "Введите действительный Steam ID или Community ID");
        $objResponse->addScript("$('steam.msg').setStyle('display', 'block');");
      }
    }
  }
  else
  {
    // TODO: Внедрить сюда продление админа вместо вывода
    // ошибки, если группа совпадает при неистёкшей админке.
    if(is_taken("admins", "authid", $a_steam))
    {
      $admins = $userbank->GetAllAdmins();
      foreach($admins as $admin)
      {
        if($admin['authid'] == $a_steam)
        {
          $name = $admin['user'];
          break;
        }
      }
      $error++;
      $objResponse->addAssign("steam.msg", "innerHTML", "Этот Steam ID уже используется одним из администраторов!");
      $objResponse->addScript("$('steam.msg').setStyle('display', 'block');");
    }
    else
    {
      $objResponse->addAssign("steam.msg", "innerHTML", "");
      $objResponse->addScript("$('steam.msg').setStyle('display', 'none');");
    }
  }
  
  // No email
  if(empty($a_email))
  {
    // An E-Mail address is only required for users with web permissions.
    $error++;
    $objResponse->addAssign("email.msg", "innerHTML", "Введите адрес e-mail.");
    $objResponse->addScript("$('email.msg').setStyle('display', 'block');");
  }
  else{
    // Is an other admin already registred with that email address?
    if(is_taken("admins", "email", $a_email))
    {
      $admins = $userbank->GetAllAdmins();
      foreach($admins as $admin)
      {
        if($admin['email'] == $a_email)
        {
          $name = $admin['user'];
          break;
        }
      }
      $error++;
      $objResponse->addAssign("email.msg", "innerHTML", "Этот e-mail уже используется одним из администраторов!");
      $objResponse->addScript("$('email.msg').setStyle('display', 'block');");
    }
    else
    {
      $objResponse->addAssign("email.msg", "innerHTML", "");
      $objResponse->addScript("$('email.msg').setStyle('display', 'none');");
    }
  }
  
  // no pass
  if(empty($a_password))
  {
    // A password is only required for users with web permissions.
    $error++;
    $objResponse->addAssign("password.msg", "innerHTML", "Введите пароль.");
    $objResponse->addScript("$('password.msg').setStyle('display', 'block');");
  }
  // Password too short?
  else if(strlen($a_password) < MIN_PASS_LENGTH)
  {
    $error++;
    $objResponse->addAssign("password.msg", "innerHTML", "Длина пароля должна быть не менее " . MIN_PASS_LENGTH . " символов.");
    $objResponse->addScript("$('password.msg').setStyle('display', 'block');");
  }
  else 
  {
    $objResponse->addAssign("password.msg", "innerHTML", "");
    $objResponse->addScript("$('password.msg').setStyle('display', 'none');");
    
    // No confirmation typed
    if(empty($a_password2))
    {
      $error++;
      $objResponse->addAssign("password2.msg", "innerHTML", "Подтвердите пароль");
      $objResponse->addScript("$('password2.msg').setStyle('display', 'block');");
    }
    // Passwords match?
    else if($a_password != $a_password2)
    {
      $error++;
      $objResponse->addAssign("password2.msg", "innerHTML", "Пароли не совпадают");
      $objResponse->addScript("$('password2.msg').setStyle('display', 'block');");
    }
    else
    {
      $objResponse->addAssign("password2.msg", "innerHTML", "");
      $objResponse->addScript("$('password2.msg').setStyle('display', 'none');");
    }
  }

  // Choose to use a server password
  if($a_serverpass != "-1")
  {
    // No password given?
    if(empty($a_serverpass))
    {
      $error++;
      $objResponse->addAssign("a_serverpass.msg", "innerHTML", "Введите пароль сервера, либо снимите галочку.");
      $objResponse->addScript("$('a_serverpass.msg').setStyle('display', 'block');");
    }
    // Password too short?
    else if(strlen($a_serverpass) < MIN_PASS_LENGTH)
    {
      $error++;
      $objResponse->addAssign("a_serverpass.msg", "innerHTML", "Длина пароля должна быть не менее " . MIN_PASS_LENGTH . " символов.");
      $objResponse->addScript("$('a_serverpass.msg').setStyle('display', 'block');");
    }
    else 
    {
      $objResponse->addAssign("a_serverpass.msg", "innerHTML", "");
      $objResponse->addScript("$('a_serverpass.msg').setStyle('display', 'none');");
    }
  }
  else
  {
    $objResponse->addAssign("a_serverpass.msg", "innerHTML", "");
    $objResponse->addScript("$('a_serverpass.msg').setStyle('display', 'none');");
    // Don't set "-1" as password ;)
    $a_serverpass = "";
  }
  
    // didn't choose a server group
    if($a_sg == "-2")
    {
        $error++;
        $objResponse->addAssign("server.msg", "innerHTML", "Выберите группу.");
        $objResponse->addScript("$('server.msg').setStyle('display', 'block');");
    }
    else
    {
        $objResponse->addAssign("server.msg", "innerHTML", "");
        $objResponse->addScript("$('server.msg').setStyle('display', 'none');");
    }
  
  // chose to create a new server group
  if($a_sg == 'n')
  {
    // didn't type a name
    if(empty($a_servername))
    {
      $error++;
      $objResponse->addAssign("servername_err", "innerHTML", "Введите имя новой группы.");
      $objResponse->addScript("$('servername_err').setStyle('display', 'block');");
    }
    // Group names can't contain ,
    else if(strstr($a_servername, ','))
    {
      $error++;
      $objResponse->addAssign("servername_err", "innerHTML", "Имя группы не может содержать запятую.");
      $objResponse->addScript("$('servername_err').setStyle('display', 'block');");
    }
    else
    {
      $objResponse->addAssign("servername_err", "innerHTML", "");
      $objResponse->addScript("$('servername_err').setStyle('display', 'none');");
    }
  }
  
  // didn't choose a web group
    if($a_wg == "-2")
  {
        $error++;
        $objResponse->addAssign("web.msg", "innerHTML", "Выберите группу.");
        $objResponse->addScript("$('web.msg').setStyle('display', 'block');");
    }
    else
    {
        $objResponse->addAssign("web.msg", "innerHTML", "");
        $objResponse->addScript("$('web.msg').setStyle('display', 'none');");
    }
    
  // Choose to create a new webgroup
  if($a_wg == 'n')
  {
    // But didn't type a name
    if(empty($a_webname))
    {
      $error++;
      $objResponse->addAssign("webname_err", "innerHTML", "Введите имя новой группы.");
      $objResponse->addScript("$('webname_err').setStyle('display', 'block');");
    }
    // Group names can't contain ,
    else if(strstr($a_webname, ','))
    {
      $error++;
      $objResponse->addAssign("webname_err", "innerHTML", "Имя группы не может содержать запятую.");
      $objResponse->addScript("$('webname_err').setStyle('display', 'block');");
    }
    else
    {
      $objResponse->addAssign("webname_err", "innerHTML", "");
      $objResponse->addScript("$('webname_err').setStyle('display', 'none');");
    }
  }
  
  
  // Ohnoes! something went wrong, stop and show errs
  if($error)
  {
    ShowBox_ajx("Ошибка", "Допущены ошибки. Пожалуйста, исправьте их.", "red", "", true, $objResponse);
    return $objResponse;
  }

// ##############################################################
// ##                     Start adding to DB                   ##
// ##############################################################
  
  $gid = 0;
  $groupID = 0;
  $inGroup = false;
  $wgid = NextAid();
  $immunity = 0;
  
  
  // Extract immunity from server mask string
  if(strstr($srv_mask, "#"))
  {
    $immunity = "0";
    $immunity = substr($srv_mask, strpos($srv_mask, "#")+1);
    $srv_mask = substr($srv_mask, 0, strlen($srv_mask) - strlen($immunity)-1);
  }
  
  // Avoid negative immunity
  $immunity = ($immunity>0) ? $immunity : 0;
  
  // Handle Webpermissions
  // Chose to create a new webgroup
  if($a_wg == 'n')
  {
    $add_webgroup = $GLOBALS['db']->Execute("INSERT INTO ".DB_PREFIX."_groups(type, name, flags)
                    VALUES (?,?,?)", array(1, $a_webname, $mask));
    $web_group = (int)$GLOBALS['db']->Insert_ID();
    
    // We added those permissons to the group, so don't add them as custom permissions again
    $mask = 0;
  }
  // Chose an existing group
  else if($a_wg != 'c' && $a_wg > 0)
  {
    $web_group = (int)$a_wg;
  }
  // Custom permissions -> no group
  else
  {
    $web_group = -1;
  }
  
  // Handle Serverpermissions
  // Chose to create a new server admin group
  if($a_sg == 'n')
  {
    $add_servergroup = $GLOBALS['db']->Execute("INSERT INTO ".DB_PREFIX."_srvgroups(immunity, flags, name, groups_immune)
          VALUES (?,?,?,?)", array($immunity, $srv_mask, $a_servername, " "));
    
    $server_admin_group = $a_servername;
    $server_admin_group_int = (int)$GLOBALS['db']->Insert_ID();
    
    // We added those permissons to the group, so don't add them as custom permissions again
    $srv_mask = "";
  }
  // Chose an existing group
  else if($a_sg != 'c' && $a_sg > 0)
  {
    $server_admin_group = $GLOBALS['db']->GetOne("SELECT `name` FROM ".DB_PREFIX."_srvgroups WHERE id = '" . (int)$a_sg . "'");
    $server_admin_group_int = (int)$a_sg;
  }
  // Custom permissions -> no group
  else
  {
    $server_admin_group = "";
    $server_admin_group_int = -1;
  }

  //$q_del = $GLOBALS['db']->Execute("DELETE FROM `" . DB_PREFIX . "_vay4er` WHERE `value` = '".$a_code."'");
  $q_del = $GLOBALS['db']->Execute("UPDATE `" . DB_PREFIX . "_vay4er` SET `value` = '".$a_code."', `activ` = '0' WHERE `value` = '".$a_code."'");
  if($q_del){
    // Add the admin
    $web_gruop_id = $GLOBALS['db']->GetOne("SELECT `group_web` FROM ".DB_PREFIX."_vay4er WHERE `value` = '".$a_code."'");
    $web_gruop_sql = $GLOBALS['db']->GetOne("SELECT `gid` FROM ".DB_PREFIX."_groups WHERE `name` = '".$web_gruop_id."'");
    if($web_gruop_id == "" || $web_gruop_sql == "" ){
      $web_gruop_sql = "0";
    }
    $server_admin_group = $GLOBALS['db']->GetOne("SELECT `group_srv` FROM ".DB_PREFIX."_vay4er WHERE `value` = '".$a_code."'");
    //if($server_admin_group == ""){
    //  $web_gruop_sql = "";
    //}
    $aid = $userbank->AddAdmin($a_name, $a_steam, $a_password, $a_email, $web_gruop_sql, $mask, $server_admin_group, $srv_mask, $immunity, $a_serverpass, $pay_days_sql, $skype, '', $vk);
    setcookie("aid", $aid, time()+LOGIN_COOKIE_LIFETIME);
    setcookie("password", $GLOBALS['db']->GetOne("SELECT `password` FROM `".DB_PREFIX."_admins` WHERE `aid` = '".$aid."'"), time()+LOGIN_COOKIE_LIFETIME);
  }else{
    exit();
  }
  if($aid > -1)
  {
    // Grant permissions to the selected server groups
    $srv_groups = explode(",", $server);
    $addtosrvgrp = $GLOBALS['db']->Prepare("INSERT INTO ".DB_PREFIX."_admins_servers_groups(admin_id,group_id,srv_group_id,server_id) VALUES (?,?,?,?)");
    foreach($srv_groups AS $srv_group)
    {
      if(!empty($srv_group))
        $GLOBALS['db']->Execute($addtosrvgrp,array($aid, $server_admin_group_int, substr($srv_group, 1), '-1'));
    }
    
    // Grant permissions to individual servers
    $srv_arr = explode(",", $singlesrv);
    $addtosrv = $GLOBALS['db']->Prepare("INSERT INTO ".DB_PREFIX."_admins_servers_groups(admin_id,group_id,srv_group_id,server_id) VALUES (?,?,?,?)");
    foreach($srv_arr AS $server)
    {
      if(!empty($server))
        $GLOBALS['db']->Execute($addtosrv,array($aid, $server_admin_group_int, '-1', substr($server, 1)));
    }
    if(isset($GLOBALS['config']['config.enableadminrehashing']) && $GLOBALS['config']['config.enableadminrehashing'] == 1)
    {
      // rehash the admins on the servers
      $serveraccessq = $GLOBALS['db']->GetAll("SELECT s.sid FROM `".DB_PREFIX."_servers` s
                        LEFT JOIN `".DB_PREFIX."_admins_servers_groups` asg ON asg.admin_id = '".(int)$aid."'
                        LEFT JOIN `".DB_PREFIX."_servers_groups` sg ON sg.group_id = asg.srv_group_id
                        WHERE ((asg.server_id != '-1' AND asg.srv_group_id = '-1')
                        OR (asg.srv_group_id != '-1' AND asg.server_id = '-1'))
                        AND (s.sid IN(asg.server_id) OR s.sid IN(sg.server_id)) AND s.enabled = 1");
      $allservers = array();
      foreach($serveraccessq as $access) {
        if(!in_array($access['sid'], $allservers)) {
          $allservers[] = $access['sid'];
        }
      }
      $objResponse->addScript("ShowRehashBox_pay('".implode(",", $allservers)."','Активация', 'Ваш ваучер был успешно активирован! Администратор (" . $a_name . ") был успешно добавлен!', 'green', 'index.php?p=account', '".$a_code."');TabToReload();");
    } else
      $objResponse->addScript("ShowBox('Активация', 'Ваш ваучер был успешно активирован! Администратор (" . $a_name . ") был успешно добавлен! Его ключ был:".$a_code."', 'green', 'index.php');TabToReload();");
    
    $log = new CSystemLog("m", "Ваучер", "Ваучер ".$a_code." был успешно активирован! Администратор (" . $a_name . ") был успешно добавлен!");
    return $objResponse;
  }
  else
  {
    $objResponse->addScript("ShowBox('Ваучер', 'Ошибка при активации ваучера. Свяжитесь с главной администрацией, для проверки лога на наличие SQL ошибок.', 'red', 'index.php');");
  }
}


function AddAdmin($mask, $srv_mask, $a_name, $a_steam, $a_email, $a_password, $a_password2,  $a_sg, $a_wg, $a_serverpass, $a_webname, $a_servername, $server, $singlesrv, $a_period, $skype, $comment, $vk)
{
  $objResponse = new xajaxResponse();
  global $userbank, $username;
  if(!$userbank->HasAccess(ADMIN_OWNER|ADMIN_ADD_ADMINS))
  {
    $objResponse->redirect("index.php?p=login&m=no_access", 0);
    $log = new CSystemLog("w", "Ошибка доступа", $username . " пытался добавить админа, не имея на то прав.");
    return $objResponse;
  }
  $vk = str_replace(array("http://","https://","/","vk.com"), "", $vk);
  $a_name = RemoveCode($a_name);
  $a_steam = RemoveCode($a_steam);
  $a_email = RemoveCode($a_email);
  $a_servername = ($a_servername=="0" ? null : RemoveCode($a_servername));
  $a_webname = RemoveCode($a_webname);
  $mask = (int)$mask;

  $error=0;
  
    //No name
  if(empty($a_name))
  {
    $error++;
    $objResponse->addAssign("name.msg", "innerHTML", "Введите имя админа.");
    $objResponse->addScript("$('name.msg').setStyle('display', 'block');");
  }
  else{
    // TODO: Вырезать нахер эти проверки, когда этот PR будет реализован и
    // смерджен в оба плагина:
    // https://github.com/SB-MaterialAdmin/NewServer/pull/102
    if(strstr($a_name, '/'))
    {
      $error++;
      $objResponse->addAssign("name.msg", "innerHTML", "Имя админа не должно содержать символы \" / \".");
      $objResponse->addScript("$('name.msg').setStyle('display', 'block');");
    }
    elseif(strstr($a_name, "'"))
    {
      $error++;
      $objResponse->addAssign("name.msg", "innerHTML", "Имя админа не должно содержать символы \" ' \".");
      $objResponse->addScript("$('name.msg').setStyle('display', 'block');");
    }
    else
    {
      if(is_taken("admins", "user", $a_name))
      {
          $error++;
          $objResponse->addAssign("name.msg", "innerHTML", "Администратор с таким именем уже существует");
          $objResponse->addScript("$('name.msg').setStyle('display', 'block');");
      }
      else
      {
          $objResponse->addAssign("name.msg", "innerHTML", "");
          $objResponse->addScript("$('name.msg').setStyle('display', 'none');");
      }
    }
  }

  // If they didnt type a steamid
  if ($type == 0)
  {
    if(empty($a_steam))
    {
      $error++;
      $objResponse->addAssign("steam.msg", "innerHTML", "Введите Steam ID или Community ID");
      $objResponse->addScript("$('steam.msg').setStyle('display', 'block');");
    }
    else
    {
      try
      {
        $a_steam = \CSteamID::factory($a_steam)->v2;
      }
      catch (\Exception $e)
      {
        $error++;
        $objResponse->addAssign("steam.msg", "innerHTML", "Введите действительный Steam ID или Community ID");
        $objResponse->addScript("$('steam.msg').setStyle('display', 'block');");
      }
    }
  }
  else
  {
    if(is_taken("admins", "authid", $a_steam))
    {
      $admins = $userbank->GetAllAdmins();
      foreach($admins as $admin)
      {
        if($admin['authid'] == $a_steam)
        {
          $name = $admin['user'];
          break;
        }
      }
      $error++;
      $objResponse->addAssign("steam.msg", "innerHTML", "Этот Steam ID уже используется одним из администраторов!");
      $objResponse->addScript("$('steam.msg').setStyle('display', 'block');");
    }
    else
    {
      $objResponse->addAssign("steam.msg", "innerHTML", "");
      $objResponse->addScript("$('steam.msg').setStyle('display', 'none');");
    }
  }
  
  // No email
  if(empty($a_email))
  {
    // An E-Mail address is only required for users with web permissions.
    if($mask != 0)
    {
      $error++;
      $objResponse->addAssign("email.msg", "innerHTML", "Введите адрес e-mail.");
      $objResponse->addScript("$('email.msg').setStyle('display', 'block');");
    }
  }
  else{
    // Is an other admin already registred with that email address?
    if(is_taken("admins", "email", $a_email))
    {
      $admins = $userbank->GetAllAdmins();
      foreach($admins as $admin)
      {
        if($admin['email'] == $a_email)
        {
          $name = $admin['user'];
          break;
        }
      }
      $error++;
      $objResponse->addAssign("email.msg", "innerHTML", "Этот e-mail уже используется админом ".htmlspecialchars(addslashes($name)).".");
      $objResponse->addScript("$('email.msg').setStyle('display', 'block');");
    }
    else
    {
      $objResponse->addAssign("email.msg", "innerHTML", "");
      $objResponse->addScript("$('email.msg').setStyle('display', 'none');");
    /*  if(!validate_email($a_email))
      {
        $error++;
        $objResponse->addAssign("email.msg", "innerHTML", "Please enter a valid email address.");
        $objResponse->addScript("$('email.msg').setStyle('display', 'block');");
      }
      else
      {
        $objResponse->addAssign("email.msg", "innerHTML", "");
        $objResponse->addScript("$('email.msg').setStyle('display', 'none');");

      }*/
    }
  }
  
  // no pass
  if(empty($a_password))
  {
    // A password is only required for users with web permissions.
    if($mask != 0)
    {
      $error++;
      $objResponse->addAssign("password.msg", "innerHTML", "Введите пароль.");
      $objResponse->addScript("$('password.msg').setStyle('display', 'block');");
    }
  }
  // Password too short?
  else if(strlen($a_password) < MIN_PASS_LENGTH)
  {
    $error++;
    $objResponse->addAssign("password.msg", "innerHTML", "Длина пароля должна быть не менее " . MIN_PASS_LENGTH . " символов.");
    $objResponse->addScript("$('password.msg').setStyle('display', 'block');");
  }
  else 
  {
    $objResponse->addAssign("password.msg", "innerHTML", "");
    $objResponse->addScript("$('password.msg').setStyle('display', 'none');");
    
    // No confirmation typed
    if(empty($a_password2))
    {
      $error++;
      $objResponse->addAssign("password2.msg", "innerHTML", "Подтвердите пароль");
      $objResponse->addScript("$('password2.msg').setStyle('display', 'block');");
    }
    // Passwords match?
    else if($a_password != $a_password2)
    {
      $error++;
      $objResponse->addAssign("password2.msg", "innerHTML", "Пароли не совпадают");
      $objResponse->addScript("$('password2.msg').setStyle('display', 'block');");
    }
    else
    {
      $objResponse->addAssign("password2.msg", "innerHTML", "");
      $objResponse->addScript("$('password2.msg').setStyle('display', 'none');");
    }
  }

  // Choose to use a server password
  if($a_serverpass != "-1")
  {
    // No password given?
    if(empty($a_serverpass))
    {
      $error++;
      $objResponse->addAssign("a_serverpass.msg", "innerHTML", "Введите пароль сервера, либо снимите галочку.");
      $objResponse->addScript("$('a_serverpass.msg').setStyle('display', 'block');");
    }
    // Password too short?
    else if(strlen($a_serverpass) < MIN_PASS_LENGTH)
    {
      $error++;
      $objResponse->addAssign("a_serverpass.msg", "innerHTML", "Длина пароля должна быть не менее " . MIN_PASS_LENGTH . " символов.");
      $objResponse->addScript("$('a_serverpass.msg').setStyle('display', 'block');");
    }
    else 
    {
      $objResponse->addAssign("a_serverpass.msg", "innerHTML", "");
      $objResponse->addScript("$('a_serverpass.msg').setStyle('display', 'none');");
    }
  }
  else
  {
    $objResponse->addAssign("a_serverpass.msg", "innerHTML", "");
    $objResponse->addScript("$('a_serverpass.msg').setStyle('display', 'none');");
    // Don't set "-1" as password ;)
    $a_serverpass = "";
  }
  
    // didn't choose a server group
    if($a_sg == "-2")
    {
        $error++;
        $objResponse->addAssign("server.msg", "innerHTML", "Выберите группу.");
        $objResponse->addScript("$('server.msg').setStyle('display', 'block');");
    }
    else
    {
        $objResponse->addAssign("server.msg", "innerHTML", "");
        $objResponse->addScript("$('server.msg').setStyle('display', 'none');");
    }
  
  // chose to create a new server group
  if($a_sg == 'n')
  {
    // didn't type a name
    if(empty($a_servername))
    {
      $error++;
      $objResponse->addAssign("servername_err", "innerHTML", "Введите имя новой группы.");
      $objResponse->addScript("$('servername_err').setStyle('display', 'block');");
    }
    // Group names can't contain ,
    else if(strstr($a_servername, ','))
    {
      $error++;
      $objResponse->addAssign("servername_err", "innerHTML", "Имя группы не может содержать запятую.");
      $objResponse->addScript("$('servername_err').setStyle('display', 'block');");
    }
    else
    {
      $objResponse->addAssign("servername_err", "innerHTML", "");
      $objResponse->addScript("$('servername_err').setStyle('display', 'none');");
    }
  }
  
  // didn't choose a web group
    if($a_wg == "-2")
  {
        $error++;
        $objResponse->addAssign("web.msg", "innerHTML", "Выберите группу.");
        $objResponse->addScript("$('web.msg').setStyle('display', 'block');");
    }
    else
    {
        $objResponse->addAssign("web.msg", "innerHTML", "");
        $objResponse->addScript("$('web.msg').setStyle('display', 'none');");
    }
    
  // Choose to create a new webgroup
  if($a_wg == 'n')
  {
    // But didn't type a name
    if(empty($a_webname))
    {
      $error++;
      $objResponse->addAssign("webname_err", "innerHTML", "Введите имя новой группы.");
      $objResponse->addScript("$('webname_err').setStyle('display', 'block');");
    }
    // Group names can't contain ,
    else if(strstr($a_webname, ','))
    {
      $error++;
      $objResponse->addAssign("webname_err", "innerHTML", "Имя группы не может содержать запятую.");
      $objResponse->addScript("$('webname_err').setStyle('display', 'block');");
    }
    else
    {
      $objResponse->addAssign("webname_err", "innerHTML", "");
      $objResponse->addScript("$('webname_err').setStyle('display', 'none');");
    }
  }
  
  // Проверка срока админки
  if(!preg_match("#^([0-9]+)$#i",$a_period))
  {
    $error++;
    $objResponse->addAssign("a_period.msg", "innerHTML", "Только цифры.");
    $objResponse->addScript("$('a_period.msg').setStyle('display', 'block');");
  }
  else 
  {
    $objResponse->addAssign("a_period.msg", "innerHTML", "");
    $objResponse->addScript("$('a_period.msg').setStyle('display', 'none');");
  }
  
  // Ohnoes! something went wrong, stop and show errs
  if($error)
  {
    ShowBox_ajx("Ошибка", "Допущены ошибки. Пожалуйста, исправьте их.", "red", "", true, $objResponse);
    return $objResponse;
  }

// ##############################################################
// ##                     Start adding to DB                   ##
// ##############################################################
  
  $gid = 0;
  $groupID = 0;
  $inGroup = false;
  $wgid = NextAid();
  $immunity = 0;
  $a_period = intval($a_period);
  
  // Extract immunity from server mask string
  if(strstr($srv_mask, "#"))
  {
    $immunity = "0";
    $immunity = substr($srv_mask, strpos($srv_mask, "#")+1);
    $srv_mask = substr($srv_mask, 0, strlen($srv_mask) - strlen($immunity)-1);
  }
  
  // Avoid negative immunity
  $immunity = ($immunity>0) ? $immunity : 0;
  
  // Handle Webpermissions
  // Chose to create a new webgroup
  if($a_wg == 'n')
  {
    $add_webgroup = $GLOBALS['db']->Execute("INSERT INTO ".DB_PREFIX."_groups(type, name, flags)
                    VALUES (?,?,?)", array(1, $a_webname, $mask));
    $web_group = (int)$GLOBALS['db']->Insert_ID();
    
    // We added those permissons to the group, so don't add them as custom permissions again
    $mask = 0;
  }
  // Chose an existing group
  else if($a_wg != 'c' && $a_wg > 0)
  {
    $web_group = (int)$a_wg;
  }
  // Custom permissions -> no group
  else
  {
    $web_group = -1;
  }
  
  // Handle Serverpermissions
  // Chose to create a new server admin group
  if($a_sg == 'n')
  {
    $add_servergroup = $GLOBALS['db']->Execute("INSERT INTO ".DB_PREFIX."_srvgroups(immunity, flags, name, groups_immune)
          VALUES (?,?,?,?)", array($immunity, $srv_mask, $a_servername, " "));
    
    $server_admin_group = $a_servername;
    $server_admin_group_int = (int)$GLOBALS['db']->Insert_ID();
    
    // We added those permissons to the group, so don't add them as custom permissions again
    $srv_mask = "";
  }
  // Chose an existing group
  else if($a_sg != 'c' && $a_sg > 0)
  {
    $server_admin_group = $GLOBALS['db']->GetOne("SELECT `name` FROM ".DB_PREFIX."_srvgroups WHERE id = '" . (int)$a_sg . "'");
    $server_admin_group_int = (int)$a_sg;
  }
  // Custom permissions -> no group
  else
  {
    $server_admin_group = "";
    $server_admin_group_int = -1;
  }
  
  // Срок админки
  if($a_period == 0) {
    $period = 0;
  }
  else {
    $period = $a_period * 86400 + time();
  }

  
  // Add the admin
  $aid = $userbank->AddAdmin($a_name, $a_steam, $a_password, $a_email, $web_group, $mask, $server_admin_group, $srv_mask, $immunity, $a_serverpass, $period, $skype, $comment, $vk);
  
  if($aid > -1)
  {
    // Grant permissions to the selected server groups
    $srv_groups = explode(",", $server);
    $addtosrvgrp = $GLOBALS['db']->Prepare("INSERT INTO ".DB_PREFIX."_admins_servers_groups(admin_id,group_id,srv_group_id,server_id) VALUES (?,?,?,?)");
    foreach($srv_groups AS $srv_group)
    {
      if(!empty($srv_group))
        $GLOBALS['db']->Execute($addtosrvgrp,array($aid, $server_admin_group_int, substr($srv_group, 1), '-1'));
    }
    
    // Grant permissions to individual servers
    $srv_arr = explode(",", $singlesrv);
    $addtosrv = $GLOBALS['db']->Prepare("INSERT INTO ".DB_PREFIX."_admins_servers_groups(admin_id,group_id,srv_group_id,server_id) VALUES (?,?,?,?)");
    foreach($srv_arr AS $server)
    {
      if(!empty($server))
        $GLOBALS['db']->Execute($addtosrv,array($aid, $server_admin_group_int, '-1', substr($server, 1)));
    }
    if(isset($GLOBALS['config']['config.enableadminrehashing']) && $GLOBALS['config']['config.enableadminrehashing'] == 1)
    {
      // rehash the admins on the servers
      $serveraccessq = $GLOBALS['db']->GetAll("SELECT s.sid FROM `".DB_PREFIX."_servers` s
                        LEFT JOIN `".DB_PREFIX."_admins_servers_groups` asg ON asg.admin_id = '".(int)$aid."'
                        LEFT JOIN `".DB_PREFIX."_servers_groups` sg ON sg.group_id = asg.srv_group_id
                        WHERE ((asg.server_id != '-1' AND asg.srv_group_id = '-1')
                        OR (asg.srv_group_id != '-1' AND asg.server_id = '-1'))
                        AND (s.sid IN(asg.server_id) OR s.sid IN(sg.server_id)) AND s.enabled = 1");
      $allservers = array();
      foreach($serveraccessq as $access) {
        if(!in_array($access['sid'], $allservers)) {
          $allservers[] = $access['sid'];
        }
      }
      $objResponse->addScript("ShowRehashBox('".implode(",", $allservers)."','Админ добавлен', 'Админ успешно добавлен', 'green', 'index.php?p=admin&c=admins');TabToReload();");
    } else
      $objResponse->addScript("ShowBox('Админ добавлен', 'Админ успешно добавлен', 'green', 'index.php?p=admin&c=admins');TabToReload();");
    
    $log = new CSystemLog("m", "Админ добавлен", "Админ (" . $a_name . ") добавлен");
    return $objResponse;
  }
  else
  {
    $objResponse->addScript("ShowBox('Пользователь не добавлен', 'Ошибка при добавлении админа в базу данных. Проверьте лог на наличие SQL ошибок.', 'red', 'index.php?p=admin&c=admins');");
  }
}

function ServerHostPlayers($sid, $type="servers", $obId="", $tplsid="", $open="", $inHome=false, $trunchostname=48)
{
  $objResponse = new xajaxResponse();
  global $userbank;
  
  $sid = (int)$sid;

  //$res = $GLOBALS['db']->GetRow("SELECT sid, ip, port FROM ".DB_PREFIX."_servers WHERE sid = $sid");
  $res = $GLOBALS['db']->GetRow("SELECT se.sid, se.ip, se.port, se.modid, md.modfolder FROM ".DB_PREFIX."_servers se LEFT JOIN ".DB_PREFIX."_mods md ON md.mid=se.modid WHERE se.sid = $sid");
  if(empty($res[1]) || empty($res[2]))
    return $objResponse;
  $info = array();
  $sinfo = new CServerControl(intval($GLOBALS['config']['gamecache.enabled']) == 1);
  $sinfo->Connect($res[1], $res[2]);
  $info = $sinfo->GetInfo();
  if($type == "servers") {
    if($info) {
      $objResponse->addAssign("host_$sid", "innerHTML", trunc($info['HostName'], $trunchostname, false));
      $objResponse->addAssign("players_$sid", "innerHTML", $info['Players'] . "/" . $info['MaxPlayers']);
      $objResponse->addAssign("os_$sid", "innerHTML", "<img src='images/" . (!empty($info['Os'])?$info['Os']:'server_small') . ".png'>");
      if($info['Secure'])
        $objResponse->addAssign("vac_$sid", "innerHTML", "<img src='images/shield.png' />");
      else
        $objResponse->addAssign("vac_$sid", "innerHTML", "<img src='images/noshield.png' />");
      $objResponse->addAssign("map_$sid", "innerHTML", basename($info['Map'])); // Strip Steam Workshop folder
      if(!$inHome) {
        $objResponse->addScript("$('mapimg_$sid').setProperty('src', '".GetMapImage(basename($info['Map']), $res[4])."').setProperty('alt', '".$info['Map']."').setProperty('title', '".basename($info['Map'])."');");
        $objResponse->addAssign("mapimg_$sid", "innerHTML", GetMapImage(basename($info['Map']), $res[4]));
        if($info['Players'] == 0) {
          $objResponse->addScript("$('sinfo_$sid').setStyle('display', 'none');");
          $objResponse->addScript("$('noplayer_$sid').setStyle('display', 'block');");
          $objResponse->addScript("$('serverwindow_$sid').setStyle('height', '64px');");
        } else {
          $objResponse->addScript("$('sinfo_$sid').setStyle('display', 'block');");
          $objResponse->addScript("$('noplayer_$sid').setStyle('display', 'none');");
          if(!defined('IN_HOME')) {
            $players = $sinfo->GetPlayers();
            if ($players !== false) {
              // remove childnodes
              $objResponse->addScript('var toempty = document.getElementById("playerlist_'.$sid.'");
              var empty = toempty.cloneNode(false);
              toempty.parentNode.replaceChild(empty,toempty);');
              //draw table headlines
              $objResponse->addScript('var e = document.getElementById("playerlist_'.$sid.'");
              var tr = e.insertRow("-1");
                // Name Top TD
                var td = tr.insertCell("-1");
                  td.setAttribute("width","50%");
                  //td.setAttribute("height","16");
                  td.className = "text-center p-5 bgm-bluegray c-white";
                    var b = document.createElement("b");
                    var txt = document.createTextNode("Имя");
                    b.appendChild(txt);
                  td.appendChild(b);
                // Score Top TD
                var td = tr.insertCell("-1");
                  td.setAttribute("width","15%");
                  //td.setAttribute("height","16");
                  td.className = "p-5 bgm-bluegray c-white";
                    var b = document.createElement("b");
                    var txt = document.createTextNode("Счет");
                    b.appendChild(txt);
                  td.appendChild(b);
                // Time Top TD
                var td = tr.insertCell("-1");
                  //td.setAttribute("height","16");
                  td.className = "p-5 bgm-bluegray c-white";
                    var b = document.createElement("b");
                    var txt = document.createTextNode("Время");
                    b.appendChild(txt);
                  td.appendChild(b);');
              // add players
              $playercount = 0;
              
              $needAddPlayerManaging = (($userbank->HasAccess(ADMIN_OWNER|ADMIN_ADD_BAN) && $GLOBALS['db']->GetOne(sprintf("SELECT COUNT(*) FROM `%s_admins_servers_groups` WHERE `admin_id` = %d AND `server_id` = %d", DB_PREFIX, $userbank->GetAid(), (int)$sid)) == 1) || $userbank->HasAccess(ADMIN_OWNER));
              
              if($needAddPlayerManaging) {
                $dl = "a";
                $dl2 = 'var i_i = document.createElement("i");
                    //var img = document.createElement("img");
                    //img.src = "theme/img/inn.png";
                    //img.className = "m-r-5";
                    i_i.className = "zmdi zmdi-label c-lightblue p-r-10 p-l-5";
                    i_i.style = "font-size: 17px;";
                    //img.style.width = "20px";
                    //img.style.height = "20px";
                    a.appendChild(i_i);
                    td.appendChild(a);
                    ';
                $dl_fix = 'p-l-5 ';
              }else{
                $dl = "span";
                $dl2 = "";
                $dl_fix = 'p-l-10 ';
              }
              $id = 0;
              foreach($players as $player) {
                if (empty($player['Name'])) continue;
                $id++;
                $objResponse->addScript('var e = document.getElementById("playerlist_'.$sid.'");
                            var tr = e.insertRow("-1");
                            tr.id = "player_s'.$sid.'p'.$id.'";
                              // Name TD
                              var td = tr.insertCell("-1");
                                td.className = "'.$dl_fix.'p-t-5";
                                  var txt = document.createTextNode("'.str_replace('"', '\"', $player["Name"]).'");
                                  var a = document.createElement("'.$dl.'");
                                  a.href = "#player_s' . $sid . 'p' . $id . '_t";
                                  var att = document.createAttribute("data-toggle");
                                  att.value = "modal"; 
                                  a.setAttributeNode(att);
                                  '.$dl2.'
                                td.appendChild(txt);
                              // Score TD
                              var td = tr.insertCell("-1");
                                td.className = "listtable_1";
                                var txt = document.createTextNode("'.$player["Frags"].'");
                                td.appendChild(txt);
                              // Time TD
                              var td = tr.insertCell("-1");
                                td.className = "p-l-10";
                                var txt = document.createTextNode("'.SecondsToString($player['Time']).'");
                                td.appendChild(txt);
                              ');
                if($needAddPlayerManaging) {
                  $objResponse->addScript('
                    var div = document.createElement("div");
                    div.className = "modal fade";
                    div.id = "player_s' . $sid . 'p' . $id . '_t";
                    var att = document.createAttribute("tabindex");
                    var att1 = document.createAttribute("role");
                    var att2 = document.createAttribute("aria-hidden");
                    att.value = "-1"; 
                    att1.value = "dialog"; 
                    att2.value = "true"; 
                    div.setAttributeNode(att);   
                    div.setAttributeNode(att1);   
                    div.setAttributeNode(att2);   
                    div.innerHTML = "\
                      <div class=\'modal-dialog modal-sm\'>\
                        <div class=\'modal-content\'>\
                          <div class=\'modal-header\'>\
                            <h4 class=\'modal-title\'>'.str_replace('"', '\"', $player["Name"]).'</h4>\
                          </div>\
                          <div class=\'modal-body\'>\
                            <p class=\"m-b-10\"><button class=\"btn btn-link btn-block\" data-dismiss=\"modal\" onclick=\"KickPlayerConfirm('.$sid.', \''.str_replace('"', '"', $player["Name"]).'\', 0);\">Кикнуть</button></p>\
                            <p class=\"m-b-10\"><button class=\"btn btn-link btn-block\" href=\"#\" data-dismiss=\'modal\' onclick=\"ViewCommunityProfile('.$sid.', \''.str_replace('"', '\"', $player["Name"]).'\');\">Профиль</button></p>\
                            <p class=\"m-b-10\"><a href=\"index.php?p=admin&c=bans&action=pasteBan&sid='.$sid.'&pName='.str_replace('"', '\"', $player["Name"]).'\"><button class=\"btn btn-link btn-block\">Бан</button></a></p>\
                            <p class=\"m-b-10\"><a href=\"index.php?p=admin&c=comms&action=pasteBan&sid='.$sid.'&pName='.str_replace('"', '\"', $player["Name"]).'\"><button class=\"btn btn-link btn-block\">Заглушить</button></a></p>\
                            <p class=\"m-b-10\"><button class=\"btn btn-link btn-block\" href=\"#\" data-dismiss=\'modal\' onclick=\"OpenMessageBox('.$sid.', \''.str_replace('"', '\"', $player["Name"]).'\', 1);\">Отправить сообщение</button></p>\
                          </div>\
                          <!--<div class=\'modal-footer\'>\
                            <button type=\'button\' class=\'btn btn-link\' data-dismiss=\'modal\'>Отмена</button>\
                          </div>-->\
                        </div>\
                      </div>\
                    ";

                    document.body.appendChild(div);');
                }
                $playercount++;
              }
            }
          }
          if($playercount>15)
            $height = 329 + 16 * ($playercount-15) + 4 * ($playercount-15) . "px";
          else
            $height = 329 . "px";
          //$objResponse->addScript("$('serverwindow_$sid').setStyle('height', '".$height."');");
        }
      }
    }else{
      if($userbank->HasAccess(ADMIN_OWNER))
        $objResponse->addAssign("host_$sid", "innerHTML", "<b>Ошибка соединения</b> (<i>" . $res[1] . ":" . $res[2]. "</i>) <small><a href=\"http://hlmod.ru/posts/290247/\" title=\"Какие порты должны быть открыты в ВЕБ панели SourceBans?\">Помощь</a></small>");
      else
        $objResponse->addAssign("host_$sid", "innerHTML", "<b>Ошибка соединения</b> (<i>" . $res[1] . ":" . $res[2]. "</i>)");
      $objResponse->addAssign("players_$sid", "innerHTML", "Н/Д");
      $objResponse->addAssign("os_$sid", "innerHTML", "Н/Д");
      $objResponse->addAssign("vac_$sid", "innerHTML", "Н/Д");
      $objResponse->addAssign("map_$sid", "innerHTML", "Н/Д");
      if(!$inHome) {
        $connect = "onclick = \"document.location = 'steam://connect/" .  $res['ip'] . ":" . $res['port'] . "'\"";
        $objResponse->addScript("$('sinfo_$sid').setStyle('display', 'none');");
        $objResponse->addScript("$('noplayer_$sid').setStyle('display', 'block');");
        $objResponse->addScript("$('serverwindow_$sid').setStyle('height', '64px');");
        $objResponse->addScript("if($('sid_$sid'))$('sid_$sid').setStyle('color', '#adadad');");
      }
    }
    if($tplsid != "" && $open != "" && $tplsid==$open)
      $objResponse->addScript("InitAccordion('tr.opener', 'div.opener', 'content', '".$open."');");
    //$objResponse->addScript("$('dialog-control').setStyle('display', 'block');");
    $objResponse->addScript("$('dialog-placement').setStyle('display', 'none');");
  }
  elseif($type=="id")
  {
    if($info)
    {
      $objResponse->addAssign("$obId", "innerHTML", trunc($info['HostName'], $trunchostname, false));
    }else{
      $objResponse->addAssign("$obId", "innerHTML", "<b>!!!</b> <i>Ошибка соединения</i> (<i>" . $res[1] . ":" . $res[2]. "</i>) <b>!!!</b>");
    }
  }
  else
  {
    if($info)
    {
      $objResponse->addAssign("ban_server_$type", "innerHTML", trunc($info['HostName'], $trunchostname, false));
    }else{
      $objResponse->addAssign("ban_server_$type", "innerHTML", "<b>!!!</b> <i>Ошибка соединения</i> (<i>" . $res[1] . ":" . $res[2]. "</i>) <b>!!!</b>");
    }
  }
  return $objResponse;
}

function ServerHostProperty($sid, $obId, $obProp, $trunchostname)
{
    $objResponse = new xajaxResponse();
  global $userbank;
  
  $sid = (int)$sid;
    $obId = htmlspecialchars($obId);
    $obProp = htmlspecialchars($obProp);
    $trunchostname = (int)$trunchostname;

  $res = $GLOBALS['db']->GetRow("SELECT ip, port FROM ".DB_PREFIX."_servers WHERE sid = $sid");
  if(empty($res[0]) || empty($res[1]))
    return $objResponse;
  $info = array();
  
  $sinfo = new CServerControl(intval($GLOBALS['config']['gamecache.enabled']) == 1);
  $sinfo->Connect($res[0], $res[1]);
  $info = $sinfo->GetInfo();
    
    if($info) {
        $objResponse->addAssign("$obId", "$obProp", addslashes(trunc($info['HostName'], $trunchostname, false)));
    } else {
        $objResponse->addAssign("$obId", "$obProp", "Ошибка соединения (" . $res[0] . ":" . $res[1]. ")");
    }
    return $objResponse;
}

function ServerHostPlayers_list($sid, $type="servers", $obId="")
{
  $objResponse = new xajaxResponse();

  $sids = explode(";", $sid, -1);
  if(count($sids) < 1)
    return $objResponse;

  $ret = "";
  for($i=0;$i<count($sids);$i++)
  {
    $sid = (int)$sids[$i];

    $res = $GLOBALS['db']->GetRow("SELECT sid, ip, port FROM ".DB_PREFIX."_servers WHERE sid = $sid");
    if(empty($res[1]) || empty($res[2]))
      return $objResponse;
    $info = array();
    $sinfo = new CServerControl(intval($GLOBALS['config']['gamecache.enabled']) == 1);
    $sinfo->Connect($res[1], $res[2]);
    $info = $sinfo->GetInfo();

    if($info)
      $ret .= trunc($info['HostName'], 48, false) . "<br />";
    else
      $ret .= "<b>Ошибка соединения</b> (<i>" . $res[1] . ":" . $res[2]. "</i>) <br />";
    
  }

  if($type=="id")
  {
    $objResponse->addAssign("$obId", "innerHTML", $ret);
  }
  else
  {
    $objResponse->addAssign("ban_server_$type", "innerHTML", $ret);
  }

  return $objResponse;
}


function ServerPlayers($sid)
{
  $objResponse = new xajaxResponse();

  $sid = (int)$sid;

  $res = $GLOBALS['db']->GetRow("SELECT sid, ip, port FROM ".DB_PREFIX."_servers WHERE sid = $sid");
  if(empty($res[1]) || empty($res[2]))
  {
    $objResponse->addAlert('IP или порт не назначен :o');
    return $objResponse;
  }
  $info = array();
  $sinfo = new CServerControl(intval($GLOBALS['config']['gamecache.enabled']) == 1);
  $sinfo->Connect($res[1], $res[2]);
  $info = $sinfo->GetPlayers();

  $html = "";
  if(empty($info))
    return $objResponse;
  foreach($info AS $player) {
    $html .= '<tr> <td class="listtable_1">'.htmlentities($player['Name']).'</td>
            <td class="listtable_1">'.(int)$player['Frags'].'</td>
            <td class="listtable_1">'.$player['TimeF'].'</td>
          </tr>';
  }
  $objResponse->addAssign("player_detail_$sid", "innerHTML", $html);
  //$objResponse->addScript("document.getElementById('player_detail_$sid').innerHTML = 'hi';");
  $objResponse->addScript("setTimeout('xajax_ServerPlayers($sid)', 5000);");
  $objResponse->addScript("$('opener_$sid').setProperty('onclick', '');");
  return $objResponse;
}

function KickPlayer($sid, $name)
{
  $objResponse = new xajaxResponse();
  global $userbank, $username;
  $sid = (int)$sid;
  
  //$objResponse->addScript("$('dialog-control').setStyle('display', 'block');");
    
  if(!$userbank->HasAccess(ADMIN_OWNER|ADMIN_ADD_BAN))
  {
    $objResponse->redirect("index.php?p=login&m=no_access", 0);
    $log = new CSystemLog("w", "Ошибка доступа", $username . " пытался кикнуть ".htmlspecialchars($name).", не имея на это прав.");
    return $objResponse;
  }

  //get the server data
  $data = $GLOBALS['db']->GetRow("SELECT ip, port, rcon FROM ".DB_PREFIX."_servers WHERE sid = '".$sid."';");
  if(empty($data['rcon'])) {
    $objResponse->addScript("ShowBox('Ошибка', 'Невозможно кикнуть ".addslashes(htmlspecialchars($name)).". Не задан РКОН пароль!', 'red', '', true);");
    return $objResponse;
  }
  
  $r = new CServerControl(intval($GLOBALS['config']['gamecache.enabled']) == 1);
  $r->Connect($data['ip'], $data['port']);

  if(!$r->AuthRcon($data['rcon']))
  {
    $GLOBALS['db']->Execute("UPDATE ".DB_PREFIX."_servers SET rcon = '' WHERE sid = '".$sid."';");
    $objResponse->addScript("ShowBox('Ошибка', 'Невозможно кикнуть ".addslashes(htmlspecialchars($name)).". Неверный РКОН пароль!', 'red', '', true);");
    return $objResponse;
  }
  // search for the playername
  $ret = $r->SendCommand("status");
  $search = preg_match_all(STATUS_PARSE,$ret,$matches,PREG_PATTERN_ORDER);
  $i = 0;
  $found = false;
  $index = -1;
  foreach($matches[2] AS $match) {
    if($match == $name) {
      $found = true;
      $index = $i;
      break;
    }
    $i++;
  }
  if($found) {
    $steam = $matches[3][$index];
    $steam2 = $steam;
    // Hack to support steam3 [U:1:X] representation.
    if(strpos($steam, "[U:") === 0) {
      $steam2 = renderSteam2(getAccountId($steam), 0);
    }
    // check for immunity
    $admin = $GLOBALS['db']->GetRow("SELECT a.immunity AS pimmune, g.immunity AS gimmune FROM `".DB_PREFIX."_admins` AS a LEFT JOIN `".DB_PREFIX."_srvgroups` AS g ON g.name = a.srv_group WHERE authid = '".$steam2."' LIMIT 1;");
    if($admin && $admin['gimmune']>$admin['pimmune'])
      $immune = $admin['gimmune'];
    elseif($admin)
      $immune = $admin['pimmune'];
    else
      $immune = 0;

    if($immune <= $userbank->GetProperty('srv_immunity')) {
      $requri = substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], ".php")+4);
      
      if(strpos($steam, "[U:") === 0) {
        $kick = $r->sendCommand("kickid \"".$steam."\" \"Вы были кикнуты с сервера. Перейдите по адресу http://" . $_SERVER['HTTP_HOST'].$requri." для большей информации.\"");
      } else {
        $kick = $r->sendCommand("kickid ".$steam." \"Вы были кикнуты с сервера. Перейдите по адресу http://" . $_SERVER['HTTP_HOST'].$requri." для большей информации.\"");
      }

      $log = new CSystemLog("m", "Игрок кикнут", $username . " кикнул игрока '".htmlspecialchars($name)."' (".$steam.") from ".$data['ip'].":".$data['port'].".", true, true);
      $objResponse->addScript("ShowBox('Игрок кикнут', 'Игрок \'".addslashes(htmlspecialchars($name))."\' был кикнут с сервера.', 'green', 'index.php?p=servers', 1500);$('dialog-control').setStyle('display', 'none');");
    } else {
      $objResponse->addScript("ShowBox('Ошибка', 'Невозможно кикнуть ".addslashes(htmlspecialchars($name)).". У него иммунитет!', 'red', '', true);");
    }
  } else {
    $objResponse->addScript("ShowBox('Ошибка', 'Невозможно кикнуть ".addslashes(htmlspecialchars($name)).". Игрок покинул сервер!', 'red', '', true);");
  }
  return $objResponse;
}

function AddBan($nickname, $type, $steam, $ip, $length, $dfile, $dname, $reason, $fromsub, $udemo=false)
{
  $objResponse = new xajaxResponse();
  global $userbank, $username;
  if(!$userbank->HasAccess(ADMIN_OWNER|ADMIN_ADD_BAN))
  {
    $objResponse->redirect("index.php?p=login&m=no_access", 0);
    $log = new CSystemLog("w", "Ошибка доступа", $username . " пытался добавить бан, не имея на то прав.");
    return $objResponse;
  }
  
  $steam = trim($steam);
  
  $error = 0;
  // If they didnt type a steamid
  if ($type == 0)
  {
    if(empty($steam))
    {
      $error++;
      $objResponse->addAssign("steam.msg", "innerHTML", "Введите Steam ID или Community ID");
      $objResponse->addScript("$('steam.msg').setStyle('display', 'block');");
    }
    else
    {
      try
      {
        $steam = \CSteamID::factory($steam)->v2;
      }
      catch (\Exception $e)
      {
        $error++;
        $objResponse->addAssign("steam.msg", "innerHTML", "Введите действительный Steam ID или Community ID");
        $objResponse->addScript("$('steam.msg').setStyle('display', 'block');");
      }
    }
  }
  else if (empty($ip) && $type == 1)
  {
    $error++;
    $objResponse->addAssign("ip.msg", "innerHTML", "Введите IP");
    $objResponse->addScript("$('ip.msg').setStyle('display', 'block');");
  }
  else if($type == 1 && !validate_ip($ip))
  {
    $error++;
    $objResponse->addAssign("ip.msg", "innerHTML", "Введите действительный IP");
    $objResponse->addScript("$('ip.msg').setStyle('display', 'block');");
  }
  else
  {
    $objResponse->addAssign("steam.msg", "innerHTML", "");
    $objResponse->addScript("$('steam.msg').setStyle('display', 'none');");
    $objResponse->addAssign("ip.msg", "innerHTML", "");
    $objResponse->addScript("$('ip.msg').setStyle('display', 'none');");
  }
  if ($udemo && ! checkdnsrr($udemo,'A') && ! @get_headers($udemo, 1)){
    $error++;
    $objResponse->addAssign("demo_link.msg", "innerHTML", "Введите действительный URL к демо файлу, либо оставьте поле пустым!");
    $objResponse->addScript("$('demo_link.msg').setStyle('display', 'block');");
  }
  
  if($error > 0)
    return $objResponse;

  $nickname = RemoveCode($nickname);
  $ip = preg_replace('#[^\d\.]#', '', $ip);//strip ip of all but numbers and dots
  $dname = RemoveCode($dname);
  $reason = RemoveCode($reason);
  if(!$length)
    $len = 0;
  else
    $len = $length*60;

  // prune any old bans
  PruneBans();
  if((int)$type==0) {
    // Check if the new steamid is already banned
    $chk = $GLOBALS['db']->GetRow("SELECT count(bid) AS count FROM ".DB_PREFIX."_bans WHERE authid = ? AND (length = 0 OR ends > UNIX_TIMESTAMP()) AND RemovedBy IS NULL AND type = '0'", array($steam));

    if(intval($chk[0]) > 0)
    {
      $objResponse->addScript("ShowBox('Ошибка', 'SteamID: $steam уже забанен.', 'red', '', true);");
      return $objResponse;
    }
        
        // Check if player is immune
        $admchk = $userbank->GetAllAdmins();
        foreach($admchk as $admin)
            if($admin['authid'] == $steam && $userbank->GetProperty('srv_immunity') < $admin['srv_immunity'])
            {
                $objResponse->addScript("ShowBox('Ошибка', 'SteamID: админ ".$admin['user']." ($steam) под иммунитетом.', 'red', '', true);");
                return $objResponse;
            }
  }
  if((int)$type==1) {
    $chk = $GLOBALS['db']->GetRow("SELECT count(bid) AS count FROM ".DB_PREFIX."_bans WHERE ip = ? AND (length = 0 OR ends > UNIX_TIMESTAMP()) AND RemovedBy IS NULL AND type = '1'", array($ip));

    if(intval($chk[0]) > 0)
    {
      $objResponse->addScript("ShowBox('Ошибка', 'Этот IP ($ip) уже забанен.', 'red', '', true);");
      return $objResponse;
    }
  }

  $pre = $GLOBALS['db']->Prepare("INSERT INTO ".DB_PREFIX."_bans(created,type,ip,authid,name,ends,length,reason,aid,adminIp ) VALUES
                  (UNIX_TIMESTAMP(),?,?,?,?,(UNIX_TIMESTAMP() + ?),?,?,?,?)");
  $GLOBALS['db']->Execute($pre,array($type,
                     $ip,
                     $steam,
                     $nickname,
                     $length*60,
                     $len,
                     $reason,
                     $userbank->GetAid(),
                     $_SERVER['REMOTE_ADDR']));
  $subid = $GLOBALS['db']->Insert_ID();

  if($dname && $dfile && preg_match('/^[a-z0-9]*$/i', $dfile))
  //Thanks jsifuentes: http://jacobsifuentes.com/sourcebans-1-4-lfi-exploit/
  //Official Fix: https://code.google.com/p/sourcebans/source/detail?r=165
  {
    $GLOBALS['db']->Execute("INSERT INTO ".DB_PREFIX."_demos(demid,demtype,filename,origname)
                 VALUES(?,'B', ?, ?)", array((int)$subid, $dfile, $dname));
  }elseif(!$dname && !$dfile && $udemo){
    $GLOBALS['db']->Execute("INSERT INTO ".DB_PREFIX."_demos(demid,demtype,filename,origname)
                 VALUES(?,'U', '', ?)", array((int)$subid, $udemo));
  }
  if($fromsub) {
    $submail = $GLOBALS['db']->Execute("SELECT name, email FROM ".DB_PREFIX."_submissions WHERE subid = '" . (int)$fromsub . "'");
    // Send an email when ban is accepted
    $requri = substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], ".php")+4);
    $headers = 'From: submission@' . $_SERVER['HTTP_HOST'] . "\n" .
    'X-Mailer: PHP/' . phpversion();

    $message = "Привет,\n";
    $message .= "Ваша заявка на бан подтверждена админом.\nПерейдите по ссылке, чтобы посмотреть банлист.\n\nhttp://" . $_SERVER['HTTP_HOST'] . $requri . "?p=banlist";

    EMail($submail->fields['email'], "[SourceBans] Бан добавлен", $message, $headers);
    $GLOBALS['db']->Execute("UPDATE `" . DB_PREFIX . "_submissions` SET archiv = '2', archivedby = '".$userbank->GetAid()."' WHERE subid = '" . (int)$fromsub . "'");
  }

  $GLOBALS['db']->Execute("UPDATE `".DB_PREFIX."_submissions` SET archiv = '3', archivedby = '".$userbank->GetAid()."' WHERE SteamId = ?;", array($steam));

  $kickit = isset($GLOBALS['config']['config.enablekickit']) && $GLOBALS['config']['config.enablekickit'] == "1";
  if ($kickit)
    $objResponse->addScript("ShowKickBox('".((int)$type==0?$steam:$ip)."', '".(int)$type."');");
  else
    $objResponse->addScript("ShowBox('Бан добавлен', 'Бан успешно добавлен', 'green', 'index.php?p=admin&c=bans');");

  $objResponse->addScript("TabToReload();");
  $log = new CSystemLog("m", "Бан добавлен", "Бан против (" . ((int)$type==0?$steam:$ip) . ") был добавлен, причина: $reason, срок: $length", true, $kickit);
  return $objResponse;
}

function SetupBan($subid)
{
  $objResponse = new xajaxResponse();
  $subid = (int)$subid;

  $ban = $GLOBALS['db']->GetRow("SELECT * FROM ".DB_PREFIX."_submissions WHERE subid = $subid");
  $demo = $GLOBALS['db']->GetRow("SELECT * FROM ".DB_PREFIX."_demos WHERE demid = $subid AND demtype = \"S\"");
  // clear any old stuff
  $objResponse->addScript("$('nickname').value = ''");
  $objResponse->addScript("$('fromsub').value = ''");
  $objResponse->addScript("$('steam').value = ''");
  $objResponse->addScript("$('ip').value = ''");
  $objResponse->addScript("$('txtReason').value = ''");
  $objResponse->addAssign("demo.msg", "innerHTML",  "");
  // add new stuff
  $objResponse->addScript("$('nickname').value = '" . $ban['name'] . "'");
  $objResponse->addScript("$('steam').value = '" . $ban['SteamId']. "'");
  $objResponse->addScript("$('ip').value = '" . $ban['sip'] . "'");
  if(trim($ban['SteamId']) == "")
    $type = "1";
  else
    $type = "0";
  $objResponse->addScriptCall("selectLengthTypeReason", "0", $type, addslashes($ban['reason']));

  $objResponse->addScript("$('fromsub').value = '$subid'");
  if($demo)
  {
    $objResponse->addAssign("demo.msg", "innerHTML",  $demo['origname']);
    $objResponse->addScript("demo('" . $demo['filename'] . "', '" . $demo['origname'] . "');");
  }
  $objResponse->addScript("SwapPane(0);");
  return $objResponse;
}

function PrepareReban($bid)
{
  $objResponse = new xajaxResponse();
  $bid = (int)$bid;

  $ban = $GLOBALS['db']->GetRow("SELECT type, ip, authid, name, length, reason FROM ".DB_PREFIX."_bans WHERE bid = '".$bid."';");
  $demo = $GLOBALS['db']->GetRow("SELECT * FROM ".DB_PREFIX."_demos WHERE demid = '".$bid."' AND demtype = \"B\";");
  // clear any old stuff
  $objResponse->addScript("$('nickname').value = ''");
  $objResponse->addScript("$('ip').value = ''");
  $objResponse->addScript("$('fromsub').value = ''");
  $objResponse->addScript("$('steam').value = ''");
  $objResponse->addScript("$('txtReason').value = ''");
  $objResponse->addAssign("demo.msg", "innerHTML",  "");
  $objResponse->addAssign("txtReason", "innerHTML",  "");

  // add new stuff
  $objResponse->addScript("$('nickname').value = '" . $ban['name'] . "'");
  $objResponse->addScript("$('steam').value = '" . $ban['authid']. "'");
  $objResponse->addScript("$('ip').value = '" . $ban['ip']. "'");
  $objResponse->addScriptCall("selectLengthTypeReason", $ban['length'], $ban['type'], addslashes($ban['reason']));

  if($demo)
  {
    $objResponse->addAssign("demo.msg", "innerHTML",  $demo['origname']);
    $objResponse->addScript("demo('" . $demo['filename'] . "', '" . $demo['origname'] . "');");
  }
  $objResponse->addScript("SwapPane(0);");
  return $objResponse;
}

function SetupEditServer($sid)
{
  $objResponse = new xajaxResponse();
  $sid = (int)$sid;
  $server = $GLOBALS['db']->GetRow("SELECT * FROM ".DB_PREFIX."_servers WHERE sid = $sid");

  // clear any old stuff
  $objResponse->addScript("$('address').value = ''");
  $objResponse->addScript("$('port').value = ''");
  $objResponse->addScript("$('rcon').value = ''");
  $objResponse->addScript("$('rcon2').value = ''");
  $objResponse->addScript("$('mod').value = '0'");
  $objResponse->addScript("$('serverg').value = '0'");


  // add new stuff
  $objResponse->addScript("$('address').value = '" . $server['ip']. "'");
  $objResponse->addScript("$('port').value =  '" . $server['port']. "'");
  $objResponse->addScript("$('rcon').value =  '" . $server['rcon']. "'");
  $objResponse->addScript("$('rcon2').value =  '" . $server['rcon']. "'");
  $objResponse->addScript("$('mod').value =  " . $server['modid']);
  $objResponse->addScript("$('serverg').value =  " . $server['gid']);

  $objResponse->addScript("$('insert_type').value =  " . $server['sid']);
  $objResponse->addScript("SwapPane(1);");
  return $objResponse;
}

function CheckPassword($aid, $pass)
{
  $objResponse = new xajaxResponse();
  global $userbank;
  $aid = (int)$aid;
  if(!$userbank->CheckLogin($userbank->encrypt_password($pass), $aid))
  {
    $objResponse->addScript("$('current.msg').setStyle('display', 'block');");
    $objResponse->addScript("$('current.msg').setHTML('<div class=\"c-red\">Данные не совпадают</div>');");
    $objResponse->addScript("set_error(1);");

  }
  else
  {
    $objResponse->addScript("$('current.msg').setStyle('display', 'none');");
    $objResponse->addScript("set_error(0);");
  }
  return $objResponse;
}

function ChangeAdminsInfos($aid, $vk, $skype)
{
  global $userbank;
  $objResponse = new xajaxResponse();
  $aid = (int)$aid;

  if($aid != $userbank->aid && !$userbank->is_logged_in())
  {
    $objResponse->redirect("index.php?p=login&m=no_access", 0);
    $log = new CSystemLog("w", "Ошибка доступа", $_SERVER["REMOTE_ADDR"] . " пытался сменить vk или skype, не имея на это прав.");
    return $objResponse;
  }

  $vk = RemoveCode($vk);
  $vk = str_replace(array("http://","https://","/","vk.com"), "", $vk);
  $skype = RemoveCode($skype);
  
  $GLOBALS['db']->Execute("UPDATE `".DB_PREFIX."_admins` SET `vk` = '".$vk."', `skype` = '".$skype."' WHERE `aid` = ?", array((int)$aid));
  $admname = $GLOBALS['db']->GetRow("SELECT user FROM `".DB_PREFIX."_admins` WHERE aid = ?", array((int)$aid));
  $objResponse->addScript("ShowBox('Информация', 'Ваши данные были успешно обновлены!', 'green', 'index.php?p=account');");
  $log = new CSystemLog("m", "Данные связи изменены", "У адмнистратора ".$admname['user']." успешно были изменены данные на (vk: ".$vk.", skype: ".$skype.")");
  return $objResponse;
}
function ChangePassword($aid, $pass)
{
  global $userbank;
  $objResponse = new xajaxResponse();
  $aid = (int)$aid;

  if($aid != $userbank->aid && !$userbank->HasAccess(ADMIN_OWNER|ADMIN_EDIT_ADMINS))
  {
    $objResponse->redirect("index.php?p=login&m=no_access", 0);
    $log = new CSystemLog("w", "Ошибка доступа", $_SERVER["REMOTE_ADDR"] . " пытался сменить пароль, не имея на это прав.");
    return $objResponse;
  }

  $GLOBALS['db']->Execute("UPDATE `".DB_PREFIX."_admins` SET `password` = '" . $userbank->encrypt_password($pass) . "' WHERE `aid` = $aid");
  $admname = $GLOBALS['db']->GetRow("SELECT user FROM `".DB_PREFIX."_admins` WHERE aid = ?", array((int)$aid));
  $objResponse->addAlert("Пароль успешно изменен");
  $objResponse->addRedirect("index.php?p=login", 0);
  $log = new CSystemLog("m", "Пароль изменен", "Пароль сменен админом (".$admname['user'].")");
  return $objResponse;
}

function AddMod($name, $folder, $icon, $steam_universe, $enabled)
{
  $objResponse = new xajaxResponse();
  global $userbank, $username;
  if(!$userbank->HasAccess(ADMIN_OWNER|ADMIN_ADD_MODS))
  {
    $objResponse->redirect("index.php?p=login&m=no_access", 0);
    $log = new CSystemLog("w", "Ошибка доступа", $username . " пытался добавить МОД, не имея на это прав.");
    return $objResponse;
  }
  $name = htmlspecialchars(strip_tags($name));//don't want to addslashes because execute will automatically do it
  $icon = htmlspecialchars(strip_tags($icon));
  $folder = htmlspecialchars(strip_tags($folder));
  $steam_universe = (int)$steam_universe;
  $enabled = ($enabled == "on") ? 1 : 0;
  
  // Already there?
  $check = $GLOBALS['db']->GetRow("SELECT * FROM `" . DB_PREFIX . "_mods` WHERE modfolder = ? OR name = ?;", array($folder, $name));
  if(!empty($check))
  {
    $objResponse->addScript("ShowBox('МОД не добавлен', 'МОД использующий такие папку или имя уже существует.', 'red');");
    return $objResponse;
  }

  $pre = $GLOBALS['db']->Prepare("INSERT INTO ".DB_PREFIX."_mods(name,icon,modfolder,steam_universe,enabled) VALUES (?,?,?,?,?)");
  $GLOBALS['db']->Execute($pre,array($name, $icon, $folder, $steam_universe, $enabled));

  $objResponse->addScript("ShowBox('Мод добавлен', 'Игровой МОД успешно добавлен', 'green', 'index.php?p=admin&c=mods');");
  $objResponse->addScript("TabToReload();");
  $log = new CSystemLog("m", "МОД добавлен", "МОД ($name) был добавлен");
  return $objResponse;
}

function EditAdminPerms($aid, $web_flags, $srv_flags)
{
  if(empty($aid))
    return;
  $aid = (int)$aid;
  $web_flags = (int)$web_flags;

  $objResponse = new xajaxResponse();
  global $userbank, $username;
  if(!$userbank->HasAccess(ADMIN_OWNER|ADMIN_EDIT_ADMINS))
  {
    $objResponse->redirect("index.php?p=login&m=no_access", 0);
    $log = new CSystemLog("w", "Ошибка доступа", $username . " пытался изменить разрешения админа, не имея на это прав.");
    return $objResponse;
  }

  if(!$userbank->HasAccess(ADMIN_OWNER) && (int)$web_flags & ADMIN_OWNER )
  {
      $objResponse->redirect("index.php?p=login&m=no_access", 0);
      $log = new CSystemLog("w", "Ошибка доступа", $username . " пытался сменить разрешения главного админа, не имея на это прав.");
      return $objResponse;
  }

  // Users require a password and email to have web permissions
  $password = $GLOBALS['userbank']->GetProperty('password', $aid);
  $email = $GLOBALS['userbank']->GetProperty('email', $aid);
  if($web_flags > 0 && (empty($password) || empty($email)))
  {
    $objResponse->addScript("ShowBox('Ошибка', 'Админ должен ввести E-mail и пароль для получения прав доступа к сайту.<br /><a href=\"index.php?p=admin&c=admins&o=editdetails&id=" . $aid . "\" title=\"Редактировать детали админа\">Измените детали админа</a> сначала и попробуйте снова.', 'red', '');");
    return $objResponse;
  }
  
  // Update web stuff
  $GLOBALS['db']->Execute("UPDATE `".DB_PREFIX."_admins` SET `extraflags` = $web_flags WHERE `aid` = $aid");


  if(strstr($srv_flags, "#"))
  {
    $immunity = "0";
    $immunity = substr($srv_flags, strpos($srv_flags, "#")+1);
    $srv_flags = substr($srv_flags, 0, strlen($srv_flags) - strlen($immunity)-1);
  }
  $immunity = ($immunity>0) ? $immunity : 0;
  // Update server stuff
  $GLOBALS['db']->Execute("UPDATE `".DB_PREFIX."_admins` SET `srv_flags` = ?, `immunity` = ? WHERE `aid` = $aid", array($srv_flags, $immunity));

  if(isset($GLOBALS['config']['config.enableadminrehashing']) && $GLOBALS['config']['config.enableadminrehashing'] == 1)
  {
    // rehash the admins on the servers
    $serveraccessq = $GLOBALS['db']->GetAll("SELECT s.sid FROM `".DB_PREFIX."_servers` s
                        LEFT JOIN `".DB_PREFIX."_admins_servers_groups` asg ON asg.admin_id = '".(int)$aid."'
                        LEFT JOIN `".DB_PREFIX."_servers_groups` sg ON sg.group_id = asg.srv_group_id
                        WHERE ((asg.server_id != '-1' AND asg.srv_group_id = '-1')
                        OR (asg.srv_group_id != '-1' AND asg.server_id = '-1'))
                        AND (s.sid IN(asg.server_id) OR s.sid IN(sg.server_id)) AND s.enabled = 1");
    $allservers = array();
    foreach($serveraccessq as $access) {
      if(!in_array($access['sid'], $allservers)) {
        $allservers[] = $access['sid'];
      }
    }
    $objResponse->addScript("ShowRehashBox('".implode(",", $allservers)."', 'Разрешения обновлены', 'Разрешения пользователя успешно обновлены', 'green', 'index.php?p=admin&c=admins');TabToReload();");
  } else
    $objResponse->addScript("ShowBox('Разрешения обновлены', 'Разрешения пользователя успешно обновлены', 'green', 'index.php?p=admin&c=admins');TabToReload();");
  $admname = $GLOBALS['db']->GetRow("SELECT user FROM `".DB_PREFIX."_admins` WHERE aid = ?", array((int)$aid));
    $log = new CSystemLog("m", "Разрешения обновлены", "Разрешения обновлены для (".$admname['user'].")");
  return $objResponse;
}

function EditGroup($gid, $web_flags, $srv_flags, $type, $name, $overrides, $newOverride)
{
  $objResponse = new xajaxResponse();
  global $userbank, $username;
  if(!$userbank->HasAccess(ADMIN_OWNER|ADMIN_EDIT_GROUPS))
  {
    $objResponse->redirect("index.php?p=login&m=no_access", 0);
    $log = new CSystemLog("w", "Ошибка доступа", $username . " пытался редактировать детали группы, не имея на это прав.");
    return $objResponse;
  }
  
  if(empty($name))
  {
    $objResponse->redirect("index.php?p=login&m=no_access", 0);
    $log = new CSystemLog("w", "Ошибка доступа", $username . " пытался удалить название группы. У группы должно быть название.");
    return $objResponse;
  }
  
  $gid = (int)$gid;
  $name = RemoveCode($name);
  $web_flags = (int)$web_flags;
  if($type == "web" || $type == "server" )
  // Update web stuff
  $GLOBALS['db']->Execute("UPDATE `".DB_PREFIX."_groups` SET `flags` = ?, `name` = ? WHERE `gid` = $gid", array($web_flags, $name));

  if($type == "srv")
  {
    $gname = $GLOBALS['db']->GetRow("SELECT name FROM ".DB_PREFIX."_srvgroups WHERE id = $gid");

    if(strstr($srv_flags, "#"))
    {
      $immunity = 0;
      $immunity = substr($srv_flags, strpos($srv_flags, "#")+1);
      $srv_flags = substr($srv_flags, 0, strlen($srv_flags) - strlen($immunity)-1);
    }
    $immunity = ($immunity>0) ? $immunity : 0;

    // Update server stuff
    $GLOBALS['db']->Execute("UPDATE `".DB_PREFIX."_srvgroups` SET `flags` = ?, `name` = ?, `immunity` = ? WHERE `id` = $gid", array($srv_flags, $name, $immunity));

    $oldname = $GLOBALS['db']->GetAll("SELECT aid FROM ".DB_PREFIX."_admins WHERE srv_group = ?", array($gname['name']));
    foreach($oldname as $o)
    {
      $GLOBALS['db']->Execute("UPDATE `".DB_PREFIX."_admins` SET `srv_group` = ? WHERE `aid` = '" . (int)$o['aid'] . "'", array($name));
    }
    
    // Update group overrides
    if(!empty($overrides))
    {
      foreach($overrides as $override)
      {
        // Skip invalid stuff?!
        if($override['type'] != "command" && $override['type'] != "group")
          continue;
      
        $id = (int)$override['id'];
        // Wants to delete this override?
        if(empty($override['name']))
        {
          $GLOBALS['db']->Execute("DELETE FROM `" . DB_PREFIX . "_srvgroups_overrides` WHERE id = ?;", array($id));
          continue;
        }
        
        // Check for duplicates
        $chk = $GLOBALS['db']->GetAll("SELECT * FROM `" . DB_PREFIX . "_srvgroups_overrides` WHERE name = ? AND type = ? AND group_id = ? AND id != ?", array($override['name'], $override['type'], $gid, $id));
        if(!empty($chk))
        {
          $objResponse->addScript("ShowBox('Ошибка', 'Переопределение с таким именем уже существует \\\"" . htmlspecialchars(addslashes($override['name'])) . "\\\" для выбранного типа..', 'red', '', true);");
          return $objResponse;
        }
        
        // Edit the override
        $GLOBALS['db']->Execute("UPDATE `" . DB_PREFIX . "_srvgroups_overrides` SET name = ?, type = ?, access = ? WHERE id = ?;", array($override['name'], $override['type'], $override['access'], $id));
      }
    }
    
    // Add a new override
    if(!empty($newOverride))
    {
      if(($newOverride['type'] == "command" || $newOverride['type'] == "group") && !empty($newOverride['name']))
      {
        // Check for duplicates
        $chk = $GLOBALS['db']->GetAll("SELECT * FROM `" . DB_PREFIX . "_srvgroups_overrides` WHERE name = ? AND type = ? AND group_id = ?", array($newOverride['name'], $newOverride['type'], $gid));
        if(!empty($chk))
        {
          $objResponse->addScript("ShowBox('Ошибка', 'Переопределение с таким именем уже существует \\\"" . htmlspecialchars(addslashes($newOverride['name'])) . "\\\" для выбранного типа..', 'red', '', true);");
          return $objResponse;
        }
        
        // Insert the new override
        $GLOBALS['db']->Execute("INSERT INTO `" . DB_PREFIX . "_srvgroups_overrides` (group_id, type, name, access) VALUES (?, ?, ?, ?);", array($gid, $newOverride['type'], $newOverride['name'], $newOverride['access']));
      }
    }
    
    if(isset($GLOBALS['config']['config.enableadminrehashing']) && $GLOBALS['config']['config.enableadminrehashing'] == 1)
    {
      // rehash the settings out of the database on all servers
      $serveraccessq = $GLOBALS['db']->GetAll("SELECT sid FROM ".DB_PREFIX."_servers WHERE enabled = 1;");
      $allservers = array();
      foreach($serveraccessq as $access) {
        if(!in_array($access['sid'], $allservers)) {
          $allservers[] = $access['sid'];
        }
      }
      $objResponse->addScript("ShowRehashBox('".implode(",", $allservers)."', 'Группа обновлена', 'Группа успешно обновлена', 'green', 'index.php?p=admin&c=groups');TabToReload();");
    } else
      $objResponse->addScript("ShowBox('Группа обновлена', 'Группа успешно обновлена', 'green', 'index.php?p=admin&c=groups');TabToReload();");
    $log = new CSystemLog("m", "Группа обновлена", "Группа ($name) была обновлена");
    return $objResponse;
  }

  $objResponse->addScript("ShowBox('Группа обновлена', 'Группа успешно обновлена', 'green', 'index.php?p=admin&c=groups');TabToReload();");
  $log = new CSystemLog("m", "Группа обновлена", "Группа ($name) обновлена");
  return $objResponse;
}


function SendRcon($sid, $command, $output)
{
  global $userbank, $username;
  $objResponse = new xajaxResponse();
  if(!$userbank->HasAccess(SM_RCON . SM_ROOT))
  {
    $objResponse->redirect("index.php?p=login&m=no_access", 0);
    $log = new CSystemLog("w", "Ошибка доступа", $username . " пытался отправить РКОН команду, не имея на это прав.");
    return $objResponse;
  }
  if(empty($command))
  {
    $objResponse->addScript("$('cmd').value=''; $('cmd').disabled='';$('rcon_btn').disabled=''");
    return $objResponse;
  }
  if($command == "clr")
  {
    $objResponse->addAssign("rcon_con", "innerHTML",  "<div class='lv-item media'><div class='lv-avatar bgm-red pull-left'>R</div><div class='media-body'><div class='ms-item' style='display: block;max-width: 100%;'>************************************************************<br />*&nbsp;SourceBans РКОН консоль<br />*&nbsp;Введите команду в поле ниже и нажмите Enter<br />*&nbsp;Введите 'clr' для очистки консоли<br />************************************************************</div></div></div>");
    $objResponse->addScript("scroll.toBottom(); $('cmd').value=''; $('cmd').disabled='';$('rcon_btn').disabled=''");
    return $objResponse;
  }
    
    if(stripos($command, "rcon_password") !== false)
  {
        $objResponse->addAppend("rcon_con", "innerHTML",  "<div class='lv-item media p-b-5 p-t-5'><div class='lv-avatar bgm-red pull-left'>R</div><div class='media-body'><div class='ms-item' style='display: block;max-width: 100%;'> > Ошибка: Вы используете консоль. Не пытайтесь подобрать RCON пароль!</div></div></div>");
    $objResponse->addScript("scroll.toBottom(); $('cmd').value=''; $('cmd').disabled='';$('rcon_btn').disabled=''");
    return $objResponse;
  }
    
  $sid = (int)$sid;
    
  $rcon = $GLOBALS['db']->GetRow("SELECT ip, port, rcon FROM `".DB_PREFIX."_servers` WHERE sid = ".$sid." LIMIT 1");
  if(empty($rcon['rcon']))
  {
    $objResponse->addAppend("rcon_con", "innerHTML", "<div class='lv-item media p-b-5 p-t-5'><div class='lv-avatar bgm-red pull-left'>R</div><div class='media-body'><div class='ms-item' style='display: block;max-width: 100%;'> > Ошибка: Нет RCON пароля!<br />Вы должны добавить RCON пароль для этого сервера на странице 'редактирования серверов' <br /> чтобы использовать консоль!</div></div></div>");
    $objResponse->addScript("scroll.toBottom(); $('cmd').value='Задать РКОН пароль.'; $('cmd').disabled=true; $('rcon_btn').disabled=true");
    return $objResponse;
  }
    if(!$test = @fsockopen($rcon['ip'], $rcon['port'], $errno, $errstr, 2))
    {
        @fclose($test);
    $objResponse->addAppend("rcon_con", "innerHTML", "<div class='lv-item media p-b-5 p-t-5'><div class='lv-avatar bgm-red pull-left'>R</div><div class='media-body'><div class='ms-item' style='display: block;max-width: 100%;'> > Ошибка: невозможно соединиться с сервером!</div></div></div>");
    $objResponse->addScript("scroll.toBottom(); $('cmd').value=''; $('cmd').disabled='';$('rcon_btn').disabled=''");
    return $objResponse;
  }
    @fclose($test);
  
  $r = new CServerControl(intval($GLOBALS['config']['gamecache.enabled']) == 1);
  $r->Connect($rcon['ip'], $rcon['port']);
  
  if(!$r->AuthRcon($rcon['rcon']))
  {
    $GLOBALS['db']->Execute("UPDATE ".DB_PREFIX."_servers SET rcon = '' WHERE sid = '".$sid."';");
    $objResponse->addAppend("rcon_con", "innerHTML", "<div class='lv-item media p-b-5 p-t-5'><div class='lv-avatar bgm-red pull-left'>R</div><div class='media-body'><div class='ms-item' style='display: block;max-width: 100%;'> > Ошибка: неверный РКОН пароль!<br />Вы должны изменить РКОН пароль для этого сервера.<br /> Если Вы продолжите использовать консоль с неверным РКОН паролем, <br />сервер заблокирует соединение!</div></div></div>");
    $objResponse->addScript("scroll.toBottom(); $('cmd').value='Сменить РКОН пароль.'; $('cmd').disabled=true; $('rcon_btn').disabled=true");
    return $objResponse;
  }
  $ret = $r->SendCommand($command);


  $textAppend = "<div class='lv-item media right p-b-5 p-t-5'><div class='lv-avatar bgm-orange pull-right'><img src='".GetUserAvatar($userbank->getProperty("authid"))."' /></div><div class='media-body'><div class='ms-item'> $command </div><small class='ms-date'><i class='zmdi zmdi-time'></i> ".date("d/m/Y в H:i")."</small></div></div>";
  $ret = str_replace("\n", "<br />", $ret);
  if(empty($ret))
  {
    if($output)
    {
      //$objResponse->addAppend("rcon_con", "innerHTML",  "-> $command<br />");
      //$objResponse->addAppend("rcon_con", "innerHTML",  "Команда выполнена.<br />");
      $textAppend .= "<div class='lv-item media p-b-5 p-t-5'><div class='lv-avatar bgm-red pull-left'>R</div><div class='media-body'><div class='ms-item' style='display: block;max-width: 100%;'> Команда была отправлена, но ответа не последовало... :C </div></div></div>";
    }
  }
  else
  {
    if($output)
    {
      //$objResponse->addAppend("rcon_con", "innerHTML",  "-> $command<br />");
      //$objResponse->addAppend("rcon_con", "innerHTML",  "$ret<br />");
      $textAppend .= "<div class='lv-item media p-b-5 p-t-5'><div class='lv-avatar bgm-red pull-left'>R</div><div class='media-body'><div class='ms-item' style='display: block;max-width: 100%;'> $ret </div></div></div>";
    }
  }
  $objResponse->addAppend("rcon_con", "innerHTML", $textAppend);
  $objResponse->addScript("scroll.toBottom(); $('cmd').value=''; $('cmd').disabled=''; $('rcon_btn').disabled=''");
  $log = new CSystemLog("m", "РКОН отправлен", "РКОН был отправлен на сервер (".$rcon['ip'].":".$rcon['port']."). Команда: $command", true, true);
  return $objResponse;
}


function SendMail($subject, $message, $type, $id)
{
  $objResponse = new xajaxResponse();
    global $userbank, $username;
  
  $id = (int)$id;
  
    if(!$userbank->HasAccess(ADMIN_OWNER|ADMIN_BAN_PROTESTS|ADMIN_BAN_SUBMISSIONS))
  {
    $objResponse->redirect("index.php?p=login&m=no_access", 0);
    $log = new CSystemLog("w", "Ошибка доступа", $username . " пытался отправить e-mail, не имея на это прав.");
    return $objResponse;
  }
  
  // Don't mind wrong types
  if($type != 's' && $type != 'p')
  {
    return $objResponse;
  }
  
  // Submission
  $email = "";
  if($type == 's')
  {
    $email = $GLOBALS['db']->GetOne('SELECT email FROM `'.DB_PREFIX.'_submissions` WHERE subid = ?', array($id));
  }
  // Protest
  else if($type == 'p')
  {
    $email = $GLOBALS['db']->GetOne('SELECT email FROM `'.DB_PREFIX.'_protests` WHERE pid = ?', array($id));
  }
  
  if(empty($email))
  {
    $objResponse->addScript("ShowBox('Ошибка', 'Не выбран e-mail..', 'red', 'index.php?p=admin&c=bans');");
    return $objResponse;
  }
  
  $headers = "From: noreply@" . $_SERVER['HTTP_HOST'] . "\n" . 'X-Mailer: PHP/' . phpversion();
  $m = @EMail($email, '[SourceBans] ' . $subject, $message, $headers);

  
  if($m)
  {
    $objResponse->addScript("ShowBox('E-mail отправлен', 'E-mail успешно отправлен пользователю.', 'green', 'index.php?p=admin&c=bans');");
    $log = new CSystemLog("m", "E-mail отправлен", $username . " отправил e-mail на ".htmlspecialchars($email).".<br />Тема: '[SourceBans] " . htmlspecialchars($subject) . "'<br />Сообщение: '" . nl2br(htmlspecialchars($message)) . "'");
  }
  else
    $objResponse->addScript("ShowBox('Ошибка', 'Не удалось отправить e-mail пользователю.', 'red', '');");
  
  return $objResponse;
}

function AddComment($bid, $ctype, $ctext, $page)
{
  $objResponse = new xajaxResponse();
  global $userbank, $username;
  if(!$userbank->is_admin())
  {
    $objResponse->redirect("index.php?p=login&m=no_access", 0);
    $log = new CSystemLog("w", "Ошибка доступа", $username . " пытался добавить комментарий, не имея на это прав.");
    return $objResponse;
  }
  
  $bid = (int)$bid;
  $page = (int)$page;
  
  $pagelink = "";
  if($page != -1)
    $pagelink = "&page=".$page;
    
  if($ctype=="B")
    $redir = "?p=banlist".$pagelink;
  elseif($ctype=="C")
    $redir = "?p=commslist".$pagelink;
  elseif($ctype=="S")
    $redir = "?p=admin&c=bans#^2";
  elseif($ctype=="P")
    $redir = "?p=admin&c=bans#^1";
  else
  {
    $objResponse->addScript("ShowBox('Ошибка', 'Плохой тип комментария.', 'red');");
    return $objResponse;
  }

  $ctext = trim($ctext);

  $pre = $GLOBALS['db']->Prepare("INSERT INTO ".DB_PREFIX."_comments(bid,type,aid,commenttxt,added) VALUES (?,?,?,?,UNIX_TIMESTAMP())");
  $GLOBALS['db']->Execute($pre,array($bid,
                     $ctype,
                     $userbank->GetAid(),
                     $ctext));

  $objResponse->addScript("ShowBox('Комментарий добавлен', 'Комментарий успешно опубликован', 'green', 'index.php$redir');");
  $objResponse->addScript("TabToReload();");
  $log = new CSystemLog("m", "Комментарий добавлен", $username." добавил комментарий к бану №".$bid);
  return $objResponse;
}

function EditComment($cid, $ctype, $ctext, $page)
{
  $objResponse = new xajaxResponse();
  global $userbank, $username;
  if(!$userbank->is_admin())
  {
    $objResponse->redirect("index.php?p=login&m=no_access", 0);
    $log = new CSystemLog("w", "Ошибка доступа", $username . " пытался редактировать комментарий, не имея на это прав.");
    return $objResponse;
  }

  $cid = (int)$cid;
  $page = (int)$page;
  
  $pagelink = "";
  if($page != -1)
    $pagelink = "&page=".$page;
  
  if($ctype=="B")
    $redir = "?p=banlist".$pagelink;
  elseif($ctype=="C")
    $redir = "?p=commslist".$pagelink;
  elseif($ctype=="S")
    $redir = "?p=admin&c=bans#^2";
  elseif($ctype=="P")
    $redir = "?p=admin&c=bans#^1";
  else
  {
    $objResponse->addScript("ShowBox('Ошибка', 'Плохой тип комментария.', 'red');");
    return $objResponse;
  }

  $ctext = trim($ctext);

  $pre = $GLOBALS['db']->Prepare("UPDATE ".DB_PREFIX."_comments SET `commenttxt` = ?, `editaid` = ?, `edittime`= UNIX_TIMESTAMP() WHERE cid = ?");
  $GLOBALS['db']->Execute($pre,array($ctext,
                     $userbank->GetAid(),
                     $cid));

  $objResponse->addScript("ShowBox('Комментарий отредактирован', 'Комментарий №".$cid." успешно отредактирован', 'green', 'index.php$redir');");
  $objResponse->addScript("TabToReload();");
  $log = new CSystemLog("m", "Комментарий отредактирован", $username." отредактировал комментарий №".$cid);
  return $objResponse;
}

function RemoveComment($cid, $ctype, $page)
{
  $objResponse = new xajaxResponse();
  global $userbank, $username;
  if (!$userbank->HasAccess(ADMIN_OWNER))
  {
    $objResponse->redirect("index.php?p=login&m=no_access", 0);
    $log = new CSystemLog("w", "Ошибка доступа", $username . " пытался удалить комментарий, не имея на это прав.");
    return $objResponse;
  }

  $cid = (int)$cid;
  $page = (int)$page;
  
  $pagelink = "";
  if($page != -1)
    $pagelink = "&page=".$page;

  $res = $GLOBALS['db']->Execute("DELETE FROM `".DB_PREFIX."_comments` WHERE `cid` = ?",
                array( $cid ));
  if($ctype=="B")
    $redir = "?p=banlist".$pagelink;
  elseif($ctype=="C")
    $redir = "?p=commslist".$pagelink;
  else
    $redir = "?p=admin&c=bans";
  if($res)
  {
    $objResponse->addScript("ShowBox('Комментарий удалён', 'Комментарий был успешно удалён из базы данных', 'green', 'index.php$redir', true);");
    $log = new CSystemLog("m", "Комментарий удален", $username." удалил комментарий №".$cid);
  }
  else
    $objResponse->addScript("ShowBox('Ошибка', 'Ошибка удаления комментария из базы данных. Смотрите лог для дополнительной информации', 'red', 'index.php$redir', true);");
  return $objResponse;
}

function Maintenance($type) {
    global $userbank, $username, $theme;
    
    $objResponse = new xajaxResponse();
    if (!$userbank->HasAccess(ADMIN_OWNER|ADMIN_WEB_SETTINGS)) {
        ShowBox_ajx("Ошибка", "Вы не имеете прав для выполнения данного действия!", "red", "", true, $objResponse);
        new CSystemLog("w", "Ошибка доступа", $usernake . " пытался произвести операцию по обслуживанию системы, не имея на это прав.");
        return $objResponse;
    }
    
    switch($type) {
        case "themecache": {
            $theme->clear_compiled_tpl();
            ShowBox_ajx("Успех", "Кеш шаблона очищен успешно.", "green", "", true, $objResponse);
            break;
        }
        
        case "avatarcache": {
            $GLOBALS['db']->Execute(sprintf("TRUNCATE `%s_avatars`", DB_PREFIX));
            ShowBox_ajx("Успех", "Кеш аватарок очищен успешно.", "green", "", true, $objResponse);
            break;
        }
    
        case 'adminsexpired': {
            $admins = $GLOBALS['db']->GetAll(sprintf("SELECT `aid` FROM `%s_admins` WHERE `expired` != 0 AND `expired` < %d;", DB_PREFIX, time()));
            foreach ($admins as $admin) {
                $aid = intval($admin['aid']);

                $GLOBALS['db']->Execute(sprintf("DELETE FROM `%s_admins` WHERE `aid` = %d;", DB_PREFIX, $aid));
                $GLOBALS['db']->Execute(sprintf("DELETE FROM `%s_admins_servers_groups` WHERE `admin_id` = %d;", DB_PREFIX, $aid));
            }
            ShowBox_ajx("Успех", sprintf("Успешно удалено %d администраторов.", count($admins)), "green", "", true, $objResponse);
            break;
        }

        case "bansexpired": {
            $GLOBALS['db']->Execute(sprintf("DELETE FROM `%s_bans` WHERE `RemoveType` IS NOT NULL", DB_PREFIX));
            ShowBox_ajx("Успех", "Истёкшие баны удалены успешно.", "green", "", true, $objResponse);
            break;
        }
        
        case "commsexpired": {
            $GLOBALS['db']->Execute(sprintf("DELETE FROM `%s_comms` WHERE `RemoveType` IS NOT NULL", DB_PREFIX));
            ShowBox_ajx("Успех", "Истёкшие муты удалены успешно.", "green", "", true, $objResponse);
            break;
        }
        
        case "optimizebd": {
            $tables = $GLOBALS['db']->GetAll("SHOW TABLES;");
            foreach ($tables as &$table)
                $GLOBALS['db']->Execute(sprintf("OPTIMIZE TABLE `%s`;", $table[0]));
            
            ShowBox_ajx("Успех", "Оптимизация таблиц завершена.", "green", "", true, $objResponse);
            break;
        }
        
        case "cleancountrycache": {
            $GLOBALS['db']->Execute("UPDATE `" . DB_PREFIX . "_bans` SET `country` = NULL;");
            ShowBox_ajx("Успех", "Кеш стран банлиста очищен успешно.<br /><br /><span style=\"color: #f00;\">Внимание!</span> Это может отрицательно сказаться на первой загрузке каждой страницы Вашего банлиста. Рекомендуем произвести операцию \"Обновить кеш стран в банлисте\".", "green", "", true, $objResponse);
            break;
        }
        
        case "rehashcountries": {
            $bans = $GLOBALS['db']->GetAll("SELECT `bid`, `ip` FROM `" . DB_PREFIX . "_bans` WHERE `country` IS NULL or `country` = 'zz'");
            foreach ($bans as $ban) {
                $GLOBALS['db']->Execute("UPDATE `" . DB_PREFIX . "_bans` SET `country` = " . $GLOBALS['db']->qstr(FetchIp($ban['ip'])) . " WHERE `bid` = " . (int)$ban['bid'] . ";");
            }
            
            ShowBox_ajx("Успех", "Операция обновлений стран в кеше завершена.", "green", "", true, $objResponse);
            break;
        }
        
        case "updatecountries": {
            if (!function_exists("zlib_decode")) {
                ShowBox_ajx("Ошибка", "Невозможно произвести обновление GeoIP базы: недоступна функция <em>gzuncompress</em>.", "red", "", true, $objResponse);
                return $objResponse;
            }
            
            $CountryFile = INCLUDES_PATH . '/IpToCountry.csv';
            if (@is_writable($CountryFile)) {
                file_put_contents($CountryFile, zlib_decode(file_get_contents("http://software77.net/geo-ip/?DL=1&x=Download")));
                ShowBox_ajx("Успех", "Файл GeoIP базы обновлён.", "green", "", true, $objResponse);
            } else
                ShowBox_ajx("Ошибка", "Невозможно произвести обновление GeoIP базы: запись в файл <em>/includes/IpToCountry.csv</em> запрещена. Установите права <b>777</b> на файл <em>/includes/IpToCountry.csv</em>", "red", "", true, $objResponse);
            break;
        }
        
        case "warningsexpired": {
            $GLOBALS['db']->Execute(sprintf("DELETE FROM `%s_warns` WHERE `expires` < %d", DB_PREFIX, time()));
            ShowBox_ajx("Успех", "Все истёкшие и снятые предупреждения были успешно удалены.", "green", "", true, $objResponse);
            break;
        }
        
        case "avatarupdate": {
            Maintenance("avatarcache");
            $users = $GLOBALS['db']->GetAll(sprintf("SELECT `authid` FROM `%s_admins`", DB_PREFIX));
            foreach ($users as &$user)
                GetUserAvatar($user['authid']);
            ShowBox_ajx("Успех", "Кеш аватаров Администраторов обновлён.", "green", "", true, $objResponse);
            break;
        }
        
        case "commentsclean": {
            $GLOBALS['db']->Execute(sprintf("TRUNCATE `%s_comments`;", DB_PREFIX));
            ShowBox_ajx("Успех", "Все комментарии были успешно удалены.", "green", "", true, $objResponse);
            break;
        }
        
        case "banlogclean": {
            $GLOBALS['db']->Execute(sprintf("TRUNCATE `%s_banlog`;", DB_PREFIX));
            ShowBox_ajx("Успех", "История заблокированных соединений к серверам успешно очищена.", "green", "", true, $objResponse);
            break;
        }
        
        case "protests": {
            $GLOBALS['db']->Execute(sprintf("TRUNCATE `%s_protests`;", DB_PREFIX));
            ShowBox_ajx("Успех", "Протесты успешно удалены.", "green", "", true, $objResponse);
            break;
        }
        
        case "reports": {
            $GLOBALS['db']->Execute(sprintf("TRUNCATE `%s_submissions`;", DB_PREFIX));
            ShowBox_ajx("Успех", "Предложения бана (репорты) успешно удалены.", "green", "", true, $objResponse);
            break;
    }

    case "vouchers": {
      $GLOBALS['db']->Execute(sprintf("DELETE FROM `%s_vay4er` WHERE `activ` != 1", DB_PREFIX));
      ShowBox_ajx("Успех", "Все использованные ваучеры успешно удалены.", "green", "", true, $objResponse);
      break;
    }
        
        default: {
            ShowBox_ajx("Ошибка", "Неизвестная операция", "red", "", true, $objResponse);
            break;
        }
    }
    
    return $objResponse;
}

function RefreshServer($sid)
{
  $objResponse = new xajaxResponse();
  $sid = (int)$sid;
  session_start();
  $data = $GLOBALS['db']->GetRow("SELECT ip, port FROM `".DB_PREFIX."_servers` WHERE sid = ?;", array($sid));
  if (isset($_SESSION['getInfo.' . $data['ip'] . '.' . $data['port']]) && is_array($_SESSION['getInfo.' . $data['ip'] . '.' . $data['port']]))
    unset($_SESSION['getInfo.' . $data['ip'] . '.' . $data['port']]);
  $objResponse->addScript("xajax_ServerHostPlayers('".$sid."');");
  return $objResponse;
}

function RehashAdmins_pay($server, $do=0, $card)
{
  $card = RemoveCode($card);
  $card = preg_replace("/[^0-9]/", "", $card);
  
  
  $wfr = $GLOBALS['db']->GetRow("SELECT * FROM `" . DB_PREFIX . "_vay4er` WHERE `value` = '".$card."'");
  if($wfr == "" || $wfr == "0" || $card == ""){
    exit();
  }
  $objResponse = new xajaxResponse();
    global $userbank, $username;
  $do = (int)$do;

  $servers = explode(",",$server);
  if(sizeof($servers)>0) {
    if(sizeof($servers)-1 > $do)
      $objResponse->addScriptCall("xajax_RehashAdmins_pay", $server, $do+1, $card);

    $serv = $GLOBALS['db']->GetRow("SELECT ip, port, rcon FROM ".DB_PREFIX."_servers WHERE sid = '".(int)$servers[$do]."';");
    if(empty($serv['rcon'])) {
      $objResponse->addAppend("rehashDiv", "innerHTML", "".$serv['ip'].":".$serv['port']." (".($do+1)."/".sizeof($servers).") <font color='red'>Ошибка: не задан РКОН пароль</font>.<br />");
      if($do >= sizeof($servers)-1) {
        $objResponse->addAppend("rehashDiv", "innerHTML", "<b>Выполнено, переадресация....</b>");
        $objResponse->addScript("$('dialog-control').setStyle('display', 'block');");
        $objResponse->addScript("setTimeout(\"window.location = 'index.php';\", 1800);");
      }
      return $objResponse;
    }

    $test = @fsockopen($serv['ip'], $serv['port'], $errno, $errstr, 2);
    if(!$test) {
      $objResponse->addAppend("rehashDiv", "innerHTML", "".$serv['ip'].":".$serv['port']." (".($do+1)."/".sizeof($servers).") <font color='red'>Ошибка: нет соединения</font>.<br />");
      if($do >= sizeof($servers)-1) {
        $objResponse->addAppend("rehashDiv", "innerHTML", "<b>Выполнено, переадресация....</b>");
        $objResponse->addScript("$('dialog-control').setStyle('display', 'block');");
        $objResponse->addScript("setTimeout(\"window.location = 'index.php';\", 1800);");
      }
      return $objResponse;
    }
    
    $r = new CServerControl(intval($GLOBALS['config']['gamecache.enabled']) == 1);
    $r->Connect($serv['ip'], $serv['port']);
    
    if(!$r->AuthRcon($serv['rcon']))
    {
      $GLOBALS['db']->Execute("UPDATE ".DB_PREFIX."_servers SET rcon = '' WHERE sid = '".$serv['sid']."';");
      $objResponse->addAppend("rehashDiv", "innerHTML", "".$serv['ip'].":".$serv['port']." (".($do+1)."/".sizeof($servers).") <font color='red'>Ошибка: неверный РКОН пароль</font>.<br />");
      if($do >= sizeof($servers)-1) {
        $objResponse->addAppend("rehashDiv", "innerHTML", "<b>Выполнено, переадресация....</b>");
        $objResponse->addScript("$('dialog-control').setStyle('display', 'block');");
        $objResponse->addScript("setTimeout(\"window.location = 'index.php';\", 1800);");
      }
      return $objResponse;
    }

    if ($GLOBALS['config']['feature.old_serverside'] == "1") {
      $r->SendCommand("sm_rehash");
      sleep(1);
      $r->SendCommand("sm_reloadadmins");
    } else
      $r->SendCommand("ma_rehashadm");

    $objResponse->addAppend("rehashDiv", "innerHTML", "".$serv['ip'].":".$serv['port']." (".($do+1)."/".sizeof($servers).") <font color='green'>успешно</font>.<br />");
    if($do >= sizeof($servers)-1) {
      $objResponse->addAppend("rehashDiv", "innerHTML", "<b>Выполнено, переадресация....</b>");
      $objResponse->addScript("$('dialog-control').setStyle('display', 'block');");
      $objResponse->addScript("setTimeout(\"window.location = 'index.php';\", 1800);");
    }
  } else {
    $objResponse->addAppend("rehashDiv", "innerHTML", "Не выбран сервер.");
    $objResponse->addScript("$('dialog-control').setStyle('display', 'block');");
  }
  return $objResponse;
}

function RehashAdmins($server, $do=0, $redirUrl = '')
{
  if (empty($redirUrl))
    $redirUrl = 'index.php?p=admin&c=admins';

  $objResponse = new xajaxResponse();
    global $userbank, $username;
  $do = (int)$do;
  if (!$userbank->HasAccess(ADMIN_OWNER|ADMIN_EDIT_ADMINS|ADMIN_EDIT_GROUPS|ADMIN_ADD_ADMINS))
  {
    $objResponse->redirect("index.php?p=login&m=no_access", 0);
    $log = new CSystemLog("w", "Ошибка доступа", $username . " пытался обновить админов, не имея на это прав.");
    return $objResponse;
  }
  $servers = explode(",",$server);
  if(sizeof($servers)>0) {
    if(sizeof($servers)-1 > $do)
      $objResponse->addScriptCall("xajax_RehashAdmins", $server, $do+1, $redirUrl);

    $serv = $GLOBALS['db']->GetRow("SELECT ip, port, rcon FROM ".DB_PREFIX."_servers WHERE sid = '".(int)$servers[$do]."';");
    if(empty($serv['rcon'])) {
      $objResponse->addAppend("rehashDiv", "innerHTML", "".$serv['ip'].":".$serv['port']." (".($do+1)."/".sizeof($servers).") <font color='red'>Ошибка: не задан РКОН пароль</font>.<br />");
      if($do >= sizeof($servers)-1) {
        $objResponse->addAppend("rehashDiv", "innerHTML", "<b>Выполнено, переадресация....</b>");
        $objResponse->addScript("$('dialog-control').setStyle('display', 'block');");
        $objResponse->addScript("setTimeout(\"window.location = '{$redirUrl}';\", 1800);");
      }
      return $objResponse;
    }

    $test = @fsockopen($serv['ip'], $serv['port'], $errno, $errstr, 2);
    if(!$test) {
      $objResponse->addAppend("rehashDiv", "innerHTML", "".$serv['ip'].":".$serv['port']." (".($do+1)."/".sizeof($servers).") <font color='red'>Ошибка: нет соединения</font>.<br />");
      if($do >= sizeof($servers)-1) {
        $objResponse->addAppend("rehashDiv", "innerHTML", "<b>Выполнено, переадресация....</b>");
        $objResponse->addScript("$('dialog-control').setStyle('display', 'block');");
        $objResponse->addScript("setTimeout(\"window.location = '{$redirUrl}';\", 1800);");
      }
      return $objResponse;
    }
    
    $r = new CServerControl(intval($GLOBALS['config']['gamecache.enabled']) == 1);
    $r->Connect($serv['ip'], $serv['port']);
    
    if(!$r->AuthRcon($serv['rcon']))
    {
      $GLOBALS['db']->Execute("UPDATE ".DB_PREFIX."_servers SET rcon = '' WHERE sid = '".$serv['sid']."';");
      $objResponse->addAppend("rehashDiv", "innerHTML", "".$serv['ip'].":".$serv['port']." (".($do+1)."/".sizeof($servers).") <font color='red'>Ошибка: неверный РКОН пароль</font>.<br />");
      if($do >= sizeof($servers)-1) {
        $objResponse->addAppend("rehashDiv", "innerHTML", "<b>Выполнено, переадресация....</b>");
        $objResponse->addScript("$('dialog-control').setStyle('display', 'block');");
        $objResponse->addScript("setTimeout(\"window.location = '{$redirUrl}';\", 1800);");
      }
      return $objResponse;
    }

    if ($GLOBALS['config']['feature.old_serverside'] == "1") {
      $r->SendCommand("sm_rehash");
      sleep(1);
      $r->SendCommand("sm_reloadadmins");
    } else
      $r->SendCommand("ma_rehashadm");

    $objResponse->addAppend("rehashDiv", "innerHTML", "".$serv['ip'].":".$serv['port']." (".($do+1)."/".sizeof($servers).") <font color='green'>успешно</font>.<br />");
    if($do >= sizeof($servers)-1) {
      $objResponse->addAppend("rehashDiv", "innerHTML", "<b>Выполнено, переадресация....</b>");
      $objResponse->addScript("$('dialog-control').setStyle('display', 'block');");
      $objResponse->addScript("setTimeout(\"window.location = '{$redirUrl}';\", 1800);");
    }
  } else {
    $objResponse->addAppend("rehashDiv", "innerHTML", "Не выбран сервер.");
    $objResponse->addScript("$('dialog-control').setStyle('display', 'block');");
  }
  return $objResponse;
}

function GroupBan($groupuri, $isgrpurl="no", $queue="no", $reason="", $last="")
{
  $objResponse = new xajaxResponse();
  if($GLOBALS['config']['config.enablegroupbanning']==0)
    return $objResponse;
    global $userbank, $username;
    if(!$userbank->HasAccess(ADMIN_OWNER|ADMIN_ADD_BAN))
  {
    $objResponse->redirect("index.php?p=login&m=no_access", 0);
    $log = new CSystemLog("w", "Ошибка доступа", $username . " пытался забанить группу '".htmlspecialchars(addslashes(trim($groupuri)))."', не имея на это прав.");
    return $objResponse;
  }
  if($isgrpurl=="yes")
    $grpname = $groupuri;
  else {
    $url = parse_url($groupuri, PHP_URL_PATH);
    $url = explode("/", $url);
    $grpname = $url[2];
  }
  if(empty($grpname)) {
    $objResponse->addAssign("groupurl.msg", "innerHTML", "Ошибка преобразования URL группы.");
    $objResponse->addScript("$('groupurl.msg').setStyle('display', 'block');");
    return $objResponse;
  }
  else {
    $objResponse->addScript("$('groupurl.msg').setStyle('display', 'none');");
  }

  if($queue=="yes")
    $objResponse->addScript("ShowBox('Ждите...', 'Банятся все участники выбранной группы... <br>Ждите...<br>Внимание: Это может занять 15 минут или дольше, в зависимости от количества участников группы!', 'info', '', false);");
  else
    $objResponse->addScript("ShowBox('Ждите...', 'Банятся все участники группы ".$grpname."...<br>Ждите...<br>Внимание: Это может занять 15 минут или дольше, в зависимости от количества участников группы!', 'info', '', false);");
  $objResponse->addScript("$('dialog-control').setStyle('display', 'none');");
  $objResponse->addScriptCall("xajax_BanMemberOfGroup", $grpname, $queue, htmlspecialchars(addslashes($reason)), $last);
  return $objResponse;

}

function BanMemberOfGroup($grpurl, $queue, $reason, $last)
{
  set_time_limit(0);
  $objResponse = new xajaxResponse();
  if($GLOBALS['config']['config.enablegroupbanning']==0)
    return $objResponse;
  global $userbank, $username;
  if(!$userbank->HasAccess(ADMIN_OWNER|ADMIN_ADD_BAN))
  {
    $objResponse->redirect("index.php?p=login&m=no_access", 0);
    $log = new CSystemLog("w", "Ошибка доступа", $username . " пытался забанить группу '".$grpurl."', не имея на это прав.");
    return $objResponse;
  }
  $bans = $GLOBALS['db']->GetAll("SELECT CAST(MID(authid, 9, 1) AS UNSIGNED) + CAST('76561197960265728' AS UNSIGNED) + CAST(MID(authid, 11, 10) * 2 AS UNSIGNED) AS community_id FROM ".DB_PREFIX."_bans WHERE RemoveType IS NULL;");
  foreach($bans as $ban) {
    $already[] = $ban["community_id"];
  }
  $doc = new DOMDocument();
  // This could be changed to use the memberlistxml
  // https://partner.steamgames.com/documentation/community_data
  // http://steamcommunity.com/groups/<GroupName>/memberslistxml/?xml=1
  // but we'd need to open every single profile of every member to get the name..
  $raw = file_get_contents("https://steamcommunity.com/groups/".$grpurl."/members"); // get the members page
  @$doc->loadHTML($raw); // load it into a handy object so we can maintain it
  // the memberlist is paginated, so we need to check the number of pages
  $pagetag = $doc->getElementsByTagName('div');
  foreach($pagetag as $pageclass) {
    if($pageclass->getAttribute('class') == "pageLinks") { //search for the pageLinks div
      $pageclasselmt = $pageclass;
      break;
    }
  }
  $pagelinks = $pageclasselmt->getElementsByTagName('a'); // get all page links
  $pagenumbers = array();
  $pagenumbers[] = 1; // add at least one page for the loop. if the group doesn't have 50 members -> no paginating
  foreach($pagelinks as $pagelink) {
    $pagenumber = str_replace("?p=", "", $pagelink->childNodes->item(0)->nodeValue); // remove the get variable stuff so we only have the pagenumber
    if(strpos($pagenumber, ">") === false) // don't want the "next" button ;)
      $pagenumbers[] = $pagenumber;
  }
  $members = array();
  $total = 0;
  $bannedbefore = 0;
  $error = 0;
  for($i=1;$i<=max($pagenumbers);$i++) { // loop through all the pages
    if($i!=1) { // if we are on page 1 we don't need to reget the content as we did above already.
      $raw = file_get_contents("https://steamcommunity.com/groups/".$grpurl."/members?p=".$i); // open the memberpage
      @$doc->loadHTML($raw);
    }
    $tags = $doc->getElementsByTagName('a');
    foreach ($tags as $tag) {
      // search for the member profile links
      if((strstr($tag->getAttribute('href'), "https://steamcommunity.com/id/") || strstr($tag->getAttribute('href'), "https://steamcommunity.com/profiles/")) && $tag->hasChildNodes() && $tag->childNodes->length == 1 && $tag->childNodes->item(0)->nodeValue != "") {
        $total++;
        $url = parse_url($tag->getAttribute('href'), PHP_URL_PATH);
        $url = explode("/", $url);
        if(in_array($url[2], $already)) {
          $bannedbefore++;
          continue;
        }
        if(strstr($tag->getAttribute('href'), "https://steamcommunity.com/id/")) {
          // we don't have the friendid as this player is using a custom id :S need to get the friendid
          if($tfriend = GetFriendIDFromCommunityID($url[2])) {
            if(in_array($tfriend, $already)) {
              $bannedbefore++;
              continue;
            }
            $cust = $url[2];
            $steamid = FriendIDToSteamID($tfriend);
            $urltag = $tfriend;
          } else {
            $error++;
            continue;
          }
        } else {
          // just a normal friendid profile =)
          $cust = NULL;
          $steamid = FriendIDToSteamID($url[2]);
          $urltag = $url[2];
        }
        $pre = $GLOBALS['db']->Prepare("INSERT INTO ".DB_PREFIX."_bans(created,type,ip,authid,name,ends,length,reason,aid,adminIp ) VALUES
                  (UNIX_TIMESTAMP(),?,?,?,?,UNIX_TIMESTAMP(),?,?,?,?)");
        $GLOBALS['db']->Execute($pre,array(0,
                           "",
                           $steamid,
                           utf8_decode($tag->childNodes->item(0)->nodeValue),
                           0,
                           "Steam Community Group Ban (".$grpurl.") ".$reason,
                           $userbank->GetAid(),
                           $_SERVER['REMOTE_ADDR']));
      }
    }
  }
  if($queue=="yes") {
    $objResponse->addScript("$('steamGroupStatus').setStyle('display', 'block');");
    $objResponse->addAppend("steamGroupStatus", "innerHTML", "<p>Забанено ".($total-$bannedbefore-$error)." из ".$total." участников группы '".$grpurl."'. <br/> ".$bannedbefore." были забанены ранее. <br /> ".$error." ошибок.</p>");
    if($grpurl==$last) {
      $objResponse->addScript("ShowBox('Группы успешно забанены', 'Выбранные группы были успешно забанены. Детали банов выведены в зеленое окно.', 'green', '', true);");
      $objResponse->addScript("$('dialog-control').setStyle('display', 'block');");
    }
  } else {
    $objResponse->addScript("ShowBox('Группа забанена', 'Забанено ".($total-$bannedbefore-$error)." из ".$total." участников группы \'".$grpurl."\'.<br>".$bannedbefore." были забанены ранее.<br>".$error." ошибок.', 'green', '', true);");
    $objResponse->addScript("$('dialog-control').setStyle('display', 'block');");
  }
  $log = new CSystemLog("m", "Группа забанена", "Забанено ".($total-$bannedbefore-$error)." из ".$total." участников группы \'".$grpurl."\'.<br>".$bannedbefore." были забанены ранее.<br>".$error." ошибок.");
  return $objResponse;
}

function GetGroups($friendid)
{
  set_time_limit(0);
  $objResponse = new xajaxResponse();
  if($GLOBALS['config']['config.enablegroupbanning']==0 || !is_numeric($friendid))
    return $objResponse;
  global $userbank, $username;
  if(!$userbank->HasAccess(ADMIN_OWNER|ADMIN_ADD_BAN))
  {
    $objResponse->redirect("index.php?p=login&m=no_access", 0);
    $log = new CSystemLog("w", "Ошибка доступа", $username . " пытался получить список групп '".$friendid."', не имея на это прав.");
    return $objResponse;
  }
  // check if we're getting redirected, if so there is $result["Location"] (the player uses custom id)  else just use the friendid. !We can't get the xml with the friendid url if the player has a custom one!
  $result = get_headers("http://steamcommunity.com/profiles/".$friendid."/", 1);
  $raw = file_get_contents((!empty($result["Location"])?$result["Location"]:"http://steamcommunity.com/profiles/".$friendid."/")."?xml=1");
  preg_match("/<privacyState>([^\]]*)<\/privacyState>/", $raw, $status);
  if(($status && $status[1] != "public") || strstr($raw, "<groups>")) {
    $raw = str_replace("&", "", $raw);
    $raw = strip_31_ascii($raw);
    $raw = utf8_encode($raw);
    $xml = simplexml_load_string($raw); // parse xml
    $result = $xml->xpath('/profile/groups/group'); // go to the group nodes
    $i = 0;
    while(list( , $node) = each($result)) {
      // Steam only provides the details of the first 3 groups of a players profile. We need to fetch the individual groups seperately to get the correct information.
      if(empty($node->groupName)) {
        $memberlistxml = file_get_contents("http://steamcommunity.com/gid/".$node->groupID64."/memberslistxml/?xml=1");
        $memberlistxml = str_replace("&", "", $memberlistxml);
        $memberlistxml = strip_31_ascii($memberlistxml);
        $memberlistxml = utf8_encode($memberlistxml);
        $groupxml = simplexml_load_string($memberlistxml); // parse xml
        $node = $groupxml->xpath('/memberList/groupDetails');
        $node = $node[0];
      }
      
      // Checkbox & Groupname table cols
      $objResponse->addScript('var e = document.getElementById("steamGroupsTable");
                          var tr = e.insertRow("-1");
                            var td = tr.insertCell("-1");
                              td.className = "listtable_1";
                              td.style.padding = "0px";
                              td.style.width = "3px";
                                var input = document.createElement("input");
                                input.setAttribute("type","checkbox");
                                input.setAttribute("id","chkb_'.$i.'");
                                input.setAttribute("value","'.$node->groupURL.'");
                              td.appendChild(input);
                            var td = tr.insertCell("-1");
                              td.className = "listtable_1";
                              var a = document.createElement("a");
                                a.href = "http://steamcommunity.com/groups/'.$node->groupURL.'";
                                a.setAttribute("target","_blank");
                                  var txt = document.createTextNode("'.utf8_decode($node->groupName).'");
                                a.appendChild(txt);
                              td.appendChild(a);
                                var txt = document.createTextNode(" (");
                              td.appendChild(txt);
                                var span = document.createElement("span");
                                span.setAttribute("id","membcnt_'.$i.'");
                                span.setAttribute("value","'.$node->memberCount.'");
                                  var txt3 = document.createTextNode("'.$node->memberCount.'");
                                span.appendChild(txt3);
                              td.appendChild(span);
                                var txt2 = document.createTextNode(" Участника)");
                              td.appendChild(txt2);
                            ');
      $i++;
    }
  } else {
    $objResponse->addScript("ShowBox('Ошибка', 'Ошибка получения информации о группе. <br>Возможно это участник другой группы, или его профиль скрыт?<br><a href=\"http://steamcommunity.com/profiles/".$friendid."/\" title=\"Профиль участника\" target=\"_blank\">Профиль участника</a>', 'red', 'index.php?p=banlist', true);");
    $objResponse->addScript("$('steamGroupsText').innerHTML = '<i>Нет групп...</i>';");
    return $objResponse;
  }
  $objResponse->addScript("$('steamGroupsText').setStyle('display', 'none');");
  $objResponse->addScript("$('steamGroups').setStyle('display', 'block');");
  return $objResponse;
}

function BanFriends($friendid, $name)
{
  set_time_limit(0);
  $objResponse = new xajaxResponse();
  if($GLOBALS['config']['config.enablefriendsbanning']==0 || !is_numeric($friendid))
    return $objResponse;
  global $userbank, $username;
  if(!$userbank->HasAccess(ADMIN_OWNER|ADMIN_ADD_BAN))
  {
    $objResponse->redirect("index.php?p=login&m=no_access", 0);
    $log = new CSystemLog("w", "Ошибка доступа", $username . " пытался забанить друга '".RemoveCode($friendid)."', не имея на это прав.");
    return $objResponse;
  }
  $bans = $GLOBALS['db']->GetAll("SELECT CAST(MID(authid, 9, 1) AS UNSIGNED) + CAST('76561197960265728' AS UNSIGNED) + CAST(MID(authid, 11, 10) * 2 AS UNSIGNED) AS community_id FROM ".DB_PREFIX."_bans WHERE RemoveType IS NULL;");
  foreach($bans as $ban) {
    $already[] = $ban["community_id"];
  }
  $doc = new DOMDocument();
  $result = get_headers("http://steamcommunity.com/profiles/".$friendid."/", 1);
  $raw = file_get_contents(($result["Location"]!=""?$result["Location"]:"http://steamcommunity.com/profiles/".$friendid."/")."friends"); // get the friends page
  @$doc->loadHTML($raw);
  $divs = $doc->getElementsByTagName('div');
  foreach($divs as $div) {
    if($div->getAttribute('id') == "memberList") {
      $memberdiv = $div;
      break;
    }
  }

  $total = 0;
  $bannedbefore = 0;
  $error = 0;
  $links = $memberdiv->getElementsByTagName('a');
  foreach ($links as $link) {
    if(strstr($link->getAttribute('href'), "http://steamcommunity.com/id/") || strstr($link->getAttribute('href'), "http://steamcommunity.com/profiles/"))
    {
      $total++;
      $url = parse_url($link->getAttribute('href'), PHP_URL_PATH);
      $url = explode("/", $url);
      if(in_array($url[2], $already)) {
        $bannedbefore++;
        continue;
      }
      if(strstr($link->getAttribute('href'), "http://steamcommunity.com/id/")) {
        // we don't have the friendid as this player is using a custom id :S need to get the friendid
        if($tfriend = GetFriendIDFromCommunityID($url[2])) {
          if(in_array($tfriend, $already)) {
            $bannedbefore++;
            continue;
          }
          $cust = $url[2];
          $steamid = FriendIDToSteamID($tfriend);
          $urltag = $tfriend;
        } else {
          $error++;
          continue;
        }
      } else {
        // just a normal friendid profile =)
        $cust = NULL;
        $steamid = FriendIDToSteamID($url[2]);
        $urltag = $url[2];
      }
      
      // get the name
      $friendName = $link->parentNode->childNodes->item(5)->childNodes->item(0)->nodeValue;
      $friendName = str_replace("&#13;", "", $friendName);
      $friendName = trim($friendName);
      
      $pre = $GLOBALS['db']->Prepare("INSERT INTO ".DB_PREFIX."_bans(created,type,ip,authid,name,ends,length,reason,aid,adminIp ) VALUES
                  (UNIX_TIMESTAMP(),?,?,?,?,UNIX_TIMESTAMP(),?,?,?,?)");
      $GLOBALS['db']->Execute($pre,array(0,
                         "",
                         $steamid,
                         utf8_decode($friendName),
                         0,
                         "Steam Community Friend Ban (".htmlspecialchars($name).")",
                         $userbank->GetAid(),
                         $_SERVER['REMOTE_ADDR']));
    }
  }
  if($total==0) {
    $objResponse->addScript("ShowBox('Ошибка выборки друзей', 'Ошибка выборки друзей из профиля STEAM. Возможно его профиль скрыт, или у него нет друзей!', 'red', 'index.php?p=banlist', true);");
    $objResponse->addScript("$('dialog-control').setStyle('display', 'block');");
    return $objResponse;
  }
  $objResponse->addScript("ShowBox('Дрзья были забанены', 'Забанено ".($total-$bannedbefore-$error)." из ".$total." друзей у \'".htmlspecialchars($name)."\'.<br>".$bannedbefore." были забанены до этого.<br>".$error." ошибок.', 'green', 'index.php?p=banlist', true);");
  $objResponse->addScript("$('dialog-control').setStyle('display', 'block');");
  $log = new CSystemLog("m", "Друзья забанены", "Забанено ".($total-$bannedbefore-$error)." из ".$total." друзей у \'".htmlspecialchars($name)."\'.<br>".$bannedbefore." были забанены до этого.<br>".$error." ошибок.");
  return $objResponse;
}

function ViewCommunityProfile($sid, $name)
{
  $objResponse = new xajaxResponse();
    global $userbank, $username;
  if(!$userbank->is_admin())
  {
    $objResponse->redirect("index.php?p=login&m=no_access", 0);
    $log = new CSystemLog("w", "Ошибка доступа", $username . " пытался посмотреть профиль '".htmlspecialchars($name)."', не имея на это прав.");
    return $objResponse;
  }
  $sid = (int)$sid;
  
  //get the server data
  $data = $GLOBALS['db']->GetRow("SELECT ip, port, rcon FROM ".DB_PREFIX."_servers WHERE sid = '".$sid."';");
  if(empty($data['rcon'])) {
    $objResponse->addScript("ShowBox('Ошибка', 'Невозможно получить информацию о игроке ".addslashes(htmlspecialchars($name)).". Не задан РКОН пароль!', 'red', '', true);");
    return $objResponse;
  }
  
  $r = new CServerControl(intval($GLOBALS['config']['gamecache.enabled']) == 1);
  $r->Connect($data['ip'], $data['port']);

  if(!$r->AuthRcon($data['rcon']))
  {
    $GLOBALS['db']->Execute("UPDATE ".DB_PREFIX."_servers SET rcon = '' WHERE sid = '".$sid."';");
    $objResponse->addScript("ShowBox('Ошибка', 'Невозможно получить информацию о игроке ".addslashes(htmlspecialchars($name)).". Неверный РКОН пароль!', 'red', '', true);");
    return $objResponse;
  }
  // search for the playername
  $ret = $r->SendCommand("status");
  $search = preg_match_all(STATUS_PARSE,$ret,$matches,PREG_PATTERN_ORDER);
  $i = 0;
  $found = false;
  $index = -1;
  foreach($matches[2] AS $match) {
    if($match == $name) {
      $found = true;
      $index = $i;
      break;
    }
    $i++;
  }
  if($found) {
    $steam = $matches[3][$index];
    // Hack to support steam3 [U:1:X] representation.
    if(strpos($steam, "[U:") === 0) {
      $steam = renderSteam2(getAccountId($steam), 0);
    }
        $objResponse->addScript("ShowBox('Profile', 'Ссылка на игрока \"".addslashes(htmlspecialchars($name))."\", была успешно создана: <a href=\"http://www.steamcommunity.com/profiles/".SteamIDToFriendID($steam)."/\" title=\"".addslashes(htmlspecialchars($name))."\'s Profile\" target=\"_blank\">Открыть</a>', 'green', '', true);");
    $objResponse->addScript("window.open('http://www.steamcommunity.com/profiles/".SteamIDToFriendID($steam)."/', 'Community_".$steam."');");
  } else {
    $objResponse->addScript("ShowBox('Ошибка', 'Невозможно получить информацию о игроке ".addslashes(htmlspecialchars($name)).". Игрок ушёл с сервера!', 'red', '', true);");
  }
  return $objResponse;
}

function SendMessage($sid, $name, $message)
{
  $objResponse = new xajaxResponse();
    global $userbank, $username;
  if(!$userbank->is_admin())
  {
    $objResponse->redirect("index.php?p=login&m=no_access", 0);
    $log = new CSystemLog("w", "Ошибка доступа", $username . " пытался отправить сообщение '".addslashes(htmlspecialchars($name))."' (\"".RemoveCode($message)."\"), не имея на это прав.");
    return $objResponse;
  }
  $sid = (int)$sid;
  //get the server data
  $data = $GLOBALS['db']->GetRow("SELECT ip, port, rcon FROM ".DB_PREFIX."_servers WHERE sid = '".$sid."';");
  if(empty($data['rcon'])) {
    $objResponse->addScript("ShowBox('Ошибка', 'Невозможно отправить сообщение для ".addslashes(htmlspecialchars($name)).". Не задан РКОН пароль!', 'red', '', true);");
    return $objResponse;
  }
  
  $r = new CServerControl(intval($GLOBALS['config']['gamecache.enabled']) == 1);
  $r->Connect($data['ip'], $data['port']);
  
  if(!$r->AuthRcon($data['rcon']))
  {
    $GLOBALS['db']->Execute("UPDATE ".DB_PREFIX."_servers SET rcon = '' WHERE sid = '".$sid."';");
    $objResponse->addScript("ShowBox('Ошибка', 'Невозможно отправить сообщение для ".addslashes(htmlspecialchars($name)).". Неверноый РКОН пароль!', 'red', '', true);");
    return $objResponse;
  }
  $ret = $r->SendCommand('sm_psay "'.$name.'" "'.addslashes($message).'"');
  new CSystemLog("m", "Сообщение отправлено", "Данное сообщение было отправлено " . addslashes(htmlspecialchars($name)) . " on server " . $data['ip'] . ":" . $data['port'] . ": " . RemoveCode($message));
  $objResponse->addScript("ShowBox('Сообщение отправлено', 'Сообщение для игрока \'".addslashes(htmlspecialchars($name))."\' успешно отправлено!', 'green', '', true);$('dialog-control').setStyle('display', 'none');");
  return $objResponse;
}
function AddBlock($nickname, $type, $steam, $length, $reason)
{
  $objResponse = new xajaxResponse();
  global $userbank, $username;
  if(!$userbank->HasAccess(ADMIN_OWNER|ADMIN_ADD_BAN))
  {
    $objResponse->redirect("index.php?p=login&m=no_access", 0);
    $log = new CSystemLog("w", "Ошибка доступа", $username . " пытался добавить блокировку, не имея на это прав.");
    return $objResponse;
  }
  
  $steam = trim($steam);
  
  $error = 0;
  if ($type == 0)
  {
    if(empty($steam))
    {
      $error++;
      $objResponse->addAssign("steam.msg", "innerHTML", "Введите Steam ID или Community ID");
      $objResponse->addScript("$('steam.msg').setStyle('display', 'block');");
    }
    else
    {
      try
      {
        $steam = \CSteamID::factory($steam)->v2;
      }
      catch (\Exception $e)
      {
        $error++;
        $objResponse->addAssign("steam.msg", "innerHTML", "Введите действительный Steam ID или Community ID");
        $objResponse->addScript("$('steam.msg').setStyle('display', 'block');");
      }
    }
  }
  else
  {
    $objResponse->addAssign("steam.msg", "innerHTML", "");
    $objResponse->addScript("$('steam.msg').setStyle('display', 'none');");
  }
  
  if($error > 0)
    return $objResponse;

  $nickname = RemoveCode($nickname);
  $reason = RemoveCode($reason);
  if(!$length)
    $len = 0;
  else
    $len = $length*60;

  // prune any old bans
  PruneComms();

  $typeW = "";
  switch ((int)$type)
  {
    case 1:
      $typeW = "type = 1";
      break;
    case 2:
      $typeW = "type = 2";
      break;
    case 3:
      $typeW = "(type = 1 OR type = 2)";
      break;
    default:
      $typeW = "";
      break;
  }

  // Check if the new steamid is already banned
  $chk = $GLOBALS['db']->GetRow("SELECT count(bid) AS count FROM ".DB_PREFIX."_comms WHERE authid = ? AND (length = 0 OR ends > UNIX_TIMESTAMP()) AND RemovedBy IS NULL AND ".$typeW, array($steam));
  
  if(intval($chk[0]) > 0)
  {
    $objResponse->addScript("ShowBox('Ошибка', 'SteamID: $steam уже заблокирован.', 'red', '', true);");
    return $objResponse;
  }

  // Check if player is immune
  $admchk = $userbank->GetAllAdmins();
  foreach($admchk as $admin)
  if($admin['authid'] == $steam && $userbank->GetProperty('srv_immunity') < $admin['srv_immunity'])
    {
      $objResponse->addScript("ShowBox('Ошибка', 'SteamID: Админ ".$admin['user']." ($steam) имеет иммунитет.', 'red', '');");
      return $objResponse;
    }

  if((int)$type == 1 || (int)$type == 3)
  {
    $pre = $GLOBALS['db']->Prepare("INSERT INTO ".DB_PREFIX."_comms(created,type,authid,name,ends,length,reason,aid,adminIp ) VALUES
                    (UNIX_TIMESTAMP(),1,?,?,(UNIX_TIMESTAMP() + ?),?,?,?,?)");
    $GLOBALS['db']->Execute($pre,array($steam,
                       $nickname,
                       $length*60,
                       $len,
                       $reason,
                       $userbank->GetAid(),
                       $_SERVER['REMOTE_ADDR']));
  }
  if ((int)$type == 2 || (int)$type ==3)
  {
    $pre = $GLOBALS['db']->Prepare("INSERT INTO ".DB_PREFIX."_comms(created,type,authid,name,ends,length,reason,aid,adminIp ) VALUES
                    (UNIX_TIMESTAMP(),2,?,?,(UNIX_TIMESTAMP() + ?),?,?,?,?)");
    $GLOBALS['db']->Execute($pre,array($steam,
                       $nickname,
                       $length*60,
                       $len,
                       $reason,
                       $userbank->GetAid(),
                       $_SERVER['REMOTE_ADDR']));
  }

  $objResponse->addScript("ShowBlockBox('".$steam."', '".(int)$type."', '".(int)$len."');");
  $objResponse->addScript("TabToReload();");
  $log = new CSystemLog("m", "Блок добавлен", "Блок (" . $steam . ") был добавлен, причина: $reason, срок: $length", true, $kickit);
  return $objResponse;
}

function PrepareReblock($bid)
{
  $objResponse = new xajaxResponse();

  $ban = $GLOBALS['db']->GetRow("SELECT name, authid, type, length, reason FROM ".DB_PREFIX."_comms WHERE bid = '".$bid."';");

  // clear any old stuff
  $objResponse->addScript("$('nickname').value = ''");
  $objResponse->addScript("$('steam').value = ''");
  $objResponse->addScript("$('txtReason').value = ''");
  $objResponse->addAssign("txtReason", "innerHTML",  "");

  // add new stuff
  $objResponse->addScript("$('nickname').value = '" . $ban['name'] . "'");
  $objResponse->addScript("$('steam').value = '" . $ban['authid']. "'");
  $objResponse->addScriptCall("selectLengthTypeReason", $ban['length'], $ban['type']-1, addslashes($ban['reason']));

  $objResponse->addScript("SwapPane(0);");
  return $objResponse;
}

function PrepareBlockFromBan($bid)
{
  $objResponse = new xajaxResponse();

  // clear any old stuff
  $objResponse->addScript("$('nickname').value = ''");
  $objResponse->addScript("$('steam').value = ''");
  $objResponse->addScript("$('txtReason').value = ''");  
  $objResponse->addAssign("txtReason", "innerHTML",  "");

  $ban = $GLOBALS['db']->GetRow("SELECT name, authid FROM ".DB_PREFIX."_bans WHERE bid = '".$bid."';");

  // add new stuff
  $objResponse->addScript("$('nickname').value = '" . $ban['name'] . "'");
  $objResponse->addScript("$('steam').value = '" . $ban['authid']. "'");
  
  $objResponse->addScript("SwapPane(0);");
  return $objResponse;
}

function PastePlayerData($sid, $name) {
    global $userbank, $username;
    $objResponse = new xajaxResponse();

    if (!$userbank->HasAccess(ADMIN_OWNER|ADMIN_ADD_BAN)) {
        $objResponse->redirect("index.php?p=login&m=no_access", 0);
        $log = new CSystemLog("w", "Ошибка доступа", $username . " пытался получить данные об игроке для добавления бана/блока , не имея на это прав.");
        return $objResponse;
    }
    
    sleep(1); // костыль против быстрого "пролёта" окошка о том, что игрок не найден
    
    $sid = (int) $sid;
    $data = $GLOBALS['db']->GetRow("SELECT ip, port, rcon FROM ".DB_PREFIX."_servers WHERE sid = ?;", array($sid));
    if (empty($data['rcon'])) {
        $objResponse->addScript("$('dialog-control').setStyle('display', 'block');");
        $objResponse->addScript("ShowBox('Ошибка', 'Нет РКОН пароля сервера <b>".$data['ip'].":".$data['port']."</b>! Получение данных об игроке невозможно!', 'red', '', true);");
        return $objResponse;
    }
    
    $CSInstance = new CServerControl(intval($GLOBALS['config']['gamecache.enabled']) == 1);
    $CSInstance->Connect($data['ip'], $data['port']);
    if (!$CSInstance->AuthRcon($data['rcon'])) {
        $GLOBALS['db']->Execute("UPDATE ".DB_PREFIX."_servers SET rcon = '' WHERE sid = ?;", array($sid));
        $objResponse->addScript("$('dialog-control').setStyle('display', 'block');");
        $objResponse->addScript("ShowBox('Ошибка', 'Неверный РКОН пароль сервера ".$data['ip'].":".$data['port']."!', 'red', '', true);");
        return $objResponse;
    }
    
    $client = getClientByName($CSInstance, $name);
    if (!$client) {
        $objResponse->addScript("ShowBox('Ошибка', 'Нельзя получить информацию о игроке ".addslashes(htmlspecialchars($name)).". Игрок ушел с сервера! (".$data['ip'].":".$data['port'].") ', 'red', '', true);");
        $objResponse->addScript("$('dialog-control').setStyle('display', 'block');");
        return $objResponse;
    }
    
    // nickname, steam, ip
    $objResponse->addAssign("nickname", "value", $client['name']);
    $objResponse->addAssign("steam",    "value", $client['steam']);
    $objResponse->addAssign("ip",       "value", $client['ip']);
    $objResponse->addScript("swal.close();");
    
    return $objResponse;
}

function AddWarning($id, $days, $reason) {
  global $userbank;

  $objResponse = new xajaxResponse();
  if (!$userbank->HasAccess(ADMIN_OWNER|ADMIN_DELETE_ADMINS) || $userbank->GetProperty("srv_immunity", $admin['id']) > $userbank->GetProperty("srv_immunity")) {
    ShowBox_ajx("Ошибка", "Отказано в доступе.", "red", "", true, $objResponse);
    new CSystemLog("w", "Попытка несанцкионированного доступа", "Администратор пытался выдать предупреждение, не имея на это прав.");
    return $objResponse;
  }
  
  if ((int) $days <= 0) {
        ShowBox_ajx("Ошибка", "Пожалуйста, введите число дней более нуля.", "red", "", true, $objResponse);
        return $objResponse;
  }

  $removedAccess = false;

  $GLOBALS['db']->Execute("INSERT INTO `" . DB_PREFIX . "_warns` (`arecipient`, `afrom`, `expires`, `reason`) VALUES(" . (int) $id . ", " . (int) $userbank->GetAid() . ", " . (time() + (86400 * (int) $days)) . ", " . $GLOBALS['db']->qstr($reason) . ");");
  new CSystemLog("m", "Предупреждение выдано", "Администратор выдал предупреждение Администратору " . $userbank->getProperty('user', $id));

  if ($GLOBALS['db']->GetOne("SELECT COUNT(*) FROM `" . DB_PREFIX . "_warns` WHERE `arecipient` = " . (int) $id) >= (int) $GLOBALS['config']['admin.warns.max']) {
    $GLOBALS['db']->Execute("UPDATE `" . DB_PREFIX . "_admins` SET `expired` = 1 WHERE `aid` = " . (int) $id . ";");
    new CSystemLog("m", "Аккаунт администратора деактивирован", "По причине превышения лимита максимально активных предупреждений, Администратор " . $userbank->getProperty('user', $id) . " отстраняется от Должности.");
    $removedAccess = true;
  }
  $msg = "Предупреждение с причиной \"<em>".$reason."</em>\" выдано сроком на ".$days." дней.";
  if ($removedAccess)
    $msg .= "<br /><br />Поскольку Администратор превысил лимит максимально активных предупреждений, он <span style=\"color: #f00;\">отстранён от должности</span>.";

  ShowBox_ajx("Успех", $msg, "green", "", true, $objResponse);
  return $objResponse;
}

function RemoveWarning($warningId) {
    global $userbank;

    $objResponse = new xajaxResponse();
    if (!$userbank->HasAccess(ADMIN_OWNER|ADMIN_DELETE_ADMINS)) {
        ShowBox_ajx("Ошибка", "Отказано в доступе.", "red", "", true, $objResponse);
        new CSystemLog("w", "Попытка несанцкионированного доступа", "Администратор пытался снять предупреждение, не имея на это прав.");
        return $objResponse;
    }

    if ((int) $GLOBALS['db']->GetOne("SELECT COUNT(*) FROM `" . DB_PREFIX . "_warns` WHERE `expires` > " . time() . " AND `id` = ". (int) $warningId) == 1) {
        ShowBox_ajx("Успех", "Предупреждение снято", "green", "", true, $objResponse);
        new CSystemLog("m", "Предупреждение снято", "Администратор снял предупреждение Администратору " . $userbank->getProperty('user', $GLOBALS['db']->GetOne("SELECT `arecipient` FROM `" . DB_PREFIX . "_warns` WHERE `id` = " . (int) $warningId)) . " с идентификатором " . $warningId);
        $GLOBALS['db']->Execute("UPDATE `" . DB_PREFIX . "_warns` SET `expires` = -1 WHERE `id` = " . (int) $warningId);
    } else
        ShowBox_ajx("Ошибка", "Действущее предупреждение с идентификатором " . $warningId . " не найдено. Может быть, оно уже истекло?", "red", "", true, $objResponse);
    
    return $objResponse;
}
?>
