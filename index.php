<?php
require_once __DIR__.'/vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

$app = new Silex\Application;

// global configuration settings for the application, containing db connection settings etc.
$app['config'] = parse_ini_file(__DIR__.'/.config.ini', true);
$app['debug'] = $app['config']['app']['debug'];

// serve static files in debug mode
if ($app['debug'] == true) {
  $filename = __DIR__.preg_replace('#(\?.*)$#', '', $_SERVER['REQUEST_URI']);
  if (php_sapi_name() === 'cli-server' && is_file($filename)) {
    return false;
  }
}

// Fudge authentication with phpbb
define('IN_PHPBB', true);
$phpbb_root_path = $app['config']['forum']['path'];
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);
// $user and $auth come from that file
$user->session_begin();
$auth->acl($user->data);
$user->setup();

$app->register(new Silex\Provider\TwigServiceProvider(), array(
  'twig.path'       => __DIR__.'/views',
));

$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
  'db.options' => array(
    'driver'    => 'pdo_mysql',
    'host'      => $app['config']['database']['host'],
    'dbname'    => $app['config']['database']['name'],
    'user'      => $app['config']['database']['user'],
    'password'  => $app['config']['database']['pass']
  ),
));

$session_config = array();
if (isset($app['config']['app']['session_path'])) {
  $session_config['session.storage.save_path'] = $app['config']['app']['session_path'];
}

$app->register(new Silex\Provider\SessionServiceProvider(), $session_config);
$app['session']->start();

$app['twig']->addGlobal('Maths', new Cplaac\TwigExtensions\Maths());
$app['twig']->addGlobal('Filters', new Cplaac\TwigExtensions\Filters());

$app['last_page'] = function() use ($app) {
  $request = $app['request'];
  $referer = $request->server->get('HTTP_REFERER');
  $request_uri = $request->getRequestUri();
  $server = $request->server->get('SERVER_NAME');

  if ($referer) {
    $url = parse_url($referer);
    if ($url['host'] === $server && $url['path'] !== $request_uri) return $url['path'];
  }

  return '/';
};

$app['current_page'] = function() use ($app) {
  $request = $app['request'];
  return $request->getScheme().'://'.$request->getHost().$request->getRequestUri();
};

$app['api'] = $app->share(function() use ($app) {
  $config = $app['config']['api'];
  return new Cplaac\ResourceExchange\Api($config['uri'], $config['username'], $config['password']);
});

$app['auth'] = $app->share(function() use ($app, $user, $auth) {
  return new Cplaac\Core\Auth($app['config']['forum'], $user, $auth);
});

$app['user'] = function() use ($app) {
  if ($user = $app['auth']->getUserData()) {
    $user = new Cplaac\ResourceExchange\UserProfile($app['api'], $user);
  }
  return $user;
};

$app['requireAuth'] = $app->protect(function(Request $request) use ($app) {
  if (!$app['user']) {
    $app['session']->set('last_page', $app['current_page']);
    return new RedirectResponse('/login');
  }
});

$app->mount('/services/', new Cplaac\Controllers\ServicesControllerProvider);
$app->mount('/exchange/', new Cplaac\Controllers\ExchangeControllerProvider);

$app->get('/', function() use ($app) {
  return $app['twig']->render('index.twig');
});

$app->get('/signup', function() use ($app) {
  return $app->redirect('/forum/profile.php?mode=register');
});

$app->get('/login', function() use ($app) {
  return $app['twig']->render('user/login.twig');
});

$app->post('/login', function(Request $request) use ($app) {
  $username = $request->get('username');
  $password = $request->get('password');
  $remember = $request->get('autologin') ? 1 : 0;
  $redirect = $request->get('redirect');

  $app['auth']->login($username, $password, $remember);

  return $app['twig']->render('user/login_redirect.twig', array(
    'timed_redirect' => $redirect
  ));
});

$app->get('/logout', function() use ($app) {
  $app['auth']->logout();

  return $app['twig']->render('user/logout_redirect.twig', array(
    'timed_redirect' => $app['last_page']
  ));
});

// Basic about page
$app->get('/about', function() use ($app) {
  return $app['twig']->render('about/index.twig');
});

$app->run();
