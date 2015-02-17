<?php

namespace Cplaac\Controllers;

use Silex\Application;
use Silex\ControllerProviderInterface;

class ServicesControllerProvider implements ControllerProviderInterface {
  
  public function connect(Application $app) {
    $services = $app['controllers_factory'];

    $services->get('/', function() use ($app) {
      return $app['twig']->render('services/index.twig');
    });

    $services->get('/new', function() use ($app) {
      return $app['twig']->render('services/new.twig');
    })->before($app['requireAuth']);

    $services->post('/create', function() use ($app) {
  
    })->before($app['requireAuth']);

    return $services;
  }
}
