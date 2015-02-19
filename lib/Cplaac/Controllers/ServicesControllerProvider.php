<?php

namespace Cplaac\Controllers;

use Cplaac\Services\Service;

use Silex\Application;
use Silex\ControllerProviderInterface;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\Request;

class ServicesControllerProvider implements ControllerProviderInterface {
  
  public function connect(Application $app) {
    $services = $app['controllers_factory'];

    $services->get('/', function() use ($app) {
      $services = Service::findAll($app['db']);
      return $app['twig']->render('services/index.twig', [
        'services' => $services
      ]);
    });

    $services->get('/new', function() use ($app) {
      $form = $this->createForm($app);

      return $app['twig']->render('services/new.twig', [
        'form' => $form->createView()
      ]);

    })->before($app['requireAuth']);

    $services->get('/{id}', function(Request $request, $id) use ($app) {
      $service = Service::findById($app['db'], $id);
      if ($service) {
        return $app['twig']->render('services/show.twig', [
          'service' => $service
        ]);
      } else {
        return $app->abort(404, "Service not found");
      }
    })->assert('id', '\d+');

    $services->get('/{id}/edit', function(Request $request, $id) use ($app) {
      $service = Service::findById($app['db'], $id);
      $form = $this->createForm($app, $service);

      return $app['twig']->render('services/edit.twig', [
        'service' => $service,
        'form' => $form->createView()
      ]);
    })->assert('id', '\d+');

    $services->post('/{id}', function(Request $request, $id) use ($app) {
      $service = Service::findById($app['db'], $id);

      if (!$service) {
        return $app->abort(404, "Service not found");
      }

      $form = $this->createForm($app);
      $form->handleRequest($request);

      if ($form->isValid()) {
        $service->update($form->getData()); 

        if ($service->save()) {
          return $app->redirect('/services');
        }
      } else {
        return $app['twig']->render('services/new.twig', [
          'form' => $form->createView()
        ]);
      }
    })->assert('id', '\d+');

    $services->post('/', function(Request $request) use ($app) {
      $form = $this->createForm($app);
      $form->handleRequest($request);

      if ($form->isValid()) {
        $service = new Service($app['db'], $form->getData());
        $service->user_id = $app['user']->user_id;

        if ($service->save()) {
          return $app->redirect('/services');
        }
      } else {
        return $app['twig']->render('services/new.twig', [
          'form' => $form->createView()
        ]);
      }
    })->before($app['requireAuth']);

    return $services;
  }

  private function createForm($app, $data = null) {
    $serviceForm = $app['form.factory']->createNamedBuilder('service', 'form', $data);
    $infoForm = $app['form.factory']->createNamedBuilder('additional_info', 'form', json_decode($data->additional_info), [
      'label' => false,
      'required' => false
    ]);

    $serviceForm
      ->add('id', 'hidden')
      ->add('service_name', 'text', ['constraints' => new Assert\NotBlank])
      ->add('address', 'textarea', ['constraints' => new Assert\NotBlank])
      ->add('contact_name', 'text', ['constraints' => new Assert\NotBlank])
      ->add('contact_phone', 'text', ['constraints' => new Assert\NotBlank])
      ->add('contact_email', 'email', ['constraints' => new Assert\Email])
      ->add($infoForm);
     
     
    $infoForm ->add('staffing', 'textarea')
      ->add('type', 'textarea')
      ->add('camh', 'textarea')
      ->add('referral_pathway', 'textarea')
      ->add('referral_rules', 'textarea');

    $infoForm->add('client_groups', 'choice', [
      'choices' => Service::CLIENT_GROUPS,
      'expanded' => true,
      'multiple' => true,
      'constraints' => new Assert\Choice([
        'choices' => range(0, count(Service::CLIENT_GROUPS)),
        'multiple' => true
      ])
    ]);

    $infoForm->add('client_groups_other', 'text');

    $infoForm
      ->add('screening', 'textarea')
      ->add('psychometrics', 'textarea')
      ->add('court_proceedings', 'textarea')
      ->add('therapy_models', 'textarea');

    $infoForm->add('services_offered', 'choice', [
      'choices' => Service::SERVICES_OFFERED,
      'expanded' => true,
      'multiple' => true,
      'constraints' => new Assert\Choice([
        'choices' => range(0, count(Service::SERVICES_OFFERED)),
        'multiple' => true
      ])
    ]);

    $infoForm->add('services_offered_other', 'textarea');

    return $serviceForm->getForm();
  }
}
