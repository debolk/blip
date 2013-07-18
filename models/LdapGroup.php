<?php
namespace Models;

class LdapGroup extends LdapObject
{
  /**
   * The mappings from membership status to the group they should belong to
   */
  public static $memberGroups = array(
      'lid' => 'cn=leden,ou=groups,o=nieuwedelft,dc=bolkhuis,dc=nl',
      'kandidaatlid' => 'cn=kandidaatleden,ou=groups,o=nieuwedelft,dc=bolkhuis,dc=nl',
      'oudlid' => 'cn=oud-leden,ou=groups,o=nieuwedelft,dc=bolkhuis,dc=nl',
      'lidvanverdienste' => 'cn=ledenvanverdienste,ou=groups,o=nieuwedelft,dc=bolkhuis,dc=nl',
      'geen lid' => 'cn=exleden,ou=groups,o=nieuwedelft,dc=bolkhuis,dc=nl',
  );

  /**
   * Constructs a new Group
   * @param array $attributes
   */
  public function __construct($attributes = array())
  {
    parent::__construct($attributes);
  }

  /**
   * Construct a Person-object from its DN
   * @static
   * @param  string $dn DN to load
   * @return Person     complete Person-object
   */
  public static function fromDn($dn)
  {
    $ldap = \Helper\LdapHelper::connect();

    $attributes = $ldap->flatten($ldap->get($dn, 'posixGroup'));
    $result = new self($attributes);
    $result->exists = true;
    $result->dn = $dn;
    return $result;
  }

  /**
   * Returns the People in an array of groups
   * @param array $groups  An array of DNs to look up
   * @return array         The people in the specified groups
   */
  public static function peopleInGroups($groups)
  {
    $results = array();

    foreach($groups as $group)
      $results = array_merge($results, self::fromDn($group)->people());

    return $results;
  }

  /**
   * Returns the array of people that belong to this group
   * @return array   The array of People in this group
   */
  public function people()
  {
    $result = array();
    if(!isset($this->attributes['memberuid']))
      return $result;
    
    //Convert to array if this is a string 
    if(is_string($this->attributes['memberuid']))
      $this->attributes['memberuid'] = array($this->attributes['memberuid']);

    foreach($this->attributes['memberuid'] as $uid)
    {
      $person = Person::fromUid($uid);
      if($person)
        $result[] = $person;
    }

    return $result;
  }

  /**
   * @param string $uid the uid to look up
   * @return bool       wether the uid belongs to this group
   */
  public function hasMember($uid)
  {
    if(!isset($this->attributes['memberuid']))
      return false;

    if(is_string($this->attributes['memberuid']))
      return $this->attributes['memberuid'] == $uid;

    return in_array($uid, $this->attributes['memberuid']);
  }

  public function addMember($uid)
  {
    if(!isset($this->attributes['memberuid']))
      $this->attributes['memberuid'] = array();
   
    //Convert to array if this is a string 
    if(is_string($this->attributes['memberuid']))
      $this->attributes['memberuid'] = array($this->attributes['memberuid']);

    $this->__set('memberuid', array_merge($this->attributes['memberuid'], array($uid)));
  }

  public function removeMember($uid)
  {
    if(!isset($this->attributes['memberuid']))
      return;

    //Convert to array if this is a string 
    if(is_string($this->attributes['memberuid']))
      $this->attributes['memberuid'] = array($this->attributes['memberuid']);

    if(!in_array($uid, $this->attributes['memberuid']))
      return;

    $new = array();
    foreach($this->attributes['memberuid'] as $name)
      if($name != $uid)
        $new[] = $name;

    $this->__set('memberuid', $new);
  }
}
