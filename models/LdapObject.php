<?php

namespace Models;

/**
 * Handles creation ldap objects
 */
class LdapObject {

  protected $exists = false;
  protected $attributes = array();
  protected $dirty = array();
  public $dn = null;

  /**
   * Creates a new LdapObject
   * @param array $attributes  The attributes to set
   */
  public function __construct($attributes = array())
  {
    $this->attributes = $attributes;
  }

  /**
   * Creates an LdapObject from a dn
   * @param  string $dn   the DN to look up
   * @return LdapEntry    the specified entry under the dn
   */
  public static function fromDn($dn)
  {
    $ldap = \Helper\LdapHelper::connect();

    $attributes = $ldap->get($dn);
    $attributes = $ldap->flatten($attributes);

    $result = new self($attributes);
    $result->exists = true;
    $result->dn = $dn;
    return $result;
  }

  /**
   * Gets a property of a LdapObject
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
   * Determines if a property has been set
   * @param string $name  the property to look up
   * @return bool         wether the property exists
   */
  public function __isset($name)
  {
    return isset($this->attributes[$name]);
  }

  /**
   * Sets a property of a LdapObject
   * @param string $name  the property to set
   * @param mixed $value  the value to set
   */
  public function __set($name, $value)
  {
    if(!isset($this->attributes[$name]) || $this->attributes[$name] != $value)
      $this->dirty[$name] = true;

    $this->attributes[$name] = $value;
  }

  /**
   * Saves the LdapObject to Ldap
   */
  public function save()
  {
    if(count($this->dirty) == 0)
      return;

    $ldap = \Helper\LdapHelper::connect();

    if(!$this->exists)
    {
      $this->exists = true;
      $ldap->add($this->dn, $this->attributes);
    } else {
      $diff = array();

      foreach($this->dirty as $key => $value)
        $diff[$key] = $this->attributes[$key];

      $ldap->modify($this->dn, $diff);
    }

    $this->dirty = array();
  }
}