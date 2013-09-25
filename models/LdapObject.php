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
    #$attributes = $ldap->flatten($attributes);

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
    if (!isset($this->attributes[$name]))
      return;

		return strip($this->attributes[$name]);
  }

	/**
	 * Strips the count entry from a value
	 * @param mixed $val		the value to strip from
	 * @return mixed				the stripped result
	 */
	protected function strip($val)
	{
		if(!is_array($val))
			return $val;

		if(!isset($val['count']))
			return $val;

		if($val['count'] == 1)
			return $val[0];

		$result = array();
		foreach($val as $key => $value)
			$result[$key] = $value;
		unset($result['count']);

		return $result;
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
		
		if(empty($value))
			$this->attributes[$name] = array();
		else
	    $this->attributes[$name] = $value;
  }

  /**
   * Saves the LdapObject to Ldap
   */
  public function save()
  {
    if(count($this->dirty) == 0)
      return true;

    $ldap = \Helper\LdapHelper::connect();

    if(!$this->exists)
    {
      $this->exists = true;
      $result = $ldap->add($this->dn, $this->attributes);
    } else {
      $diff = array();

      foreach($this->dirty as $key => $value)
        $diff[$key] = $this->__get($key);

      $result = $ldap->modify($this->dn, $diff);
    }

    $this->dirty = array();

		return $result;
  }
}
