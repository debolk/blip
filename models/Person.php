<?php

namespace Models;

class Person implements \JSONSerializable
{
    private $attributes = array();
    private $ldapPerson = null;

    /**
     * The list of properties that can be set by the user
     */
    public $allowed = array(
        'initials',
        'firstname',
        'lastname',
        'email',
        'phone',
        'phone_parents',
        'address',
        'gender',
        'pronouns',
        'nickname',
        'programme',
        'institution',
    );

    /**
     * The mapping from local properties to properties in LdapPerson
     */
    protected $renaming = array(
        'uid' => 'uid',
        'initials' => 'initials',
        'firstname' => 'givenName',
        'lastname' => 'sn',
        'email' => 'mail',
        'phone' => 'homePhone',
        'phone_parents' => 'fdParentPhone',
        'address' => 'homePostalAddress',
        'dateofbirth' => 'dateOfBirth',
        'gender' => 'gender',
        'pronouns' => 'fdPronouns',
        'membership' => 'gidNumber',
        'nickname' => 'fdNickName',
        'programme' => 'fdProgramme',
        'institution' => 'fdInstitution',
        'iva' => 'fdIVA',
        'inauguration_date' => 'fdDateOfInauguration',
        'resignation_letter' => 'fdDateOfResignationLetter',
        'resignation' => 'fdDateOfResignation',
        'dead' => 'fdDead',
    );

    /**
     * The mapping from membership status to guidnumber
     */
    protected $groupIds = array(
        'lid' => 1025,
        'kandidaatlid' => 1084,
        'oudlid' => 1095,
        'lidvanverdienste' => 1098,
        'donateur' => 1014,
        'geen lid' => 1097,
    );

    protected $dirty = array();
    protected $pass;

    /**
     * Constructs a new Person
     * @param array $attributes
     */
    public function __construct($attributes = array())
    {
        $this->attributes = $attributes;
    }

    /**
     * Creates a new Person from an LdapPerson
     * @param LdapPerson $person    the LdapPerson to create a Person from
     * @returns Person              the resulting Person
     */
    public static function fromLdapPerson($person)
    {
        $result = new self();
        foreach ($result->renaming as $local => $ldap) {
            if (isset($person->$ldap)) {
                $result->attributes[$local] = $person->$ldap;
            }
        }

        $result->ldapPerson = $person;

        return $result;
    }

    /**
     * Generates a password and stores it to be saved
     * After save the user will be notified by mail
     */
    public function generatePassword()
    {
        $this->pass = bin2hex(openssl_random_pseudo_bytes(5));
    }

    /**
     * Constructs a new Person based off its UID
     * @static
     * @param  string $uid UID of the Person to find
     * @return Person      complete Person-object
     */
    public static function fromUid($uid)
    {
        $person = LdapPerson::fromUid($uid);
        if (!$person) {
            throw new \Exception("User ($uid) not found!");
        }

        if (in_array('gosaUserTemplate', $person->objectClass)) {
            return false;
        }

        return self::fromLdapPerson($person);
    }

    /**
     * Searches for persons with a given ldap query
     * @param string $query     the ldap query
     * @returns array           the persons matching the query
     */
    public static function where($query, $basedn = null)
    {
        $ldap = \Helper\LdapHelper::connect();
        $search = $ldap->search('(&(objectClass=iNetOrgPerson)(!(objectClass=gosaUserTemplate))(!(uid=nobody))' . $query . ')', null, $basedn);

        $results = array();
        foreach ($search as $key => $object) {
            if ($key === 'count') {
                continue;
            }
            $person = new LdapPerson($object);
            $results[] = Person::fromLdapPerson($person);
        }

        return $results;
    }

    /**
     * Returns all users from LDAP
     * @static
     * @return array[Person] all persons
     */
    public static function all()
    {
        return self::where("");
    }

    /**
     * Saves the current person to ldap, creates a new LdapPerson if needed
     */
    public function save()
    {
        if ($this->ldapPerson == null) {
            $this->attributes['uid'] = $this->findUid();
            $this->ldapPerson = LdapPerson::getDefault();
            $setdept = true;
        }

        $data = $this->to_array();
        foreach ($data as $key => $value) {
            if (!isset($this->renaming[$key])) {
                continue;
            }

            $ldapkey = $this->renaming[$key];
            $this->ldapPerson->$ldapkey = $value;
        }

        if (isset($this->pass)) {
            $this->ldapPerson->userpassword = $this->pass;
        }

        $result = $this->ldapPerson->save();

        //Set membership after saving
        if (isset($setdept) && isset($this->attributes['membership'])) {
            $this->ldapPerson->moveDN($this->attributes['uid'], LdapDepartment::$memberDeps[$this->attributes['membership']]);
        }

        return $result;
    }

    /**
     * Finds an unused uid for a new user
     * @returns string          an unused uid
     */
    protected function findUid()
    {
        $strip = function ($uid) {
            setlocale(LC_ALL, 'en_US.UTF8');
            $uid = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $uid);
            return preg_replace('#[^a-z0-9]#', '', $uid);
        };

        $ldap = \Helper\LdapHelper::connect();

        // Try sensible options first
        $options = [];

        if (! empty($this->attributes['initials'])) {
            $options[] = strtolower($this->attributes['initials'][0].$this->attributes['lastname']);
            $options[] = strtolower($this->attributes['initials'].$this->attributes['lastname']);
        }
        $options[] = strtolower($this->attributes['firstname'].$this->attributes['lastname']);

        foreach ($options as $candidate_uid) {
            $candidate_uid = $strip($candidate_uid);
            if (! $ldap->getDn($candidate_uid)) {
                return $candidate_uid;
            }
        }

        // Try a numbered option
        for ($i=1; true; $i++) {
            $candidate_uid = $strip(strtolower($this->attributes['firstname'].$this->attributes['lastname']).$i);
            if (! $ldap->getDn($candidate_uid)) {
                return $candidate_uid;
            }
        }
    }


    /**
     * Returns an array-representation of this Person
     * @return array representation of this Person
     */
    public function to_array()
    {
        return array_merge($this->attributes, [
          'href' => getenv('BASE_URL').'persons/'.$this->__get('uid'),
          'name' => $this->name(),
          'membership' => $this->membership(),
      ]);
    }

    /**
     * The full name of this person
     * @return string
     */
    public function name()
    {
        if (isset($this->attributes['firstname'])) {
            if (isset($this->attributes['lastname'])) {
                return $this->attributes['firstname'].' '.$this->attributes['lastname'];
            } else {
                return $this->attributes['firstname'];
            }
        } else {
            if (isset($this->attributes['lastname'])) {
                return $this->attributes['lastname'];
            } else {
                return '';
            }
        }
    }

    /**
     * The membership status of this person
     * @return string
     */
    public function membership()
    {

        foreach (LdapDepartment::$memberDeps as $status => $dn) {
            $dept = LdapDepartment::fromDn($dn);
            if ($dept->hasMember($this->uid)) {
                return $status;
            }
        }

        return 'geen lid';
    }

    /**
     * Adds this user to the group belonging to the new membership status
     * and saves the member
     * @param string $membership      the new membership status
     */
    public function setMembership($membership)
    {
        if (!array_key_exists($membership, LdapDepartment::$memberDeps)) {
            return;
        }

        $prev = $this->membership();
        if ($membership == $prev) {
            return;
        }

        $this->ldapPerson->moveDN($this->attributes['uid'], LdapDepartment::$memberDeps[$this->attributes['membership']]);

        if (in_array($membership, array('lid', 'kandidaatlid'))) {
            if (!isset($this->ldapPerson->userpassword)) {
                $this->generatePassword();
            }
        }

        $this->save();
    }

    /**
     * Serializes this Person to JSON
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->to_array();
    }

    /**
     * Gets a property of a Person
     * @param  string $name the property to read
     * @return mixed        the value of the property
     */
    public function __get($name)
    {
        if (isset($this->attributes[$name])) {
            return $this->attributes[$name];
        }
    }

    /**
     * Sets a property of a Person
     * @param string $name  the property to set
     * @param mixed $value  the value to set
     */
    public function __set($name, $value)
    {
        if ($name == "membership") {
            $this->setMembership($value);
            return;
        }
        $this->attributes[$name] = $value;
        $dirty[$name] = true;
    }
}
