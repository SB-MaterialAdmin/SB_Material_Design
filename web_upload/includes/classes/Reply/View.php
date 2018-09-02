<?php
namespace Reply;

class View extends AbstractReply {
  protected $responseType = 'text/html';

  /**
   * Specific Twig params.
   */
  protected $viewFile = '';
  protected $params   = [];

  public function __construct($viewFile, array $params = []) {
    $this->setViewFile($viewFile);
    $this->setParams($params);
  }

  public function getViewFile() {
    return $this->viewFile;
  }

  public function setViewFile($file) {
    $this->viewFile = strval($file);
  }

  public function getParams() {
    return $this->params;
  }

  public function setParams(array $params = []) {
    $this->params = $params;
  }

  protected function getRenderBody() {
    if (!\TemplateManager::IsInitialized())
      throw new \LogicException('Can\'t render body: TemplateManager is not ready.');

    $twig = \TemplateManager::GetTwig();
    return $twig
      ->load($this->getViewFile())
      ->render($this->getParams());
  }
}