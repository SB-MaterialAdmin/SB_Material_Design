<?php
namespace Reply;

abstract class AbstractReply {
  protected $statusCode = 200;
  protected $responseType = null;

  public function getResponseCode() {
    return $this->statusCode;
  }

  public function setResponseCode($code) {
    $code = intval($code);
    if (!$code) {
      throw new \InvalidArgumentException('Invalid response code');
    }

    $this->statusCode = $code;
  }

  public function getResponseType() {
    return $this->responseType;
  }

  public function setResponseType($type) {
    $this->responseType = strval($type);
  }

  public function getResponse() {
    http_response_code($this->statusCode);
    header("Content-Type: {$this->responseType}; charset=UTF8");
    echo($this->getRenderBody());
  }

  abstract protected function getRenderBody();
}