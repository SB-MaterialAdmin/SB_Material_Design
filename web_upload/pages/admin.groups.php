<?php
/**************************************************************************
 * Эта программа является частью SourceBans MATERIAL Admin.
 *
 * Все права защищены © 2016-2017 Sergey Gut <webmaster@kruzefag.ru>
 *
 * SourceBans MATERIAL Admin распространяется под лицензией
 * Creative Commons Attribution-NonCommercial-ShareAlike 3.0.
 *
 * Вы должны были получить копию лицензии вместе с этой работой. Если нет,
 * см. <http://creativecommons.org/licenses/by-nc-sa/3.0/>.
 *
 * ПРОГРАММНОЕ ОБЕСПЕЧЕНИЕ ПРЕДОСТАВЛЯЕТСЯ «КАК ЕСТЬ», БЕЗ КАКИХ-ЛИБО
 * ГАРАНТИЙ, ЯВНЫХ ИЛИ ПОДРАЗУМЕВАЕМЫХ, ВКЛЮЧАЯ, НО НЕ ОГРАНИЧИВАЯСЬ,
 * ГАРАНТИИ ПРИГОДНОСТИ ДЛЯ КОНКРЕТНЫХ ЦЕЛЕЙ И НЕНАРУШЕНИЯ. НИ ПРИ КАКИХ
 * ОБСТОЯТЕЛЬСТВАХ АВТОРЫ ИЛИ ПРАВООБЛАДАТЕЛИ НЕ НЕСУТ ОТВЕТСТВЕННОСТИ ЗА
 * ЛЮБЫЕ ПРЕТЕНЗИИ, ИЛИ УБЫТКИ, НЕЗАВИСИМО ОТ ДЕЙСТВИЯ ДОГОВОРА,
 * ГРАЖДАНСКОГО ПРАВОНАРУШЕНИЯ ИЛИ ИНАЧЕ, ВОЗНИКАЮЩИЕ ИЗ, ИЛИ В СВЯЗИ С
 * ПРОГРАММНЫМ ОБЕСПЕЧЕНИЕМ ИЛИ ИСПОЛЬЗОВАНИЕМ ИЛИ ИНЫМИ ДЕЙСТВИЯМИ
 * ПРОГРАММНОГО ОБЕСПЕЧЕНИЯ.
 *
 * Эта программа базируется на работе, охватываемой следующим авторским
 *                                                           правом (ами):
 *
 *  * SourceBans ++
 *    Copyright © 2014-2016 Sarabveer Singh
 *    Выпущено под лицензией CC BY-NC-SA 3.0
 *    Страница: <https://sbpp.github.io/>
 *
 ***************************************************************************/

if (!defined('IN_SB')) {echo("Вы не должны быть здесь. Используйте только ссылки внутри системы!");die();}
global $userbank, $theme;

echo '<div id="admin-page-content">';

// web groups
$web_group_list = $GLOBALS['db']->GetAll("SELECT * FROM `" . DB_PREFIX . "_groups` WHERE type != '3'");
for($i=0;$i<count($web_group_list);$i++)
{
	$web_group_list[$i]['permissions'] = BitToString($web_group_list[$i]['flags'], $web_group_list[$i]['type']);
	$query = $GLOBALS['db']->GetRow("SELECT COUNT(gid) AS cnt FROM `" . DB_PREFIX . "_admins` WHERE gid = '" . $web_group_list[$i]['gid'] . "'");
	$web_group_count[$i] = $query['cnt'];
	$web_group_admins[$i] = $GLOBALS['db']->GetAll("SELECT aid, user, authid FROM `" . DB_PREFIX . "_admins` WHERE gid = '" . $web_group_list[$i]['gid'] . "'");
}

// Server admin groups
$server_admin_group_list = $GLOBALS['db']->GetAll("SELECT * FROM `" . DB_PREFIX . "_srvgroups`") ;
for($i=0;$i<count($server_admin_group_list);$i++)
{
	$server_admin_group_list[$i]['permissions'] = SmFlagsToSb($server_admin_group_list[$i]['flags']);
	$srvGroup = $GLOBALS['db']->qstr($server_admin_group_list[$i]['name']);
	$query = $GLOBALS['db']->GetRow("SELECT COUNT(aid) AS cnt FROM `" . DB_PREFIX . "_admins` WHERE srv_group = $srvGroup;");
	$server_admin_group_count[$i] = $query['cnt'];
	$server_admin_group_admins[$i] = $GLOBALS['db']->GetAll("SELECT aid, user, authid FROM `" . DB_PREFIX . "_admins` WHERE srv_group = $srvGroup;");
	$server_admin_group_overrides[$i] = $GLOBALS['db']->GetAll("SELECT type, name, access FROM `" . DB_PREFIX . "_srvgroups_overrides` WHERE group_id = ?", array($server_admin_group_list[$i]['id']));
}


// server groups
$server_group_list = $GLOBALS['db']->GetAll("SELECT * FROM `" . DB_PREFIX . "_groups` WHERE type = '3'") ;
for($i=0;$i<count($server_group_list);$i++)
{
	$query = $GLOBALS['db']->GetRow("SELECT COUNT(server_id) AS cnt FROM `" . DB_PREFIX . "_servers_groups` WHERE `group_id` = ".  $server_group_list[$i]['gid'] ) ;
	$server_group_count[$i] = $query['cnt'];
	$server_group_list[$i]['servers'] = $GLOBALS['db']->GetAll("SELECT server_id FROM `" . DB_PREFIX . "_servers_groups` WHERE group_id = " . $server_group_list[$i]['gid']);
}



// List Group
echo '<div id="0" style="display:none;">';

	$theme->assign('permission_listgroups', 	$userbank->HasAccess(ADMIN_OWNER|ADMIN_LIST_GROUPS));
	$theme->assign('permission_editgroup',		$userbank->HasAccess(ADMIN_OWNER|ADMIN_EDIT_GROUPS));
	$theme->assign('permission_deletegroup',	$userbank->HasAccess(ADMIN_OWNER|ADMIN_DELETE_GROUPS));
	$theme->assign('permission_editadmin',		$userbank->HasAccess(ADMIN_OWNER|ADMIN_EDIT_ADMINS));
	$theme->assign('web_group_count',			count($web_group_list));
	$theme->assign('web_admins', 				(isset($web_group_count)?$web_group_count:'0'));
	$theme->assign('web_admins_list',			$web_group_admins);
	$theme->assign('web_group_list', 			$web_group_list);
	$theme->assign('server_admin_group_count',	count($server_admin_group_list));
	$theme->assign('server_admins', 			(isset($server_admin_group_count)?$server_admin_group_count:'0'));
	$theme->assign('server_admins_list',		$server_admin_group_admins);
	$theme->assign('server_overrides_list',		$server_admin_group_overrides);
	$theme->assign('server_group_list', 		$server_admin_group_list);
	$theme->assign('server_group_count',		count($server_group_list));
	$theme->assign('server_list', 				$server_group_list);
	$theme->display('page_admin_groups_list.tpl');

echo '</div>';



// Add Groups
echo '<div id="1" style="display:none;">';
	$theme->assign('permission_addgroup', 		$userbank->HasAccess(ADMIN_OWNER|ADMIN_ADD_GROUP));
	$theme->display('page_admin_groups_add.tpl');
echo '</div>';

?>

</div>
