<?php

require_once('BlipResource.php');

use Respect\Validation\Validator as v;

/**
 * @uri /members
 * @uri /members/([a-z]+)
 * @provides application/json
 */
class MemberCollection extends BlipResource
{
  /**
   * @method GET
   * @return Tonic\Response
   */
  public function router($subset = '')
  {
    switch ($subset) {
      case '': { return $this->all(); break; }
      case 'current': { return $this->current(); break; }
      case 'candidate': { return $this->candidate(); break; }
      case 'past': { return $this->past(); break; }
    }
  }

  private function all()
  {
    return new Tonic\Response(200, json_encode($this->ldap->find_all_members()));
  }

  private function current()
  {
    return new Tonic\Response(200, json_encode($this->ldap->find_current_members()));
  }

  private function candidate()
  {
    return new Tonic\Response(200, json_encode($this->ldap->find_candidate_members()));
  }

  private function past()
  {
    return new Tonic\Response(200, json_encode($this->ldap->find_past_members()));
  }
}
