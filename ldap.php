<?php

class LDAP
{
  /**
   * The connection to the Bolk LDAP-server
   * @type Zend\Ldap\Ldap
   */
  private $server;

  /**
   * Construct a connection to the server
   */
  public function __construct()
  {
    $options = array(
      'host'              => getenv('LDAP_HOST'),
      'username'          => getenv('LDAP_USERNAME'),
      'password'          => getenv('LDAP_PASSWORD'),
      'bindRequiresDn'    => getenv('LDAP_BINDREQUIRESDN'),
      'accountDomainName' => getenv('LDAP_ACCOUNTDOMAINNAME'),
      'baseDN'            => getenv('LDAP_BASEDN'),
    );
    $this->server = new Zend\Ldap\Ldap($options);
  }

  /**
   * Find the information of every member
   * @return array[object]
   */
  public function find_all()
  {
    throw new Exception('Method not implemented');
  }

  /**
   * Find the information of a specific member
   * @param int $id the id of the member to find
   * @throws LDAPNotFoundException if the member doesn't exist
   * @return object
   */
  public function find($id)
  {
    throw new Exception('Method not implemented');
  }

  /**
   * Create a new member with the given data
   * @param array $data
   * @return the results of LDAP::find() of the new member
   */
  public function create($data)
  {
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
}