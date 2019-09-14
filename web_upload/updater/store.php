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

return [
  352 =>  "SourceBans_MyArena.php",
  356 =>  "SourceComms_DBStructure.php",
  480 =>  "MATERIAL_Admin_Upgrade.php",
  500 =>  "ThemeCustom_Header.php",
  501 =>  "Voucher_System_1.php",
  502 =>  "Voucher_System_2.php",
  503 =>  "ThemeCustom_Background_1.php",
  504 =>  "ThemeCustom_Background_2.php",
  505 =>  "SteamAvatars.php",
  506 =>  "Dashboard_Comms.php",
  507 =>  "ShowAdminInfo_Banlist.php",
  508 =>  "ChangePersonalAdminInfo_Profile.php",
  509 =>  "Menu.php",
  510 =>  "SMTP.php",
  511 =>  "Menu_NewTab.php",
  512 =>  "GenerationPage_Footer.php",
  513 =>  "SteamAvatars_ExpireDrop.php",
  514 =>  "OldServerSide.php",
  515 =>  "Theme_CFG.php",
  516 =>  "Warnings.php",
  517 =>  "Warnings_fixes.php",
  518	=> 	"518.php",
  519 =>  "519.php",
  520 =>  '520.php',
  521 =>  '521.php',
  522 =>  '522.php',
  523 =>  '523.php',
  524 =>  '524.php', // drop summertime support
  525 =>  '525.php', // reset default TZ

  530 =>  '530.php', // InnoDB rules!

  // Обновление sb_admins
  // Это капитальное обновление, должно происходить в четыре этапа:
  550 =>  '550.php',  // Первый этап: создание новых, необходимых таблиц.
  551 =>  '551.php',  // Второй этап: перенос существующих данных из старых таблиц в новые.
  552 =>  '552.php',  // Третий этап: удаление устаревших колонок.
  553 =>  '553.php',  // Четвёртый этап: перестроение кеша прав и добавление CRON-записи.
];
