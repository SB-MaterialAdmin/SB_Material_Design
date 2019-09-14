<?php
$DB = \DatabaseManager::GetConnection();

// Первый шаг: загрузка базовой инфы об админах.
$Admins = $DB->Query("
  SELECT
    `aid`, `authid`, `gid`, `srv_password`, `expired`, `immunity`, `extraflags`, `srv_flags`
  FROM
    `{{prefix}}admins`;
")->All();

// Второй шаг: подготовка запросов.
$DB->Prepare("
  INSERT INTO
    `{{prefix}}admins_auths`
  (`aid`, `type`, `identifier`)
  VALUES
  (
    :aid, :type, :identifier
  );
");
$InsertAuthStatement = $DB->GetStatement();

$DB->Prepare("
  INSERT INTO
    `{{prefix}}admins_rights`
  (`aid`, `servers`, `gid`,
   `password`, `expires`,
   `immunity`, `web_flags`,
   `server_flags`
  )
  VALUES
  (:aid, :servers, :gid,
   :password, :expires,
   :immunity, :web_flags,
   :server_flags
  );
");
$InsertRightsStatement = $DB->GetStatement();

$DB->Prepare("
  SELECT
    `server_id`, `srv_group_id`
  FROM
    `{{prefix}}admins_servers_groups`
  WHERE
    `admin_id` = :aid;
");
$SelectServerStatement = $DB->GetStatement();

$DB->Prepare("
  SELECT
    `server_id`
  FROM
    `{{prefix}}servers_groups`
  WHERE
    `group_id` = :group;
");
$SelectServerByGIDStatement = $DB->GetStatement();

$DB->Prepare("
  SELECT
    `id`
  FROM
    `{{prefix}}srvgroups`
  WHERE
    `name` = :name;
");
$SelectGIDByGroupNameStatement = $DB->GetStatement();

// Третий шаг: пройдёмся по админам.
foreach ($Admins as $Admin) {
  $aid = $Admin['aid'];

  // Добавим сразу запись в авторизации со Стимом.
  $InsertAuthStatement->BindMultipleData([
    'aid'         => $aid,
    'type'        => 'steam',
    'identifier'  => $Admin['authid'],
  ]);
  $InsertAuthStatement->Execute();
  $InsertAuthStatement->EndData();

  // Теперь сервера. Получим перечень серверов, где у админа есть хоть какие-то права.
  $Servers = [];
  $SelectServerStatement->BindData('aid', $aid);
  $SelectServerStatement->Execute();
  $Dummy = $SelectServerStatement->All();
  $SelectServerStatement->EndData();

  foreach ($Dummy as $Item) {
    $SID = $Item['server_id'];
    if ($SID != -1 && !in_array($SID, $Servers))
      $Servers[] = $SID;

    $GID = $Item['srv_group_id'];
    if ($GID != -1) {
      // Запросим "дочерние" сервера.
      $SelectServerByGIDStatement->BindData('group', $GID);
      $SelectServerByGIDStatement->Execute();
      $DummyGroup = $SelectServerByGIDStatement->All();
      $SelectServerByGIDStatement->EndData();

      foreach ($DummyGroup as $ItemGroup) {
        $SID = $ItemGroup['server_id'];

        if ($SID != -1 && !in_array($SID, $Servers))
          $Servers[] = $SID;
      }
    }
  }

  // Получаем GroupID.
  $SelectGIDByGroupNameStatement->BindData('name', $Admin['srv_group']);
  $SelectGIDByGroupNameStatement->Execute();
  $gid = $SelectGIDByGroupNameStatement->Single();
  $SelectGIDByGroupNameStatement->EndData();
  $gid = $gid['id'];

  // Биндим данные и заносим в таблицу.
  $InsertRightsStatement->BindMultipleData([
    'aid'           => $aid, 
    'servers'       => json_encode($Servers),
    'gid'           => $gid,
    'password'      => $Admin['srv_password'],
    'expires'       => $Admin['expired'],
    'immunity'      => $Admin['immunity'],
    'web_flags'     => 0,
    'server_flags'  => ParseAdminFlags($Admin['srv_flags']),
  ]);
  $InsertRightsStatement->Execute();
  $InsertRightsStatement->EndData();
}