<?php
namespace Models;

class Group
{
  protected $attributes;

  /**
   * Constructs a new Group
   * @param array $attributes
   */
  public function __construct($attributes)
  {
    $this->attributes = $attributes;
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
    return new Group($attributes);
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
      $results = array_merge($results, Group::fromDn($group)->people());

    return $results;
  }

  /**
   * Gets a property of a Group
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
   * Returns the array of people that belong to this group
   * @return array   The array of People in this group
   */
  public function people()
  {
    $result = array();
    if(!isset($this->attributes['memberuid']))
      return $result;

    foreach($this->attributes['memberuid'] as $uid)
      $result[] = Person::fromUid($uid);

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

    return in_array($uid, $this->attributes['memberuid']);
  }

  /**
   * Sets a property of a Group
   * @param string $name  the property to set
   * @param mixed $value  the value to set
   */
  public function __set($name, $value)
  {
    $this->attributes[$name] = $value;
    $dirty[$name] = true;
  }
}
