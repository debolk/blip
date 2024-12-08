<?php
namespace Models;

use Helper\LdapHelper;

class LdapOUnit extends LdapObject
{
    /**
     * The mappings from membership status to the unit they should belong to
     */
    private static array $personOUnits = array(
      'erelid' => 'ou=people,ou=ereleden,o=nieuwedelft,',
      'lid' => "ou=people,ou=leden,o=nieuwedelft,",
      'kandidaatlid' => 'ou=people,ou=kandidaatleden,o=nieuwedelft,',
      'oud lid' => 'ou=people,ou=oudleden,o=nieuwedelft,',
      'lid van verdienste' => 'ou=people,ou=ledenvanverdienste,o=nieuwedelft,',
      'donateur' => 'ou=people,ou=donateurs,o=nieuwedelft,',
      'ex lid' => 'ou=people,ou=exleden,o=nieuwedelft,',
      'extern' => 'ou=people,ou=externen,o=nieuwedelft,',
    );

    /**
     * Returns the defined member units after adding the BASE_DN
     *
     * @return array the member units
     */
    public static function getPersonOUnits() {
	    $units = array();
        foreach (LdapOUnit::$personOUnits as $k => $v) {
            $units[$k] = $v . LdapHelper::Connect()->basedn;
        }
        return $units;
    }

    protected static $cache = array();
	protected $memberUids;

    /**
     * Constructs a new OUnit
     * @param array $attributes
     */
    public function __construct($attributes = array())
    {
        parent::__construct($attributes);
    }

	/**
	 * Construct a OUnit-object from its DN
	 * @static
	 * @param string $dn DN to load
	 * @return LdapOUnit|null complete OUnit-object or null
	 */
    public static function fromDn(string $dn) : LdapOUnit | null
    {
        if (isset(self::$cache[$dn])) {
            return self::$cache[$dn];
        }

        $ldap = \Helper\LdapHelper::connect();

        $attributes = $ldap->get($dn, 'organizationalUnit');
		if (!$attributes){
			return null;
		}
        $result = new self($attributes);
        $result->exists = true;
        $result->dn = $dn;

        self::$cache[$dn] = $result;

        return $result;
    }

    /**
     * Returns the People in an array of units
     * @param array $units  An array of DNs to look up
     * @return array         The people in the specified groups
     */
    public static function peopleInUnits(array $units, string $mode = 'all') : array
    {
        $results = array();

        foreach ($units as $unit) {
            $results = array_unique(array_merge($results, self::fromDn($unit)->people($mode)), SORT_REGULAR);
        }

        return $results;
    }

	private function getMembers() : void {
		if ($this->hasMembers()) return;

		$ldap = LdapHelper::Connect();
		$result = $ldap->search('(&(objectClass=fdBolkData)(!(uid=nobody)))', ['uid'], $this->dn);
		$this->memberUids = $result;
	}

	private function hasMembers() : bool {
		return (isset($this->memberUids) && $this->memberUids != null);
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
        foreach ($this->memberUids as $uid) {
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

		return in_array($uid, $this->memberUids);
    }
}
