<?php

namespace Models;

class LDAPEntry
{
  private $attributes = array();

  /**
   * Converts a LDAP-resultset to a LDAPEntry-instance
   * @class  array $entry
   * @return Models\LDAPEntry
   */
  public static function from_result($entry)
  {
    $keys = ['uid', 'givenname', 'sn', 'mail', 'telephonenumber', 'mobile', 'homephone', 'homepostaladdress'];
    foreach ($keys as $key) {
      if (isset($entry[$key][0])) {
        $parameters[$key] = $entry[$key][0];
      }
    }

    // Format parameters
    return new LDAPEntry($parameters);
  }

  /**
   * Constructs a new LDAP-entry
   */
  public function __construct($attributes)
  {
    $this->attributes = $attributes;
  }

  /**
   * Converts this LDAP-entry to a Person-model
   * @return Models\Person
   */
  public function to_Person()
  {
    // Create correct properties
    $input = [];

    if (isset($this->attributes['uid'])) {
      $input['uid'] = $this->attributes['uid'];
    }
    if (isset($this->attributes['givenname'])) {
      $input['firstname'] = $this->attributes['givenname'];
    }
    if (isset($this->attributes['sn'])) {
      $input['lastname'] = $this->attributes['sn'];
    }
    if (isset($this->attributes['mail'])) {
      $input['email'] = $this->attributes['mail'];
    }
    if (isset($this->attributes['telephonenumber'])) {
      $input['phone'] = $this->attributes['telephonenumber'];
    }
    if (isset($this->attributes['mobile'])) {
      $input['mobile'] = $this->attributes['mobile'];
    }
    if (isset($this->attributes['homephone'])) {
      $input['phone_parents'] = $this->attributes['homephone'];
    }
    if (isset($this->attributes['homepostaladdress'])) {
      $input['address'] = $this->attributes['homepostaladdress'];
    }
    if (isset($this->attributes['initials'])) {
      $input['initials'] = $this->attributes['initials'];
    }

    // Create and return model
    return new Person($input);
  }

  /**
   * Cast to array
   * @return array
   */
  public function to_array()
  {
    return $this->attributes;
  }
}
