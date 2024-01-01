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
        return Helper\Memcache::cache('members_list', function () {
            $groups = array(
                'ou=people,ou=leden,o=nieuwedelft,dc=i,dc=personaltardis,dc=me',
                'ou=people,ou=kandidaatleden,o=nieuwedelft,dc=i,dc=personaltardis,dc=me',
            );

            $people = Models\LdapDepartment::peopleInDepts($groups);

            // Filter to the desired attributes
            $people = array_map(function ($person) {
                $t = new stdClass();
                $t->uid = $person->uid;
                $t->name = $person->name();
                return $t;
            }, $people);

            return new Tonic\Response(200, json_encode($people, JSON_UNESCAPED_SLASHES));
        });
    }

    private function all()
    {
        return Helper\Memcache::cache('members_all', function () {
            $groups = array(
                'ou=people,ou=leden,o=nieuwedelft,dc=i,dc=personaltardis,dc=me',
                'ou=people,ou=kandidaatleden,o=nieuwedelft,dc=i,dc=personaltardis,dc=me',
                'ou=people,ou=oudleden,o=nieuwedelft,dc=i,dc=personaltardis,dc=me',
                'ou=people,ou=ledenvanverdienste,o=nieuwedelft,dc=i,dc=personaltardis,dc=me',
                'ou=people,ou=donateurs,o=nieuwedelft,dc=i,dc=personaltardis,dc=me',
                'ou=people,ou=exleden,o=nieuwedelft,dc=i,dc=personaltardis,dc=me',
            );

            $people = Models\LdapDepartment::peopleInDepts($groups);
            return new Tonic\Response(200, json_encode($people, JSON_UNESCAPED_SLASHES));
        });
    }

    private function current()
    {
        return Helper\Memcache::cache('members_current', function () {
            $groups = array(
                'ou=people,ou=leden,o=nieuwedelft,dc=i,dc=personaltardis,dc=me',
                'ou=people,ou=kandidaatleden,o=nieuwedelft,dc=i,dc=personaltardis,dc=me',
            );

            $people = Models\LdapDepartment::peopleInDepts($groups);
            return new Tonic\Response(200, json_encode($people, JSON_UNESCAPED_SLASHES));
        });
    }

    private function candidate()
    {
        return Helper\Memcache::cache('members_candidate', function () {
            $groups = array(
                'ou=people,ou=kandidaatleden,o=nieuwedelft,dc=i,dc=personaltardis,dc=me',
            );

            $people = Models\LdapDepartment::peopleInDepts($groups);
            return new Tonic\Response(200, json_encode($people, JSON_UNESCAPED_SLASHES));
        });
    }

    private function past()
    {
        return Helper\Memcache::cache('members_past', function () {
            $groups = array(
                'ou=people,ou=oudleden,o=nieuwedelft,dc=i,dc=personaltardis,dc=me',
                'ou=people,ou=exleden,o=nieuwedelft,dc=i,dc=personaltardis,dc=me',
            );

            $people = Models\LdapDepartment::peopleInDepts($groups);
            return new Tonic\Response(200, json_encode($people, JSON_UNESCAPED_SLASHES));
        });
    }
}
