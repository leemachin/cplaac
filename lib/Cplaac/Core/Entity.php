<?php

namespace Cplaac\Core;
use ArrayObject;

/**
 * Entity class that just stops us from copying the database constructor 
 * code everywhere, and at some point may offer more functionality.
 */
class Entity extends ArrayObject {

  protected $db;

  public function __construct($data) {
    parent::__construct($data, ArrayObject::ARRAY_AS_PROPS);
  }

  public function setDb($db) {
    $this->db = $db;
  }
}