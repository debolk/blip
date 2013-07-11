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

  public static function fromDn($dn)
  {
    $ldap = \Helper\LdapHelper::connect();

    $attributes = $ldap->flatten($ldap->get($dn, 'posixGroup'));
    return new Group($attributes);
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
