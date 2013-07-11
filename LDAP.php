<?php

require_once('models/Person.php');
require_once('models/LDAPEntry.php');
require_once('mailer/NewPerson.php');

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
    $result = $this->ldap_find('(&(objectClass=iNetOrgPerson)(!(objectClass=gosaUserTemplate))(!(uid=nobody)))', array());
    return array_map(function($entry){
      return Models\LDAPEntry::from_result($entry)->to_Person();
    }, $result);
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
   * @param int $uid the id of the member to find
   * @throws LDAPNotFoundException if the member doesn't exist
   * @return Models\Person or null if the person does not exist
   */
  public function find($uid)
  {
    // Retrieve results
    $search = ldap_search($this->server, getenv('LDAP_BASEDN'), "(uid=$uid)", array());
    $result = ldap_get_entries($this->server, $search);

    // Remove the first, useless entry
    array_shift($result);

    // Person doesn't exist
    if (empty($result)) {
      return null;
    }

    // Find and append its membership status
    $result[0]['membership'] = array($this->determine_membership($uid));

    // Return a resource object
    return Models\LDAPEntry::from_result($result[0])->to_Person();
  }

  /**
   * Returns whether a user with a given uid exists
   * @param string $uid the UID of the user to find
   * @return boolean whether the user exists
   */
  public function user_exists($uid)
  {
    $search = ldap_search($this->server, getenv('LDAP_BASEDN'), "(uid=$uid)", array());
    return (ldap_count_entries($this->server, $search) > 0);
  }

  /**
   * Creates a new LDAP-entry on the server
   * @param  entry $stdClass
   * @return string the UID of the newly created entry or null on failure
   */
  public function create($entry)
  {
    // Parse the entry
    $entry = new Models\Person($entry);

    // Generate some useful attributes
    $uid = $this->find_free_uid($entry);
    $uid_number = $this->get_new_uid_number();
    $name = $entry->name();

    // Convert the model to an array for insertion in LDAP
    $entry = $entry->to_LDAPEntry()->to_array();

    // Create default attributes not supplied by the user
    $entry = array_merge([
      'uid' => $uid,
      'uidnumber' => $uid_number,
      'cn' => $name,
      'gecos' => $name,
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
      'sambasid' => 'S-1-5-21-1816619821-1419577557-1603852640-'.(1000+2*$uid_number),
      'gidNumber' => '1084',
      'sambaprimarygroupsid' => 'S-1-5-21-1816619821-1419577557-1603852640-3051',
    ], $entry);

    // Create LDAP-entry
    $success = ldap_add($this->server, "uid=$uid,ou=people,o=nieuwedelft,dc=bolkhuis,dc=nl", $entry);

    // Send an email
    $mail = new Mailer\NewPerson($entry['mail'], $uid, $entry['cn'], $entry['userpassword']);
    $mail->send();

    // Return the new user
    return $this->find($uid);
  }

  /**
   * Updates an existing LDAP-entry on the server
   * @param  string $uid the uid of the user
   * @param  Models\LDAPEntry $entry
   * @return Models\Person the created entry or null on failure
   */
  public function update($uid, $entry)
  {
    // User must exist
    if (! $this->user_exists($uid)) {
      throw new Exception('User doesn\'t exist');
    }

    // Cast to array
    $attributes = (array) $entry;

    // Only allow whitelisted attributes
    $accepted = ['firstname', 'lastname', 'initials', 'dateofbirth', 'email', 'phone', 'mobile', 'phone_parents', 'address'];
    foreach (array_keys($attributes) as $key) {
      if (! in_array($key, $accepted)) {
        throw new Exception("Setting $key is not allowed");
      }
    }

    // Rewrite to LDAPEntry
    $entry = new Models\Person($attributes);
    $entry = $entry->to_LDAPEntry()->to_array();

    // Update the LDAP server
    ldap_modify($this->server, "uid=$uid,ou=people,o=nieuwedelft,dc=bolkhuis,dc=nl", $entry);

    // Return the updated user
    return ($this->find($uid));
  }

  /*
   * Private utility functions start here
   */

  /**
   * Returns a free uid for a new user
   * @param array $data describing the user
   * @return string the free UID
   * @throws Exception if no free UID can be found
   */
  private function find_free_uid($data)
  {
    // Try sensible options first
    $options = [];

    if (! empty($data->initials)) {
      $options[] = strtolower($data->initials[0].$data->lastname);
      $options[] = strtolower($data->initials.$data->lastname);
    }
    $options[] = strtolower($data->firstname.$data->lastname);

    foreach ($options as $candidate_uid) {
      if (! $this->user_exists($candidate_uid)) {
        return $candidate_uid;
      }
    }

    // Try a numbered option
    for ($i=1; true; $i++) { 
      $candidate_uid = strtolower($data->firstname.$data->lastname).$i;
      if (! $this->user_exists($candidate_uid)) {
        return $candidate_uid;
      }
    }
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
   * Returns all members of a specific group
   * @param $dn the DN of the group to find (excluding BASE_DN)
   * @return array[Models\Person]
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
      $search = $this->ldap_find("(&(uid=$uid)(objectClass=iNetOrgPerson)(!(objectClass=gosaUserTemplate)))", array());
      if ($search !== array()) {
        array_push($members, [Models\LDAPEntry::from_result($search[0])->to_Person()]);
      }
    }

    return $members;
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
   * Returns a string indicating the users membership
   * @param  string $uid the UID of the user
   * @return string      either 'kandidaatlid', 'lid', 'oudlid' or 'geen lid'
   */
  private function determine_membership($uid)
  {
    $groups = array(
      'lid' => 'cn=leden,ou=groups,o=nieuwedelft',
      'kandidaatlid' => 'cn=kandidaatleden,ou=groups,o=nieuwedelft',
      'oudlid' => 'cn=oud-leden,ou=groups,o=nieuwedelft',
    );

    foreach($groups as $type => $group)
    {
      $dn = $group . ',' . getenv('LDAP_BASEDN');
      $query = ldap_read($this->server, $dn, '(objectClass=posixGroup)', array('memberuid'));
      if(!$query || ldap_count_entries($this->server, $query) < 1)
        throw new Exception('Group "' . $group . '" does not exist in ldap');

      $properties = ldap_get_entries($this->server, $query);
      $properties = $properties[0];

      $members = $properties['memberuid'];
      unset($members['count']);

      if(in_array($uid, $members))
        return $type;
    }

    return '';
    // Not a member
    return 'geen lid';
  }

}
