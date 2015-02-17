<?php

namespace Cplaac\Core;

/**
 * Model class that just stops us from copying the database constructor code everywhere
 */
class Model {

  public function __construct($db) {
    $this->db = $db;
  }
}