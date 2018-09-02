<?php
class Router {
  private static $Router = NULL;

  public static function Initialize() {
    if (self::$Router !== NULL)
      throw new \LogicException('Router already initialized.');

    self::$Router = new \Core\Router();
  }

  public static function Add($url, $parameters = []) {
    self::$Router->add($url, $parameters);
  }

  public static function Create($pattern, $parameters = []) {
    static $preparedQuery = NULL;
    if ($preparedQuery === NULL) {
      $DB = \DatabaseManager::GetConnection();
      $DB->Prepare('INSERT INTO `{{prefix}}routes` (`url`, `parameters`) VALUES (:pattern, :parameters);');
      $preparedQuery = $DB->GetStatement();
    }

    $preparedQuery->BindMultipleData([
      'pattern'     => $pattern,
      'parameters'  => serialize($parameters)
    ]);
    $preparedQuery->Execute();
    return \DatabaseManager::GetConnection()->LastInsertID();
  }

  public static function Run() {
    self::$Router->dispatch($_SERVER['QUERY_STRING']);
  }
}