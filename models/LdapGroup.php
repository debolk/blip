<?php
namespace Models;

use Helper\LdapHelper;

class LdapGroup extends LdapObject
{

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
	 * @param string $dn DN to load
	 * @return LdapGroup|null complete OUnit-object or null
	 */
    public static function fromDn(string $dn) : LdapGroup | null
    {
        if (isset(self::$cache[$dn])) {
            return self::$cache[$dn];
        }

        $ldap = LdapHelper::connect();

		$attributes = $ldap->get($dn, 'posixGroup');

		if (!$attributes){
			return null;
		}

        $result = new self($attributes);
        $result->exists = true;
        $result->dn = $dn;

        self::$cache[$dn] = $result;

        return $result;
    }

	public static function fromId(string $id) : LdapGroup | null {
		$ldap = LdapHelper::Connect();

		$result = $ldap->getDn('(&(gidnumber=' . $id . ')(objectClass=posixGroup))');
		if (!$result) {
			return null;
		}
		return self::fromDn($result);
	}


	/**
	 * Returns the People in an array of groups
	 * @param array $groups  An array of DNs to look up
	 * @return array         The people in the specified groups
	 */
	public static function peopleInGroups(array $groups, string $mode = 'all') : array
	{
		$results = array();

		foreach ($groups as $group) {
			$results = array_unique(array_merge($results, self::fromId($group)->people($mode)), SORT_REGULAR);
		}

		return $results;
	}

	private function getMembers() : void {
		if ($this->hasMembers()) return;

		$ldap = LdapHelper::Connect();
		$result = $ldap->get($this->dn, 'posixGroup', array('memberuid'));

		if ($result and isset($result['memberuid'])) {
			$this->memberuid = $result['memberuid'];
		}
	}

	private function hasMembers() : bool {
		return (isset($this->memberuid) && $this->memberuid != null);
	}

    /**
     * Returns the array of people that belong to this unit
     * @return array   The array of People in this unit as PersonModel
     */
    public function people(string $mode = 'all') : array
    {
	    $this->getMembers();
		if (!$this->hasMembers()) {
			return array();
		}

        $query = '(|';
        foreach ($this->memberuid as $uid) {
            $query .= "(uid=$uid)";
        }
        $query .= ')';

        return PersonModel::where($query, $mode);
    }

    /**
     * @param string $uid the uid to look up
     * @return bool       wether the uid belongs to this unit
     */
    public function hasMember(string $uid) : bool
    {
	    $this->getMembers();
	    if (!$this->hasMembers()) {
		    return false;
	    }

		return in_array($uid, $this->memberuid);
    }

	/**
	 * Adds a member to the LdapGroup
	 * @param string $uid   the uid for the user to add
	 * @return void
	 */
	public function addMember(string $uid) : void {
		if (!isset($this->attributes['memberuid'])) {
			$this->attributes['memberuid'] = array();
		} else if (is_string($this->memberuid)) {
			$this->attributes['memberuid'] = array($this->memberuid);
		}
		
		if (in_array($uid, $this->memberuid)){
			return;
		}
		
		$this->__set('memberuid', array_merge($this->memberuid, array($uid)));
	}

	/**
	 * Removes a member from the LdapGroup
	 * @param string $uid   the user uid to be removed
	 * @return void
	 */
	public function removeMember(string $uid) : void {
		if (!isset($this->attributes['memberuid'])) {
			return;
		}

		if (is_string($this->memberuid)){
			$this->attributes['memberuid'] = array($this->memberuid);
		}

		if (!in_array($uid, $this->memberuid)){
			return;
		}

		$new = array();
		foreach($this->memberuid as $name) {
			if ($name != $uid){
				$new[] = $name;
			}
		}
		$this->__set('memberuid', $new);
	}
}
