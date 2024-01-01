<?php
namespace Models;

class LdapDepartment extends LdapObject
{
    /**
     * The mappings from membership status to the group they should belong to
     */
    public static $memberDepts = array(
      'lid' => 'ou=people,ou=leden,o=nieuwedelft,dc=i,dc=personaltardis,dc=me',
      'kandidaatlid' => 'ou=people,ou=kandidaatleden,o=nieuwedelft,dc=i,dc=personaltardis,dc=me',
      'oudlid' => 'ou=people,ou=oudleden,o=nieuwedelft,dc=i,dc=personaltardis,dc=me',
      'lidvanverdienste' => 'ou=people,ou=ledenvanverdienste,o=nieuwedelft,dc=i,dc=personaltardis,dc=me',
      'donateur' => 'ou=people,ou=donateurs,o=nieuwedelft,dc=i,dc=personaltardis,dc=me',
      'geen lid' => 'ou=people,ou=exleden,o=nieuwedelft,dc=i,dc=personaltardis,dc=me',
  );

    protected static $cache = array();

    /**
     * Constructs a new Department
     * @param $dn
     */
    public function __construct($dn)
    {
        parent::__construct($dn);
    }

    /**
     * Construct a Department-object from its DN
     * @static
     * @param  string $dn DN to load
     * @return LdapDepartment complete Department-object
     */
    public static function fromDn($dn)
    {
        if (isset(self::$cache[$dn])) {
            return self::$cache[$dn];
        }

        $ldap = \Helper\LdapHelper::connect();

        $result = new self($dn);
        $result->exists = true;

        self::$cache[$dn] = $result;

        return $result;
    }

    /**
     * Returns the People in an array of departments
     * @param array $depts  An array of DNs to look up
     * @return array         The people in the specified groups
     */
    public static function peopleInDepts($depts)
    {
        $results = array();

        foreach ($depts as $dept) {
            $results = array_merge($results, self::fromDn($dept)->people());
        }

        return $results;
    }

    /**
     * Returns the array of people that belong to this group
     * @return array   The array of People in this group
     */
    public function people()
    {
        $result = array();
        if (!isset($dn)) {
            return $result;
        }

        $query = '(uid=*)';

        return Person::where($query, $dn);
    }

    /**
     * @param string $uid the uid to look up
     * @return bool       wether the uid belongs to this department
     */
    public function hasMember($uid)
    {
        $query = '(uid=' . $uid . ')';

        $ldap = \Helper\LdapHelper::connect();
        $result = $ldap->search($query, $this->dn);

        return count($result) > 0;
    }
}
