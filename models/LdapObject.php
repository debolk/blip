<?php

namespace Models;

use Helper\LdapHelper;

/**
 * Handles creation ldap objects
 */
class LdapObject
{
    protected bool $exists = false;
    protected array $attributes = array();
    protected $dirty = array();
    public ?string $dn = null;

    /**
     * Creates a new LdapObject
     * @param array $attributes  The attributes to set
     */
    public function __construct(array $attributes = array())
    {
		$this->attributes = $attributes;
    }

	/**
	 * Creates an LdapObject from a dn
	 * @param string $dn the DN to look up
	 * @return LdapObject|null the specified entry under the dn or null
	 */
    public static function fromDn(string $dn) : LdapObject | null
    {
        $ldap = LdapHelper::Connect();

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
    public function __get(string $name) : mixed
    {
        if (!isset($this->attributes[$name])) {
            return null;
        }

		if (!is_array($this->attributes[$name])) {
            return $this->attributes[$name];
        }

        if (!isset($this->attributes[$name]['count'])) {
            return $this->attributes[$name];
        }

        if ($this->attributes[$name]['count'] == 1) {
            return $this->attributes[$name][0];
        }

        $result = array();
        foreach ($this->attributes[$name] as $key => $value) {
            $result[$key] = $value;
        }
        unset($result['count']);

        return $result;
    }

    /**
     * Determines if a property has been set
     * @param string $name  the property to look up
     * @return bool         wether the property exists
     */
    public function __isset(string $name) : bool
    {
        return isset($this->attributes[$name]);
    }

    /**
     * Sets a property of a LdapObject
     * @param string $name  the property to set
     * @param mixed $value  the value to set
     */
    public function __set(string $name, mixed $value)
    {
		if (!isset($this->attributes[$name]) || $this->attributes[$name] != $value) {
            $this->dirty[$name] = true;
        }

        if (empty($value)) {
            $this->attributes[$name] = array();
        } else {
            $this->attributes[$name] = $value;
        }
    }

    /**
     * Saves the LdapObject to Ldap
     */
    public function save() : bool
    {
        if (count($this->dirty) == 0) {
            return true;
        }

	    $ldap = \Helper\LdapHelper::connect();

	    if (!$this->exists) {
            $result = $ldap->add($this->dn, $this->attributes);
			$this->exists = $result;
        } else {
            $diff = array();

            foreach ($this->dirty as $key => $value) {
				$value = $this->$key;

	            if (is_bool($value)){
		            if ($value) {
			            $value = "TRUE";
		            } else {
			            $value = "FALSE";
		            }
	            }
                $diff[$key] = $value;

            }
            $result = $ldap->modify($this->dn, $diff);
        }

        $this->dirty = array();

        return $result;
    }
}
