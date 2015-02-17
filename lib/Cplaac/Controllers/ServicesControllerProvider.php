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
      return $app['twig']->render('services/index.twig');
    });

    $services->get('/new', function() use ($app) {
      $form = $this->createForm($app);

      return $app['twig']->render('services/new.twig', [
        'form' => $form->createView()
      ]);

    })->before($app['requireAuth']);

    $services->post('/create', function(Request $request) use ($app) {
      $form = $this->createForm($app);
      $form->handleRequest($request);

      if ($form->isValid()) {
      } else {
      }
    })->before($app['requireAuth']);

    return $services;
  }

  private function createForm($app, $data = null) {
    $serviceForm = $app['form.factory']->createNamedBuilder('service', 'form', $data);
    $infoForm = $app['form.factory']->createNamedBuilder('additional_info', 'form', $data, [
      'label' => false,
      'required' => false
    ]);

    $serviceForm
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
      'constraints' => new Assert\Choice(range(0, count(Service::CLIENT_GROUPS)))
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
      'constraints' => new Assert\Choice(range(0, count(Service::SERVICES_OFFERED)))
    ]);

    $infoForm->add('services_offered_other', 'textarea');

    return $serviceForm->getForm();
  }
}
