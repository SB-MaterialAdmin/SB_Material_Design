<?php
class SBCronJob {
  private static function db() {
    return \DatabaseManager::GetConnection();
  }

  public static function PruneBans(&$data, &$finished) {
    $aid = \UserManager::getMyID();
    if ($aid == -1)
      $aid = 0;

    $DB = self::db();

    $DB->Query('UPDATE `{{prefix}}bans` SET `RemovedBy` = 0, `RemoveType` = "E", `RemovedOn` = UNIX_TIMESTAMP() WHERE `length` != 0 AND `ends` < UNIX_TIMESTAMP() AND `RemoveType` IS NULL');

    $DB->Prepare('UPDATE `{{prefix}}protests` SET `archiv` = 3, `archivedby` = :adminid WHERE `archiv` = 0 AND `bid` IN ((SELECT `bid` FROM `{{prefix}}bans` WHERE `RemoveType` = "E"))');
    $DB->BindData('adminid', $aid);
    $DB->Finish()->EndData();

    $DB->Prepare('UPDATE `{{prefix}}submissions` SET `archiv` = 3, `archivedby` = :adminid WHERE `archiv` = 0 AND (SteamId IN((SELECT `authid` FROM `{{prefix}}bans` WHERE `type` = 0 AND `RemoveType` IS NULL)) OR sip IN((SELECT `ip` FROM `{{prefix}}bans` WHERE `type` = 1 AND `RemoveType` IS NULL)))');
    $DB->BindData('adminid', $aid);
    $DB->Finish()->EndData();

    $DB->Query('DELETE FROM `{{prefix}}bans` WHERE `authid` NOT REGEXP "^STEAM_[0-9]:[0-9]:[0-9]"');

    $finished = true;
  }

  public static function PruneComms(&$data, &$finished) {
    self::db()->Query('UPDATE `{{prefix}}comms` SET `RemovedBy` = 0, `RemoveType` = "E", `RemovedOn` = UNIX_TIMESTAMP() WHERE `length` != 0 and `ends` < UNIX_TIMESTAMP() and `RemoveType` IS NULL');

    $finished = true;
  }

  public static function ServerCache(&$data, &$finished) {
    $finished = true;
  }

  public static function UpdateChecker(&$data, &$finished) {
    $DB = self::db();
    $DB->Prepare('UPDATE `{{prefix}}settings` SET `value` = :data WHERE `setting` = "sb.updater"');
    $DB->BindData('data', @file_get_contents("https://raw.githubusercontent.com/SB-MaterialAdmin/Web/" . MA_BRANCH . "/updates.json"));
    $DB->Finish();

    $finished = true;
  }

  public static function CollectStats(&$data, &$finished) {
    // We collect stats like PHP version, MySQL version.
    // Our server receives:
    // -> PHP version
    // -> MySQL version
    // -> SB address
    // -> SB version (database and generic)
    // -> Bans, comms, servers count
    $DB  = self::db();
    $ver = $DB->GetAttribute(\PDO::ATTR_SERVER_VERSION);
    $dbver  = $DB->Query('SELECT `value` FROM `{{prefix}}settings` WHERE `setting` = "config.version";')->Single();
    $counts = $DB->Query('SELECT (SELECT COUNT(*) FROM `{{prefix}}bans`) AS `bans`, (SELECT COUNT(*) FROM `{{prefix}}comms`) AS `comms`, (SELECT COUNT(*) FROM `{{prefix}}servers`) AS `servers`;')->Single();

    $body = [
      'php'     => phpversion(),
      'mysql'   => $ver,
      'counts'  => serialize($counts),
      'address' => SB_WP_URL,
      'version' => serialize([
        'files' => SB_VERSION,
        'db'    => $dbver['value']
      ])
    ];

    // Make HTTP query.
    \HTTP::request('https://kruzefag.ru', 'POST')->setData($body)
      ->run('sourcebans/stats.php');
    $finished = true;
  }

  public static function UpdateAvatarCache(&$data, &$finished) {
    // TODO: migrate avatar cache updater.
    $finished = true;
  }

  public static function RebuildRightsCache(&$data, &$finished) {
    $db = self::db();

    // Well... This is hard task.
    // And dangerous.

    // First step: start transaction.
    $db->BeginTxn();

    // Second step: drop all data.
    $db->Query("DELETE FROM `{{prefix}}permissions_cache`;");

    // Third step: load all data.
    $AdminsBase   = $db->Query("SELECT `aid`, `user`, `web_flags` FROM `{{prefix}}admins` WHERE `deleted` = 0")
      ->All();

    // Four step: prepare statements.
    $db->Prepare("
      INSERT INTO
        `{{prefix}}permissions_cache`
      (`user`, `auth_type`, `auth_identifier`,
       `sid`, `gid`, `password`,
       `srv_flags`, `web_flags`,
       `immunity`
      ) VALUES (
        :user, :auth_type, :auth_identifier,
        :sid, :gid, :password,
        :srv_flags, :web_flags,
        :immunity
      );
    ");
    $InsertStatement = $db->GetStatement();

    $db->Prepare("
    SELECT
      `type`, `identifier`
    FROM
      `{{prefix}}admins_auths`
    WHERE
      `aid` = :aid;
    ");
    $SelectAuthStatement = $db->GetStatement();

    $db->Prepare("
      SELECT
        `servers`, `gid`, `password`, `expires`, `immunity`, `web_flags`, `server_flags`
      FROM
        `{{prefix}}admins_rights`
      WHERE
        `aid` = :aid
    ");
    $SelectPermissionsStatement = $db->GetStatement();

    // Five step: build cache.
    $Cache = [];
    foreach ($AdminsBase as $AdminBase) {
      // Init new variables: aid and user.
      $aid = $AdminBase['aid'];
      $user = $AdminBase['user'];

      // Grab all existing auths.
      $SelectAuthStatement->BindData('aid', $aid);
      $SelectAuthStatement->Execute();

      // Check existing any user auth.
      if ($SelectAuthStatement->RowCount() < 0) {
        continue; // skip, if no one auth added.
      }

      // Get all auths and finish this query.
      $Auths = $SelectAuthStatement->All();
      $SelectAuthStatement->EndData();

      // Grab all existing right entries.
      $SelectPermissionsStatement->BindData('aid', $aid);
      $SelectPermissionsStatement->Execute();

      // Check existing any user permission.
      if ($SelectPermissionsStatement->RowCount() < 0) {
        continue; // skip, if no one permission rule added.
      }

      // Get all permissions and finish this query.
      $Permissions = $SelectPermissionsStatement->All();
      $SelectPermissionsStatement->EndData();

      // Now, work with existing data.
      foreach ($Permissions as $PermissionEntry) {
        $Servers = json_decode($PermissionEntry['servers'], true);  // parse servers.

        foreach ($Servers as $Server) {
          // Build permission ID.
          $PermissionID   = "{$aid}_{$Server}";
          $CacheEntry = [
            'user'      => $user,
            'sid'       => $Server,
            'password'  => $PermissionEntry['password'],
            'srv_flags' => $PermissionEntry['server_flags'],
            'web_flags' => (
              $PermissionEntry['web_flags'] | $AdminBase['web_flags']
            ),
            'immunity'  => $PermissionEntry['immunity'],
          ];

          // Work with all auths.
          foreach ($Auths as $Auth) {
            $Type = $Auth['type'];
            $Id   = $Auth['identifier'];
            $CacheEntry['auth_type']        = $Type;
            $CacheEntry['auth_identifier']  = $Id;

            // Create unique permission ID for this auth.
            $AuthID  = md5("{$Type}_{$Id}");
            $CacheID = "{$PermissionID}_{$AuthID}";

            // Add our entry to cache.
            $Cache[$CacheID] = $CacheEntry;
          }
        }
      }
    }

    // Six step: push all data to database.
    foreach ($Cache as $CacheEntry) {
      $InsertStatement->BindData($CacheEntry);
      $InsertStatement->Execute();
      $InsertStatement->EndData();
    }

    // Seven step: finish transaction.
    $db->EndTxn();
    $finished = true;
  }
}