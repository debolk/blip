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
    'mobile',
    'phone_parents',
    'address',
    'dateofbirth',
    'gender',
    'membership',
  );
  
  /**
   * The mapping from local properties to properties in LdapPerson
   */
  protected $renaming = array(
    'uid' => 'uid',
		'initials' => 'initials',
    'firstname' => 'givenname',
    'lastname' => 'sn',
    'email' => 'mail',
    'phone' => 'telephonenumber',
    'mobile' => 'mobile',
    'phone_parents' => 'homephone', 
    'address' => 'homepostaladdress',
		'dateofbirth' => 'dateofbirth',
    'gender' => 'gender',
		'initials' => 'initials',
  );

  protected $additionalClasses = array(
    'lid' => array('pptpServerAccount', 'gosaIntranetAccount'),
    'oudlid' => array('pptpServerAccount', 'gosaIntranetAccount'),
    'geen lid' => array(),
    'lid van verdienste' => array(),
    'kandidaatlid' => array('pptpServerAccount', 'gosaIntranetAccount'),
  );

  /**
   * The mapping from membership status to guidnumber
   */
  protected $groupIds = array(
    'lid' => 1025,
    'kandidaatlid' => 1084,
    'oudlid' => 1095,
    'oudlid' => 1098,
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
    foreach($result->renaming as $local => $ldap)
      if(isset($person->$ldap))
        $result->attributes[$local] = $person->$ldap;

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
    if(!$person)
      throw new \Exception('User not found!');

    if(in_array('gosaUserTemplate', $person->objectclass))
      return false;

    return self::fromLdapPerson($person);
  }

  /**
   * Searches for persons with a given ldap query
   * @param string $query     the ldap query
   * @returns array           the persons matching the query
   */
  public static function where($query)
  {
    $ldap = \Helper\LdapHelper::connect();
    $search = $ldap->search('(&(objectClass=iNetOrgPerson)(!(objectClass=gosaUserTemplate))(!(uid=nobody))' . $query . ')');
    
    $results = array();
    foreach($search as $object)
    {
      $person = new LdapPerson($ldap->flatten($object));
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
    if($this->ldapPerson == null)
    {
      $this->attributes['uid'] = $this->findUid();
      $this->ldapPerson = LdapPerson::getDefault();
    }

    $data = $this->to_array();
    foreach($data as $key => $value)
    {
      if(!isset($this->renaming[$key]))
        continue;

      $ldapkey = $this->renaming[$key];
      $this->ldapPerson->$ldapkey = $value;
    }
    $this->ldapPerson->gidnumber = $this->groupIds[$data['membership']];
    if(isset($this->pass))
      $this->ldapPerson->userpassword = $this->pass;

    $this->ldapPerson->save();
  }

  /**
   * Finds an unused uid for a new user
   * @returns string          an unused uid
   */
  protected function findUid()
  {
    $ldap = \Helper\LdapHelper::connect();

    // Try sensible options first
    $options = [];

    if (! empty($this->attributes['initials'])) {
      $options[] = str_replace(' ', '', strtolower($this->attributes['initials'][0].$this->attributes['lastname']));
      $options[] = str_replace(' ', '', strtolower($this->attributes['initials'].$this->attributes['lastname']));
    }
    $options[] = str_replace(' ', '', strtolower($this->attributes['firstname'].$this->attributes['lastname']));

    foreach ($options as $candidate_uid) {
      if (! $ldap->getDn($candidate_uid)) {
        return $candidate_uid;
      }
    }

    // Try a numbered option
    for ($i=1; true; $i++) { 
      $candidate_uid = str_replace(' ', '', strtolower($this->attributes['firstname'].$this->attributes['lastname']).$i);
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
      'href' => getenv('BASE_URL').'persons/'.$this->attributes['uid'],
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
      }
      else {
        return $this->attributes['firstname'];
      }
    }
    else {
      if (isset($this->attributes['lastname'])) {
        return $this->attributes['lastname'];
      }
      else {
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
    $groups = array(
      'lid' => 'cn=leden,ou=groups,o=nieuwedelft,dc=bolkhuis,dc=nl',
      'kandidaatlid' => 'cn=kandidaatleden,ou=groups,o=nieuwedelft,dc=bolkhuis,dc=nl',
      'oudlid' => 'cn=oud-leden,ou=groups,o=nieuwedelft,dc=bolkhuis,dc=nl',
    );

    foreach($groups as $status => $dn)
    {
      $group = LdapGroup::fromDn($dn);
      if($group->hasMember($this->attributes['uid']))
        return $status;
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
    if(!array_key_exists($membership, LdapGroup::$memberGroups))
      return;

    $prev = $this->membership();
    if($membership == $prev)
      return;

    //Remove from current groups
    foreach(LdapGroup::$memberGroups as $type => $dn)
    {
      $group = LdapGroup::fromDn($dn);
      $group->removeMember($this->attributes['uid']);
      $group->save();
    }
    
    //Add to new group
    $group = LdapGroup::fromDn(LdapGroup::$memberGroups[$membership]);
    $group->addMember($this->attributes['uid']);
    $group->save();

    $this->save();

    if(in_array($membership, array('lid', 'kandidaatlid')))
      if(!isset($this->ldapPerson->userpassword))
        $this->generatePassword();

    //Remove objectclasses from previous status
    foreach($this->additionalClasses[$prev] as $class)
    {
      if(!in_array($class, $this->ldapPerson->objectclass))
        continue;

      $new = array();
      foreach($this->ldapPerson->objectclass as $prevclass)
        if($prevclass != $class)
          $new[] = $prevclass;

      $this->ldapPerson->objectclass = $new;
    }

    //Add new objectclasses
    foreach($this->additionalClasses[$membership] as $class)
    {
      if(in_array($class, $this->ldapPerson->objectclass))
        continue;

      $new = array_merge($this->ldapPerson->objectclass, array($class));
      $this->ldapPerson->objectclass = $new;
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
    if($name == "membership")
    {
      $this->setMembership($value);
      return;
    }
    $this->attributes[$name] = $value;
    $dirty[$name] = true;
  }
}
