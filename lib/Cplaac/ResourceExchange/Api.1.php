<?php

namespace Cplaac\ResourceExchange;

class Api {

  private $url
  ,       $username
  ,       $password
  ,       $options
  ,       $files = array();

  public $response;

  public function __construct($url, $username, $password) {
    $this->url = $url;
    $this->username = $username;
    $this->password = $password;
  }

  public function get($route) {
    $this->makeRequest($this->url . $route);
    return $this;
  }

  public function post($route, Array $data) {    
    $options = array(
      CURLOPT_POST => true,
      CURLOPT_POSTFIELDS => $data
    );

    $this->makeRequest($this->url . $route, $options);
    return $this;
  }

  public function getResponse() {
    return $this->response;
  }

  public function getResponseText() {
    return $this->response->body;
  }

  public function getResponseStatus() {
    return $this->response->status;
  }

  protected function makeRequest($url, $options = array()) {
    $this->setOptions($options);

    $ch = curl_init($url);
    curl_setopt_array($ch, $this->options);

    $response = curl_exec($ch);
    $headers = curl_getinfo($ch);

    if ($response === false) throw new \Exception(curl_error($ch));

    curl_close($ch);

    $this->parseResponse($headers, $response);
  }

  protected function parseResponse($headers, $response) {
    $this->response = (object) array(
      'status' => (int) $headers['http_code'],
      'body' => json_decode($response)
    );
  }

  protected function setOptions($options = array()) {
    $defaults = array(
      CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
      CURLOPT_USERPWD => "{$this->username}:{$this->password}",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_USERAGENT => 'Cplaac;Resource-Exchange'
    );

    $this->options = array_replace($defaults, $options);
  }
}