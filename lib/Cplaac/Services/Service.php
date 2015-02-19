<?php

namespace Cplaac\Services;

class Service {

  public $id,
         $user_id,
         $service_name,
         $address,
         $contact_name,
         $contact_phone,
         $contact_email,
         $additional_info;

  const CLIENT_GROUPS = [
    'Children in residential homes',
    'Children in long term foster placements',
    'Children in short term foster placements',
    'Children en route to adoptions',
    'Potential adopters',
    'Children who are adopted',
    'Children who are accommodated (no care order)',
    'Children who live in kinship placement or special guardianship',
    'Children who are at home on a care order',
    'Children who are on a child protection plan, in need, or considered to be at the edge of care',
    'Birth parents who have had children removed (plan is to rehabilitate)',
    'Birth parents who have had children removed (plan is to not rehabilitate)'
  ];

  const SERVICES_OFFERED = [
    'Consultation for professionals',
    'Consultation for carers',
    'Attending professional/child protection meetings, or LAC reviews',
    'Attending school meetings',
    'Input into panels',
    'Advice on matching or placement choice',
    'Training for staff',
    'Training for carers',
    'Research',
    'Audit/service evaluation/clinical governance',
    'Strategic/systemic involvement in organisation',
    'Liaison between agencies'
  ];

  public function __construct($db, $data) {
    $this->db = $db;

    $this->id = $data['id'] ?: null;
    $this->user_id = $data['user_id'] ?: null;
    $this->service_name = $data['service_name'];
    $this->address = $data['address'];
    $this->contact_name = $data['contact_name'];
    $this->contact_phone = $data['contact_phone'];
    $this->contact_email = $data['contact_email'];

    if (!$data['id']) {
      $this->additional_info = json_encode($data['additional_info']);
    } else {
      $this->additional_info = $data['additional_info']; 
    }
  }

  public function additional_info() {
    $info = json_decode($this->additional_info, true);
    
    $info['client_groups'] = array_map(function($id) {
      return self::CLIENT_GROUPS[$id];
    }, $info['client_groups']);

    $info['services_offered'] = array_map(function($id) {
      return self::SERVICES_OFFERED[$id];
    }, $info['services_offered']);

    return $info;
  }

  public function save() {
    $query = $this->db->createQueryBuilder();

    if ($this->exists()) {
      $query
        ->update('cplaac_services')
        ->set('service_name', '?')
        ->set('address', '?')
        ->set('contact_name', '?')
        ->set('contact_phone', '?')
        ->set('contact_email', '?')
        ->set('additional_info', '?')
        ->where('id = ? AND user_id = ?');

      $query->setParameters([
        $this->service_name,
        $this->address,
        $this->contact_name,
        $this->contact_phone,
        $this->contact_email,
        $this->additional_info,
        $this->id,
        $this->user_id
      ]);
    } else {
      $query->insert('cplaac_services');
      $query->values([
        'user_id' => '?',
        'service_name' => '?',
        'address' => '?',
        'contact_name' => '?',
        'contact_phone' => '?',
        'contact_email' => '?',
        'additional_info' => '?'
      ]);
      $query->setParameters([
        $this->user_id,
        $this->service_name,
        $this->address,
        $this->contact_name,
        $this->contact_phone,
        $this->contact_email,
        $this->additional_info
      ]);
    }

    $result = $query->execute();

    if ($result && !$this->exists()) {
      $this->id = $this->db->lastInsertId();
    }

    return $result;
  }

  public function update($data) {
    $this->service_name = $data['service_name'];
    $this->address = $data['address'];
    $this->contact_name = $data['contact_name'];
    $this->contact_phone = $data['contact_phone'];
    $this->contact_email = $data['contact_email'];
    $this->additional_info = json_encode($data['additional_info']);
  }

  public function exists() {
    return (bool) $this->id;
  }

  public static function findById($db, $id) {
    $query = $db->createQueryBuilder();
    $query
      ->select('*')
      ->from('cplaac_services')
      ->where('id = :id')
      ->setParameter('id', $id);

    $result = $query->execute()->fetch();

    if ($result) {
      return new static($db, $result);
    }
  }

  public static function findAll($db) {
    $query = $db->createQueryBuilder();
    $query
      ->select('*')
      ->from('cplaac_services');

    $results = $query->execute()->fetchAll();
    $services = [];

    foreach ($results as $result) {
      $services[] = new static($db, $result);
    }

    return $services;
  }

}
