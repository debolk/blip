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
    $members = $this->ldap_group_members('cn=leden,ou=groups,o=nieuwedelft');
    $members = array_merge($members, $this->ldap_group_members('cn=kandidaatleden,ou=groups,o=nieuwedelft'));
    $members = array_merge($members, $this->ldap_group_members('cn=oud-leden,ou=groups,o=nieuwedelft'));
    return $members;
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
    return $this->ldap_group_members('cn=kandidaatleden,ou=groups,o=nieuwedelft');
  }

  /**
   * Finds all past members
   * @return array[Models\Person]
   */
  public function find_past_members()
  {
    return $this->ldap_group_members('cn=oud-leden,ou=groups,o=nieuwedelft');
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
    // Find a free UID
    $uid = (string)$this->find_free_uid($data);

    // Calculate UID-number
    $uid_number = (string)$this->get_new_uid_number();

    // Construct full name
    if (isset($data->lastname_prefix)) {
      $name = implode(' ', array_filter([$data->firstname, $data->lastname_prefix, $data->lastname]));
    }
    else {
      $name = implode(' ', array_filter([$data->firstname, $data->lastname]));
    }

    // Build complete input array
    $input = [
      'uid' => $uid,
      'cn' => $name,
      'gecos' => $name,
      'givenname' => $data->firstname,
      'sn' => $data->lastname,
      'mail' => $data->email,
      'objectClass' => ['top', 'person', 'organizationalPerson', 'iNetOrgPerson','gosaAccount','posixAccount','shadowAccount','sambaSamAccount','sambaIdmapEntry','pptpServerAccount','gosaMailAccount','gosaIntranetAccount'],
      'gosamaildeliverymode' => '[L]',
      'gosamailserver' => 'mail',
      'gosaspammailbox' => 'INBOX',
      'gosaspamsortlevel' => '0',
      'gotolastsystemlogin' => '01.01.1970 00:00:00',
      'loginshell' => '/bin/bash',
      'sambaacctflags' => '[U           ]',
      'sambadomainname' => 'nieuwedelft',
      'sambahomedrive' => 'Z:',
      'sambahomepath' => '\\\samba\commissies',
      'sambalogofftime' => '2147483647',
      'sambalogontime' => '0',
      'sambapwdlastset' => '0',
      'sambamungeddial' => 'IAAgACAAIAAgACAAIAAgACAAIAAgACAAIAAgACAAIAAgACAAIAAgACAAIAAgACAAIAAgACAAIAAgACAAIAAgACAAIAAgACAAIAAgACAAIAAgACAAIAAgACAAIAAgACAAUAAQABoACAABAEMAdAB4AEMAZgBnAFAAcgBlAHMAZQBuAHQANTUxZTBiYjAYAAgAAQBDAHQAeABDAGYAZwBGAGwAYQBnAHMAMQAwMDAwMDEwMBYAAAABAEMAdAB4AEMAYQBsAGwAYgBhAGMAawASAAgAAQBDAHQAeABTAGgAYQBkAG8AdwAwMTAwMDAwMCIAAAABAEMAdAB4AEsAZQB5AGIAbwBhAHIAZABMAGEAeQBvAHUAdAAqAAIAAQBDAHQAeABNAGkAbgBFAG4AYwByAHkAcAB0AGkAbwBuAEwAZQB2AGUAbAAwMCAAAgABAEMAdAB4AFcAbwByAGsARABpAHIAZQBjAHQAbwByAHkAMDAgAAIAAQBDAHQAeABOAFcATABvAGcAbwBuAFMAZQByAHYAZQByADAwGAACAAEAQwB0AHgAVwBGAEgAbwBtAGUARABpAHIAMDAiAAIAAQBDAHQAeABXAEYASABvAG0AZQBEAGkAcgBEAHIAaQB2AGUAMDAgAAIAAQBDAHQAeABXAEYAUAByAG8AZgBpAGwAZQBQAGEAdABoADAwIgACAAEAQwB0AHgASQBuAGkAdABpAGEAbABQAHIAbwBnAHIAYQBtADAwIgACAAEAQwB0AHgAQwBhAGwAbABiAGEAYwBrAE4AdQBtAGIAZQByADAwKAAIAAEAQwB0AHgATQBhAHgAQwBvAG4AbgBlAGMAdABpAG8AbgBUAGkAbQBlADAwMDAwMDAwLgAIAAEAQwB0AHgATQBhAHgARABpAHMAYwBvAG4AbgBlAGMAdABpAG8AbgBUAGkAbQBlADAwMDAwMDAwHAAIAAEAQwB0AHgATQBhAHgASQBkAGwAZQBUAGkAbQBlADAwMDAwMDAw',
      'userpassword' => uniqid(),
      'homedirectory' => "/home/$uid",
      'uidnumber' => $uid_number,
      'sambasid' => 'S-1-5-21-1816619821-1419577557-1603852640-'.(1000+2*$uid_number),
      'gidNumber' => '1084',
      'sambaprimarygroupsid' => 'S-1-5-21-1816619821-1419577557-1603852640-3051',
    ];

    // Create LDAP-entry
    $success = ldap_add($this->server, "uid=$uid,ou=people,o=nieuwedelft,dc=bolkhuis,dc=nl", $input);

    // Return the new user
    return $this->find($uid);
  }

  /**
   * Returns a free uid for a new user
   * @param $data object describing the user
   * @return string the free UID
   * @throws Exception if no free UID can be found
   */
  private function find_free_uid($data)
  {
    $uid = null;
    $options = array(
      strtolower($data->initials[0].$data->lastname),
      strtolower($data->initials.$data->lastname),
      strtolower($data->firstname.$data->lastname),
    );
    if (isset($data->lastname_prefix)) {
      $options[] = strtolower($data->initials.$data->lastname_prefix.$data->lastname);
      $options[] = strtolower($data->firstname.$data->lastname_prefix.$data->lastname);
    }
    foreach ($options as $candidate_uid) {
      if (! $this->user_exists($candidate_uid)) {
        $uid = $candidate_uid;
        break;
      }
    }
    if ($uid == null) {
      throw new Exception('Cannot create user: All UIDs taken');
    }
    return $uid;
  }

  /**
   * Returns a new, unused uidNumber fromLDAP
   * @return int
   */
  private function get_new_uid_number()
  {
    // Find all existing entries with a uidNumber
    $search = $this->ldap_find('(objectClass=posixAccount)', array('uidNumber'));

    // Slap array until it's formatted
    $numbers = array_map(function($e){
      return (int)$e['uidnumber'][0];
    }, $search);

    // Find the first free number
    for ($i=1001; $i < 65000; $i++) { 
      if (! in_array($i, $numbers)) {
        return $i;
      }
    }
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

    // Group does not exist
    if (!isset($entries[0])) {
      throw new Exception('Group doesn\'t exist');
    }

    // Group has no members
    if (!isset($entries[0]['memberuid'])) {
      return array();
    }

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

  /**
   * Returns whether a user with a given uid exists
   * @param string $uid the UID of the user to find
   * @return boolean whether the user exists
   */
  private function user_exists($uid)
  {
    $search = ldap_search($this->server, getenv('LDAP_BASEDN'), "(uid=$uid)", array());
    return (ldap_count_entries($this->server, $search) > 0);
  }
}
