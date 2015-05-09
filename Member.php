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
      case 'list': { return $this->limitedList(); break; }
    }
  }

  /**
   * Return a list of all members
   */
  private function limitedList()
  {
    return Helper\Memcache::cache('members_list', function(){
      $groups = array(
        'cn=leden,ou=groups,o=nieuwedelft,dc=bolkhuis,dc=nl',
        'cn=kandidaatleden,ou=groups,o=nieuwedelft,dc=bolkhuis,dc=nl',
      );

      $people = Models\LdapGroup::peopleInGroups($groups);

      // Filter to the desired attributes
      $people = array_map(function($person){
        $t = new stdClass();
        $t->uid = $person->uid;
        $t->name = $person->name;
        return $t;
      }, $people);

      return new Tonic\Response(200, json_encode($people, JSON_UNESCAPED_SLASHES));
    });
  }

  private function all()
  {
    return Helper\Memcache::cache('members_all', function(){
      $groups = array(
        'cn=leden,ou=groups,o=nieuwedelft,dc=bolkhuis,dc=nl',
        'cn=kandidaatleden,ou=groups,o=nieuwedelft,dc=bolkhuis,dc=nl',
        'cn=oud-leden,ou=groups,o=nieuwedelft,dc=bolkhuis,dc=nl',
        'cn=ledenvanverdienste,ou=groups,o=nieuwedelft,dc=bolkhuis,dc=nl',
      );

      $people = Models\LdapGroup::peopleInGroups($groups);
      return new Tonic\Response(200, json_encode($people, JSON_UNESCAPED_SLASHES));
    });
  }

  private function current()
  {
    return Helper\Memcache::cache('members_current', function(){
      $groups = array(
        'cn=leden,ou=groups,o=nieuwedelft,dc=bolkhuis,dc=nl',
        'cn=kandidaatleden,ou=groups,o=nieuwedelft,dc=bolkhuis,dc=nl',
      );

      $people = Models\LdapGroup::peopleInGroups($groups);
      return new Tonic\Response(200, json_encode($people, JSON_UNESCAPED_SLASHES));
    });
  }

  private function candidate()
  {
    return Helper\Memcache::cache('members_candidate', function(){
      $groups = array(
        'cn=kandidaatleden,ou=groups,o=nieuwedelft,dc=bolkhuis,dc=nl',
      );

      $people = Models\LdapGroup::peopleInGroups($groups);
      return new Tonic\Response(200, json_encode($people, JSON_UNESCAPED_SLASHES));
    });
  }

  private function past()
  {
    return Helper\Memcache::cache('members_past', function(){
      $groups = array(
        'cn=oud-leden,ou=groups,o=nieuwedelft,dc=bolkhuis,dc=nl',
      );

      $people = Models\LdapGroup::peopleInGroups($groups);
      return new Tonic\Response(200, json_encode($people, JSON_UNESCAPED_SLASHES));
    });
  }
}
