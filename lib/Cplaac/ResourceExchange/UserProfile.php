<?php

namespace Cplaac\ResourceExchange;

class UserProfile extends \ArrayObject {

  public function __construct($api, $user) {
    $this->api = $api;
    parent::__construct($user, \ArrayObject::ARRAY_AS_PROPS);
  }

  public function getPoints() {
    if (!$this->offsetExists('points')) {
      $points = $this->api->get("/user/{$this->user_id}/points")->getResponseText() ?: 0;
      $this->offsetSet('points', $points);
    }
    return $this->offsetGet('points');
  }

  public function getRank() {
    if (!$this->offsetExists('rank')) {
      $rank = $this->api->get("/user/{$this->user_id}/rank")->getResponseText();
      $this->offsetSet('rank', $rank);
    }
    return $this->offsetGet('rank');
  }

  public function getUploads() {
    if (!$this->offsetExists('uploads')) {
      $uploads = $this->api->get("/user/{$this->user_id}/uploads")->getResponseText();
      $this->offsetSet('uploads', $uploads);
    }
    return $this->offsetGet('uploads');
  }

  public function getDownloads() {
    if (!$this->offsetExists('downloads')) {
      $downloads = $this->api->get("/user/{$this->user_id}/downloads")->getResponseText();
      $this->offsetSet('downloads', $downloads);
    }
    return $this->offsetGet('downloads');
  }
}