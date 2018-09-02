<?php
use Core\Controller;

abstract class BaseController extends Controller {
  protected $use_db = 'sourcebans';

  protected function before() {
    if (\TemplateManager::IsInitialized()) 
      $this->InitTemplateVariables();
  }

  public function db() {
    return \DatabaseManager::GetConnection($this->use_db);
  }

  /**
   * Initializers.
   */
  private function InitTemplateVariables() {
    $twig = \TemplateManager::GetTwig();
    $twig->addGlobal('is_logged', (\UserManager::getMyID() != -1));
    $twig->addGlobal('settings',  $GLOBALS['config']);
  }
}