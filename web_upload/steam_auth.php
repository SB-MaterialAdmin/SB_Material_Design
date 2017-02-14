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

require_once('init.php');
require_once(sprintf('%s/LightOpenID.php', INCLUDES_PATH));
require_once(sprintf('%s/SteamOpenID.php', INCLUDES_PATH));

if (defined('DEVELOPER_MODE')) {
    ini_set('error_reporting', E_ALL);
    ini_set('display_errors', true);
    ini_set('display_startup_errors', true);
}

function RedirectToSite($url = SB_WP_URL, $text = "", $NotJS = false) {
    if (!headers_sent() && !$NotJS) {
        Header(sprintf('Location: %s', $url));
    } else {
        echo("<script>");
        if ($text != "")
            printf('alert("%s");', addslashes($text));
        printf('document.location.href="%s";</script>', $url);
    }
    
    exit();
}

function CommunityIDToSteamID($communityid) {
    $authserver = bcsub( $communityid, '76561197960265728' ) & 1;
    $authid = (bcsub( $communityid, '76561197960265728' ) - $authservers ) / 2;
    return sprintf("STEAM_0:%d:%d", $authserver, $authid);
}

$Site = SB_WP_URL;
$Site = str_replace(array('https', 'http', '://'), '', $Site);

$AuthResult = SteamAuthorize($Site);
if (!$AuthResult)
    RedirectToSite(); // Something error. User cancelled authentication?
else if (strpos($AuthResult, 'steamcommunity') !== false)
    RedirectToSite($AuthResult); // Auth started. Redirect to Steam.
else {
    // Auth success. Steam returned SteamID64
    $SteamID = CommunityIDToSteamID($AuthResult);
    
    $AdminsNum = 0;
    $ExpiredAdmin = false;
    $aid = 0;
    $password = '';
    
    $result = $GLOBALS['db']->query(sprintf("SELECT aid,password,expired FROM %s_admins WHERE authid LIKE '%%%s'", DB_PREFIX, str_replace('STEAM_0:', '', $SteamID)));
	while($res = $result->fetch(PDO::FETCH_LAZY)) {
        $exp = $res['expired'];
        if (($exp > 0 && $exp > time()) || $exp == '0' || $exp == '') {
            $AdminsNum++;
            $aid      = $res['aid'];
            $password = $res['password'];
        } else
            $ExpiredAdmin = true;
    }
    
    if ($AdminsNum > 1)
        RedirectToSite(SB_WP_URL, "Найдено более одного администратора. Свяжитесь с главным администратором.", true);
    else if ($AdminsNum == 0)
        RedirectToSite(SB_WP_URL, 'По предоставленным данным, не найдено ни одного администратора.', true);
    else {
        setcookie("aid", $aid, time()+LOGIN_COOKIE_LIFETIME);
        setcookie("password", $password, time()+LOGIN_COOKIE_LIFETIME);
        RedirectToSite(SB_WP_URL, 'Администратор найден, переадресация...');
    }
}
?>
