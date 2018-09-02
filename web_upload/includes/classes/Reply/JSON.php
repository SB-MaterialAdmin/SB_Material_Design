<?php
namespace Reply;

class JSON extends AbstractReply {
  protected $responseType = 'application/json';

  /**
   * Specific JSON render params.
   */
  protected $jsonBody     = [];
  protected $jsonOptions  = 0;
  protected $jsonDepth    = 512;

  public function __construct(array $body, $options = 0, $depth = 512) {
    $this->setBody($body);
    $this->setOptions($options);
    $this->setDepth($depth);
  }

  public function getBody() {
    return $this->jsonBody;
  }

  public function setBody(array $body) {
    $this->jsonBody = $body;
  }

  public function getOptions() {
    return $this->jsonOptions;
  }

  public function setOptions($options = 0) {
    $this->jsonOptions = intval($options);
  }

  public function getDepth() {
    return $this->jsonDepth;
  }

  public function setDepth($depth = 512) {
    $this->jsonDepth = intval($depth);
  }

  protected function getRenderBody() {
    return json_encode(
      $this->getBody(),
      $this->getOptions(),
      $this->getDepth()
    );
  }
}