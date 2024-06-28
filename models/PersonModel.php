<?php

namespace Models;

use Helper\ResponseHelper;
use Slim\Psr7\Response;

class PersonModel implements \JSONSerializable
{
    private array $attributes = array();
    private LdapPerson|null $ldapPerson = null;

    /**
     * The list of properties that can be set by the user
     */
    public array $allowed = array(
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
    );

    /**
     * The mapping from local properties to properties in LdapPerson
     */
    protected array $renaming = array(
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

    protected array $additionalClasses = array(
        'lid' => array('pptpServerAccount', 'gosaIntranetAccount'),
        'oudlid' => array('pptpServerAccount', 'gosaIntranetAccount'),
        'geen lid' => array(),
        'lidvanverdienste' => array(),
        'kandidaatlid' => array('pptpServerAccount', 'gosaIntranetAccount'),
    );

    protected string $pass;

    /**
     * Constructs a new Person
     * @param array $attributes
     */
    public function __construct(array $attributes = array())
    {
        $this->attributes = $attributes;
    }

    /**
     * Creates a new Person from an LdapPerson
     * @param LdapPerson $person    the LdapPerson to create a Person from
     * @returns PersonModel              the resulting Person
     */
    public static function fromLdapPerson(LdapPerson $person) : PersonModel
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
    public static function where(string $query) : array
    {
        $ldap = \Helper\LdapHelper::connect();
        $search = $ldap->search('(&(objectClass=iNetOrgPerson)(!(objectClass=gosaUserTemplate))(!(uid=nobody))' . $query . ')');

        $results = array();
        foreach ($search as $key => $object) {
            if ($key === 'count') {
                continue;
            }
            $person = new LdapPerson($object);
            $results[] = PersonModel::fromLdapPerson($person);
        }

        return $results;
    }

    /**
     * Returns all users from LDAP
     * @static
     * @return array[PersonModel] all persons
     */
    public static function all() : array
    {
        return self::where("");
    }

    /**
     * Saves the current person to ldap, creates a new LdapPerson if needed
     */
    public function save() : bool
    {
        if ($this->ldapPerson == null) {
            $this->attributes['uid'] = $this->findUid();
            $this->ldapPerson = LdapPerson::getDefault();
            $setgroup = true;
        }

        $data = $this->to_array();
        foreach ($data as $key => $value) {
            if (!isset($this->renaming[$key])) {
                continue;
            }

            $ldapkey = $this->renaming[$key];
            $this->ldapPerson->$ldapkey = $value;
        }
        $this->ldapPerson->gidnumber = $this->groupIds[$data['membership']];
        if (isset($this->pass)) {
            $this->ldapPerson->userpassword = $this->pass;
        }

        $result = $this->ldapPerson->save();

        //Set membership after saving
        if (isset($setgroup) && isset($this->attributes['membership'])) {
            $this->setMembership($this->attributes['membership']);
        }

        return $result;
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
     * Returns a JSON serializable representation of this Person of only the basic data
     * @return \stdClass
     */
    public function getBasic() : \stdClass {
        $basic = new \stdClass();
        $basic->uid=$this->__get('uid');
        $basic->href=getenv('BASE_URL').'persons/'.$this->__get('uid');
        $basic->name=$this->name();
        $basic->membership=$this->membership();
        if ($this->__get('avg_email') && $this->__get('avg')) $basic->email=$this->__get('email'); //only send mail if fdMailShare is true
        $basic->avg_email=$this->__get('avg_email');
        $basic->photo_visible=$this->__get('photo_visible');
        return $basic;
    }

    /**
     * Returns the persons information after removing all the information the user doesn't want to share.
     * @return array sanitized array of attributes
     */
    public function sanitizeAvg() : array {
        $avg = array();
        if ( !$this->__get('avg_address') ) $avg[] = 'address';
        if ( !$this->__get('avg_dob')) $avg[] = 'dateofbirth';
        if ( !$this->__get('avg_institution')) $avg[] = 'institution';
        if ( !$this->__get('avg_programme')) $avg[] = 'programme';
        if ( !$this->__get('avg_email')) $avg[] = 'email';
        if ( !$this->__get('avg_phone_parent')) $avg[] = 'phone_parent';
        if ( !$this->__get('avg_phone')) $avg[] = 'phone';
        if ( !$this->__get('avg_pronouns')) $avg[] = 'pronouns';

        if ( !$this->__get('avg')) { //remove all avg attributes if the person didn't accept the privacy statement
            $avg = ['address', 'dateofbirth', 'institution', 'programme', 'email', 'phone_parent', 'phone', 'pronouns'];
        }

        $sanitized = array_diff_key($this->attributes, array_fill_keys($avg, false));

        return array_merge($sanitized, [
            'href' => getenv('BASE_URL').'persons/'.$this->__get('uid'),
            'name' => $this->name(),
            'membership' => $this->membership(),
        ]);
    }

    /**
     * Returns an array-representation of this Person
     * @return array representation of this Person
     */
    public function to_array() : array
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
    public function name() : string
    {
        $first = '';
        $nick = '';
        $last = '';
        if (isset($this->attributes['firstname'])) $first = $this->attributes['firstname'];
        if (isset($this->attributes['surname'])) $last = $this->attributes['surname'];
        if (isset($this->attributes['nickname'])) $nick = ' "'.$this->attributes['nickname'].'"';
        return $first.$nick.' '.$last;
    }

    /**
     * The membership status of this person
     * @return string
     */
    public function membership() : string {

        foreach (LdapGroup::$memberGroups as $status => $dn) {
            $group = LdapGroup::fromDn($dn);
            if ($group->hasMember($this->uid)) {
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
    public function setMembership(string $membership)
    {
        if (!array_key_exists($membership, LdapGroup::$memberGroups)) {
            return;
        }

        $prev = $this->membership();
        if ($membership == $prev) {
            return;
        }

        //Remove from current groups
        foreach (LdapGroup::$memberGroups as $type => $dn) {
            $group = LdapGroup::fromDn($dn);
            $group->removeMember($this->attributes['uid']);
            $group->save();
        }

        //Add to new group
        $group = LdapGroup::fromDn(LdapGroup::$memberGroups[$membership]);
        $group->addMember($this->attributes['uid']);
        $group->save();

        $this->save();

        if (in_array($membership, array('lid', 'kandidaatlid'))) {
            if (!isset($this->ldapPerson->userpassword)) {
                $this->generatePassword();
            }
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
     * @param $width    width of the picture
     * @param $height   height of the picture
     * @return Response
     */
    public function getPhoto(Response $response, string $width = "256", string $height = "256") : Response {
        //cast params
        $width = (int)$width;
        $height = (int)$height;

        //get from LDAP
        $photo = $this->ldapPerson->__get('jpegPhoto');
        if ( $photo == null ) { //retrieve a cat if person has no jpegPhoto
            $seed = ((int)substr(base_convert(md5($uid), 15, 10), -6)) % 500; //per-user seed to generate different cats

            $request = curl_init("https://api.lunoct.nl/avatar/$seed?background=ffffff");
            curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
            $photo = curl_exec($request);
        }

        //process for displaying
        $img = new \Imagick();
        $img->readImageBlob($photo);
        $img->setImageInterpolateMethod(\Imagick::INTERPOLATE_BICUBIC);

        //scale to best fit
        if ( $img->getImageWidth() > $img->getImageHeight() ){
            $img->resizeImage($width, 0, \Imagick::FILTER_CATROM, 1);
        } else {
            $img->resizeImage(0, $height, \Imagick::FILTER_CATROM, 1);
        }
        $img->setImageFormat('jpg');

        return ResponseHelper::data($response, base64_encode($img), 'image/jpeg');
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
        return false;
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
        $dirty[$name] = true;
    }
}
