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

$app['autoloader']->registerNamespace('Cplaac', __DIR__.'/lib');

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

$authenticateUser = function(Request $request) use ($app) {
  if (!$app['user']) {
    $app['session']->set('last_page', $app['current_page']);
    return new RedirectResponse('/login');
  }
};

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

// Resource Exchange index page
$app->get('/exchange', function() use ($app) {
  return $app['twig']->render('resource-exchange/index.twig');
});

// Resource Exchange upload form
$app->get('/exchange/upload', function() use ($app) {
  return $app['twig']->render('resource-exchange/upload.twig');
})->middleware($authenticateUser);

// Latest uploads
$app->get('/exchange/latest', function() use ($app) {
  return $app['twig']->render('resource-exchange/files/latest.twig', array(
    'files' => $app['api']->get('/resource/latest')->getResponseText()
  ));
})->middleware($authenticateUser);

// Most popular uploads
$app->get('/exchange/popular', function() use ($app) {
  return $app['twig']->render('resource-exchange/files/popular.twig', array(
    'files' => $app['api']->get('/resource/popular')->getResponseText()
  ));
})->middleware($authenticateUser);

// Browse all uploads
$app->get('/exchange/browse/{page_number}', function($page_number) use ($app) {
  return $app['twig']->render('resource-exchange/files/browse.twig', array(
    'files' => $app['api']->get('/resource/browse/alpha/asc/'.$page_number)->getResponseText()
  ));
})->middleware($authenticateUser)
  ->value('page_number', 1);

$app->get('/exchange/leaderboard', function() use ($app) {
  return $app['twig']->render('resource-exchange/leaderboard.twig', array(
    'ranks' => $app['api']->get('/leaderboard')->getResponseText()
  ));
});

// User's resource exchange rank
$app->get('/exchange/profile/rank', function() use ($app) {
  return $app['twig']->render('resource-exchange/profile/rank.twig');
})->middleware($authenticateUser);

// List of what the user has uploaded or downloaded
$app->get('/exchange/profile/{type}', function($type) use ($app) {
  if (!in_array($type, array('uploads', 'downloads'))) {
    $app->abort(404);
  }

  // Gets the uploads list or the downloads list, depending on the request.
  $files = $app['user']->{'get'.ucfirst($type)}() ?: null;
  return $app['twig']->render('resource-exchange/profile/files.twig', array(
    'type' => ucfirst($type),
    'files' => $files
  ));
})->middleware($authenticateUser);

// Download a file (and award points if the user and owner aren't the same)
$app->get('/exchange/download/{filename}', function($filename) use ($app) {
  // first, check that the file actually exists
  $resource = $app['api']->get("/resource/{$filename}")->getResponseText();
  if (!$resource) $app->abort(404, 'File does not exist');

  $file = $app['config']['app']['upload_dir'].'/'.$resource->user_id.'/'.$resource->real_filename;
  if (!file_exists($file)) $app->abort(404, 'File does not exist');

  // ensure the user is registered (not got a good way to do this really)
  $app['api']->post("/register", $app['user']->getArrayCopy());

  // now reward the points, since we're good to do so
  $app['api']->post("/resource/{$filename}/download", array(
    'user_id' => $app['user']->user_id
  ));

  // create a streaming response for the download (bit of a weird way to do it)
  $stream = function() use ($file) {
    readfile($file);
  };

  // get the mime type of the file, so we can set the correct content type
  $finfo = finfo_open(FILEINFO_MIME_TYPE);
  $content_type = finfo_file($finfo, $file);

  // return the stream
  return $app->stream($stream, 200, array('Content-Type' => $content_type));
})->middleware($authenticateUser)
  ->assert('id', '\d+')->assert('filename', '[a-zA-Z0-9_\-]+');

$app->post('/exchange/upload', function(Request $request) use ($app) {

  $resource = $request->files->get('resource');
  $user_id = $app['user']->user_id;

  $dir = $app['config']['app']['upload_dir'].'/'.$user_id;

  if (!is_dir($dir)) {
    mkdir($dir);
    chmod($dir, 0777);
  }

  $resource->move($dir, $resource->getClientOriginalName());

  $data = array(
    'user_id' => $user_id,
    'filename' => $resource->getClientOriginalName(),
    'description' => $request->get('description'),
    'tags' => $request->get('tags')
  );

  try {
    // First we need to register the user; in case they don't have an account
    $app['api']->post("/register", $app['user']->getArrayCopy());
    // Then we can record the upload with the API
    $result = $app['api']->post("/resource/upload", $data)->getResponse();

    // It worked, so set a flash message indicating as much, then redirect to the user's files
    $app['session']->setFlash('success', 'Your file was uploaded successfully');
    return $app->redirect('/exchange/profile/uploads');
  } catch (\Exception $e) {
    // Error log the problem, show a failure flash message
    error_log($e->getMessage());
    $app['session']->setFlash('error', 'A problem was encountered when trying to upload this file');
    return $app->redirect('/exchange/upload');
  }
})->middleware($authenticateUser);

$app->run();
