<?php

namespace Cplaac\Core;

/**
 * Wrapper class for phpBB3's authentication and user things.
 * It requires those environments to be present, otherwise it will die.
 * Those are set in index.php at the minute.
 */
class Auth {

  private $config
  ,       $user;

  public  $user_data = false;

  /**
   * Inject a database instance into the class.
   */
  public function __construct($config, $user, $auth) {
    $this->config = $config;
    $this->user = $user;  
    $this->auth = $auth;
  }

  public function getUserData() {
    if ($this->userLoggedIn()) {
      $this->user_data = (object) array(
        'user_id' => $this->user->data['user_id'],
        'username' => $this->user->data['username'],
        'email' => $this->user->data['user_email'],
        'joined' => $this->user->data['user_regdate'],
      );
    }

    return $this->user_data;
  }

  public function login($username, $password, $remember) {
    return $this->auth->login($username, $password, $remember);
  }

  public function logout() {
    if ($this->userLoggedIn()) {
      $this->user->session_kill();
    }
  }
 
  public function userLoggedIn() {
    return $this->user_data !== false || $this->user->data['user_id'] != 1;
  }
}