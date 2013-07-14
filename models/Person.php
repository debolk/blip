<?php

namespace Models;

require_once('Group.php');

class Person implements \JSONSerializable
{
  private $attributes = array();
  private $ldapPerson = null;

  public $allowed = array(
    'initials',
    'fistname',
    'lastname',
    'email',
    'phone',
    'mobile',
    'phone_parents',
    'address',
    'dateofbirth'
  );
  
  protected $renaming = array(
    'uid' => 'uid',
    'firstname' => 'givenname',
    'lastname' => 'sn',
    'email' => 'mail',
    'mobile' => 'mobile',
    'phone' => 'telephonenumber',
    'phone_parents' => 'homephone', 
    'address' => 'homepostaladdress',
  );

  protected $groupIds = array(
    'lid' => 1025,
    'kandidaatlid' => 1084,
    'oud-leden' => 1095,
    'geen lid' => 1097,
  );

  protected $dirty = array();

  /**
   * Constructs a new Person
   * @param array $attributes
   */
  public function __construct($attributes = array())
  {
    $this->attributes = $attributes;
  }

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

    return self::fromLdapPerson($person);
  }

  public static function where($query)
  {
    $ldap = \Helper\LdapHelper::connect();
    $search = $ldap->search('(&(objectClass=iNetOrgPerson)(!(objectClass=gosaUserTemplate))(!(uid=nobody))' . $query . ')');
    
    $results = array();
    foreach($search as $object)
      $results[] = new Person($ldap->flatten($object));

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

  public function save()
  {
    if($this->ldapPerson == null)
    {
      $this->attributes['uid'] = $this->findUid();
      $this->ldapPerson = LdapPerson::fromPerson($this);
    } else {
      $this->ldapPerson->mergePerson($this);
    }

    $this->ldapPerson->save();
  }

  protected function findUid()
  {
    $ldap = \Helper\LdapHelper::connect();

    // Try sensible options first
    $options = [];

    if (! empty($this->attributes['initials'])) {
      $options[] = strtolower($this->attributes['initials'][0].$this->attributes['lastname']);
      $options[] = strtolower($this->attributes['initials'].$this->attributes['lastname']);
    }
    $options[] = strtolower($this->attributes['firstname'].$this->attributes['lastname']);

    foreach ($options as $candidate_uid) {
      if (! $ldap->getDn($candidate_uid)) {
        return $candidate_uid;
      }
    }

    // Try a numbered option
    for ($i=1; true; $i++) { 
      $candidate_uid = strtolower($this->attributes['firstname'].$this->attributes['lastname']).$i;
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
      $group = Group::fromDn($dn);
      if($group->hasMember($this->attributes['uid']))
        return $status;
    }
    
    return 'geen lid';
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
   * Converts this Person-model to a LDAP-entry
   * @return Models\LDAPEntry
   */
  public function to_LDAPEntry()
  {
    $input = $this->attributes;

    // Rename keys
    $renaming = ['firstname' => 'givenname', 'lastname' => 'sn', 'email' => 'mail',
                 'phone' => 'telephonenumber', 'phone_parents' => 'homephone', 
                 'address' => 'homepostaladdress'];
    foreach(array_keys($input) as $key) {
      if (isset($renaming[$key])) {
        $input[$renaming[$key]] = $input[$key];
        unset($input[$key]);
      }
    }

    // Create and return model
    return new LDAPEntry($input);
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
    $this->attributes[$name] = $value;
    $dirty[$name] = true;
  }
}
