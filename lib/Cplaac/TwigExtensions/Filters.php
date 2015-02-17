<?php

namespace Cplaac\TwigExtensions;

class Filters {

  public function boolToIcon($bool) {
    return (bool) $bool ? "<i class='icon-ok icon-white'></i>" : "<i class='icon-remove icon-white'></i>";
  }
}