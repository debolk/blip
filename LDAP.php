<?php

require_once('models/Person.php');

class LDAP
{
  /**
   * The connection to the Bolk LDAP-server
   */
  private $server;

  /**
   * Construct a connection to the server
   */
  public function __construct()
  {
    $this->server = ldap_connect('ldap.i.bolkhuis.nl');
  }

  /**
   * Find the information of every member
   * @return array[object]
   */
  public function find_all()
  {
    // Retrieve results
    $search = ldap_search($this->server, 'dc=bolkhuis,dc=nl', '(&(objectClass=iNetOrgPerson)(!(objectClass=gosaUserTemplate)))', array('uid', 'givenname', 'sn', 'mail'));
    $result = ldap_get_entries($this->server, $search);

    // Remove the first, useless entry
    array_shift($result);

    // Convert to resource objects
    return array_map(array($this, 'to_resource'), $result);
  }

  /**
   * Find the information of a specific member
   * @param int $id the id of the member to find
   * @throws LDAPNotFoundException if the member doesn't exist
   * @return object
   */
  public function find($id)
  {
    // Retrieve results
    $search = ldap_search($this->server, 'dc=bolkhuis,dc=nl', "(uid=$id)", array('uid', 'givenname', 'sn', 'mail'));
    $result = ldap_get_entries($this->server, $search);

    // Remove the first, useless entry
    array_shift($result);

    // Return a resource object
    return $this->to_resource($result[0]);
  }

  /**
   * Create a new member with the given data
   * @param array $data containing the keys name, email and status. Status can be any string of: lid, kandidaat-lid, oud-lid or ex-lid.
   * @return the results of LDAP::find() of the new member
   */
  public function create($data)
  {
    // Guard against invalid input
    if (!isset($data['name'], $data['email'], $data['status'])) {
      throw new LDAPInvalidUserException('Not all required fields are present');
    }

    // Rest of the method not yet implemented
    throw new Exception('Method not implemented');
  }

  /**
   * Update an existing member with the given data
   * @param array $data
   * @return the results of LDAP::find() of the member
   * @throws LDAPNotFoundException if the member doesn't exist
   */
  public function update($data)
  {
    throw new Exception('Method not implemented');
  }

  /**
   * Converts an LDAP-entry to an resource
   * @param array $entry the LDAP-entry to convert
   * @returns Models\Person the resulting resource
   */
  private function to_resource($entry)
  {
    $person = new Models\Person;
    $person->id = isset($entry['uid'][0]) ? ($entry['uid'][0]) : (null);
    $person->first_name = isset($entry['givenname'][0]) ? ($entry['givenname'][0]) : (null);
    $person->last_name = isset($entry['sn'][0]) ? ($entry['sn'][0]) : (null);
    $person->email = isset($entry['mail'][0]) ? ($entry['mail'][0]) : (null);
    return $person;
  }

  /**
   * Converts an LDAP-entry to an resource
   * @param array $entry the LDAP-entry to convert
   * @returns Models\Person the resulting resource
   */
  private function from_resource($entry)
  {

  }
}

/**
 * Exceptions used in the class
 */
class LDAPInvalidUserException extends Exception {}
