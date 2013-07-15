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
   * @loggedIn lid
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
    $groups = array(
      'cn=leden,ou=groups,o=nieuwedelft,dc=bolkhuis,dc=nl',
      'cn=kandidaatleden,ou=groups,o=nieuwedelft,dc=bolkhuis,dc=nl',
      'cn=oud-leden,ou=groups,o=nieuwedelft,dc=bolkhuis,dc=nl',
    );

    $people = Models\LdapGroup::peopleInGroups($groups);
    return new Tonic\Response(200, json_encode($people, JSON_UNESCAPED_SLASHES));
  }

  private function current()
  {
    $groups = array(
      'cn=leden,ou=groups,o=nieuwedelft,dc=bolkhuis,dc=nl',
      'cn=kandidaatleden,ou=groups,o=nieuwedelft,dc=bolkhuis,dc=nl',
    );

    $people = Models\LdapGroup::peopleInGroups($groups);
    return new Tonic\Response(200, json_encode($people, JSON_UNESCAPED_SLASHES));
  }

  private function candidate()
  {
    $groups = array(
      'cn=kandidaatleden,ou=groups,o=nieuwedelft,dc=bolkhuis,dc=nl',
    );

    $people = Models\LdapGroup::peopleInGroups($groups);
    return new Tonic\Response(200, json_encode($people, JSON_UNESCAPED_SLASHES));
  }

  private function past()
  {
    $groups = array(
      'cn=oud-leden,ou=groups,o=nieuwedelft,dc=bolkhuis,dc=nl',
    );

    $people = Models\LdapGroup::peopleInGroups($groups);
    return new Tonic\Response(200, json_encode($people, JSON_UNESCAPED_SLASHES));
  }
}
