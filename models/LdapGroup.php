<?php
namespace Models;

use Helper\LdapHelper;

class LdapGroup extends LdapObject
{
    /**
     * The mappings from membership status to the group they should belong to
     */
    private static array $memberGroups = array(
      'lid' => "ou=people,ou=leden,o=nieuwedelft",
      'kandidaatlid' => 'ou=people,ou=kandidaatleden,o=nieuwedelft',
      'oudlid' => 'ou=people,ou=oudleden,o=nieuwedelft',
      'lidvanverdienste' => 'ou=people,ou=ledenvanverdienste,o=nieuwedelft',
      'donateur' => 'ou=people,ou=donateurs,o=nieuwedelft',
      'geen lid' => 'ou=people,ou=exleden,o=nieuwedelft',
    );

    /**
     * Returns the defined member groups after adding the BASE_DN
     *
     * @return array the member groups
     */
    public static function getMemberGroups() {
        $groups = array();
        foreach (LdapGroup::$memberGroups as $k => $v) {
            $groups[$k] = $v . LdapHelper::Connect()->basedn;
        }
        return $groups;
    }

    protected static $cache = array();

    /**
     * Constructs a new Group
     * @param array $attributes
     */
    public function __construct($attributes = array())
    {
        parent::__construct($attributes);
    }

    /**
     * Construct a Group-object from its DN
     * @static
     * @param  string $dn DN to load
     * @return LdapGroup     complete Group-object
     */
    public static function fromDn(string $dn) : LdapGroup
    {
        if (isset(self::$cache[$dn])) {
            return self::$cache[$dn];
        }

        $ldap = \Helper\LdapHelper::connect();

        $attributes = $ldap->get($dn, 'posixGroup');
        $result = new self($attributes);
        $result->exists = true;
        $result->dn = $dn;

        self::$cache[$dn] = $result;

        return $result;
    }

    /**
     * Returns the People in an array of groups
     * @param array $groups  An array of DNs to look up
     * @return array         The people in the specified groups
     */
    public static function peopleInGroups(array $groups) : array
    {
        $results = array();

        foreach ($groups as $group) {
            $results = array_merge($results, self::fromDn($group)->people());
        }

        return $results;
    }

    /**
     * Returns the array of people that belong to this group
     * @return array   The array of People in this group as PersonModel
     */
    public function people() : array
    {
        $result = array();
        if (!isset($this->attributes['memberuid'])) {
            return $result;
        }

        //Convert to array if this is a string
        if (is_string($this->memberuid)) {
            $this->attributes['memberuid'] = array($this->memberuid);
        }

        $query = '(|';
        foreach ($this->memberuid as $uid) {
            $query .= "(uid=$uid)";
        }
        $query .= ')';

        return PersonModel::where($query);
    }

    /**
     * @param string $uid the uid to look up
     * @return bool       wether the uid belongs to this group
     */
    public function hasMember(string $uid) : bool
    {
        if (!isset($this->attributes['memberuid'])) {
            return false;
        }

        if (is_string($this->attributes['memberuid'])) {
            return $this->memberuid == $uid;
        }

        return in_array($uid, $this->memberuid);
    }

    /**
     * Adds a member to the group
     * @param string $uid The new member's uid
     * @return void
     */
    public function addMember(string $uid): void
    {
        if (!isset($this->attributes['memberuid'])) {
            $this->attributes['memberuid'] = array();
        }

        //Convert to array if this is a string
        if (is_string($this->memberuid)) {
            $this->attributes['memberuid'] = array($this->memberuid);
        }

        $this->__set('memberuid', array_merge($this->attributes['memberuid'], array($uid)));
    }

    /**
     * Removes a member from the group
     * @param string $uid The member's uid
     * @return void
     */
    public function removeMember(string $uid) : void
    {
        if (!isset($this->attributes['memberuid'])) {
            return;
        }

        //Convert to array if this is a string
        if (is_string($this->memberuid)) {
            $this->attributes['memberuid'] = array($this->memberuid);
        }

        if (!in_array($uid, $this->memberuid)) {
            return;
        }

        $new = array();
        foreach ($this->memberuid as $name) {
            if ($name != $uid) {
                $new[] = $name;
            }
        }

        $this->__set('memberuid', $new);
    }
}
