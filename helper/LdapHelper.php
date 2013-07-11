<?php
namespace Helper;

class LdapHelper
{
  static private $instance = null;

  static function Connect() {
    if(self::$instance == null)
      self::$instance = new LdapHelper;

    return self::$instance;
  }

  protected $ldap;
  public $basedn;

  public function __construct()
  {
    $this->ldap = ldap_connect(getenv('LDAP_HOST'));
    $this->basedn = getenv('LDAP_BASEDN');
  }

  public function escapeArgument($argument)
  {
    $sanitized=array('\\' => '\5c',
        '*' => '\2a',
        '(' => '\28',
        ')' => '\29',
        "\x00" => '\00');
    return str_replace(array_keys($sanitized),array_values($sanitized),$argument);
  }

  public function getDn($uid)
  {
    $uid = $this->escapeArgument($uid);
    $users = ldap_search($this->ldap, $this->basedn, '(uid=' . $uid . ')', array('dn'));

    if(!$users || ldap_count_entries($this->ldap, $users) == 0)
        return false;
    
    $users = ldap_get_entries($this->ldap, $users);

    return $users[0]['dn'];
  }

  public function bind($dn, $pass)
  {
    if(!$dn || empty($pass))
      return false;
    return ldap_bind($this->ldap, $dn, $pass);
  }

  public function search($filter, $attributes = null, $basedn = null)
  {
    if($basedn == null)
      $basedn = $this->basedn;

    $query = ldap_search($this->ldap, $basedn, $filter, $attributes);
    if(!$query)
      return array();

    $results = ldap_get_entries($this->ldap, $query);
    return $this->stripCounts($results);
  }

  public function memberOf($groupdn, $uid)
  {
    $group = $this->get($groupdn, 'posixGroup', array('memberuid'));
    if(!$group)
      throw new Exception("Group '" . $groupdn . "' not found!");

    if(!isset($group['memberuid']))
      return false;

    if(!in_array($uid, $group['memberuid']))
      return false;

    return true;
  }

  public function get($dn, $objectClass = '*', $attributes = null)
  {
    $objectClass = $this->escapeArgument($objectClass);
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

  public function flatten($ldap_object)
  {
    $result = array();
    foreach($ldap_object as $key => $value)
      if(!is_int($key))
        $result[$key] = $value[0];

    return $result;
  }
}
