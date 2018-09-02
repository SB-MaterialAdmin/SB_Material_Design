<?php
class Database {
  private $TablePrefix;
  private $Statement = NULL;
  private $PDO = NULL;

  public function __construct($config) {
    $this->CheckValid($config);
    $this->Connect($config);
  }

  public function Query($query) {
    $query = $this->ReplacePrefix($query);
    return new \DatabaseResult($this->PDO->query($query));
  }

  public function Prepare($query) {
    $query = $this->ReplacePrefix($query);
    $this->Statement = new \DatabaseResult($this->PDO->prepare($query));
  }

  public function BindData($name, $value, $type = NULL) {
    if ($this->Statement === NULL)
      throw new \LogicException('No one query has been prepared');

    $this->Statement->BindData($name, $value, $type);
  }

  public function BindMultipleData($data) {
    if ($this->Statement === NULL)
      throw new \LogicException('No one query has been prepared');

    $this->Statement->BindMultipleData($data);
  }

  public function Finish($Clean = true, $data = null) {
    if ($this->Statement === NULL)
      throw new \LogicException('No one query has been prepared');

    try {
      $Statement = $this->Statement;
      $Statement->Execute($data);

      if ($Clean)
        $this->Statement = NULL;

      return $Statement;
    } catch (\Exception $e) {
      \ExceptionHandler::handle($e);
    }
  }

  public function BeginTxn() {
    return $this->PDO->beginTransaction();
  }

  public function EndTxn($commit = true) {
    if ($commit)
      return $this->PDO->commit();
    else
      return $this->PDO->rollBack();
  }

  public function LastInsertID() {
    return $this->PDO->lastInsertId();
  }

  public function ErrorCode() {
    return $this->PDO->errorCode();
  }

  public function ErrorInfo() {
    return $this->PDO->errorInfo();
  }

  public function GetAttribute($attr) {
    return $this->PDO->getAttribute($attr);
  }

  public function GetStatement($clear = true) {
    $statement = $this->Statement;
    if ($clear)
      $this->Statement = null;
    return $statement;
  }

  /**
   * internals
   */
  private function CheckValid($configuration) {
    if (!isset($configuration['dsn']))
      throw new \LogicException('Invalid configuration passed.');

    $ValidKeys = ['dsn', 'user', 'pass', 'options', 'prefix'];
    $Required  = [true,  false,  false,  false,     true];
    foreach ($configuration as $Key => $Value) {
      if (!in_array($Key, $ValidKeys, true))
        throw new \LogicException("Unknown key $Key in configuration.");
    }

    foreach ($ValidKeys as $KeyID => $KeyName) {
      if (!$Required[$KeyID])
        continue;

      if (!isset($configuration[$KeyName]))
        throw new \LogicException("Required key $Key is not set.");
    }
  }

  private function Connect($config) {
    if ($this->PDO !== NULL)
      return;

    $dsn      = $config['dsn'];
    $user     = $config['user'];
    $pass     = $config['pass'];
    $options  = $config['options'];

    $this->PDO          = new \PDO($dsn, $user, $pass, $options);
    $this->TablePrefix  = $config['prefix'];
  }

  private function ReplacePrefix($query) {
    return str_replace('{{prefix}}', $this->TablePrefix, $query);
  }
}