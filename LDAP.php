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
    $this->server = ldap_connect(getenv('LDAP_HOST'));
    ldap_bind($this->server, getenv('LDAP_USERNAME'), getenv('LDAP_PASSWORD'));
  }

  /**
   * Find the information of every member
   * @return array[Models\Person]
   */
  public function find_all()
  {
    $result = $this->ldap_find('(&(objectClass=iNetOrgPerson)(!(objectClass=gosaUserTemplate)))', array('uid', 'givenname', 'sn', 'mail'));
    return array_map(array($this, 'to_resource'), $result);
  }

  /**
   * Finds all current, past and candidate members
   * @return array[Models\Person]
   */
  public function find_all_members()
  {
    // Rest of the method not yet implemented
    throw new Exception('Method not implemented');
  }

  /**
   * Finds all current members
   * @return array[Models\Person]
   */
  public function find_current_members()
  {
    // Rest of the method not yet implemented
    throw new Exception('Method not implemented');
  }

  /**
   * Finds all candidate members
   * @return array[Models\Person]
   */
  public function find_candidate_members()
  {
    // Rest of the method not yet implemented
    throw new Exception('Method not implemented');
  }

  /**
   * Finds all past members
   * @return array[Models\Person]
   */
  public function find_past_members()
  {
    // Rest of the method not yet implemented
    throw new Exception('Method not implemented');
  }

  /**
   * Find the information of a specific member
   * @param int $id the id of the member to find
   * @throws LDAPNotFoundException if the member doesn't exist
   * @return Models\Person
   */
  public function find($id)
  {
    // Retrieve results
    $search = ldap_search($this->server, getenv('LDAP_BASEDN'), "(uid=$id)", array('uid', 'givenname', 'sn', 'mail'));
    $result = ldap_get_entries($this->server, $search);

    // Remove the first, useless entry
    array_shift($result);

    // Person doesn't exist
    if (empty($result)) {
      return null;
    }

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
   * Converts a LDAP-entry to a resource
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
   * Converts a resource to a LDAP-entry
   * @param array $resource the resource to convert
   * @returns array the resulting LDAP-entry
   */
  private function from_resource($resource)
  {
    throw new Exception('Method not implemented');
  }

  /**
   * Performs a LDAP search
   * @param string $query the query to search
   * @param array[string] $attributes attributes to include in the result set
   * @return LDAP result set
   */
  private function ldap_find($query, $attributes = array())
  {
    // Retrieve results
    if (sizeof($attributes) > 0) {
      $search = ldap_search($this->server, getenv('LDAP_BASEDN'), $query, array('uid', 'givenname', 'sn', 'mail'));
    }
    else {
      $search = ldap_search($this->server, getenv('LDAP_BASEDN'), $query);
    }
    $result = ldap_get_entries($this->server, $search);

    // Remove the first, useless entry
    array_shift($result);
    return $result;
  }
}
