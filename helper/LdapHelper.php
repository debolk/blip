<?php
namespace Helper;

class LdapHelper
{
  /**
   * Stores the singleton instance of this class
   * @var LdapHelper
   */
  static private $instance = null;

  /**
   * Return a singleton connection to the LDAP-server
   */
  static function Connect() {
    if(self::$instance == null)
      self::$instance = new LdapHelper;

    return self::$instance;
  }

  /**
   * Connection to the LDAP-server
   * @var resource
   */
  protected $ldap;

  /**
   * Base DN of all LDAP-entries
   * @var string
   */
  public $basedn;

  /**
   * Connect to the LDAP-server and set basic configuration
   */
  public function __construct()
  {
    $this->ldap = ldap_connect(getenv('LDAP_HOST'));
    $this->bind(getenv('LDAP_USERNAME'), getenv('LDAP_PASSWORD'));
    $this->basedn = getenv('LDAP_BASEDN');
  }

  /**
   * Escape arguments for safe searches in LDAP
   * @param  string $argument argument to escape
   * @return string           escaped string
   */
  public function escapeArgument($argument)
  {
    $sanitized=array('\\' => '\5c',
        '*' => '\2a',
        '(' => '\28',
        ')' => '\29',
        "\x00" => '\00');
    return str_replace(array_keys($sanitized),array_values($sanitized),$argument);
  }

  /**
   * Returns the full DN for a LDAP-entry
   * @param  string $uid the UID to find
   * @return string      full DN
   */
  public function getDn($uid)
  {
    $uid = $this->escapeArgument($uid);
    $users = ldap_search($this->ldap, $this->basedn, '(uid=' . $uid . ')', array('dn'));

    if(!$users || ldap_count_entries($this->ldap, $users) == 0)
        return false;
    
    $users = ldap_get_entries($this->ldap, $users);

    return $users[0]['dn'];
  }

  /**
   * Binds to the LDAP-server
   * @param  string $dn   the full DN of the user to bind to
   * @param  string $pass password
   * @return boolean      whether the bind succeeded
   */
  public function bind($dn, $pass)
  {
    if(!$dn || empty($pass))
      return false;
    return ldap_bind($this->ldap, $dn, $pass);
  }

  /**
   * Searches an LDAP-entry
   * @param  string $filter     the filter to use
   * @param  array $attributes  attributes to include in the result set
   * @param  string $basedn     the DN to search
   * @return array              a filtered array of results (without counts)
   */
  public function search($filter, $attributes = null, $basedn = null)
  {
    if($basedn == null)
      $basedn = $this->basedn;

    if($attributes == null)
      $query = ldap_search($this->ldap, $basedn, $filter);
    else
      $query = ldap_search($this->ldap, $basedn, $filter, $attributes);

    if(!$query)
      return array();

    $results = ldap_get_entries($this->ldap, $query);
    return $this->stripCounts($results);
  }

  /**
   * Gets an entry from LDAP
   * @param  string $dn          DN of the entry
   * @param  string $objectClass optional objectClass filter, default *
   * @param  array $attributes   array of attributes to return
   * @return array               filtered array of desired attributes, or false if the entry was not found
   */
  public function get($dn, $objectClass = null, $attributes = null)
  {
    $objectClass = $this->escapeArgument($objectClass);
    if($objectClass == null)
      $objectClass = '*';
    $query = '(objectClass=' . $objectClass . ')';

    if($attributes == null)
      $read = ldap_read($this->ldap, $dn, '(objectClass=' . $objectClass . ')');
    else
      $read = ldap_read($this->ldap, $dn, '(objectClass=' . $objectClass . ')', $attributes);

    if(!$query || ldap_count_entries($this->ldap, $read) < 1)
      return false;

    $results = ldap_get_entries($this->ldap, $read);
    $result = $results[0];

    return $this->stripCounts($result);
  }

  /**
   * Adds an object to ldap
   * @param string $dn          DN of the entry
   * @param array  $data        the data the object should contain
   * @return bool               TRUE on success or FALSE on failure
   */
  public function add($dn, $data)
  {
		// Remove unset parameters
		foreach($data as $key => $value)
			if(is_array($value) && count($value) == 0)
				unset($data[$key]);

    return ldap_add($this->ldap, $dn, $data);
  }

  /**
   * Modifies an object in ldap
   * @param string $dn          DN of the entry
   * @param array  $data        the changed data the object should contain
   * @return bool               TRUE on success or FALSE on failure
   */
  public function modify($dn, $data)
  {
    return ldap_modify($this->ldap, $dn, $data);
  }

  /**
   * Recursively strip all unneeded 'count' parameters from a LDAP-result
   * @param  array $array array to filter
   * @return array        filtered array
   */
  protected function stripCounts($array)
  {
    $result = array();
    foreach($array as $key => $value)
    {
      if(is_array($array[$key]))
        $result[$key] = $this->stripCounts($array[$key]);
      elseif($key !== 'count')
        $result[$key] = $value;
    }

    return $result;
  }

  /**
   * Flatten a LDAP-object
   * @param  object $ldap_object object to flatten
   * @return object              flattened object
   */
  public function flatten($ldap_object)
  {
    $result = array();
    foreach($ldap_object as $key => $value)
      if(!is_int($key))
        if(is_array($value) && count($value) == 1)
          $result[$key] = $value[0];
        else
          $result[$key] = $value;

    return $result;
  }
}
