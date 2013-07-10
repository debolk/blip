<?php

namespace Models;

class Person implements \JSONSerializable
{
  private $attributes;

  /**
   * Constructs a new Person
   * @param array $attributes
   */
  public function __construct($attributes)
  {
    $this->attributes = $attributes;
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
   * Serializes this Person to JSON
   * @return array
   */
  public function jsonSerialize()
  {
    return array_merge($this->attributes, [
      'href' => getenv('BASE_URL').'persons/'.$this->attributes['uid'],
      'name' => $this->name(),
    ]);
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

    // Add some custom keys
    $input['cn'] = $this->name();
    $input['gecos'] = $this->name();

    // Create and return model
    return new Models\LDAPEntry($input);
  }
}