<?php

namespace Cplaac\Controllers;

use Silex\Application;
use Silex\ControllerProviderInterface;

class ExchangeControllerProvider implements ControllerProviderInterface {

  public function connect(Application $app) {
    $exchange = $app['controllers_factory'];

    $exchange->get('', function() use ($app) {
      return $app['twig']->render('resource-exchange/index.twig');
    });

    $exchange->get('/upload', function() use ($app) {
      return $app['twig']->render('resource-exchange/upload.twig');
    })->before($app['requireAuth']);

    $exchange->get('/latest', function() use ($app) {
      return $app['twig']->render('resource-exchange/files/latest.twig', array(
        'files' => $app['api']->get('/resource/latest')->getResponseText()
      ));
    })->before($app['requireAuth']);

    $exchange->get('/popular', function() use ($app) {
      return $app['twig']->render('resource-exchange/files/popular.twig', array(
        'files' => $app['api']->get('/resource/popular')->getResponseText()
      ));
    })->before($app['requireAuth']);

    $exchange->get('/browse/{page_number}', function($page_number) use ($app) {
      return $app['twig']->render('resource-exchange/files/browse.twig', array(
        'files' => $app['api']->get('/resource/browse/alpha/asc/'.$page_number)->getResponseText()
      ));
    })->before($app['requireAuth'])
      ->value('page_number', 1);

    $exchange->get('/leaderboard', function() use ($app) {
      return $app['twig']->render('resource-exchange/leaderboard.twig', array(
        'ranks' => $app['api']->get('/leaderboard')->getResponseText()
      ));
    });

    $exchange->get('/profile/rank', function() use ($app) {
      return $app['twig']->render('resource-exchange/profile/rank.twig');
    })->before($app['requireAuth']);

    // List of what the user has uploaded or downloaded
    $exchange->get('/profile/{type}', function($type) use ($app) {
      if (!in_array($type, array('uploads', 'downloads'))) {
        $app->abort(404);
      }

      // Gets the uploads list or the downloads list, depending on the request.
      $files = $app['user']->{'get'.ucfirst($type)}() ?: null;
      return $app['twig']->render('resource-exchange/profile/files.twig', array(
        'type' => ucfirst($type),
        'files' => $files
      ));
    })->before($app['requireAuth']);

    // Download a file (and award points if the user and owner aren't the same)
    $exchange->get('/download/{filename}', function($filename) use ($app) {
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
    })->before($app['requireAuth'])
      ->assert('id', '\d+')->assert('filename', '[a-zA-Z0-9_\-]+');

    $exchange->post('/upload', function(Request $request) use ($app) {
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
        return $app->redirect('/profile/uploads');
      } catch (\Exception $e) {
        // Error log the problem, show a failure flash message
        error_log($e->getMessage());
        $app['session']->setFlash('error', 'A problem was encountered when trying to upload this file');
        return $app->redirect('/upload');
      }
    })->before($app['requireAuth']);

    return $exchange;
  }
}
