<?php

namespace Models;

use Helper\LdapHelper;
use Helper\ResponseHelper;
use Imagick;
use ImagickException;
use Slim\Psr7\Response;

class PersonModel implements \JSONSerializable
{
    public array $attributes = array();
	private array $dirty = array();
    private LdapPerson|null $ldapPerson = null;
	private static $base_url;

    /**
     * The list of properties that can be set by the user
     */
    public static array $allowed = array(
        'initials',
        'firstname',
        'surname',
        'nickname',
        'dateofbirth',
        'pronouns',
        'email',
        'phone',
        'phone_emergency',
        'address',
        'inauguration_date',
        'resignation_letter_date',
        'resignation_date',
        'programme',
        'institution',
        'dead',
	    'membership',
    );

	protected static array $bools = array(
		'photo_visible',
		'iva',
		'dead',
		'avg',
		'avg_address',
		'avg_dob',
		'avg_institution',
		'avg_programme',
		'avg_email',
		'avg_phone_emergency',
		'avg_phone',
		'avg_pronouns'
	);

    /**
     * The mapping from local properties to properties in LdapPerson
     */
    protected static array $renaming = array(
        'uid' => 'uid',
        'initials' => 'initials',
        'firstname' => 'givenName',
        'surname' => 'sn',
        'nickname' => 'fdNickName',
        'dateofbirth' => 'dateOfBirth',
        'pronouns' => 'fdPronouns',
        'email' => 'mail',
        'phone' => 'homePhone',
        'phone_emergency' => 'fdEmergencyPhone',
        'address' => 'homePostalAddress',
        'inauguration_date' => 'fdDateOfInauguration',
        'resignation_letter_date' => 'fdDateOfResignationLetter',
        'resignation_date' => 'fdDateOfResignation',
        'programme' => 'fdProgramme', //might be array
        'institution' => 'fdInstitution', //might be array
        'photo_visible' => 'fdPhotoVisible',
        'iva' => 'fdIVA',
        'dead' => 'fdDead',
        'avg' => 'fdAVGAccept',
        'avg_address' => 'fdAddressShare',
        'avg_dob' => 'fdDoBShare',
        'avg_institution' => 'fdInstitutionShare',
        'avg_programme' => 'fdProgrammeShare',
        'avg_email' => 'fdMailShare',
        'avg_phone_emergency' => 'fdEmergencyPhoneShare',
        'avg_phone' => 'fdPhoneShare',
        'avg_pronouns' => 'fdPronounsShare',
    );

	public static array $groupIds = array(
		'member' => 1025,
		'former_member' => 1095,
		'external' => 1108,
		'member_of_merit' => 1098,
		'candidate_member' => 1084,
		'honorary_member' => 1109,
		'ex_member' => 1110,
		'donor' => 1014
	);

	/**
	 * The mappings from membership status to the unit they should belong to
	 */
	public static array $personOUnits = array(
		'honorary_member' => 'ou=people,ou=ereleden,o=nieuwedelft',
		'member' => "ou=people,ou=leden,o=nieuwedelft",
		'candidate_member' => 'ou=people,ou=kandidaatleden,o=nieuwedelft',
		'former_member' => 'ou=people,ou=oudleden,o=nieuwedelft',
		'member_of_merit' => 'ou=people,ou=ledenvanverdienste,o=nieuwedelft',
		'donor' => 'ou=people,ou=donateurs,o=nieuwedelft',
		'ex_member' => 'ou=people,ou=exleden,o=nieuwedelft',
		'external' => 'ou=people,ou=externen,o=nieuwedelft',
	);

    protected array $additionalClasses = array(
        'member' => array('posixAccount', 'gosaIntranetAccount', 'fdBolkData', 'fdBolkDataAVG'),
        'former_member' => array('posixAccount', 'gosaIntranetAccount', 'fdBolkData', 'fdBolkDataAVG'),
        'external' => array(),
		'ex_member' => array('posixAccount', 'gosaIntranetAccount', 'fdBolkData', 'fdBolkDataAVG'),
        'member_of_merit' => array('posixAccount', 'gosaIntranetAccount', 'fdBolkData', 'fdBolkDataAVG'),
        'candidate_member' => array('posixAccount', 'gosaIntranetAccount', 'fdBolkData', 'fdBolkDataAVG'),
	    'honorary_member' => array('posixAccount', 'gosaIntranetAccount', 'fdBolkData', 'fdBolkDataAVG'),
    );

    protected string $pass;
	protected LdapHelper $ldap;

	/**
     * Constructs a new Person
     * @param array $attributes
     */
    public function __construct(array $attributes = array())
    {
        $this->attributes = $attributes;
		$ldap = LdapHelper::Connect();
    }

	public static function Initialise($base_url){
		self::$base_url = $base_url;
	}

    /**
     * Creates a new Person from an LdapPerson
     * @param LdapPerson $person    the LdapPerson to create a Person from
     * @returns PersonModel              the resulting Person
     */
    public static function fromLdapPerson(LdapPerson $person) : PersonModel
    {
		$result = new self();
        foreach (self::$renaming as $local => $ldap) {
			$ldap = strtolower($ldap);
            if (isset($person->$ldap)) {
				$value = $person->$ldap;
				if ( in_array($local, self::$bools) ){
					$value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
				}
				$result->attributes[$local] = $value;
            } else {
				if ( in_array($local, self::$bools)) {
					$result->attributes[$local] = false;
				}
            }
        }

        $result->ldapPerson = $person;
		$result->name();

        return $result;
    }

    /**
     * Generates a password and stores it to be saved
     * After save the user will be notified by mail
     */
    public function generatePassword(): void {
        $this->pass = bin2hex(openssl_random_pseudo_bytes(8));
    }

    /**
     * Constructs a new Person based off its UID
     * @static
     * @param  string $uid UID of the Person to find
     * @return PersonModel|null      complete Person-object
     */
    public static function fromUid(string $uid) : ?PersonModel
    {
        $person = LdapPerson::fromUid($uid);
        if (!$person) {
            throw new \Exception("User ($uid) not found!");
        }

        if (in_array('gosaUserTemplate', $person->objectclass)) {
            return null;
        }

        return self::fromLdapPerson($person);
    }

    /**
     * Searches for persons with a given ldap query
     * @param string $query     the ldap query
     * @returns array           the persons matching the query
     */
    public static function where(string $query, string $mode = 'all') : array
    {
        $ldap = \Helper\LdapHelper::connect();

        $search = $ldap->search('(&(objectClass=fdBolkData)(!(uid=nobody))' . $query . ')');

        $results = array();
        foreach ($search as $key => $object) {
			$object = $ldap->flatten($object);
			$person = new LdapPerson($object);

	        $results[] = match ($mode) {
		        'basic' => PersonModel::fromLdapPerson($person)->getBasic(),
		        'sanitize' => PersonModel::fromLdapPerson($person)->sanitizeAvg(),
		        default => PersonModel::fromLdapPerson($person),
	        };
        }

        return $results;
    }

    /**
     * Returns all users from LDAP
     * @static
     * @return array[PersonModel] all persons
     */
    public static function all($mode = 'all') : array
    {
        return self::where("", $mode);
    }

    /**
     * Saves the current person to ldap, creates a new LdapPerson if needed
     */
    public function save($force_save = false) : bool
    {
        if ($this->ldapPerson == null) {
            $this->attributes['uid'] = $this->findUid();
            $this->ldapPerson = LdapPerson::getDefault();
        }

        $data = $this->to_array();

	    foreach ($data as $key => $value) {
            if (!isset(self::$renaming[$key]) or
	            (!$force_save && !isset($this->dirty[$key])) or
	            ($value === null)) {
                continue;
            }

			$ldapkey = self::$renaming[$key];
            $this->ldapPerson->$ldapkey = $value;
        }

        if (isset($this->pass)) {
            $this->ldapPerson->userpassword = hash("sha256", $this->pass);
        }

        $result = $this->ldapPerson->save();
		if (!$result) return false;

        //Set membership after saving
        if (isset($this->attributes['membership']) &&
	        ($force_save || isset($this->dirty['membership']))) {
            $this->setMembership($this->attributes['membership']);
        }

        return true;
    }

    /**
     * Finds an unused uid for a new user
     * @returns string          an unused uid
     */
    protected function findUid() : string
    {
        $strip = function ($uid) {
            setlocale(LC_ALL, 'en_US.UTF8');
            $uid = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $uid);
            return preg_replace('#[^a-z0-9]#', '', $uid);
        };

        $ldap = \Helper\LdapHelper::connect();

        // Try sensible options first
        $options = [];

        if (! empty($this->attributes['initials']) ) {
            $options[] = strtolower($this->attributes['initials'][0].$this->attributes['surname']);
            $options[] = strtolower($this->attributes['initials'].$this->attributes['surname']);
        }
        $options[] = strtolower($this->attributes['firstname'].$this->attributes['surname']);

        foreach ($options as $candidate_uid) {
            $candidate_uid = $strip($candidate_uid);
            if (! $ldap->getUserDn($candidate_uid)) {
                return $candidate_uid;
            }
        }

        // Try a numbered option
        for ($i=1; true; $i++) {
            $candidate_uid = $strip(strtolower($this->attributes['firstname'].$this->attributes['surname']).$i);
            if (! $ldap->getUserDn($candidate_uid)) {
                return $candidate_uid;
            }
        }
    }

    /**
     * Returns a JSON serializable representation of this Person of only the basic data
     * @return \stdClass
     */
    public function getBasic() : \stdClass {
        $basic = new \stdClass();
        $basic->uid=$this->uid;
        $basic->href=self::$base_url.'/person/'.$this->uid;
		$basic->initials=$this->initials;
        $basic->name=$this->name;
		$basic->firstname=$this->firstname;
		$basic->surname=$this->surname;
		$basic->nickname=$this->nickname;
        $basic->membership=$this->membership();
        if ($this->avg_email && $this->avg) $basic->email=$this->email; //only send mail if fdMailShare is true
        $basic->avg_email=$this->avg_email;
        $basic->photo_visible=$this->photo_visible;
        return $basic;
    }

    /**
     * Returns the persons information after removing all the information the user doesn't want to share.
     * @return array sanitized array of attributes
     */
    public function sanitizeAvg() : array {

		$avg = array();
        if ( !$this->avg_address ) $avg[] = 'address';
        if ( !$this->avg_dob) $avg[] = 'dateofbirth';
        if ( !$this->avg_institution) $avg[] = 'institution';
        if ( !$this->avg_programme) $avg[] = 'programme';
        if ( !$this->avg_email) $avg[] = 'email';
        if ( !$this->avg_phone_emergency) $avg[] = 'phone_emergency';
        if ( !$this->avg_phone) $avg[] = 'phone';
        if ( !$this->avg_pronouns) $avg[] = 'pronouns';

        if ( !$this->avg) { //remove all avg attributes if the person didn't accept the privacy statement
            $avg = ['address', 'dateofbirth', 'institution', 'programme', 'email', 'phone_emergency', 'phone', 'pronouns'];
        }

        $sanitized = array_diff_key($this->attributes, array_fill_keys($avg, false));

        return array_merge($sanitized, [
            'href' => self::$base_url.'/persons/'.$this->uid,
            'name' => $this->name,
            'membership' => $this->membership(),
        ]);
    }

    /**
     * Returns an array-representation of this Person
     * @return array representation of this Person
     */
    public function to_array() : array {
        return array_merge($this->attributes, [
          'href' => self::$base_url.'/persons/'.$this->uid,
          'name' => $this->name,
          'membership' => $this->membership(),
      ]);
    }

    /**
     * Sets the full name of this person
     */
    public function name()
    {
        $first = '';
        $nick = '';
        $last = '';
        if (isset($this->attributes['firstname'])) $first = $this->firstname;
        if (isset($this->attributes['surname'])) $last = $this->surname;
        if (isset($this->attributes['nickname'])) $nick = ' "'.$this->nickname.'"';
		$this->attributes['name'] = $first.$nick.' '.$last;
    }

    /**
     * The membership status of this person
     * @return string
     */
    public function membership() : string {

	    if (!isset($this->ldapPerson->gidnumber)) {
		    return "external";
	    }

        foreach (PersonModel::$groupIds as $status => $id) {
			if ($id == $this->ldapPerson->gidnumber){
				return $status;
			}
        }

        return 'external';
    }

    /**
     * Adds this user to the group belonging to the new membership status
     * and saves the member
     * @param string $membership      the new membership status
     */
    public function setMembership(string $membership)
    {
		if (!array_key_exists($membership, PersonModel::$groupIds)) {
            return;
        }

        $prev = $this->membership();
        if ($membership == $prev) {
            return;
        }

        //Remove from current groups
        foreach (PersonModel::$groupIds as $type => $id) {
		    $group = LdapGroup::fromId($id);
            $group->removeMember($this->uid);
            $group->save();
        }

        //Add to new group
        $group = LdapGroup::fromId(PersonModel::$groupIds[$membership]);
        $group->addMember($this->uid);
        $group->save();

        $this->save();

	    if (!isset($this->ldapPerson->userpassword)) {
		    $this->generatePassword();
	    }

        //Remove objectclasses from previous status
        foreach ($this->additionalClasses[$prev] as $class) {
            if (!in_array($class, $this->ldapPerson->objectclass)) {
                continue;
            }

            $new = array();
            foreach ($this->ldapPerson->objectclass as $prevclass) {
                if ($prevclass != $class) {
                    $new[] = $prevclass;
                }
            }

            $this->ldapPerson->objectclass = $new;
        }

        //Add new objectclasses
        foreach ($this->additionalClasses[$membership] as $class) {
            if (in_array($class, $this->ldapPerson->objectclass)) {
                continue;
            }

            $new = array_merge($this->ldapPerson->objectclass, array($class));
            $this->ldapPerson->objectclass = $new;
        }

		$this->save();

	    $this->ldapPerson->gidnumber = strval(PersonModel::$groupIds[$membership]);

		$this->ldapPerson->save();

		$ldap = LdapHelper::Connect(); //move user to correct OU

	    if(!isset($this->attributes['dn'])) {
			$this->attributes['dn'] = $ldap->getUserDn($this->uid);
	    }

		$ldap->move($this->dn, PersonModel::$personOUnits[$membership] . ',' . $ldap->basedn);
    }

    /**
     * Serializes this Person to JSON
     * @return array
     */
    public function jsonSerialize() : array
    {
        return $this->to_array();
    }

	/**
	 * Returns the user profile picture, or a cat.
	 * @param Response $response
	 * @return string
	 */
    public function getPhoto() : string {
        //get from LDAP
        $photo = $this->ldapPerson->jpegphoto;

        if ( $photo == null ) { //retrieve a cat if person has no jpegPhoto
            $seed = ((int)substr(base_convert(md5($this->uid), 15, 10), -6)) % 500; //per-user seed to generate different cats

            $request = curl_init("https://api.lunoct.nl/avatar/$seed?background=ffffff");
            curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
            $photo = curl_exec($request);
        }

        return base64_encode($photo);
    }

    /**
     * Gets a property of a Person
     * @param  string $name the property to read
     * @return mixed        the value of the property or false
     */
    public function __get(string $name) : mixed
    {
        if (isset($this->attributes[$name])) {
            return $this->attributes[$name];
        }
        return null;
    }

    /**
     * Sets a property of a Person
     * @param string $name  the property to set
     * @param mixed $value  the value to set
     */
    public function __set(string $name, mixed $value)
    {
        if ($name == "membership") {
            $this->setMembership($value);
            return;
        }
        $this->attributes[$name] = $value;
		$this->dirty[$name] = true;
    }
}
