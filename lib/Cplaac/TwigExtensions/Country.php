<?php

namespace Cplaac\TwigExtensions;

class Country {

  public static function getCountryList() {
    include __DIR__.'/countries-array.php';
    return $countries;
  }
}