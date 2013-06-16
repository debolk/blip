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
  public function __construct($server)
  {
    $this->server = $server;
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
}

/**
 * Exceptions used in the class
 */
class LDAPInvalidUserException extends Exception {}
