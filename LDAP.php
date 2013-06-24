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
    $result = $this->server_find('(&(objectClass=iNetOrgPerson)(!(objectClass=gosaUserTemplate)))', array('uid', 'givenname', 'sn', 'mail'));
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
    return $this->ldap_group_members('cn=leden,ou=groups,o=nieuwedelft');
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
   * @param string $dn_prefix an extra part to prefix for the base_dn as defined in the configuration
   * @return LDAP result set
   */
  private function ldap_find($query, $attributes = null, $dn_prefix = '')
  {
    // Prefix the dn to search to enable extension
    if ($dn_prefix !== '') {
      $dn_prefix = ','.$dn_prefix;
    }
    $dn = $dn_prefix.getenv('LDAP_BASEDN');

    // Retrieve results
    $search = ldap_search($this->server, $dn, $query, $attributes);
    $result = ldap_get_entries($this->server, $search);

    // Remove the first, useless entry
    array_shift($result);
    return $result;
  }

  /**
   * Returns all members of a specific group
   * @param $dn the DN of the group to find (excluding BASE_DN)
   * @return array[Person]
   */
  private function ldap_group_members($dn)
  {
    // Find the group
    $search = ldap_search($this->server, $dn.','.getenv('LDAP_BASEDN'), '(objectClass=PosixGroup)', array('memberuid'));
    $entries = ldap_get_entries($this->server, $search);

    // Locate the member uids
    $results = $entries[0]['memberuid'];

    // Ignore the first, useless count entry
    array_shift($results);

    // Find actual user objects
    $members = array();
    foreach ($results as $uid) {
      $member = $this->find_member($uid);
      if ($member !== null) {
        array_push($members, $member);
      }
    }

    return $members;
  }

  /**
   * Find a member in LDAP and returns its corresponding model
   * @param string $uid the UID of the user to find
   * @return Models\Person
   */
  private function find_member($uid)
  {
    $search = $this->ldap_find("(&(uid=$uid)(objectClass=iNetOrgPerson)(!(objectClass=gosaUserTemplate)))", array('uid', 'givenname', 'sn', 'mail'));
    // No results
    if ($search == array()) {
      return null;
    }
    else {
      return $this->to_resource($search[0]);
    }
  }
}
