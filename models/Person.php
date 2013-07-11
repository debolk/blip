<?php

namespace Models;

require_once('Group.php');

class Person implements \JSONSerializable
{
  private $attributes;
  
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

  protected $dirty = array();

  /**
   * Constructs a new Person
   * @param array $attributes
   */
  public function __construct($attributes)
  {
    $this->attributes = array();

    foreach($this->renaming as $local => $ldap)
      if(isset($attributes[$ldap]))
        $this->attributes[$local] = $attributes[$ldap];
  }

  public static function fromUid($uid)
  {
    $ldap = \Helper\LdapHelper::connect();
    $dn = $ldap->getDn($uid);

    if(!$dn)
      throw new \Exception('User not found!');

    $attributes = $ldap->flatten($ldap->get($dn, 'iNetOrgPerson'));
    return new Person($attributes);
  }

  public static function all()
  {
    $ldap = \Helper\LdapHelper::connect();

    $query = $ldap->search('(&(objectClass=iNetOrgPerson)(!(objectClass=gosaUserTemplate))(!(uid=nobody)))');

    $results = array();
    foreach($query as $object)
      $results[] = new Person($ldap->flatten($object));

    return $results;
  }

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
