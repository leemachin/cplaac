<?php

namespace Cplaac\Services;

use Cplaac\Core\Entity;

class Service extends Entity {

  protected $service_name,
            $address,
            $contact_name,
            $contact_phone,
            $contact_email,
            $additional_info;

  protected $table_name = "cplaac_services";

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

  public function isValid() {

  }

  public function additional_info() {
    return json_decode($this->additional_info);
  }

}
