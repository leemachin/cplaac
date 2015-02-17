<?php

namespace Cplaac\TwigExtensions;

class Maths {

  public function splitDecimal($num) {
    $int = floor($num);
    $dec = $num - $int;

    return array($int, $dec);
  }

  # Not the most elegant at all, but it's a copy paste job
  public function ordinal($num, $sup = true) {
    $ends = array('th','st','nd','rd','th','th','th','th','th','th');
    if (($num %100) >= 11 && ($num%100) <= 13)
       return $num. ($sup === true ? '<sup>th</sup>' : 'th');
    else
       return $num. ($sup === true ? "<sup>{$ends[$num % 10]}</sup>" : $ends[$num % 10]);

  }
}