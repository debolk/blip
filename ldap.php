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
      'host'              => 's0.foo.net',
      'username'          => 'CN=user1,DC=foo,DC=net',
      'password'          => 'pass1',
      'bindRequiresDn'    => true,
      'accountDomainName' => 'foo.net',
      'baseDN'            => 'OU=sales,DC=foo,DC=net',
    );
    $this->server = new Zend\Ldap\Ldap($options);
  }

  public function find_all()
  {
    throw new Exception('Method not implemented');
  }

  public function find($id)
  {
    throw new Exception('Method not implemented');
  }

  public function create($data)
  {
    throw new Exception('Method not implemented');
  }

  public function update($data)
  {
    throw new Exception('Method not implemented');
  }
}